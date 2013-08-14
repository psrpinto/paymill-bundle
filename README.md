**WORK IN PROGRESS, DO NOT USE**

# paymill-bundle
Straight forward integration of [Paymill](http://paymill.com) payments into Symfony 2 applications. Makes your life easier in two ways:

* Credit Card form to include in your page (pictured below)
* High-level API to use in your controllers

![Credit card form screenshot](Resources/doc/form.png)

The Credit Card form can be included directly in your page (no *iframes*). It's inspired by [Stripe's Checkout](https://stripe.com/blog/stripe-checkout) and uses [jquery.payment](https://github.com/stripe/jquery.payment) and [Paymill's Bridge](https://www.paymill.com/en-gb/documentation-3/reference/paymill-bridge/). **It's completely optional**, which means that you can easily roll your own and still use this bundle in the backend. If you decide to use it, you still have 100% control of how it looks using plain CSS.

In the backend, this bundle uses [JMSPaymentCoreBundle](https://github.com/schmittjoh/JMSPaymentCoreBundle). This allows you to add new payment backends (e.g Paypal) with minimum changes to your code. It uses [Paymill's PHP library](https://github.com/Paymill/Paymill-PHP) under the hood.

## Features

* Plug-and-play credit card form, inspired by Stripe's Checkout (optional)
* Support for Paymill's [client resources](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#clients)

### Coming soon

* [Webhooks](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#webhooks)
* [Refunds](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#refunds)
* [Preauthorizations](https://www.paymill.com/it-it/documentation-3/reference/api-reference/#preauthorizations)
* 3-D Secure
* Unit tests/travis
* Tags/releases, packagist
* License
* PR to JMSPaymentCoreBundle

## Setup
For installation and configuration instructions see [setup.md](Resources/doc/setup.md)

## How it works
TODO

## Credit card form
TODO

## Accepting a payment
TODO

## Listeners
TODO