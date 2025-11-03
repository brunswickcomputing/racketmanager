<?php
/**
 * Sports registrar for Tennis (PSR-4)
 *
 * Registers the 'tennis' sport via the racketmanager_sports filter.
 * Classes for the sport (Competition_Tennis, League_Tennis) are PSR-4 and will
 * be autoloaded on demand by Composer.
 */

namespace Racketmanager\sports;

// Register the Tennis sport label for selection in admin/settings.
\add_filter('racketmanager_sports', function(array $sports): array {
    $sports['tennis'] = \__('Tennis', 'racketmanager');
    return $sports;
});
