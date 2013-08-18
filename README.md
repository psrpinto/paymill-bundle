**WORK IN PROGRESS, DO NOT USE**

# paymill-bundle
Straight forward integration of [Paymill](http://paymill.com) payments into Symfony 2 applications. Makes your life easier in two ways:

* Credit Card form to include in your page (pictured below)
* High-level API to use in your controllers

![Credit card form screenshot](Resources/doc/form.png)

The Credit Card form can be included directly in your page (no *iframes*). It's inspired by [Stripe's Checkout](https://stripe.com/blog/stripe-checkout) and uses [jquery.payment](https://github.com/stripe/jquery.payment) and [Paymill's Bridge](https://www.paymill.com/en-gb/documentation-3/reference/paymill-bridge/). **It's completely optional**, which means you can easily roll your own and still use this bundle in the backend. If you decide to use it, you still have 100% control of how it looks.

In the backend, [JMSPaymentCoreBundle](https://github.com/schmittjoh/JMSPaymentCoreBundle) is used. This allows you to add new payment backends (e.g Paypal) with minimum changes to your code. It uses [Paymill's PHP library](https://github.com/Paymill/Paymill-PHP) under the hood.

## Features

* Plug-and-play credit card form, inspired by Stripe's Checkout (optional)
* Support for Paymill's [client resources](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#clients)

### Coming soon

* [Webhooks](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#webhooks)
* [Refunds](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#refunds)
* [Preauthorizations](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#preauthorizations)

## Setup
For installation and configuration instructions see [setup.md](Resources/doc/setup.md)

## How it works
At the end of your app's checkout workflow, you'll have a form where your customer enters his credit card information. This bundle comes packaged with a template that you can include in your page that will render such form as pictured above. You are free to change how it looks in any way you see fit by changing its markup and/or CSS.

In order for you to not have to worry about PCI compliance, the credit card information your customers enter in the form should never reach your servers. This is where [Paymill's Bridge](https://www.paymill.com/en-gb/documentation-3/reference/paymill-bridge/) comes into action: it's a small javascript library that makes an Ajax request to Paymill's servers containing the credit card information. The response to this request is a unique `token`.

You then submit the form to your server, excluding the credit card information but including the `token` returned from Paymill's server. In your controller, this `token` will be used to make a request to Paymill's API and [create a transaction](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#create-new-transaction-with), aka a Payment. (If fact, you will not make requests to Paymill's API directly, you'll be using the high-level API made available by JMSPaymentCoreBundle.) If the `transaction` was successfuly created, the money was transferred to your Paymill account and you're done.

See [paymill-bundle-example](https://github.com/fitmemes/paymill-bundle-example) for an example Symfony app that uses this bundle.

## Modifying the default form
The Credit Card form is where your customers will enter their credit card information. It consists of a [PaymillType](Form/PaymillType.php) and a [template](Resources/views/form.html.twig) that defines how each field of the form is rendered.

TODO

## Listeners
TODO