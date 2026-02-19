# Repository Guidelines

## Project Structure & Module Organization
- Entity layer (`Entity/`) provides `MetaInterface`, `MetaTrait`, and `RobotsBehaviour` enum under `ChamberOrchestra\MetaBundle`.
- CMS form layer (`Cms/Form/`) provides DTOs and Symfony form types — requires external `dev/*` packages.
- Bundle entry point is `ChamberOrchestraMetaBundle.php`; DI extension in `DependencyInjection/ChamberOrchestraMetaExtension.php`.
- Tests belong in `tests/` (autoloaded as `Tests\`); tools are in `bin/` (`bin/phpunit`).
- Autoloading is PSR-4 from the package root (no `src/` directory).
- Requirements: PHP 8.5+, Doctrine ORM ^3.0, Symfony 8.0.

## Build, Test, and Development Commands
- Install dependencies: `composer install`.
- Run the suite: `./bin/phpunit` (uses `phpunit.xml.dist`). Add `--filter ClassNameTest` or `--filter testMethodName` to scope.
- `composer test` is an alias for `vendor/bin/phpunit`.
- Quick lint: `php -l path/to/File.php`; keep code PSR-12 even though no fixer is bundled.

## Coding Style & Naming Conventions
- Follow PSR-12: 4-space indent, one class per file, strict types (`declare(strict_types=1);`).
- Use typed properties and return types; favor `readonly` where appropriate.
- Keep constructors light; prefer small, composable services injected via Symfony DI.

## Testing Guidelines
- Use PHPUnit (13.x). Name files `*Test.php` mirroring the class under test.
- Unit tests live in `tests/Unit/` extending `TestCase`.
- Keep tests deterministic; use data providers where appropriate.
- Cover entity trait behavior, enum choices/formatting, and edge cases.

## Commit & Pull Request Guidelines
- Commit messages: short, action-oriented, optionally bracketed scope (e.g., `[fix] handle null meta description`, `[master] bump version`).
- Keep commits focused; avoid unrelated formatting churn.
- Pull requests should include: purpose summary, key changes, test results.
