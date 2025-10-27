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
Classes: `Charge`, `Invoice`, `Club`, `Player`, `Team`, `Season`, `Tournament`, `Tournament_Entry`, `Message`.
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