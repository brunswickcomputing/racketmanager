<?php
declare( strict_types=1 );
/**
 * Sports registrar for Tennis (PSR-4)
 *
 * Registers the 'tennis' sport via the racketmanager_sports filter.
 * Classes for the sport (Competition_Tennis, League_Tennis) are PSR-4 and will
 * be autoloaded on demand by Composer.
 */

namespace Racketmanager\Sports;

// Register the Tennis sport label for selection in admin/settings.
\add_filter('racketmanager_sports', function(array $sports): array {
    $sports['tennis'] = \__('Tennis', 'racketmanager');
    return $sports;
});

// Import filter hooks for sport-specific custom fields
\add_filter('racketmanager_import_matches_tennis', function( array $custom, array $line, int $match_id, int $col ): array {
    return $custom;
}, 10, 4);

\add_filter('racketmanager_import_teams_tennis', function( array $custom, array $line, int $col ): array {
    return $custom;
}, 10, 3);
