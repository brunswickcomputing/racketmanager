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

### CSS/SCSS and Assets

- Author styles in scss/ and compile to dist/css/.
- Images, icons, and static assets live under assets/.
- Reference assets via RACKETMANAGER_URL in PHP templates or import in JS/CSS when bundled.

---

### Build & Distribution

- Use a bundler (Vite, Rollup, or Webpack) to compile src/js/index.js to dist/js/racketmanager.js and scss to dist/css/racketmanager.css.
- Produce source maps in development and minified assets in production.
- Version assets with the plugin version via wp_enqueue_script/style (add a version argument) to bust caches.

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
