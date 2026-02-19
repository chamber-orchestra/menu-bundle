# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

A Symfony bundle providing a reusable navigation/menu system — navigation builders, item trees, route-based matching, role-based access, Twig rendering, and optional caching.

**Package:** `chamber-orchestra/menu-bundle`
**Requirements:** PHP ^8.5, `ext-ds`
**Namespace:** `ChamberOrchestra\MenuBundle` (PSR-4 from package root — no `src/` directory)
**Bundle class:** `ChamberOrchestraMenuBundle` — legacy alias `DevMenuBundle` kept for BC

## Architecture

### Core Flow

1. Implement `NavigationInterface::build(MenuBuilderInterface, array): void` to define a menu tree
2. `NavigationFactory::create($nav, $options)` resolves the navigation (by class name string or object), calls `build()`, returns the root `ItemInterface`
3. `TwigRenderer::render(ItemInterface, $template, $options)` passes `root`, `matcher`, and `accessor` to a Twig template
4. In Twig: `{{ render_menu('App\\Navigation\\MyNav', '@Bundle/nav.html.twig') }}`

### Navigation Layer (`Navigation/`)

- `NavigationInterface` — implement `build()` to populate the builder
- `AbstractNavigation` — base class
- `AbstractCachedNavigation` — adds caching (24 h TTL, `navigation` tag by default); override `getCacheKey()` if needed
- `CachedNavigationInterface` — `getCacheKey()`, `configureCacheItem()`, `getCacheBeta()`
- `ClosureNavigation` — wraps a callable as a one-off navigation

Navigation services are auto-tagged `chamber_orchestra_menu.navigation` and resolved by `NavigationRegistry` (a `ServiceLocator` keyed by class name).

### Item / Builder Layer (`Menu/`, `Factory/`)

- `MenuBuilder` — fluent builder: `add(name, options, prepend, section)` → `children()` → `end()` → `build()`
- `Factory` — creates `Item` instances; applies `ExtensionInterface` plugins sorted by priority
- `Item` / `ItemInterface` — tree node with: `name`, `label`, `uri`, `roles`, `attributes`, children (`Collection`), `isSection()`

**Item options** passed to `MenuBuilder::add()`:

| Option | Extension | Effect |
|---|---|---|
| `route`, `route_params`, `route_type` | `RoutingExtension` | Generates `uri`; appends to `routes` array |
| `routes` | — | Routes that activate the item (supports regex) |
| `label`, `translation_domain` | `LabelExtension` | Label with optional Symfony translation |
| `roles` | — | Security roles required to show the item |
| `attributes` | `CoreExtension` | HTML attributes |
| `extras` | `CoreExtension` | Arbitrary extra data |

### Matching (`Matcher/`)

- `Matcher` — voter-based; `isCurrent(item)` / `isAncestor(item, depth?)`; results cached in `SplObjectStorage`
- `RouteVoter` — matches `_route` against item's `routes` option; route values are treated as regex patterns; optionally checks `_route_params`

Add custom voters by implementing `VoterInterface` — auto-tagged `chamber_orchestra_menu.matcher.voter`.

### Access Control (`Accessor/`)

- `Accessor` — checks `AuthorizationChecker` against item `roles`; results cached with `Ds\Map`
- Available in Twig templates as `accessor`; call `accessor.hasAccess(item)` to gate rendering

### Rendering (`Renderer/`, `Twig/`)

- `TwigRenderer` — renders a template with variables: `root` (the menu tree), `matcher`, `accessor`
- `MenuExtension` — registers `render_menu(nav, template, options)` Twig function
- `Helper` — wires `NavigationFactory` + `TwigRenderer` for the Twig runtime

### DI Autoconfiguration

Implementing these interfaces is enough — no manual service config needed:

- `NavigationInterface` → tagged `chamber_orchestra_menu.navigation`
- `ExtensionInterface` → tagged `chamber_orchestra_menu.factory.extension`
- `VoterInterface` → tagged `chamber_orchestra_menu.matcher.voter`

`CoreExtension` is registered with priority `-10` (runs last, sets defaults).

## Code Conventions

- PSR-12, `declare(strict_types=1)`, 4-space indent
- Typed properties and return types
- Commit style: short, action-oriented with optional bracketed scope — `[fix] ...`, `[master] ...`
