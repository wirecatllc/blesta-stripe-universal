<?php

use PHPUnit\Framework\TestCase;

class GetLineItemsTest extends TestCase
{
    private $gateway;
    private $method;

    protected function setUp(): void
    {
        $this->gateway = new StripeUniversal();
        $this->gateway->setCurrency('USD');
        $this->method = new ReflectionMethod(StripeUniversal::class, 'getLineItems');
        $this->method->setAccessible(true);
    }

    public function testDefaultLineItem()
    {
        $result = $this->method->invoke($this->gateway, 10.50, null);

        $this->assertCount(1, $result);
        $this->assertEquals('USD', $result[0]['price_data']['currency']);
        $this->assertEquals(
            'StripeUniversal.charge_description_default',
            $result[0]['price_data']['product_data']['name']
        );
        $this->assertEquals(1050, $result[0]['price_data']['unit_amount_decimal']);
        $this->assertEquals(1, $result[0]['quantity']);
    }

    public function testEmptyInvoiceArray()
    {
        $result = $this->method->invoke($this->gateway, 10.50, []);

        $this->assertCount(1, $result);
        $this->assertEquals(
            'StripeUniversal.charge_description_default',
            $result[0]['price_data']['product_data']['name']
        );
    }

    public function testMultipleInvoices()
    {
        $mockInvoices = new class () {
            public function get($id)
            {
                $map = [
                    1 => (object) ['id_code' => 'INV-001'],
                    2 => (object) ['id_code' => 'INV-002'],
                ];
                return isset($map[$id]) ? $map[$id] : null;
            }
        };
        $this->gateway->Invoices = $mockInvoices;

        $invoiceAmounts = [
            ['id' => 1, 'amount' => 10.50],
            ['id' => 2, 'amount' => 20.00],
        ];

        $result = $this->method->invoke($this->gateway, 30.50, $invoiceAmounts);

        $this->assertCount(2, $result);
        $this->assertStringContainsString(
            'StripeUniversal.charge_description',
            $result[0]['price_data']['product_data']['name']
        );
        $this->assertEquals(1050, $result[0]['price_data']['unit_amount']);
        $this->assertEquals(2000, $result[1]['price_data']['unit_amount']);
    }

    public function testInvalidInvoiceId()
    {
        $mockInvoices = new class () {
            public function get($id)
            {
                if ($id === 1) {
                    return (object) ['id_code' => 'INV-001'];
                }
                return null;
            }
        };
        $this->gateway->Invoices = $mockInvoices;

        $invoiceAmounts = [
            ['id' => 1, 'amount' => 10.50],
            ['id' => 999, 'amount' => 20.00],
        ];

        $result = $this->method->invoke($this->gateway, 30.50, $invoiceAmounts);

        $this->assertCount(1, $result);
        $this->assertEquals(1050, $result[0]['price_data']['unit_amount']);
    }
}
