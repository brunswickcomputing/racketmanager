# RacketManager Public API

## Overview

`Racketmanager\Public_Api` is the supported, strongly-typed integration facade for external WordPress themes and third-party plugins integrating with RacketManager.

Starting with **v11.0**, all legacy procedural helper functions (such as `Racketmanager\get_user()`, `Racketmanager\seo_url()`), runtime class aliases (`src/php/aliases.php`), and `Legacy_User` decorator wrappers have been retired. `Public_Api` provides a clean, stable entry point that delegates directly to internal domain value objects and container-managed repositories.

---

## Lifecycle & Initialization

The facade is automatically initialized during the WordPress plugin lifecycle:

1. **`Plugin_Bootstrap::launch()`**: Registers the active `Simple_Container` as soon as the plugin runtime launches.
2. **`Plugin_Bootstrap::boot()`**: Re-binds the container during application instance boot.

External consumers can safely check for class availability before invoking methods:

```php
if ( class_exists( 'Racketmanager\Public_Api' ) ) {
    $user = \Racketmanager\Public_Api::get_user( get_current_user_id() );
}
```

---

## Method Reference

All methods on `Racketmanager\Public_Api` are static and strictly typed.

### User Resolution

#### `get_user( int $user_id ): ?Racketmanager\Domain\Player\User`
Retrieves a player user domain entity by WordPress user ID.

- **Parameters**: `int $user_id` — The WordPress user ID.
- **Returns**: `User|null` — The domain entity if found, or `null` if the user does not exist or the container is not initialized.
- **Example**:
  ```php
  $user = \Racketmanager\Public_Api::get_user( get_current_user_id() );
  if ( $user ) {
      echo esc_html( $user->display_name );
  }
  ```

---

### SEO Slugs

#### `seo_url( string $string_field ): string`
Encodes a human-readable string (such as a player name or tournament title) into an SEO-friendly URL slug.

- **Parameters**: `string $string_field` — Text string to encode.
- **Returns**: `string` — Normalized slug (spaces converted to `-`, hyphens to `_`, ampersands to `and`).
- **Example**:
  ```php
  $slug = \Racketmanager\Public_Api::seo_url( 'John Smith' );
  // Output: 'john-smith'
  ```

#### `un_seo_url( string $string_field ): string`
Decodes an SEO-friendly URL slug back into its approximate text representation.

- **Parameters**: `string $string_field` — Slug string to decode.
- **Returns**: `string` — Decoded text string.
- **Example**:
  ```php
  $text = \Racketmanager\Public_Api::un_seo_url( 'john-smith' );
  // Output: 'john smith'
  ```

---

### Messages & Notifications

#### `get_unread_message_count( int $user_id ): int`
Returns the count of unread messages for a given player user.

- **Parameters**: `int $user_id` — The player user ID.
- **Returns**: `int` — Number of unread messages (defaults to `0` if none found or repository unavailable).
- **Example**:
  ```php
  $unread_count = \Racketmanager\Public_Api::get_unread_message_count( $user->id );
  if ( $unread_count > 0 ) {
      echo ' (' . esc_html( $unread_count ) . ')';
  }
  ```

#### `get_messages( int $user_id, array $args = [] ): array|int`
Queries messages for a user with optional filter criteria.

- **Parameters**:
  - `int $user_id` — The player user ID.
  - `array<string, mixed> $args` — Optional filters (e.g. `[ 'status' => 'unread', 'count' => true ]`).
- **Returns**: `array<int, Racketmanager\Domain\Communication\Message>|int` — Array of message entities, or integer count when `'count' => true`.
- **Example**:
  ```php
  $messages = \Racketmanager\Public_Api::get_messages( $user->id, [ 'status' => 'unread' ] );
  ```

---

### Container Access

#### `container(): ?Racketmanager\Services\Container\Simple_Container`
#### `get_container(): ?Racketmanager\Services\Container\Simple_Container`
Provides access to the underlying dependency injection container for advanced service resolution.

- **Returns**: `Simple_Container|null` — The active container instance, or `null` if uninitialized.

#### `init( Simple_Container $container ): void`
Initializes or updates the container reference used by the facade.

#### `reset(): void`
Clears the cached container instance. Intended for isolated testing.

---

## Migration Guide

| Retired Legacy Pattern (v10 & earlier) | Supported Public API (v11+) |
| :--- | :--- |
| `Racketmanager\get_user( $user_id )` | `\Racketmanager\Public_Api::get_user( $user_id )` |
| `Racketmanager\seo_url( $string )` | `\Racketmanager\Public_Api::seo_url( $string )` |
| `Racketmanager\un_seo_url( $slug )` | `\Racketmanager\Public_Api::un_seo_url( $slug )` |
| `$legacy_user->get_messages( [ 'count' => true, 'status' => 'unread' ] )` | `\Racketmanager\Public_Api::get_unread_message_count( $user_id )` |
| `class_exists( 'Racketmanager\Domain\User' )` | `class_exists( 'Racketmanager\Public_Api' )` |

---

## Theme Integration Example

The following pattern demonstrates safe header dropdown integration for themes (as implemented in `wp-content/themes/sydney-child/functions.php`):

```php
function sydney_woocommerce_header_cart() {
    if ( is_user_logged_in() ) {
        $current_user    = wp_get_current_user();
        $account_url     = esc_url( home_url( '/member-account/' ) );
        $favourites_url  = esc_url( home_url( '/member-account/favourites/' ) );
        $messages_url    = esc_url( home_url( '/member-account/messages/' ) );
        $memberships_url = esc_url( home_url( '/member-account/memberships/' ) );
        $profile_url     = $account_url;
        $message_count   = 0;

        if ( class_exists( 'Racketmanager\Public_Api' ) ) {
            $rm_user = \Racketmanager\Public_Api::get_user( get_current_user_id() );
            if ( $rm_user ) {
                $profile_path = '/player/' . \Racketmanager\Public_Api::seo_url( $rm_user->display_name ) . '/';
                if ( ! empty( $rm_user->btm ) ) {
                    $profile_path .= rawurlencode( (string) $rm_user->btm ) . '/';
                }
                $profile_url   = esc_url( home_url( $profile_path ) );
                $user_id       = (int) ( $rm_user->id ?? $rm_user->ID ?? get_current_user_id() );
                $message_count = \Racketmanager\Public_Api::get_unread_message_count( $user_id );
            }
        }
        ?>
        <div class="dropdown">
            <a class="nav-link dropdown-toggle header-username" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <?php echo esc_html( $current_user->display_name ); ?>
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?php echo esc_attr( $profile_url ); ?>"><?php esc_html_e( 'Profile', 'sydney' ); ?></a></li>
                <li><a class="dropdown-item" href="<?php echo esc_attr( $account_url ); ?>"><?php esc_html_e( 'Account', 'sydney' ); ?></a></li>
                <li><a class="dropdown-item" href="<?php echo esc_attr( $memberships_url ); ?>"><?php esc_html_e( 'Memberships', 'sydney' ); ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo esc_attr( $messages_url ); ?>"><?php esc_html_e( 'Messages', 'sydney' ); ?><?php echo ( empty( $message_count ) ) ? '' : ' (' . esc_html( $message_count ) . ')'; ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo esc_attr( $favourites_url ); ?>"><?php esc_html_e( 'Favourites', 'sydney' ); ?></a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?php echo esc_url( wp_logout_url() ); ?>"><?php esc_html_e( 'Logout', 'sydney' ); ?></a></li>
            </ul>
        </div>
        <?php
    }
}
```

---

## Testing Strategy & CI Boundaries

Testing around `Racketmanager\Public_Api` is organized into two distinct layers with defined boundaries and CI policies:

### 1. Isolated Unit Tests (`tests/Unit/Public_Api_Test.php`)
- **Purpose:** Verifies all methods and fallback paths on `Public_Api` in strict isolation.
- **Dependencies:** Uses pure mocks for repository interfaces (`User_Repository_Interface`, `Message_Repository_Interface`) and in-memory containers (`Simple_Container`).
- **Isolation:** Has zero dependencies on external themes or files. Runs on every build and CI environment.

### 2. Consumer Integration Tests (`tests/Integration/Sydney_Child_Header_Cart_Test.php`)
- **Purpose:** Validates that external theme consumers (specifically `sydney_woocommerce_header_cart()` in `wp-content/themes/sydney-child/functions.php`) correctly interact with `Public_Api`, properly encoding URLs, formatting BTM strings, and handling message badges in rendered HTML.
- **Real Execution vs. Mocking:** Does not mock the theme function under test; executing the live theme function provides real integration confidence.
- **Environment Handling:** Resolves the theme file dynamically via `SYDNEY_CHILD_THEME_PATH` or `dirname( __DIR__, 4 ) . '/themes/sydney-child/functions.php'`. When the theme checkout is not present (such as in standalone plugin checkouts), the test suite cleanly calls `$this->markTestSkipped()`.

### 3. CI Issue Policy & Skip Flags
- **Strict Issue Checking:** CI workflows (`.github/workflows/ci.yml` and `.github/workflows/sonarcloud.yml`) run PHPUnit with `--display-all-issues --fail-on-all-issues --do-not-fail-on-skipped`.
- **Zero False Alarms:** Because PHPUnit's `--fail-on-all-issues` includes `--fail-on-skipped` by default, `--do-not-fail-on-skipped` is paired with it so that intentional skips due to missing external repositories do not fail builds.
- **Defect Detection Preserved:** Real regressions (warnings, deprecations, notices, risky tests, or failing assertions) continue to immediately fail CI builds with exit status code 1.
