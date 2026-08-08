<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests refund() — refunding a payment via Stripe Refund API.
 */
class RefundTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        // Preload Stripe class to prevent loadApi() fatal error
        class_exists(\Stripe\Stripe::class);

        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
        $this->gateway->setMeta(['secret_key' => 'sk_test_123']);
        $this->gateway->setCurrency('USD');
    }

    public function testRefundSuccess()
    {
        // First response: PaymentIntent::retrieve (to get currency)
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'pi_test_123',
            'object' => 'payment_intent',
            'currency' => 'usd',
            'amount' => 1050,
            'status' => 'succeeded',
        ]));

        // Second response: Refund::create
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 're_test_123',
            'object' => 'refund',
            'amount' => 1050,
            'currency' => 'usd',
            'payment_intent' => 'pi_test_123',
            'status' => 'succeeded',
        ]));

        $result = $this->gateway->refund('cs_test_ref', 'pi_test_123', 10.50);

        $this->assertEquals('refunded', $result['status']);
        $this->assertEquals('cs_test_ref', $result['reference_id']);
        $this->assertEquals('pi_test_123', $result['transaction_id']);

        // Verify two API calls were made (retrieve + create)
        $requests = MockHttpClient::getAllRequests();
        $this->assertCount(2, $requests);

        // First call: PaymentIntent::retrieve (GET)
        $this->assertEquals('get', $requests[0]['method']);
        $this->assertStringContainsString('payment_intents/pi_test_123', $requests[0]['absUrl']);

        // Second call: Refund::create (POST)
        $this->assertEquals('post', $requests[1]['method']);
        $this->assertStringContainsString('/v1/refunds', $requests[1]['absUrl']);
    }

    public function testPartialRefund()
    {
        // PaymentIntent::retrieve response
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'pi_test_partial',
            'object' => 'payment_intent',
            'currency' => 'usd',
            'amount' => 3050,
            'status' => 'succeeded',
        ]));

        // Refund::create response (partial)
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 're_test_partial',
            'object' => 'refund',
            'amount' => 525,
            'currency' => 'usd',
            'payment_intent' => 'pi_test_partial',
            'status' => 'succeeded',
        ]));

        $result = $this->gateway->refund('cs_test_ref', 'pi_test_partial', 5.25);

        $this->assertEquals('refunded', $result['status']);

        // Verify the amount was converted to cents (5.25 * 100 = 525)
        // Note: $params is an array (Stripe SDK passes array to HttpClient, URL encoding happens in CurlClient)
        $requests = MockHttpClient::getAllRequests();
        $refundRequest = $requests[1]; // Second call is Refund::create
        $this->assertEquals(525, $refundRequest['params']['amount']);
    }

    public function testRefundApiError()
    {
        // PaymentIntent::retrieve succeeds (needs currency for formatAmount)
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'pi_test_err',
            'object' => 'payment_intent',
            'currency' => 'usd',
            'amount' => 1050,
            'status' => 'succeeded',
        ]));

        // Refund::create returns error
        MockHttpClient::enqueueResponse(json_encode([
            'error' => [
                'type' => 'invalid_request_error',
                'message' => 'Charge ch_xxx has already been refunded.',
            ],
        ]), 400);

        $result = $this->gateway->refund('cs_test_ref', 'pi_test_err', 10.50);

        $this->assertNull($result);

        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('api', $errors);

        // Verify error was logged with success=false
        $logs = $this->gateway->getLogEntries();
        $errorLog = end($logs);
        $this->assertEquals('output', $errorLog['direction']);
        $this->assertFalse($errorLog['success']);
    }

    public function testRefundZeroDecimalCurrency()
    {
        // PaymentIntent::retrieve returns JPY (zero-decimal currency)
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'pi_test_jpy',
            'object' => 'payment_intent',
            'currency' => 'jpy',
            'amount' => 1000,
            'status' => 'succeeded',
        ]));

        // Refund::create response
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 're_test_jpy',
            'object' => 'refund',
            'amount' => 1000,
            'currency' => 'jpy',
            'payment_intent' => 'pi_test_jpy',
            'status' => 'succeeded',
        ]));

        $result = $this->gateway->refund('cs_test_ref', 'pi_test_jpy', 1000);

        $this->assertEquals('refunded', $result['status']);

        // Verify JPY amount was NOT multiplied by 100 (zero-decimal currency)
        $requests = MockHttpClient::getAllRequests();
        $refundRequest = $requests[1];
        $this->assertEquals(1000, $refundRequest['params']['amount']);
    }

    public function testPendingRefundRemainsPending()
    {
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'pi_test_pending',
            'object' => 'payment_intent',
            'currency' => 'usd',
        ]));
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 're_test_pending',
            'object' => 'refund',
            'payment_intent' => 'pi_test_pending',
            'status' => 'pending',
        ]));

        $result = $this->gateway->refund('cs_test_ref', 'pi_test_pending', 10.50);

        $this->assertSame('pending', $result['status']);
    }
}
