<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Exceptions\SomethingWentChangedException;

class UkrSibBankDOMDocumentGrabber extends DOMDocumentGrabber
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
                \CURLOPT_URL => 'https://my.ukrsibbank.com/ua/personal/operations/currency_exchange/',
                \CURLOPT_HEADER => false,
                \CURLOPT_RETURNTRANSFER => true,
                \CURLOPT_CONNECTTIMEOUT => 30,
                \CURLOPT_TIMEOUT => 30,
            ])
        ) {
            throw new \Exception(\curl_error($ch));
        }

        return $ch;
    }

    protected function getDOMNodeList(?string $response = null): \DOMNodeList
    {
        $DOMXPathQuery = '//table[@class=\'currency__table\']/tbody/tr';

        $DOMNodeList = $this->getDOMDocumentDOMXPath($this->getDOMDocument($response))->query($DOMXPathQuery);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(\sprintf('%s was not found.', $DOMXPathQuery));
        }

        return $DOMNodeList;
    }

    protected function getBaseCurrencyCode(\DOMNode $DOMNode): string
    {
        return 'UAH';
    }

    protected function getDestinationCurrencyCode(\DOMNode $DOMNode): string
    {
        $DOMXPathQuery = 'td[1]/text()';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(
                \sprintf('%s (responsible for destination currency code) was not found.', $DOMXPathQuery)
            );
        }

        $result = (string) \preg_replace(
            '/[^A-Z]+/', '', \trim($DOMNodeList->item(0)->textContent)
        );

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new SomethingWentChangedException('Destination currency code is invalid.');
        }

        return $result;
    }

    protected function getBuyRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[2]/text()';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(
                \sprintf('%s (responsible for buy rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->textContent);

        if (!\is_numeric($result)) {
            throw new SomethingWentChangedException('Buy rate is invalid.');
        }

        return (float) $result;
    }

    protected function getSaleRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[3]/text()';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(
                \sprintf('%s (responsible for sale rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->textContent);

        if (!\is_numeric($result)) {
            throw new SomethingWentChangedException('Sale rate is invalid.');
        }

        return (float) $result;
    }
}
