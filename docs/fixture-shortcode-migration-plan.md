# Fixture Shortcode Migration Plan (DDD)

This document outlines the plan to migrate the legacy `[match]` shortcode (currently handled by `Shortcodes_League::show_match`) to a modern Domain-Driven Design (DDD) architecture.

## 1. Architectural Strategy

The migration follows a **Hexagonal Architecture** pattern, decoupling WordPress infrastructure from core business logic.

### Layered Breakdown
| Layer | Component | Responsibility |
| :--- | :--- | :--- |
| **Infrastructure (UI)** | `Fixture_Presenter` | Maps DTOs to Read Models and renders HTML fragments (Header, Detail, Compact). |
| **Infrastructure (Web)** | `Fixture_Shortcode_Adapter` | Thin WordPress bridge. Parses attributes/query vars and dispatches Application Queries. |
| **Application** | `Get_Fixture_Details_Handler` | Use Case coordinator. Fetches the Fixture Aggregate and produces a `Fixture_Details_DTO`. |
| **Domain** | `Fixture` Aggregate | Pure business rules and state (scores, dates, status). |
| **Persistence** | `Fixture_Repository` | Data access (retrieval by ID or slug-based criteria). |

## 2. Component Design

### Reusable UI Fragments
To support multi-context display (Player pages, Team pages, League standings), the UI is broken into granular fragments:

1.  **Fixture Header**: Team names, date, and core score. AJAX-refreshable.
2.  **Fixture Detail**: Rubbers, individual scores, and specific player/team performance.
3.  **Compact Row**: Minimalistic representation for lists.

### Context-Aware Rendering
The `Fixture_Presenter` will support a "Focus" context to highlight specific entities:
*   `focus_player_id`: Highlights the player's performance within the detail view.
*   `focus_team_id`: Highlights the team's perspective.

## 3. Implementation Plan

### Phase 1: Persistence & Application (The Foundation) [COMPLETED]
1.  **Repository Enhancement**:
    *   Implement `Fixture_Repository::find_one_by_slug_criteria()` to encapsulate the complex logic currently in `Shortcodes_League` for resolving matches from URL parameters (season, teams, match day). [DONE]
2.  **Application Query**:
    *   Create `Get_Fixture_Details_Query` (DTO) and `Get_Fixture_Details_Handler`. [DONE]
    *   The handler will utilize `Fixture_Detail_Service` to generate the enriched `Fixture_Details_DTO`. [DONE]

### Phase 2: Presentation (The UI) [COMPLETED]
1.  **Presenter Refactoring**:
    *   Extend `Fixture_Presenter` to include specific rendering methods: `render_header()`, `render_detail()`. [DONE]
    *   Introduce `Read Model` objects to strip all logic from the `.php` templates. [DONE]
2.  **Template Fragmentation**:
    *   Create modular templates under `src/php/Infrastructure/Wordpress/Views/match/fragments/`. [DONE]

### Phase 3: Infrastructure (The Bridge) [COMPLETED]
1.  **New Shortcode Adapter**:
    *   Create `Fixture_Shortcode_Adapter`. [DONE]
    *   This class will handle the `[fixture]` (and eventually `[match]`) shortcode. [DONE]
    *   It parses `$atts` and `get_query_var`, calls the Handler, and uses the Presenter to return HTML. [DONE]

## 4. Constraint Compliance
*   **No Legacy Changes**: All new logic resides in new DDD-compliant classes. Legacy `Shortcodes_League` remains untouched for now.
*   **Shortcode Trigger**: The `[match]` and `[fixture]` shortcodes now use `Fixture_Shortcode_Adapter`.
*   **AJAX Consistency**: `Fixture_Ajax_Adapter` has been refactored to use the same `Fixture_Presenter` rendering flow, ensuring perfect UI parity.
*   **Test Coverage**: Comprehensive unit tests cover all layers (Repository, Service, Handler, Presenter, and Adapters). Full test suite (436 tests) is green.
