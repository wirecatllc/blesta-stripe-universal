<?php
// Errors
$lang['StripeUniversal.!error.auth'] = 'The gateway could not authenticate.';
$lang['StripeUniversal.!error.secret_key.empty'] = 'Please enter a Secret Key.';
$lang['StripeUniversal.!error.secret_key.valid'] = 'Unable to connect to the Stripe API using the given Secret Key.';

$lang['StripeUniversal.!error.invalid_request_error'] = 'The payment gateway returned an error when processing the request.';

$lang['StripeUniversal.!error.payment_in_progress'] = 'Payment gateway indicates that the payment has successfully captured, while it is still processing. System will automatic update status once finished.';
$lang['StripeUniversal.!error.payment_not_received'] = 'Payment gateway shows that you didn\'t finished the transaction. Please try again.';
$lang['StripeUniversal.!error.payment_expired'] = 'Payment gateway returns that the current transaction has expired. Please re-checkout';

$lang['StripeUniversal.!error.payment_canceled'] = 'Payment gateway returns that the current transaction has canceled. Please re-checkout';
$lang['StripeUniversal.!error.session_id.missing'] = 'Return URL missing session ID. If your checkout process has finished, please wait for system validation.';
$lang['StripeUniversal.!error.payment_expired'] = 'Payment gateway returns that the current transaction has expired. Please re-checkout';
$lang['StripeUniversal.!error.metadata.missing'] = 'Response is missing metadata, please open a support ticket for this transaction.';
$lang['StripeUniversal.!error.metadata.missing_client_id'] = 'Payment gateway returns invalid metadata, please open a support ticket for this transaction.';

$lang['StripeUniversal.name'] = 'Stripe Payments';
$lang['StripeUniversal.description'] = 'Uses Stripe Elements and the Payment Request API to automatically handle 3D Secure and SCA to send credit cards directly through Stripe';


// Settings
$lang['StripeUniversal.publishable_key'] = 'API Publishable Key';
$lang['StripeUniversal.secret_key'] = 'API Secret Key';
$lang['StripeUniversal.tooltip_publishable_key'] = 'Your API Publishable Key is specific to either live or test mode. Be sure you are using the correct key.';
$lang['StripeUniversal.tooltip_secret_key'] = 'Your API Secret Key is specific to either live or test mode. Be sure you are using the correct key.';

$lang['StripeUniversal.webhook'] = 'Stripe Webhook';
$lang['StripeUniversal.webhook_note'] = 'It is recommended to configure the following url as a Webhook for "payment_intent" events in your Stripe account.';


$lang['StripeUniversal.heading_migrate_accounts'] = 'Migrate Old Payment Accounts';
$lang['StripeUniversal.text_accounts_remaining'] = 'Accounts Remaining: %1$s'; // Where %1$s is the number of accounts yet to be migrated
$lang['StripeUniversal.text_migrate_accounts'] = 'You can automatically migrate payment accounts stored offsite by the old Stripe gateway over to this Stripe Payments gateway. Accounts that are not stored offsite must be migrated by manually creating new payment accounts. In order to prevent timeouts migrations will be done in batches of %1$s. Run this as many times as needed to migrate all payment accounts.'; // Where %1$s is the batch size
$lang['StripeUniversal.warning_migrate_accounts'] = 'Do not uninstall the old Stripe gateway until you finish using this migration tool. Doing so will make the tool inaccessible.';
$lang['StripeUniversal.migrate_accounts'] = 'Migrate Accounts';

// Charge description
$lang['StripeUniversal.charge_description_default'] = 'Charge for specified amount';
$lang['StripeUniversal.charge_description'] = 'Charge for %1$s'; // Where %1$s is a comma seperated list of invoice ID display codes
