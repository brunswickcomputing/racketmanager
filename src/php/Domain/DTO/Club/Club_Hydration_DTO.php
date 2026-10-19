<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Club;

/**
 * Data Transfer Object for hydrating a Club entity from database rows or input data.
 */
readonly class Club_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string $name
	 * @param string|null $shortcode
	 * @param string|null $type
	 * @param string|null $website
	 * @param string|null $contactno
	 * @param int|null $founded
	 * @param string|null $facilities
	 * @param string|null $address
	 * @param int $team_count
	 * @param int $player_count
	 * @param array $players
	 * @param array $teams
	 * @param array $matches
	 * @param array $results
	 * @param string|null $created_date
	 * @param string|null $removed_date
	 * @param bool $single
	 * @param array $entry
	 * @param array $competitions
	 * @param object|null $match_secretary
	 */
	public function __construct(
		public ?int $id = null,
		public string $name = '',
		public ?string $shortcode = null,
		public ?string $type = null,
		public ?string $website = null,
		public ?string $contactno = null,
		public ?int $founded = null,
		public ?string $facilities = null,
		public ?string $address = null,
		public int $team_count = 0,
		public int $player_count = 0,
		public array $players = [],
		public array $teams = [],
		public array $matches = [],
		public array $results = [],
		public ?string $created_date = null,
		public ?string $removed_date = null,
		public bool $single = false,
		public array $entry = [],
		public array $competitions = [],
		public ?object $match_secretary = null
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
		$name = (string) ( $data->name ?? '' );
		$shortcode = isset( $data->shortcode ) ? (string) $data->shortcode : null;
		$type = isset( $data->type ) ? (string) $data->type : null;
		$website = isset( $data->website ) ? (string) $data->website : null;
		$contactno = isset( $data->contactno ) ? (string) $data->contactno : null;
		$founded = isset( $data->founded ) && is_numeric( $data->founded ) ? (int) $data->founded : null;
		$facilities = isset( $data->facilities ) ? (string) $data->facilities : null;
		$address = isset( $data->address ) ? (string) $data->address : null;
		$team_count = isset( $data->team_count ) && is_numeric( $data->team_count ) ? (int) $data->team_count : 0;
		$player_count = isset( $data->player_count ) && is_numeric( $data->player_count ) ? (int) $data->player_count : 0;
		$players = isset( $data->players ) && is_array( $data->players ) ? $data->players : [];
		$teams = isset( $data->teams ) && is_array( $data->teams ) ? $data->teams : [];
		$matches = isset( $data->matches ) && is_array( $data->matches ) ? $data->matches : [];
		$results = isset( $data->results ) && is_array( $data->results ) ? $data->results : [];
		$created_date = isset( $data->created_date ) ? (string) $data->created_date : null;
		$removed_date = isset( $data->removed_date ) ? (string) $data->removed_date : null;
		$single = ! empty( $data->single );
		$entry = isset( $data->entry ) && is_array( $data->entry ) ? $data->entry : [];
		$competitions = isset( $data->competitions ) && is_array( $data->competitions ) ? $data->competitions : [];
		$match_secretary = isset( $data->match_secretary ) && is_object( $data->match_secretary ) ? $data->match_secretary : null;

		return new self(
			id: $id,
			name: $name,
			shortcode: $shortcode,
			type: $type,
			website: $website,
			contactno: $contactno,
			founded: $founded,
			facilities: $facilities,
			address: $address,
			team_count: $team_count,
			player_count: $player_count,
			players: $players,
			teams: $teams,
			matches: $matches,
			results: $results,
			created_date: $created_date,
			removed_date: $removed_date,
			single: $single,
			entry: $entry,
			competitions: $competitions,
			match_secretary: $match_secretary
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
