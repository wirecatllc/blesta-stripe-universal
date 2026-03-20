<?php

use PHPUnit\Framework\TestCase;

class TrivialMethodsTest extends TestCase
{
    private $gateway;

    protected function setUp(): void
    {
        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
    }

    public function testEncryptableFields()
    {
        $this->assertEquals(['secret_key'], $this->gateway->encryptableFields());
    }

    public function testRequiresCustomerPresent()
    {
        $this->assertTrue($this->gateway->requiresCustomerPresent());
    }

    public function testSetMeta()
    {
        $meta = ['secret_key' => 'sk_test_123', 'webhook_secret' => 'whsec_123'];
        $this->gateway->setMeta($meta);

        $ref = new ReflectionProperty(StripeUniversal::class, 'meta');
        $ref->setAccessible(true);
        $this->assertEquals($meta, $ref->getValue($this->gateway));
    }

    public function testSetCurrency()
    {
        $this->gateway->setCurrency('USD');

        $ref = new ReflectionProperty(StripeUniversal::class, 'currency');
        $ref->setAccessible(true);
        $this->assertEquals('USD', $ref->getValue($this->gateway));
    }
}
