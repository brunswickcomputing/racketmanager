### RacketManager Frontend Migration & Delivery Plan

This plan documents the phased migration of the legacy `js/racketmanager.js` codebase to modular, delegated-handler architecture under `src/js/`, and outlines delivery, testing, and rollout steps.

---

### Objectives
- Remove legacy inline handlers and global functions; adopt modular features under `src/js/features/` with delegated event handlers on `document`.
- Centralize configuration, error handling, and observability.
- Maintain behavior parity during migration; minimize template churn per phase.

---

### Scope
- JavaScript UI behavior used by public templates and admin pages, currently implemented in `js/racketmanager.js` and inline scripts within templates.
- PHP controllers/templates are in scope only for removing inline JS calls and adding data attributes where required.

---

### Phases

1. Phase 0 — Foundations (Completed)
   - Create centralized app config and feature flags (src/js/config/app-config.js).
   - Add lightweight logger and optional local-only telemetry (src/js/utils/logger.js; src/js/features/telemetry/).
   - Centralize AJAX error handling and integrate logging (src/js/features/ajax/handle-ajax-error.js).
   - Wire telemetry opt-in; disabled by default in production.

2. Phase 1 — Messages (Completed)
   - Modularize getMessage, deleteMessage, deleteMessages into src/js/features/messages/messages.js.
   - Replace template inline handlers with delegated handlers (no globals/back-compat).
   - Verify templates: templates/account/messages.php and templates/account/message.php.

3. Phase 2 — Match Status Modal and Set Match Status (Completed)
   - setMatchStatus and modal helpers modularized under src/js/features/match/.
   - Delegated handlers attached using data-action attributes for opening modal and saving match/rubber status.
   - Behavior parity verified for singles and rubbers; centralized error handling in place.

4. Phase 3 — Player Search (Completed)
- Player search extracted into src/js/features/player/player-search.js with delegated submit handler for #playerSearch.
- Inline handlers removed from templates/players.php; preserves URL pushState with ?q= and AJAX rendering into #searchResultsContainer.

5. Phase 4 — Tournament Partner Workflow (Completed)
- Partner modal open/save logic modularized in src/js/features/modals/partner-modal.js with delegated handlers ([data-action="open-partner-modal"], [data-action="set-partner"]).
- has-modal checkbox flow updated to call module directly (no globals) and template inline onclick removed from templates/event/partner-modal.php and templates/entry/entry-tournament.php.

6. Phase 5 — Pricing and Totals (Completed)
   - Extracted pricing helpers into src/js/features/pricing/pricing.js: setEventPrice, clearEventPrice, updateTotalPrice, with currency utilities in src/js/utils/currency.js.
   - Updated has-modal checkboxes and partner modal to call modular pricing (no globals) and wired initializePricing in src/js/index.js.

7. Phase 6 — Payments & Withdrawals (Completed)
   - Modularized payment status (src/js/features/payments/payment-status.js) with delegated handlers.
   - Modularized tournament withdrawals (src/js/features/withdrawals/tournament-withdrawal.js) with delegated handlers and centralized error handling.
   - Removed inline JS from templates/entry/entry-tournament.php and added data-action hooks.
   - Wired initializers in src/js/index.js; pricing totals update integrated.

8. Phase 7 — Teams & Ordering (Completed)
   - Modularize team selection, ordering, and management UIs.
   - Implemented team-order module with delegated handlers; removed inline JS from templates/team-order.php; wired initialization in src/js/index.js.

9. Phase 8 — Club Admin (Completed)
   - Modularized Club Admin (Roles) interactions under src/js/features/club/admin/club-roles.js with delegated handlers.
   - Updated templates/club/roles.php and templates/club/club-role-modal.php to remove inline JS and use data-action hooks.
   - Wired initializeClubAdmin() in src/js/index.js (on ready and after AJAX).
   - Enhancement: Added username autocomplete in Club Role modal (#userName) that looks up users by name (scoped by clubId) and auto-fills userId/contact fields.

10. Phase 9 — Printing Helpers (Completed)
    - Implemented modular printing under src/js/features/printing/ with print-utils.js, print-match-card.js, and print-scorecard.js.
    - Wired delegated handlers in initializePrinting():
      - [data-print-match-card] → opens match card print window and auto-prints.
      - Backward-compat: #printMatchCard with data-match-id supported.
    - Template updates: templates/match-teams.php now uses data-print-match-card and removed inline JS listener.

11. Phase 10 — Cleanup & Removal (In Progress)
    - Stage A completed:
      - Removed inline onclick handlers in public templates and replaced with modular delegated handlers:
        - templates/includes/nav-pills.php → data-action="switch-tab" (new module navigation/switch-tab.js).
        - templates/includes/team.php → removed inline JS; now uses data-action="open-team-edit-modal" and tabDataLink data-* attributes.
      - Added new JS module src/js/features/navigation/switch-tab.js and wired initializeSwitchTab() in src/js/index.js.
      - Verified tab data links use existing tabdata module.
    - Stage 1 completed:
      - Guarded legacy functions in js/racketmanager.js to avoid overwriting modular implementations (printScoreCard, playerSearch, partner modal/save, pricing, payments, withdrawals, team order/admin, club roles, entryRequest).
      - Deprecated legacy js/entry-link.js (file retained with no-op comment to avoid 404s if enqueued by older templates).
    - Stage 2 completed:
      - Modularized Match Options (schedule/switch/reset) under src/js/features/match/match-options.js with delegated handlers.
      - Removed inline JS listener from templates/match/match-header.php; rely on data attributes and delegated handler.
      - Wired initializeMatchOptions() in src/js/index.js.
    - Stage 3 completed:
      - Modularized Update Match Results under src/js/features/match/update-match-results.js with delegated handlers.
      - Updated templates/forms/match-input.php, templates/match-tournament.php, and templates/match-teams-result.php to remove inline onclick and use data-action="update-match-results".
      - Wired initializeUpdateMatchResults() in src/js/index.js.
      - Parity update (2025-10-23): aligned success handling with legacy — pre-request cleanup, splash/body visibility, inline success fade-out, and updates to #home_points/#away_points and winner highlighting.
    - Stage 4 — Completed (2025-10-22):
      - Modularized Set Match Date under src/js/features/match/set-match-date.js with delegated handler [data-action="set-match-date"].
      - Modularized Reset Match Result under src/js/features/match/reset-match-result.js with delegated handler [data-action="reset-match-result"].
      - Modularized Reset Match Scores under src/js/features/match/reset-match-scores.js with delegated handler [data-action="reset-match-scores"].
      - Added match header refresh helper src/js/features/match/match-header.js used by set/reset flows.
      - Wired initializers in src/js/index.js and updated templates/match-tournament.php to remove inline reset binding.
    - Stage 5 completed:
      - Removed inline JS from templates/match/match-option-modal.php and mapped action button to modular data-action values.
      - Added new module src/js/features/match/switch-home-away.js with delegated handler [data-action="switch-home-away"].
      - Wired initializeSwitchHomeAway() in src/js/index.js.
    - Stage 6 — Completed (2025-10-23):
      - Removed inline onclick from templates/tournament/withdrawal-modal.php and replaced with data-action="confirm-tournament-withdrawal" handled by the modular withdrawal feature.
      - Replaced inline JS in templates/team-details.php with data-action="open-team-edit-modal"; relies on Teams Admin module.
      - Replaced onclick="Racketmanager.updateTeam(...)" in templates/club/team-edit-modal.php and templates/club/team.php with data-action="update-team"; added new module src/js/features/teams/team-update.js and wired initializer in src/js/index.js (on ready and after AJAX).
      - Replaced onclick="Racketmanager.updateResults(...)" in templates/match-teams-result.php with data-action="update-match-results"; added wrapper support for data-action="update-team-result".
    - Stage 7 — Completed (2025-10-23):
      - Implemented Team Match Result wrapper and moved it under src/js/features/match/update-team-result.js (from teams/), leaving a thin re-export shim.
      - Added re-entrancy guard to prevent double submissions for team rubber updates; updated delegated initializer accordingly.
      - Tidied and aligned success handling for update-match-results.js (re-entrancy guard, robust payload parsing, winner/points/sets updates, splash/body visibility, inline fade-out parity).
    - Stage 8 — Completed (2025-10-23):
      - Modularised Club Player actions (Request/Remove).
      - Added new modules: src/js/features/club/club-player-request.js and src/js/features/club/club-player-remove.js.
      - Wired initializers in src/js/index.js (ready and after AJAX).
      - Refactored templates/club/players.php to replace inline onclick with delegated data-action hooks (club-player-request and club-player-remove).
      - Centralized error handling via handleAjaxError; preserved behavior parity (alerts, validation, row removal).
    - Stage C — Completed:
      - Added global flag `RACKETMANAGER_DISABLE_LEGACY` (default true) and a neutralizer block at end of legacy file to no‑op migrated legacy functions with console warnings. Updated constants to set the flag by default.
      - Guarded remaining legacy functions to avoid overwriting modular implementations.
    - Stage D — Completed (2025-10-22):
      - Decommissioned legacy enqueue by default. The legacy script js/racketmanager.js is no longer enqueued unless explicitly enabled via a PHP filter.
      - New filter: `racketmanager_enqueue_legacy` (default false). To temporarily roll back to legacy, add in theme/plugin: `add_filter('racketmanager_enqueue_legacy', '__return_true');`.
      - When legacy is explicitly enabled, we set `window.RACKETMANAGER_DISABLE_LEGACY = false` before loading the legacy file to re-enable legacy behavior.
    - Stage E — Completed (2025-10-23):
      - Removed remaining dead legacy frontend code from js/racketmanager.js, keeping only Racketmanager.loadingModal and the Stage C neutralizer block.
      - Legacy file is gated by Stage D and not enqueued by default; if explicitly enabled, neutralizer prevents collisions.
      - Performed a quick sanity check; modular features unaffected.
    - Stage F — Completed (2025-10-23): Fully decommission legacy enqueue in PHP
      - Removed racketmanager_enqueue_legacy filter usage and the conditional enqueue code path in include/class-racketmanager.php.
      - Verified ajax_var and locale_var inline config remain injected for the module bundle only.
      - Confirmed no other code paths enqueue the legacy file by default.
    - Stage G — Completed (2025-10-23): Final verification sweep (public + admin)
      - Verified public features: messages, printing, matches (status/options/date/reset/results), tournaments (entries, partner, withdrawals), players (search), pricing totals, favourites, navigation/tabdata — all passed with parity.
      - Verified admin‑adjacent features: teams (order, update, admin modal), club admin roles (modal + username lookup) — modals opened/saved and UI updated correctly.
      - Console sweep: no Stage C neutralizer warnings observed across tested pages; no legacy globals invoked.
      - Dynamic content: delegated handlers remained functional after AJAX injections; ajaxComplete re‑initializers verified where present.
    - Stage H — Completed (2025-10-23): Documentation updates
      - Updated docs/migration-plan.md to mark Stage E/F as Completed with dates; summarized removals and verification.
      - Updated docs/architecture.md with finalized “no legacy file” policy, delegated‑handler guidance, and note on rollback strategy removal.
    - Stage I — Completed (2025-10-23): Quality gates and housekeeping
      - Added ESLint configuration (.eslintrc.json) and ignore file at wp-content/plugins/racketmanager/.
      - Added Prettier configuration (.prettierrc) and ignore file.
      - Prepared CI configuration for JS linting by adding qodana-js.yaml (jetbrains/qodana-js) alongside existing qodana.yaml.
      - Added a smoke-test checklist at docs/tests.md for maintainers.
      - Verified src/js/index.js remains the single entry point; no legacy enqueue present.
    - Stage J — Rollout and monitoring
      - Tag a release; communicate deprecation/removal of legacy to stakeholders.
      - Monitor error logs/console post‑deploy; be ready with a hotfix branch if any missed inline handler surfaces.
    - Submission of changes for this issue
      - Implement Stage E edits (dead code deletions) and submit PR for review (✓ when done).
    - Next stages:
      - Identify and remove any remaining dead code blocks in js/racketmanager.js once verified unused across templates.
      - Final removal of legacy file once all features are confirmed migrated.

---

### Cross-Cutting Tasks
- Build/Packaging: ensure src/js/index.js remains the single entry; output to dist/js/racketmanager.js.
- Lint/Format: add CI gate (ESLint/Prettier) with consistent rules.
- Documentation: update docs/architecture.md as modules are added; keep this plan current.
- Accessibility: ensure new handlers preserve keyboard access and ARIA states.
- Performance: avoid duplicate bindings using namespaced jQuery events.

---

### Testing & Verification
- For each feature extraction:
  - Create a manual test checklist covering success, error, and edge cases.
  - Verify AJAX error rendering via centralized handler.
  - Validate no regressions in dynamic content (content loaded via AJAX still works due to delegated handlers).

---

### Rollout & Risk Mitigation
- Migrate feature-by-feature; keep legacy code until a feature is fully replaced.
- Avoid global namespace exposure; if necessary during transition, scope under a temporary shim and remove soon after.
- Telemetry remains off in production; can be enabled locally via:
  - window.RACKETMANAGER_CONFIG = { env: 'development', flags: { enableTelemetry: true, enablePerfMarks: true }, logging: { level: 'debug' } };

---

### Current Status (Updated)
- Phase 0: Completed.
- Phase 1 (Messages): Completed — inline handlers removed, delegated handlers added; templates updated.
- Phase 2 (Match Status): Completed — modal open/save via delegated handlers; singles and rubbers parity verified; no inline calls.
- Phase 3 (Player Search): Completed — delegated submit handler; inline script removed from templates/players.php; URL pushState preserved.
- Phase 4 (Partner Workflow): Completed — partner modal modularized; templates updated; no globals.
- Phase 5 (Pricing & Totals): Completed — modular pricing utilities; has-modal and partner flows updated; initializer wired.
- Phase 6 (Payments & Withdrawals): Completed — withdrawals and payments modularized; templates updated; initializers wired.
- Phase 7 (Teams & Ordering): Completed — team order/admin modularized; delegated handlers.
- Phase 8 (Club Admin): Completed — club roles modal open/save modularized; templates updated; initializer wired.
- Phase 9 (Printing Helpers): Completed — printing helpers modularized; templates updated; initializer wired.
- Phase 10 (Cleanup & Removal): In Progress — public inline handlers removed; multiple legacy functions guarded; match options/results/date/reset flows modularized; next stages focus on fully guarding/removing remaining legacy functions and decommissioning the legacy enqueue.

---

### Acceptance Criteria
- No remaining inline JS handlers in templates for migrated features.
- All migrated features initialized via src/js/index.js and delegated handlers.
- Legacy js/racketmanager.js no longer directly called by templates for migrated features.
- Centralized error handling used across features.
- Production telemetry disabled by default.



#### Housekeeping (2025-10-23)
- Moved module update-team-result from src/js/features/teams/ to src/js/features/match/ to keep all match-related flows co-located.
- Left a thin re-export shim at src/js/features/teams/update-team-result.js to preserve any older import paths.
- Updated src/js/index.js import to the new location.
