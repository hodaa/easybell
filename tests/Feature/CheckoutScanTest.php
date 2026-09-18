<?php

namespace Tests\Feature;

use App\Console\Commands\CheckoutScan;
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

    public function test_interactive_mode_reads_until_blank_line(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "AA\nB\n\n");
        rewind($stream);

        $this->app->instance(CheckoutScan::class, new CheckoutScan($stream));

        $this->artisan('checkout:scan')
            ->expectsOutputToContain('total: 50')
            ->expectsOutputToContain('total: 80')
            ->assertExitCode(0);

        fclose($stream);
    }

    public function test_interactive_mode_reports_unknown_item_and_continues(): void
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, "Z\nB\n\n");
        rewind($stream);

        $this->app->instance(CheckoutScan::class, new CheckoutScan($stream));

        $this->artisan('checkout:scan')
            ->expectsOutputToContain('Unknown item [Z]')
            ->expectsOutputToContain('total: 30')
            ->assertExitCode(0);

        fclose($stream);
    }
}
