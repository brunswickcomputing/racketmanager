### DDD Audit Report: Racketmanager Plugin

I have examined the Racketmanager plugin's codebase against Domain-Driven Design (DDD) principles. The project is currently in a transitional state, moving from a legacy "Active Record / Transaction Script" pattern towards a more structured "Layered Architecture."

#### 1. Findings

**A. The "God Object" Problem (Legacy Domain Layer)**
The core entities—`Racketmanager_Match`, `League`, `Event`, and `Competition`—are currently "God Objects." 
*   **Massive File Sizes:** `League` and `Racketmanager_Match` exceed 3,500 lines each.
*   **Mixed Concerns:** They handle domain logic, direct SQL queries (`$wpdb`), UI formatting, and infrastructure tasks (like sending emails) all within the same class.
*   **Anemic vs. Bloated:** While they have many methods, much of the logic is procedural. Conversely, newer entities like `Fixture` are currently "Anemic," acting only as data buckets with getters and setters.

**B. Emerging Service and Repository Layers (Modern DDD)**
There is a clear effort to implement DDD in newer modules:
*   **Services:** `League_Service` and `Tournament_Service` correctly encapsulate application logic and orchestrate domain objects.
*   **Repositories:** `League_Repository`, `Fixture_Repository`, and others are present, which is a key DDD pattern for isolating persistence logic.
*   **DTOs:** The use of `Tournament_Overview_DTO` and similar classes shows a good separation between internal domain models and the data needed by the UI or external callers.

**C. Ubiquitous Language & Bounded Contexts**
*   **Ambiguity:** There is some overlap between `Racketmanager_Match` and `Fixture`. In DDD, these should either be the same concept or clearly defined within different bounded contexts (e.g., `Scheduling` vs. `Results Management`).
*   **Aggregates:** There are no clearly defined Aggregate Roots. For example, does a `League` own its `Matches`, or are they independent? Currently, they are loosely coupled via IDs, which leads to consistency management being spread across services.

**D. Infrastructure Leakage**
*   **WordPress Coupling:** The domain layer is heavily coupled with WordPress-specific globals (`$wpdb`) and functions (`get_option`, `__`). This makes unit testing the domain logic difficult without a full WordPress environment.

---

#### 2. Proposed Next Steps

**Step 1: Define Bounded Contexts**
Explicitly define the boundaries for modules like `Leagues`, `Tournaments`, `Finances`, and `Clubs`. This will help decide where logic belongs and prevent "leaky" dependencies.

**Step 2: Refactor God Objects into Aggregates**
*   Break down `League.php` and `Racketmanager_Match.php`.
*   Extract "Value Objects" for complex types (e.g., a `Score` object instead of multiple float properties).
*   Move side effects (notifications, email) out of the Entities and into Domain Events or Application Services.

**Step 3: Strengthen the Repository Pattern**
*   Shift all `$wpdb` calls from Entities/Services into the Repositories.
*   Ensure Entities are hydrated from Repositories and saved back through them, rather than entities having their own `update()` or `save()` methods.

**Step 4: Decouple Domain from Infrastructure**
*   Introduce interfaces for infrastructure concerns (like a `NotificationInterface`). The Domain layer should depend on the interface, while the implementation (WordPress mail) lives in an Infrastructure layer.

**Step 5: Consolidate Duplicate Concepts**
*   Resolve the relationship between `Fixture` and `Racketmanager_Match`. Ideally, one should represent the "Scheduled" state and the other the "Result" state, or they should be merged into a single `Match` entity with a clear lifecycle.
