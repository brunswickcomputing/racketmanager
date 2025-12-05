### RacketManager Plugin Architecture

This document describes the recommended, future‑proof structure for the RacketManager WordPress plugin. It aligns with WordPress best practices and the project’s current move toward modular JavaScript.

---

### Top‑Level Layout

wp-content/plugins/racketmanager/
- racketmanager.php               (Plugin bootstrap: headers, constants, hooks, enqueue)
- readme.txt                      (WP.org style readme if applicable)
- composer.json                   (Optional: PSR‑4 autoload for PHP)
- package.json                    (Bundler and tooling)
- qodana.yaml / phpcs.xml         (Quality config; optional)
- languages/                      (Translation files, .mo/.po)
- assets/
  - images/
  - icons/
- templates/
  - includes/                     (Shared partials, modals, etc.)
- src/
  - php/                          (If using PSR‑4; otherwise use includes/)
    - Plugin.php                  (Main plugin class / service container — in this project, `RacketManager` boots a small DI container; see docs/dependency-injection.md)
    - Activator.php               (Activation/deactivation hooks handler; PSR‑4 bridge allowed)
    - Admin/                      (Admin‑only screens, settings pages)
    - Public/                     (Frontend hooks, shortcodes/blocks)
    - Ajax/                       (AJAX controllers for wp‑ajax)
    - Rest/                       (REST controllers, if used)
    - Domain/                     (Entities/Models)
    - Repositories/               (Data access abstraction)
    - Services/                   (Business logic, helpers)
      - Contracts/                (Service interfaces for external integrations; e.g., Wtn_Api_Client_Interface)
      - External/                 (Concrete clients for external APIs; e.g., Wtn_Api_Client)
    - Util/                       (Utility classes)
  - js/
    - index.js                    (Single JS entry point)
    - config/
      - ajax-config.js            (Centralized WordPress AJAX URL + nonce) 
      - constants.js              (Global constants, minimal globals)
    - utils/                      (UI helpers, shared functions)
    - features/
      - ajax/                     (Centralized error handling, etc.)
      - forms/                    (Form UX: password toggle, checkboxes)
      - navigation/               (Navigation, popstate, etc.)
      - autocomplete/
      - teams/
      - favourites/
      - printing/
      - tabdata/
      - tournaments/
      - modals/
        - index.js                (Modals aggregate)
        - modal-utils.js          (Shared modal helpers)
        - match-status-modal.js   (Modular match status modal)
        - po-modal.js
        - po-set-purchase-order.js
      - account/
      - club/
      - player/
    - legacy/                     (Optional, for thin shims only)
- dist/
  - js/
    - racketmanager.js            (Built bundle from src/js/index.js)
  - css/
    - racketmanager.css           (Built CSS)
- scss/                           (If using Sass; otherwise css/)
- js/                             (Legacy file(s) for BC; use only as shims)
  - racketmanager.js              (Thin wrappers delegating to modules)
- docs/
  - architecture.md               (This document)
  - dependency-injection.md       (Lightweight DI container overview and usage)

Notes
- Keep business logic in PHP classes under src/php (or includes/ if not using Composer), not inside templates.
- Templates should remain logic‑light and receive data from controllers/services.

---

### PHP Architecture

- Namespace: Racketmanager\ for all PHP classes.
- Autoloading: Prefer Composer PSR‑4 mapping, e.g. "Racketmanager\\": "src/php/". If not using Composer, require files in racketmanager.php.
- Bootstrap flow (racketmanager.php):
  - Define constants: RACKETMANAGER_PATH, RACKETMANAGER_URL, RACKETMANAGER_VER.
  - Load autoloader (Composer) or requires.
  - Instantiate exactly one core object to avoid duplicate hooks/actions:
    - In wp‑admin: instantiate Admin (extends RacketManager)
    - Otherwise: instantiate RacketManager
    - Global $racketmanager is set to this single instance for BC
  - Instantiate a Plugin (service) class that:
    - Registers hooks for activation/deactivation.
    - Wires Admin and Public components.
    - Registers AJAX/REST controllers.

Suggested responsibilities
- Public\Assets: Handles enqueueing frontend CSS/JS.
- Admin\Assets: Handles admin CSS/JS.
- Ajax\*: Each action (e.g. racketmanager_match_status) has a controller method that validates nonce/capabilities and returns wp_send_json_* responses.
- Rest\*: For REST endpoints if/when needed.
- Services\*: Reusable business logic.
- Player and club responsibilities:
  - Player_Management_Service owns calculate_player_ratings(int $club_id = null). RacketManager delegates to it.
  - Player_Management_Service uses Club_Player_Management_Service::get_registered_players_list() to obtain active registered players (optionally scoped by club) before invoking the WTN update in core.
- Repositories\*: Data access (WP_Query, custom tables, etc.).
- Util\*: Pure helpers.

External API clients
- Location and contracts
  - Interfaces live under Services/Contracts (e.g., Services/Contracts/Wtn_Api_Client_Interface.php)
  - Concrete implementations live under Services/External (e.g., Services/External/Wtn_Api_Client.php)
- Usage
  - Domain services (e.g., Player_Management_Service) should type‑hint interfaces and receive clients via dependency injection.
  - Default Wtn_Api_Client delegates to existing core helpers (set_wtn_env, get_player_wtn) to preserve behavior while isolating HTTP details.
  - This separation makes external calls mockable and keeps transport concerns out of domain logic.

---

### Using Symfony components in the plugin

You can use individual Symfony components inside this WordPress plugin without adopting the full Symfony framework. Components are installed via Composer and autoloaded through the existing vendor/autoload.php that the plugin already requires.

General guidance
- Prefer using focused components to solve concrete problems; avoid introducing a parallel framework that duplicates WordPress features.
- Keep the integration at the Services layer so templates and controllers remain unaware of implementation details.
- Wrap components behind our existing interfaces to keep them optional and replaceable.

Recommended components and where they fit
- symfony/http-client
  - Purpose: Robust HTTP client for external API calls (timeouts, retries, middleware).
  - Placement: Used inside Services/External clients (e.g., Wtn_Api_Client implementation) to replace wp_remote_* if desired.
  - Mapping: Implement Wtn_Api_Client_Interface and use HttpClient under the hood. Keep the interface stable so the rest of the plugin is unaffected.
- symfony/cache
  - Purpose: Cache API responses or expensive computations (FilesystemAdapter, RedisAdapter, ArrayAdapter).
  - Placement: Services/External (for API response caching) or Services/* where computation caching helps.
  - Mapping: Optionally create a Cache interface in Services/Contracts and an adapter that uses Symfony Cache. Alternatively, back with WordPress object cache/transients where available.
- symfony/serializer
  - Purpose: Serialize/deserialize DTOs and arrays (e.g., Club_Player_DTO) when exchanging structured data.
  - Placement: Services or Repositories that map between arrays and domain objects.
  - Mapping: Optional; the project currently uses simple constructors. Adopt where complex mappings arise.
- symfony/validator
  - Purpose: Declarative validation rules for input data.
  - Placement: Services/Validator or domain services that validate payloads.
  - Mapping: The project already has Services/Validator/Validator. If Symfony Validator is introduced, wrap it behind our Validator to avoid a double system.
- symfony/event-dispatcher
  - Purpose: Internal domain events decoupling.
  - Placement: Services layer to signal events like PlayerRegistered, WtnUpdated.
  - Mapping: Use for internal decoupling. For WordPress-wide events, continue using do_action/apply_filters.
- symfony/rate-limiter
  - Purpose: Throttle calls to external services (e.g., WTN lookups) to respect rate limits.
  - Placement: Services/External around API client calls.
- symfony/filesystem and symfony/finder
  - Purpose: Safer filesystem operations and file discovery (useful in Admin imports/exports).
  - Placement: Admin services (e.g., Admin_Import) and any file‑heavy features.
- symfony/options-resolver
  - Purpose: Robust handling and validation of associative configuration arrays.
  - Placement: Service constructors/configuration parsing.

Less recommended (use WordPress-native first)
- symfony/console: Prefer WP‑CLI for command‑line tasks in WordPress ecosystems.
- symfony/dependency-injection: Possible, but heavy for a WP plugin. If used, keep the container encapsulated and do not leak DI types across the codebase. The current simple constructor‑based DI is adequate.

Composer installation examples
```
composer require symfony/http-client:^7.0 symfony/cache:^7.0 symfony/rate-limiter:^7.0
# Optional extras depending on needs
composer require symfony/serializer:^7.0 symfony/validator:^7.0 symfony/filesystem:^7.0 symfony/finder:^7.0 symfony/options-resolver:^7.0
```

Example: Using HttpClient inside an alternate WTN client
- Create Services/External/Wtn_Http_Api_Client.php implementing Wtn_Api_Client_Interface.
- Inject Symfony HttpClientInterface.
- Implement prepare_env() and fetch_player_wtn() using HttpClient. Register this implementation when constructing Player_Management_Service if you want to switch from the default.

Operational notes
- Error handling: Convert component exceptions to WP_Error or domain exceptions to preserve existing error flows.
- Performance: Be mindful of autoloaded classes on every request. Only require components you use.
- Caching: In shared hosting, FilesystemAdapter is usually safe. Prefer RedisAdapter or APCuAdapter where available.
- Security: Follow WordPress nonces/cap checks for admin actions; Symfony components do not replace WP’s security model.

Decision summary
- Yes, Symfony can be used in this plugin selectively. Use individual components where they provide clear benefits (HTTP, caching, rate limiting, validation, filesystem). Keep them behind our Services/Contracts interfaces to avoid framework lock‑in and to preserve compatibility with WordPress conventions.

---

### Templates

- templates/ contains all PHP view files. Avoid complex logic; pass data from PHP controllers/services.
- templates/includes/ holds shared partials (modals, alerts, loaders).
- Keep template functions (e.g., the_match_time) in a small view helper class under src/php/Public/ or Util.

---

### JavaScript Architecture

- Single entry point: src/js/index.js initializes all features on document ready and on ajaxComplete.
- Features are organized by domain under src/js/features/* with their own initialize* functions.
- Use centralized AJAX helpers only:
  - getAjaxUrl() and getAjaxNonce() from src/js/config/ajax-config.js.
- Keep globals minimal. window.Racketmanager exists only for legacy compatibility, exposing thin shims where needed.
- Use data‑action hooks and delegated events (e.g., [data-action="open-match-status-modal"]) instead of inline onclick handlers.
- Error handling centralized in src/js/features/ajax/handle-ajax-error.js.

Legacy JS migration strategy
- Keep js/racketmanager.js but reduce it to shims that delegate to modules (as already done for statusModal).
- Gradually move each legacy function into src/js/features/* with an initialize function and, if necessary, a global shim.

---

### CSS/SCSS and Assets

- Author styles in scss/ and compile to dist/css/.
- Images, icons, and static assets live under assets/.
- Reference assets via RACKETMANAGER_URL in PHP templates or import in JS/CSS when bundled.

---

### Build & Distribution

- Use a bundler to compile src/js/index.js to dist/js/racketmanager.js and process CSS into dist/css/.
- Produce source maps in development and minified assets in production.
- Version assets with the plugin version via wp_enqueue_script/style (add a version argument) to bust caches.

Current setup (2025-11-04)
- JS build: esbuild bundles src/js/index.js → dist/js/racketmanager.js (package.json scripts: build, build:prod).
- CSS build: esbuild copies/minifies css/style.css, css/admin.css, css/modal.css, css/print.css → dist/css/*.css.
- Run locally:
  - npm install
  - npm run build (dev) or npm run build:prod (prod)
- CI: .github/workflows/ci-release.yml runs npm run build:prod and verifies dist/js/racketmanager.js and dist/css/*.css exist; artifacts include dist/** and assets/**.

Example enqueue (frontend)
- In a Public\Assets class:

  public function enqueue() {
      wp_enqueue_style(
          'racketmanager',
          RACKETMANAGER_URL . 'dist/css/racketmanager.css',
          [],
          RACKETMANAGER_VER
      );

      wp_enqueue_script(
          'racketmanager',
          RACKETMANAGER_URL . 'dist/js/racketmanager.js',
          ['jquery', 'jquery-ui-core'],
          RACKETMANAGER_VER,
          true
      );

      wp_localize_script(
          'racketmanager',
          'ajax_var',
          [
              'url' => admin_url('admin-ajax.php'),
              'ajax_nonce' => wp_create_nonce('racketmanager_nonce'),
          ]
      );
  }

- The JS should only ever read AJAX details via getAjaxUrl()/getAjaxNonce().

---

### AJAX & REST

- Use dedicated controllers (src/php/Ajax/*) for admin-ajax actions. Validate nonces and capabilities.
- Return structured JSON: { message/msg, data, status, err_flds/err_msgs } so the centralized JS error handler can display field errors.
- If the plugin adds a REST API, mirror the structure under src/php/Rest/*.

---

### Coding Conventions

- PHP: PSR‑12 style, strict types when possible, namespaced classes, small single‑responsibility classes.
- JS: ES modules, no direct window.ajax_var usage outside ajax-config.js, favor feature initialize* functions.
- Templates: minimal logic, escape output properly (esc_html, esc_attr, esc_url, etc.).
- Naming: kebab‑case for files in assets, dash‑separated template names, PascalCase for PHP classes.

---

### Testing & Quality

- Linters: ESLint + Prettier for JS; PHP_CodeSniffer with WordPress ruleset for PHP.
- Optional unit tests: PHPUnit for PHP, Jest/Vitest for JS.
- Smoke tests for critical user flows (match status modal, purchase orders, match updates).

---

### Migration Checklist (Incremental)

1) JS
- Keep src/js/index.js as the single entry point.
- Migrate functions from js/racketmanager.js into src/js/features/*.
- Provide legacy shims until templates are fully decoupled from globals.

2) PHP
- Introduce Composer autoload (optional). Move classes under src/php and namespaced Racketmanager\\.
- Centralize hooks in a Plugin class and split Admin/Public concerns.

3) Templates
- Remove inline JS handlers over time; favor data-action hooks.
- Keep includes/ for shared partials.

4) Build
- Add a bundler config and output to dist/.
- Update enqueue to load dist assets and localize ajax_var once.

5) Docs & QA
- Maintain this architecture document.
- Add CONTRIBUTING.md with setup, build and coding standards.

---

### Current Project Alignment

- The repository already uses src/js/index.js and modular features (e.g., modals/match-status-modal.js), with centralized AJAX helpers. Continue this direction.
- js/racketmanager.js should remain only as a compatibility layer while features are migrated.
- Templates have started using data-action hooks. Maintain this pattern to reduce JS coupling.

This structure keeps the plugin maintainable, testable, and consistent with WordPress standards while enabling gradual migration from legacy code.

---

### Backward Compatibility Policy Update

As of the latest changes, legacy global JavaScript hooks for match rubber status have been removed:

- Removed global Racketmanager.setMatchRubberStatus and any delegated handlers tied to data-action="set-match-rubber-status".
- The supported pattern is the modular, delegated approach provided by src/js/features/match/rubber-status-modal.js:
  - Open modal: [data-action="open-rubber-status-modal"]
  - Save status: [data-action="set-rubber-status"]

Templates must not use inline onclick handlers. Instead, render semantic buttons/links with the appropriate data-action attributes inside the correct modal/form.

This keeps the codebase consistent with the modular architecture and avoids reliance on global shims.

Additionally, the UI helpers for updating rubber status messages and classes are now modular:
- src/js/features/match/rubber-status-ui.js exports setRubberStatusMessages, setRubberStatusClasses, and setTeamMessage.
- The legacy implementations Racketmanager.setRubberStatusMessages, Racketmanager.setRubberStatusClasses, and Racketmanager.setTeamMessage have been removed from js/racketmanager.js.
- Modular callers (rubber-status-modal.js and set-match-status.js) import and use these helpers directly.


---

### Auth (Login & Reset Password)

- Modular JS modules handle login and reset password under src/js/features/auth/:
  - src/js/features/auth/login.js exports initializeLogin() and submitLogin().
  - src/js/features/auth/reset-password.js exports initializeResetPassword() and submitResetPassword().
- Delegated data-action hooks (no inline onclick):
  - Login submit button: [data-action="login-submit"]
  - Reset password submit button: [data-action="reset-password"]
- Both modules use centralized AJAX helpers (getAjaxUrl/getAjaxNonce) and the shared error handler.

Legacy removal
- Removed global functions from js/racketmanager.js:
  - Racketmanager.login
  - Racketmanager.resetPassword
- Templates must not use inline handlers like onclick="Racketmanager.login(this)". Render semantic buttons with data-action attributes instead.



---

### Finalized JavaScript Policy (2025-10-23)

- No legacy JS enqueue
  - The legacy file js/racketmanager.js is no longer enqueued. All frontend behavior lives under src/js/ with a single entry point at src/js/index.js.
  - Do not reintroduce legacy globals or inline scripts.

- Delegated event handlers by default
  - Use data-action attributes and delegated handlers bound to document with a namespaced event (e.g., .racketmanager.feature).
  - Avoid inline onclick/onchange in templates. Prefer semantic attributes (data-*) that modules subscribe to.

- Centralized AJAX error handling
  - Use src/js/features/ajax/handle-ajax-error.js for all AJAX failures. It renders messages into provided containers and applies field-level validation when available.

- Minimal globals policy
  - Only window.Racketmanager.loadingModal is supported for compatibility with templates that reference the loading modal selector.
  - Do not expose new globals. Features should be imported and initialized via src/js/index.js.

- Rollback strategy removed
  - The temporary rollback path that conditionally enqueued the legacy file has been decommissioned. Do not rely on legacy functions.
  - All features must operate via modular modules and delegated handlers.

- Dynamic content and re-initialization
  - Delegated handlers naturally support dynamically injected content via AJAX.
  - For components that require direct bindings (e.g., certain checkboxes or third-party widgets), re-run their initialize* functions from the ajaxComplete re-initializer in src/js/index.js.

- Adding new features
  - Create a module under src/js/features/<area>/ and export an initialize* function.
  - Import and invoke the initializer from src/js/index.js on document ready (and optionally on ajaxComplete if needed).
  - Use centralized utilities (ajax-config, handle-ajax-error, logger/telemetry if enabled) and follow the data-action + delegated handler pattern.


---

### Current setup vs architecture (2025-11-04)

This section summarizes how the current codebase aligns with the architecture described above and highlights any gaps or intentional deviations.

Alignment highlights
- Bootstrap (racketmanager.php)
  - Uses an ABSPATH guard and defines core constants (RACKETMANAGER_PATH/URL/VERSION/etc.).
  - Loads Composer’s autoloader if present: vendor/autoload.php. ✓ See racketmanager.php lines 45–51.
  - Registers activation/deactivation hooks to Racketmanager\Activator. ✓
  - Defers initialization to hooks (plugins_loaded) and limits work at file load. ✓
  - Loads i18n via load_plugin_textdomain(). ✓
- Autoloading and namespaces
  - Composer configured with PSR‑4 for "Racketmanager\\" → "src/php/" and no classmap. ✓ See composer.json.
  - Legacy include/ shims exist only to forward external direct requires to PSR‑4 classes (Admin, Activator, RacketManager). ✓ Intentional, to be removed after a stable cycle.
- Core structure under src/php/
  - Central class Racketmanager\RacketManager used as the main service/container. ✓
  - Admin class Racketmanager\Admin extends RacketManager but is instantiated only in admin context and handles admin UI screens and assets. ✓
  - AJAX controllers under src/php/Ajax/ (e.g., Ajax_Tournament) register endpoints and return JSON. ✓
  - Services under src/php/Services/ (e.g., Validator, Stripe_Settings) implement business logic. ✓
  - Domain models and utilities exist under src/php/Domain and src/php/Util respectively. ✓
  - Sports: class files live in src/php/sports and registrar scripts (lowercase) are loaded manually by RacketManager::load_libraries() as documented. ✓
- REST layer
  - Rest_Routes class is referenced and initialized via Rest_Routes::single(). ✓ (REST usage present if routes are defined there.)
- Templates
  - PHP templates are in templates/ with shared partials under templates/includes/ (e.g., templates/includes/team.php). ✓
  - Template helpers remain in template-tags.php and functions.php; they are included from RacketManager::load_libraries(). ✓
- JavaScript
  - Modular source exists under src/js/ with index.js, utils/, features/, and core helpers (initialisation, ajax-complete). ✓
  - A documented migration plan exists for moving legacy JS into modules (docs/migration-plan.md). ✓
  - A legacy script exists at js/racketmanager.legacy.js, acting as compatibility glue. ✓

Notable differences or open items
- Service container naming
  - The architecture.md suggests a src/php/Plugin.php acting as the main container. The project uses src/php/RacketManager.php for this role. This is functionally equivalent and aligned; no change required. (Doc already treats naming as advisory.)
- Admin dependency on WP admin helpers
  - Admin::__construct() requires ABSPATH . 'wp-admin/includes/template.php' to access meta box helpers. This is an acceptable admin‑only dependency and is documented inline. ✓
- JS build artifacts
  - Build configured: src/js/index.js is bundled by esbuild to dist/js/racketmanager.js (see package.json). RacketManager::load_scripts() enqueues the dist bundle via wp_register_script_module. CSS build remains TODO if/when a Sass pipeline is added.
- Legacy shims
  - include/ contains thin shims for a few classes. This is intentional for a stabilization period and noted in psr4-relocation-plan.md. Removal is planned after one stable release without external direct requires. ✓/TODO later.
- Repositories layer
  - The architecture outlines a Repositories/ layer as optional. If data access abstractions are mixed into Services/ or Domain/ today, consider extracting to Repositories/ incrementally. TODO (as needed).

Recent confirmations (2025‑11‑28)
- Single‑instance bootstrap is implemented: Admin is the only instance in dashboard; RacketManager otherwise. This resolved duplicate hooks (e.g., racketmanager_mail) caused by double instantiation. ✓
- calculate_player_ratings flow moved to Player_Management_Service with RacketManager delegating; it uses Club_Player_Management_Service::get_registered_players_list() to determine the player set. ✓
- External API client architecture established: Wtn_Api_Client_Interface (Contracts) and Wtn_Api_Client (External) added; services can inject these clients. ✓

Verification pointers
- Composer autoload works end‑to‑end: vendor/autoload.php is loaded by racketmanager.php; classes resolve via PSR‑4; Stripe SDK is required via composer.json and used via \Stripe\StripeClient in Ajax_Tournament. ✓
- Sports registrars load as per design (lowercase non‑class .php files), while sports classes autoload lazily via PSR‑4. ✓
- Admin loads only in dashboard context; public hooks and shortcodes are wired in RacketManager initialization. ✓

Next small steps (non‑blocking)
- JS build outputs: verify bundler configuration outputs to dist/js and dist/css and that enqueue handles production/minified assets.
- Begin pruning legacy include/ shims after one stable release window without external direct requires.
- Consider introducing a Repositories/ namespace for complex data access patterns to decouple from Services/.



---

### How the JavaScript build step is triggered (2025-11-04)

There is no automatic PHP-side build. The JavaScript bundle is produced on demand using the NPM scripts defined in package.json.

- Local development:
  - From the plugin directory (wp-content/plugins/racketmanager):
    - npm install
    - npm run build        # fast build with sourcemap
    - npm run build:prod   # minified build for release
- What it does:
  - Bundles src/js/index.js and its imports into dist/js/racketmanager.js (ESM). Racketmanager\RacketManager::load_scripts() enqueues this file via wp_register_script_module.
- CI/release recommendations:
  - Run npm ci && npm run build:prod as part of your release or deployment pipeline so dist/js/racketmanager.js is present on the server.
- Verifying:
  - Load any front-end page and check the Network tab for /wp-content/plugins/racketmanager/dist/js/racketmanager.js. The console should be free of module load errors.
- Notes:
  - If dist/js/racketmanager.js is missing, the front-end module will not load. Build the assets using the commands above.


### RacketManager Plugin Architecture

This document describes the recommended, future‑proof structure for the RacketManager WordPress plugin. It aligns with WordPress best practices and the project’s current move toward modular JavaScript.

---

### Top‑Level Layout

wp-content/plugins/racketmanager/
- racketmanager.php               (Plugin bootstrap: headers, constants, hooks, enqueue)
- readme.txt                      (WP.org style readme if applicable)
- composer.json                   (Optional: PSR‑4 autoload for PHP)
- package.json                    (Bundler and tooling)
- qodana.yaml / phpcs.xml         (Quality config; optional)
- languages/                      (Translation files, .mo/.po)
- assets/
  - images/
  - icons/
- templates/
  - includes/                     (Shared partials, modals, etc.)
- src/
  - php/                          (If using PSR‑4; otherwise use includes/)
    - Plugin.php                  (Main plugin class / service container)
    - Activator.php               (Activation/deactivation hooks handler; PSR‑4 bridge allowed)
    - Admin/                      (Admin‑only screens, settings pages)
    - Public/                     (Frontend hooks, shortcodes/blocks)
    - Ajax/                       (AJAX controllers for wp‑ajax)
    - Rest/                       (REST controllers, if used)
    - Domain/                     (Entities/Models)
    - Repositories/               (Data access abstraction)
    - Services/                   (Business logic, helpers)
      - Contracts/                (Service interfaces for external integrations; e.g., Wtn_Api_Client_Interface)
      - External/                 (Concrete clients for external APIs; e.g., Wtn_Api_Client)
    - Util/                       (Utility classes)
  - js/
    - index.js                    (Single JS entry point)
    - config/
      - ajax-config.js            (Centralized WordPress AJAX URL + nonce) 
      - constants.js              (Global constants, minimal globals)
    - utils/                      (UI helpers, shared functions)
    - features/
      - ajax/                     (Centralized error handling, etc.)
      - forms/                    (Form UX: password toggle, checkboxes)
      - navigation/               (Navigation, popstate, etc.)
      - autocomplete/
      - teams/
      - favourites/
      - printing/
      - tabdata/
      - tournaments/
      - modals/
        - index.js                (Modals aggregate)
        - modal-utils.js          (Shared modal helpers)
        - match-status-modal.js   (Modular match status modal)
        - po-modal.js
        - po-set-purchase-order.js
      - account/
      - club/
      - player/
    - legacy/                     (Optional, for thin shims only)
- dist/
  - js/
    - racketmanager.js            (Built bundle from src/js/index.js)
  - css/
    - racketmanager.css           (Built CSS)
- scss/                           (If using Sass; otherwise css/)
- js/                             (Legacy file(s) for BC; use only as shims)
  - racketmanager.js              (Thin wrappers delegating to modules)
- docs/
  - architecture.md               (This document)

Notes
- Keep business logic in PHP classes under src/php (or includes/ if not using Composer), not inside templates.
- Templates should remain logic‑light and receive data from controllers/services.

---

### PHP Architecture

- Namespace: Racketmanager\ for all PHP classes.
- Autoloading: Prefer Composer PSR‑4 mapping, e.g. "Racketmanager\\": "src/php/". If not using Composer, require files in racketmanager.php.
- Bootstrap flow (racketmanager.php):
  - Define constants: RACKETMANAGER_PATH, RACKETMANAGER_URL, RACKETMANAGER_VER.
  - Load autoloader (Composer) or requires.
  - Instantiate a Plugin (service) class that:
    - Registers hooks for activation/deactivation.
    - Wires Admin and Public components.
    - Registers AJAX/REST controllers.

Suggested responsibilities
- Public\Assets: Handles enqueueing frontend CSS/JS.
- Admin\Assets: Handles admin CSS/JS.
- Ajax\*: Each action (e.g. racketmanager_match_status) has a controller method that validates nonce/capabilities and returns wp_send_json_* responses.
- Rest\*: For REST endpoints if/when needed.
- Services\*: Reusable business logic.
- Repositories\*: Data access (WP_Query, custom tables, etc.).
- Util\*: Pure helpers.

---

### Templates

- templates/ contains all PHP view files. Avoid complex logic; pass data from PHP controllers/services.
- templates/includes/ holds shared partials (modals, alerts, loaders).
- Keep template functions (e.g., the_match_time) in a small view helper class under src/php/Public/ or Util.

---

### JavaScript Architecture

- Single entry point: src/js/index.js initializes all features on document ready and on ajaxComplete.
- Features are organized by domain under src/js/features/* with their own initialize* functions.
- Use centralized AJAX helpers only:
  - getAjaxUrl() and getAjaxNonce() from src/js/config/ajax-config.js.
- Keep globals minimal. window.Racketmanager exists only for legacy compatibility, exposing thin shims where needed.
- Use data‑action hooks and delegated events (e.g., [data-action="open-match-status-modal"]) instead of inline onclick handlers.
- Error handling centralized in src/js/features/ajax/handle-ajax-error.js.

Legacy JS migration strategy
- Keep js/racketmanager.js but reduce it to shims that delegate to modules (as already done for statusModal).
- Gradually move each legacy function into src/js/features/* with an initialize function and, if necessary, a global shim.

---

This structure keeps the plugin maintainable, testable, and consistent with WordPress standards while enabling gradual migration from legacy code.

---

### Backward Compatibility Policy Update

As of the latest changes, legacy global JavaScript hooks for match rubber status have been removed:

- Removed global Racketmanager.setMatchRubberStatus and any delegated handlers tied to data-action="set-match-rubber-status".
- The supported pattern is the modular, delegated approach provided by src/js/features/match/rubber-status-modal.js:
  - Open modal: [data-action="open-rubber-status-modal"]
  - Save status: [data-action="set-rubber-status"]

Templates must not use inline onclick handlers. Instead, render semantic buttons/links with the appropriate data-action attributes inside the correct modal/form.

This keeps the codebase consistent with the modular architecture and avoids reliance on global shims.

Additionally, the UI helpers for updating rubber status messages and classes are now modular:
- src/js/features/match/rubber-status-ui.js exports setRubberStatusMessages, setRubberStatusClasses, and setTeamMessage.
- The legacy implementations Racketmanager.setRubberStatusMessages, Racketmanager.setRubberStatusClasses, and Racketmanager.setTeamMessage have been removed from js/racketmanager.js.
- Modular callers (rubber-status-modal.js and set-match-status.js) import and use these helpers directly.


---

### Auth (Login & Reset Password)

- Modular JS modules handle login and reset password under src/js/features/auth/:
  - src/js/features/auth/login.js exports initializeLogin() and submitLogin().
  - src/js/features/auth/reset-password.js exports initializeResetPassword() and submitResetPassword().
- Delegated data-action hooks (no inline onclick):
  - Login submit button: [data-action="login-submit"]
  - Reset password submit button: [data-action="reset-password"]
- Both modules use centralized AJAX helpers (getAjaxUrl/getAjaxNonce) and the shared error handler.

Legacy removal
- Removed global functions from js/racketmanager.js:
  - Racketmanager.login
  - Racketmanager.resetPassword
- Templates must not use inline handlers like onclick="Racketmanager.login(this)". Render semantic buttons with data-action attributes instead.



---

### Finalized JavaScript Policy (2025-10-23)

- No legacy JS enqueue
  - The legacy file js/racketmanager.js is no longer enqueued. All frontend behavior lives under src/js/ with a single entry point at src/js/index.js.
  - Do not reintroduce legacy globals or inline scripts.

- Delegated event handlers by default
  - Use data-action attributes and delegated handlers bound to document with a namespaced event (e.g., .racketmanager.feature).
  - Avoid inline onclick/onchange in templates. Prefer semantic attributes (data-*) that modules subscribe to.

- Centralized AJAX error handling
  - Use src/js/features/ajax/handle-ajax-error.js for all AJAX failures. It renders messages into provided containers and applies field-level validation when available.

- Minimal globals policy
  - Only window.Racketmanager.loadingModal is supported for compatibility with templates that reference the loading modal selector.
  - Do not expose new globals. Features should be imported and initialized via src/js/index.js.

- Rollback strategy removed
  - The temporary rollback path that conditionally enqueued the legacy file has been decommissioned. Do not rely on legacy functions.
  - All features must operate via modular modules and delegated handlers.

- Dynamic content and re-initialization
  - Delegated handlers naturally support dynamically injected content via AJAX.
  - For components that require direct bindings (e.g., certain checkboxes or third-party widgets), re-run their initialize* functions from the ajaxComplete re-initializer in src/js/index.js.

- Adding new features
  - Create a module under src/js/features/<area>/ and export an initialize* function.
  - Import and invoke the initializer from src/js/index.js on document ready (and optionally on ajaxComplete if needed).
  - Use centralized utilities (ajax-config, handle-ajax-error, logger/telemetry if enabled) and follow the data-action + delegated handler pattern.


---

### Current setup vs architecture (2025-11-04)

This section summarizes how the current codebase aligns with the architecture described above and highlights any gaps or intentional deviations.

Alignment highlights
- Bootstrap (racketmanager.php)
  - Uses an ABSPATH guard and defines core constants (RACKETMANAGER_PATH/URL/VERSION/etc.).
  - Loads Composer’s autoloader if present: vendor/autoload.php. ✓ See racketmanager.php lines 45–51.
  - Registers activation/deactivation hooks to Racketmanager\Activator. ✓
  - Defers initialization to hooks (plugins_loaded) and limits work at file load. ✓
  - Loads i18n via load_plugin_textdomain(). ✓
- Autoloading and namespaces
  - Composer configured with PSR‑4 for "Racketmanager\\" → "src/php/" and no classmap. ✓ See composer.json.
  - Legacy include/ shims exist only to forward external direct requires to PSR‑4 classes (Admin, Activator, RacketManager). ✓ Intentional, to be removed after a stable cycle.
- Core structure under src/php/
  - Central class Racketmanager\RacketManager used as the main service/container. ✓
  - Admin class Racketmanager\Admin extends RacketManager but is instantiated only in admin context and handles admin UI screens and assets. ✓
  - AJAX controllers under src/php/Ajax/ (e.g., Ajax_Tournament) register endpoints and return JSON. ✓
  - Services under src/php/Services/ (e.g., Validator, Stripe_Settings) implement business logic. ✓
  - Domain models and utilities exist under src/php/Domain and src/php/Util respectively. ✓
  - Sports: class files live in src/php/sports and registrar scripts (lowercase) are loaded manually by RacketManager::load_libraries() as documented. ✓
- REST layer
  - Rest_Routes class is referenced and initialized via Rest_Routes::single(). ✓ (REST usage present if routes are defined there.)
- Templates
  - PHP templates are in templates/ with shared partials under templates/includes/ (e.g., templates/includes/team.php). ✓
  - Template helpers remain in template-tags.php and functions.php; they are included from RacketManager::load_libraries(). ✓
- JavaScript
  - Modular source exists under src/js/ with index.js, utils/, features/, and core helpers (initialisation, ajax-complete). ✓
  - A documented migration plan exists for moving legacy JS into modules (docs/migration-plan.md). ✓
  - A legacy script exists at js/racketmanager.legacy.js, acting as compatibility glue. ✓

Notable differences or open items
- Service container naming
  - The architecture.md suggests a src/php/Plugin.php acting as the main container. The project uses src/php/RacketManager.php for this role. This is functionally equivalent and aligned; no change required. (Doc already treats naming as advisory.)
- Admin dependency on WP admin helpers
  - Admin::__construct() requires ABSPATH . 'wp-admin/includes/template.php' to access meta box helpers. This is an acceptable admin‑only dependency and is documented inline. ✓
- JS build artifacts
  - Build configured: src/js/index.js is bundled by esbuild to dist/js/racketmanager.js (see package.json). RacketManager::load_scripts() enqueues the dist bundle via wp_register_script_module. CSS build remains TODO if/when a Sass pipeline is added.
- Legacy shims
  - include/ contains thin shims for a few classes. This is intentional for a stabilization period and noted in psr4-relocation-plan.md. Removal is planned after one stable release without external direct requires. ✓/TODO later.
- Repositories layer
  - The architecture outlines a Repositories/ layer as optional. If data access abstractions are mixed into Services/ or Domain/ today, consider extracting to Repositories/ incrementally. TODO (as needed).

Verification pointers
- Composer autoload works end‑to‑end: vendor/autoload.php is loaded by racketmanager.php; classes resolve via PSR‑4; Stripe SDK is required via composer.json and used via \Stripe\StripeClient in Ajax_Tournament. ✓
- Sports registrars load as per design (lowercase non‑class .php files), while sports classes autoload lazily via PSR‑4. ✓
- Admin loads only in dashboard context; public hooks and shortcodes are wired in RacketManager initialization. ✓

Next small steps (non‑blocking)
- JS build outputs: verify bundler configuration outputs to dist/js and dist/css and that enqueue handles production/minified assets.
- Begin pruning legacy include/ shims after one stable release window without external direct requires.
- Consider introducing a Repositories/ namespace for complex data access patterns to decouple from Services/.

---

### How the JavaScript build step is triggered (2025-11-04)

There is no automatic PHP-side build. The JavaScript bundle is produced on demand using the NPM scripts defined in package.json.

- Local development:
  - From the plugin directory (wp-content/plugins/racketmanager):
    - npm install
    - npm run build        # fast build with sourcemap
    - npm run build:prod   # minified build for release
- What it does:
  - Bundles src/js/index.js and its imports into dist/js/racketmanager.js (ESM). Racketmanager\RacketManager::load_scripts() enqueues this file via wp_register_script_module.
- CI/release recommendations:
  - Run npm ci && npm run build:prod as part of your release or deployment pipeline so dist/js/racketmanager.js is present on the server.
- Verifying:
  - Load any front-end page and check the Network tab for /wp-content/plugins/racketmanager/dist/js/racketmanager.js. The console should be free of module load errors.
- Notes:
  - If dist/js/racketmanager.js is missing, the front-end module will not load. Build the assets using the commands above.

#### CI configuration (GitHub Actions)
- This repository includes a workflow that runs the JS build during CI:
  - File: wp-content/plugins/racketmanager/.github/workflows/ci-release.yml
  - It performs: composer install --no-dev -o, npm ci, npm run build:prod, verifies dist/js/racketmanager.js exists, and uploads build artifacts.
- To trigger:
  - Push to main/master, open a pull request, tag a release like v10.0.0, or run manually via workflow_dispatch.


---

### Exceptions

- Location: src/php/Exceptions/
- Namespace: Racketmanager\\Exceptions\\ (PSR-4 is already configured in composer.json).
- Base class: Create a minimal base exception, e.g., Racketmanager\\Exceptions\\PluginException extends \\RuntimeException. Use this as the parent for plugin-specific errors.
- Sub-namespaces: For clarity you may organize exceptions by area, e.g., Exceptions\\Domain, Exceptions\\Services, Exceptions\\Repositories.
- Usage guidance:
  - Throw domain-/service-specific exceptions rather than generic Exception.
  - Catch and translate exceptions at boundaries (AJAX/REST controllers) into appropriate wp_send_json_* responses.
  - Keep exceptions thin (no heavy logic); include actionable messages and codes where appropriate.
