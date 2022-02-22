<?php

declare(strict_types=1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\Grabber\Models\Model;
use NassFloPetr\ExchangeRateGrabber\Models\ExchangeRateModel;
use NassFloPetr\Grabber\Grabbers\JSONWebGrabber as WebGrabber;

abstract class JSONWebGrabber extends WebGrabber
{
    abstract protected function getBaseCurrencyCode(array $decodedJSONItem): string;

    abstract protected function getDestinationCurrencyCode(array $decodedJSONItem): string;

    abstract protected function getBuyRate(array $decodedJSONItem): float;

    abstract protected function getSaleRate(array $decodedJSONItem): float;

    protected function getModel(array $decodedJSONItem): Model
    {
        return new ExchangeRateModel(
            $this,
            $this->getBaseCurrencyCode($decodedJSONItem),
            $this->getDestinationCurrencyCode($decodedJSONItem),
            $this->getBuyRate($decodedJSONItem),
            $this->getSaleRate($decodedJSONItem)
        );
    }
}