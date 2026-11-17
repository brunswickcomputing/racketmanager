<?php
declare( strict_types=1 );
/**
 * Portal Navigation View Model
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

/**
 * View model for portal navigation bar.
 */
final readonly class Portal_Navigation_View_Model {

    /**
     * @param array<int, Portal_Navigation_Item> $items
     */
    public function __construct(
        public array $items = [],
    ) {
    }
}
