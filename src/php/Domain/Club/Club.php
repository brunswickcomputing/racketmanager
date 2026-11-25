<?php
/**
 * Club API: Club class
 *
 * @package RacketManager
 * @subpackage Club
 */

declare( strict_types=1 );
namespace Racketmanager\Domain\Club;

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
    public private(set) ?int $id = null;

    /**
     * Match secretary
     *
     * @var object|null
     */
    public private(set) ?object $match_secretary = null;

    /**
     * Name
     *
     * @var string
     */
    public private(set) string $name = '';

    /**
     * Short code
     *
     * @var string|null
     */
    public private(set) ?string $shortcode = null;

    /**
     * Type
     *
     * @var string|null
     */
    public private(set) ?string $type = null;

    /**
     * Website
     *
     * @var string|null
     */
    public private(set) ?string $website = null;

    /**
     * Contact number
     *
     * @var string|null
     */
    public private(set) ?string $contactno = null;

    /**
     * Founded
     *
     * @var int|null
     */
    public private(set) ?int $founded = null;

    /**
     * Facilities
     *
     * @var string|null
     */
    public private(set) ?string $facilities = null;

    /**
     * Address
     *
     * @var string|null
     */
    public private(set) ?string $address = null;

    /**
     * Team count
     *
     * @var int
     */
    public private(set) int $team_count = 0;

    /**
     * Player count
     *
     * @var int
     */
    public private(set) int $player_count = 0;

    /**
     * Players variable
     *
     * @var array
     */
    public private(set) array $players = [];

    /**
     * Teams variable
     *
     * @var array
     */
    public private(set) array $teams = [];

    /**
     * Matches variable
     *
     * @var array
     */
    public private(set) array $matches = [];

    /**
     * Result variable
     *
     * @var array
     */
    public private(set) array $results = [];

    /**
     * Created date variable
     *
     * @var string|null
     */
    public private(set) ?string $created_date = null;

    /**
     * Removed date variable
     *
     * @var string|null
     */
    public private(set) ?string $removed_date = null;

    /**
     * Single instance variable
     *
     * @var bool
     */
    public private(set) bool $single = false;

    /**
     * Entry
     *
     * @var array
     */
    public private(set) array $entry = [];

    /**
     * Competitions variable
     *
     * @var array
     */
    public private(set) array $competitions = [];

    /**
     * Constructor
     *
     * @param Club_Hydration_DTO|null $dto Hydration DTO.
     */
    public function __construct( ?Club_Hydration_DTO $dto = null ) {
        if ( null === $dto ) {
            return;
        }

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
     * Create from a generic object (e.g. database row) via Club_Factory.
     *
     * @param object $row Database row object.
     * @return self
     */
    public static function from_object( object $row ): self {
        return Club_Factory::from_object( $row );
    }

    /**
     * Create from an array via Club_Factory.
     *
     * @param array $data Raw data array.
     * @return self
     */
    public static function from_array( array $data ): self {
        return Club_Factory::from_array( $data );
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
        return null !== $this->founded ? (string) $this->founded : null;
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
     * @param int $insert_id Insert ID.
     * @return void
     */
    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }

    /**
     * Set name
     *
     * @param string $name Club name.
     * @return void
     */
    public function set_name( string $name ): void {
        $this->name = $name;
    }

    /**
     * Set website
     *
     * @param string|null $website Website URL.
     * @return void
     */
    public function set_website( ?string $website ): void {
        $this->website = $website;
    }

    /**
     * Set type
     *
     * @param string|null $type Club type.
     * @return void
     */
    public function set_type( ?string $type ): void {
        $this->type = $type;
    }

    /**
     * Set address
     *
     * @param string|null $address Address.
     * @return void
     */
    public function set_address( ?string $address ): void {
        $this->address = $address;
    }

    /**
     * Set contact no
     *
     * @param string|null $contactno Contact number.
     * @return void
     */
    public function set_contact_no( ?string $contactno ): void {
        $this->contactno = $contactno;
    }

    /**
     * Set contact number alias.
     *
     * @param string|null $contactno Contact number.
     * @return void
     */
    public function set_contactno( ?string $contactno ): void {
        $this->set_contact_no( $contactno );
    }

    /**
     * Set founded
     *
     * @param string|int|null $founded Year founded.
     * @return void
     */
    public function set_founded( string|int|null $founded ): void {
        $this->founded = is_numeric( $founded ) ? (int) $founded : null;
    }

    /**
     * Set facilities
     *
     * @param string|null $facilities Facilities description.
     * @return void
     */
    public function set_facilities( ?string $facilities ): void {
        $this->facilities = $facilities;
    }

    /**
     * Set shortcode
     *
     * @param string|null $shortcode Short code.
     * @return void
     */
    public function set_shortcode( ?string $shortcode ): void {
        $this->shortcode = $shortcode;
    }

    /**
     * Set single instance flag.
     *
     * @param bool $single Single instance flag.
     * @return void
     */
    public function set_single( bool $single ): void {
        $this->single = $single;
    }

    /**
     * Set teams.
     *
     * @param array $teams Teams array.
     * @return void
     */
    public function set_teams( array $teams ): void {
        $this->teams = $teams;
    }

    /**
     * Set matches.
     *
     * @param array $matches Matches array.
     * @return void
     */
    public function set_matches( array $matches ): void {
        $this->matches = $matches;
    }

    /**
     * Set results.
     *
     * @param array $results Results array.
     * @return void
     */
    public function set_results( array $results ): void {
        $this->results = $results;
    }

    /**
     * Set players.
     *
     * @param array $players Players array.
     * @return void
     */
    public function set_players( array $players ): void {
        $this->players = $players;
    }

    /**
     * Set competitions.
     *
     * @param array $competitions Competitions array.
     * @return void
     */
    public function set_competitions( array $competitions ): void {
        $this->competitions = $competitions;
    }

    /**
     * Set entry.
     *
     * @param array $entry Entry array.
     * @return void
     */
    public function set_entry( array $entry ): void {
        $this->entry = $entry;
    }

    /**
     * Set match secretary.
     *
     * @param object|null $match_secretary Match secretary object.
     * @return void
     */
    public function set_match_secretary( ?object $match_secretary ): void {
        $this->match_secretary = $match_secretary;
    }

    /**
     * Set team count.
     *
     * @param int $team_count Team count.
     * @return void
     */
    public function set_team_count( int $team_count ): void {
        $this->team_count = $team_count;
    }

    /**
     * Set player count.
     *
     * @param int $player_count Player count.
     * @return void
     */
    public function set_player_count( int $player_count ): void {
        $this->player_count = $player_count;
    }

    /**
     * Set created date.
     *
     * @param string|null $created_date Created date.
     * @return void
     */
    public function set_created_date( ?string $created_date ): void {
        $this->created_date = $created_date;
    }

    /**
     * Set removed date.
     *
     * @param string|null $removed_date Removed date.
     * @return void
     */
    public function set_removed_date( ?string $removed_date ): void {
        $this->removed_date = $removed_date;
    }

    /**
     * Unserialize callback, safely ignoring stale dynamic properties from cache.
     *
     * @param array $data Serialized data.
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
