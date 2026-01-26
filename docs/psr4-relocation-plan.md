### PSR‑4 Relocation Plan

#### Objective
Migrate legacy PHP classes from `include/` to PSR‑4 compliant locations under `src/php/` with zero runtime regressions, full backward compatibility during the transition, and deterministic Composer autoloading.

---

### Phase A — Complete core relocation (Immediate)
1) Move `Racketmanager\RacketManager`
- Action: Relocate implementation from `include/class-racketmanager.php` → `src/php/RacketManager.php`.
- Legacy shim: Convert `include/class-racketmanager.php` into a thin loader that `require_once` the PSR‑4 file when `class_exists('Racketmanager\\RacketManager', false)` is false.
- Keep safety guards: Retain load‑order guards in `RacketManager::load_libraries()` for `League` and `League_Team` until sports classes move.
- Verification: Plugin activates; Admin + front‑end pages render; shortcodes register; AJAX controllers initialize; no fatal “class not found”.

2) Autoload alignment
- Composer: Keep `"psr-4": { "Racketmanager\\": "src/php/" }` and temporary `"classmap": ["include/"]`.
- Run `composer dump-autoload -o` after each batch.

3) CI sanity check
- In the plugin directory:
  - `composer install --no-dev -o`
  - Smoke: require `racketmanager.php` and call `Racketmanager\RacketManager::get_instance()`.

Deliverable: `RacketManager` lives under `src/php/`; legacy include is a shim; site boots cleanly.

---

### Phase B — Domain models (Batch 1)
Classes: `Charge`, `Invoice`, `Club`, `Club_Player`, `Club_Role`, `Player`, `Team`, `Season`, `Competition`, `Event`, `Match`, `Rubber`, `Results_Report`, `Results_Checker`, `Tournament`, `Tournament_Entry`, `Message`, `User`.
- For each:
  - Move to `src/php/<Class>.php` preserving namespace and API.
  - Convert `include/class-<name>.php` to a thin shim.
  - Verify common operations (shortcodes/admin screens that touch these models).

Deliverable: Core domain entities resolve via PSR‑4; legacy includes act as BC shims.

---

### Phase C — Controllers & services (Batch 2)
1) AJAX controllers
- Move `include/ajax/*.php` → `src/php/ajax/` (namespaces remain `Racketmanager\ajax`).
- Ensure `add_action('wp_ajax_*', ...)` registrations are preserved.
- Add shims under `include/ajax/` to require new files.
- Verify endpoints (success + typical error paths).

2) Shortcodes
- Move `include/shortcodes/*.php` → `src/php/shortcodes/` (same namespaces).
- Verify shortcode registration and front‑end output.

3) Validators & remaining utilities
- Move `include/validator/*.php` → `src/php/validator/`.
- Convert existing util bridges to full moves when stable.

Deliverable: All request/response code paths run via PSR‑4 without regressions.

---

### Phase D — Sports and load‑order cleanup (Batch 3)
- Move `include/class-league.php`, `include/class-league-team.php`, and `sports/*` classes to `src/php/sports/`.
- Update `RacketManager::load_libraries()` to avoid manual includes for classes now autoloaded; retain template tags & functions includes.
- Remove temporary guards for `League`/`League_Team` after migration.
- Verify standings, scheduling, and league tables pages.

Deliverable: Sports classes PSR‑4 loaded; guards removed; clean bootstrap.

---

### Phase E — Cleanup & consolidation
1) Remove Composer classmap
- After all targets moved and shims in place for any external requires, drop `"classmap": ["include/"]` and `composer dump-autoload -o`.

2) Remove legacy shims (optional, after stabilization)
- After at least one release cycle with no external direct requires detected, remove shim files from `include/`.

3) Prune runtime guards
- Remove `class_exists` fallbacks in `racketmanager.php` and any temporary guards in `RacketManager`.

Deliverable: Pure PSR‑4 autoloading; minimal bootstrap; no duplicated loaders.

---

### Verification checklist (each phase)
- Activate plugin and load an Admin page and a public page.
- Confirm `src/js/index.js` is enqueued; browser console free of errors.
- Exercise representative flows:
  - Payments checkout + completion pages.
  - Messages list/detail/delete.
  - Player search.
  - Match entry UI (where applicable).
- Review PHP error logs for autoload/class errors.

---

### CI/CD updates
- In the plugin path: `composer install --no-dev -o`.
- Optional: `php -l` on moved files; tiny bootstrap script to instantiate key classes.
- Keep Qodana and non‑blocking PHPCS.

---

### Risks & mitigations
- Stale classmap or missing vendor: Always run `composer dump-autoload -o` and ship `vendor/` or install server‑side.
- Load‑order issues (sports): Keep guards until `League`/`League_Team` move; test standings pages.
- External code requiring legacy files: Maintain shims through one release cycle before removal.

---

### Timeline
- Phase A: 1 PR (same day).
- Phase B: 2–3 PRs over 1–2 days.
- Phase C: 2 PRs over 1 day.
- Phase D: 1–2 PRs over 1–2 days.
- Phase E: 1 PR after stabilization window.

---

### Acceptance criteria
- Classes resolve via PSR‑4 under `src/php/` with no fatal errors.
- CI installs Composer autoloader and passes the smoke check.
- After Phase E: `composer.json` has no `classmap` entry, and legacy shims are removed (or retained only with explicit justification).

---

### Status (2025-11-04)
- Phase E complete.
  - Composer autoload config contains only PSR‑4 for `Racketmanager\` and no `classmap` entry (see composer.json).
  - Main plugin bootstrap (racketmanager.php) is minimal, loads Composer autoloader, registers hooks, and defers initialization; no runtime `class_exists` fallbacks remain there.
  - Racketmanager\RacketManager::load_libraries() no longer requires class files; it only includes sports registrar scripts plus template-tags.php and functions.php.
  - Legacy shims remain under `include/` strictly for backward compatibility with any external direct `require` calls; they will be removed after one stable release unless usage is detected.

Verification reminder:
- Run in plugin dir: `composer install --no-dev -o && composer dump-autoload -o`.
- Activate the plugin; visit admin and front‑end pages; check for autoload/class errors.
- Confirm sports list via Util::get_sports() and typical flows (payments/messages/player search/match entry) work without regressions.

---

### FAQ

#### Why was the Stripe autoload removed?
With Phase E complete, the plugin now relies on a single Composer autoloader (vendor/autoload.php). Previously there were scattered, package‑specific loaders (e.g., directly requiring Stripe's init.php). Keeping those would:
- Duplicate autoloaders, increasing load order complexity and risk of conflicts.
- Defeat Composer’s optimized class map and PSR‑4 resolution.
- Make maintenance harder when packages update their own internal autoloading.

What happens now:
- racketmanager.php includes vendor/autoload.php once; this autoloader registers all packages, including stripe/stripe-php, so Stripe classes are available automatically (e.g., new \Stripe\StripeClient(...)).
- No more manual require of vendor/stripe/stripe-php/init.php or similar paths.
- The plugin now explicitly requires stripe/stripe-php in composer.json, ensuring the SDK is present and autoloaded in all supported deployments.

Migration notes:
- If you have custom code that previously did require_once RACKETMANAGER_PATH . 'vendor/stripe/stripe-php/init.php'; remove that line. Just ensure Composer’s autoloader is loaded and use Stripe’s namespaced classes.
- After pulling this change, run Composer in the plugin directory so stripe/stripe-php is installed and registered by vendor/autoload.php.

Verification:
- Run: composer install --no-dev -o && composer dump-autoload -o within the plugin directory.
- Exercise payment flows to confirm Stripe classes resolve without manual require calls.
- If the SDK is missing at runtime, AJAX endpoints will return a clear JSON error instructing to install Composer dependencies, rather than hard-failing the request.
