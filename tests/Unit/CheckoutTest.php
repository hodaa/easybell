<?php

namespace Tests\Unit;

use App\Services\Checkout;
use App\Services\Pricing\FlatPrice;
use App\Services\Pricing\PriceRule;
use App\Services\Pricing\PriceRuleFactory;
use App\Services\Pricing\PriceRuleRegistry;
use App\Services\Pricing\PricingConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CheckoutTest extends TestCase
{
    private const RULES = [
        'A' => [
            'unit' => 50,
            'offers' => [['type' => 'multiprice', 'bundle_count' => 3, 'bundle_price' => 130, 'active' => true]],
        ],
        'B' => [
            'unit' => 30,
            'offers' => [['type' => 'multiprice', 'bundle_count' => 2, 'bundle_price' => 45, 'active' => true]],
        ],
        'C' => ['unit' => 20, 'offers' => []],
        'D' => ['unit' => 15, 'offers' => []],
    ];

    private function configuration(): PricingConfiguration
    {
        return new PricingConfiguration(new PriceRuleFactory(new PriceRuleRegistry));
    }

    private function checkout(): Checkout
    {
        return new Checkout($this->configuration()->resolve(self::RULES));
    }

    private function price(string $goods): int
    {
        $checkout = $this->checkout();

        foreach (str_split($goods) as $item) {
            $checkout->scan($item);
        }

        return $checkout->total();
    }

    public function test_totals(): void
    {
        $this->assertSame(0, $this->price(''));
        $this->assertSame(50, $this->price('A'));
        $this->assertSame(80, $this->price('AB'));
        $this->assertSame(115, $this->price('CDBA'));

        $this->assertSame(100, $this->price('AA'));
        $this->assertSame(130, $this->price('AAA'));
        $this->assertSame(180, $this->price('AAAA'));
        $this->assertSame(230, $this->price('AAAAA'));
        $this->assertSame(260, $this->price('AAAAAA'));

        $this->assertSame(160, $this->price('AAAB'));
        $this->assertSame(175, $this->price('AAABB'));
        $this->assertSame(190, $this->price('AAABBD'));
        $this->assertSame(190, $this->price('DABABA'));
    }

    public function test_incremental(): void
    {
        $checkout = $this->checkout();

        $this->assertSame(0, $checkout->total());
        $checkout->scan('A');
        $this->assertSame(50, $checkout->total());
        $checkout->scan('B');
        $this->assertSame(80, $checkout->total());
        $checkout->scan('A');
        $this->assertSame(130, $checkout->total());
        $checkout->scan('A');
        $this->assertSame(160, $checkout->total());
        $checkout->scan('B');
        $this->assertSame(175, $checkout->total());
    }

    public function test_unknown_item_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->checkout()->scan('Z');
    }

    public function test_unknown_strategy_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration()->resolve([
            'X' => ['unit' => 10, 'offers' => [['type' => 'not-a-strategy', 'active' => true]]],
        ]);
    }

    public function test_inactive_offer_is_ignored(): void
    {
        $checkout = new Checkout($this->configuration()->resolve([
            'X' => [
                'unit' => 10,
                'offers' => [
                    ['type' => 'multiprice', 'bundle_count' => 2, 'bundle_price' => 15, 'active' => true],
                    ['type' => 'multiprice', 'bundle_count' => 1, 'bundle_price' => 1, 'active' => false],
                ],
            ],
        ]));

        $checkout->scan('X');
        $checkout->scan('X');

        $this->assertSame(15, $checkout->total());
    }

    public function test_multiple_active_offers_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration()->resolve([
            'X' => [
                'unit' => 10,
                'offers' => [
                    ['type' => 'multiprice', 'bundle_count' => 2, 'bundle_price' => 15, 'active' => true],
                    ['type' => 'multiprice', 'bundle_count' => 3, 'bundle_price' => 20, 'active' => true],
                ],
            ],
        ]);
    }

    public function test_no_active_offer_prices_at_unit(): void
    {
        $checkout = new Checkout($this->configuration()->resolve([
            'X' => ['unit' => 10, 'offers' => [
                ['type' => 'multiprice', 'bundle_count' => 2, 'bundle_price' => 5, 'active' => false],
            ]],
        ]));

        $checkout->scan('X');
        $checkout->scan('X');

        $this->assertSame(20, $checkout->total());
    }

    public function test_missing_offer_field_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration()->resolve([
            'X' => ['unit' => 10, 'offers' => [
                ['type' => 'multiprice', 'bundle_price' => 130, 'active' => true],
            ]],
        ]);
    }

    public function test_flat_offer_requires_unit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        FlatPrice::fromConfig(['type' => 'flat']);
    }

    public function test_zero_bundle_count_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration()->resolve([
            'X' => ['unit' => 10, 'offers' => [
                ['type' => 'multiprice', 'bundle_count' => 0, 'bundle_price' => 20, 'active' => true],
            ]],
        ]);
    }

    public function test_buyonegetone_missing_field_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration()->resolve([
            'X' => ['unit' => 10, 'offers' => [
                ['type' => 'buyonegetone', 'bundle_price' => 20, 'active' => true],
            ]],
        ]);
    }

    public function test_zero_bundle_count_rejected_for_buyonegetone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->configuration()->resolve([
            'X' => ['unit' => 10, 'offers' => [
                ['type' => 'buyonegetone', 'bundle_count' => 0, 'bundle_price' => 20, 'active' => true],
            ]],
        ]);
    }

    public function test_default_strategy_cannot_be_overridden_in_constructor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PriceRuleRegistry(['flat' => FlatPrice::class]);
    }

    public function test_registered_strategy_cannot_be_replaced(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $registry = new PriceRuleRegistry;
        $registry->register('double', FlatPrice::class);
        $registry->register('double', Multiprice::class);
    }

    public function test_custom_strategy_can_be_registered(): void
    {
        $double = new class implements PriceRule
        {
            public static function fromConfig(array $offer): static
            {
                return new self;
            }

            public function calculatePrice(int $count): int
            {
                return $count * 2;
            }
        };

        $registry = new PriceRuleRegistry;
        $registry->register('double', $double::class);

        $rules = (new PricingConfiguration(new PriceRuleFactory($registry)))->resolve([
            'X' => ['unit' => 10, 'offers' => [['type' => 'double', 'active' => true]]],
        ]);
        $checkout = new Checkout($rules);
        $checkout->scan('X');
        $checkout->scan('X');

        $this->assertSame(4, $checkout->total());
    }
}
