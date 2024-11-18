<?php
namespace App\Service;

use App\Repository\ExchangeRateRepository;

class CurrencyConverter
{
    private ExchangeRateRepository $repository;

    public function __construct(ExchangeRateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function convert(string $from, string $to, float $amount): ?float
    {
        if ($from === $to) {
            return $amount;
        }
        // Check for direct conversion
        $directRate = $this->repository->findOneBy([
            'baseCurrency' => $from,
            'targetCurrency' => $to,
        ]);
        if ($directRate) {
            return $amount * $directRate->getRate();
        }

        // Attempt indirect conversion via available base currencies
        foreach (['EUR', 'RUB'] as $baseCurrency) {
            $fromRate = $this->repository->findOneBy([
                'baseCurrency' => $baseCurrency,
                'targetCurrency' => $from,
            ]);

            $toRate = $this->repository->findOneBy([
                'baseCurrency' => $baseCurrency,
                'targetCurrency' => $to,
            ]);

            if ($fromRate && $toRate) {
                $amountInBase = $amount / $fromRate->getRate();
                return $amountInBase * $toRate->getRate();
            }
        }

        // Null if no conversion is possible
        return null;
    }
}