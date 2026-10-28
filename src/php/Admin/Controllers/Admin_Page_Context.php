<?php
declare( strict_types=1 );
/**
 * Admin Page Context
 *
 * Encapsulates the admin screen page and target type context for shared controllers.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

final readonly class Admin_Page_Context {

    public function __construct(
        public string $page,
        public string $target_type
    ) {
    }

    public static function league(): self {
        return new self( 'racketmanager-leagues', 'league' );
    }

    public static function cup(): self {
        return new self( 'racketmanager-cups', 'cup' );
    }

    public static function tournament(): self {
        return new self( 'racketmanager-tournaments', 'tournament' );
    }
}
