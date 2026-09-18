<?php

namespace App\Services\Pricing;

final class FlatPrice implements ConfigurablePriceRule
{
    public function __construct(
        private int $unit,
    ) {}

    public static function fromConfig(array $offer): self
    {
        return new self($offer['unit']);
    }

    public function calculatePrice(int $count): int
    {
        return $count * $this->unit;
    }
}
