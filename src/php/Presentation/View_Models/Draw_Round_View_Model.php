<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model representing a round column in a knockout draw.
 */
final readonly class Draw_Round_View_Model {
    /**
     * @param object|null $final        Round data containing fixtures and metadata.
     * @param int|null    $f            1-based round index.
     * @param bool        $last_round   Whether this round is the final round.
     * @param string      $player_class CSS class applied to player elements.
     */
    public function __construct(
        // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.finalFound
        public ?object $final = null,
        public ?int $f = null,
        public bool $last_round = false,
        public string $player_class = '',
    ) {}
}
