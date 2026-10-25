<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Component;

use Racketmanager\RacketManager;

/**
 * Interface for coordinating frontend component initialization and registrations.
 */
interface Component_Coordinator_Interface {

    /**
     * Initialize all components, AJAX actions, REST routes, and shortcode adapters.
     *
     * @param RacketManager $app
     */
    public function init_components( RacketManager $app ): void;

    /**
     * Handle export requests if present in the current request.
     *
     * @param RacketManager $app
     */
    public function handle_exports( RacketManager $app ): void;
}
