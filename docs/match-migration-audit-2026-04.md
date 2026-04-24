# Audit: Legacy Racketmanager_Match Method Usages

This document details the remaining usages of the legacy `Racketmanager_Match` class (excluding `get_instance` and `__constructor`) as of April 2026, and provides a roadmap for their migration to the modern service-oriented architecture.

## 1. Current State of Usages

### 1.1 Display and Data Retrieval (Public/Shortcodes & Templates)
These are primarily read-only usages where the object drives UI logic in templates and shortcodes.
- **Properties:** `id`, `date`, `start_time`, `match_day`, `location`, `score`, `link`, `teams`, `league`, `season`, `is_pending`, `confirmed`, `confirmed_display`, `is_walkover`, `walkover`, `is_retired`, `retired`, `is_shared`, `result_overdue_date`, `post_id`.
- **Methods:**
    - `get_rubbers()`: Used to fetch individual rubber results for display.
    - `match_title()`: Used for generating titles in `match.php` template.
    - `is_update_allowed()`: Checks if the user has permission to edit results.

### 1.2 Match Management (AJAX & Admin Controllers)
Used for modifying match-level metadata and state.
- **Methods:**
    - `update_match_date()`: Used in `Ajax_Fixture` to reschedule matches.
    - `set_status()`: Updates the match status (e.g., Postponed, Cancelled).
    - `set_teams()`: Updates participating teams in `Admin_League`.
    - `delete()`: Removes matches from the system.
    - `add()`: Used when creating new manual matches or league fixtures.

### 1.3 Notifications & Lifecycle (Services & AJAX)
Triggered during specific match lifecycle events.
- **Methods:**
    - `notify_next_match_teams()`: Critical for knock-out competitions, used in `Championship_Manager` and `Ajax_Admin`.
    - `notify_date_change()`: Triggered internally when `update_match_date` is called.
    - `notify_team_withdrawal()`: Sends emails when a team pulls out.

### 1.4 Legacy Persistence Path
Some result-related operations still fall back to legacy methods when not using `Fixture_Result_Manager`.
- **Methods:**
    - `update_result_database()`: Directly interacts with `$wpdb` for result updates.
    - `update_result_tie()`: Specifically handles tie-break score updates.
    - `report_result()`: Used by `Exporter.php` to generate CSV/Excel exports.

---

## 2. Migration Strategy (Phase 8)

The goal is to replace these remaining usages with modern service-based and repository-based logic, allowing for the final deprecation of `Racketmanager_Match`.

### 2.1 Enhance `Fixture_Detail_Service` and `Fixture_Details_DTO`
- **Objective:** Move all "Display Logic" away from the legacy class.
- **Action:** Update `Fixture_Details_DTO` to include `link`, `score_display`, `status_flags`, and `is_update_allowed` status.
- **Action:** Refactor `Shortcodes_Match.php` to use `Fixture_Detail_Service` to fetch a DTO instead of `get_match()`.

### 2.2 Expand `Fixture_Repository` and `Fixture_Maintenance_Service`
- **Objective:** Migrate all match metadata updates and provide a service-layer API for controllers.
- **Action:** Move logic from `update_match_date`, `set_status`, `set_teams`, and `delete` into the `Fixture_Repository`.
- **Action:** Update `Ajax_Fixture`, `Admin_League`, `Ajax_Admin`, and other controllers to call `Fixture_Maintenance_Service` (or similar) rather than repositories directly.

### 2.3 Introduce `Fixture_Lifecycle_Service`
- **Objective:** Centralize business events and notifications.
- **Action:** Create a service to handle `reschedule_fixture()`, `advance_teams()` (replacing `notify_next_match_teams`), and `handle_withdrawal()`.

### 2.4 Final Persistence Cleanup
- **Objective:** Eliminate direct `$wpdb` calls in the domain layer.
- **Action:** Migrate `update_result_tie` and `update_result_database` logic into `Fixture_Result_Manager` and `Fixture_Repository`.
- **Action:** Migrate the reporting logic from `report_result` into `Result_Reporting_Service`.

### 2.5 Template Refactoring
- **Objective:** Decouple templates from `Racketmanager_Match` properties.
- **Action:** Update templates to accept `Fixture_Details_DTO` or `Fixture` domain objects, ensuring they use getter methods rather than raw public properties.

Once these steps are completed, all remaining usages of get_match() can be replaced with Fixture_Repository::find_by_id(), and the Racketmanager_Match class can be safely marked as @deprecated.
