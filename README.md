# ChamberOrchestra MenuBundle

[![PHP Composer](https://github.com/chamber-orchestra/menu-bundle/actions/workflows/php.yml/badge.svg)](https://github.com/chamber-orchestra/menu-bundle/actions/workflows/php.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-max-brightgreen.svg)](https://phpstan.org/)
[![PHP-CS-Fixer](https://img.shields.io/badge/code%20style-PER--CS%20%2F%20Symfony-blue.svg)](https://cs.symfony.com/)
[![Latest Stable Version](https://img.shields.io/packagist/v/chamber-orchestra/menu-bundle.svg)](https://packagist.org/packages/chamber-orchestra/menu-bundle)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP 8.5+](https://img.shields.io/badge/PHP-8.5%2B-777BB4.svg)](https://www.php.net/)
[![Symfony 8.0](https://img.shields.io/badge/Symfony-8.0-000000.svg)](https://symfony.com/)

A **Symfony 8** bundle for building navigation menus, sidebars, and breadcrumbs — fluent tree builder, route-based active-item matching, role-based access control, runtime extensions for dynamic badges, PSR-6 tag-aware caching, and Twig rendering.

---

## Features

- **Fluent builder API** — `add()`, `children()`, `end()` for deeply-nested trees
- **Route-based matching** — `RouteVoter` marks the current item and its ancestors active; route values are treated as regex patterns
- **Role-based access** — `Accessor` gates items by Symfony security roles; results are memoized per request
- **PSR-6 caching** — `AbstractCachedNavigation` caches the item tree for 24 h with tag-based invalidation
- **Runtime extensions** — `RuntimeExtensionInterface` runs post-cache on every request for fresh dynamic data without rebuilding the tree
- **Badge support** — `BadgeExtension` resolves `int` and `\Closure` badges at runtime; implement `RuntimeExtensionInterface` for service-injected dynamic badges
- **Twig integration** — `render_menu()` function with fully customisable templates
- **Extension system** — build-time `ExtensionInterface` for cached option enrichment, runtime `RuntimeExtensionInterface` for post-cache processing
- **DI autoconfiguration** — implement an interface, done; no manual service tags required

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | `^8.5` |
| ext-ds | `*` |
| doctrine/collections | `^2.0 \|\| ^3.0` |
| symfony/\* | `^8.0` |
| twig/twig | `^3.0` |

---

## Installation

```bash
composer require chamber-orchestra/menu-bundle
```

### Register the bundle

```php
// config/bundles.php
return [
    // ...
    ChamberOrchestra\MenuBundle\ChamberOrchestraMenuBundle::class => ['all' => true],
];
```

---

## Quick Start

### 1. Create a navigation class

```php
<?php

namespace App\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use ChamberOrchestra\MenuBundle\Navigation\AbstractCachedNavigation;

final class SidebarNavigation extends AbstractCachedNavigation
{
    public function build(MenuBuilder $builder, array $options = []): void
    {
        $builder
            ->add('dashboard', ['label' => 'Dashboard', 'route' => 'app_dashboard'])
            ->add('blog', ['label' => 'Blog'])
            ->children()
                ->add('posts', ['label' => 'Posts', 'route' => 'app_blog_post_index'])
                ->add('tags',  ['label' => 'Tags',  'route' => 'app_blog_tag_index'])
            ->end()
            ->add('settings', ['label' => 'Settings', 'route' => 'app_settings',
                               'roles' => ['ROLE_ADMIN']]);
    }
}
```

The class is auto-tagged as a navigation service — no YAML/XML service definition needed.

### 2. Create a Twig template

```twig
{# templates/nav/sidebar.html.twig #}
{% for item in root %}
    {% if accessor.hasAccess(item) %}
        <a href="{{ item.uri }}"
           class="{{ matcher.isCurrent(item) ? 'active' : '' }}">
            {{ item.label }}
        </a>
    {% endif %}
{% endfor %}
```

### 3. Render in Twig

```twig
{{ render_menu('App\\Navigation\\SidebarNavigation', 'nav/sidebar.html.twig') }}
```

---

## Item Options

Options are passed as the second argument to `MenuBuilder::add()`:

| Option | Type | Description |
|---|---|---|
| `label` | `string` | Display text; falls back to item name if absent (`LabelExtension`) |
| `route` | `string` | Route name; generates `uri` and appends to `routes` (`RoutingExtension`) |
| `route_params` | `array` | Route parameters passed to the URL generator (`RoutingExtension`) |
| `route_type` | `int` | `UrlGeneratorInterface::ABSOLUTE_PATH` (default) or `ABSOLUTE_URL` (`RoutingExtension`) |
| `routes` | `array` | Additional routes that activate this item (supports regex) |
| `uri` | `string` | Raw URI; set directly if not using `route` |
| `roles` | `array` | Security roles **all** required to display the item (AND logic) |
| `badge` | `int\|\Closure` | Badge count; resolved post-cache by `BadgeExtension` (a runtime extension); stored in `extras['badge']` |
| `attributes` | `array` | HTML attributes merged onto the rendered element (`CoreExtension`) |
| `extras` | `array` | Arbitrary extra data attached to the item (`CoreExtension`) |

### Section items

Pass `section: true` to mark an item as a non-linkable section heading:

```php
$builder
    ->add('main', ['label' => 'Main Section'], section: true)
    ->children()
        ->add('dashboard', ['label' => 'Dashboard', 'route' => 'app_dashboard'])
    ->end();
```

---

## Caching

Navigation classes form a hierarchy — extend the one that fits your use case:

```
AbstractNavigation                     (base: 0 TTL, no tags)
└── AbstractCachedNavigation           (24 h TTL, 'chamber_orchestra_menu' tag)
```

| Base class | TTL | Tags | Use case |
|---|---|---|---|
| `AbstractCachedNavigation` | 24 h | `chamber_orchestra_menu` | Menu structures (recommended) |
| `AbstractNavigation` | 0 | none | Base class, no caching across requests |

All navigations are deduped within the same request via `NavigationFactory`. When a PSR-6 `CacheInterface` (tag-aware) is wired in, `AbstractCachedNavigation` stores the tree across requests. Without one, an in-memory `ArrayAdapter` is used automatically.

Dynamic data (badges, counters) does not require sacrificing the cache — use runtime extensions instead.

```php
<?php

namespace App\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use ChamberOrchestra\MenuBundle\Navigation\AbstractCachedNavigation;
use Symfony\Contracts\Cache\ItemInterface;

final class MainNavigation extends AbstractCachedNavigation
{
    public function __construct(private readonly string $locale)
    {
        parent::__construct();
    }

    // Override the cache key if you need per-locale or per-user trees
    public function getCacheKey(): string
    {
        return 'main_nav_'.$this->locale;
    }

    // Fine-tune TTL and tags
    public function configureCacheItem(ItemInterface $item): void
    {
        $item->expiresAfter(3600);
        $item->tag(['navigation', 'main_nav']);
    }

    public function build(MenuBuilder $builder, array $options = []): void
    {
        $builder->add('home', ['label' => 'Home', 'route' => 'app_home']);
    }
}
```

The default cache key is the fully-qualified class name; default TTL is **24 hours**; default tag is `chamber_orchestra_menu`.

---

## Route Matching

`RouteVoter` reads `_route` from the current request and compares it against each item's `routes` array. Route values are **treated as regex patterns**, so you can highlight an entire section:

```php
$builder->add('blog', [
    'label'  => 'Blog',
    'route'  => 'app_blog_post_index',
    'routes' => [
        ['route' => 'app_blog_.*'], // all blog_* routes keep the item active
    ],
]);
```

---

## Role-Based Access

The `accessor` variable is injected into every rendered template. Call `hasAccess(item)` to gate visibility:

```twig
{% if accessor.hasAccess(item) %}
    <li>...</li>
{% endif %}
```

`hasAccess()` returns `true` when:
- the item has no `roles` restriction, **or**
- the current user has **all** of the required roles (AND logic).

`hasAccessToChildren(collection)` returns `true` when **any** child in the collection is accessible.

---

## Badges

### Via the `badge` option

The built-in `BadgeExtension` is a runtime extension that resolves the `badge` item option on every request. Pass an `int` or a `\Closure`:

```php
$builder
    ->add('news', ['label' => 'News', 'badge' => 3])
    ->add('inbox', ['label' => 'Inbox', 'badge' => fn (): int => $this->messages->countUnread()]);
```

### Via a custom runtime extension

For service-injected dynamic data, implement `RuntimeExtensionInterface`. The tree stays cached; the extension runs post-cache on every request:

```php
<?php

namespace App\Navigation\Extension;

use App\Repository\MessageRepository;
use ChamberOrchestra\MenuBundle\Factory\Extension\RuntimeExtensionInterface;
use ChamberOrchestra\MenuBundle\Menu\Item;

final class InboxBadgeExtension implements RuntimeExtensionInterface
{
    public function __construct(private readonly MessageRepository $messages)
    {
    }

    public function processItem(Item $item): void
    {
        if ('inbox' === $item->getName()) {
            $item->setExtra('badge', $this->messages->countUnread());
        }
    }
}
```

In Twig, read the badge via `item.badge`:

```twig
{% if item.badge is not null %}
    <span class="badge">{{ item.badge }}</span>
{% endif %}
```

---

## Factory Extensions

### Build-time extensions (cached)

Implement `ExtensionInterface` to enrich item options before the `Item` is created. Results are cached with the tree. Extensions are auto-tagged and sorted by `priority` (higher runs first; `CoreExtension` runs last at `-10`):

```php
use ChamberOrchestra\MenuBundle\Factory\Extension\ExtensionInterface;

final class IconExtension implements ExtensionInterface
{
    public function buildOptions(array $options): array
    {
        $options['attributes']['data-icon'] ??= $options['icon'] ?? null;
        unset($options['icon']);

        return $options;
    }
}
```

### Runtime extensions (post-cache)

Implement `RuntimeExtensionInterface` to apply fresh data after every cache fetch. `processItem()` is called on every `Item` in the tree:

```php
use ChamberOrchestra\MenuBundle\Factory\Extension\RuntimeExtensionInterface;
use ChamberOrchestra\MenuBundle\Menu\Item;

final class NotificationBadgeExtension implements RuntimeExtensionInterface
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    public function processItem(Item $item): void
    {
        if ('alerts' === $item->getName()) {
            $item->setExtra('badge', $this->notifications->countUnread());
        }
    }
}
```

---

## DI Autoconfiguration

Implement an interface and you're done — no manual service tags required:

| Interface | Auto-tag |
|---|---|
| `NavigationInterface` | `chamber_orchestra_menu.navigation` |
| `ExtensionInterface` | `chamber_orchestra_menu.factory.extension` |
| `RuntimeExtensionInterface` | `chamber_orchestra_menu.factory.runtime_extension` |

---

## Twig Reference

```twig
{# Renders a navigation using the given template #}
{{ render_menu('App\\Navigation\\MyNavigation', 'nav/my.html.twig') }}

{# With extra options passed to build() #}
{{ render_menu('App\\Navigation\\MyNavigation', 'nav/my.html.twig', {locale: app.request.locale}) }}
```

**Template variables:**

| Variable | Type | Description |
|---|---|---|
| `root` | `Item` | Root item — iterate to get top-level items |
| `matcher` | `Matcher` | Call `isCurrent(item)` / `isAncestor(item)` |
| `accessor` | `Accessor` | Call `hasAccess(item)` / `hasAccessToChildren(collection)` |

---

## Testing

```bash
composer install
composer test
```

---

## License

MIT. See [LICENSE](LICENSE).
