<?php
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOTWEBDIR')) {
    define('ROOTWEBDIR', dirname(__DIR__) . DS);
}

require_once dirname(__DIR__) . DS . 'vendor' . DS . 'autoload.php';

\Stripe\ApiRequestor::setHttpClient(new MockHttpClient());

require_once dirname(__DIR__) . DS . 'stripe_universal.php';
