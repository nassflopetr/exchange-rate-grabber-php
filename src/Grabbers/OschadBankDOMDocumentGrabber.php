<?php declare(strict_types=1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Exceptions\SomethingWentChangedException;

class OschadBankDOMDocumentGrabber extends DOMDocumentGrabber
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
                \CURLOPT_URL => 'https://www.oschadbank.ua/currency-rate',
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
        $DOMXPathQuery = '//table[contains(@class, \'heading-block-currency-rate__table\')]/'
            . 'tbody[contains(@class, \'heading-block-currency-rate__table-body\')]/tr';

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
        $DOMXPathQuery = 'td[2]/span';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(
                \sprintf('%s (responsible for destination currency code) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\preg_match('/^[A-Z]{3}$/', $result)) {
            throw new SomethingWentChangedException('Destination currency code is invalid.');
        }

        return $result;
    }

    protected function getBuyRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[4]/span';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(
                \sprintf('%s (responsible for buy rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\is_numeric($result)) {
            throw new SomethingWentChangedException('Buy rate is invalid.');
        }

        return (float) ($result / $this->getUnit($DOMNode));
    }

    protected function getSaleRate(\DOMNode $DOMNode): float
    {
        $DOMXPathQuery = 'td[5]/span';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(
                \sprintf('%s (responsible for sale rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\is_numeric($result)) {
            throw new SomethingWentChangedException('Sale rate is invalid.');
        }

        return (float) ($result / $this->getUnit($DOMNode));
    }

    private function getUnit(\DOMNode $DOMNode): int
    {
        $DOMXPathQuery = 'td[3]/span';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(
                \sprintf('%s (responsible for unit) was not found.', $DOMXPathQuery)
            );
        }

        $result = \trim($DOMNodeList->item(0)->nodeValue);

        if (!\is_numeric($result)) {
            throw new SomethingWentChangedException('Unit is invalid.');
        }

        return (int) $result;
    }
}
