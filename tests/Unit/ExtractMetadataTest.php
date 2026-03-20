<?php

use PHPUnit\Framework\TestCase;

class ExtractMetadataTest extends TestCase
{
    private $gateway;
    private $method;

    protected function setUp(): void
    {
        MockHttpClient::reset();
        \Stripe\ApiRequestor::setHttpClient(new MockHttpClient());
        $this->gateway = new StripeUniversal();
        $this->method = new ReflectionMethod(StripeUniversal::class, 'extractMetadata');
        $this->method->setAccessible(true);
    }

    public function testValidMetadata()
    {
        $invoices = [['id' => 1, 'amount' => 10.50], ['id' => 2, 'amount' => 20.00]];
        $metadata = \Stripe\StripeObject::constructFrom([
            'client_id' => 42,
            'invoices' => base64_encode(serialize($invoices)),
        ]);

        $result = $this->method->invoke($this->gateway, $metadata);

        $this->assertIsArray($result);
        $this->assertEquals(42, $result['client_id']);
        $this->assertEquals($invoices, $result['invoices']);
    }

    public function testMissingMetadataThrowsTypeError()
    {
        $this->expectException(\TypeError::class);
        $this->method->invoke($this->gateway, null);
    }

    public function testMissingClientId()
    {
        $metadata = \Stripe\StripeObject::constructFrom([
            'client_id' => 0,
            'invoices' => base64_encode(serialize([])),
        ]);

        $result = $this->method->invoke($this->gateway, $metadata);

        $this->assertFalse($result);
        $errors = $this->gateway->Input->errors();
        $this->assertArrayHasKey('metadata', $errors);
    }

    public function testInvoiceDeserialization()
    {
        $invoices = [
            ['id' => 101, 'amount' => 49.99],
            ['id' => 102, 'amount' => 25.00],
            ['id' => 103, 'amount' => 0.01],
        ];
        $encoded = base64_encode(serialize($invoices));

        $metadata = \Stripe\StripeObject::constructFrom([
            'client_id' => 7,
            'invoices' => $encoded,
        ]);

        $result = $this->method->invoke($this->gateway, $metadata);

        $this->assertEquals($invoices, $result['invoices']);
    }
}
