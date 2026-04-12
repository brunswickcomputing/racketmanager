# Modern DDD Architecture for Shortcodes and Fixture Components

This document outlines the ideal architecture for handling shortcodes and UI components (like Fixtures) in a modern Domain-Driven Design (DDD) environment, specifically within a WordPress context. This design is independent of any existing legacy patterns.

## 1. Core Principles: Hexagonal Architecture (Ports & Adapters)

Shortcodes and AJAX entry points are treated strictly as **Adapters** in the **Infrastructure Layer**. The "Domain" and "Application" logic must remain agnostic of the delivery mechanism (WordPress).

### Architectural Layers

*   **Infrastructure Layer (Adapters):** Contains the Shortcode classes and AJAX Handlers. They parse inputs into type-safe Commands or Queries.
*   **Application Layer (Use Cases):** Contains Command/Query Handlers. They coordinate the domain logic and return **Read Models (DTOs)**.
*   **Domain Layer (Core):** Contains pure business logic, Entities (Aggregates), and Value Objects (e.g., `Fixture`, `FixtureId`, `FixtureStatus`).

---

## 2. Ideal Solution for the 'Fixture' Shortcode

The term **Fixture** replaces the legacy term "Match" to align with the business language.

### Step-by-Step Workflow

1.  **Entry:** WordPress triggers `[fixture id="123" view="card"]`.
2.  **Mapping:** The `Fixture_Shortcode_Adapter` parses attributes and creates a `GetFixtureDetailsQuery`.
3.  **Dispatch:** The adapter dispatches the query to a **Query Bus**.
4.  **Retrieval:** The `GetFixtureDetailsHandler` fetches the `Fixture` aggregate from the `FixtureRepository`.
5.  **Read Model:** The handler maps the complex entity into a flat `FixtureDetailsReadModel` (DTO) and returns it.
6.  **Rendering:** The adapter passes the DTO to a **Presenter/Renderer** to generate the final HTML.

### Comparison: Legacy vs. Ideal

| Concept | Legacy (Match) | Ideal (Fixture) |
| :--- | :--- | :--- |
| **Object** | `Racketmanager_Match` (Active Record) | `Fixture` (Domain Entity/Aggregate) |
| **Data Fetching** | `get_match($id)` (Global Function) | `FixtureRepository::get(FixtureId $id)` |
| **Shortcode Logic** | `Shortcodes_Match::show_match_detail` | `FixtureShortcodeAdapter` (Thin mapping) |
| **UI Data** | Raw properties in templates | Type-safe `FixtureDetailsReadModel` (DTO) |

---

## 3. Reusable UI Components (The Fixture Header)

To handle reusable sections that require independent refreshing (via AJAX), we utilize a **Component Pattern**.

### The Header Read Model
The Header is defined by its own specific DTO, allowing it to be used in isolation from the full fixture detail.

```php
class FixtureHeaderReadModel {
    public string $home_team_name;
    public string $away_team_name;
    public string $score_display;
    public string $formatted_date;
    public string $status_label;
}
```

### Composition and AJAX Refresh

*   **Composition:** The `FixtureShortcodeAdapter` can render the header as part of a full page by calling a `FixtureHeaderPresenter`.
*   **AJAX Refresh:** When a result is updated via an AJAX call:
    1.  The `UpdateFixtureResultCommand` is executed.
    2.  The AJAX Handler dispatches a `GetFixtureHeaderQuery`.
    3.  It receives a fresh `FixtureHeaderReadModel`.
    4.  It returns only the HTML fragment for the header (via the `FixtureHeaderPresenter`) in the JSON response.

### Key Benefits
*   **Single Source of Truth:** The header's display logic exists in exactly one place.
*   **Logic-less Views:** All calculations (like scores or date formatting) are done in the Application Handler, not in the template.
*   **Performance:** AJAX calls can return small HTML fragments rather than reloading full pages.
