<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a player in a draw.
 */
final readonly class Draw_Player_View_Model {
    public function __construct(
        public string $name,
        public ?string $url = null
    ) {}
}
