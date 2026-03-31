# RacketManager Plugin Development Guidelines

This document provides project-specific information for advanced developers working on the RacketManager WordPress plugin.

## 1. Build and Configuration

### Requirements
- PHP 8.3 or higher.
- Node.js and npm for frontend asset compilation.
- Composer for PHP dependency management.

### Setup Instructions
1.  **PHP Dependencies**: Run `composer install` to install required PHP packages (including PHPUnit for development).
2.  **Frontend Assets**: Run `npm install` followed by `npm run build` to compile JS and CSS files using **esbuild**.
    - For development with sourcemaps: `npm run build`
    - For production (minified): `npm run build:prod`

## 2. Testing

### Configuration
The project uses **PHPUnit 10** for testing. The configuration is defined in `phpunit.xml.dist`.
A custom `tests/bootstrap.php` handles autoloading and loading of WordPress stubs (`tests/wp-stubs.php`).

### Running Tests
-   **Run all tests**: `./vendor/bin/phpunit`
-   **Run a specific test suite**: `./vendor/bin/phpunit --testsuite Unit` or `Integration`
-   **Run a specific test file**: `./vendor/bin/phpunit tests/Unit/Simple_Sanity_Test.php`
-   **Run a specific test method**: `./vendor/bin/phpunit --filter test_method_name`

### Adding New Tests
-   **Unit Tests**: Place in `tests/Unit/`. Mock external dependencies (like WordPress core functions) using PHPUnit's mocking framework or the provided stubs in `tests/wp-stubs.php`.
-   **Integration Tests**: Place in `tests/Integration/`. These are for testing components that interact with the database or other services.
-   **Namespace**: Follow the `Racketmanager\Tests\` namespace, mapping to the `tests/` directory (PSR-4).

### Sample Test Case
```php
<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class Simple_Sanity_Test extends TestCase {
    public function test_sanity_check(): void {
        $this->assertTrue(true);
    }
}
```

## 3. Development Information

### Code Style
-   **WordPress Coding Standards**: The project strictly follows WordPress Coding Standards as defined in `phpcs.xml.dist`.
-   **PHP Version**: Target PHP 8.3+. Use strict typing (`declare(strict_types=1);`).
-   **Autoloading**: PSR-4 compliant. `Racketmanager\` namespace maps to `src/php/`.
-   **Asset Organization**:
    - Source: `src/js/` and `css/`
    - Compiled: `dist/js/` and `dist/css/`
-   **Dependency Injection**: A simple container implementation is available at `Racketmanager\Services\Container\Simple_Container`.

### Debugging
- WordPress stubs are provided in `tests/wp-stubs.php` to facilitate testing outside a full WordPress environment.
- Use `qodana.yaml` for static analysis configuration.
