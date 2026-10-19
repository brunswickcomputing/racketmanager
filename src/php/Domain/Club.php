<?php
/**
 * Club API: Club class
 *
 * @package RacketManager
 * @subpackage Club
 */

declare( strict_types=1 );

namespace Racketmanager\Domain;

use Racketmanager\Domain\DTO\Club\Club_Hydration_DTO;

/**
 * Class to implement the Club domain entity
 */
class Club {
	/**
	 * Id
	 *
	 * @var int|null
	 */
	public ?int $id = null;

	/**
	 * Match secretary
	 *
	 * @var object|null
	 */
	public ?object $match_secretary = null;

	/**
	 * Name
	 *
	 * @var string
	 */
	public string $name = '';

	/**
	 * Short code
	 *
	 * @var string|null
	 */
	public ?string $shortcode = null;

	/**
	 * Type
	 *
	 * @var string|null
	 */
	public ?string $type = null;

	/**
	 * Website
	 *
	 * @var string|null
	 */
	public ?string $website = null;

	/**
	 * Contact number
	 *
	 * @var string|null
	 */
	public ?string $contactno = null;

	/**
	 * Founded
	 *
	 * @var int|null
	 */
	public ?int $founded = null;

	/**
	 * Facilities
	 *
	 * @var string|null
	 */
	public ?string $facilities = null;

	/**
	 * Address
	 *
	 * @var string|null
	 */
	public ?string $address = null;

	/**
	 * Team count
	 *
	 * @var int
	 */
	public int $team_count = 0;

	/**
	 * Player count
	 *
	 * @var int
	 */
	public int $player_count = 0;

	/**
	 * Players variable
	 *
	 * @var array
	 */
	public array $players = [];

	/**
	 * Teams variable
	 *
	 * @var array
	 */
	public array $teams = [];

	/**
	 * Matches variable
	 *
	 * @var array
	 */
	public array $matches = [];

	/**
	 * Results variable
	 *
	 * @var array
	 */
	public array $results = [];

	/**
	 * Created date variable
	 *
	 * @var string|null
	 */
	public ?string $created_date = null;

	/**
	 * Removed date variable
	 *
	 * @var string|null
	 */
	public ?string $removed_date = null;

	/**
	 * Single instance variable
	 *
	 * @var bool
	 */
	public bool $single = false;

	/**
	 * Entry
	 *
	 * @var array
	 */
	public array $entry = [];

	/**
	 * Competitions variable
	 *
	 * @var array
	 */
	public array $competitions = [];

	/**
	 * Constructor
	 *
	 * @param object|Club_Hydration_DTO|null $club Club object or hydration DTO.
	 */
	public function __construct( ?object $club = null ) {
		if ( is_null( $club ) ) {
			return;
		}

		$dto = $club instanceof Club_Hydration_DTO ? $club : Club_Hydration_DTO::from_object( $club );

		$this->id              = $dto->id;
		$this->name            = $dto->name;
		$this->shortcode       = $dto->shortcode;
		$this->type            = $dto->type;
		$this->website         = $dto->website;
		$this->contactno       = $dto->contactno;
		$this->founded         = $dto->founded;
		$this->facilities      = $dto->facilities;
		$this->address         = $dto->address;
		$this->team_count      = $dto->team_count;
		$this->player_count    = $dto->player_count;
		$this->players         = $dto->players;
		$this->teams           = $dto->teams;
		$this->matches         = $dto->matches;
		$this->results         = $dto->results;
		$this->created_date    = $dto->created_date;
		$this->removed_date    = $dto->removed_date;
		$this->single          = $dto->single;
		$this->entry           = $dto->entry;
		$this->competitions    = $dto->competitions;
		$this->match_secretary = $dto->match_secretary;
	}

	/**
	 * Get id
	 *
	 * @return int|null
	 */
	public function get_id(): ?int {
		return $this->id;
	}

	/**
	 * Get name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Get website
	 *
	 * @return string|null
	 */
	public function get_website(): ?string {
		return $this->website;
	}

	/**
	 * Get type
	 *
	 * @return string|null
	 */
	public function get_type(): ?string {
		return $this->type;
	}

	/**
	 * Get address
	 *
	 * @return string|null
	 */
	public function get_address(): ?string {
		return $this->address;
	}

	/**
	 * Get contact no
	 *
	 * @return string|null
	 */
	public function get_contact_no(): ?string {
		return $this->contactno;
	}

	/**
	 * Get founded
	 *
	 * @return string|int|null
	 */
	public function get_founded(): string|int|null {
		return $this->founded !== null ? (string) $this->founded : null;
	}

	/**
	 * Get facilities
	 *
	 * @return string|null
	 */
	public function get_facilities(): ?string {
		return $this->facilities;
	}

	/**
	 * Get shortcode
	 *
	 * @return string|null
	 */
	public function get_shortcode(): ?string {
		return $this->shortcode;
	}

	/**
	 * Set id
	 *
	 * @param int $insert_id
	 * @return void
	 */
	public function set_id( int $insert_id ): void {
		$this->id = $insert_id;
	}

	/**
	 * Set name
	 *
	 * @param string $name
	 * @return void
	 */
	public function set_name( string $name ): void {
		$this->name = $name;
	}

	/**
	 * Set website
	 *
	 * @param string $website
	 * @return void
	 */
	public function set_website( string $website ): void {
		$this->website = $website;
	}

	/**
	 * Set type
	 *
	 * @param string $type
	 * @return void
	 */
	public function set_type( string $type ): void {
		$this->type = $type;
	}

	/**
	 * Set address
	 *
	 * @param string $address
	 * @return void
	 */
	public function set_address( string $address ): void {
		$this->address = $address;
	}

	/**
	 * Set contact no
	 *
	 * @param string $contactno
	 * @return void
	 */
	public function set_contact_no( string $contactno ): void {
		$this->contactno = $contactno;
	}

	/**
	 * Set founded
	 *
	 * @param string|int $founded
	 * @return void
	 */
	public function set_founded( string|int $founded ): void {
		$this->founded = is_numeric( $founded ) ? (int) $founded : null;
	}

	/**
	 * Set facilities
	 *
	 * @param string $facilities
	 * @return void
	 */
	public function set_facilities( string $facilities ): void {
		$this->facilities = $facilities;
	}

	/**
	 * Set shortcode
	 *
	 * @param string $shortcode
	 * @return void
	 */
	public function set_shortcode( string $shortcode ): void {
		$this->shortcode = $shortcode;
	}

	/**
	 * Unserialize callback, safely ignoring stale dynamic properties from cache.
	 *
	 * @param array $data
	 * @return void
	 */
	public function __unserialize( array $data ): void {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->$key = $value;
			}
		}
	}
}
