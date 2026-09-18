<?php

namespace App\Services;

use App\Services\Pricing\PriceRule;
use InvalidArgumentException;

final class Checkout
{
    private array $counts = [];

    /** @param array<string, PriceRule> $pricingRules */
    public function __construct(
        private array $pricingRules
    ) {}

    public function scan(string $item): void
    {
        if (! isset($this->pricingRules[$item])) {
            throw new InvalidArgumentException("Unknown item [{$item}]");
        }

        $this->counts[$item] = ($this->counts[$item] ?? 0) + 1;
    }

    public function total(): int
    {
        $total = 0;

        foreach ($this->counts as $item => $count) {
            $total += $this->pricingRules[$item]->calculatePrice($count);
        }

        return $total;
    }
}
