### Implementation Plan: Migrating `Ajax_Fixture` to DDD Adapters

This plan outlines the conceptual steps to migrate the current inheritance-based AJAX handlers to a decoupled, adapter-based DDD architecture without modifying existing code.

#### 1. Phase 1: Infrastructure Decoupling (Composition)
Before moving the methods, replace the "magic" provided by the `Ajax` base class with explicit composition.
*   **Create a `Security_Service`:** Extract `check_security_token()` logic from `Ajax.php` into a standalone service. The new AJAX adapters will call this service instead of inheriting it.
*   **Standardize Response Handlers:** Create a `Json_Response_Factory` to handle the standard WordPress `wp_send_json_success/error` calls with consistent error formatting.

#### 2. Phase 2: Application Layer Extraction
Identify the business logic currently trapped inside `Ajax_Fixture` and move it to the Application Layer.
*   **Expand `Fixture_Result_Manager`:** Move the orchestration logic from `update_fixture_result` and `update_team_match` into this service.
*   **Introduce `Fixture_Maintenance_Service`:** Move operations like `set_fixture_status`, `set_fixture_date`, `switch_home_away`, and `reset_fixture_result` here.
*   **The Goal:** The AJAX method should contain no `if/else` logic regarding business rules; it should only call a service method.

#### 3. Phase 3: Contract Definition (DTOs)
Define the "language" used to communicate between the AJAX entry point and the Services.
*   **Request DTOs (Commands):** For every POST action, create a DTO (e.g., `Change_Fixture_Status_Command`, `Reschedule_Fixture_Command`).
*   **Read Model DTOs (Queries):** Define what the UI needs back (e.g., `Fixture_Header_Read_Model`). This prevents the AJAX handler from returning raw entities or arrays.

#### 4. Phase 4: UI Presentation (Presenters)
Move HTML fragment generation out of global functions and into the Presenter layer.
*   **Enhance `Fixture_Presenter`:** Instead of the AJAX handler calling global functions like `match_header()`, the `Fixture_Presenter` should take a `Fixture_Header_Read_Model` and return the HTML.
*   **Logic-less Fragments:** Ensure the Presenter handles all the `mysql2date` and status-label logic, keeping the AJAX class clean.

#### 5. Phase 5: Creating the First Adapter (The "Parallel" approach)
Instead of refactoring `Ajax_Fixture.php` in place, create a new `Fixture_Ajax_Adapter.php` in the `Infrastructure` layer.
1.  **Select a "Pilot" Method:** Start with a high-impact method like `update_fixture_result`.
2.  **Implementation Pattern:**
    *   **Infrastructure:** Security check (via Service).
    *   **Infrastructure:** Map `$_POST` to `Update_Fixture_Command` DTO.
    *   **Application:** Call `Fixture_Result_Manager->execute(command)`.
    *   **Application:** Receive `Read_Model` DTO.
    *   **Presentation:** Map `Read_Model` to HTML/JSON via `Fixture_Presenter`.
    *   **Infrastructure:** Send JSON response.
3.  **Registration:** Register the new AJAX action in WordPress to point to the new Adapter instead of the old `Ajax_Fixture` class.

#### Summary of Mapping for `Ajax_Fixture`
| Old Method | New Application Service | Input DTO | Output DTO / Presenter |
| :--- | :--- | :--- | :--- |
| `set_fixture_status` | `Fixture_Maintenance_Service` | `Update_Status_Command` | `Status_Read_Model` |
| `set_fixture_date` | `Fixture_Maintenance_Service` | `Reschedule_Command` | `Date_Read_Model` |
| `update_fixture_header` | `Fixture_Query_Service` | `Get_Header_Query` | `Fixture_Header_Presenter` |
| `reset_fixture_result` | `Fixture_Result_Manager` | `Reset_Result_Command` | `Fixture_Presenter` |