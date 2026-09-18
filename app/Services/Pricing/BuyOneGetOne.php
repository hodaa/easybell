<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

final class BuyOneGetOne implements ConfigurablePriceRule
{
    public function __construct(
        private int $unit,
        private int $bundleCount,
    ) {
        if ($this->bundleCount < 2) {
            throw new InvalidArgumentException('bundleCount must be at least 2 for a buy-one-get-one offer');
        }
    }

    public static function fromConfig(array $offer): self
    {
        $type = $offer['type'] ?? 'offer';

        foreach (['unit', 'bundle_count'] as $field) {
            if (! isset($offer[$field])) {
                throw new InvalidArgumentException("{$type} pricing requires a {$field}");
            }
        }

        return new self($offer['unit'], $offer['bundle_count']);
    }

    public function calculatePrice(int $count): int
    {
        $bundles = intdiv($count, $this->bundleCount);
        $freePerBundle = $this->bundleCount - 1;

        return $bundles * $freePerBundle * $this->unit + ($count % $this->bundleCount) * $this->unit;
    }
}
