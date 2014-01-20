# paymill-bundle
Straight forward integration of [Paymill](http://paymill.com) payments into Symfony 2 applications. **See [paymill-bundle-example](https://github.com/memeoirs/paymill-bundle-example) for an example Symfony app that uses this bundle.**

![Credit card form screenshot](Resources/doc/form.png)

## Features

* Plug-and-play credit card form, inspired by Stripe's Checkout (optional)
* High level API to create payments
* Support for Paymill's [client resources](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#clients)
* Uses [Paymill's PHP library](https://github.com/Paymill/Paymill-PHP) under the hood.

### Coming soon (PRs welcome!)

* [Webhooks](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#webhooks)
* [Refunds](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#refunds)
* [Preauthorizations](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#preauthorizations)

## Setup
*This bundle uses functionality provided by [JMSPaymentCoreBundle](https://github.com/schmittjoh/JMSPaymentCoreBundle) which allows you to add new payment backends (e.g. Paypal) with minimum changes to your code. These instructions will also guide you through the installation of that bundle.*

### Installation
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

### Configuration

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

### Create database tables
JMSPaymentCoreBundle needs a few database tables so you'll have to create them. If you want to know more about the data model see [JMSPaymentCoreBundle's documentation](http://jmsyst.com/bundles/JMSPaymentCoreBundle/master/model).

If you're using [database migrations](http://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html), you can create the new tables with following commands:

    php app/console doctrine:migrations:diff
    php app/console doctrine:migrations:migrate

Or, without migrations:

    php app/console doctrine:schema:update

## Rendering the form
If you wish to use the provided credit card form, you need to include some scripts in the pages in which you plan to use this bundle. To simplify this, a Twig function is available, which you'll typically call before the closing `<body>` tag:

```twig
{{ paymill_initialize(10, 'EUR') }}
```

This will simply render the [Resources/views/init.html.twig](Resources/views/init.html.twig) template. If you need to change the output of `paymill_initialize` you can use your own template:

```yml
// app/config/config.yml
memeoirs_paymill:
    initialize_template: AcmeDemoBundle::init_paymill.html.twig
```

### Modifying the default form
TODO

## Specifying a Client
TODO

## Listeners
TODO