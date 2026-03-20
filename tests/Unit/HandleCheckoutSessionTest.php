<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests handleCheckoutSession() indirectly through success().
 * Targets currency duplication bug from commit 64ae2cb.
 */
class HandleCheckoutSessionTest extends TestCase
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

    /**
     * Helper: enqueue a Stripe Session retrieve response and call success().
     */
    private function callSuccessWithSession(array $sessionData)
    {
        $defaults = [
            'id' => 'cs_test_xxx',
            'object' => 'checkout.session',
            'payment_status' => 'unpaid',
            'status' => 'open',
            'currency' => 'usd',
            'amount_total' => 1050,
            'payment_intent' => 'pi_test_xxx',
            'metadata' => [
                'client_id' => 42,
                'invoices' => base64_encode(serialize([['id' => 1, 'amount' => 10.50]])),
            ],
        ];
        $data = array_merge($defaults, $sessionData);

        MockHttpClient::enqueueResponse(json_encode($data));
        return $this->gateway->success(['session_id' => 'cs_test_xxx'], []);
    }

    public function testPaidSession()
    {
        $result = $this->callSuccessWithSession([
            'payment_status' => 'paid',
            'status' => 'complete',
        ]);

        $this->assertEquals('approved', $result['status']);
        $this->assertEquals(42, $result['client_id']);
        $this->assertEquals(10.50, $result['amount']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals('cs_test_xxx', $result['reference_id']);
    }

    public function testExpiredSession()
    {
        $result = $this->callSuccessWithSession([
            'payment_status' => 'unpaid',
            'status' => 'expired',
        ]);

        $this->assertEquals('void', $result['status']);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('payment_status', $errors);
    }

    public function testCompleteButUnpaidSession()
    {
        $result = $this->callSuccessWithSession([
            'payment_status' => 'unpaid',
            'status' => 'complete',
        ]);

        $this->assertEquals('pending', $result['status']);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('payment_status', $errors);
    }

    public function testOpenSession()
    {
        $result = $this->callSuccessWithSession([
            'payment_status' => 'unpaid',
            'status' => 'open',
        ]);

        $this->assertEquals('pending', $result['status']);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('payment_status', $errors);
    }

    public function testCurrencyConversion()
    {
        $result = $this->callSuccessWithSession([
            'payment_status' => 'paid',
            'status' => 'complete',
            'currency' => 'eur',
            'amount_total' => 950,
            'currency_conversion' => [
                'source_currency' => 'usd',
                'amount_total' => 1050,
            ],
        ]);

        $this->assertEquals('approved', $result['status']);
        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals(10.50, $result['amount']);
    }

    public function testNoCurrencyConversion()
    {
        $result = $this->callSuccessWithSession([
            'payment_status' => 'paid',
            'status' => 'complete',
            'currency' => 'eur',
            'amount_total' => 950,
        ]);

        $this->assertEquals('EUR', $result['currency']);
        $this->assertEquals(9.50, $result['amount']);
    }

    /**
     * Regression: commit 64ae2cb — currency should always be uppercased
     */
    public function testCurrencyIsUppercased()
    {
        $result = $this->callSuccessWithSession([
            'payment_status' => 'paid',
            'status' => 'complete',
            'currency' => 'usd',
        ]);

        $this->assertEquals('USD', $result['currency']);
    }
}
