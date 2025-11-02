### Inventory: include/ classes without PSR-4 counterparts under src/php/

This document captures the output of the audit that identified classes still only present under include/ and not yet available via PSR-4 paths under src/php/.

Notes
- The list excludes files that already have PSR-4 counterparts or bridges (e.g., Widget, most validators, shortcodes, AJAX controllers, and many domain models).
- Class names are shown with their fully qualified namespaces as declared in the include/ files today.

Admin (include/admin/*.php)
- include/admin/class-admin-championship.php — Racketmanager\admin\Admin_Championship
- include/admin/class-admin-club.php — Racketmanager\admin\Admin_Club
- include/admin/class-admin-competition.php — Racketmanager\admin\Admin_Competition
- include/admin/class-admin-cup.php — Racketmanager\admin\Admin_Cup
- include/admin/class-admin-display.php — Racketmanager\admin\Admin_Display
- include/admin/class-admin-event.php — Racketmanager\admin\Admin_Event
- include/admin/class-admin-finances.php — Racketmanager\admin\Admin_Finances
- include/admin/class-admin-import.php — Racketmanager\admin\Admin_Import
- include/admin/class-admin-index.php — Racketmanager\admin\Admin_Index
- include/admin/class-admin-league.php — Racketmanager\admin\Admin_League
- include/admin/class-admin-options.php — Racketmanager\admin\Admin_Options
- include/admin/class-admin-player.php — Racketmanager\admin\Admin_Player
- include/admin/class-admin-result.php — Racketmanager\admin\Admin_Result
- include/admin/class-admin-season.php — Racketmanager\admin\Admin_Season
- include/admin/class-admin-tournament.php — Racketmanager\admin\Admin_Tournament

Utilities/Services (include/*.php)
- include/class-championship.php — Racketmanager\Championship
- include/class-exporter.php — Racketmanager\Exporter
- include/class-login.php — Racketmanager\Login
- include/class-player-error.php — Racketmanager\Player_Error
- include/class-privacy-exporters.php — Racketmanager\Privacy_Exporters
- include/class-rest-resources.php — Racketmanager\Rest_Resources
- include/class-rest-routes.php — Racketmanager\Rest_Routes
- include/class-rewrites.php — Racketmanager\Rewrites
- include/class-schedule-round-robin.php — Racketmanager\Schedule_Round_Robin
- include/class-stripe-settings.php — Racketmanager\Stripe_Settings
- include/class-svg-icons.php — Racketmanager\SVG_Icons

Where to go from here
- Minimal bridges (safe, low-risk): For each class above, create a PSR-4 bridge at src/php/<path>.php that requires the include implementation and returns. Example:
  - src/php/admin/Admin_League.php → require_once RACKETMANAGER_PATH . 'include/admin/class-admin-league.php';
  - src/php/Privacy_Exporters.php → require_once RACKETMANAGER_PATH . 'include/class-privacy-exporters.php';
- Full relocation (higher effort): Move the class bodies into src/php/… and convert the include files into thin guarded shims using class_exists checks, matching the pattern already used for validators and domain models.

Verification checklist
- Run composer dump-autoload -o after adding bridges.
- Smoke test: admin pages that instantiate the above classes (menus and screens), and any hooks they register.
- Review logs for class-not-found errors or double-declarations (guards should prevent re-declare).
