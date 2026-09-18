<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

final class PriceRuleRegistry
{
    /** @var array<string, class-string<PriceRule>> */
    private array $strategies;

    public function __construct(array $strategies = [])
    {
        $this->strategies = $strategies + [
            'flat' => FlatPrice::class,
            'multiprice' => Multiprice::class,
            'buyonegetone' => BuyOneGetOne::class,
        ];
    }

    public function register(string $type, string $class): void
    {
        $this->strategies[$type] = $class;
    }

    /** @return class-string<PriceRule> */
    public function classFor(string $type): string
    {
        if (! isset($this->strategies[$type])) {
            throw new InvalidArgumentException("Unknown pricing strategy [{$type}]");
        }

        return $this->strategies[$type];
    }
}
