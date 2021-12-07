<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabber\Grabbers;

use NassFloPetr\ExchangeRateGrabber\Exceptions\SomethingWentChangedException;

class NbuDOMDocumentGrabber extends DOMDocumentGrabber
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
                \CURLOPT_URL => 'https://bank.gov.ua/ua/markets/exchangerates?' . \http_build_query(
                    [
                        'date' => (new \DateTime())->setTimezone(new \DateTimeZone('Europe/Kiev'))
                            ->format('d.m.Y'),
                        'period' => 'daily',
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

    protected function getDOMNodeList(?string $response = null): \DOMNodeList
    {
        $DOMXPathQuery = '//table[@id=\'exchangeRates\']/tbody/tr';

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
        $DOMXPathQuery = 'td[2]';

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
        $DOMXPathQuery = 'td[5]';

        $DOMNodeList = $this->getDOMNodeDOMXPath($DOMNode)->query($DOMXPathQuery, $DOMNode);

        if (!$DOMNodeList || $DOMNodeList->length === 0) {
            throw new SomethingWentChangedException(
                \sprintf('%s (responsible for buy (sale) rate) was not found.', $DOMXPathQuery)
            );
        }

        $result = \str_replace(',', '.', \trim($DOMNodeList->item(0)->nodeValue));

        if (!\is_numeric($result)) {
            throw new SomethingWentChangedException('Buy (sale) rate is invalid.');
        }

        return (float) ($result / $this->getUnit($DOMNode));
    }

    protected function getSaleRate(\DOMNode $DOMNode): float
    {
        return $this->getBuyRate($DOMNode);
    }

    private function getUnit(\DOMNode $DOMNode): int
    {
        $DOMXPathQuery = 'td[3]';

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
