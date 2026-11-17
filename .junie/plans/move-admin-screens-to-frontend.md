---
sessionId: session-261117-152345-1q2t
---

# Requirements

### Overview & Goals
The objective of this initiative is to transition RacketManager administrative screens to a dedicated frontend management portal (`/manage/`) delivered **incrementally by section**. Rather than a high-risk all-at-once migration, screens will be ported in logical functional increments while preserving seamless dual-context execution with `wp-admin`.

The implementation introduces clean, modern permalinks (`/manage/:section/:view`), a standalone responsive portal app shell, contextual URL resolution, and an incremental section registry that seamlessly redirects unmigrated sections back to their existing `wp-admin` screens until their designated delivery stage is completed.

### Feasibility Analysis: `manage/xxx` vs `page=racketmanager-xxx`
Using clean two-level path permalinks (`/manage/:section/:view`) instead of query-string routing (`admin.php?page=racketmanager-xxx`) is **fully feasible, technically superior, and strongly recommended**:
- **Clean Permalinks:** WordPress rewrite rules cleanly capture two-level path segments: `^manage/([^/]+)/([^/]+)/?$` mapped to `index.php?rm_manage=1&rm_section=$matches[1]&rm_view=$matches[2]`.
- **Decoupled URL Structure:** The frontend URL scheme is decoupled from internal WordPress admin page slugs (`racketmanager-clubs`), eliminating admin query noise.
- **Controller Compatibility:** Because admin screens operate via granular view controllers (`Club_Admin_Controller`, `League_Admin_Controller`, etc.) delegating via `Completes_Admin_Requests`, controllers receive input as structured query/post arrays rather than reading raw admin page parameters.
- **Dual-Context Coexistence:** The same screen bridges and controllers can be accessed concurrently from both `/wp-admin/admin.php?page=racketmanager-...` and `/manage/:section/:view`.

### Scope
- **In Scope:**
  - Registering rewrite rules for `/manage/`, `/manage/:section`, and `/manage/:section/:view`.
  - Implementing `Portal_Section_Registry` with progressive section enablement flags and seamless 302 redirects to `wp-admin` for sections pending migration.
  - Creating a contextual URL generator (`Manage_Url_Resolver_Interface`) to construct clean permalinks in frontend portal context and admin URLs in wp-admin context.
  - Creating a dedicated frontend portal application shell (`templates/portal/layout.php`) providing responsive navigation, breadcrumbs, user status, and flash notice alerts.
  - Implementing `Portal_Page_Router` and `Portal_Screen_Presenter` to drive GET rendering and POST/PRG mutation flows.
  - Phased incremental rollout across 4 functional section increments:
    1. Foundational entities: Clubs & Competitions Dashboard.
    2. Competitive play: Leagues, Cups, and Tournaments.
    3. Operational workflows: Results verification and Players.
    4. Administration: Seasons, Finances, Settings, Import, and Documentation.
  - Migrating legacy hardcoded `admin.php?page=...` template references to `Manage_Url_Resolver`.
  - Validating all 37 PHPUnit architecture guardrails and Jest ratchet rules.
- **Out of Scope:**
  - Modifying domain business rules or database repositories.
  - Redesigning individual form layouts or view model contracts.
  - Altering public competition or tournament display shortcodes.

### User Stories
- **As a Club Administrator / League Official**, I want to manage competitions, clubs, players, and match results via a modern, clean frontend portal (`/manage/clubs`, `/manage/results`) so that I do not need to access the WordPress wp-admin dashboard.
- **As a Mobile User**, I want a responsive, full-screen management interface that works seamlessly on tablet and mobile devices without the obstructive wp-admin admin bar and menu chrome.
- **As a System Administrator**, I want an incremental, staged migration by section so that each functional area can be tested and verified independently in production without risking downtime or breaking unported workflows.
- **As a Developer**, I want clean REST-like URL routes (`/manage/:section/:view`) mapped to granular controller classes without breaking existing wp-admin screen workflows.

### Functional Requirements
- **Route Resolution & Defaults:**
  - `/manage/` -> Defaults to competitions management dashboard (`/manage/competitions`).
  - `/manage/:section` -> Routes to the default view of the specified section (e.g. `/manage/clubs` -> default clubs view).
  - `/manage/:section/:view` -> Routes directly to the specified subview (e.g. `/manage/clubs/club?club_id=12`, `/manage/tournaments/draw?tournament=5`).
- **Section Gating & Fallback:**
  - During the phased rollout, `Portal_Page_Router` consults `Portal_Section_Registry`.
  - If a section is **enabled**, it renders inside the frontend portal shell.
  - If a section is **pending migration**, the router issues a 302 redirect to its corresponding `wp-admin` URL (`admin_url('admin.php?page=racketmanager-...&view=...')`) with query parameters preserved.
- **Authentication & Capability Enforcement:**
  - Unauthenticated requests to `/manage/*` redirect to the standard WordPress login screen (`/wp-login.php?redirect_to=...`).
  - Authenticated users lacking required capabilities (e.g. `racket_manager`, `racketmanager_settings`, `import_leagues`) receive an HTTP 403 unauthorized view within the portal shell.
- **Post-Redirect-Get (PRG) & Flash Messages:**
  - Form mutations on `/manage/*` process via the respective controller and issue a 302/303 redirect back to the appropriate `/manage/:section/:view` URL with flash notices preserved.
- **Navigation:**
  - The portal shell provides primary navigation tabs reflecting currently enabled sections (and linking unported sections to wp-admin or marking them pending).
  - The active section is highlighted in the navigation bar.

### Non-Functional Requirements
- **Architecture Integrity:** Zero violations across all 37 PHPUnit architecture rules; no regression on the 8 ratchet rules; no inline template JavaScript; all templates read from typed `$vm` view models.
- **Security:** Nonce validation on all state-altering requests (`wp_verify_nonce`); strict sanitization of path parameters and query strings; explicit output escaping at rendering boundaries.
- **Performance:** No duplicate database queries or additional overhead compared to backend wp-admin execution.


# Technical Design

### Current Implementation vs Target Architecture
In earlier iterations of RacketManager, admin screens inherited from `Admin_Display`, loaded inline templates directly, and depended on query strings like `wp-admin/admin.php?page=racketmanager-clubs&view=club`.

Following the recent architectural refactoring:
- `Admin_Display` has been completely removed and forbidden by architecture rule `admin.no_admin_display_inheritance`.
- All 12 screen classes (`Admin_Club`, `Admin_League`, `Admin_Cup`, `Admin_Tournament`, `Admin_Result`, `Admin_Season`, `Admin_Player`, `Admin_Finances`, `Admin_Options`, `Admin_Import`, `Admin_Index`, `Admin_Upgrade`) are `final readonly` bridges using `Completes_Admin_Requests`.
- Granular `*_Admin_Controller` classes handle mutations and queries, returning typed view models (rendered via `Admin_Screen_Presenter` and `Php_View_Renderer`) or PRG redirect arrays (handled via `Admin_Action_Adapter`).
- `Admin_Page_Router` acts as the single entry point for `wp-admin/admin.php`.

The target architecture introduces `Portal_Page_Router`, which reuses the exact same screen bridges and controllers while serving a dedicated frontend app shell, clean two-level path permalinks (`/manage/:section/:view`), and an incremental section registry that seamlessly redirects unmigrated sections back to `wp-admin`.

### Key Decisions
1. **Two-Level Path Permalinks (`/manage/:section/:view`):**
   - *Chosen Approach:* Register rewrite rules for `manage/([^/]+)/([^/]+)/?$` capturing section and subview as path segments, while entity identifiers and context parameters remain standard query parameters (`?club_id=12`).
   - *Rationale:* Provides clean, readable permalinks, eliminates legacy `racketmanager-` prefixes from public URLs, and avoids the combinatorial explosion of rewrite rules required for dozens of subview parameter combinations.
2. **Dedicated Standalone Portal App Shell:**
   - *Chosen Approach:* Render a dedicated, standalone frontend application shell (`templates/portal/layout.php`) isolated from WordPress theme header/footer loops.
   - *Rationale:* Eliminates theme CSS/JS conflicts, provides a consistent administrative UI, and enables full-width responsive layouts tailored for tournament and league management.
3. **Contextual URL Resolver (`Manage_Url_Resolver_Interface`):**
   - *Chosen Approach:* Centralize URL generation behind `Manage_Url_Resolver_Interface`, with implementations generating `/manage/:section/:view` in portal mode and `admin.php?page=racketmanager-...` in admin mode.
   - *Rationale:* Allows `Admin_Redirect_Url_Builder` and templates to remain completely agnostic of whether they are executing in the backend dashboard or the frontend portal.
4. **Incremental Delivery by Section with Seamless wp-admin Fallback:**
   - *Chosen Approach:* Use `Portal_Section_Registry` to track migration readiness per section. Ported sections execute in the portal shell; unmigrated sections transparently issue a 302 redirect to the corresponding `wp-admin` screen with parameters intact.
   - *Rationale:* Eliminates risky "big-bang" deployment, allows continuous production deployment of finished sections, and provides a seamless user experience during migration.

### Architecture Diagram
```mermaid
graph TD
  subgraph Frontend Request
    Req["GET /manage/:section/:view?params"] --> Rew["Rewrite Rules: rm_manage=1, rm_section, rm_view"]
    Rew --> PR["Portal_Page_Router"]
  end

  PR --> Auth["Portal_Auth_Guard (Auth & Capability Check)"]
  Auth -->|Unauthorized| Denied["403 Forbidden / Login Redirect"]
  Auth -->|Authorized| Reg["Portal_Section_Registry"]

  Reg -->|Section Pending Migration| Fallback["302 Redirect to wp-admin/admin.php?page=racketmanager-..."]
  Reg -->|Section Ported & Enabled| Dispatch["Screen Bridge Dispatcher"]

  Dispatch --> Bridge["Screen Bridge (e.g. Admin_Club)"]
  Bridge --> Ctrl["Controller (e.g. Club_Admin_Controller)"]

  Ctrl -->|Mutation (POST)| PRG["Admin_Action_Adapter (Flash + PRG Redirect)"]
  PRG --> UrlRes["Manage_Url_Resolver (/manage/:section/:view)"]

  Ctrl -->|Display (GET)| Pres["Portal_Screen_Presenter"]
  Pres --> Shell["Portal App Shell (templates/portal/layout.php)"]
  Shell --> ViewTpl["View Template ($vm)"]
```

### Proposed Changes

#### 1. Routing and Rewrites (`src/php/Infrastructure/Wordpress/Rewrites/` & `Routing/`)
- Update `Rewrite_Rules.php` to register `manage/?$`, `manage/([^/]+)/?$`, and `manage/([^/]+)/([^/]+)/?$`.
- Register public query variables: `rm_manage`, `rm_section`, `rm_view`.
- Implement `Manage_Route_Mapper` in `Infrastructure/Wordpress/Routing/` mapping external section slugs to internal screen bridges:
  - `competitions` -> `Admin_Index` (`racketmanager`)
  - `clubs` -> `Admin_Club` (`racketmanager-clubs`)
  - `leagues` -> `Admin_League` (`racketmanager-leagues`)
  - `cups` -> `Admin_Cup` (`racketmanager-cups`)
  - `tournaments` -> `Admin_Tournament` (`racketmanager-tournaments`)
  - `results` -> `Admin_Result` (`racketmanager-results`)
  - `players` -> `Admin_Player` (`racketmanager-players`)
  - `seasons` -> `Admin_Season` (`racketmanager-seasons`)
  - `finances` -> `Admin_Finances` (`racketmanager-finances`)
  - `settings` -> `Admin_Options` (`racketmanager-settings`)
  - `import` -> `Admin_Import` (`racketmanager-import`)
  - `documentation` / `doc` -> `Documentation_Page_View_Model` (`racketmanager-doc`)

#### 2. Section Registry & Fallback Dispatcher (`src/php/Infrastructure/Wordpress/Portal/`)
- Implement `Portal_Section_Registry`:
  - Maintains state for each section: screen class, capability requirement, default view, and migration status flag (`PORTED` vs `PENDING`).
  - When `Portal_Page_Router` receives a request for a `PENDING` section, it constructs the legacy admin URL and issues a 302 redirect.

#### 3. Contextual URL Builder (`src/php/Infrastructure/Wordpress/Routing/`)
- Define `Manage_Url_Resolver_Interface` and context-aware implementation `Manage_Url_Resolver`.
- Update `Admin_Redirect_Url_Builder` to inject and consume `Manage_Url_Resolver_Interface` instead of hardcoded `admin.php` and `page=racketmanager-xxx` arguments.

#### 4. Portal Presenter & Shell (`src/php/Infrastructure/Wordpress/Portal/` & `templates/portal/`)
- Implement `Portal_Screen_Presenter` which wraps the inner `$vm` template inside `templates/portal/layout.php`.
- The shell includes navigation tabs, user bar with logout link, flash notices container (`apply_flash`), and breadcrumb navigation.
- Register and enqueue compiled portal stylesheet (`dist/css/portal.css`) and script (`dist/js/portal.js`).

#### 5. Section Phasing Matrix
The sections will be ported and activated across 4 sequential stages:
- **Increment 1:** `clubs` (all 6 subviews) and `competitions` (overview index).
- **Increment 2:** `leagues`, `cups`, and `tournaments`.
- **Increment 3:** `results` and `players`.
- **Increment 4:** `seasons`, `finances`, `settings`, `import`, and `documentation`.

### Data Models / Contracts

#### `Manage_Url_Resolver_Interface`
```php
namespace Racketmanager\Application\Ports;

interface Manage_Url_Resolver_Interface {
    public function get_url( string $section, string $view = '', array $query_args = [] ): string;
    public function is_portal_context(): bool;
}
```

#### `Portal_Section_Registry` Configuration
```php
namespace Racketmanager\Infrastructure\Wordpress\Portal;

final readonly class Portal_Section_Config {
    public function __construct(
        public string $slug,
        public string $admin_page,
        public string $screen_class,
        public string $capability,
        public string $default_view,
        public bool $is_ported = false,
    ) {}
}
```

### File Structure
- **Added Files:**
  - `src/php/Application/Ports/Manage_Url_Resolver_Interface.php`
  - `src/php/Infrastructure/Wordpress/Routing/Manage_Url_Resolver.php`
  - `src/php/Infrastructure/Wordpress/Routing/Manage_Route_Mapper.php`
  - `src/php/Infrastructure/Wordpress/Portal/Portal_Section_Registry.php`
  - `src/php/Infrastructure/Wordpress/Portal/Portal_Page_Router.php`
  - `src/php/Infrastructure/Wordpress/Portal/Portal_Screen_Presenter.php`
  - `src/php/Infrastructure/Wordpress/Portal/Portal_Auth_Guard.php`
  - `templates/portal/layout.php`
  - `templates/portal/navigation.php`
  - `templates/portal/unauthorized.php`
  - `tests/Unit/Infrastructure/Wordpress/Portal/Portal_Page_Router_Test.php`
  - `tests/Unit/Infrastructure/Wordpress/Portal/Portal_Section_Registry_Test.php`
  - `tests/Unit/Infrastructure/Wordpress/Routing/Manage_Url_Resolver_Test.php`
  - `tests/Unit/Infrastructure/Wordpress/Routing/Manage_Route_Mapper_Test.php`
- **Modified Files:**
  - `src/php/Infrastructure/Wordpress/Rewrites/Rewrite_Rules.php`
  - `src/php/Admin/Controllers/Admin_Redirect_Url_Builder.php`
  - `src/php/Services/Container/Providers/Core_Service_Provider.php`
  - `src/php/Admin/Admin_Screens_Service_Provider.php`
  - `src/php/Infrastructure/Wordpress/Component/Component_Coordinator.php`
  - Templates in `templates/admin/` (incrementally per section stage)

### Risks & Mitigations
- **Rewrite Rule Flushing:** WordPress rewrite rules must be flushed when the new rules are activated.
  - *Mitigation:* Ensure plugin activation, version updates, and migration runners trigger `flush_rewrite_rules()`.
- **Capability Differences:** Actions previously assumed running in `wp-admin` where `is_admin()` is true.
  - *Mitigation:* Controllers rely strictly on explicit capability checks (`current_user_can()`) and nonce verification rather than checking `is_admin()`.
- **Template URL Hardcoding:** Legacy templates containing literal `admin.php?page=...` hrefs.
  - *Mitigation:* Migrate templates section by section in lockstep with the section delivery stages.
- **Architecture Guardrail Violations:** Strict guardrails prohibit direct superglobal usage or improper layer imports.
  - *Mitigation:* All request parsing is done in `Portal_Page_Router` and passed via typed arrays/DTOs to controllers. Zero new baseline entries.


# Testing

### Validation Approach
Verification combines automated PHPUnit unit tests, integration tests for rewrite and route resolution, architecture guardrail verification via php-parser rules, and Jest ratchet checks. Each section increment will be validated independently before progressing to the next.

### Key Scenarios

#### 1. Core Rewrite & Route Mapping
- Navigating to `/manage/clubs` correctly resolves `rm_manage=1`, `rm_section=clubs`, and defaults `rm_view=clubs`.
- Navigating to `/manage/clubs/club?club_id=10` correctly resolves section `clubs` and view `club` with query argument `club_id=10`.
- Navigating to `/manage/competitions` correctly maps to the index screen (`Admin_Index`).
- Navigating to an invalid section (e.g. `/manage/invalid-section`) returns an HTTP 404 response.

#### 2. Section Registry & Seamless Fallback Gating
- When a section is marked `is_ported = true` (e.g. `clubs`), navigating to `/manage/clubs` renders the portal shell and club management view model.
- When a section is marked `is_ported = false` (e.g. `finances` prior to Stage 5), navigating to `/manage/finances?view=charge&charge_id=5` issues an immediate 302 redirect to `/wp-admin/admin.php?page=racketmanager-finances&view=charge&charge_id=5`.
- Unauthenticated access to pending sections redirects to `/wp-login.php` rather than disclosing admin URLs.

#### 3. Context-Aware URL Generation
- In frontend portal context, `Manage_Url_Resolver::get_url('clubs', 'club', ['club_id' => 10])` produces `/manage/clubs/club?club_id=10`.
- In wp-admin context, the same call produces `http://.../wp-admin/admin.php?page=racketmanager-clubs&view=club&club_id=10`.

#### 4. Section-by-Section POST / Mutation / PRG Flow
- Submitting an update form on `/manage/clubs/club?club_id=10` triggers the controller mutation.
- `Admin_Action_Adapter::deliver_redirect()` captures the success/error notice as a flash message.
- A 302/303 redirect is issued to `/manage/clubs/club?club_id=10`.
- Subsequent GET request renders `/manage/clubs/club?club_id=10` with the flash message rendered inside the portal shell alert slot.
- Analogous test scenarios verify mutation and PRG flows for competitive sections (draw publishing, match scheduling), operational sections (scorecard entry, result approval), and administrative sections.

#### 5. Authorization & Security
- Unauthenticated access to `/manage/*` redirects to `/wp-login.php?redirect_to=...`.
- Authenticated user lacking the required capability (e.g. lacking `racketmanager_settings` when accessing `/manage/settings`) receives an HTTP 403 unauthorized page template inside the portal shell.
- Form submission with invalid or missing nonce is rejected with an HTTP 403 status.

### Architecture Guardrail Compliance
- **PHPUnit Architecture Suite:** Run `./vendor/bin/phpunit --testsuite Architecture` to confirm 0 violations against strict rules (`admin.no_admin_display_inheritance`, `admin.no_template_require`, `templates.require_vm`, `templates.no_globals_or_db`, `all.no_fallback_construction`).
- **Jest Ratchet Suite:** Run `npm test -- tests/js/architecture` to ensure no new jQuery references, inline event handlers, or global window assignments are introduced.


# Delivery Steps

###   Step 1: Core Portal Foundation, Routing & Section Fallback Infrastructure
WordPress recognizes `/manage/:section/:view` routes, exposing sanitized query variables to `Portal_Page_Router`, which renders the portal shell for ported sections and seamlessly redirects unported sections to `wp-admin`.

- Register rewrite rules in `Rewrite_Rules::racketmanager_rewrites()` for `manage/?$`, `manage/([^/]+)/?$`, and `manage/([^/]+)/([^/]+)/?$`.
- Register public query variables `rm_manage`, `rm_section`, and `rm_view` via the WordPress `query_vars` filter.
- Implement `Manage_Url_Resolver_Interface` and `Manage_Url_Resolver` in `Infrastructure/Wordpress/Routing/` with dual-context awareness (`is_portal_context()`) to generate either `/manage/:section/:view` or `wp-admin/admin.php?page=racketmanager-...`.
- Implement `Portal_Section_Registry` in `Infrastructure/Wordpress/Portal/` storing configuration for all 12 sections (slug, screen bridge class, capability, and enablement flag).
- Create portal app shell template `templates/portal/layout.php` with responsive navigation, breadcrumbs, user profile bar with logout, and flash notices container (`apply_flash`).
- Implement `Portal_Screen_Presenter` in `Infrastructure/Wordpress/Portal/` to render `$vm` view models within the portal shell.
- Implement `Portal_Page_Router` hooked to `template_redirect` and `template_include`; when an authorized request targets a section marked disabled/pending, issue an immediate 302 redirect to its corresponding `wp-admin` URL with query parameters preserved.
- Add unit and integration tests verifying rewrite parsing, query variable sanitization, URL resolver outputs, and seamless fallback redirection.

###   Step 2: Section Increment 1 – Clubs & Competitions Dashboard
The `clubs` section (all 6 subviews) and `competitions` dashboard run fully inside the frontend portal with complete PRG mutation support and modernized URLs.

- Enable `clubs` (`Admin_Club`) and `competitions` (`Admin_Index`) in `Portal_Section_Registry`.
- Wire `Portal_Page_Router` dispatching to `Admin_Club` across all subviews: `clubs`, `club`, `players`, `teams`, `team`, and `roles`, delegating to their respective controllers (`Clubs_Admin_Controller`, `Club_Admin_Controller`, `Club_Players_Admin_Controller`, `Club_Teams_Admin_Controller`, `Club_Team_Admin_Controller`, `Club_Roles_Admin_Controller`).
- Wire `Portal_Page_Router` dispatching to `Admin_Index` and `Index_Admin_Controller` for the primary `/manage/competitions` dashboard overview.
- Update templates in `templates/admin/` (`show-clubs.php`, `includes/club.php`, `club/show-club-players.php`, `club/show-teams.php`, `includes/teams/*.php`, `club/show-roles.php`, and `index.php`) to resolve internal links via `Manage_Url_Resolver` instead of hardcoded `admin.php?page=` strings.
- Verify POST mutations for club details, team additions, role assignments, and player associations execute via PRG with flash messages rendered in the portal shell alert container.
- Add integration tests covering club and competition frontend workflows, nonces, and capability enforcement (`racket_manager`).

###   Step 3: Section Increment 2 – Competitive Play (Leagues, Cups & Tournaments)
Administrators manage leagues, cups, and tournaments via `/manage/leagues`, `/manage/cups`, and `/manage/tournaments` with responsive draws, brackets, and fixture schedules.

- Enable `leagues` (`Admin_League`), `cups` (`Admin_Cup`), and `tournaments` (`Admin_Tournament`) in `Portal_Section_Registry`.
- Connect `Portal_Page_Router` to competitive screen bridges and controllers:
  - League controllers: `League_Overview_Admin_Controller`, `League_Constitution_Admin_Controller`, `League_Schedule_Admin_Controller`, `League_Matches_Admin_Controller`, `League_Standings_Admin_Controller`, `League_Setup_Admin_Controller`, `League_Competitions_Admin_Controller`, `League_Event_Admin_Controller`.
  - Cup controllers: `Cup_Overview_Admin_Controller`, `Cup_Draw_Admin_Controller`, `Cup_Matches_Admin_Controller`, `Cup_Plan_Admin_Controller`, `Cup_Setup_Admin_Controller`, `Cup_Competitions_Admin_Controller`.
  - Tournament controllers: `Tournament_Overview_Admin_Controller`, `Tournament_Draw_Admin_Controller`, `Tournament_Fixtures_Admin_Controller`, `Tournament_Information_Admin_Controller`, `Tournament_Plan_Admin_Controller`, `Tournament_Setup_Admin_Controller`, `Tournament_Tournaments_Admin_Controller`.
- Update templates across `templates/admin/` for leagues, cups, tournaments, and championship finals to use `Manage_Url_Resolver` for internal navigation and subview links.
- Test draw generation, schedule publishing, constitution team assignments, and match score displays under portal routing.
- Add unit and integration tests validating competitive route resolution and permission checks.

###   Step 4: Section Increment 3 – Match Operations & Players (Results & Player Management)
Match reporting, score verification, results checker tools, and player directories operate smoothly via `/manage/results` and `/manage/players`.

- Enable `results` (`Admin_Result`) and `players` (`Admin_Player`) in `Portal_Section_Registry`.
- Wire `Portal_Page_Router` to `Results_Admin_Controller` (incorporating `Results_Checker_Tab_Handler` and `Results_Fixtures_Tab_Handler`) for match approval, scorecard review, and result validation.
- Wire `Portal_Page_Router` to player management controllers (`Players_Admin_Controller`, `Player_Admin_Controller`, `Player_Errors_Admin_Controller`, `Player_Requests_Admin_Controller`) for player profiles, duplicate merging, and registration requests.
- Update results and player templates in `templates/admin/` (`results/*.php`, `player/*.php`, scorecard review modals) to use `Manage_Url_Resolver` for search pagination, filter tabs, and detail views.
- Test score entry submissions, result approvals, dispute handling, and player profile updates with PRG redirect flows back to `/manage/results/*` and `/manage/players/*`.
- Add integration tests verifying score entry mutations, nonce verification, and capability enforcement.

###   Step 5: Section Increment 4 – Administration & Final Cutover (Seasons, Finances, Settings, Import, Docs)
All remaining sections (`seasons`, `finances`, `settings`, `import`, `documentation`) are active on the frontend portal, completing full portal migration while retaining backward compatibility.

- Enable `seasons` (`Admin_Season`), `finances` (`Admin_Finances`), `settings` (`Admin_Options`), `import` (`Admin_Import`), and `documentation` (`Documentation_Page_View_Model`) in `Portal_Section_Registry`.
- Wire `Portal_Page_Router` to remaining administration controllers:
  - Seasons: `Seasons_Admin_Controller`.
  - Finances: `Finances_Home_Admin_Controller`, `Finances_Charge_Admin_Controller`, `Finances_Charges_Admin_Controller`, `Finances_Invoice_Admin_Controller`, `Finances_Invoices_Admin_Controller`.
  - Settings: `Options_Admin_Controller`.
  - Import: `Import_Admin_Controller`.
- Migrate remaining administration templates in `templates/admin/` to use `Manage_Url_Resolver`.
- Update portal navigation to reflect all 12 active sections with active tab indicators and role-based capability filtering (`racket_manager`, `racketmanager_settings`, `import_leagues`).
- Conduct full regression testing verifying concurrent operation between frontend portal (`/manage/*`) and wp-admin screens (`admin.php?page=*`), ensuring zero differences in business logic execution.
- Run automated architecture suites to verify 0 violations against all 37 PHPUnit architecture rules and Jest ratchets.