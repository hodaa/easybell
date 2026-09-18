<?php

namespace App\Services\Pricing;

interface PriceRule
{
    /** @param  array<string, mixed>  $offer */
    public static function fromConfig(array $offer): self;

    public function price(int $count): int;
}
