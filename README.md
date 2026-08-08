# Stripe Universal

This is a non-merchant gateway for Blesta that integrates with [Stripe Checkout](https://stripe.com/payments/checkout). 

Stripe Checkout can present cards, bank payments, wallets, vouchers, buy-now-pay-later services, and other local payment methods enabled for the account. See the complete categorized list below.

## What it does

- Generate `Stripe/Checkout/Session` checkout link and verify&record payment if completed
- Webhook support for checkout completion and async payment methods (ACH, SEPA, etc.)
- Separate items for multiple invoices checkout
- Refund support (full and partial) via Stripe Refund API
- Void transaction support (processed as full refund)

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

### Payment Methods

This gateway intentionally does not send Stripe's `payment_method_types` parameter when it creates a Checkout Session. Configure the payment methods offered to customers in the Stripe Dashboard; Stripe then applies the account settings and eligibility rules for each session.

Stripe currently documents Checkout support for the following payment methods:

| Family | Payment methods |
| --- | --- |
| Cards | Cards, including eligible global and local card networks and South Korean cards |
| Bank debits | Instant Bank Payments, ACH Direct Debit, Bacs Direct Debit, Australia BECS Direct Debit, New Zealand BECS Direct Debit, pre-authorized debit in Canada (ACSS), SEPA Direct Debit |
| Bank redirects | Bancontact, BLIK, EPS, FPX, iDEAL / Wero, P24, Pay by Bank, TWINT |
| Bank transfers | USD, SEPA, UK, Japan (Furikomi), and Mexico bank transfers |
| Buy now, pay later | Affirm, Afterpay / Clearpay, Alma, Billie, Capchase Pay, Klarna, Kriya, Mondu, Scalapay, SeQura, Sunbit, Zip |
| Real-time payments | PayNow, PayTo, Pix, PromptPay, Swish, UPI |
| Vouchers | Boleto, Konbini, Multibanco, OXXO |
| Wallets | Alipay, Amazon Pay, Apple Pay, Cash App Pay, Google Pay, GrabPay, Kakao Pay, Link, MB WAY, MobilePay, Naver Pay, PayPal, PayPay, PAYCO, Revolut Pay, Samsung Pay, Satispay, Vipps, WeChat Pay |
| Other | Stablecoin and crypto payments; eligible custom payment methods |

Availability depends on the Stripe account's business location and capabilities,
the customer's location, currency, amount, Checkout mode, device, and other
eligibility rules. Consult Stripe's current
[Checkout payment-method support matrix](https://docs.stripe.com/payments/payment-methods/payment-method-support)
before promising a particular method to customers.

[Giropay](https://support.stripe.com/questions/availability-of-giropay-june-2024-update)
appeared in older versions of this README, but Stripe stopped accepting Giropay
payments on June 30, 2024 and recommends removing it from integrations.

An in-plugin allow-list is not provided. Add a code-level override only when payment methods must vary for individual transactions, rather than for the account as a whole.

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
