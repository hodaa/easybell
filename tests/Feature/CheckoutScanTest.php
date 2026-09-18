<?php

namespace Tests\Feature;

use Tests\TestCase;

class CheckoutScanTest extends TestCase
{
    public function test_scans_argument_items_and_prints_totals(): void
    {
        $this->artisan('checkout:scan', ['items' => ['AAA']])
            ->expectsOutputToContain('total 100')
            ->assertExitCode(0);
    }

    public function test_rejects_unknown_item(): void
    {
        $this->artisan('checkout:scan', ['items' => ['A', 'Z']])
            ->expectsOutput('Unknown item [Z]')
            ->assertExitCode(1);
    }
}
