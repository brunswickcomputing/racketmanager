### Analysis and Plan for Next Steps: Move Away from Match Class Map

Following the strategy outlined in `move-away-from-match-class-map.md`, specifically **Section 8: Best minimal version**, I have analyzed the current project structure and defined a phased plan to migrate from the monolithic `Racketmanager_Match` class towards a more granular, domain-driven architecture.

#### Current State Analysis
- **`Racketmanager_Match`**: Currently acts as a "God object" (3494 lines), handling domain logic (results, scoring), persistence (CRUD), and orchestration (notifications, progression).
- **Domain Classes**: Classes like `Fixture`, `Result`, and `Rubber` exist but are often tightly coupled to database logic or are not yet using specialized value objects (like `Set_Score`).
- **Services**: `Result_Service`, `Fixture_Service`, and `Championship_Manager` already exist but need alignment with the new domain models to handle orchestration.
- **Repositories**: `Fixture_Repository`, `Result_Repository`, and `Rubber_Repository` are present but might need updates to support refined domain models.

#### Proposed Plan (Based on Minimal Version)

The plan focuses on the "Most important migration path" (Section 9) and "Phase" recommendations (Section 6) from the document.

##### Phase 1: Core Domain Refinement (Structural Foundations) - [COMPLETE]
The goal is to introduce missing abstractions and refine existing core models.

1.  **Create `Set_Score` Value Object**: [DONE]
   - Location: `src/php/Domain/Scoring/Set_Score.php`
   - Purpose: Encapsulate home/away games and tiebreak scores.
   - Note: Refined to handle `null` for non-played sets and 1-based indexing for template compatibility.
2.  **Introduce `Entrant` Abstractions**: [DONE]
   - Location: `src/php/Domain/Entrant/`
   - Actions: Create `Entrant` interface, `Team_Entrant`, and `Player_Entrant` implementations to abstract "who" is competing.
3.  **Introduce `Stage` Domain Model**: [DONE]
   - Location: `src/php/Domain/Competition/Stage.php`
   - Purpose: Abstract "divisions," "draws," and "brackets" into a single concept.
4.  **Refine `Result` and `Rubber`**: [IN PROGRESS]
   - Update `Result` to use `Set_Score` objects instead of raw arrays. [DONE]
   - Ensure `Rubber` correctly references the new `Result` model. [TO DO]
   - Note: `Result_Factory` and `Result_Calculator` updated to support `Set_Score` and 1-based indexing.

##### Phase 2: Orchestration & Services (Functional Extraction) - [IN PROGRESS]
The goal is to move complex logic out of `Racketmanager_Match` into specialized services.

1.  **Create `Fixture_Result_Manager` Service**: [IN PROGRESS]
   - Location: `src/php/Services/Fixture/Fixture_Result_Manager.php`
   - Actions: Migrate `update_result()`, `confirm_result()`, and validation logic from `Racketmanager_Match`.
   - Status: `handle_single_result_update()` implemented. Logic from `handle_result_update()` has been migrated.
2.  **Create `Standings_Service`**: [SKELETON CREATED]
   - Location: `src/php/Services/Standings/Standings_Service.php`
   - Actions: Centralize league table calculation logic, currently scattered in `League_Service` or `Racketmanager_Match`.
3.  **Align `Knockout_Progression_Service`**: [IN PROGRESS]
   - Location: `src/php/Services/Competition/Knockout_Progression_Service.php`
   - Actions: Ensure `Championship_Manager` or a new progression service uses the `Stage` and `Fixture` domain models for advancing winners.
   - Status: `progress_winner()` implemented by delegating to `Championship_Manager::proceed()`. Integrated into `Fixture_Result_Manager::update_result()`. Refined to use `Stage` and `Championship` domain models instead of legacy `get_league()`.
4.  **Refine `Set_Score` Value Object**: [DONE]
   - Status: Implemented `ArrayAccess` to maintain compatibility with legacy templates (`round-draw.php`, etc.) while migrating to object-oriented domain models.
   - Verification: Added `Set_Score_Test` to verify `ArrayAccess` aliases, winning logic, and immutability. Verified `Result_Factory_Test` correctly handles the new model.

##### Phase 3: Repository & Persistence Cleanup
Decouple domain objects from the database.

1.  **Update Repositories**:
   - Ensure `Fixture_Repository`, `Result_Repository`, and `Rubber_Repository` handle the mapping between the refined domain objects and the database, removing persistence methods (like `add()`, `update()`) from the Domain classes themselves.

#### Recommended Implementation Steps
1.  **Step 1 (Immediate)**: Create new Domain directories and skeleton classes for `Entrant`, `Stage`, and `Set_Score`. [DONE]
2.  **Step 2**: Refactor `Racketmanager_Match::update_result()` by extracting its core logic into the new `Fixture_Result_Manager` service. [COMPLETE]
   - `Racketmanager_Match::handle_result_update()` has been removed.
   - `Fixture_Result_Manager::handle_single_result_update()` has been created to encapsulate this logic.
   - External callers (`Ajax_Match::ajax_update_match_result()`, `League::withdraw_team()`, `League::update_match_results()`) have been migrated to use `Fixture_Result_Manager` and `Result_Service` directly.
   - `Racketmanager_Match::update_result()` is currently kept only for internal use by `handle_team_result_update()`.
3.  **Step 3**: Introduce `Set_Score` into the `Result` domain model and update `Result_Calculator` to produce these objects. [DONE]
   - Status: Update `Result_Factory` and `Result_Calculator` to support `Set_Score` and 1-based indexing.
4.  **Step 4**: Extract team match result processing (league-based results with multiple rubbers) into `Fixture_Result_Manager`. [IN PROGRESS]
   - This involves migrating `Racketmanager_Match::handle_team_result_update()`.
5.  **Step 5**: Gradually deprecate `Racketmanager_Match` methods in favor of the new domain-service-repository pattern. [IN PROGRESS]

This approach follows the "minimal version" to avoid over-engineering while providing a clear path away from the overloaded legacy match class.