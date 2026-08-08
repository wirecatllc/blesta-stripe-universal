<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests validate() — webhook handling.
 * Covers commit 4cd8bbf (payload checking).
 */
class ValidateTest extends TestCase
{
    private $gateway;
    private $webhookSecret = 'whsec_test_secret';

    protected function setUp(): void
    {
        class_exists(\Stripe\Stripe::class);
        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
        $this->gateway->setMeta([
            'secret_key' => 'sk_test_123',
            'webhook_secret' => $this->webhookSecret,
        ]);
    }

    /**
     * Helper: generate a valid Stripe webhook signature header.
     */
    private function generateSignatureHeader($payload, $secret, $timestamp = null)
    {
        $timestamp = $timestamp ?: time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        return "t={$timestamp},v1={$signature}";
    }

    /**
     * Helper: set php://input content for validate().
     * We use a custom stream wrapper to override php://input.
     */
    private function setPhpInput($content)
    {
        // Store for the stream wrapper
        PhpInputStreamWrapper::$content = $content;

        // Register our wrapper (unregister first if already registered)
        if (in_array('php', stream_get_wrappers())) {
            stream_wrapper_unregister('php');
        }
        stream_wrapper_register('php', PhpInputStreamWrapper::class);
    }

    protected function tearDown(): void
    {
        // Restore the default php:// wrapper
        if (in_array('php', stream_get_wrappers())) {
            stream_wrapper_unregister('php');
        }
        stream_wrapper_restore('php');

        // Clean up $_SERVER
        unset($_SERVER['HTTP_STRIPE_SIGNATURE']);
    }

    public function testValidWebhookWithSignature()
    {
        $payload = json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_xxx',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'status' => 'complete',
                    'currency' => 'usd',
                    'amount_total' => 1050,
                    'payment_intent' => 'pi_test_xxx',
                    'metadata' => [
                        'client_id' => 42,
                        'invoices' => base64_encode(serialize([['id' => 1, 'amount' => 10.50]])),
                    ],
                ],
            ],
        ]);

        $this->setPhpInput($payload);
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->generateSignatureHeader($payload, $this->webhookSecret);

        $result = $this->gateway->validate([], []);

        $this->assertEquals('approved', $result['status']);
        $this->assertEquals(42, $result['client_id']);
    }

    public function testInvalidSignature()
    {
        $payload = json_encode(['id' => 'evt_test', 'type' => 'checkout.session.completed']);

        $this->setPhpInput($payload);
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->generateSignatureHeader($payload, 'wrong_secret');

        $result = $this->gateway->validate([], []);

        $this->assertEquals([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('event', $errors);
        $this->assertEquals('invalid_signature', $errors['event']['internal']);
    }

    public function testMissingSignatureHeader()
    {
        $payload = json_encode(['id' => 'evt_test', 'type' => 'checkout.session.completed']);
        $this->setPhpInput($payload);

        $result = $this->gateway->validate([], []);

        $this->assertSame([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertSame('invalid_signature', $errors['event']['internal']);
    }

    public function testInvalidPayload()
    {
        $this->setPhpInput('not valid json {{{');
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = 't=12345,v1=invalidsig';

        $result = $this->gateway->validate([], []);

        $this->assertEquals([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('event', $errors);
        // Could be invalid_signature or invalid_payload depending on which fails first
        $this->assertContains($errors['event']['internal'], ['invalid_signature', 'invalid_payload']);
    }

    public function testWebhookWithoutSecret()
    {
        $this->gateway->setMeta([
            'secret_key' => 'sk_test_123',
            // no webhook_secret
        ]);

        $payload = '{"id":"evt_test","type":"checkout.session.completed","data":{"object":{"id":"cs_xxx"}}}';
        $this->setPhpInput($payload);

        $result = $this->gateway->validate([], []);

        $this->assertSame([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertSame('missing_secret', $errors['event']['internal']);
    }

    public function testCheckoutSessionCompletedEvent()
    {
        $payload = json_encode([
            'id' => 'evt_test_route',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_routed',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'status' => 'complete',
                    'currency' => 'usd',
                    'amount_total' => 500,
                    'payment_intent' => 'pi_test_routed',
                    'metadata' => [
                        'client_id' => 99,
                        'invoices' => base64_encode(serialize([['id' => 1, 'amount' => 5.00]])),
                    ],
                ],
            ],
        ]);

        $this->setPhpInput($payload);
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->generateSignatureHeader($payload, $this->webhookSecret);

        $result = $this->gateway->validate([], []);

        // Verifies event type routing to handleCheckoutSession
        $this->assertEquals('approved', $result['status']);
        $this->assertEquals(99, $result['client_id']);
    }

    public function testUnhandledEventType()
    {
        $payload = json_encode([
            'id' => 'evt_test_456',
            'object' => 'event',
            'type' => 'payment_intent.created',
            'data' => ['object' => ['id' => 'pi_xxx']],
        ]);

        $this->setPhpInput($payload);
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->generateSignatureHeader($payload, $this->webhookSecret);

        $result = $this->gateway->validate([], []);

        $this->assertEquals([], $result);
    }

    public function testCheckoutEventRejectsUnexpectedObjectType()
    {
        $payload = json_encode([
            'id' => 'evt_test_wrong_object',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'pi_wrong_object',
                    'object' => 'payment_intent',
                    'description' => 'sensitive@example.com',
                ],
            ],
        ]);

        $this->setPhpInput($payload);
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->generateSignatureHeader($payload, $this->webhookSecret);

        $result = $this->gateway->validate([], []);

        $this->assertSame([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertSame('invalid_object', $errors['event']['internal']);
        $logs = $this->gateway->getLogEntries();
        $this->assertSame(
            serialize([
                'event_id' => 'evt_test_wrong_object',
                'event_type' => 'checkout.session.completed',
            ]),
            end($logs)['data']
        );
    }

    public function testCheckoutEventRejectsMissingObject()
    {
        $payload = json_encode([
            'id' => 'evt_test_missing_object',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [],
        ]);

        $this->setPhpInput($payload);
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->generateSignatureHeader($payload, $this->webhookSecret);

        $result = $this->gateway->validate([], []);

        $this->assertSame([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertSame('invalid_object', $errors['event']['internal']);
    }

    public function testAsyncPaymentSucceededEvent()
    {
        $payload = json_encode([
            'id' => 'evt_test_async_ok',
            'object' => 'event',
            'type' => 'checkout.session.async_payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'cs_test_async_ok',
                    'object' => 'checkout.session',
                    'payment_status' => 'paid',
                    'status' => 'complete',
                    'currency' => 'usd',
                    'amount_total' => 3000,
                    'payment_intent' => 'pi_test_async_ok',
                    'metadata' => [
                        'client_id' => 77,
                        'invoices' => base64_encode(serialize([['id' => 5, 'amount' => 30.00]])),
                    ],
                ],
            ],
        ]);

        $this->setPhpInput($payload);
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->generateSignatureHeader($payload, $this->webhookSecret);

        $result = $this->gateway->validate([], []);

        $this->assertEquals('approved', $result['status']);
        $this->assertEquals(77, $result['client_id']);
        $this->assertEquals(30.00, $result['amount']);
    }

    public function testAsyncPaymentFailedEvent()
    {
        $payload = json_encode([
            'id' => 'evt_test_async_fail',
            'object' => 'event',
            'type' => 'checkout.session.async_payment_failed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_async_fail',
                    'object' => 'checkout.session',
                    'payment_status' => 'unpaid',
                    'status' => 'complete',
                    'currency' => 'usd',
                    'amount_total' => 5000,
                    'payment_intent' => 'pi_test_async_fail',
                    'metadata' => [
                        'client_id' => 88,
                        'invoices' => base64_encode(serialize([['id' => 7, 'amount' => 50.00]])),
                    ],
                ],
            ],
        ]);

        $this->setPhpInput($payload);
        $_SERVER['HTTP_STRIPE_SIGNATURE'] = $this->generateSignatureHeader($payload, $this->webhookSecret);

        $result = $this->gateway->validate([], []);

        $this->assertEquals('declined', $result['status']);
        $this->assertEquals(88, $result['client_id']);
        $this->assertEquals(50.00, $result['amount']);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('payment_status', $errors);
    }
}

/**
 * Custom stream wrapper to override php://input for testing.
 * Handles php://input with configurable content; delegates all other
 * php:// paths (stdout, stderr, memory, temp) to avoid breaking PHPUnit.
 */
class PhpInputStreamWrapper
{
    public static $content = '';
    private $position = 0;
    private $internalStream = null;
    public $context;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $host = parse_url($path, PHP_URL_HOST);

        if ($host === 'input') {
            $this->position = 0;
            return true;
        }

        // For any other php:// path, delegate to a memory stream
        // to avoid breaking PHPUnit internals
        $this->internalStream = fopen('php://memory', $mode);
        return $this->internalStream !== false;
    }

    public function stream_read($count)
    {
        if ($this->internalStream) {
            return fread($this->internalStream, $count);
        }
        $data = substr(self::$content, $this->position, $count);
        $this->position += strlen($data);
        return $data;
    }

    public function stream_write($data)
    {
        if ($this->internalStream) {
            return fwrite($this->internalStream, $data);
        }
        return strlen($data);
    }

    public function stream_eof()
    {
        if ($this->internalStream) {
            return feof($this->internalStream);
        }
        return $this->position >= strlen(self::$content);
    }

    public function stream_stat()
    {
        return ['size' => strlen(self::$content)];
    }

    public function stream_set_option($option, $arg1, $arg2)
    {
        return true;
    }

    public function stream_close()
    {
        if ($this->internalStream) {
            fclose($this->internalStream);
        }
    }
}
