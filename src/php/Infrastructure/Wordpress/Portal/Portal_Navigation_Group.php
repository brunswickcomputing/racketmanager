<?php
declare( strict_types=1 );
/**
 * Portal Navigation Group DTO
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

/**
 * Represents a cluster of navigation items under a group title.
 */
final readonly class Portal_Navigation_Group {

    /**
     * @param string                             $label Group display title (empty string for ungrouped items).
     * @param array<int, Portal_Navigation_Item> $items Navigation items belonging to this group.
     */
    public function __construct(
        public string $label,
        public array $items = [],
    ) {
    }
}
