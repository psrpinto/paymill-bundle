# Setup
Note that this bundle uses functionality provided by [JMSPaymentCoreBundle](https://github.com/schmittjoh/JMSPaymentCoreBundle). These instructions will also guide you through the installation of that bundle.

## Installation
Add the following to your `composer.json`:

```json
{
    "require": {
        "fitmemes/paymill-bundle": "@dev"
    }
}
```

You can then install the bundle and it's dependencies by running Composer’s `update` command from the directory where your `composer.json` file is located:

    $ php composer.phar update fitmemes/paymill-bundle

Then register the new bundles in `AppKernel.php`:

```php
// app/AppKernel.php
$bundles = array(
    // ...
    new JMS\Payment\CoreBundle\JMSPaymentCoreBundle(),
    new Fm\PaymentPaymillBundle\FmPaymentPaymillBundle(),
    // ...
);
```

If you wish to use the included *Credit Card form*, you'll have to tell *assetic* about this bundle:

```yml
// app/config.yml
assetic:
    bundles: ['AcmeSomeBundle', 'FmPaymentPaymillBundle']
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
fm_payment_paymill:
    api_private_key: paymill_api_private_key
    api_public_key:  paymill_api_public_key
```

## Create database tables
JMSPaymentCoreBundle needs a few database tables so you'll have to create them. If you want to know more about the data model see [JMSPaymentCoreBundle's documentation](http://jmsyst.com/bundles/JMSPaymentCoreBundle/master/model).

If you're using [database migrations](http://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html), you can create the new tables with following commands:

    php app/console doctrine:migrations:diff
    php app/console doctrine:migrations:migrate

Or, if you're not using migrations:

    php app/console doctrine:schema:update


## Initialize javascript
You need to include some scripts in the pages in which you plan on using this bundle. To simplify this, a Twig function is available, which you'll typically call before closing the `<body>` tag:

```twig
{{ paymill_initialize() }}
```

This will simply render the [Resources/views/init.html.twig](../../Resources/views/init.html.twig) template. If you have special requirements, instead of calling `paymill_initialize()` you can simply copy the contents of the template and paste it in your page, modifying it as you see fit.