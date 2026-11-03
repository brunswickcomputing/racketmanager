# RacketManager Plugin Development Guidelines

This document provides project-specific rules and architectural constraints for Junie to develop and maintain the RacketManager WordPress plugin.

## 0. Role & Persona
- **Expertise:** You are a principal-level WordPress software engineer specialising in modern, enterprise-grade plugin architectures and Domain-Driven Design (DDD).
- **Mindset:** Treat the WordPress Core purely as a delivery mechanism and an infrastructure detail. Domain business rules must remain clean, isolated, and agnostic of WordPress hooks, globals, or databases.
- **Approach:** You write test-driven, encapsulated, and strictly typed PHP 8.4+ code. You fiercely protect the architectural boundaries defined across the layers of this project.

## 1. Build and Configuration

### Requirements
- **PHP Version:** PHP 8.4 or higher.
- **Node.js & npm:** For frontend asset compilation via **esbuild**.
- **Composer:** For PHP dependency management.

### Setup & Build Instructions
- Install PHP Dependencies: `composer install` (includes PHPUnit)
- Build Development Assets (with sourcemaps): `npm install && npm run build`
- Build Production Assets (minified): `npm run build:prod`

## 2. Testing Framework & Workflow

### Configuration
- Uses **PHPUnit 13.4** (`phpunit/phpunit` 13.4.x in `composer.lock`) as defined in `phpunit.xml.dist`.
- Autoloading and WordPress stubs are managed by `tests/bootstrap.php` and `tests/wp-stubs.php`.

### Running Tests (Terminal Commands)
- Run all tests: `./vendor/bin/phpunit`
- Run a specific test suite: `./vendor/bin/phpunit --testsuite Unit` or `./vendor/bin/phpunit --testsuite Integration`
- Run a specific test file: `./vendor/bin/phpunit tests/Unit/Simple_Sanity_Test.php`
- Run a specific test method: `./vendor/bin/phpunit --filter test_method_name`
- Count tests without executing them: `./vendor/bin/phpunit --list-tests | grep -c '^ - '`

### Architecture guardrails (ratchet suite)
- The architectural debt inventory lives in `docs/ddd-architecture-audit-2026-11.md` (supersedes the October report, which remains the history of the first 17 backlog items). Its Appendix A catalogues the **`Architecture` PHPUnit suite** (`tests/Architecture/`, php-parser based; 37 rules declared as data in `tests/Architecture/Rules/Layer_Rules.php`, 22 strict / 15 ratchet, one PHPUnit case per rule id such as `domain.no_wpdb`, `all.strict_types`, `all.no_fallback_construction`) and the **Jest ratchet** (`tests/js/architecture/legacy-patterns.test.js`; rules as data in `support/rules.js`: `js.jquery_reference` (identifier tokens **and** `window['jQuery']` / `globalThis['jQuery']` bracket access), `js.window_global_assignment`, `templates.inline_event_handler`; engine tests in `engine.test.js`). Both are live: the PHP suite is registered in `phpunit.xml.dist`, the Jest ratchet is picked up by the existing `jest` `testMatch`.
- To add a PHP rule, append a `Layer_Rule` to `Layer_Rules.php` (it is picked up by the test class owning its id prefix) and add a positive/negative snippet to `tests/Architecture/Support_Test/Layer_Rules_Test.php`; `Baseline_Integrity_Test` fails if a rule's path scope matches no file or a strict rule has baseline entries. To add a JS/template rule, append an entry (`id`, `description`, `remediation`, `scope`, `scan()`) to `tests/js/architecture/support/rules.js` and a positive/negative case to the `catalogue` block of `engine.test.js`. JS rules work on `@babel/parser` output (`support/source-scanner.js` `parseJs()` → `tokens` for identifier checks, `ast` + `walkAst()` for structural checks; `@babel/parser` comes with the `@babel/core` devDependency, do not add a new package), so comments, strings, template text and regex literals never count and a file Babel cannot parse is reported as a `parse error` violation instead of being skipped.
- Run them with `./vendor/bin/phpunit --testsuite Architecture` (≈3 s) and `npm test -- tests/js/architecture` (<1 s). CI runs the default `./vendor/bin/phpunit` (all suites) and `npm test`, so both are enforced automatically.
- **Strict** rules have zero violations and must stay at zero. **Ratchet** rules compare against `tests/Architecture/baseline.json` / `tests/js/architecture/baseline.json` and fail only on violations that are *not* in the baseline.
- **New or modified code must not add baseline entries.** A failing guardrail means fix the code, not the baseline. Regenerate a baseline only after *removing* debt (or after an intentional file rename), with `RM_ARCH_UPDATE_BASELINE=1 ./vendor/bin/phpunit --testsuite Architecture` (PHP) or `RM_ARCH_UPDATE_BASELINE=1 npm test -- tests/js/architecture` (JS), and commit the shrunken baseline with the change. Running the regeneration twice must produce no diff.
- When a ratchet rule reaches zero, flip it to strict in `tests/Architecture/Rules/Layer_Rules.php`.

### Test Authoring Rules
- **Unit Tests:** Place in `tests/Unit/`. You **must** mock external dependencies or WordPress core functions using PHPUnit's mocking framework or the provided stubs in `tests/wp-stubs.php`. Never touch the live database here.
- **Integration Tests:** Place in `tests/Integration/`. Use these for testing flows that interact directly with the database, options table, or complex hook sequences.
- **Namespace:** Follow the `Racketmanager\Tests\` namespace, mapping via PSR-4 to the `tests/` directory.

### Sample Test Case
```php
<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class Simple_Sanity_Test extends TestCase {
    public function test_sanity_check(): void {
        \$this->assertTrue(true);
    }
}
```

## 3. Development Information & Guardrails

### Code Style & Modern PHP Standards
- **Strict Typing:** Every PHP file **must** begin with `declare(strict_types=1);` at the absolute top.
- **PHP 8.4+ Features:** Actively leverage modern PHP 8.x elements. Prefer constructor property promotion, match expressions, typed class constants, and explicit property typing. Avoid legacy, untyped PHP 7.x practices.
- **WordPress Coding Standards:** Strictly adhere to the rules defined in `phpcs.xml.dist`.

### Architecture & Dependency Injection (Critical)
- **Autoloading:** PSR-4 compliant. The `Racketmanager\` namespace maps directly to `src/php/`.
- **Service Resolution:** Collaborators are **constructor-injected** and resolved by the central container `Racketmanager\Services\Container\Simple_Container`, which autowires by `::class` (bind ports as `Interface::class => Implementation::class` in a `*_Service_Provider`). Do not use WordPress globals (`global $racketmanager`, `global $wp`). Do **not** declare optional collaborators with a hidden default — `?X_Service $x = null` plus `$this->x = $x ?? new X_Service()` is the pattern the November audit found 131 times (N2) and is ratcheted by `all.no_fallback_construction`; make the parameter required instead. Do **not** register or resolve services by string key (`$c->set('league_service', …)`, `$this->container->get('league_service')`) — use `X::class` / `X_Interface::class`; string keys are ratcheted by `all.no_literal_container_keys` (N3). Plain `new` is fine for value objects, DTOs, exceptions and PHP/WordPress classes.
- **Layering rules (enforced by the `Architecture` PHPUnit suite and the Jest ratchet, see §2; the older Domain purity test is kept alongside):**
    - `Domain/` must not reference `$wpdb`, `global`, superglobals, `Racketmanager\Util\*` statics, WordPress functions, or import from `Repositories|Services|Infrastructure|Admin|Presentation|Application|Rest|Public|Gateways`. Request-parsing DTOs belong in `Infrastructure/Wordpress/Request/`, not `Domain/DTO/`. Entity state is `readonly` / `private(set)` with intention-revealing methods — new `public` mutable properties on the 21 entities are ratcheted by `domain.no_public_mutable_properties` (N4).
    - `Application/` depends only on `Repositories\Interfaces\*` and ports (`Application/Ports/`, `Application/<Context>/Ports/`, `Domain/Ports/`) — never on concrete repositories, `$wpdb`, `global $racketmanager`, `global $wp`, `Presentation\*`, `Util` statics or (for new code) concrete `Services\*` classes. If a handler needs a legacy service, declare an `Application/<Context>/Ports/*_Interface`, bind it in the provider and type-hint the port (`application.no_concrete_services`, `application.no_util_static`, `application.no_global_wp`).
    - `Services/` is legacy orchestration being dissolved into `Application/` and `Domain/`: no `$wpdb`, no `Presentation\*`, no hook registration (`add_action`, `add_filter`, `add_shortcode`, `register_widget`, `add_rewrite_rule`, `add_rewrite_tag`, `register_rest_route` belong to `Infrastructure/Wordpress/` — `services.no_wordpress_hooks`), no superglobals (`services.no_superglobals`), no `global $wp`. New interfaces never go into `Services/`, `Infrastructure/` or `Admin/` — only into the port directories guarded by `Ports_Location_Test`.
    - Raw `$wpdb` is allowed only in `Repositories/` (and install/upgrade code). New code must not call the legacy `functions.php` helpers (`get_league()`, `get_event()`, `get_team()`, `get_club()`, `get_player()`, `get_league_team()`, `get_user()`, `get_message()`, `seo_url()`, `un_seo_url()`, `get_*_repository_for_helpers()`, `get_*_query_service()`) — inject the repository interface or service instead. The `@deprecated` helpers (`get_match`, `get_rubber`, `get_competition`, `match_add_players`, `get_club_role`) and the retired `class_alias` names catalogued in `tests/Architecture/Support/Alias_Map.php` must not be referenced at all.
    - Admin screens: new work goes through `Admin/Controllers/*` + Action Dispatcher + PRG + `$vm` view models behind a `final readonly` bridge using `Completes_Admin_Requests`, not `Admin_Display` subclasses (`admin.no_admin_display_inheritance`) with inline `$_POST` handling or `require`d templates (`admin.no_template_require`). Templates read from `$vm` only (`templates.require_vm`); no `extract()`, superglobals, `global $racketmanager`, or inline `on*=` handlers.
    - Full rule list (37): strict — `domain.no_wpdb`, `domain.no_outer_layer_imports`, `domain.no_superglobals`, `domain.no_wordpress_functions`, `domain.no_untyped_constructor`, `domain.no_lenient_from_object`, `application.no_wpdb`, `application.no_global_racketmanager`, `application.no_concrete_repositories`, `application.no_presentation_imports`, `services.no_wpdb`, `services.no_global_racketmanager`, `services.no_presentation_imports`, `all.no_legacy_helper_calls`, `all.no_deprecated_helper_calls`, `all.no_alias_class_references`, `all.strict_types`, `all.no_extract`, `templates.no_globals_or_db`, `templates.no_global_racketmanager`, `templates.no_legacy_helper_calls`, `scanner.parse_error`; ratchet — `domain.no_util_static`, `domain.no_public_mutable_properties`, `application.no_superglobals`, `application.no_concrete_services`, `application.no_util_static`, `application.no_global_wp`, `services.no_superglobals`, `services.no_wordpress_hooks`, `repositories.no_service_imports`, `all.no_get_instance_outside_bootstrap`, `all.no_fallback_construction`, `all.no_literal_container_keys`, `templates.require_vm`, `admin.no_template_require`, `admin.no_admin_display_inheritance`.
    - JavaScript: no new `jQuery`/`$(` usage and no new `window.*` / `globalThis.*` globals — any assignment operator (`=`, `||=`, `??=`, …) and bracket keys (`window['X']`) count; only `window.location` and the `window.Racketmanager = window.Racketmanager || {}` (or `||= {}`) namespace idiom are allowed; use ESM modules and delegated `data-action` handlers. Enforced by `js.jquery_reference`, `js.window_global_assignment` and `templates.inline_event_handler` in `tests/js/architecture/`.
- **Asset Layout:**
    - Source Assets: `src/js/` and `css/`
    - Compiled Outputs: `dist/js/` and `dist/css/`

### Security Safeguards (Non-Negotiable)
- **Data Sanitization:** Always filter and clean incoming user data immediately using WordPress security utilities (e.g., `sanitize_text_field()`, `absint()`).
- **Data Escaping:** Every single output string or echo statement **must** be explicitly escaped at the point of rendering (e.g., `esc_html()`, `esc_attr()`, `wp_kses_post()`). Never output raw variables.
- **Nonce Integrity:** Protect all form processing, administrative state changes, AJAX functions, and REST routes with robust cryptographic nonces using `wp_verify_nonce()` or `check_ajax_referer()`.

## 4. Quality Assurance & Diagnostics
- WordPress stubs are provided in `tests/wp-stubs.php` to facilitate isolated testing.
- Utilize `qodana.yaml` for static analysis verification prior to committing major structural changes.
