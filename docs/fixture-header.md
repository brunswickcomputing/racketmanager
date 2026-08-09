To handle a reusable and AJAX-refreshable **Fixture Header** within a DDD architecture, we must treat the Header as a **UI Component** (or "Fragment") that is decoupled from any specific shortcode.

### 1. The Component Pattern (UI Adapter)
The "Header" should be its own sub-component with a dedicated **Read Model** and **View**. This ensures that the logic for rendering the header (team names, scores, date) is never duplicated.

#### A. The Header Read Model
Instead of a single massive `FixtureDetailsReadModel`, we use composition:
```php
class FixtureHeaderReadModel {
    public string $home_team_name;
    public string $away_team_name;
    public string $score_display;
    public string $formatted_date;
    public string $status_label;
    // No "rubbers" or "players" here; just header data.
}
```

#### B. The Header Component/Fragment
We create a **`FixtureHeaderPresenter`** (Infrastructure/UI) that is responsible for rendering the HTML for just the header.
*   **Source 1 (Shortcode):** The `FixtureShortcodeAdapter` calls the `FixtureHeaderPresenter` as part of its full page render.
*   **Source 2 (AJAX):** The `FixtureAjaxAdapter` calls the `FixtureHeaderPresenter` after a result update to return only the updated HTML fragment.

---

### 2. The Ideal Workflow for AJAX Refresh

When a user submits a result (e.g., via a modal):

1.  **Command Execution:** The `FixtureAjaxAdapter` receives the score and dispatches an `UpdateFixtureResultCommand`.
2.  **Domain Update:** The `Fixture` Aggregate updates its state, calculates the new score, and persists via the `FixtureRepository`.
3.  **Fragment Retrieval:** Instead of the AJAX handler redirecting or returning "Success," it dispatches a `GetFixtureHeaderQuery`.
4.  **Read Model Return:** The handler returns a fresh `FixtureHeaderReadModel`.
5.  **Targeted Rendering:** The `FixtureAjaxAdapter` passes this Read Model to the `FixtureHeaderPresenter`.
6.  **JSON Response:** The AJAX call returns:
    ```json
    {
      "success": true,
      "fragments": {
        ".fixture-header-123": "<div class='header'>...new html...</div>"
      }
    }
    ```

---

### 3. Reusability Across Shortcodes
Because the `FixtureHeaderReadModel` and its corresponding template are isolated:
*   **Fixture Detail Shortcode:** Includes the header at the top.
*   **Daily Fixtures Shortcode:** Can loop through multiple fixtures and render only the `FixtureHeader` component for each row.
*   **Club Dashboard Shortcode:** Reuses the same header component to show upcoming fixtures.

### 4. Architectural Summary

| Layer | Component | Responsibility |
| :--- | :--- | :--- |
| **Infrastructure (UI)** | `FixtureHeaderPresenter` | Takes a `FixtureHeaderReadModel` and returns HTML. |
| **Infrastructure (Web)** | `FixtureShortcodeAdapter` | Orchestrates full page display; uses the Presenter. |
| **Infrastructure (Web)** | `FixtureAjaxAdapter` | Orchestrates result updates; returns the Presenter's HTML as a fragment. |
| **Application** | `GetFixtureHeaderQuery` | Specific query to fetch only header-related data. |
| **Application** | `FixtureHeaderReadModel` | The "contract" between the logic and the UI component. |

### Key Benefit
By separating the **Header** into its own "Query" and "Presenter," you solve the AJAX refresh problem without reloading the entire page or duplicating template logic. The "source of truth" for what a header looks like remains in exactly one place.