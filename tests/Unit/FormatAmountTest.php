<?php

use PHPUnit\Framework\TestCase;

class FormatAmountTest extends TestCase
{
    private $gateway;
    private $method;

    protected function setUp(): void
    {
        $this->gateway = new StripeUniversal();
        $this->method = new ReflectionMethod(StripeUniversal::class, 'formatAmount');
        $this->method->setAccessible(true);
    }

    private function formatAmount($amount, $currency, $direction = 'to')
    {
        return $this->method->invoke($this->gateway, $amount, $currency, $direction);
    }

    public function testToStripeDecimalCurrency()
    {
        $this->assertSame(1050, $this->formatAmount(10.50, 'USD', 'to'));
    }

    public function testFromStripeDecimalCurrency()
    {
        $result = $this->formatAmount(1050, 'USD', 'from');
        $this->assertEquals(10.50, $result);
    }

    public function testNonDecimalCurrencyTo()
    {
        $this->assertSame(500, $this->formatAmount(500, 'JPY', 'to'));
    }

    public function testFromNonDecimalCurrency()
    {
        $this->assertSame(500, $this->formatAmount(500, 'JPY', 'from'));
    }

    public function testDecimalPreservation()
    {
        $result = $this->formatAmount(1099, 'USD', 'from');
        $this->assertEquals(10.99, $result);
        $this->assertIsFloat($result);
    }

    public function testLargeAmount()
    {
        $this->assertSame(100000, $this->formatAmount(1000.00, 'USD', 'to'));
    }

    public function testZeroAmount()
    {
        $this->assertSame(0, $this->formatAmount(0.00, 'USD', 'to'));
    }

    /**
     * @dataProvider nonDecimalCurrencyProvider
     */
    public function testAllNonDecimalCurrencies($currency)
    {
        $this->assertSame(500, $this->formatAmount(500, $currency, 'to'));
        $this->assertSame(500, $this->formatAmount(500, $currency, 'from'));
    }

    public function nonDecimalCurrencyProvider()
    {
        return [
            ['BIF'], ['CLP'], ['DJF'], ['GNF'], ['JPY'],
            ['KMF'], ['KRW'], ['MGA'], ['PYG'], ['RWF'],
            ['VUV'], ['XAF'], ['XOF'], ['XPF'],
        ];
    }
}
