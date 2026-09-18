<?php

namespace App\Services\Pricing;

interface PriceRule
{
    public function calculatePrice(int $count): int;
}
