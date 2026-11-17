<?php
declare( strict_types=1 );
/**
 * Portal Navigation Item
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Portal
 */

namespace Racketmanager\Infrastructure\Wordpress\Portal;

/**
 * Value object representing a primary navigation entry in the portal shell.
 */
final readonly class Portal_Navigation_Item {

    /**
     * @param string $slug      Section slug identifier.
     * @param string $title     Navigation label.
     * @param string $url       Target URL.
     * @param bool   $is_active Whether this item corresponds to the active section.
     * @param bool   $is_ported Whether this section is rendered in the portal or links to wp-admin.
     */
    public function __construct(
        public string $slug,
        public string $title,
        public string $url,
        public bool $is_active = false,
        public bool $is_ported = false,
    ) {
    }
}
