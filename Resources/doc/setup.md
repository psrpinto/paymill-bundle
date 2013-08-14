# Setup
Note that this bundle uses functionality provided by [JMSPaymentCoreBundle](https://github.com/schmittjoh/JMSPaymentCoreBundle). These instructions will also guide through the installation of that bundle.

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

```
$ php composer.phar update fitmemes/paymill-bundle
```

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