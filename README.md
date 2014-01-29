# paymill-bundle
Straight forward integration of [Paymill](http://paymill.com) payments into Symfony 2 applications.

![Credit card form screenshot](Resources/doc/form.png)

# Features

* Plug-and-play credit card form, inspired by Stripe's Checkout (optional)
* High level API to create payments
* CRUD access to Paymill's API from the command line using Symfony commands
* Support for Paymill's [client resources](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#clients)
* Uses [Paymill's PHP library](https://github.com/Paymill/Paymill-PHP) under the hood

## Coming soon (PRs welcome!)

* [Webhooks](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#webhooks)
* [Refunds](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#refunds)
* [Preauthorizations](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#preauthorizations)

# Setup
*This bundle uses functionality provided by [JMSPaymentCoreBundle](https://github.com/schmittjoh/JMSPaymentCoreBundle) which allows you to add new payment backends (e.g. Paypal) with minimum changes to your code. These instructions will also guide you through the installation of that bundle.*

## Installation
Add the following to your `composer.json`:

```json
{
    "require": {
        "memeoirs/paymill-bundle": "0.1.*"
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

Finally, you need to tell *Assetic* about this bundle:

```yml
// app/config.yml
assetic:
    bundles: ['AcmeSomeBundle', 'MemeoirsPaymillBundle']
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
// Acme\DemoBundle\Controller\OrdersController
public function checkoutAction ()
{
    // $order is a Acme\DemoBundle\Entity\Order
    $order = new Order;
    $order->setAmount(50);
    $order->setCurrency('EUR');

    // $form is a Memeoirs\PaymillBundle\Form\PaymillType
    $form = $this->get('form.factory')->create('jms_choose_payment_method', null, array(
        'allowed_methods' => array('paymill'),
        'default_method'  => 'paymill',
        'amount'          => $order->getAmount(),
        'currency'        => $order->getCurrency()
    ));

    return $this->render('AcmeDemoBundle::checkout.html.twig', array(
        'form'  => $form->createView(),
        'order' => $order,
    ));
}
```

The twig template:
```twig
// src/Acme/DemoBundle/Resources/views/checkout.html.twig
{{ paymill_initialize(order.amount, order.currency) }}

{# looks better with bootstrap #}
<link rel="stylesheet" type="text/css" href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css">

{% stylesheets '@MemeoirsPaymillBundle/Resources/assets/css/paymill.css' %}
  <link rel="stylesheet" type="text/css" href="{{ asset_url }}" />
{% endstylesheets %}

{% form_theme form 'MemeoirsPaymillBundle::form.html.twig' %}
<form action="{{ path('checkout', {'id': order.id}) }}"
    method="post" autocomplete="on" novalidate class="paymill well">
  {{ form_widget(form) }}

  <input type="submit" class="btn btn-success"
    value="Pay {{ order.amount }} {{ order.currency }}" />
  <div class="payment-errors"></div>
</form>
```

`paymill_initialize()` renders the [Resources/views/init.html.twig](Resources/views/init.html.twig) template. If you need to change the output of `paymill_initialize` you can use your own template:
```yml
// app/config/config.yml
memeoirs_paymill:
    initialize_template: AcmeDemoBundle::init_paymill.html.twig
```

## Handling form submission
When the user clicks the *buy* button, an Ajax request is made to paymill's servers containing the credit card information. The response to this request is a unique *token*. The form will then be submitted, excluding the credit card information but including the *token*.

You'll handle the form submission in the same controller action that renders the form:

```php
// Acme\DemoBundle\Controller\OrdersController
public function checkoutAction ()
{
    // (...)

    if ('POST' === $this->getRequest()->getMethod()) {
        $form->bind($this->getRequest());

        if ($form->isValid()) {
            // Create a PaymentInstruction and associate it with the order
            $ppc = $this->get('payment.plugin_controller');
            $ppc->createPaymentInstruction($instruction = $form->getData());
            $order->setPaymentInstruction($instruction);

            $em = $this->getDoctrine()->getManager();
            $em->persist($order);
            $em->flush($order);

            return $this->redirect($this->generateUrl('checkout_complete', array(
                'id' => $order->getId(),
            )));
        }
    }

    return $this->render('AcmeDemoBundle:::checkout.html.twig', array(
        'form'  => $form->createView(),
        'order' => $order,
    ));
}

```

## Accepting the payment
In the previous step, the *token* obtained on the client-side was saved (encrypted) in the database and the user was redirected to `checkout_complete`. In the controller action for that route, the *token* is used to tell Paymill to accept the payemnt.

```yml
// app/config/routing.yml
checkout_complete:
    pattern:  /complete/{id}
    defaults: { _controller: AcmeDemoBundle:Orders:complete }
```

```php
// Acme\DemoBundle\Controller\OrdersController
use JMS\Payment\CoreBundle\PluginController\Result;

public function completeAction ($id)
{
    $repository = $this->getDoctrine()->getManager()
            ->getRepository('MemeoirsPaymillExampleBundle:Order');
    if (!$order = $repository->find($id)) {
        throw $this->createNotFoundException("Order $id not found");
    }

    // retrieve an existing payment or create a new one
    $ppc = $this->get('payment.plugin_controller');
    $instruction = $order->getPaymentInstruction();
    if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
        $amount = $instruction->getAmount() - $instruction->getDepositedAmount();
        $payment = $ppc->createPayment($instruction->getId(), $amount);
    } else {
        $payment = $pendingTransaction->getPayment();
    }

    // the following line queries Paymill's API to accept the payment
    $result = $ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());

    if (Result::STATUS_SUCCESS === $result->getStatus()) {
        // Payment was successful. Money was transfered to you paymill account.
        // Redirect the user to a confirmation page.
        return $this->redirect($this->generateUrl('thankyou', array(
            'id' => $order->getId(),
        )));
    } else {
        throw new \RuntimeException('Transaction was not successful: '.$result->getReasonCode());
    }
}

```

## Specifying a Client
Paymill allows you to *attach* each payment to a certain [client](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#clients). To have this bundle automatically manage clients, you can pass the client information as additional data when creating the form:

```php
// Acme\DemoBundle\Controller\OrdersController
public function checkoutAction ()
{
    // ...

    $orderDescription = 'Two baskets of apples';
    $email = 'user@example.com';
    $name = 'John Doe';

    $form = $this->get('form.factory')->create('jms_choose_payment_method', null, array(
        // ...

        'predefined_data' => array(
            'paymill' => array(
                'client' => array(
                    'email'       => $email,
                    'description' => $name,
                ),
                'description' => $orderDescription
            ),
        ),
    ));

    // ...
}
```

## Changing how the form looks
TODO

# Console
*Work in progress. Currently only webhooks are supported*

The console commands give you CRUD access to Paymill's API from the command line.

## Webhooks
### List webhooks
The `paymill:webhook:list` command retrieves the list of the most recent webhooks:

    app/console paymill:webhook:list

You can filter and paginate the results using a set of filters formatted as an HTTP query string. See [here](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#list-webhooks) for the list of all available filters. To retrieve the second page of results ordered chronologically:

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

# Listeners
TODO

# License
[MIT](Resources/meta/LICENSE)