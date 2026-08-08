# Stripe Universal

This is a non-merchant gateway for Blesta that integrates with [Stripe Checkout](https://stripe.com/payments/checkout). 

Checkout page support all payment methods (From Alipay, WeChat, Google Pay, Apple Pay to EPS, iDEAL Giropay). 

## What it does

- Generate `Stripe/Checkout/Session` checkout link and verify&record payment if completed
- Webhook support for checkout completion and async payment methods (ACH, SEPA, etc.)
- Separate items for multiple invoices checkout
- Refund support (full and partial) via Stripe Refund API
- Void transaction support (processed as full refund)

### TODO

- Disable payment type in settings

## Install the Gateway

Upload the source code to `/components/gateways/nonmerchant/stripe_universal/` directory within your Blesta installation path.

For example:

```
/var/www/html/blesta/components/nonmerchant/stripe_universal/
```

1. Log in to your admin Blesta account
2. Navigate to `Settings > Payment Gateways`
3. Find the Stripe Universal gateway
4. Click the "Install" button
5. Configure the Stripe API secret and webhook signing secret

The webhook endpoint must subscribe to `checkout.session.completed`,
`checkout.session.async_payment_succeeded`, and
`checkout.session.async_payment_failed` events.

After upgrading an existing installation to 1.1.0, save the gateway settings
once to encrypt a webhook secret that was stored by an earlier release.

### Checkout Session lifecycle

Stripe recommends creating a Checkout Session for each payment attempt. This
gateway follows that model because Blesta's `buildProcess` interface provides
the contact, amount, invoices, and redirect options, but no durable
payment-attempt identifier or gateway-owned storage. Reusing a session by
matching those values could send two legitimate attempts to the same session.

Creating a session uses an idempotency key and two Stripe SDK-managed network
retries. That protects a temporary transport failure during one `buildProcess`
call, but intentionally does not reuse sessions across later visits or retries
initiated by Blesta. Safe cross-request reuse requires a Blesta payment-attempt
ID, persistent session storage, and validation of the invoice set, amount,
currency, customer, session status, and expiration.

## Customize the plugin

Here are some tips if you want to customize/setup this plugin

### Configuring Redirect Image

Credits: [@xboxfly15](https://github.com/xboxfly15)

We have two versions available: one with Alipay, and one without it.

If you would like to use the one without Alipay, rename the `views/default/images/button-no_alipay.png` to `button.png`. Possibly this would be an option in the setting in the future.

Raw SVG file availble in `views/default/images/svg`

### Compatibility

```
Blesta >= v4.9.0
PHP >= 7.2.0
```
