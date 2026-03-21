<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests handleAsyncPaymentFailed() via reflection.
 * Validates that async payment failures return 'declined' status.
 */
class HandleAsyncPaymentFailedTest extends TestCase
{
    private $gateway;
    private $method;

    protected function setUp(): void
    {
        class_exists(\Stripe\Stripe::class);
        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
        $this->gateway->setMeta(['secret_key' => 'sk_test_123']);
        $this->method = new ReflectionMethod(StripeUniversal::class, 'handleAsyncPaymentFailed');
        $this->method->setAccessible(true);
    }

    private function makeSession(array $overrides = [])
    {
        $defaults = [
            'id' => 'cs_test_async',
            'object' => 'checkout.session',
            'payment_status' => 'unpaid',
            'status' => 'complete',
            'currency' => 'usd',
            'amount_total' => 2000,
            'payment_intent' => 'pi_test_async',
            'metadata' => [
                'client_id' => 55,
                'invoices' => base64_encode(serialize([['id' => 10, 'amount' => 20.00]])),
            ],
        ];
        $data = array_merge($defaults, $overrides);
        return \Stripe\Checkout\Session::constructFrom($data);
    }

    public function testDeclinedStatus()
    {
        $session = $this->makeSession();
        $result = $this->method->invoke($this->gateway, $session);

        $this->assertEquals('declined', $result['status']);
        $this->assertEquals('cs_test_async', $result['reference_id']);
        $this->assertEquals('pi_test_async', $result['transaction_id']);
    }

    public function testMetadataExtraction()
    {
        $invoices = [['id' => 10, 'amount' => 20.00]];
        $session = $this->makeSession();
        $result = $this->method->invoke($this->gateway, $session);

        $this->assertEquals(55, $result['client_id']);
        $this->assertEquals($invoices, $result['invoices']);
    }

    public function testAmountAndCurrency()
    {
        $session = $this->makeSession([
            'currency' => 'eur',
            'amount_total' => 1500,
        ]);
        $result = $this->method->invoke($this->gateway, $session);

        $this->assertEquals('EUR', $result['currency']);
        $this->assertEquals(15.00, $result['amount']);
    }

    public function testCurrencyConversion()
    {
        $session = $this->makeSession([
            'currency' => 'eur',
            'amount_total' => 1500,
            'currency_conversion' => [
                'source_currency' => 'usd',
                'amount_total' => 1050,
            ],
        ]);
        $result = $this->method->invoke($this->gateway, $session);

        $this->assertEquals('USD', $result['currency']);
        $this->assertEquals(10.50, $result['amount']);
    }

    public function testErrorMessageSet()
    {
        $session = $this->makeSession();
        $this->method->invoke($this->gateway, $session);

        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('payment_status', $errors);
    }

    public function testMissingClientIdReturnsEmpty()
    {
        $session = $this->makeSession([
            'metadata' => [
                'client_id' => 0,
                'invoices' => base64_encode(serialize([])),
            ],
        ]);
        $result = $this->method->invoke($this->gateway, $session);

        $this->assertEquals([], $result);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('metadata', $errors);
    }
}
