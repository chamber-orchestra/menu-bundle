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
- **Custom voters** — implement `VoterInterface` to add custom matching logic alongside the built-in `RouteVoter`
- **Role-based access** — `Accessor` gates items by Symfony security roles; results are memoized per request
- **PSR-6 caching** — `AbstractCachedNavigation` caches the item tree for 24 h with tag-based invalidation
- **Runtime extensions** — `RuntimeExtensionInterface` runs post-cache on every request for fresh dynamic data without rebuilding the tree
- **Badge support** — `BadgeExtension` resolves `int` and `\Closure` badges at runtime; implement `RuntimeExtensionInterface` for service-injected dynamic badges
- **Counters** — `CounterExtension` resolves multiple named counters (`int` or `\Closure`) at runtime
- **Icons** — `IconExtension` moves the `icon` option into `extras['icon']` at build time
- **Dividers** — `DividerExtension` marks items as dividers via `extras['divider']` at build time
- **Visibility** — `VisibilityExtension` resolves `visible` (bool or `\Closure`) at runtime into `extras['visible']`
- **Label translation** — `TranslationExtension` translates item labels via Symfony's `TranslatorInterface` (auto-disabled when no translator is available)
- **Breadcrumbs** — `menu_breadcrumbs()` Twig function returns the path from root to the current item
- **Raw tree access** — `menu_get()` Twig function returns the root `Item` without rendering
- **Twig integration** — `render_menu()` function with fully customisable templates and optional default template
- **Bundle configuration** — centralised config for default template, translation domain, and cache namespace
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
| symfony/translation-contracts | `^3.4` |
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

## Configuration

```yaml
# config/packages/chamber_orchestra_menu.yaml
chamber_orchestra_menu:
    default_template: ~              # ?string — fallback template for render_menu()
    translation:
        domain: 'messages'           # string — default translation domain for labels
    cache:
        namespace: '$NAVIGATION$'    # string — cache key namespace prefix
```

All values are optional with sensible defaults.

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
            ->add('dashboard', ['label' => 'Dashboard', 'route' => 'app_dashboard', 'icon' => 'fa-home'])
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

| Option | Type | Extension | Description |
|---|---|---|---|
| `label` | `string` | `LabelExtension` | Display text; falls back to item name if absent |
| `route` | `string` | `RoutingExtension` | Route name; generates `uri` and appends to `routes` |
| `route_params` | `array` | `RoutingExtension` | Route parameters passed to the URL generator |
| `route_type` | `int` | `RoutingExtension` | `UrlGeneratorInterface::ABSOLUTE_PATH` (default) or `ABSOLUTE_URL` |
| `routes` | `array` | — | Additional routes that activate this item (supports regex) |
| `uri` | `string` | — | Raw URI; set directly if not using `route` |
| `roles` | `array` | — | Security roles **all** required to display the item (AND logic) |
| `icon` | `string` | `IconExtension` | Icon identifier; moved to `extras['icon']` at build time |
| `divider` | `bool` | `DividerExtension` | When `true`, marks the item as a divider via `extras['divider']` |
| `badge` | `int\|\Closure` | `BadgeExtension` | Badge count; resolved post-cache; stored in `extras['badge']` |
| `counters` | `array<string, int\|\Closure>` | `CounterExtension` | Named counters; resolved post-cache; stored in `extras['counters']` |
| `visible` | `bool\|\Closure` | `VisibilityExtension` | Visibility flag; resolved post-cache; stored in `extras['visible']` |
| `translation_domain` | `string` | `TranslationExtension` | Per-item translation domain override |
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
└── AbstractCachedNavigation           (24 h TTL, 'chamber_orchestra_menu' tag)
```

| Base class | TTL | Tags | Use case |
|---|---|---|---|
| `AbstractCachedNavigation` | 24 h | `chamber_orchestra_menu` | Menu structures (recommended) |
| `AbstractNavigation` | 0 | none | Base class, no caching across requests |
| `ClosureNavigation` | 0 (configurable) | none | Quick one-off menus; optionally cacheable |

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

### ClosureNavigation caching

`ClosureNavigation` is uncached by default (TTL 0), but you can opt in to caching by providing a unique `cacheKey` and a `ttl`:

```php
use ChamberOrchestra\MenuBundle\Navigation\ClosureNavigation;

// Uncached (default)
$nav = new ClosureNavigation(function (MenuBuilder $builder): void {
    $builder->add('home', ['label' => 'Home', 'route' => 'app_home']);
});

// Cached for 1 hour
$nav = new ClosureNavigation(
    callback: function (MenuBuilder $builder): void {
        $builder->add('home', ['label' => 'Home', 'route' => 'app_home']);
    },
    cacheKey: 'sidebar_nav',
    ttl: 3600,
);
```

Each cached `ClosureNavigation` **must** have a unique `cacheKey` — without one, all instances share the same key and overwrite each other.

The cache namespace prefix defaults to `$NAVIGATION$` and can be changed via bundle configuration.

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

### Custom Voters

Implement `VoterInterface` to add custom matching logic. Custom voters are auto-tagged and used alongside `RouteVoter`:

```php
<?php

namespace App\Navigation\Voter;

use ChamberOrchestra\MenuBundle\Matcher\Voter\VoterInterface;
use ChamberOrchestra\MenuBundle\Menu\Item;

final class QueryParamVoter implements VoterInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function matchItem(Item $item): ?bool
    {
        // Return true to mark current, false to reject, null to abstain
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        $expectedTab = $item->getOption('tab');
        if (null === $expectedTab) {
            return null;
        }

        return $request->query->get('tab') === $expectedTab ? true : null;
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

## Icons

The built-in `IconExtension` moves the `icon` option into `extras['icon']` at build time, so the value is cached with the tree:

```php
$builder->add('dashboard', ['label' => 'Dashboard', 'icon' => 'fa-home']);
```

In Twig:

```twig
{% set icon = item.option('extras').icon|default(null) %}
{% if icon %}
    <i class="{{ icon }}"></i>
{% endif %}
```

---

## Dividers

The `DividerExtension` marks items as visual dividers at build time:

```php
$builder->add('separator', ['divider' => true]);
```

In Twig:

```twig
{% if item.option('extras').divider|default(false) %}
    <hr/>
{% else %}
    <a href="{{ item.uri }}">{{ item.label }}</a>
{% endif %}
```

---

## Visibility

The `VisibilityExtension` resolves the `visible` option at runtime. Pass a `bool` or a `\Closure`:

```php
$builder
    ->add('beta_feature', ['label' => 'Beta', 'visible' => false])
    ->add('promo', ['label' => 'Promo', 'visible' => fn (): bool => $this->featureFlags->isEnabled('promo')]);
```

In Twig:

```twig
{% if item.option('extras').visible|default(true) %}
    <a href="{{ item.uri }}">{{ item.label }}</a>
{% endif %}
```

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

## Counters

The `CounterExtension` resolves multiple named counters at runtime. Pass a `array<string, int|\Closure>`:

```php
$builder->add('orders', [
    'label' => 'Orders',
    'counters' => [
        'pending' => fn (): int => $this->orders->countPending(),
        'shipped' => fn (): int => $this->orders->countShipped(),
    ],
]);
```

In Twig:

```twig
{% set counters = item.option('extras').counters|default({}) %}
{% for name, count in counters %}
    <span class="counter counter--{{ name }}">{{ count }}</span>
{% endfor %}
```

---

## Label Translation

The `TranslationExtension` translates item labels using Symfony's `TranslatorInterface`. It runs at runtime (post-cache) so translated labels are always fresh.

- **Default domain:** configured via `chamber_orchestra_menu.translation.domain` (defaults to `messages`)
- **Per-item override:** set the `translation_domain` option on an item
- **Empty labels** are skipped
- **Auto-disabled** when no `TranslatorInterface` service is available in the container

```php
$builder
    ->add('scores', ['label' => 'nav.scores'])
    ->add('rehearsals', ['label' => 'nav.rehearsals', 'translation_domain' => 'navigation']);
```

---

## Factory Extensions

### Build-time extensions (cached)

Implement `ExtensionInterface` to enrich item options before the `Item` is created. Results are cached with the tree. Extensions are auto-tagged and sorted by `priority` (higher runs first; `CoreExtension` runs last at `-10`):

```php
use ChamberOrchestra\MenuBundle\Factory\Extension\ExtensionInterface;

final class TooltipExtension implements ExtensionInterface
{
    public function buildOptions(array $options): array
    {
        if (isset($options['tooltip'])) {
            $extras = $options['extras'] ?? [];
            $extras['tooltip'] = $options['tooltip'];
            $options['extras'] = $extras;
            unset($options['tooltip']);
        }

        return $options;
    }
}
```

### Built-in build-time extensions

| Extension | Option | Stored in | Description |
|---|---|---|---|
| `RoutingExtension` | `route`, `route_params`, `route_type` | `uri`, `routes` | Generates URI from route |
| `LabelExtension` | `label` | `label` | Falls back to item name |
| `IconExtension` | `icon` | `extras['icon']` | Icon identifier |
| `DividerExtension` | `divider` | `extras['divider']` | Divider flag |
| `CoreExtension` | `attributes`, `extras` | — | Defaults (priority `-10`, runs last) |

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

### Built-in runtime extensions

| Extension | Option | Stored in | Description |
|---|---|---|---|
| `BadgeExtension` | `badge` | `extras['badge']` | Single badge count (`int\|\Closure`) |
| `CounterExtension` | `counters` | `extras['counters']` | Named counters map |
| `VisibilityExtension` | `visible` | `extras['visible']` | Visibility flag (`bool\|\Closure`) |
| `TranslationExtension` | `translation_domain` | label (via `setLabel()`) | Translates labels |

---

## DI Autoconfiguration

Implement an interface and you're done — no manual service tags required:

| Interface | Auto-tag |
|---|---|
| `NavigationInterface` | `chamber_orchestra_menu.navigation` |
| `ExtensionInterface` | `chamber_orchestra_menu.factory.extension` |
| `RuntimeExtensionInterface` | `chamber_orchestra_menu.factory.runtime_extension` |
| `VoterInterface` | `chamber_orchestra_menu.matcher.voter` |

---

## Breadcrumbs

The `menu_breadcrumbs()` Twig function returns the path from root to the current item (root excluded):

```twig
{% set crumbs = menu_breadcrumbs('App\\Navigation\\SidebarNavigation') %}

<nav aria-label="breadcrumb">
    <ol>
        {% for item in crumbs %}
            <li{% if loop.last %} class="active"{% endif %}>
                {% if not loop.last and item.uri %}
                    <a href="{{ item.uri }}">{{ item.label }}</a>
                {% else %}
                    {{ item.label }}
                {% endif %}
            </li>
        {% endfor %}
    </ol>
</nav>
```

Returns an empty array when no item is currently active.

---

## Twig Reference

```twig
{# Renders a navigation using the given template #}
{{ render_menu('App\\Navigation\\MyNavigation', 'nav/my.html.twig') }}

{# Uses the default_template from bundle config (template argument omitted) #}
{{ render_menu('App\\Navigation\\MyNavigation') }}

{# With extra options passed to the template #}
{{ render_menu('App\\Navigation\\MyNavigation', 'nav/my.html.twig', {locale: app.request.locale}) }}

{# Get the raw Item tree without rendering #}
{% set root = menu_get('App\\Navigation\\MyNavigation') %}

{# Get the breadcrumb path to the current item #}
{% set crumbs = menu_breadcrumbs('App\\Navigation\\MyNavigation') %}
```

**Template variables (available inside `render_menu` templates):**

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
