### Domain-Driven Design Analysis: RacketManager Plugin (August 2026)

This document evaluates the compliance of existing domain entities with Domain-Driven Design (DDD) principles and identifies areas for improvement.

---

### ✅ Entities in a Strong Position (DDD Compliant)

These objects demonstrate good separation of concerns, strict typing, and use of DTOs or Value Object patterns.

*   **`Results_Checker`**: 
    *   **Status**: High Compliance.
    *   **Why**: It uses a dedicated DTO (`Results_Checker_Data`) for hydration in its constructor. It is a focused entity with a clear, single responsibility in the domain.
*   **`Set_Score` & `Scoring_Context`**:
    *   **Status**: Excellent (Value Objects).
    *   **Why**: These are immutable (using `readonly` or throwing exceptions on mutation), strictly typed, and represent core domain logic (scoring) without being tied to database rows. They are highly testable.
*   **`Entrant` (Interface), `Player_Entrant`, & `Team_Entrant`**:
    *   **Status**: High Compliance.
    *   **Why**: They use the Interface pattern to allow the domain to treat players and teams polymorphically. They are essentially Value Objects that simplify how the domain handles "who is playing."
*   **Domain DTOs** (e.g., `Fixture_Details_DTO`, `Team_Entry_DTO`):
    *   **Status**: High Compliance.
    *   **Why**: These provide a contract between the Infrastructure (persistence) and Domain layers, preventing raw database results from leaking into domain logic.

---

### ⚠️ Entities in the Worst Position (Legacy/Non-Compliant)

These entities are currently "God Objects" that violate the Single Responsibility Principle and hinder testability due to generic object injection.

*   **`Fixture`**:
    *   **Position**: Worst.
    *   **Issues**: 
        *   The constructor accepts a generic `?object`, requiring manual mapping of ~40 properties.
        *   It contains its own state-calculation logic (`set_status_flags`).
        *   It handles its own "reset" and "result" logic internally, which should ideally be handled by a Domain Service (like `Fixture_Result_Manager`).
*   **`Player`**:
    *   **Position**: Poor.
    *   **Issues**: 
        *   It acts as a data fetcher. Methods like `get_matches()` and `get_stats()` contain complex query logic that should belong in a `Repository` or `Query_Service`.
        *   It includes validation-like logic (`check_results_warning`) that belongs in a Policy or Service.
*   **`Competition`**:
    *   **Position**: Poor.
    *   **Issues**: 
        *   Massive file (>1400 lines).
        *   Contains heavy persistence-related logic (`get_teams`, `get_players`, `get_matches`) which leads to "Leaky Abstractions" where the domain entity knows too much about how data is retrieved.

---

### 🚀 Suggested Improvements

To bring the legacy entities into alignment with the `Results_Checker` and `Set_Score` patterns, I suggest the following:

1.  **Extract Hydration to Factories**:
    *   Create a `Fixture_Factory` and `Player_Factory`. 
    *   The Repository should fetch data, pass it to the Factory, which returns a hydrated Entity. This removes the `__construct(?object $data)` pattern.

2.  **Move "Active" Logic to Domain Services**:
    *   **`Fixture::set_status_flags()`** → `Fixture_Status_Policy`.
    *   **`Player::get_stats()`** → `Player_Stats_Service`.
    *   **`Competition::contact_teams()`** → `Competition_Communication_Service`.

3.  **Introduce Strict Hydration DTOs**:
    *   Define a `Fixture_Hydration_DTO` that mirrors the database schema but with strict types. This makes it impossible to instantiate a `Fixture` with invalid or missing data.

4.  **Thin out the "God Classes"**:
    *   Aim to reduce `Competition.php` and `Player.php` by at least 50% by moving query logic into the `Infrastructure` layer (Repositories) and complex calculations into the `Application` layer (Services).

### Summary Table

| Entity | Position | Key Weakness | Recommendation |
| :--- | :--- | :--- | :--- |
| **Fixture** | 🔴 Worst | Generic Object Injection | Implement `Fixture_Hydration_DTO` |
| **Player** | 🔴 Poor | Data-fetching logic | Move `get_matches` to `Player_Repository` |
| **Competition**| 🟢 Good | None (Modernized 2026) | Already refactored into DDD patterns |
| **Results_Checker**| 🟢 Good | None (Modern) | Keep as a template for new entities |
| **Set_Score** | 🟢 Excellent| None (Value Object) | Use as a model for all VOs |

### 🛠 Competition Entity Refactoring (August 2026)

The `Competition` entity has been modernized following DDD principles:
*   **Hydration**: Decoupled from generic objects via `Competition_Hydration_DTO` and `Competition_Factory`.
*   **Persistence**: Database logic moved to `Competition_Repository`.
*   **Actions**: Domain behaviors like notifications moved to `Competition_Notification_Service`.
*   **Lifecycle**: Complex season resolution and lifecycle phase calculations moved to `Competition_Season_Service`.
*   **State Rules**: Complex calculation logic moved to `Competition_Policy`.
*   **Value Objects**: `seasons` and `settings` extracted into `Season_Collection` and `Competition_Settings`.
*   **Type Safety**: Competition types refactored into `Competition_Type` Enum, ensuring strict domain rules for leagues, cups, and tournaments.
