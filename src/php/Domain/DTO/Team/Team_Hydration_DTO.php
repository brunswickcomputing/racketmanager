<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Team;

/**
 * Data Transfer Object for hydrating a Team entity from database rows or input data.
 */
readonly class Team_Hydration_DTO {
    /**
     * Creates a team data transfer object with optional identifiers, metadata, player references, standings, and match data.
     *
     * @param int|null        $id Team identifier.
     * @param string          $title Team title or name.
     * @param string|null     $stadium Stadium name or reference.
     * @param string|int|null $profile Team profile identifier or reference.
     * @param int|null        $club_id Associated club identifier.
     * @param object|null     $club Associated club data.
     * @param string|null     $status Team status.
     * @param string|null     $player Primary player name or reference.
     * @param string|null     $player_1 First player name or reference.
     * @param string|null     $player_2 Second player name or reference.
     * @param int|null        $player_1_id First player identifier.
     * @param int|null        $player_2_id Second player identifier.
     * @param string          $type Team type value.
     * @param string|null     $team_type Team category or classification.
     * @param string|null     $home Home team indicator or reference.
     * @param array           $player_ids List of player identifiers.
     * @param array           $players List of player data.
     * @param string|null     $team_ref External team reference.
     * @param object|null     $info Additional team information.
     * @param array|object    $standings Standings data for the team.
     * @param array           $matches Match data associated with the team.
     *
     * @return void
     */
    public function __construct(
        public ?int $id = null,
        public string $title = '',
        public ?string $stadium = null,
        public string|int|null $profile = null,
        public ?int $club_id = null,
        public ?object $club = null,
        public ?string $status = null,
        public ?string $player = null,
        public ?string $player_1 = null,
        public ?string $player_2 = null,
        public ?int $player_1_id = null,
        public ?int $player_2_id = null,
        public string $type = '',
        public ?string $team_type = null,
        public ?string $home = null,
        public array $player_ids = [],
        public array $players = [],
        public ?string $team_ref = null,
        public ?object $info = null,
        public array|object $standings = [],
        public array $matches = []
    ) {
    }

    /**
     * Creates a team data transfer object from a generic object, normalizing supported values and applying defaults for missing or invalid data.
     *
     * @param object $data Source object containing team data.
     *
     * @return self Team data transfer object populated from the source object.
     */
    public static function from_object( object $data ): self {
        $id          = isset( $data->id ) && is_numeric( $data->id ) ? (int) $data->id : null;
        $title       = (string) ( $data->title ?? $data->name ?? '' );
        $stadium     = isset( $data->stadium ) ? (string) $data->stadium : null;
        $profile     = $data->profile ?? null;
        $club_id     = isset( $data->club_id ) && is_numeric( $data->club_id ) ? (int) $data->club_id : null;
        $club        = isset( $data->club ) && is_object( $data->club ) ? $data->club : null;
        $status      = isset( $data->status ) ? (string) $data->status : null;
        $player      = isset( $data->player ) ? (string) $data->player : null;
        $player_1    = isset( $data->player_1 ) ? (string) $data->player_1 : null;
        $player_2    = isset( $data->player_2 ) ? (string) $data->player_2 : null;
        $player_1_id = isset( $data->player_1_id ) && is_numeric( $data->player_1_id ) ? (int) $data->player_1_id : null;
        $player_2_id = isset( $data->player_2_id ) && is_numeric( $data->player_2_id ) ? (int) $data->player_2_id : null;
        $type        = (string) ( $data->type ?? '' );
        $team_type   = isset( $data->team_type ) ? (string) $data->team_type : null;
        $home        = isset( $data->home ) ? (string) $data->home : null;
        $player_ids  = isset( $data->player_ids ) && is_array( $data->player_ids ) ? $data->player_ids : [];
        $players     = isset( $data->players ) && is_array( $data->players ) ? $data->players : [];
        $team_ref    = isset( $data->team_ref ) ? (string) $data->team_ref : null;
        $info        = isset( $data->info ) && is_object( $data->info ) ? $data->info : null;
        $standings   = $data->standings ?? [];
        $matches     = isset( $data->matches ) && is_array( $data->matches ) ? $data->matches : [];

        return new self(
            id: $id,
            title: $title,
            stadium: $stadium,
            profile: $profile,
            club_id: $club_id,
            club: $club,
            status: $status,
            player: $player,
            player_1: $player_1,
            player_2: $player_2,
            player_1_id: $player_1_id,
            player_2_id: $player_2_id,
            type: $type,
            team_type: $team_type,
            home: $home,
            player_ids: $player_ids,
            players: $players,
            team_ref: $team_ref,
            info: $info,
            standings: $standings,
            matches: $matches
        );
    }

    /**
     * Creates an instance from an associative array of data.
     *
     * @param array $data Source data used to populate the instance.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }
}
