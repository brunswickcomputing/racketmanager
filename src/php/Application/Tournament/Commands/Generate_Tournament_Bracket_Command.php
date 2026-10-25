<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Commands;

use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;

/**
 * Command DTO for generating and setting tournament/championship bracket fixtures.
 */
readonly class Generate_Tournament_Bracket_Command {
    /**
     * @param int $league_id
     * @param int|string $season
     * @param string $action 'add' or 'replace'
     * @param array<string, mixed> $rounds Round fixture configurations
     * @param int|null $tournament_id
     */
    public function __construct(
        public int $league_id,
        public int|string $season,
        public string $action = 'add',
        public array $rounds = [],
        public ?int $tournament_id = null
    ) {
    }

    /**
     * Build command from POST / request array.
     *
     * @param array<string, mixed> $post
     * @param int|null $league_id
     * @param int|null $tournament_id
     * @param string|int|null $season
     * @return self
     */
    public static function from_post(
        array $post,
        ?int $league_id = null,
        ?int $tournament_id = null,
        string|int|null $season = null
    ): self {
        $resolved_league_id     = $league_id ?? ( isset( $post['league'] ) ? (int) $post['league'] : ( isset( $post['league_id'] ) ? (int) $post['league_id'] : 0 ) );
        $resolved_tournament_id = $tournament_id ?? ( isset( $post['tournament_id'] ) ? (int) $post['tournament_id'] : ( isset( $post['tournamentId'] ) ? (int) $post['tournamentId'] : null ) );
        $resolved_season        = $season ?? ( $post['season'] ?? '' );
        $action                 = isset( $post['action'] ) ? sanitize_text_field( wp_unslash( strval( $post['action'] ) ) ) : 'add';
        $rounds                 = isset( $post['rounds'] ) && is_array( $post['rounds'] ) ? $post['rounds'] : [];

        return new self(
            league_id: $resolved_league_id,
            season: $resolved_season,
            action: $action,
            rounds: $rounds,
            tournament_id: $resolved_tournament_id
        );
    }

    /**
     * Convert to Draw_Action_Request_DTO for downstream admin championship service.
     *
     * @return Draw_Action_Request_DTO
     */
    public function to_draw_action_dto(): Draw_Action_Request_DTO {
        return new Draw_Action_Request_DTO(
            tournament_id: $this->tournament_id ?? 0,
            league_id: $this->league_id,
            season: (string) $this->season,
            post: [
                'action' => $this->action,
                'rounds' => $this->rounds,
                'season' => (string) $this->season,
            ]
        );
    }
}
