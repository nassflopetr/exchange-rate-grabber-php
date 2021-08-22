<?php declare(strict_types=1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Model\ExchangeRate;
use NassFloPetr\ExchangeRateGrabber\Exceptions\SomethingWentChangedException;

abstract class DOMDocumentGrabber extends Grabber
{
    public function getExchangeRates(?string $response = null): iterable
    {
        $DOMNodeList = $this->getDOMNodeList($response);

        foreach ($DOMNodeList as $DOMNode) {
            yield new ExchangeRate(
                static::class,
                $this->getBaseCurrencyCode($DOMNode),
                $this->getDestinationCurrencyCode($DOMNode),
                $this->getBuyRate($DOMNode),
                $this->getSaleRate($DOMNode)
            );
        }
    }

    abstract protected function getDOMNodeList(?string $response = null): \DOMNodeList;

    abstract protected function getBaseCurrencyCode(\DOMNode $DOMNode): string;

    abstract protected function getDestinationCurrencyCode(\DOMNode $DOMNode): string;

    abstract protected function getBuyRate(\DOMNode $DOMNode): float;

    abstract protected function getSaleRate(\DOMNode $DOMNode): float;

    protected function getDOMDocument(?string $response = null): \DOMDocument
    {
        if (\is_null($response)) {
            $response = $this->getResponse();
        }

        $DOMDocument = new \DOMDocument();

        try {
            $DOMDocument->loadHTML($response, \LIBXML_NOERROR);

            if (!$DOMDocument) {
                $error = \libxml_get_last_error();

                throw new SomethingWentChangedException(
                    ($error instanceof \libXMLError) ? \serialize($error) : 'Can\'t create DOMDocument object.'
                );
            }
        } catch (\Exception $e) {
            if ($e->getSeverity() !== \E_WARNING) {
                throw new SomethingWentChangedException($e->getMessage());
            }
        }

        return $DOMDocument;
    }

    protected function getDOMDocumentDOMXPath(?\DOMDocument $DOMDocument = null): \DOMXPath
    {
        if (\is_null($DOMDocument)) {
            $DOMDocument = $this->getDOMDocument();
        }

        return new \DOMXPath($DOMDocument);
    }

    protected function getDOMNodeDOMXPath(\DOMNode $DOMNode): \DOMXPath
    {
        return new \DOMXPath($DOMNode->ownerDocument);
    }
}
