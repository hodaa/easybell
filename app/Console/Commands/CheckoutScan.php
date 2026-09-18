<?php

namespace App\Console\Commands;

use App\Services\Checkout;
use Illuminate\Console\Command;
use InvalidArgumentException;

final class CheckoutScan extends Command
{
    protected $signature = 'checkout:scan {items?* : letters to scan, e.g. "A A A" or "AAA"}';

    protected $description = 'Scan items through the checkout and print the running total';

    /** @param  resource|null  $stdin */
    public function __construct(
        private Checkout $checkout,
        private mixed $stdin = null,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->argument('items') === []) {
            $this->interactive();

            return self::SUCCESS;
        }

        foreach ($this->argument('items') as $token) {
            foreach (str_split($token) as $item) {
                try {
                    $this->checkout->scan($item);
                } catch (InvalidArgumentException $e) {
                    $this->error($e->getMessage());

                    return self::FAILURE;
                }

                $this->line("scan {$item}  ->  total {$this->checkout->total()}");
            }
        }

        return self::SUCCESS;
    }

    private function interactive(): void
    {
        $this->info('Type letters to scan, a blank line to stop.');

        while (($line = fgets($this->stdin ?? STDIN)) !== false) {
            if (trim($line) === '') {
                break;
            }

            foreach (str_split(str_replace(' ', '', trim($line))) as $item) {
                try {
                    $this->checkout->scan($item);
                } catch (InvalidArgumentException $e) {
                    $this->error($e->getMessage());
                }
            }

            $this->line("total: {$this->checkout->total()}");
        }
    }
}
