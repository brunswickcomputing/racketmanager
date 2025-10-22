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
    - Stage 1 completed:
      - Guarded legacy functions in js/racketmanager.js to avoid overwriting modular implementations (printScoreCard, playerSearch, partner modal/save, pricing, payments, withdrawals, team order/admin, club roles, entryRequest).
      - Deprecated legacy js/entry-link.js (file retained with no-op comment to avoid 404s if enqueued by older templates).
    - Stage 2 completed:
      - Modularized Match Options (schedule/switch/reset) under src/js/features/match/match-options.js with delegated handlers.
      - Removed inline JS listener from templates/match/match-header.php; rely on data attributes and delegated handler.
      - Wired initializeMatchOptions() in src/js/index.js.
    - Stage 3 completed:
      - Modularized Update Match Results under src/js/features/match/update-match-results.js with delegated handlers.
      - Updated templates/forms/match-input.php and templates/match-tournament.php to remove inline onclick and use data-action="update-match-results".
      - Wired initializeUpdateMatchResults() in src/js/index.js.
    - Stage 4 completed:
      - Modularized Set Match Date under src/js/features/match/set-match-date.js with delegated handler [data-action="set-match-date"].
      - Modularized Reset Match Result under src/js/features/match/reset-match-result.js with delegated handler [data-action="reset-match-result"].
      - Modularized Reset Match Scores under src/js/features/match/reset-match-scores.js with delegated handler [data-action="reset-match-scores"].
      - Added match header refresh helper src/js/features/match/match-header.js used by set/reset flows.
      - Wired initializers in src/js/index.js and updated templates/match-tournament.php to remove inline reset binding.
    - Stage 5 completed:
      - Removed inline JS from templates/match/match-option-modal.php and mapped action button to modular data-action values.
      - Added new module src/js/features/match/switch-home-away.js with delegated handler [data-action="switch-home-away"].
      - Wired initializeSwitchHomeAway() in src/js/index.js.
    - Next stages:
      - Guard or remove legacy Racketmanager.updateMatchResults, setMatchDate, resetMatchResult, resetMatchScores, matchHeader; add thin shims delegating to modular implementations if needed.
      - Identify and remove any remaining dead code blocks in js/racketmanager.js once verified unused across templates.
      - Final removal of legacy file once all features are confirmed migrated.
    - Cleanup progress:
      - Removed legacy fallback in src/js/features/match/reset-match-result.js for resetting visible match scores; now relies solely on modular resetMatchScoresByFormId for known form candidates.
      - Guarded legacy functions in js/racketmanager.js to avoid overwriting modular implementations: updateMatchResults, setMatchDate, resetMatchResult, resetMatchScores, matchHeader, matchOptions, switchHomeAway.

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
- Phase 7 (Teams & Ordering): Completed — team order/admin modularized; delegated handlers.
- Phase 6 (Payments & Withdrawals): Completed — withdrawals and payments modularized; templates updated; initializers wired.
- Phase 8 (Club Admin): Completed — club roles modal open/save modularized; templates updated; initializer wired.
- Next focus: Phase 9 (Printing Helpers) planning/implementation.

---

### Acceptance Criteria
- No remaining inline JS handlers in templates for migrated features.
- All migrated features initialized via src/js/index.js and delegated handlers.
- Legacy js/racketmanager.js no longer directly called by templates for migrated features.
- Centralized error handling used across features.
- Production telemetry disabled by default.
