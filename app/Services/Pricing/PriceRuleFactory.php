<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

final class PriceRuleFactory
{
    public function __construct(private PriceRuleRegistry $registry) {}

    public function make(array $offer): PriceRule
    {
        $type = $offer['type'] ?? throw new InvalidArgumentException('pricing offer requires a type');

        $class = $this->registry->classFor($type);

        return $class::fromConfig($offer);
    }
}
