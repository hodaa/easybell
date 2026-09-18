<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

final class Multiprice implements PriceRule
{
    public function __construct(
        private int $unit,
        private int $bundleCount,
        private int $bundlePrice,
    ) {
        if ($this->bundleCount < 1) {
            throw new InvalidArgumentException('bundleCount must be at least 1');
        }
    }

    public static function fromConfig(array $offer): self
    {
        $type = $offer['type'] ?? 'offer';

        foreach (['unit', 'bundle_count', 'bundle_price'] as $field) {
            if (! isset($offer[$field])) {
                throw new InvalidArgumentException("{$type} pricing requires a {$field}");
            }
        }

        return new self($offer['unit'], $offer['bundle_count'], $offer['bundle_price']);
    }

    public function calculatePrice(int $count): int
    {
        $bundles = intdiv($count, $this->bundleCount);

        return $bundles * $this->bundlePrice + ($count % $this->bundleCount) * $this->unit;
    }
}
