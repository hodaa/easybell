<?php

namespace App\Console\Commands;

use App\Services\Checkout;
use App\Services\Pricing\PriceRuleFactory;
use App\Services\Pricing\PriceRuleRegistry;
use App\Services\Pricing\PricingConfiguration;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class CheckoutScan extends Command
{
    protected $signature = 'checkout:scan {items* : letters to scan, e.g. "A A A" or "AAA"}';

    protected $description = 'Scan items through the checkout and print the running total';

    public function handle(): int
    {
        $checkout = $this->checkout();

        if ($this->argument('items') === []) {
            $this->interactive($checkout);

            return self::SUCCESS;
        }

        foreach ($this->argument('items') as $token) {
            foreach (str_split($token) as $item) {
                try {
                    $checkout->scan($item);
                } catch (InvalidArgumentException $e) {
                    $this->error($e->getMessage());

                    return self::FAILURE;
                }

                $this->line("scan {$item}  ->  total {$checkout->total()}");
            }
        }

        return self::SUCCESS;
    }

    private function checkout(): Checkout
    {
        $rules = (new PricingConfiguration(new PriceRuleFactory(new PriceRuleRegistry)))->resolve(config('checkout.rules'));

        return new Checkout($rules);
    }

    private function interactive(Checkout $checkout): void
    {
        $this->info('Type letters to scan, a blank line to stop.');

        while (($line = fgets(STDIN)) !== false) {
            if (trim($line) === '') {
                break;
            }

            foreach (str_split(str_replace(' ', '', trim($line))) as $item) {
                try {
                    $checkout->scan($item);
                } catch (InvalidArgumentException $e) {
                    $this->error($e->getMessage());
                }
            }

            $this->line("total: {$checkout->total()}");
        }
    }
}
