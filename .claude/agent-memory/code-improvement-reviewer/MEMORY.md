# Menu Bundle - Code Review Memory

## Architecture
- Namespace: `ChamberOrchestra\MenuBundle` (PSR-4 from `src/`, package root is project root)
- PHP ^8.5, ext-ds required
- Legacy alias bundle: `DevMenuBundle` — NOT present in current codebase (removed or never implemented — only `ChamberOrchestraMenuBundle` exists)
- Test suite: present as of Feb 2026 (Unit + Integrational, no DI kernel test)

## Key Patterns
- Navigation: implement `NavigationInterface::build()`, auto-tagged `chamber_orchestra_menu.navigation`
- Factory: `Factory` applies `ExtensionInterface` plugins sorted by priority (krsort = higher int = higher priority)
- `CoreExtension` is priority -10 (runs last, sets defaults like uri/extras/current/attributes)
- Caching: `AbstractCachedNavigation` uses Symfony Cache Contracts, 24h TTL, tag `navigation`; tags ARE set via `$item->tag()`
- Matching: voter-based `SplObjectStorage` cache; `RouteVoter` treats route values as regex patterns; null-request guard is present
- Access: `Ds\Map` caches `isGranted()` results per item object; role grants cached in plain array too
- `Item` uses `__serialize`/`__unserialize` (modern PHP serialization — old `Serializable` interface NOT used)
- `MenuBuilder::children()` and `end()` both throw `LogicException` on misuse (guard clauses present)
- `Item::getFirstChild()`/`getLastChild()` use `?: null` coercion — correct for `?self` return type

## Issues Found in Feb 2026 Review (current code state)
See `patterns.md` for full details. Summary of REMAINING issues:
- HIGH: `TwigRenderer::render()` merge order — reserved keys `root`/`matcher`/`accessor` silently overwrite user `$options` of same name
- HIGH: `RouteVoter::isMatchingRoute()` calls `preg_match()` with unvalidated user-supplied regex pattern — no `preg_match` error handling; malformed regex throws a PHP Warning, not a catchable exception (strict types won't help here); should use `@preg_match` + error check or `preg_match` inside try/catch with `set_error_handler`
- HIGH: `NavigationFactory` `$built` instance cache is keyed only by class name — two different anonymous class instances of the same cached nav class share the wrong cache slot (edge case in tests, but `getCacheKey()` returns `static::class` so anonymous classes each get a unique class name — actually fine for anonymous classes, but two named-class instances with different runtime state would silently share the cached tree)
- MEDIUM: `ClosureNavigation` wraps callable via `Closure::fromCallable()` — unnecessary since PHP 8.1+; direct `\Closure` typehint on constructor param eliminates the conversion
- MEDIUM: `NavigationFactory::create()` passes empty `[]` options from `Helper::render()` — `$options` parameter of `create()` is unused by all non-closure navigations (AbstractNavigation ignores it); `ClosureNavigation` is the only consumer; API is misleading
- MEDIUM: `Accessor::isGranted()` logic — if a role is cached as `true`, it `continue`s; if `false`, returns `false`; but the assignment expression `$this->grants[$role] = $isGranted = $this->authorizationChecker->isGranted($role)` is a side-effect in a condition — hard to read
- MEDIUM: `AbstractNavigation::build()` signature has `array $options = []` default that differs from `NavigationInterface::build(array $options)` — interface has no default, abstract class adds one; inconsistency could confuse static analysis
- MEDIUM: `services.php` excludes `Navigation/` directory from autoloading scan but navigations are user-defined outside the bundle — the exclusion is correct for the bundle's own abstract base classes, but the comment is missing, which could confuse maintainers
- LOW: `Factory::addExtensions()` accepts `iterable<ExtensionInterface>` but `addExtension()` always uses priority 0 — callers cannot set priority via the batch method
- LOW: `NavigationFactory::sanitizeCacheKeyPart()` replaces `.` with `_` and `\\` with `.` but the resulting key could still collide for class names that differ only in `_` vs `.` separators after transformation
- LOW: `RouteVoter` caches `lastRequest`/`lastRoute`/`lastRouteParams` as instance state — safe for Symfony request lifecycle, but `clear()` exists only on `Matcher`, not on `RouteVoter`; stale voter state across request boundary in CLI or test contexts
- LOW: No `NavigationFactory` unit test; no `NavigationRegistry` unit test; no `TwigRenderer` test; no `Helper`/`MenuRuntime` test; no `services.php` DI smoke test

## File Locations
- `src/NavigationFactory.php` — orchestration, in-request + PSR-6 caching
- `src/Menu/MenuBuilder.php` — fluent tree builder (children/end now throws LogicException correctly)
- `src/Menu/Item.php` — tree node, uses ArrayCollection, modern __serialize/__unserialize
- `src/Matcher/Matcher.php` — SplObjectStorage cache, voter chain
- `src/Matcher/Voter/RouteVoter.php` — regex route matching, request caching, null guard present
- `src/Accessor/Accessor.php` — Ds\Map + array dual cache for role checks
- `src/Factory/Factory.php` — priority-sorted extension chain (krsort)
- `src/Resources/config/services.php` — tagged_iterator wiring, instance tags (PHP format, not YAML)
- `src/Exception/` — LogicException, InvalidArgumentException, ExceptionInterface marker
- `tests/Unit/` and `tests/Integrational/` — PHPUnit 13, #[Test] attributes
