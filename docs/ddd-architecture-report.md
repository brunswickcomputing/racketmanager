# DDD Architecture Migration Report

## Overview
This report outlines the current architectural state of the RacketManager plugin and proposes a roadmap for migrating to a full Domain-Driven Design (DDD) approach. The recent refactoring of the `Results_Checker` module serves as the blueprint for this migration.

## Core Architectural Principles

1.  **Domain Entities**: Pure data objects representing core business concepts. They should use IDs for relationships (Lazy Loading) and contain only domain-specific logic.
2.  **DTOs (Data Transfer Objects)**: Typed contracts for moving data between layers (e.g., Repository to Entity).
3.  **View Models**: Logic-less objects containing pre-formatted data specifically for the presentation layer.
4.  **Presenters**: Services that map Entities to View Models, handling hydration of related entities and formatting (dates, links, translations).
5.  **Repositories**: Responsible for persistence and hydration of Entities via DTOs.
6.  **Enums**: Type-safe constants for statuses, types, and categories.

## Current State Analysis

### 1. Domain Layer
- **New Pattern (Implemented)**: `Results_Checker`.
- **Legacy Pattern**: `Fixture`, `Player`, `Team`, `League`, `Racketmanager_Match`.
    - **Issues**:
        - Constructors often take generic `?object` and map properties manually without DTOs.
        - Some entities (like `Player`) contain heavy business logic (e.g., `get_matches`, `get_stats`) that should arguably be in Services or Repositories.
        - Entites often trigger their own hydration of related objects (e.g., `Fixture` having a `set_result` that takes a `Result` object, or `Player` fetching its own competitions).

### 2. Presentation Layer
- **New Pattern (Implemented)**: `Results_Checker_Presenter` + `Results_Checker_View_Model`.
- **Legacy Pattern**: Most admin templates (e.g., `pending-results.php`, `results.php`).
    - **Issues**:
        - Templates contain significant logic: date formatting (`mysql2date`), URL construction, conditional tooltips, and even calculations (overdue time).
        - Reliance on global variables (`global $racketmanager`, `global $wpdb`).
        - Direct calls to legacy helper functions like `get_match()`.

### 3. Data Access Layer
- **Repositories**: Exist for most entities, but many still return raw objects or entities that haven't been decoupled from the underlying DB structure via DTOs.

## Proposed Next Steps

### Phase 1: Core Entity & DTO Refactoring (High Priority)
1.  **Standardize DTOs**: Create DTOs for `Fixture`, `Player`, `Team`, and `League`.
2.  **Refactor Constructors**: Update these entities to accept their respective DTOs.
3.  **Decouple Relationships**: Move away from entities holding concrete object instances of related domain objects. Use IDs and hydrate via Presenters or Services when needed.

### Phase 2: Presentation Layer Modernization
1.  **Implement Presenters**: Create presenters for common display scenarios:
    - `Fixture_Presenter`: For match lists, schedules, and result entries.
    - `Player_Presenter`: For player profiles and stats.
2.  **Logic-less Templates**: Refactor `pending-results.php` and `results.php` to use View Models. Move all `mysql2date` and URL logic into Presenters.
3.  **Introduce Enums**: Replace magic numbers in `Fixture` statuses (e.g., status flags) and `Competition` types with Enums.

### Phase 3: Service Layer Extraction
1.  **Move Business Logic**: Extract complex logic from `Player.php` (e.g., `get_career_stats`, `get_stats_teams`) into dedicated Service classes (e.g., `Player_Stats_Service`).
2.  **Orchestration**: Ensure that workflows involving multiple entities are handled by Domain Services (like `Fixture_Result_Manager`).

## Benefits
- **Testability**: Logic-less templates and pure entities are much easier to unit test.
- **Maintainability**: Centralized formatting logic in Presenters prevents duplication.
- **Performance**: Lazy loading of related entities via IDs prevents "N+1" query issues and heavy object graphs.
- **Clarity**: Type-safe DTOs and Enums provide better IDE support and reduce runtime errors.
