# Stripe Universal

This is a non-merchant gateway for Blesta that integrates with [Stripe Checkout](https://stripe.com/payments/checkout). 

Checkout page support all payment methods (From Alipay, WeChat, Google Pay, Apple Pay to EPS, iDEAL Giropay). 

**This project is not affiliated/related to [Code Cats Ltd](https://code-cats.com/). Any issue of this project should be reported using [Github Issues](https://github.com/wirecatllc/blesta-stripe-universal/issues). There is NO Discord/Telegram Support Group.**

## What it does

- Generate `Stripe/Checkout/Session` checkout link and verify&record payment if completed. 
- Partial webhook support (see below)
- Separate items for multiple invoices checkout
- A nice redirect image provided by [@xboxfly15](https://github.com/xboxfly15)

### TODO

- Support webhook events but it does not handle async payment(ACH, etc.) for now. Webhook at this stage helps to capture payments that client failed to redirect back to website 
- Refund, Void transaction
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
5. Profit!

### Configuring Redirect Image

We have two version here: one with Alipay, and one without it.

If you would like to use the one without Alipay, rename the `views/default/images/button-no_alipay.png` to `button.png`. Possibly this would be an option in the setting in the future.

### Compatibility

```
Blesta >= v4.9.0
PHP >= 7.2.0
```
