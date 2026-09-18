# EasyBell

Kata09 "Back to the Checkout" in Laravel 13 + PHP 8.5. A configurable supermarket checkout: each SKU carries a unit price and optional volume offers, all pricing math lives in services, and rules are data — no hardcoded branches.

## Running

Everything runs through Docker + the Makefile (no local PHP needed).

| Command | Purpose |
|---|---|
| `make up` | Build and start app + db containers |
| `make down` | Stop containers |
| `make build` | Rebuild the app image |
| `make logs` | Tail container logs |
| `make shell` | Open a bash shell in the app container |
| `make serve` | Run `php artisan serve` on :8000 (inside container) |
| `make scan ITEMS="AAA BB D"` | Run the checkout against the given items |
| `make install` | Run `composer install` inside the app container |

Without Docker: `php artisan serve`, `./vendor/bin/phpunit`, `./vendor/bin/pint`.

## Using the checkout

```sh
make scan ITEMS="AAA BB D"     # via Docker: A=100, B=45, D=15 → total 160
php artisan checkout:scan AAA BB D      # same, without Docker
php artisan checkout:scan DABABA        # same items, different order → 160
```

Without `ITEMS=`, the command runs interactively; a blank line stops it.

## Pricing rules (rules-as-data)

Rules live in `config/checkout.php` — a price change is a config edit, not a code change. Each item declares a unit price and an optional list of offers:

```php
'A' => [
    'unit' => 50,
    'offers' => [
        ['type' => 'multiprice', 'bundle_count' => 3, 'bundle_price' => 130, 'active' => false],
        ['type' => 'buyonegetone', 'bundle_count' => 2, 'bundle_price' => 50, 'active' => true],
    ],
],
```

- `type` maps to a `PriceRule` strategy (`multiprice`, `buyonegetone`, or the implicit `flat` fallback when no offer is active).
- **At most one offer may be `active` per item** — `PricingConfiguration::resolve()` fails loudly on more than one, and falls back to the unit price when none is active.
- `scan` only counts; all pricing math happens in `total()`, per SKU, from counts.
- Unknown SKUs and invalid configuration throw `InvalidArgumentException` — never silently ignored.

## Architecture

Strict SOLID layering (see `AGENTS.md` for the full working contract):

| Layer | Location | Responsibility |
|---|---|---|
| Command | `app/Console/Commands` | Composition root + CLI IO. Wires the pricing chain and calls `Checkout`. |
| Service | `app/Services` | Business rules. `Checkout` owns scanning and totals. |
| Pricing | `app/Services/Pricing` | Strategy pattern: `PriceRule` interface, one class per offer style, `PriceRuleRegistry` (type→class map), `PriceRuleFactory` (instantiation), `PricingConfiguration` (config→rules translation). |
| Config | `config/checkout.php` | Tuneable business data. |

Principles held:

- **Composition boundary** — `Checkout` receives resolved `PriceRule` objects; it never reads config or instantiates rules.
- **Open/closed** — adding a new offer style is a new `PriceRule` class plus one `register()` call in `PriceRuleRegistry`. Callers never change.
- **Dependency inversion** — services depend on the `PriceRule` interface, never on concrete rules.
- **Single responsibility** — commands handle IO only; pricing is pure math; `scan` counts, `total` prices.

### Adding a new offer style

1. Implement `PriceRule` (`app/Services/Pricing`): a constructor accepting its config and `price(int $count): int`.
2. Register it: `$registry->register('mystyle', MyStyle::class)` (defaults live in `PriceRuleRegistry::__construct`).
3. Reference it in `config/checkout.php` — done. `Checkout` and the factory are untouched.

## Testing

Unit + feature suites run on `sqlite:memory:`.

```sh
make test                                         # Docker: phpunit suite
./vendor/bin/phpunit                              # local phpunit
./vendor/bin/phpunit --coverage-text               # coverage (needs pcov / xdebug)
./vendor/bin/phpunit --coverage-html=coverage      # HTML coverage report
```

Coverage of the pricing domain is ~100% of executable paths; the remaining gaps are the CLI's interactive branches and defensive configuration guards.

## CI

`.github/workflows/ci.yml` runs on push to `main` and on PRs: composer install (cached), `pint --test` style gate, and the PHPUnit suite with PCOV coverage.

## Code style

Laravel Pint keeps the repo consistent.

```sh
make pint            # Docker
./vendor/bin/pint    # local
./vendor/bin/pint --test   # check-only (CI uses this)
```


## Areas to improve

These are deliberate deferrals, not missing work — each is the right thing to add when a requirement actually appears.

| Area | Current state | When to add |
|---|---|---|
| Money value object | Integer-cents everywhere; arithmetic is plain addition. | A second currency, rounding rule, or money crossing a system boundary. |
| Multiple active offers | Fails loudly; at most one `active` offer per item. | A real requirement for discount stacking/composition. |
| HTTP endpoints | No controllers; the kata is a service-layer exercise. | Exposing the checkout over an API. |
| Persistence / orders | No repositories or models. | Storing scan history or order receipts. |
# easybell
