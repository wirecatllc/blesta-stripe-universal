<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests validateConnection() — Stripe API key validation.
 */
class ValidateConnectionTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        class_exists(\Stripe\Stripe::class);
        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
        $this->gateway->setMeta(['secret_key' => 'sk_test_123']);
    }

    public function testTestKeySkipsValidation()
    {
        $result = $this->gateway->validateConnection('sk_test_abc123');

        $this->assertTrue($result);
        // Should not have made any HTTP requests
        $this->assertNull(MockHttpClient::getLastRequest());
    }

    public function testLiveKeySuccess()
    {
        // Enqueue a successful Balance::retrieve response
        MockHttpClient::enqueueResponse(json_encode([
            'object' => 'balance',
            'available' => [['amount' => 1000, 'currency' => 'usd']],
        ]));

        $result = $this->gateway->validateConnection('sk_live_abc123');

        $this->assertTrue($result);
        // Should have made an HTTP request
        $this->assertNotNull(MockHttpClient::getLastRequest());
    }

    public function testLiveKeyFailure()
    {
        // Enqueue an authentication error response
        MockHttpClient::enqueueResponse(json_encode([
            'error' => [
                'type' => 'invalid_request_error',
                'message' => 'Invalid API Key provided',
            ],
        ]), 401);

        $result = $this->gateway->validateConnection('sk_live_invalid');

        $this->assertFalse($result);
    }
}
