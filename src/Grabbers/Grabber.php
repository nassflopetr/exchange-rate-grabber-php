<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;

abstract class Grabber
{
    abstract public function getCurlHandle(): \CurlHandle;

    abstract public function getExchangeRates(?string $response = null): iterable;

    public function getResponse(?\CurlHandle $ch = null): string
    {
        if(\is_null($ch)) {
            $ch = $this->getCurlHandle();
        }

        $response = \curl_exec($ch);

        if (!$response || \curl_errno($ch) !== \CURLE_OK) {
            throw new \Exception(
                \sprintf(
                    'Open %s stream failed. %s.',
                    \curl_getinfo($ch, \CURLINFO_EFFECTIVE_URL),
                    \curl_error($ch)
                )
            );
        }

        if (\curl_getinfo($ch, \CURLINFO_RESPONSE_CODE) !== 200) {
            throw new \Exception(
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
    ): ?ExchangeRate
    {
        foreach ($this->getExchangeRates($response) as $exchangeRate) {
            if (
                $exchangeRate->getBaseCurrencyCode() == $baseCurrencyCode
                && $exchangeRate->getDestinationCurrencyCode() == $destinationCurrencyCode
            ) {
                return $exchangeRate;
            }
        }

        return null;
    }
}