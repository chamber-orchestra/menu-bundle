# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Symfony bundle providing a reusable navigation/menu system — navigation builders, item trees, route-based matching, role-based access, Twig rendering, and optional caching.

**Package:** `chamber-orchestra/menu-bundle`
**Requirements:** PHP ^8.5, `ext-ds`
**Namespace:** `ChamberOrchestra\MenuBundle` (PSR-4 from `src/`)
**Bundle class:** `ChamberOrchestraMenuBundle`

## Build and Test Commands

```bash
# Install dependencies
composer install

# Run all tests
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/Unit/Matcher/MatcherTest.php

# Run tests in specific directory
./vendor/bin/phpunit tests/Unit/Factory/

# Run single test method
./vendor/bin/phpunit --filter testMethodName

# Run static analysis (level max)
composer run-script analyse

# Check code style (dry-run)
composer run-script cs-check

# Auto-fix code style
./vendor/bin/php-cs-fixer fix
```

## Architecture

### Core Flow

1. Implement `NavigationInterface::build(MenuBuilder, array): void` to define a menu tree
2. `NavigationFactory::create($nav, $options)` resolves the navigation (by class name string or object), calls `build()`, returns the root `Item`
3. `TwigRenderer::render(Item, $template, $options)` passes `root`, `matcher`, and `accessor` to a Twig template
4. In Twig: `{{ render_menu('App\\Navigation\\MyNav', '@Bundle/nav.html.twig') }}`

### Navigation Layer (`src/Navigation/`)

- `NavigationInterface` — implement `build()`, `getCacheKey()`, `configureCacheItem()`, `getCacheBeta()` to populate the builder and control caching
- `AbstractNavigation` — minimal base class with default cache key (FQCN) and 0 TTL; two subclasses provide caching strategies:
  - `AbstractCachedNavigation` — 24 h TTL, `chamber_orchestra_menu` tag by default; for static menu structures that survive PSR-6 cache
  - `AbstractStaticNavigation` — 0 TTL, no tags; rebuilds every request (still deduped within same request via `NavigationFactory::$built`); for menus with dynamic data (e.g. badge closures)
- `ClosureNavigation` — wraps a closure as a one-off navigation (0 TTL, never cached across requests)

Navigation services are auto-tagged `chamber_orchestra_menu.navigation` and resolved by `NavigationRegistry` (a `ServiceLocator` keyed by class name).

### Item / Builder Layer (`src/Menu/`, `src/Factory/`)

- `MenuBuilder` — fluent builder: `add(name, options, prepend, section)` → `children()` → `end()` → `build()`
- `Factory` — creates `Item` instances; applies `ExtensionInterface` plugins sorted by priority
- `Item` — tree node with: `name`, `label`, `uri`, `roles`, `attributes`, `badge`, children (`Collection`), `isSection()`

**Item options** passed to `MenuBuilder::add()`:

| Option | Extension | Effect |
|---|---|---|
| `route`, `route_params`, `route_type` | `RoutingExtension` | Generates `uri`; appends to `routes` array |
| `routes` | — | Routes that activate the item (supports regex) |
| `label`, `translation_domain` | `LabelExtension` | Label with optional Symfony translation |
| `roles` | — | Security roles required to show the item |
| `badge` | `BadgeExtension` | Badge count (`int` or `\Closure`); resolved and stored in `extras['badge']` |
| `attributes` | `CoreExtension` | HTML attributes |
| `extras` | `CoreExtension` | Arbitrary extra data |

### Matching (`src/Matcher/`)

- `Matcher` — voter-based; `isCurrent(item)` / `isAncestor(item, depth?)`; results cached in `SplObjectStorage`
- `RouteVoter` — matches `_route` against item's `routes` option; route values are treated as regex patterns; optionally checks `_route_params`

The built-in `RouteVoter` handles route-based matching.

### Access Control (`src/Accessor/`)

- `Accessor` — checks `AuthorizationChecker` against item `roles`; results cached with `Ds\Map` (item-level) and array (role-level)
- Available in Twig templates as `accessor`; call `accessor.hasAccess(item)` to gate rendering

### Rendering (`src/Renderer/`, `src/Twig/`)

- `TwigRenderer` — renders a template with variables: `root` (the menu tree), `matcher`, `accessor`
- `MenuExtension` — registers `render_menu(nav, template, options)` Twig function
- `Helper` — wires `NavigationFactory` + `TwigRenderer` for the Twig runtime

### DI Autoconfiguration

Implementing these interfaces is enough — no manual service config needed:

- `NavigationInterface` → tagged `chamber_orchestra_menu.navigation`
- `ExtensionInterface` → tagged `chamber_orchestra_menu.factory.extension`

`CoreExtension` is registered with priority `-10` (runs last, sets defaults).

### Service Configuration

Services are autowired and autoconfigured via `src/Resources/config/services.php`. Directories excluded from autowiring: `DependencyInjection`, `Resources`, `Exception`, `Navigation`.

## Code Style

- PHP 8.5+ with strict types (`declare(strict_types=1);`)
- PSR-4 autoloading: `ChamberOrchestra\MenuBundle\` → `src/`
- `@PER-CS` + `@Symfony` PHP-CS-Fixer rulesets
- Native function invocations must be backslash-prefixed (e.g., `\array_merge()`, `\sprintf()`, `\count()`)
- No global namespace imports — never use `use function` or `use const`
- Ordered imports (alpha), no unused imports, single quotes, trailing commas in multiline
- 4-space indent
- PHPStan level max
- Commit style: short, action-oriented with optional bracketed scope — `[fix] ...`, `[8.0] ...`

## Dependencies

- Requires PHP 8.5, `ext-ds`, Symfony 8.0 components (`config`, `dependency-injection`, `http-foundation`, `http-kernel`, `routing`, `security-core`), Symfony contracts (`cache-contracts`, `translation-contracts`), `doctrine/collections`, `twig/twig`
- Dev: PHPUnit 13, PHPStan, `php-cs-fixer`, `symfony/cache`
- Main branch is `main`

## Testing Conventions

- Use music thematics for test fixtures and naming (e.g., entity names like `Composition`, `Instrument`, `Rehearsal`, `Score`; file names like `symphony_no_5.pdf`, `violin_concerto.mp3`, `moonlight_sonata.jpg`; prefixes like `scores`, `recordings`)
