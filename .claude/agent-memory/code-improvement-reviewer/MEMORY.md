# Menu Bundle - Code Review Memory

## Architecture
- Namespace: `ChamberOrchestra\MenuBundle` (PSR-4 from package root, no src/)
- PHP ^8.5, ext-ds required
- Legacy alias bundle: `DevMenuBundle` (BC alias for `ChamberOrchestraMenuBundle`)
- No test suite present as of Feb 2026

## Key Patterns
- Navigation: implement `NavigationInterface::build()`, auto-tagged `chamber_orchestra_menu.navigation`
- Factory: `Factory` applies `ExtensionInterface` plugins sorted by priority (krsort = higher int = higher priority)
- `CoreExtension` is priority -10 (runs last, sets defaults like uri/extras/current/attributes)
- Caching: `AbstractCachedNavigation` uses Symfony Cache Contracts, 24h TTL, tag `navigation`
- Matching: voter-based `SplObjectStorage` cache; `RouteVoter` treats route values as regex patterns
- Access: `Ds\Map` caches `isGranted()` results per item object; role grants cached in plain array too

## Known Issues Found (Feb 2026 review)
See `patterns.md` for full details. Summary:
- CRITICAL: `Matcher::isCurrent()` uses uninitialized variable `$current` — PHP TypeError if no voters
- HIGH: `Accessor::hasAccessToChildren()` logic is wrong — returns false if ANY child is denied (should be: returns true only if AT LEAST ONE child is accessible for "show section" use-case)
- HIGH: `RouteVoter::matchItem()` crashes with NPE if no current request (null check missing before `->attributes->get()`)
- HIGH: `Item::getFirstChild()`/`getLastChild()` return `false` (not `null`) when collection is empty — violates `?ItemInterface` return type, causes TypeError
- HIGH: `MenuBuilder::children()` calls `getLastChild()` which returns false — assigns false to `$this->current`, causing crash on next `add()` call
- HIGH: `Item::serialize()`/`unserialize()` uses deprecated `Serializable` interface; `$section` not included in serialized data (lost after deserialization)
- MEDIUM: `NavigationFactory::create()` takes untyped `$nav` parameter — should be `NavigationInterface|string`
- MEDIUM: `MenuBuilder::end()` silently fails — if called with no parents, `array_pop` returns null and assigns null to `$this->current`
- MEDIUM: `AbstractCachedNavigation::configureCacheItem()` does not set cache tags despite having `$this->cache['tags']`
- MEDIUM: `Factory::addExtension()` has no return type declared
- MEDIUM: `LabelExtension` uses `#[Required]` for optional translator injection — NPE if translator not set and translation_domain is used
- MEDIUM: `TwigRenderer::render()` options merge order — user `$options` can be silently overridden by root/matcher/accessor keys

## File Locations
- `NavigationFactory.php` — orchestration, caching logic, $built instance cache
- `Menu/MenuBuilder.php` — fluent tree builder (children/end state machine)
- `Menu/Item.php` — tree node, uses ArrayCollection, implements deprecated Serializable
- `Matcher/Matcher.php` — SplObjectStorage cache, voter chain (uninitialized $current bug)
- `Matcher/Voter/RouteVoter.php` — regex route matching, fetches request twice
- `Accessor/Accessor.php` — Ds\Map + array dual cache for role checks
- `Factory/Factory.php` — priority-sorted extension chain (krsort)
- `Resources/config/services.yml` — tagged_iterator wiring, instance tags
