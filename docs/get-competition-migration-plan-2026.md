# Strategic Plan: Removing the `get_competition()` Legacy Function

This document outlines the multi-phase strategy to transition the RacketManager plugin from a **Service Locator (Global Function)** pattern to a **Dependency Injection (Service)** pattern for competition management.

---

## 📋 Phase 1: Service & Infrastructure Alignment (COMPLETE ✅)
*Goal: Ensure the modern `Competition_Service` is a complete functional replacement.*

1.  **Enhance `Competition_Service`**:
    *   Added `get_competition()` method to replicate legacy behavior (global fallback, search by ID/name, and hydration) using the modern `Competition_Factory`.
    *   Implemented robust union types and unit tests to ensure stability across PHP 8.2+.
2.  **Verify Service Provider**:
    *   Ensured the service is correctly registered and available via the Dependency Injection container.

## 📋 Phase 2: Refactoring Modern Admin & Service Layers (COMPLETE ✅)
*Goal: Remove global function calls from code that already supports Dependency Injection.*

1.  **Constructor Injection**:
    *   In classes like `Admin_League`, `Admin_Display`, and `Admin_Cup`, replaced the `use function Racketmanager\get_competition` import with calls to `$this->competition_service->get_competition()`.
2.  **AJAX & REST Handlers**:
    *   Refactored `Rest_Resources.php` to use `Competition_Service`.
    *   Verified that AJAX controllers already utilize the service layer.
    *   Created `Rest_Resources_Test` to ensure stability.

## 📋 Phase 3: Domain Layer Purge (COMPLETE ✅)
*Goal: Restore Domain Purity by removing Infrastructure concerns from Entities.*

1.  **Refactor `Rubber.php` and Domain Entities**:
    *   Refactored `Rubber::check_players()` to accept an optional `$competition` parameter, reducing reliance on the global `get_competition()` helper.
    *   Refactored `Player::get_matches()` to handle injected `Competition` objects.
    *   Refactored `Player::get_competitions()` to accept an optional `Competition_Service` for hydrating competition details using the modern service layer.
    *   **Rule**: Entities must never call global helper functions or repositories.

## 📋 Phase 4: Presentation & Template Hydration (COMPLETE ✅)
*Goal: Decouple View templates from global state.*

1.  **Controller-to-View Data Flow**:
    *   Verified that Admin Controllers already hydrate the `Competition` object and pass it explicitly to templates.
    *   Confirmed templates already rely on the passed `$competition` variable rather than global lookups.
2.  **Remove Template Dependencies**:
    *   Confirmed zero occurrences of `get_competition()` or `$GLOBALS['competition']` inside template files.

## 📋 Phase 5: Legacy Wrappers & Final Deprecation (IN PROGRESS ⏳)
*Goal: Clean up non-DDD compliant functions and remove the legacy entry point.*

1.  **Remaining Usages Audit**:
    *   **Public Shortcodes (`src/php/Public/`)**:
        *   `src/php/Public/Shortcodes.php` (Line 265): `get_competition( $competition_name, 'name' )` in `get_matches()`
        *   `src/php/Public/Shortcodes.php` (Line 719): `get_competition( $competition_id )` in `show_order_of_play()`
        *   `src/php/Public/Shortcodes_Club.php` (Line 187): `get_competition( $competition_name, 'name' )` in `show_club_competitions()`
    *   **Domain Backward Compatibility Fallbacks (`src/php/Domain/`)**:
        *   `src/php/Domain/Player.php` (Line 760): Fallback in `get_matches()` when a `Competition` object is not passed
        *   `src/php/Domain/Player.php` (Line 1006): Fallback in `get_competitions()` when `Competition_Service` is not provided
    *   **Function Definition**:
        *   `functions.php` (Line 190): Global helper marked `@deprecated`
2.  **Deprecation Notice**:
    *   Marked the global `get_competition()` function as `@deprecated` in `functions.php`. ✅
    *   Added a `trigger_error(..., E_USER_DEPRECATED)` to the function body. ✅
3.  **Decommission**:
    *   Will be performed in a future release once all consumers are refactored.

---

## 🔍 Audit of Remaining `get_competition()` Usages

| Location | File | Method / Context | Purpose / Plan |
| :--- | :--- | :--- | :--- |
| **Public Layer** | `src/php/Public/Shortcodes.php:265` | `get_matches()` | Lookup competition by name from query vars |
| **Public Layer** | `src/php/Public/Shortcodes.php:719` | `show_order_of_play()` | Lookup competition by ID from shortcode atts |
| **Public Layer** | `src/php/Public/Shortcodes_Club.php:187` | `show_club_competitions()` | Lookup competition by name from query vars |
| **Domain Layer** | `src/php/Domain/Player.php:760` | `get_matches()` | Backward compatibility fallback |
| **Domain Layer** | `src/php/Domain/Player.php:1006` | `get_competitions()` | Backward compatibility fallback |
| **Root Definition** | `functions.php:190` | `get_competition()` | Deprecated global helper |

---

## 🛠 Summary Table of Changes

| Layer | Priority | Technique |
| :--- | :--- | :--- |
| **Domain Entities** (`Rubber`, etc.) | 🔴 High | Parameter/DTO Injection |
| **Admin Controllers** | 🟡 Med | Constructor Injection |
| **Shortcodes** | 🟡 Med | Service Resolution |
| **Templates** | 🟢 Low | Explicit Variable Passing |
| **Legacy `pwd/` Code** | 🟢 Low | Final Sweep / Wrapper Logic |

*Last Updated: 2026-09-06*
