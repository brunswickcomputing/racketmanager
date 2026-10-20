<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a fixture row in a knockout draw.
 */
final readonly class Draw_Fixture_View_Model {
    public function __construct(
        public string $match_link = '',
        public ?Draw_Opponent_View_Model $home = null,
        public ?Draw_Opponent_View_Model $away = null,
        public bool $skip_render = false
    ) {}
}
