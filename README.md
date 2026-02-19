# ChamberOrchestra MenuBundle

[![PHP](https://img.shields.io/badge/PHP-8.5%2B-8892BF?logo=php)](https://php.net)
[![Symfony](https://img.shields.io/badge/Symfony-8.0%2B-000000?logo=symfony)](https://symfony.com)
[![License](https://img.shields.io/badge/License-Apache_2.0-blue.svg)](LICENSE)
[![CI](https://github.com/chamber-orchestra/menu-bundle/actions/workflows/php.yml/badge.svg)](https://github.com/chamber-orchestra/menu-bundle/actions/workflows/php.yml)

A **Symfony 8** bundle for building navigation menus and sidebars — fluent tree builder, route-based active-item matching, role-based access control, PSR-6 tag-aware caching, and Twig rendering.

---

## Features

- **Fluent builder API** — `add()`, `children()`, `end()` for deeply-nested trees
- **Route-based matching** — `RouteVoter` marks the current item and its ancestors active; route values are treated as regex patterns
- **Role-based access** — `Accessor` gates items by Symfony security roles; results are memoized per request
- **PSR-6 caching** — `AbstractCachedNavigation` caches the item tree for 24 h with tag-based invalidation
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

use ChamberOrchestra\MenuBundle\Menu\MenuBuilderInterface;
use ChamberOrchestra\MenuBundle\Navigation\AbstractNavigation;

final class SidebarNavigation extends AbstractNavigation
{
    public function build(MenuBuilderInterface $builder, array $options = []): void
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

Extend `AbstractCachedNavigation` to cache the built tree between requests:

```php
<?php

namespace App\Navigation;

use ChamberOrchestra\MenuBundle\Menu\MenuBuilderInterface;
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

    public function build(MenuBuilderInterface $builder, array $options = []): void
    {
        $builder->add('home', ['label' => 'Home', 'route' => 'app_home']);
    }
}
```

The default cache key is the fully-qualified class name; default TTL is **24 hours**; default tag is `navigation`.

A PSR-6 `CacheInterface` (tag-aware) must be wired into `NavigationFactory`. Configure it in your service definition or use Symfony's `cache.app` taggable pool.

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

Custom voters implement `VoterInterface` and are auto-tagged:

```php
use ChamberOrchestra\MenuBundle\Matcher\Voter\VoterInterface;
use ChamberOrchestra\MenuBundle\Menu\ItemInterface;

final class MyVoter implements VoterInterface
{
    public function matchItem(ItemInterface $item): ?bool
    {
        // return true (current), false (not current), null (abstain)
        return null;
    }
}
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
| `VoterInterface` | `chamber_orchestra_menu.matcher.voter` |

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
| `root` | `ItemInterface` | Root item — iterate to get top-level items |
| `matcher` | `MatcherInterface` | Call `isCurrent(item)` / `isAncestor(item)` |
| `accessor` | `AccessorInterface` | Call `hasAccess(item)` / `hasAccessToChildren(collection)` |

---

## Testing

```bash
composer install
composer test
```

---

## License

Apache-2.0. See [LICENSE](LICENSE).
