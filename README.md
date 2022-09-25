# Stripe Universal

This is a non-merchant gateway for Blesta that integrates with [Stripe Checkout](https://stripe.com/payments/checkout). 

Checkout page support all payment methods (From Alipay, WeChat, Google Pay, Apple Pay to EPS, iDEAL Giropay). 

## What it does

- Generate `Stripe/Checkout/Session` checkout link and verify&record payment if completed. 
- Partial webhook support (see below)
- Separate items for multiple invoices checkout

## Screenshots

Similar to https://marketplace.blesta.com/#/extensions/175-Stripe%20Universal

### TODO

- Support webhook events but it does not handle async payment(ACH, etc.) for now. Webhook at this stage helps to capture payments that client failed to redirect back to website 
- Refund, Void transaction
- Disable payment type in settings
- Better Redirect Pictures :)

## Install the Gateway

Upload the source code to a `/components/gateways/nonmerchant/stripe_universal/` directory within
your Blesta installation path.

For example:

```
/var/www/html/blesta/components/nonmerchant/stripe_universal/
```

3. Log in to your admin Blesta account and navigate to `> Settings > Payment Gateways`

4. Find the Stripe Universal gateway and click the "Install" button to install it

5. You're done!

### Compatibility

```
Blesta >= v4.9.0
PHP >= 7.2.0
```
