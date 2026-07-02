# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

TODO

## Code Standards

Follow clean code principles and SOLID design patterns when working with this codebase:
- Write clean, readable, and maintainable code
- Apply SOLID principles (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion)
- Use meaningful variable and method names
- Keep methods and classes focused on a single responsibility
- Favor composition over inheritance
- Write code that is easy to test and extend

### Testing Requirements
- **Everything MUST be tested** — every piece of new functionality must be covered by either a unit test or a functional test (booting `tests/Application`). Prefer unit tests for isolated logic; use functional tests for what unit tests can't cover: container wiring, Doctrine mappings/listeners, grids, routes and controllers
- **Additionally verify with Playwright whenever possible** — if the functionality has any admin/shop UI surface, verify it end-to-end with the Playwright MCP against the running test application (see [UI Verification](#ui-verification)) on top of the automated tests
- Follow the BDD-style naming convention for test methods (e.g., `it_should_do_something_when_condition_is_met`)
- **MUST use Prophecy for mocking** - Use the `ProphecyTrait` and `$this->prophesize()` for all mocks, NOT PHPUnit's `$this->createMock()`
- **Form testing** - Use Symfony's best practices for form testing as documented at https://symfony.com/doc/current/form/unit_testing.html
  - Extend `Symfony\Component\Form\Test\TypeTestCase` for form type tests
  - Use `$this->factory->create()` to create form instances
  - Test form submission, validation, and data transformation
- Ensure tests are isolated and don't depend on external state
- Test both happy path and edge cases

### UI Verification
- **All UI changes MUST be verified using the Playwright MCP** - After making any change that affects the rendered UI (templates, forms, styling, layout, flash messages, etc.), use the Playwright MCP to navigate the running test application and confirm the change renders and behaves as expected
- Run the test application (see [Test Application](#test-application)) and use the Playwright MCP `browser_navigate`, `browser_snapshot`, and `browser_take_screenshot` tools to inspect the affected pages
- Verify both the visual result and the interactive behavior (e.g. submitting forms, triggering flash messages)

## PHP Version

This plugin targets **PHP 8.1** for local development. A committed `.php-version` file (`8.1`) pins the version, and the Symfony CLI honors it: in this directory `symfony php` and `symfony composer` automatically use PHP 8.1 — no matter which PHP is globally `brew link`ed.

On the maintainer's machine, `php` and `composer` are aliased to `symfony php` / `symfony composer` (in `~/.zshrc`), so `composer update`, `composer analyse`, `php vendor/bin/phpunit`, etc. transparently run on 8.1 here. Caveats:
- Aliases only apply to interactive commands whose first word is `php`/`composer`. Tools run directly via their shebang (e.g. `vendor/bin/phpunit` **without** a leading `php`) use the globally linked PHP instead — prefix them with `php` to route through 8.1.
- `composer.json` is intentionally **not** pinned via `config.platform.php`, because CI must resolve/test against the full matrix of supported PHP versions.

To run a command against a different PHP version locally (matching CI), bypass the aliases with `command` after switching the linked version:

    command composer update
    command php vendor/bin/phpunit

## Development Commands

Based on the `composer.json` scripts section:

### Code Quality & Testing
- `composer analyse` - Run PHPStan static analysis (level 8)
- `composer check-style` - Check code style with ECS (Easy Coding Standard)
- `composer fix-style` - Fix code style issues automatically with ECS
- `composer phpunit` - Run PHPUnit tests

### Static Analysis

#### PHPStan Configuration
PHPStan is configured in `phpstan.neon` with:
- **Analysis Level**: max (strictest)
- **Extensions**: Auto-loaded via `phpstan/extension-installer`
  - `phpstan/phpstan-symfony` - Symfony framework integration
  - `phpstan/phpstan-doctrine` - Doctrine ORM integration
  - `phpstan/phpstan-phpunit` - PHPUnit test integration
  - `jangregor/phpstan-prophecy` - Prophecy mocking integration
- **Symfony Integration**: Uses console application loader (`tests/console_application.php`)
- **Doctrine Integration**: Uses object manager loader (`tests/object_manager.php`)
- **Exclusions**: Test application directory and Configuration.php
- **Baseline**: Generate with `composer analyse -- --generate-baseline` to track improvements

### Test Application
The plugin includes a test Symfony application in `tests/Application/` for development and testing:
- Navigate to `tests/Application/` directory
- Run `yarn install && yarn build` to build assets
- Use standard Symfony commands for the test app
- **Sylius Backend Credentials**: Username: `sylius`, Password: `sylius`

## Bash Tools Recommendations

Use the right tool for the right job when executing bash commands:

- **Finding FILES?** → Use `fd` (fast file finder)
- **Finding TEXT/strings?** → Use `rg` (ripgrep for text search)
- **Finding CODE STRUCTURE?** → Use `ast-grep` (syntax-aware code search)
- **SELECTING from multiple results?** → Pipe to `fzf` (interactive fuzzy finder)
- **Interacting with JSON?** → Use `jq` (JSON processor)
- **Interacting with YAML or XML?** → Use `yq` (YAML/XML processor)

Examples:
- `fd "*.php" | fzf` - Find PHP files and interactively select one
- `rg "function.*validate" | fzf` - Search for validation functions and select
- `ast-grep --lang php -p 'class $name extends $parent'` - Find class inheritance patterns

## Architecture Overview

### Translations
The plugin provides multilingual support through translation files in `src/Resources/translations/`:

- **Translation Files**: Available in 10 languages (en, da, de, es, fr, it, nl, no, pl, sv)
- **Translation Domains**:
  - `messages.*` - General UI translations
  - `flashes.*` - Flash message translations (success/error messages)

Key translation keys:
- `setono_sylius_completeness.ui.*` - UI labels
- `setono_sylius_completeness.form.*` - Form field labels
- `setono_sylius_completeness.single_message` - A flash message
