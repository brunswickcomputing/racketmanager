<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a single score cell (set games or tie points) in a draw row.
 */
final readonly class Draw_Set_Score_View_Model {
    public function __construct(
        public string $games = '',
        public bool $is_winner = false,
        public ?string $winner_class = null,
        public bool $has_tiebreak = false,
        public string $tiebreak = ''
    ) {}
}
