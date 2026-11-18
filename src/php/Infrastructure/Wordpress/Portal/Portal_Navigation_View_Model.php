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
     * Creates a new instance with the provided items.
     *
     * @param array $items Items used to initialize the instance.
     *
     * @return void
     */
    public function __construct(
        public array $items = [],
    ) {
    }

    /**
     * Return navigation items grouped into Portal_Navigation_Group objects preserving first-seen group order.
     *
     * @return array<int, Portal_Navigation_Group>
     */
    public function get_groups(): array {
        /** @var array<string, array<int, Portal_Navigation_Item>> $grouped */
        $grouped = [];
        foreach ( $this->items as $item ) {
            $group_key               = trim( $item->group );
            $grouped[ $group_key ][] = $item;
        }

        $groups = [];
        foreach ( $grouped as $group_label => $group_items ) {
            $groups[] = new Portal_Navigation_Group( (string) $group_label, $group_items );
        }

        return $groups;
    }
}
