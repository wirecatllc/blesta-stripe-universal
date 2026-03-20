<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests editSettings() — validation rule setup.
 */
class EditSettingsTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
    }

    public function testSetsValidationRules()
    {
        $meta = ['secret_key' => 'sk_test_abc'];
        $this->gateway->editSettings($meta);

        $rules = $this->gateway->Input->getRules();

        $this->assertArrayHasKey('secret_key', $rules);
        $this->assertArrayHasKey('empty', $rules['secret_key']);
        $this->assertArrayHasKey('valid', $rules['secret_key']);

        // Verify the 'empty' rule structure
        $this->assertEquals('isEmpty', $rules['secret_key']['empty']['rule']);
        $this->assertTrue($rules['secret_key']['empty']['negate']);

        // Verify the 'valid' rule references validateConnection
        $this->assertIsArray($rules['secret_key']['valid']['rule']);
    }

    public function testReturnsMeta()
    {
        $meta = ['secret_key' => 'sk_test_abc', 'webhook_secret' => 'whsec_123'];
        $result = $this->gateway->editSettings($meta);

        $this->assertEquals($meta, $result);
    }
}
