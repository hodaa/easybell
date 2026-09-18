<?php

namespace App\Services\Pricing;

interface ConfigurablePriceRule extends PriceRule
{
    /** @param  array<string, mixed>  $offer */
    public static function fromConfig(array $offer): self;
}
