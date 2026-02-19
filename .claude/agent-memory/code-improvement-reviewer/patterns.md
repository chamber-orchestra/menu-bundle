# Menu Bundle - Detailed Issue Notes

## Critical Bugs

### Matcher::isCurrent() uninitialized variable
File: `Matcher/Matcher.php:43`
If the `$voters` iterable is empty OR all voters return null, the loop never assigns `$current`.
Line 43 `$current = (bool) $current;` then reads an uninitialized variable.
In PHP 8 strict mode this raises a TypeError / notice that evaluates $current as null→false.
The result is silently cached as `false`, which is usually correct but masks the bug.

### Item::getFirstChild() / getLastChild() return false, not null
File: `Menu/Item.php:74-82`
`ArrayCollection::first()` and `::last()` return `false` when the collection is empty, but the
return type annotation is `?ItemInterface`. PHP doesn't enforce this at runtime for object returns
but callers expecting null will receive false. In MenuBuilder::children() line 31, this false is
assigned to `$this->current`, causing the next `add()` call to crash on `$this->current->add()`.

## High Priority Bugs

### RouteVoter null-request crash
File: `Matcher/Voter/RouteVoter.php:18`
`$this->stack->getCurrentRequest()` can return null (CLI, sub-requests, test contexts).
Line 18 calls `->attributes->get('_route')` directly on the result without a null check.
This throws an Error in non-HTTP contexts.

### Item serialization loses $section field
File: `Menu/Item.php:99-117`
`$this->section` is never included in the serialized array, so after deserialization `isSection()`
always returns false. Also, the `Serializable` interface is deprecated in PHP 8.1+ in favor of
`__serialize()`/`__unserialize()`.

### MenuBuilder::end() silent null assignment
File: `Menu/MenuBuilder.php:38-41`
If `end()` is called more times than `children()`, `array_pop($this->parents)` returns null
and `$this->current` is set to null. Subsequent `add()` calls will throw a null dereference.

### Accessor::hasAccessToChildren() semantics wrong
File: `Accessor/Accessor.php:25-34`
The method returns false as soon as ANY child is inaccessible. The likely intended use in templates
is "does this section have any accessible children (should we render the section heading)?".
The current logic is AND — returns true only if ALL children are accessible.
This can completely hide menu sections that have a mix of accessible and restricted items.

## Medium Priority Issues

### NavigationFactory::create() untyped $nav parameter
File: `NavigationFactory.php:34`
Signature: `public function create($nav, array $options): Menu\ItemInterface`
The $nav parameter should be typed as `NavigationInterface|string`.

### AbstractCachedNavigation cache tags not applied
File: `Navigation/AbstractCachedNavigation.php:26-29`
`configureCacheItem()` calls `$item->expiresAfter()` but never calls `$item->tag($this->cache['tags'])`.
Cache invalidation via `$cachePool->invalidateTags(['navigation'])` therefore has no effect.

### Factory::addExtension() missing return type
File: `Factory/Factory.php:26`
`public function addExtension(ExtensionInterface $extension, int $priority = 0)` — no return type.
Should be `: void`.

### LabelExtension #[Required] with nullable property
File: `Factory/Extension/LabelExtension.php:12-17`
Uses `#[Required]` attribute meaning Symfony will inject the translator via setter, but the property
is declared `protected ?TranslatorInterface $translator = null`. If the service container somehow
skips injection (e.g., in a test), line 27 `$this->translator->trans(...)` will throw a fatal error.
Constructor injection would be safer.

### TwigRenderer options merge order
File: `Renderer/TwigRenderer.php:27`
`array_merge($options, ['root'=>..., 'matcher'=>..., 'accessor'=>...])` — the named keys come LAST
so they correctly override any conflicting caller options. This is actually correct behavior but
callers might be confused if they expect to pass custom 'root' or 'matcher'. A comment would help.
Actually on reflection: `array_merge($options, $builtins)` means builtins WIN. That is the right
semantics for security (can't override matcher/accessor) but callers cannot add a custom 'root'.

### RouteVoter fetches current request twice
File: `Matcher/Voter/RouteVoter.php:17` and `Matcher/Voter/RouteVoter.php:45`
`$this->stack->getCurrentRequest()` is called in both `matchItem()` and `isMatchingRoute()`.
The result should be passed as a parameter to avoid the double call.

### NavigationFactory uses spl_object_hash for cache key
File: `NavigationFactory.php:40`
`spl_object_hash()` can be reused if the object is garbage-collected and a new object is allocated
at the same memory address. In a long-lived process (Symfony with FrankenPHP/RoadRunner workers),
this could theoretically return a stale cached item for a new navigation object instance.
Using `spl_object_id()` (PHP 7.2+) has the same issue. The correct key is the class name itself
since the navigation is a service (singleton per container).

### ClosureNavigation uses call_user_func instead of direct invocation
File: `Navigation/ClosureNavigation.php:25`
`\call_user_func($this->callback, $builder, $options)` — since the property is typed `\Closure`,
direct invocation `($this->callback)($builder, $options)` is cleaner and marginally faster.

## Low Priority / Style Issues

### ItemInterface missing getLabel()
File: `Menu/ItemInterface.php`
`Item::getLabel()` exists but is not declared in `ItemInterface`. Templates calling
`item.label` on an `ItemInterface` typed variable cannot be statically analyzed.

### ItemInterface::getOption() missing return type
File: `Menu/ItemInterface.php:17`
`public function getOption(string $name, $default = null);` — missing return type.
PHP 8.5 should use `mixed` explicitly.

### services.yml exclude pattern is fragile
File: `Resources/config/services.yml:17`
`exclude: "../../{DependencyInjection,Resources,ExceptionInterface,Navigation}"`
`ExceptionInterface` in the exclude list is a file name pattern. It excludes ALL classes ending
in `ExceptionInterface` but this is a glob/path issue — the `Exception/` directory is not excluded,
only the hypothetical top-level file. The exception classes inside `Exception/` ARE loaded, which
is fine (they have no DI tags), but the exclude comment is misleading.

### AbstractNavigation is a redundant abstract class
File: `Navigation/AbstractNavigation.php`
The class only re-declares the abstract method from the interface. It adds no behavior.
It exists solely for extending convenience but `NavigationInterface` alone suffices.

### Missing composer.json Symfony/Doctrine requirements
File: `composer.json`
The bundle requires Symfony components (HttpFoundation, Routing, Security, Cache, Twig) and
Doctrine Collections, but none of these are listed in `require`. This can cause silent failures
when the bundle is used standalone without the parent project providing these dependencies.
