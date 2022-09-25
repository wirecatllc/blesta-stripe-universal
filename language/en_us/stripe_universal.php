<?php
// Errors
$lang['StripeUniversal.!error.auth'] = 'The gateway could not authenticate.';
$lang['StripeUniversal.!error.secret_key.empty'] = 'Please enter a Secret Key.';
$lang['StripeUniversal.!error.secret_key.valid'] = 'Unable to connect to the Stripe API using the given Secret Key.';

$lang['StripeUniversal.!error.invalid_request_error'] = 'The payment gateway returned an error when processing the request.';

$lang['StripeUniversal.!error.payment_in_progress'] = 'Payment gateway indicates that the payment has successfully captured, while it is still processing. System will automatic update status once finished.';
$lang['StripeUniversal.!error.payment_not_received'] = 'Payment gateway shows that you didn\'t finished the transaction. Please try again.';
$lang['StripeUniversal.!error.payment_expired'] = 'Payment gateway returns that the current transaction has expired. Please re-checkout';

// Payment Status
$lang['StripeUniversal.!error.payment_canceled'] = 'Payment gateway returns that the current transaction has canceled. Please re-checkout';
$lang['StripeUniversal.!error.session_id.missing'] = 'Return URL missing session ID. If your checkout process has finished, please wait for system validation.';
$lang['StripeUniversal.!error.payment_expired'] = 'Payment gateway returns that the current transaction has expired. Please re-checkout';
$lang['StripeUniversal.!error.metadata.missing'] = 'Response is missing metadata, please open a support ticket for this transaction.';
$lang['StripeUniversal.!error.metadata.missing_client_id'] = 'Payment gateway returns invalid metadata, please open a support ticket for this transaction.';

$lang['StripeUniversal.name'] = 'Stripe Universal';
$lang['StripeUniversal.description'] = 'Uses Stripe Checkout to process payments.';

// Settings
$lang['StripeUniversal.secret_key'] = 'API Secret Key';
$lang['StripeUniversal.test_key_detected'] = 'You are currently using Test Key! Change to Live key once you are ready.';
$lang['StripeUniversal.tooltip_secret_key'] = 'Your API Secret Key is specific to either live or test mode. Be sure you are using the correct key.';

$lang['StripeUniversal.webhook_secret'] = 'Webhook Secret';
$lang['StripeUniversal.tooltip_webhook_secret'] = 'When set, Gateway will try to verify the webhook request using the given secret.';

$lang['StripeUniversal.webhook'] = 'Stripe Webhook';
$lang['StripeUniversal.webhook_note'] = 'It is recommended to configure the following url as a Webhook for "checkout.session.completed" events in your Stripe account.';

// Charge description
$lang['StripeUniversal.charge_description_default'] = 'Charge for specified amount';
$lang['StripeUniversal.charge_description'] = 'Charge for %1$s'; // Where %1$s is a comma seperated list of invoice ID display codes
