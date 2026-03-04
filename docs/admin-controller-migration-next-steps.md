# Admin Controller Migration — Next Steps (Tournaments: draw/match/matches/information)

This document captures the immediate follow-on work after migrating the tournament admin views to the controller-service + dispatcher + PRG/flash pattern.

## Scope

Applies to these admin views:

- `view=draw` and `view=setup-event`
- `view=match`
- `view=matches`
- `view=information`

## Current target architecture (reference)

For each view:

1. **Admin bridge** (`Admin_Tournament`) is a thin router:
   - Pop flash messages
   - Delegate to controller-service
   - If controller returns `redirect`, store message to flash + redirect (PRG)
   - Render template from view model

2. **Controller-service** parses request + orchestrates:
   - Build DTOs
   - Call dispatcher(s)
   - Create view model for GET rendering

3. **Action dispatcher** performs:
   - Action detection (policy / resolver)
   - Security checks via `Action_Guard_Interface`
   - Calls application services
   - Returns response DTO

4. **PRG + user-meta flash**:
   - POST → redirect to GET
   - Message shown once



### 1) Confirm POST detection + redirect URL correctness (match & matches)

The match editing template posts with:

- `updateLeague=match`
- `racketmanager_nonce` with nonce action `racketmanager_manage-matches`

Verify:

- **`view=match` POST** redirects back to *the same match* edit URL:
  - preserves `tournament`, `league`, `final`, `edit`
- **`view=matches` POST** redirects back to the correct finals context:
  - preserves `tournament`, `league_id`, `final`

If there are additional query params that affect rendering (e.g. `leg`, `match_day`, `mode`), ensure redirects preserve them as well.

Acceptance:
- Refreshing after a POST must not re-submit changes.
- The user lands back on the same screen context they submitted from.

### 2) Make redirect URLs consistent and centralized

Create a small helper (bridge-level) for building redirect URLs for match/matches views, similar to the existing draw helper.

Acceptance:
- Redirect URL construction is not duplicated across methods.
- Redirect does not accidentally inherit stale `$_GET['view']`.

### 3) Add unit tests (no WordPress bootstrap required)

Add a minimal set of PHPUnit unit tests that:

- Assert dispatcher invokes the correct handler for a known action payload.
- Assert unknown payload is a no-op (no handler calls, no guard calls).
- Assert controller returns `redirect` on POST and `view_model` on GET.

Guideline:
- Prefer interface injection (handler + guard) to avoid WordPress runtime dependencies in unit tests.
## Next steps (ordered)
### 4) Remove legacy code paths after parity is confirmed

Once the new controllers are confirmed in staging:

- Delete or disable the old in-method legacy branches in `Admin_Tournament` for:
  - match/matches/information
- Keep the bridge methods as thin delegates only.

Acceptance:
- No duplicate “old and new” implementations of the same view remain.

### 5) Standardize view model usage in templates

Longer-term cleanup:

- Prefer templates accepting `$vm` (single variable) rather than expanding many local variables.
- Where templates require field-level errors, use an `Error_Bag` (map: `field => message`) rather than validator-shaped objects.

Acceptance:
- Templates do not depend on legacy validator objects or array_search patterns.
- Field error access is uniform and typed.

### 6) Extend the pattern to remaining `Admin_Tournament` views

Candidates after these three views are stable:

- `view=contact`
- `view=teams`
- any remaining POST-heavy views

Approach:
- Migrate one view at a time.
- Introduce dispatcher only if the view has multiple POST intents.
- Use PRG for any action that triggers an email, notification, or mutation.

## Notes / conventions

- All nonce + capability checks must flow through `Action_Guard_Interface`.
- Application services should stay “clean” (no nonce/cap checks, no output).
- Prefer a single “request DTO” per view if multiple POST intents exist.

## Smoke-test checklist (recommended)

After changes:

- `draw`: all actions still work and redirect back to correct tab
- `setup-event`: match setup actions redirect correctly
- `matches`: bulk edit finals matches → redirect back to same finals list
- `match`: edit one match → redirect back to same match edit screen
- `information`: update and notify finalists → redirect and show flash message once

See also: `docs/tests.md`.
