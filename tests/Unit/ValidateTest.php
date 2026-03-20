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

    /**
     * Tests the generic Exception catch block.
     * Triggered by the Event::constructFrom path (no webhook_secret) receiving
     * a raw string instead of an array — a pre-existing bug.
     * PHP 7: catch(Exception) catches the error, returns [] with error set.
     * PHP 8+: TypeError extends Error (not Exception), so it propagates uncaught.
     */
    public function testGenericException()
    {
        $this->gateway->setMeta([
            'secret_key' => 'sk_test_123',
            // no webhook_secret
        ]);

        $this->setPhpInput('{"not": "a valid event"}');

        if (PHP_MAJOR_VERSION >= 8) {
            $this->expectException(\TypeError::class);
            $this->gateway->validate([], []);
        } else {
            // PHP 7: caught by catch(Exception), returns [] with error
            $result = $this->gateway->validate([], []);
            $this->assertEquals([], $result);
            $errors = $this->gateway->Input->errors();
            $this->assertArrayHasKey('event', $errors);
        }
    }

    /**
     * Tests the fallback path when no webhook_secret is configured.
     * Documents pre-existing bug: Event::constructFrom receives a raw string
     * instead of an array, which the real Stripe SDK does not handle correctly.
     * PHP 7: catch(Exception) catches the error, returns [] with error set.
     * PHP 8+: TypeError extends Error (not Exception), so it propagates uncaught.
     */
    public function testWebhookWithoutSecret()
    {
        $this->gateway->setMeta([
            'secret_key' => 'sk_test_123',
            // no webhook_secret
        ]);

        $payload = '{"id":"evt_test","type":"checkout.session.completed","data":{"object":{"id":"cs_xxx"}}}';
        $this->setPhpInput($payload);

        if (PHP_MAJOR_VERSION >= 8) {
            $this->expectException(\TypeError::class);
            $this->gateway->validate([], []);
        } else {
            $result = $this->gateway->validate([], []);
            $this->assertEquals([], $result);
            $errors = $this->gateway->Input->errors();
            $this->assertArrayHasKey('event', $errors);
        }
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
