<?php

declare(strict_types=1);

namespace NassFloPetr\ExchangeRateGrabber\Models;

use NassFloPetr\Grabber\Models\Model;
use NassFloPetr\Grabber\Grabbers\Grabber;
use NassFloPetr\Grabber\Observers\Observer;

class ExchangeRateModel extends Model
{
    private string $baseCurrencyCode;
    private string $destinationCurrencyCode;
    private float $buyRate;
    private float $saleRate;

    public function __construct(
        Grabber $grabber,
        string $baseCurrencyCode,
        string $destinationCurrencyCode,
        float $buyRate,
        float $saleRate,
        array $observers = [],
        ?\DateTime $timestamp = null,
    )
    {
        if (
            !\preg_match('/^[A-Z]{3}$/', $baseCurrencyCode) ||
            !\preg_match('/^[A-Z]{3}$/',  $destinationCurrencyCode)
        ) {
            throw new \ValueError('Invalid base or destination currency code format.');
        }

        $this->baseCurrencyCode = $baseCurrencyCode;
        $this->destinationCurrencyCode = $destinationCurrencyCode;
        $this->buyRate = $buyRate;
        $this->saleRate = $saleRate;

        parent::__construct($grabber, $observers, $timestamp);
    }

    public function __serialize(): array
    {
        return \array_merge(
            [
                'base_currency_code' => $this->baseCurrencyCode,
                'destination_currency_code' => $this->destinationCurrencyCode,
                'buy_rate' => $this->buyRate,
                'sale_rate' => $this->saleRate,
            ],
            parent::__serialize()
        );
    }

    public function __unserialize(array $data): void
    {
        $this->__construct(
            \unserialize($data['grabber'], ['allowed_classes' => [Grabber::class]]),
            $data['base_currency_code'],
            $data['destination_currency_code'],
            $data['buy_rate'],
            $data['sale_rate'],
            $data['observers_class_names'],
            \unserialize($data['timestamp'], ['allowed_classes' => [\DateTime::class]]),
        );
    }

    public function __clone(): void
    {
        $this->__construct(
            $this->grabber,
            $this->baseCurrencyCode,
            $this->destinationCurrencyCode,
            $this->buyRate,
            $this->saleRate,
            $this->observers,
            $this->timestamp,
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

    public function update(Model $model): void
    {
        if (!($model instanceof ExchangeRateModel)) {
            throw new \ValueError(
                \sprintf(
                    '%s object is not instance of %s class.',
                    \get_class($model),
                    ExchangeRateModel::class
                )
            );
        }

        $preExchangeRate = clone $this;

        $this->buyRate = $model->getBuyRate();
        $this->saleRate = $model->getSaleRate();
        $this->timestamp = $model->getTimestamp();

        $this->notifyUpdated($preExchangeRate);

        if ($this->isExchangeRateChanged($preExchangeRate)) {
            $this->notifyChanged($preExchangeRate);
        }
    }

    private function isExchangeRateChanged(ExchangeRate $preExchangeRate): bool
    {
        return
            !(\abs($this->getBuyRate() - $preExchangeRate->getBuyRate()) < \PHP_FLOAT_EPSILON)
            || !(\abs($this->getSaleRate() - $preExchangeRate->getSaleRate()) < \PHP_FLOAT_EPSILON);
    }
}