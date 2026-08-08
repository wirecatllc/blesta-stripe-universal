<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests void() — voiding a payment (implemented as full refund via Stripe).
 */
class VoidTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        class_exists(\Stripe\Stripe::class);

        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
        $this->gateway->setMeta(['secret_key' => 'sk_test_123']);
        // No setCurrency() needed — void() does not convert amounts
    }

    public function testVoidSuccess()
    {
        // Only one response needed: Refund::create (no PI retrieve for void)
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 're_test_void',
            'object' => 'refund',
            'amount' => 1050,
            'currency' => 'usd',
            'payment_intent' => 'pi_test_void',
            'status' => 'succeeded',
        ]));

        $result = $this->gateway->void('cs_test_ref', 'pi_test_void');

        $this->assertEquals('void', $result['status']);
        $this->assertEquals('cs_test_ref', $result['reference_id']);
        $this->assertEquals('pi_test_void', $result['transaction_id']);

        // Verify only one API call made (no PI retrieve needed)
        $requests = MockHttpClient::getAllRequests();
        $this->assertCount(1, $requests);

        // Verify Refund::create was called without amount parameter
        // Note: $params is an array (Stripe SDK passes array to HttpClient)
        $this->assertEquals('post', $requests[0]['method']);
        $this->assertStringContainsString('/v1/refunds', $requests[0]['absUrl']);
        $this->assertArrayNotHasKey('amount', $requests[0]['params']);
    }

    public function testVoidApiError()
    {
        MockHttpClient::enqueueResponse(json_encode([
            'error' => [
                'type' => 'invalid_request_error',
                'message' => 'No such payment_intent: pi_invalid',
            ],
        ]), 400);

        $result = $this->gateway->void('cs_test_ref', 'pi_invalid');

        $this->assertNull($result);

        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('api', $errors);

        // Verify error was logged with success=false
        $logs = $this->gateway->getLogEntries();
        $errorLog = end($logs);
        $this->assertEquals('output', $errorLog['direction']);
        $this->assertFalse($errorLog['success']);
    }

    public function testFailedVoidIsDeclined()
    {
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 're_test_failed_void',
            'object' => 'refund',
            'payment_intent' => 'pi_test_failed_void',
            'status' => 'failed',
            'failure_reason' => 'declined',
        ]));

        $result = $this->gateway->void('cs_test_ref', 'pi_test_failed_void');

        $this->assertSame('declined', $result['status']);
    }
}
