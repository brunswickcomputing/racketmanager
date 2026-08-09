To design the ideal, modern DDD solution for the `fixture` (formerly `match`) shortcode—completely independent of legacy patterns—we must move away from string-based templates and global state. The shortcode should function as a **Thin Adapter** in the **Infrastructure Layer**, delegating all logic to the **Application Layer**.

### 1. The Architecture: "The Hexagonal Approach"
The ideal solution treats the WordPress shortcode as just one "driver" of the system.

#### A. Infrastructure Layer (The Adapter)
*   **`Fixture_Shortcode_Adapter`**: Implements the `fixture` shortcode.
*   **Responsibility:**
    1.  Parse `$atts` into a type-safe **Query Object**: `GetFixtureDetailsQuery`.
    2.  Dispatch the query to the **Query Bus**.
    3.  Receive a **Read Model (DTO)** from the handler.
    4.  Pass the DTO to a **Presenter/Renderer** (e.g., Twig or a Component-based view).

#### B. Application Layer (The Use Case)
*   **`GetFixtureDetailsQuery`**: A simple immutable class containing `fixture_id` and optional `display_options`.
*   **`GetFixtureDetailsHandler`**: The service that executes the logic.
*   **Responsibility:**
    1.  Request the **Fixture Aggregate** from the `FixtureRepository`.
    2.  Coordinate with the `ClubRepository` or `LeagueRepository` if supplementary data is needed.
    3.  Construct and return a `FixtureDetailsReadModel` (DTO).

#### C. Domain Layer (The Core)
*   **`Fixture` (Aggregate Root)**: Contains the pure business rules (e.g., `isResultOverdue()`, `canUpdateResult(User $user)`).
*   **`FixtureId` (Value Object)**: Type-safe identifier.
*   **`FixtureStatus` (Value Object)**: Encapsulates statuses like `Scheduled`, `Completed`, or `Postponed`.

---

### 2. The Ideal Workflow (Step-by-Step)

1.  **Entry:** WordPress encounters `[fixture id="123" view="card"]`.
2.  **Mapping:** The `Fixture_Shortcode_Adapter` validates the ID and creates:
    ```php
    $query = new GetFixtureDetailsQuery(fixtureId: 123, viewMode: 'card');
    ```
3.  **Dispatch:** The adapter calls `$queryBus->ask($query)`.
4.  **Retrieval:** The `GetFixtureDetailsHandler` fetches the `Fixture` entity.
    *   *Note:* It ignores "multiple legs" for now, returning exactly one `FixtureDetailsReadModel`.
5.  **Read Model Construction:** The handler maps the complex `Fixture` aggregate into a flat, read-only `FixtureDetailsReadModel`. This read model contains pre-calculated strings (e.g., `formatted_score`, `display_date`) so the view remains logic-less.
6.  **Rendering:** The adapter receives the Read Model and triggers the UI:
    ```php
    return $this->renderer->render('fixtures/fixture-card.html.twig', [
        'fixture' => $readModel
    ]);
    ```

---

### 3. Comparison of Concepts

| Concept | Legacy (Match) | Ideal (Fixture) |
| :--- | :--- | :--- |
| **Object** | `Racketmanager_Match` (Active Record) | `Fixture` (Domain Entity/Aggregate) |
| **Data Fetching** | `get_match($id)` (Global Function) | `FixtureRepository::get(FixtureId $id)` |
| **Shortcode Logic** | `Shortcodes_Match::show_match_detail` | `FixtureShortcodeAdapter` (Thin mapping) |
| **UI Data** | Raw properties/objects in templates | Type-safe `FixtureDetailsReadModel` (DTO) |
| **Naming** | match, match-detail, match_id | fixture, fixture-detail, fixture_id |

### 4. Key Advantages of this Design
*   **Domain Clarity:** Using the term `fixture` aligns the code with the business language (as per `move-away-from-match.md`).
*   **Testability:** You can unit test the `GetFixtureDetailsHandler` without loading WordPress or a database by mocking the `FixtureRepository`.
*   **Resilience:** If the UI requirements change (e.g., adding a React frontend), the `GetFixtureDetailsHandler` remains exactly the same; only a new "REST Adapter" is added.
*   **Immutability:** By returning a `ReadModel` instead of the `Fixture` entity, you guarantee that the shortcode cannot accidentally modify the state of a fixture.