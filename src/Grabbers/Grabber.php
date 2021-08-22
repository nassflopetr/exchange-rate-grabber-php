<?php declare(strict_types=1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;
use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateNotFoundException;

abstract class Grabber
{
    abstract public function getResponse(): string;

    abstract public function getExchangeRates(?string $response = null): iterable;

    public function getExchangeRate(
        string $baseCurrencyCode,
        string $destinationCurrencyCode,
        ?string $response = null
    ): ExchangeRate
    {
        foreach ($this->getExchangeRates($response) as $exchangeRate) {
            if (
                $exchangeRate->getBaseCurrencyCode() == $baseCurrencyCode
                && $exchangeRate->getDestinationCurrencyCode() == $destinationCurrencyCode
            ) {
                return $exchangeRate;
            }
        }

        throw new ExchangeRateNotFoundException(
            \sprintf(
                '%s can\'t find exchange rate for base %s and destination %s currency codes.',
                static::class,
                $baseCurrencyCode,
                $destinationCurrencyCode
            )
        );
    }
}
