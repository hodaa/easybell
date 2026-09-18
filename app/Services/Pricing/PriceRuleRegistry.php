<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

final class PriceRuleRegistry
{
    /** @var array<string, class-string<ConfigurablePriceRule>> */
    private array $strategies;

    public function __construct(array $strategies = [])
    {
        $this->strategies = [
            'flat' => FlatPrice::class,
            'multiprice' => Multiprice::class,
            'buyonegetone' => BuyOneGetOne::class,
        ];

        foreach ($strategies as $type => $class) {
            $this->register($type, $class);
        }
    }

    public function register(string $type, string $class): void
    {
        if (isset($this->strategies[$type])) {
            throw new InvalidArgumentException("Pricing strategy [{$type}] is already registered");
        }

        if (! is_a($class, ConfigurablePriceRule::class, true)) {
            throw new InvalidArgumentException("Pricing strategy [{$type}] must implement ".ConfigurablePriceRule::class);
        }

        $this->strategies[$type] = $class;
    }

    /**
     * @return class-string<ConfigurablePriceRule>
     */
    public function classFor(string $type): string
    {
        if (! isset($this->strategies[$type])) {
            throw new InvalidArgumentException("Unknown pricing strategy [{$type}]");
        }

        return $this->strategies[$type];
    }
}
