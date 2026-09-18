<?php

namespace App\Services\Pricing;

use InvalidArgumentException;

final class PricingConfiguration
{
    public function __construct(private PriceRuleFactory $factory) {}

    /**
     * @param  array<string, array<string, mixed>>  $rules
     * @return array<string, PriceRule>
     */
    public function resolve(array $rules): array
    {
        $resolved = [];

        foreach ($rules as $item => $definition) {
            $resolved[$item] = $this->activeOffer($item, $definition);
        }

        return $resolved;
    }

    private function activeOffer(string $item, array $definition): PriceRule
    {
        $unit = $definition['unit'] ?? null;
        $offers = array_values(array_filter(
            $definition['offers'] ?? [],
            fn ($offer) => $offer['active'] ?? false,
        ));

        if (count($offers) > 1) {
            throw new InvalidArgumentException("Item [{$item}] has more than one active offer");
        }

        if ($offers === []) {
            return $this->factory->make(['type' => 'flat', 'unit' => $unit]);
        }

        $offer = $offers[0];
        unset($offer['active']);
        $offer['unit'] = $unit;

        return $this->factory->make($offer);
    }
}
