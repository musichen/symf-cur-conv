<?php
namespace App\Tests\Service;

use App\Service\CurrencyConverter;
use App\Repository\ExchangeRateRepository;
use App\Entity\ExchangeRate;
use PHPUnit\Framework\TestCase;

class CurrencyConverterTest extends TestCase
{
    public function testSameCurrencyConversion()
    {
        $repository = $this->createMock(ExchangeRateRepository::class);
        $converter = new CurrencyConverter($repository);

        // Test conversion of 100 USD to USD
        $amount = 100;
        $result = $converter->convert('USD', 'USD', $amount);

        // Expect the result to be the same as the original amount
        $this->assertEquals($amount, $result);
    }

    public function testDirectConversion()
    {
        $repository = $this->createMock(ExchangeRateRepository::class);

        // Mock a direct rate from USD to CZK
        $usdToCzkRate = new ExchangeRate();
        $usdToCzkRate->setBaseCurrency('USD');
        $usdToCzkRate->setTargetCurrency('CZK');
        $usdToCzkRate->setRate(22.5); // Assume 1 USD = 22.5 CZK

        // Use willReturnCallback to dynamically return mock data based on criteria
        $repository->method('findOneBy')->willReturnCallback(function ($criteria) use ($usdToCzkRate) {
            if (($criteria['baseCurrency'] ?? null) === 'USD' && ($criteria['targetCurrency'] ?? null) === 'CZK') {
                return $usdToCzkRate;
            }
            return null; // Fallback if criteria do not match
        });

        $converter = new CurrencyConverter($repository);
        $result = $converter->convert('USD', 'CZK', 100); // Convert 100 USD to CZK

        $expectedResult = 100 * 22.5;
        $this->assertEquals($expectedResult, $result);
    }

    public function testIndirectConversionViaEUR()
    {
        $repository = $this->createMock(ExchangeRateRepository::class);

        // Mock EUR to USD rate
        $eurToUsdRate = new ExchangeRate();
        $eurToUsdRate->setBaseCurrency('EUR');
        $eurToUsdRate->setTargetCurrency('USD');
        $eurToUsdRate->setRate(1.2);

        // Mock EUR to CZK rate
        $eurToCzkRate = new ExchangeRate();
        $eurToCzkRate->setBaseCurrency('EUR');
        $eurToCzkRate->setTargetCurrency('CZK');
        $eurToCzkRate->setRate(25.0);

        // Use willReturnCallback for flexible matching
        $repository->method('findOneBy')->willReturnCallback(function ($criteria) use ($eurToUsdRate, $eurToCzkRate) {
            if (($criteria['baseCurrency'] ?? null) === 'EUR' && ($criteria['targetCurrency'] ?? null) === 'USD') {
                return $eurToUsdRate;
            }
            if (($criteria['baseCurrency'] ?? null) === 'EUR' && ($criteria['targetCurrency'] ?? null) === 'CZK') {
                return $eurToCzkRate;
            }
            return null; // Fallback if criteria do not match
        });

        $converter = new CurrencyConverter($repository);

        // Test indirect conversion from USD to CZK via EUR
        $amount = 100;
        $expectedResult = ($amount / 1.2) * 25.0; // Convert 100 USD -> EUR -> CZK
        $result = $converter->convert('USD', 'CZK', $amount);

        $this->assertEquals($expectedResult, $result);
    }

    public function testIndirectConversionViaRUB()
    {
        $repository = $this->createMock(ExchangeRateRepository::class);

        // Mock RUB to USD rate
        $rubToUsdRate = new ExchangeRate();
        $rubToUsdRate->setBaseCurrency('RUB');
        $rubToUsdRate->setTargetCurrency('USD');
        $rubToUsdRate->setRate(98.0);

        // Mock RUB to CZK rate
        $rubToCzkRate = new ExchangeRate();
        $rubToCzkRate->setBaseCurrency('RUB');
        $rubToCzkRate->setTargetCurrency('CZK');
        $rubToCzkRate->setRate(3.5);

        $repository->method('findOneBy')->willReturnCallback(function ($criteria) use ($rubToUsdRate, $rubToCzkRate) {
            if (($criteria['baseCurrency'] ?? null) === 'RUB' && ($criteria['targetCurrency'] ?? null) === 'USD') {
                return $rubToUsdRate;
            }
            if (($criteria['baseCurrency'] ?? null) === 'RUB' && ($criteria['targetCurrency'] ?? null) === 'CZK') {
                return $rubToCzkRate;
            }
            return null; // Fallback if criteria do not match
        });

        $converter = new CurrencyConverter($repository);

        // Test indirect conversion from USD to CZK via RUB
        $amount = 100;
        $expectedResult = ($amount / 98.0) * 3.5; // Convert 100 USD -> RUB -> CZK
        $result = $converter->convert('USD', 'CZK', $amount);

        $this->assertEquals($expectedResult, $result);
    }

    public function testInvalidCurrencyConversion()
    {
        $repository = $this->createMock(ExchangeRateRepository::class);
        $converter = new CurrencyConverter($repository);

        // Test with invalid currency codes
        $result = $converter->convert('XXX', 'YYY', 100);
        $this->assertNull($result);
    }

    public function testConversionNotPossibleDueToMissingRates()
    {
        $repository = $this->createMock(ExchangeRateRepository::class);
        $repository->method('findOneBy')->willReturn(null); // No rates available

        $converter = new CurrencyConverter($repository);

        // Attempt to convert from USD to CZK with no rates in the repository
        $result = $converter->convert('USD', 'CZK', 100);
        $this->assertNull($result);
    }
}
