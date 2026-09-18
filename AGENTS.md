# EasyBell

Laravel 13 + PHP 8.5 application. This document defines the architecture and workflow; follow it for any change.

## Running the app

Everything runs through Docker + the Makefile (no local PHP needed):

```sh
make up       # build + start app and db containers
make logs     # tail container logs
make shell    # bash into the app container
make serve    # php artisan serve on :8000
make test     # run the test suite
make pint     # format code (Laravel Pint)
make down     # stop containers
```

Alternative without Docker: `php artisan serve`, `./vendor/bin/phpunit`, `./vendor/bin/pint`.

## Architecture

The codebase follows SOLID principles and a strict layering. Do not let logic leak across layers.

| Layer | Location | Responsibility |
|---|---|---|
| Controller | `app/Http/Controllers` | HTTP only: validate the request, call one service, map the result to a response. **Thin — no business rules, no Eloquent, no DB calls.** |
| Service | `app/Services` | Business rules and use cases. One service per use case. This is where domain logic lives (e.g. `Checkout` owns pricing, not a controller). |
| Config | `config/` | Tuneable business data (e.g. pricing rules), so behavior changes without code edits. |

### Concrete conventions

- **Dependency rule:** services depend on **interfaces**, never on concrete models, `DB` facades, or concrete strategies inline. Constructor injection everywhere; resolve through the container — never `new` where dependencies can vary.
- **Composition boundary:** services never read configuration or instantiate their own strategies/dependencies. `Checkout` receives already-resolved `PriceRule` objects and nothing else; reading `config('checkout.rules')` and calling `PricingConfiguration::resolve()` belongs only to the composition root (command, controller, or test).
- **Open/closed:** adding a *new type* of rule or behavior (pricing style, payment provider, notification channel) must not modify existing callers — introduce a new class behind the existing interface (strategy pattern). See `app/Services/Pricing`: `PriceRuleRegistry` maps `type` → strategy class (`register()`), `PriceRuleFactory` instantiates from a registry, `PricingConfiguration` assembles config into a rules map for `Checkout`. A new offer style is a new `PriceRule` class plus one `register()` call — `Checkout` never changes.
- **Single responsibility:** one class, one reason to change. A controller that computes prices, a model that formats HTTP responses, or a service that also persists is a violation — split it.
- **Kata standing rule (rules-as-data):** pricing rules are plain data in `config/checkout.php` (and/or injected per transaction), *not* hardcoded branches. Each item declares a unit price and an `offers` list; **at most one offer may be `active` per item** — `PricingConfiguration::resolve()` fails loudly otherwise and falls back to unit price when none is active. `scan` only counts; all pricing math happens in `total()`, per SKU, from counts.
- **Unknown input:** validate and fail loudly (`InvalidArgumentException` for unknown SKUs). Never silently ignore.

## Testing

- Unit tests in `tests/Unit` for services and business rules (no framework state where avoidable).
- Feature tests in `tests/Feature` for HTTP + persistence flows.
- Run `make test` before finishing any change; ensure the suite is green.

## Code style

- Laravel Pint (`make pint`) — the repo is self-consistent; format what you touch.
- No comments explaining *what* code does; name things instead.