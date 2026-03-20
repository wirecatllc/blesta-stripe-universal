<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the success() method — the return-from-Stripe flow.
 */
class SuccessTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        // Ensure the Stripe class is autoloaded so loadApi() won't re-require init.php
        class_exists(\Stripe\Stripe::class);

        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
        $this->gateway->setMeta(['secret_key' => 'sk_test_123']);
    }

    public function testSuccessWithSessionId()
    {
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'cs_test_abc',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'status' => 'complete',
            'currency' => 'usd',
            'amount_total' => 2500,
            'payment_intent' => 'pi_test_abc',
            'metadata' => [
                'client_id' => 7,
                'invoices' => base64_encode(serialize([['id' => 5, 'amount' => 25.00]])),
            ],
        ]));

        $result = $this->gateway->success(['session_id' => 'cs_test_abc'], []);

        $this->assertEquals('approved', $result['status']);
        $this->assertEquals(7, $result['client_id']);
        $this->assertEquals(25.00, $result['amount']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals('cs_test_abc', $result['reference_id']);
        $this->assertEquals('pi_test_abc', $result['transaction_id']);
        $this->assertEquals([['id' => 5, 'amount' => 25.00]], $result['invoices']);
    }

    public function testSuccessCanceled()
    {
        $result = $this->gateway->success(['canceled' => 'true'], []);

        $this->assertEquals([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('exceptions', $errors);
    }

    /**
     * Note: uses ['canceled' => 'false'] to avoid undefined array key warning
     * (pre-existing code defect at line 171).
     */
    public function testSuccessMissingSessionId()
    {
        $result = $this->gateway->success(['canceled' => 'false'], []);

        $this->assertEquals([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('session_id', $errors);
    }
}
