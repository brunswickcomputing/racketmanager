<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Represents the title item data used when rendering a tournament match card.
 */
final readonly class Tournament_Match_Card_Title_Item_View_Model {
    /**
     * Creates a new instance with the provided name and optional link, tournament, and player identifiers.
     *
     * @param string          $name The display name.
     * @param string|null     $link The optional link value.
     * @param bool            $is_link Whether the name should be treated as a link.
     * @param int|string|null $tournament_id The optional tournament identifier.
     * @param int|string|null $player_id The optional player identifier.
     *
     * @return void
     */
    public function __construct(
        public string $name,
        public ?string $link = null,
        public bool $is_link = false,
        public int|string|null $tournament_id = null,
        public int|string|null $player_id = null
    ) {}
}
