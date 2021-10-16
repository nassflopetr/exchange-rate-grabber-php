<?php

declare(strict_types = 1);

namespace NassFloPetr\ExchangeRateGrabber\Model;

use NassFloPetr\ExchangeRateGrabber\Grabbers\Grabber;
use NassFloPetr\ExchangeRateGrabber\Observers\ExchangeRateObserver;

class ExchangeRate
{
    private string $grabberClassName;
    private string $baseCurrencyCode;
    private string $destinationCurrencyCode;
    private float $buyRate;
    private float $saleRate;
    private \DateTime $timestamp;
    private array $observers;

    public function __construct(
        string $grabberClassName,
        string $baseCurrencyCode,
        string $destinationCurrencyCode,
        ?float $buyRate = null,
        ?float $saleRate = null,
        ?\DateTime $timestamp = null,
        array $observers = []
    )
    {
        if (!\class_exists($grabberClassName) || !\is_subclass_of($grabberClassName, Grabber::class)) {
            throw new \ValueError(
                \sprintf(
                    '%s is not exists or not instance of %s class.',
                    $grabberClassName,
                    Grabber::class
                )
            );
        }

        if (
            !\preg_match('/^[A-Z]{3}$/', $baseCurrencyCode) ||
            !\preg_match('/^[A-Z]{3}$/',  $destinationCurrencyCode)
        ) {
            throw new \ValueError();
        }

        $this->grabberClassName = $grabberClassName;
        $this->baseCurrencyCode = $baseCurrencyCode;
        $this->destinationCurrencyCode = $destinationCurrencyCode;

        $this->observers = [];

        $this->attachObservers($observers);

        if (\is_null($buyRate) || \is_null($saleRate)) {
            $latestExchangeRate = $this->getGrabber()->getExchangeRate(
                $this->getBaseCurrencyCode(),
                $this->getDestinationCurrencyCode()
            );

            $buyRate = $latestExchangeRate->getBuyRate();
            $saleRate = $latestExchangeRate->getSaleRate();
            $timestamp = $latestExchangeRate->getTimestamp();
        }

        $this->setExchangeRate($buyRate, $saleRate, $timestamp);
    }

    public function __serialize(): array
    {
        return [
            'grabber_class_name' => $this->grabberClassName,
            'base_currency_code' => $this->baseCurrencyCode,
            'destination_currency_code' => $this->destinationCurrencyCode,
            'buy_rate' => $this->buyRate,
            'sale_rate' => $this->saleRate,
            'timestamp' => \serialize($this->timestamp),
            'observers_class_names' => \array_keys($this->observers),
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->__construct(
            $data['grabber_class_name'],
            $data['base_currency_code'],
            $data['destination_currency_code'],
            $data['buy_rate'],
            $data['sale_rate'],
            \unserialize($data['timestamp'], ['allowed_classes' => [\DateTime::class]]),
            $data['observers_class_names']
        );
    }

    public function attachObservers(array $observers): void
    {
        foreach ($observers as $observer) {
            if (!\is_string($observer) && !\is_object($observer)) {
                throw new \TypeError();
            }

            if (\is_string($observer)) {
                if (
                    !\class_exists($observer)
                    || !\is_subclass_of($observer, ExchangeRateObserver::class)
                ) {
                    throw new \ValueError(
                        \sprintf(
                            '%s is not instance of %s class.',
                            $observer,
                            ExchangeRateObserver::class
                        )
                    );
                }

                $this->observers[$observer] = new $observer;
            } else {
                if (!($observer instanceof ExchangeRateObserver)) {
                    throw new \ValueError(
                        \sprintf(
                            '%s object is not instance of %s class.',
                            \get_class($observer),
                            ExchangeRateObserver::class
                        )
                    );
                }

                $this->observers[\get_class($observer)] = $observer;
            }
        }
    }

    public function detachObservers(?array $observers = null): void
    {
        if (\is_null($observers)) {
            $this->observers = [];
        } else {
            foreach ($observers as $observer) {
                if (!\is_string($observer) && !\is_object($observer)) {
                    throw new \TypeError();
                }

                if (\is_string($observer)) {
                    if (
                        !\class_exists($observer)
                        || !\is_subclass_of($observer, ExchangeRateObserver::class)
                    ) {
                        throw new \ValueError(
                            \sprintf(
                                '%s is not instance of %s class.',
                                $observer,
                                ExchangeRateObserver::class
                            )
                        );
                    }

                    unset($this->observers[$observer]);
                } else {
                    if (!($observer instanceof ExchangeRateObserver)) {
                        throw new \ValueError(
                            \sprintf(
                                '%s object is not instance of %s class.',
                                \get_class($observer),
                                ExchangeRateObserver::class
                            )
                        );
                    }

                    unset($this->observers[\get_class($observer)]);
                }
            }
        }
    }

    public function notifyExchangeRateCreated(): void
    {
        foreach ($this->observers as $observer) {
            $observer->exchangeRateCreated($this);
        }
    }

    public function notifyExchangeRateUpdated(ExchangeRate $preExchangeRate): void
    {
        foreach ($this->observers as $observer) {
            $observer->exchangeRateUpdated($preExchangeRate, $this);
        }
    }

    public function notifyExchangeRateChanged(ExchangeRate $preExchangeRate): void
    {
        foreach ($this->observers as $observer) {
            $observer->exchangeRateChanged($preExchangeRate, $this);
        }
    }

    public function updateExchangeRate(float $buyRate, float $saleRate, ?\DateTime $timestamp = null): void
    {
        // TODO: check clone
        $preExchangeRate = clone $this;

        $this->setExchangeRate($buyRate, $saleRate, $timestamp);

        $this->notifyExchangeRateUpdated($preExchangeRate);

        if ($this->isExchangeRateChanged($preExchangeRate)) {
            $this->notifyExchangeRateChanged($preExchangeRate);
        }
    }

    public function refresh(): void
    {
        $latestExchangeRate = $this->getGrabber()->getExchangeRate(
            $this->getBaseCurrencyCode(),
            $this->getDestinationCurrencyCode()
        );

        $this->updateExchangeRate(
            $latestExchangeRate->getBuyRate(),
            $latestExchangeRate->getSaleRate(),
            $latestExchangeRate->getTimestamp()
        );
    }

    public function getBaseCurrencyCode(): string
    {
        return $this->baseCurrencyCode;
    }

    public function getDestinationCurrencyCode(): string
    {
        return $this->destinationCurrencyCode;
    }

    public function getBuyRate(): float
    {
        return $this->buyRate;
    }

    public function getSaleRate(): float
    {
        return $this->saleRate;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getGrabber(): Grabber
    {
        return new $this->grabberClassName;
    }

    private function setExchangeRate(float $buyRate, float $saleRate, ?\DateTime $timestamp = null): void
    {
        $this->buyRate = $buyRate;
        $this->saleRate = $saleRate;

        $this->timestamp = \is_null($timestamp) ? new \DateTime() : $timestamp;
    }

    private function isExchangeRateChanged(ExchangeRate $preExchangeRate): bool
    {
        return
            !(\abs($this->getBuyRate() - $preExchangeRate->getBuyRate()) < \PHP_FLOAT_EPSILON)
            || !(\abs($this->getSaleRate() - $preExchangeRate->getSaleRate()) < \PHP_FLOAT_EPSILON);
    }
}