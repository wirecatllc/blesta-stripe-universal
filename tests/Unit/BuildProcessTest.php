<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests buildProcess() — payment session creation including error handling.
 */
class BuildProcessTest extends TestCase
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
        $this->gateway->setCurrency('USD');

        // Mock Contacts model
        $this->gateway->Contacts = new class () {
            public function get($id)
            {
                return (object) ['email' => 'test@example.com'];
            }
        };

        // Mock Invoices model (needed by getLineItems when invoice_amounts is provided)
        $this->gateway->Invoices = new class () {
            public function get($id)
            {
                return (object) ['id_code' => 'INV-' . str_pad($id, 3, '0', STR_PAD_LEFT)];
            }
        };
    }

    public function testBuildProcessSuccess()
    {
        // Enqueue Stripe Session::create response
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'cs_test_session',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/pay/cs_test_session',
        ]));

        $result = $this->gateway->buildProcess(
            ['id' => 1, 'client_id' => 42],
            10.50,
            [['id' => 1, 'amount' => 10.50]],
            ['return_url' => 'https://example.com/return']
        );

        // Should return view output (JSON string from our View stub)
        $this->assertNotNull($result);
        $this->assertIsString($result);

        // Verify the Stripe API was called
        $lastRequest = MockHttpClient::getLastRequest();
        $this->assertNotNull($lastRequest);
        $this->assertEquals('post', $lastRequest['method']);
        $this->assertStringContainsString('checkout/sessions', $lastRequest['absUrl']);
        $this->assertSame('v1', $lastRequest['apiMode']);
        $this->assertContains('Stripe-Version: 2024-04-10', $lastRequest['headers']);
        $this->assertSame(2, $lastRequest['maxNetworkRetries']);
        $this->assertMatchesRegularExpression(
            '/^Idempotency-Key: stripe-universal-checkout-[a-f0-9]{32}$/',
            $this->getHeader($lastRequest['headers'], 'Idempotency-Key')
        );
    }

    public function testBuildProcessCreatesDistinctSessionsForDistinctAttempts()
    {
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'cs_test_first',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/pay/cs_test_first',
        ]));
        MockHttpClient::enqueueResponse(json_encode([
            'id' => 'cs_test_second',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/pay/cs_test_second',
        ]));

        $arguments = [
            ['id' => 1, 'client_id' => 42],
            10.50,
            [['id' => 1, 'amount' => 10.50]],
            ['return_url' => 'https://example.com/return'],
        ];
        $this->gateway->buildProcess(...$arguments);
        $this->gateway->buildProcess(...$arguments);

        $requests = MockHttpClient::getAllRequests();
        $this->assertCount(2, $requests);
        $this->assertNotSame(
            $this->getHeader($requests[0]['headers'], 'Idempotency-Key'),
            $this->getHeader($requests[1]['headers'], 'Idempotency-Key')
        );
    }

    public function testBuildProcessApiError()
    {
        // Enqueue an error response that causes the Stripe SDK to throw
        MockHttpClient::enqueueResponse(json_encode([
            'error' => [
                'type' => 'api_error',
                'message' => 'Something went wrong',
            ],
        ]), 500);

        $result = $this->gateway->buildProcess(
            ['id' => 1, 'client_id' => 42],
            10.50,
            [['id' => 1, 'amount' => 10.50]],
            ['return_url' => 'https://example.com/return']
        );

        $this->assertNull($result);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('api', $errors);
    }

    private function getHeader(array $headers, string $name): string
    {
        foreach ($headers as $header) {
            if (strpos($header, $name . ': ') === 0) {
                return $header;
            }
        }

        $this->fail('Expected ' . $name . ' header was not sent.');
    }
}
