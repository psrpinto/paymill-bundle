# paymill-bundle [![Build Status](https://travis-ci.org/memeoirs/paymill-bundle.svg?branch=master)](https://travis-ci.org/memeoirs/paymill-bundle)

Straight forward integration of [Paymill](http://paymill.com) payments into Symfony 2 applications.

![Credit card form screenshot](Resources/doc/form.png)

# Features

* Plug-and-play credit card form, inspired by Stripe's Checkout (optional)
* High level API to create payments
* [Webhooks](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#webhooks)
* CRUD access to Paymill's API from the command line using Symfony commands
* Support for Paymill's [client resources](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#clients)
* Uses [Paymill's PHP library](https://github.com/Paymill/Paymill-PHP) under the hood

# Setup
*This bundle uses functionality provided by [JMSPaymentCoreBundle](https://github.com/schmittjoh/JMSPaymentCoreBundle) which allows you to add new payment backends (e.g. Paypal) with minimum changes to your code. These instructions will also guide you through the installation of that bundle.*

## Installation
Add the following to your `composer.json`:

```json
{
    "require": {
        "memeoirs/paymill-bundle": "0.3.*"
    }
}
```

You can then install the bundle and it's dependencies by running `composer update`:

    $ composer update memeoirs/paymill-bundle

Then register the new bundles in `AppKernel.php`:

```php
// app/AppKernel.php
$bundles = array(
    // ...
    new JMS\Payment\CoreBundle\JMSPaymentCoreBundle(),
    new Memeoirs\PaymillBundle\MemeoirsPaymillBundle(),
    // ...
);
```

Include `routing.yml` in your routing file (for webhooks):

```yml
// app/config/routing.yml
memeoirs_paymill:
    resource: "@MemeoirsPaymillBundle/Resources/config/routing.yml"
```

## Configuration
[JMSPaymentCoreBundle's](https://github.com/schmittjoh/JMSPaymentCoreBundle) configuration is as easy as choosing a random secret string which will be used for encrypting data. Note that if you change the secret all data encrypted with the old secret will become unreadable.

```yml
jms_payment_core:
    secret: somesecret
```

Finally, you need to specify Paymill's private and public keys. You'll need to create a Paymill account if you don't have one and retrieve it's private and public keys. Refer to [Paymill's documentation](https://www.paymill.com/en-gb/documentation-3/introduction/brief-instructions/) for information on how to accomplish this.

```yml
// app/config.yml
memeoirs_paymill:
    api_private_key: paymill_api_private_key
    api_public_key:  paymill_api_public_key
```

## Create database tables
JMSPaymentCoreBundle needs a few database tables so you'll have to create them. If you want to know more about the data model see [JMSPaymentCoreBundle's documentation](http://jmsyst.com/bundles/JMSPaymentCoreBundle/master/model).

If you're using [database migrations](http://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html), you can create the new tables with following commands:

    php app/console doctrine:migrations:diff
    php app/console doctrine:migrations:migrate

Or, without migrations:

    php app/console doctrine:schema:update

# Usage
*If you wish to better understand how this will fit in your application see [paymill-bundle-example](https://github.com/memeoirs/paymill-bundle-example) for an example Symfony app that uses this bundle. [JMSPaymentCoreBundle's documentation](http://jmsyst.com/bundles/JMSPaymentCoreBundle) is also useful.*

## Rendering the form
You'll need a new route:

```yml
// app/config/routing.yml
checkout:
    pattern:  /
    defaults: { _controller: AcmeDemoBundle:Orders:checkout }
```

And a controller action to render the form:

```php
namespace Acme\DemoBundle\Controller;

use Acme\DemoBundle\Entity\Order;
use Memeoirs\PaymillBundle\Controller\PaymillController;

class OrdersController extends PaymillController
{
    public function checkoutAction ()
    {
        $em = $this->getDoctrine()->getManager();

        // In a real world app, instead of instantiating an Order, you will
        // probably retrieve it from the database
        $order = new Order;
        $order->setAmount(50);
        $order->setCurrency('EUR');

        $form = $this->getPaymillForm($order->getAmount(), $order->getCurrency());

        return $this->render('AcmeDemoBundle::checkout.html.twig', array(
            'form'  => $form->createView(),
            'order' => $order,
        ));
    }
}
```

The twig template:
```twig
// src/Acme/DemoBundle/Resources/views/checkout.html.twig
{{ paymill_initialize(order.amount, order.currency) }}

{# looks better with bootstrap #}
<link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css">
<link rel="stylesheet" type="text/css" href="{{ asset('bundles/memeoirspaymill/css/paymill.css') }}">

{% form_theme form 'MemeoirsPaymillBundle::form.html.twig' %}
<form action="{{ path('checkout', {'id': order.id}) }}"
    method="post" autocomplete="on" novalidate class="paymill well">
  {{ form_widget(form) }}

  <input type="submit" class="btn btn-success"
    value="Pay {{ order.amount }} {{ order.currency }}" />

  {{ form_errors(form) }}
</form>
```

`paymill_initialize()` renders the [Resources/views/init.html.twig](Resources/views/init.html.twig) template. If you need to change the output of `paymill_initialize` you can use your own template:
```yml
// app/config/config.yml
memeoirs_paymill:
    initialize_template: AcmeDemoBundle::init_paymill.html.twig
```

## Accepting the payment
When the user clicks the *buy* button, an Ajax request is made to paymill's servers containing the credit card information. The response to this request is a unique *token*. The form is then submitted through Ajax, excluding the credit card information but including the *token*.

You'll handle the form submission in the same controller action that renders the form:

```php
// Acme\DemoBundle\Controller\OrdersController
public function checkoutAction ()
{
    // (...)

    if ('POST' === $this->getRequest()->getMethod()) {
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            $instruction = $this->createPaymentInstruction($form);
            $order->setPaymentInstruction($instruction);
            $em->persist($order);
            $em->flush($order);

            // completePayment triggers a call to Paymill's API that creates the
            // the payment. It returns a JSON response that indicates success or
            // error. In the case of a successful operation the user will be
            // redirected (in javascript) to 'orders_thankyou'.
            return $this->completePayment($instruction, 'orders_thankyou', array(
                'id' => $order->getId()
            ));
        }
    }

    return $this->render('AcmeDemoBundle:::checkout.html.twig', array(
        'form'  => $form->createView(),
        'order' => $order,
    ));
}

```

## Specifying a Client
Paymill allows you to *attach* each payment to a certain [client](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#clients). To have this bundle automatically manage clients, you can pass the client information as additional data when creating the form:

```php
// Acme\DemoBundle\Controller\OrdersController
public function checkoutAction ()
{
    // ...

    $form = $this->getPaymillForm($order->getAmount(), $order->getCurrency(), array(
        'client' => array(
            'email' => 'user2@example.com',
            'description' => 'John Doe',
        ),
        'description' => 'Two baskets of apples'
    ));

    // ...
}
```

## Changing how the form looks
TODO

# Webhooks
A webhook is a controller action to which Paymill POSTs events. As of now, this bundle is able to automatically handle  notifications for the following event types: `transaction.succeeded` and `refund.succeeded`.

The only thing you need to do is create a webhook using the provided console command (see the *Console* section below):

    app/console paymill:webhook:create --url=https://myapp.com/paymill/hook \
        --event=transaction.succeeded --event=refund.succeeded

Everytime a successful transaction or refund happens, Paymill will post a request to the URL you provided, which maps to the [MemeoirsPaymillBundle:Webhooks:hook](Controller/WebhooksController.php) controller action (make sure you included [routing.yml](Resources/config/routing.yml) in your routing file).

# Listeners
TODO

# Console
*Work in progress. Currently only webhooks are supported*

The console commands give you CRUD access to Paymill's API from the command line.

## Webhooks
### List webhooks
The `paymill:webhook:list` command retrieves the list of the most recent webhooks:

    app/console paymill:webhook:list

You can filter and paginate the results using a set of filters formatted as a HTTP query string. See [here](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#list-webhooks) for the list of all available filters. To retrieve the second page of results ordered chronologically:

    app/console paymill:webhook:list "count=10&offset=10&order=created_at_asc"

### Create a webhook
The `paymill:webhook:create` command creates a new URL or Email webhook. For more information about webhooks see [Paymill's API documentation](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#document-webhooks).

To create a URL webhook specify the `--url` option:

    app/console paymill:webhook:create --url=https://myapp.com/some-paymil-webhook

If instead you wish to create an Email webhook specify the `--email` option:

    app/console paymill:webhook:create --email=payment@example.com

You can specifiy the events that trigger this webhook using multiple `--event` options. If no `--event` option is used, all events will be subescribed to. See [here](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#events) for the list of available event types.

    app/console paymill:webhook:create --url=... --event=transaction.succeeded --event=refund.succeeded

To create an inactive webhook use the `--disable` option:

    app/console paymill:webhook:create --url=... --disable

### Delete a webhook
The `paymill:webhook:delete` command deletes webhooks. It takes a series of space-separated webhook ids as arguments:

    app/console paymill:webhook:delete hook_c945c39154ab3b3e1ef6 hook_b4ae6600de00b9f69afa

# License
[MIT](Resources/meta/LICENSE)
