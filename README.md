# EasyBell

Kata09 "Back to the Checkout" — a supermarket checkout where every product has a price and optional bulk deals (like "3 for 130"). Prices and deals live in a config file, changing a price or adding a deal never means editing program logic.

## Business assumptions

- **Each product has at most one offer at a time.** Activating two offers on the same item is a configuration error — `PricingConfiguration::resolve()` fails loudly instead of guessing. No offer means unit price.
- **Prices are integer cents.** No currency, rounding, or fractions; totals are pure integer addition.
- **Offers are quantity-based only.** No purchase limits, customer tiers, dates, or other conditions.
- **How you add items doesn't affect the price.** Discounts are only calculated when you call `total()` — it looks at the final count of each item, not the order you scanned them in.

## Tech stack

| Tool | Version / role |
|---|---|
| PHP | 8.5  |
| Laravel | 13.17 — application framework |
| PHPUnit | 12.5 — unit + feature tests, code coverage (PCOV) |
| Laravel Pint | 1.x — code style (CI-enforced) |
| Docker + Docker Compose | App containerization (`php:8.5-fpm`) |


## Running

Everything runs through Docker + the Makefile (no local PHP needed).

| Command | Purpose |
|---|---|
| `make up` | Build and start app + db containers |
| `make scan ITEMS="AAA BB D"` | Run the checkout against the given items |
| `make install` | Run `composer install` inside the app container |


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
| HTTP endpoints | No controllers; the kata is a service-layer exercise. | Exposing the checkout over an API. |
| Persistence / orders | No repositories or models. | Storing scan history or order receipts. |
| Time-bound offers | An `active` offer is always valid — no start/end dates. | `valid_from` / `valid_to` on an offer so a promotion auto-expires. |
| Structured logging | CLI echoes results to stdout; nothing persists. | The checkout runs unattended — HTTP API, queue job, scheduled run — where nobody is watching stdout and you need the audit trail. |

