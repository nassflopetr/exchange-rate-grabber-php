<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Exceptions\SomethingWentChanged;

class PrivatBankJSONGrabber extends JSONGrabber
{
    public function getCurlHandle(): \CurlHandle
    {
        $ch = \curl_init();

        if (!$ch) {
            throw new \Exception(
                \sprintf('Can\'t create %s instance.', \CurlHandle::class)
            );
        }

        if (!\curl_setopt_array($ch, [
            \CURLOPT_URL => 'https://api.privatbank.ua/p24api/pubinfo?' . \http_build_query(
                    [
                        'json' => '',
                        'exchange' => '',
                        'coursid' => 5,
                    ]
                ),
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_CONNECTTIMEOUT => 30,
            \CURLOPT_TIMEOUT => 30,
            \CURLOPT_FRESH_CONNECT => true,
        ])
        ) {
            throw new \Exception(\curl_error($ch));
        }

        return $ch;
    }

    protected function getDecodedJSONItems(?string $response = null): array
    {
        return $this->getDecodedJSON($response);
    }

    protected function getBaseCurrencyCode(array $decodedJSONItem): string
    {
        if (!\array_key_exists('base_ccy', $decodedJSONItem)) {
            throw new SomethingWentChanged('No key \'base_ccy\' found in array (json) structure.');
        }

        $result = \trim($decodedJSONItem['base_ccy']);

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new SomethingWentChanged('Base currency code is invalid.');
        }

        return $result;
    }

    protected function getDestinationCurrencyCode(array $decodedJSONItem): string
    {
        if (!\array_key_exists('ccy', $decodedJSONItem)) {
            throw new SomethingWentChanged('No key \'ccy\' found in array (json) structure.');
        }

        $result = \trim($decodedJSONItem['ccy']);

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new SomethingWentChanged('Destination currency code is invalid.');
        }

        return $result;
    }

    protected function getBuyRate(array $decodedJSONItem): float
    {
        if (!\array_key_exists('buy', $decodedJSONItem)) {
            throw new SomethingWentChanged('No key \'buy\' found in array (json) structure.');
        }

        $result = \trim($decodedJSONItem['buy']);

        if (!\is_numeric($result)) {
            throw new SomethingWentChanged('Buy rate is invalid.');
        }

        return (float) $result;
    }

    protected function getSaleRate(array $decodedJSONItem): float
    {
        if (!\array_key_exists('sale', $decodedJSONItem)) {
            throw new SomethingWentChanged('No key \'sale\' found in array (json) structure.');
        }

        $result = \trim($decodedJSONItem['sale']);

        if (!\is_numeric($result)) {
            throw new SomethingWentChanged('Sale rate is invalid.');
        }

        return (float) $result;
    }
}