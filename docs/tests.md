# RacketManager — Smoke Test Checklist

This checklist helps maintainers verify core functionality after changes. It focuses on happy paths plus key error states and dynamic content.

Note: All interactions should be driven by delegated handlers (data-action, document-level bindings). No inline JS should be present.

## Public Features

- Messages
  - Click a message in the list -> detail loads in #message_detail; unread/read counters update
  - Delete single message (Delete button in detail) -> item hidden; confirmation shows
  - Delete multiple messages via toolbar -> selected type hidden; counters updated

- Matches
  - Match Options (header menu) opens modal; try schedule, switch home/away, and reset result
  - Set Match Date -> success alert shows; header date updates
  - Reset Match Result -> success alert; header refreshes; any visible score inputs cleared
  - Update Match Results (singles) -> success alert; home/away points updated; winner highlighted; sets updated
  - Print match card -> popup opens with styled scorecard (disable popup blocker)

- Team Matches (Rubbers)
  - Update Team Result -> success alert; per-rubber winners/players/sets update; button cannot double-submit

- Tournament Entry
  - Select singles event -> totals update
  - Select doubles event -> partner modal opens; save partner -> partner fields update; totals update
  - Withdraw -> confirmation modal; confirm -> events unchecked; totals recalculated; success alert

- Players Page
  - Search submits via AJAX; results render in #searchResultsContainer; URL updates with ?q=

- Favourites / Navigation
  - Favourite toggle works; Switch Tab buttons (list/grid) switch layout; Tab Data links load content via AJAX

## Admin-Adjacent

- Team Order
  - Select club + event -> team players list loads
  - Validate players -> status classes/messages update; Set Players shows when indicated

- Teams Admin
  - Team edit modal opens from data-action button
  - Update team -> success alert; captain/contact summary fields refresh

- Club Admin (Roles)
  - Open role modal from roles list
  - Username lookup autocomplete suggests users and fills hidden/user contact fields
  - Save role -> success alert in modal

## Error Handling

- Force an AJAX error (e.g., invalid nonce) -> centralized error handler renders messages; field validation marks inputs via is-invalid classes

## Dynamic Content

- After any AJAX content injection, ensure delegated handlers still operate (e.g., modals, team lists). ajaxComplete initializers should keep direct-bound widgets working.

## Accessibility

- Buttons/links are keyboard accessible; focus outlines present; ARIA roles maintained on alerts (role=alert) and modals.
