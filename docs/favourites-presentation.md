# Public favourites presentation

## Live caller map

| Include | Parent templates | Production render boundary |
| --- | --- | --- |
| `includes/favourite.php` | `competition/events.php`, `event/standings.php`, `tournament/{draw,draws,event,events,player}.php` | Query handlers → shortcode adapters → `Shortcode_View_Presenter` → `Shortcode_Template_Renderer` |
| `includes/favourite-button.php` | `archive.php`, `team-details.php`, `players-list.php`, `includes/team.php` | League, event, club and general query handlers → `Shortcode_Template_Renderer` (including the nested player-search render in `Get_Players_Handler`) |
| `includes/favourite-button.php` | `includes/club-header.php` | Club profile/directory uses `Club_Shortcode_Presenter`; club subpages use `Shortcode_Template_Renderer` |
| `includes/favourites.php` → `includes/favourite-button.php` | `forms/form-favourites.php`, `players.php` | `Get_Favourites_Handler` / `Get_Players_Handler` → `Shortcode_Template_Renderer` |

The two render boundaries receive `Favourite_Presenter` from the container. The presenter obtains the actual current viewer's metadata through `User_Favourites_Gateway_Interface`; its WordPress implementation uses the security service and the existing `favourite-{type}` user-meta keys. It never hydrates entities just to determine selection, and it does not attach UI state to domain objects.

## View contract

- `favourite_controls` is an immutable `Favourite_Controls_View_Model`: authentication visibility, selected state indexed by type and ID, and the unselected state for that authenticated viewer. Each state carries precomputed tooltip, CSS classes and the existing `data-status` string (`'1'` or `''`).
- `favourite_rows_by_type` supplies immutable list rows for the account tabs. `favourite_rows` supplies the player directory's list; `Player_View_Model` explicitly identifies its `favourite_name` as `player`.
- Raw `favourite_types`, `favourites`, and other legacy variables remain available to theme overrides. Both flat and typed overrides receive the new presentation variables as well.
- The includes retain `favourite_id`, `favourite_type`, `hidden`, and the existing AJAX attributes. They perform no plugin, authentication, metadata, container, or URL-generator lookups.
- A manually constructed renderer may omit the presenter for noninteractive rendering, or supply explicit presented variables. A direct theme include must receive `favourite_controls` to display controls, and presented rows to display the favourites list. Missing state does not trigger a global/service fallback or pretend an item is unselected.
- Snapshots are rebuilt on each render, so a shared renderer does not retain another viewer's state or stale selections after a toggle. These display snapshots do not authorize requests; the existing AJAX nonce and request checks are unchanged.

## Match result compatibility boundary (not migrated here)

Source caller searches find no plugin producers for `match-teams-result.php` or `forms/match-input.php`. They remain legacy/manual-theme templates, including their existing global fallbacks. This pass completes the favourites subset, not those legacy form contracts.

The live `[fixture]` / `[match]` route is `Fixture_Shortcode_Adapter` → `Fixture_Presenter::map_to_detail_read_model()` / `render_detail()`. Editing selects `match/fragments/result-form.php`; it does not select either of the legacy files. Any subsequent form work should trace that live permission/read-model path rather than inventing a new caller for the legacy templates.

## Regression coverage

`Favourites_Render_Test` exercises the real container-configured renderer and WordPress metadata gateway, both control includes, all five favourite types, anonymous users, user switching, add/remove changes, immutable snapshots, exact list URLs, nonmutation of input objects, real handler-to-template flows, the separate club path, and flat/typed theme overrides. Existing league-team render regressions also use the explicit presenter and metadata contract.