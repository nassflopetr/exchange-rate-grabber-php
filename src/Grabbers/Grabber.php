<?php declare(strict_types=1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;
use NassFloPetr\ExchangeRateGrabber\Exceptions\ExchangeRateNotFoundException;

abstract class Grabber
{
    abstract public function getCurlHandle(): \CurlHandle;

    abstract public function getExchangeRates(?string $response = null): iterable;

    public function getResponse(): string
    {
        $ch = $this->getCurlHandle();

        $response = \curl_exec($ch);

        if (!$response || \curl_errno($ch) !== 0) {
            throw new \Exception(\curl_error($ch));
        } elseif (\curl_getinfo($ch, \CURLINFO_RESPONSE_CODE) !== 200) {
            throw new SomethingWentChangedException(
                \sprintf(
                    'Open %s stream failed. Response code %d.',
                    \curl_getinfo($ch, \CURLINFO_EFFECTIVE_URL),
                    \curl_getinfo($ch, \CURLINFO_HTTP_CODE)
                )
            );
        }

        return $response;
    }

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
