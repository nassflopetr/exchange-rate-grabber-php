<?php

declare(strict_types=1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\Grabber\Exceptions\SomethingWentChanged;

class UkrGasBankDOMDocumentWebGrabber extends DOMDocumentWebGrabber
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
                \CURLOPT_URL => 'https://www.ukrgasbank.com/kurs/',
                \CURLOPT_HEADER => false,
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_CONNECTTIMEOUT => 30,
                \CURLOPT_TIMEOUT => 30,
                \CURLOPT_FRESH_CONNECT => true,
                \CURLOPT_ENCODING => '',
            ])
        ) {
            throw new \Exception(\curl_error($ch));
        }

        return $ch;
    }

    protected function getDOMNodeList(?string $response = null): \DOMNodeList
    {
        $DOMXPathQuery = '//div[contains(@class, \'kurs\') and contains(@class, \'kurs-full\')]/table/tr[td]';

        $DOMNodeList = $this->getDOMDocumentDOMXPath($this->getDOMDocument($response))->query($DOMXPathQuery);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChanged(\sprintf('%s was not found.', $DOMXPathQuery));
        }

        return $DOMNodeList;
    }

    protected function getBaseCurrencyCode(\DOMNode $DOMNode): string
    {
        return 'UAH';
    }

    protected function getDestinationCurrencyCode(\DOMNode $DOMNode): string
    {
        $DOMXPathQuery = 'td[1][contains(@class, \'icon\')]';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length !== 1) {
            throw new SomethingWentChanged(
                \sprintf('%s (responsible for destination currency code) was not found.', $DOMXPathQuery)
            );
        }

        $DOMAttr = $DOMNodeList->item(0)->attributes->getNamedItem('class');

        $count = \sscanf($DOMAttr->value, 'icon icon-%s', $currencyCode);

        if ($count === 0) {
            throw new SomethingWentChanged(
                \sprintf('%s (responsible for destination currency code) was not found.', $DOMXPathQuery)
            );
        }

        $result = \strtoupper(\trim($currencyCode));

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new SomethingWentChanged('Destination currency code is invalid.');
        }

        return $result;
    }

    protected function getBuyRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[3]';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length !== 1) {
            throw new SomethingWentChanged(
                \sprintf('%s (responsible for buy rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\is_numeric($result)) {
            throw new SomethingWentChanged('Buy rate is invalid.');
        }

        return (float) ($result / $this->getUnit($DOMNode));
    }

    protected function getSaleRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[4]';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length !== 1) {
            throw new SomethingWentChanged(
                \sprintf('%s (responsible for sale rate) element was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\is_numeric($result)) {
            throw new SomethingWentChanged('Sale rate is invalid.');
        }

        return (float) ($result / $this->getUnit($DOMNode));
    }

    private function getUnit(\DOMNode $DOMNode): int
    {
        $DOMXPathQuery = 'td[2]';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length !== 1) {
            throw new SomethingWentChanged(
                \sprintf('%s (responsible for unit) was not found.', $DOMXPathQuery)
            );
        }

        $DOMAttr = $DOMNodeList->item(0)->attributes->getNamedItem('class');

        $count = \sscanf(\trim($DOMNodeList->item(0)->nodeValue), '%D', $unit);

        if ($count === 0) {
            throw new SomethingWentChanged(
                \sprintf('%s (responsible for unit) was not found.', $DOMXPathQuery)
            );
        } elseif (!\is_numeric($unit)) {
            throw new SomethingWentChanged('Unit is invalid.');
        }

        return (int) $unit;
    }
}