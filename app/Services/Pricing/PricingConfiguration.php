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
        $unit = $this->integer($definition['unit'] ?? null, "Item [{$item}] unit");

        if (isset($definition['offers']) && ! is_array($definition['offers'])) {
            throw new InvalidArgumentException("Item [{$item}] offers must be a list");
        }

        $offers = array_values(array_filter(
            array_map(
                fn (mixed $offer): array => $this->normalize($item, $offer),
                $definition['offers'] ?? [],
            ),
            fn (array $offer): bool => $offer['active'] ?? false,
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

    private function normalize(string $item, mixed $offer): array
    {
        if (! is_array($offer)) {
            throw new InvalidArgumentException("Item [{$item}] offers must be arrays");
        }

        $type = $offer['type'] ?? null;
        if (! is_string($type)) {
            throw new InvalidArgumentException("Item [{$item}] offer type must be a string");
        }

        foreach (['unit', 'bundle_count', 'bundle_price'] as $field) {
            if (array_key_exists($field, $offer)) {
                $offer[$field] = $this->integer($offer[$field], "Item [{$item}] {$field}");
            }
        }

        if (array_key_exists('active', $offer) && ! is_bool($offer['active'])) {
            throw new InvalidArgumentException("Item [{$item}] active must be a boolean");
        }

        return $offer;
    }

    private function integer(mixed $value, string $label): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && preg_match('/^-?\d+$/D', $value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException("{$label} must be an integer");
    }
}
