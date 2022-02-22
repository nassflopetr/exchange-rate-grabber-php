<?php

declare(strict_types=1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\Grabber\Models\Model;
use NassFloPetr\ExchangeRateGrabber\Models\ExchangeRateModel;
use NassFloPetr\Grabber\Grabbers\DOMDocumentWebGrabber as WebGrabber;

abstract class DOMDocumentWebGrabber extends WebGrabber
{
    abstract protected function getBaseCurrencyCode(\DOMNode $DOMNode): string;

    abstract protected function getDestinationCurrencyCode(\DOMNode $DOMNode): string;

    abstract protected function getBuyRate(\DOMNode $DOMNode): float;

    abstract protected function getSaleRate(\DOMNode $DOMNode): float;

    protected function getModel(\DOMNode $DOMNode): Model
    {
        return new ExchangeRateModel(
            $this,
            $this->getBaseCurrencyCode($DOMNode),
            $this->getDestinationCurrencyCode($DOMNode),
            $this->getBuyRate($DOMNode),
            $this->getSaleRate($DOMNode)
        );
    }
}