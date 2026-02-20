# ChamberOrchestra MenuBundle

[![PHP](https://img.shields.io/badge/PHP-8.5%2B-8892BF?logo=php)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-8.0%2B-000000?logo=symfony)](https://symfony.com)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![CI](https://github.com/chamber-orchestra/menu-bundle/actions/workflows/php.yml/badge.svg)](https://github.com/chamber-orchestra/menu-bundle/actions/workflows/php.yml)

A **Symfony 8** bundle for building navigation menus and sidebars — fluent tree builder, route-based active-item matching, role-based access control, PSR-6 tag-aware caching, and Twig rendering.

---

## Features

- **Fluent builder API** — `add()`, `children()`, `end()` for deeply-nested trees
- **Route-based matching** — `RouteVoter` marks the current item and its ancestors active; route values are treated as regex patterns
- **Role-based access** — `Accessor` gates items by Symfony security roles; results are memoized per request
- **PSR-6 caching** — `AbstractCachedNavigation` caches the item tree for 24 h with tag-based invalidation; `AbstractStaticNavigation` rebuilds every request for dynamic content
- **Badge support** — attach dynamic counts to items via `int` or `\Closure`; closures are resolved at build time
- **Twig integration** — `render_menu()` function with fully customisable templates
- **Extension system** — plug in custom `ExtensionInterface` to enrich item options before creation
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

The class is auto-tagged as a navigation service — no YAML service definition needed.

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

| Option | Type | Extension | Description |
|---|---|---|---|
| `label` | `string` | `LabelExtension` | Display text; falls back to item name if absent |
| `translation_domain` | `string` | `LabelExtension` | Symfony translation domain for the label |
| `route` | `string` | `RoutingExtension` | Route name; generates `uri` and appends to `routes` |
| `route_params` | `array` | `RoutingExtension` | Route parameters passed to the URL generator |
| `route_type` | `int` | `RoutingExtension` | `UrlGeneratorInterface::ABSOLUTE_PATH` (default) or `ABSOLUTE_URL` |
| `routes` | `array` | — | Additional routes that activate this item (supports regex) |
| `uri` | `string` | — | Raw URI; set directly if not using `route` |
| `roles` | `array` | — | Security roles **all** required to display the item (AND logic) |
| `badge` | `int\|\Closure` | `BadgeExtension` | Badge count; closures are resolved at build time; stored in `extras['badge']` |
| `attributes` | `array` | `CoreExtension` | HTML attributes merged onto the rendered element |
| `extras` | `array` | `CoreExtension` | Arbitrary extra data attached to the item |

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
├── AbstractCachedNavigation           (24 h TTL, 'chamber_orchestra_menu' tag)
└── AbstractStaticNavigation           (0 TTL, no tags — explicit non-cached)
```

| Base class | TTL | Tags | Use case |
|---|---|---|---|
| `AbstractCachedNavigation` | 24 h | `chamber_orchestra_menu` | Static menu structures |
| `AbstractStaticNavigation` | 0 | none | Menus with dynamic data (badges, counters) |

Both are deduped within the same request via `NavigationFactory`. When a PSR-6 `CacheInterface` (tag-aware) is wired in, `AbstractCachedNavigation` stores the tree across requests. Without one, an in-memory `ArrayAdapter` is used automatically.

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

The `badge` option attaches a numeric count to a menu item. Pass an `int` directly, or a `\Closure` that returns one — closures are resolved at build time by `BadgeExtension`. Use `AbstractStaticNavigation` when badges need fresh data on every request:

```php
<?php

namespace App\Navigation;

use App\Repository\MessageRepository;
use ChamberOrchestra\MenuBundle\Menu\MenuBuilder;
use ChamberOrchestra\MenuBundle\Navigation\AbstractStaticNavigation;

final class InboxNavigation extends AbstractStaticNavigation
{
    public function __construct(private readonly MessageRepository $messages)
    {
    }

    public function build(MenuBuilder $builder, array $options = []): void
    {
        $builder
            ->add('inbox', [
                'label' => 'Inbox',
                'route' => 'app_inbox',
                'badge' => fn (): int => $this->messages->countUnread(),
            ]);
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

Implement `ExtensionInterface` to enrich item options before the `Item` is created. Extensions are auto-tagged and sorted by `priority` (higher runs first; `CoreExtension` runs last at `-10`):

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

---

## DI Auto-configuration

Implement an interface and you're done — no manual service tags required:

| Interface | Auto-tag |
|---|---|
| `NavigationInterface` | `chamber_orchestra_menu.navigation` |
| `ExtensionInterface` | `chamber_orchestra_menu.factory.extension` |

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
