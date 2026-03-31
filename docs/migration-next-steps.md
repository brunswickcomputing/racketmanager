### Analysis of `Racketmanager_Match` Usage

The legacy `Racketmanager_Match` class is a "God Object" of over 3,400 lines that originally handled domain logic, scoring, orchestration, and persistence. While a significant portion of its orchestration logic has already been migrated to modern services (like `Fixture_Result_Manager` and `Notification_Service`), the class is still directly used in several key areas of the codebase:

1.  **Entry Points:**
    *   `functions.php`: The global `get_match()` helper is the primary factory, directly instantiating `Racketmanager_Match`.
    *   `Admin_Import::import_fixtures()`: Directly instantiates and uses `Racketmanager_Match` to add new fixtures.
    *   `League::get_matches()`: This is a major legacy method that queries the database and returns an array of `Racketmanager_Match` objects. It is used extensively for front-end displays and standing calculations.

2.  **Domain & Persistence:**
    *   `Results_Checker`: Has a public property `$match` typed as `Racketmanager_Match`.
    *   `Racketmanager_Match.php`: Still contains all CRUD logic (`add()`, `update()`, `delete()`, `update_legs()`) and low-level result persistence (`update_result_tie()`, `set_confirmed()`).

3.  **Service & Documentation References:**
    *   References remain in `Results_Checker.php`, `Scoring_Context.php`, `Standings_Service.php`, and `League.php`.
    *   Extensive documentation (`docs/migration-next-steps.md`, `docs/match-migration-plan.md`) tracks the ongoing effort to move away from this class.

---

### Proposed Migration Plan

This plan builds upon the existing `docs/match-migration-plan.md` (specifically Phases 7 and 8) to complete the transition to the modern `Fixture` domain model.

#### Phase 1: Repository & Factory Transition (In Progress)
The goal of stopping new instances of `Racketmanager_Match` from being created at the primary entry points is partially achieved.
*   **Update `get_match()` Helper:** In Progress. The global helper in `functions.php` still returns `Racketmanager_Match`.
*   **Introduce `Fixture_Repository::find()`:** Done. Standardized retrieval by ID through the repository.
*   **Update `League::get_matches()`:** In Progress. Still returns an array of `Racketmanager_Match` objects.

#### Phase 2: Refactor Legacy CRUD & Imports (Completed)
Standardized persistence logic and decoupled it from the domain class.
*   **Migrate CRUD to `Fixture_Repository`:** Done. `save()`, `delete()`, and `find()` now handle domain object persistence.
*   **Orchestration in Services:** Done. Business logic like updating legs or applying penalties is now orchestrated through `Fixture_Result_Manager` or `Fixture_Service`.
*   **Refactor `Admin_Import::import_fixtures()`:** Done. Updated to use `Fixture_Service::create_fixture()` instead of direct instantiation.
*   **Update `Results_Checker`:** Done. `$match` property is now a `Fixture` object.

#### Phase 3: Result & Metadata Persistence (In Progress)
Finalize the decoupling of result-related persistence.
*   **Migrate Result Persistence:** Move remaining logic like `update_result_tie()` and `update_result_database()` to `Fixture_Repository` or a specialized `Result_Repository`. Note: Several methods (e.g., `set_confirmed()`, `reset_result()`) have already been removed from the legacy class during the migration.
*   **Migrate Result Checks:** Move remaining methods like `delete_result_check()` and `delete_results_report()` to a new repository (e.g., `Results_Checker_Repository`).
*   **Update `League` logic:** Refactor `update_league_with_result()` and other remaining orchestration in the `League` class to use modern services.

#### Phase 4: Final Deprecation & Cleanup
*   **Mark as `@deprecated`:** Add `@deprecated` tags to all remaining methods in `Racketmanager_Match` once they have been migrated.
*   **Cleanup Documentation:** Update the migration docs (`docs/match-migration-plan.md`) to mark these final steps as completed.
*   **Remove Class:** Once all external references (including those in templates) are gone, the `Racketmanager_Match.php` file can be safely removed.

### Summary of Key Files to Refactor
*   `functions.php`: `get_match()`
*   `src/php/Admin/Admin_Import.php`: `import_fixtures()`
*   `src/php/Domain/Competition/League.php`: `get_matches()`
*   `src/php/Domain/Results_Checker.php`: `$match` property
*   `src/php/Domain/Racketmanager_Match.php`: Remaining CRUD and persistence methods.