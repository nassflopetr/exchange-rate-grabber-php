<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;
use NassFloPetr\ExchangeRateGrabber\Exceptions\SomethingWentChangedException;

abstract class JSONGrabber extends Grabber
{
    public function getExchangeRates(?string $response = null): iterable
    {
        $decodedJSONItems = $this->getDecodedJSONItems($response);

        foreach ($decodedJSONItems as $decodedJSONItem) {
            yield new ExchangeRate(
                static::class,
                $this->getBaseCurrencyCode($decodedJSONItem),
                $this->getDestinationCurrencyCode($decodedJSONItem),
                $this->getBuyRate($decodedJSONItem),
                $this->getSaleRate($decodedJSONItem)
            );
        }
    }

    abstract protected function getDecodedJSONItems(?string $response = null): array;

    abstract protected function getBaseCurrencyCode(array $decodedJSONItem): string;

    abstract protected function getDestinationCurrencyCode(array $decodedJSONItem): string;

    abstract protected function getBuyRate(array $decodedJSONItem): float;

    abstract protected function getSaleRate(array $decodedJSONItem): float;

    protected function getDecodedJSON(?string $response = null): array
    {
        if (\is_null($response)) {
            $response = $this->getResponse();
        }

        try {
            return \json_decode($response, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new SomethingWentChangedException('JSON decoding failed. ' . $e->getMessage());
        }
    }
}
