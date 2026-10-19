<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Team;

/**
 * Data Transfer Object for hydrating a Team entity from database rows or input data.
 */
readonly class Team_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string $title
	 * @param string|null $stadium
	 * @param mixed $roster
	 * @param string|int|null $profile
	 * @param int|null $club_id
	 * @param object|null $club
	 * @param string|null $status
	 * @param string|null $player
	 * @param string|null $player_1
	 * @param string|null $player_2
	 * @param int|null $player_1_id
	 * @param int|null $player_2_id
	 * @param string $type
	 * @param string|null $team_type
	 * @param string|null $home
	 * @param array $player_ids
	 * @param array $players
	 * @param string|null $team_ref
	 * @param object|null $info
	 * @param array|object $standings
	 * @param array $matches
	 */
	public function __construct(
		public ?int $id = null,
		public string $title = '',
		public ?string $stadium = null,
		public mixed $roster = null,
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
	 * Create from a generic object (e.g., database row).
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$id = isset( $data->id ) && is_numeric( $data->id ) ? (int) $data->id : null;
		$title = (string) ( $data->title ?? $data->name ?? '' );
		$stadium = isset( $data->stadium ) ? (string) $data->stadium : null;
		$roster = $data->roster ?? null;
		$profile = $data->profile ?? null;
		$club_id = isset( $data->club_id ) && is_numeric( $data->club_id ) ? (int) $data->club_id : null;
		$club = isset( $data->club ) && is_object( $data->club ) ? $data->club : null;
		$status = isset( $data->status ) ? (string) $data->status : null;
		$player = isset( $data->player ) ? (string) $data->player : null;
		$player_1 = isset( $data->player_1 ) ? (string) $data->player_1 : null;
		$player_2 = isset( $data->player_2 ) ? (string) $data->player_2 : null;
		$player_1_id = isset( $data->player_1_id ) && is_numeric( $data->player_1_id ) ? (int) $data->player_1_id : null;
		$player_2_id = isset( $data->player_2_id ) && is_numeric( $data->player_2_id ) ? (int) $data->player_2_id : null;
		$type = (string) ( $data->type ?? '' );
		$team_type = isset( $data->team_type ) ? (string) $data->team_type : null;
		$home = isset( $data->home ) ? (string) $data->home : null;
		$player_ids = isset( $data->player_ids ) && is_array( $data->player_ids ) ? $data->player_ids : [];
		$players = isset( $data->players ) && is_array( $data->players ) ? $data->players : [];
		$team_ref = isset( $data->team_ref ) ? (string) $data->team_ref : null;
		$info = isset( $data->info ) && is_object( $data->info ) ? $data->info : null;
		$standings = $data->standings ?? [];
		$matches = isset( $data->matches ) && is_array( $data->matches ) ? $data->matches : [];

		return new self(
			id: $id,
			title: $title,
			stadium: $stadium,
			roster: $roster,
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
	 * Create from an array.
	 *
	 * @param array $data
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return self::from_object( (object) $data );
	}
}
