# DDD Refactoring Suggestions - August 2026

## Overview
Following an analysis of the current RacketManager codebase, this document provides specific suggestions for continuing the migration towards a robust Domain-Driven Design (DDD) architecture. While significant progress has been made (e.g., `Results_Checker`), several core areas still exhibit legacy patterns that hinder testability and maintainability.

## 1. Domain Entities & DTOs

### Current State
Core entities like `Fixture` and `Player` still rely on generic `object` injection in their constructors.
- **Fixture.php**: The constructor manually maps ~40 properties from a raw object, handles serialization, and triggers internal state calculations (`set_status_flags`).
- **Player.php**: Contains large methods (`get_matches`, `get_stats`) that perform complex data retrieval and calculations.

### Suggestions
- **Typed DTOs for Hydration**: Every core entity should have a corresponding `Hydration_DTO`. The Repository should be responsible for populating this DTO, and the Entity constructor should accept only this DTO.
- **Factory Pattern**: For complex entities like `Fixture`, use a `Fixture_Factory` to handle the transition from raw DB/WordPress objects to Domain Entities.
- **Move Logic to Services**: 
    - Move `Player::get_matches()` and `Player::get_stats()` into a `Player_Query_Service` or `Player_Stats_Service`. Entities should ideally be "thin" and focus on maintaining invariants, not fetching data.
    - Move `Fixture::set_status_flags()` logic into a `Fixture_Status_Policy` or similar domain service to decouple state logic from the data object.

## 2. Repository Layer

### Current State
`Player_Repository::find()` is currently a "God Method" for hydration. It fetches user meta, handles legacy data formats, and instantiates the `Player` entity directly.

### Suggestions
- **DTO-based Persistence**: Repositories should return DTOs by default. If an Entity is needed, it should be hydrated via a Factory using that DTO.
- **Separate Hydration from Retrieval**: Create dedicated Hydrators (e.g., `Player_Hydrator`) that handle the WordPress-specific `get_user_meta` calls. This keeps the Repository focused on query orchestration.
- **Standardize Finders**: Ensure all Repositories implement consistent interfaces (as seen in `Player_Repository_Interface`) but return typed DTOs instead of `?object`.

## 3. Presentation Layer (Presenters & View Models)

### Current State
`Fixture_Presenter` is handling a wide range of responsibilities, from mapping DTOs to Read Models to actually rendering PHP templates.

### Suggestions
- **Decompose Fixture_Presenter**: Break this down into more focused presenters:
    - `Fixture_Status_Presenter`: Specifically for status-related UI logic.
    - `Fixture_Header_Presenter`: For the match header display.
- **Strict View Models**: Ensure View Models are strictly logic-less. Any `mysql2date` or URL generation must happen in the Presenter.
- **Decouple Rendering**: The Presenter should return a View Model. A separate `View_Renderer` or the Controller should handle passing that View Model to the template. Presenters shouldn't need a `render()` method.

## 4. Proposed Folder Structure Refinement
Based on the `move-away-from-match-class-map.md`, we should push towards grouping by **Sub-Domain** rather than just layer:

```
src/php/
    Domain/
        Competition/        (Aggregates, Entities, Policies)
        Entrant/            (Player, Team, Entrant Interface)
        Fixture/            (Fixture, Rubber, Fixture_Status_Policy)
        Result/             (Result, Scoring_Context)
    Application/
        Services/           (Fixture_Result_Manager, Player_Stats_Service)
        DTOs/               (Hydration and Request DTOs)
    Infrastructure/
        Persistence/        (Repositories, Hydrators)
        Wordpress/          (Shortcodes, Hooks, Admin)
    Presentation/
        Presenters/         (Fixture_Presenter, Player_Presenter)
        ViewModels/         (Read Models)
```

## Summary of Priorities
1.  **Refactor Fixture Constructor**: Introduce `Fixture_Hydration_DTO` and move mapping logic out of the Entity.
2.  **Thin out Player Entity**: Extract query/stats logic into `Application/Services`.
3.  **Standardize Repository Returns**: Move towards returning DTOs/Entities instead of raw WordPress `stdClass` objects.
