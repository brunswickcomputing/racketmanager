# RacketManager Match Migration Plan

This document tracks the progress and remaining steps for the migration of the legacy `Racketmanager_Match` and `Rubber` classes to the new domain-driven architecture. This is documented in the `move-away-from-match-class-map.md` file.

## Objectives
- Extract business logic from the "Giant Match Class" into specialized services.
- Decouple domain entities from WordPress global state and database logic.
- Implement repository-based persistence for better testability and maintenance.
- Standardize data handling using modern DTOs.

## Progress Tracking

### Phase 1: Foundation and Initial Extraction (Completed)
- [x] Defined target domain model (Fixture, Rubber, Result, Set_Score).
- [x] Created `Fixture_Repository` and `Rubber_Repository`.
- [x] Implemented `Fixture_Result_Manager` for basic result orchestration.
- [x] Refactored `Ajax_Fixture` to use the new service layer for `update_fixture_result`.

### Phase 2: Team Match Logic Decomposition (Completed)
- [x] Created `Rubber_Result_Manager` to handle individual rubber updates.
- [x] Extracted rubber data extraction and validation into `Rubber_Update_Request`.
- [x] Migrated team-level score aggregation to `Result_Calculator`.
- [x] Decoupled `Fixture_Result_Manager::handle_team_result_update` from the legacy match object.

### Phase 3: Service-Oriented Refactoring (Completed)
- [x] Created `Player_Validation_Service` for eligibility and dummy player logic.
- [x] Created `Notification_Service` as a wrapper for result-related emails.
- [x] Migrated `handle_team_result_confirmation` to `Fixture_Result_Manager`.
- [x] Implemented domain-driven `winner_id` and `loser_id` determination in `Result_Calculator`.
- [x] Verified all changes with unit and integration tests.

## Remaining Steps

### Phase 4: Notification and Repository Decoupling (Completed)
- [x] Refactored `Notification_Service` to remove legacy `Racketmanager_Match` dependency.
- [x] Implemented recipient resolution (captains/secretaries) in `Notification_Service`.
- [x] Decoupled result and withdrawal notifications from global state.
- [x] Updated DI container and service instantiation for `Notification_Service`.

### Phase 5: Centralize Player and Rubber Validation (Completed)
Individual rubber validation (like WTN order and roster eligibility) is still partly handled within the legacy `Rubber` and `Racketmanager_Match` classes.
- [x] Migrate the complex `check_players()` logic from `Rubber.php` and `Racketmanager_Match.php` into the `Player_Validation_Service`.
- [x] Centralize business rules for player eligibility, ensuring they can be tested in isolation without loading large domain objects.

### Phase 6: Domain Model Refinement and Decoupling (Completed)
While the orchestration is extracted, the service layer still carries legacy "scaffolding" and the domain entities are relatively anemic.
- [x] **Standardize Response DTOs:** Replace all `stdClass` return types in `Fixture_Result_Manager` (e.g., `handle_team_result_update`) with proper Domain Response DTOs.
- [x] **Inject Configuration:** Replace all `global $racketmanager` usages in `Fixture_Result_Manager` with an injected `Settings_Service` or a configuration DTO.
- [x] **Domain File Reorganization:** Move core domain entities (`Fixture`, `Result`, `Rubber`, `Event`, `Competition`, `League`, `League_Team`) from the root `Domain/` directory into specialized sub-folders (e.g., `Domain/Fixture/`, `Domain/Result/`, `Domain/Competition/`) as per the Ideal Map.
- [x] **Constructor Simplification:** Refactor `Fixture_Result_Manager` (currently 15+ dependencies) to use a factory or a more focused set of aggregate services to reduce complexity.
- [x] **Cognitive Complexity Reduction:** Reduced cognitive complexity in `Results_Checker_Presenter` and `Notification_Service`, enforcing a maximum of 3 return statements per method.
- [x] **Service Registration Refactoring:** Reduced `Container_Bootstrap::register_services` from ~277 lines to under 150 lines (now 5 lines of core logic) through modularization.

### Phase 7: Complete Repository Transition
While most result updates now use `Fixture_Repository`, some specific updates (like penalties or tie-breaks) might still use legacy paths.
- [x] **Task:** Audit all remaining direct database calls in `Racketmanager_Match` and ensure they are migrated to the appropriate Repository (`Fixture_Repository`, `Rubber_Repository`, or `League_Team_Repository`).
- [x] **Action:** Migrate categorized `$wpdb` calls to their respective repositories:
    - [x] **CRUD/Match Persistence:** `get_instance`, `add`, `update`, `delete`, `update_legs`, `set_teams`, `set_match_date_in_db`, `set_location`.
    - [x] **Result Persistence:** `update_result_tie`, `update_result_database`, `update_match_result_status`, `reset_result`, `set_result_entered`, `set_confirmed`.
    - [x] **Related Entities:** `get_rubbers` (migrate to `Rubber_Repository`).
    - [x] **Result Checks & Reports:** `delete_results_report`, `delete_result_check`, `has_result_check` (migrate to `Results_Checker_Repository` and a new `Results_Report_Repository`).
- [x] **Goal:** Achieve full persistence ignorance in the domain model.

### Phase 8: Final Deprecation and Cleanup
Once the above logic is extracted:
- [ ] **Task:** Replace all remaining global usages of `get_match()` and `get_rubber()` with their repository equivalents (`Fixture_Repository::find_by_id()`).
- [ ] **Task:** Mark `Racketmanager_Match` and legacy `Rubber` methods as `@deprecated` or remove them if no external dependencies remain.
- [ ] **Goal:** Complete removal of the "Giant Match Class" pattern.

## Recommended Next Step
Final Deprecation and Cleanup: Replace all remaining global usages of `get_match()` and `get_rubber()` with their repository equivalents.
