<?php
/**
 * Team API: Team class
 *
 * @package RacketManager
 * @subpackage Team
 */

declare( strict_types=1 );

namespace Racketmanager\Domain;

use Racketmanager\Domain\DTO\Team\Team_Hydration_DTO;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Util\Util;

/**
 * Class to implement the Team domain entity
 */
class Team {
	/**
	 * ID variable
	 *
	 * @var int|null
	 */
	public ?int $id = null;

	/**
	 * Title variable
	 *
	 * @var string
	 */
	public string $title = '';

	/**
	 * Stadium variable
	 *
	 * @var string|null
	 */
	public ?string $stadium = null;

	/**
	 * Roster variable
	 *
	 * @var mixed
	 */
	public mixed $roster = null;

	/**
	 * Profile variable
	 *
	 * @var string|int|null
	 */
	public string|int|null $profile = null;

	/**
	 * Club id variable
	 *
	 * @var int|null
	 */
	public ?int $club_id = null;

	/**
	 * Club object variable
	 *
	 * @var object|null
	 */
	public ?object $club = null;

	/**
	 * Status variable
	 *
	 * @var string|null
	 */
	public ?string $status = null;

	/**
	 * Player variable
	 *
	 * @var string|null
	 */
	public ?string $player = null;

	/**
	 * Player 1 name variable
	 *
	 * @var string|null
	 */
	public ?string $player_1 = null;

	/**
	 * Player 2 name variable
	 *
	 * @var string|null
	 */
	public ?string $player_2 = null;

	/**
	 * Player 1 id variable
	 *
	 * @var int|null
	 */
	public ?int $player_1_id = null;

	/**
	 * Player 2 ID variable
	 *
	 * @var int|null
	 */
	public ?int $player_2_id = null;

	/**
	 * Team type variable
	 *
	 * @var string
	 */
	public string $type = '';

	/**
	 * Team type variable
	 *
	 * @var string|null
	 */
	public ?string $team_type = null;

	/**
	 * Home variable
	 *
	 * @var string|null
	 */
	public ?string $home = null;

	/**
	 * Player id variable
	 *
	 * @var array
	 */
	public array $player_ids = [];

	/**
	 * Players variable
	 *
	 * @var array
	 */
	public array $players = [];

	/**
	 * Team ref variable
	 *
	 * @var string|null
	 */
	public ?string $team_ref = null;

	/**
	 * Info variable
	 *
	 * @var object|null
	 */
	public ?object $info = null;

	/**
	 * Standings variable
	 *
	 * @var array|object
	 */
	public array|object $standings = [];

	/**
	 * Matches variable
	 *
	 * @var array
	 */
	public array $matches = [];

	/**
	 * Constructor
	 *
	 * @param object|null $team Team object or hydration DTO.
	 */
	public function __construct( ?object $team = null ) {
		if ( is_null( $team ) ) {
			return;
		}

		$dto = $team instanceof Team_Hydration_DTO ? $team : Team_Hydration_DTO::from_object( $team );

		$this->id          = $dto->id;
		$this->title       = $dto->title;
		$this->stadium     = $dto->stadium;
		$this->roster      = $dto->roster;
		$this->profile     = $dto->profile;
		$this->club_id     = $dto->club_id;
		$this->club        = $dto->club;
		$this->status      = $dto->status;
		$this->player      = $dto->player;
		$this->player_1    = $dto->player_1;
		$this->player_2    = $dto->player_2;
		$this->player_1_id = $dto->player_1_id;
		$this->player_2_id = $dto->player_2_id;
		$this->type        = $dto->type;
		$this->team_type   = $dto->team_type;
		$this->home        = $dto->home;
		$this->player_ids  = $dto->player_ids;
		$this->players     = $dto->players;
		$this->team_ref    = $dto->team_ref;
		$this->info        = $dto->info;
		$this->standings   = $dto->standings;
		$this->matches     = $dto->matches;

		if ( ! empty( $this->title ) && str_contains( $this->title, '_' ) ) {
			$team_name = Util::generate_team_name( $this->title );
			if ( ! empty( $team_name ) ) {
				$this->team_ref = $this->title;
				$this->title    = $team_name;
			}
		}
	}

	/**
	 * Get row id
	 *
	 * @return int|null
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Set team id
	 *
	 * @param int $insert_id
	 * @return void
	 */
	public function set_id( int $insert_id ): void {
		$this->id = $insert_id;
	}

	/**
	 * Get team name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->title;
	}

	/**
	 * Set team name
	 *
	 * @param string $name
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->title = $name;
	}

	/**
	 * Set title
	 *
	 * @param string $title
	 * @return void
	 */
	public function set_title( string $title ): void {
		$this->title = $title;
	}

	/**
	 * Get club id
	 *
	 * @return int|null
	 */
	public function get_club_id(): ?int {
		return $this->club_id;
	}

	/**
	 * Set club id
	 *
	 * @param int|null $club_id
	 * @return void
	 */
	public function set_club_id( ?int $club_id ): void {
		$this->club_id = $club_id;
	}

	/**
	 * Get stadium name
	 *
	 * @return string|null
	 */
	public function get_stadium(): ?string {
		return $this->stadium;
	}

	/**
	 * Set stadium name
	 *
	 * @param string|null $stadium
	 * @return void
	 */
	public function set_stadium( ?string $stadium ): void {
		$this->stadium = $stadium;
	}

	/**
	 * Get team type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->type;
	}

	/**
	 * Set team type
	 *
	 * @param string $type
	 * @return void
	 */
	public function set_type( string $type ): void {
		$this->type = $type;
	}

	/**
	 * Get team type
	 *
	 * @return string|null
	 */
	public function get_team_type(): ?string {
		return $this->team_type;
	}

	/**
	 * Set team type
	 *
	 * @param string|null $team_type
	 * @return void
	 */
	public function set_team_type( ?string $team_type ): void {
		$this->team_type = $team_type;
	}

	/**
	 * Get team players
	 *
	 * @return array
	 */
	public function get_players(): array {
		return $this->players;
	}

	/**
	 * Set team players
	 *
	 * @param array $players
	 * @return void
	 */
	public function set_players( array $players ): void {
		$this->players = $players;
	}

	/**
	 * Set player IDs
	 *
	 * @param array $player_ids
	 * @return void
	 */
	public function set_player_ids( array $player_ids ): void {
		$this->player_ids = $player_ids;
	}

	/**
	 * Get status
	 *
	 * @return string|null
	 */
	public function get_status(): ?string {
		return $this->status;
	}

	/**
	 * Set status
	 *
	 * @param string|null $status
	 * @return void
	 */
	public function set_status( ?string $status ): void {
		$this->status = $status;
	}

	/**
	 * Get profile as typed backed Enum
	 *
	 * @return Team_Profile|null
	 */
	public function get_profile_enum(): ?Team_Profile {
		return null !== $this->profile ? Team_Profile::from_value( (int) $this->profile ) : null;
	}

	/**
	 * Set profile from typed backed Enum
	 *
	 * @param Team_Profile $profile
	 * @return void
	 */
	public function set_profile_enum( Team_Profile $profile ): void {
		$this->profile = $profile->value;
	}

	/**
	 * Check if team has player
	 *
	 * @param int $player_id
	 * @return bool
	 */
	public function has_player( int $player_id ): bool {
		if ( -1 === $this->id ) {
			return false;
		}
		foreach ( $this->players as $player ) {
			$id = is_object( $player ) && method_exists( $player, 'get_id' ) ? $player->get_id() : ( $player->id ?? null );
			if ( (int) $id === $player_id ) {
				return true;
			}
		}
		if ( ! empty( $this->player_ids ) ) {
			return in_array( $player_id, array_map( 'intval', $this->player_ids ), true );
		}
		return false;
	}
}
