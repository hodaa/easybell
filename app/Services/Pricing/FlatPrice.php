<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

final class FlatPrice implements PriceRule
{
    public function __construct(
        private int $unit,
    ) {}

    public static function fromConfig(array $offer): self
    {
        $type = $offer['type'] ?? 'offer';

        if (! isset($offer['unit'])) {
            throw new InvalidArgumentException("{$type} pricing requires a unit");
        }

        return new self($offer['unit']);
    }

    public function price(int $count): int
    {
        return $count * $this->unit;
    }
}
