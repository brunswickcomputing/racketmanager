<?php
/**
 * Club API: Club class (moved to PSR-4)
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club
 */

namespace Racketmanager\Domain;

use function Racketmanager\seo_url;

/**
 * Class to implement the Club object
 */
class Club {
    /**
     * Id
     *
     * @var ?int
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
     * @var ?string
     */
    public ?string $shortcode = null;
    /**
     * Type
     *
     * @var ?string
     */
    public ?string $type = null;
    /**
     * Website
     *
     * @var ?string
     */
    public ?string $website = null;
    /**
     * Contact number
     *
     * @var ?string
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
     * @var ?string
     */
    public ?string $facilities = null;
    /**
     * Address
     *
     * @var ?string
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
    public array $players = array();
    /**
     * Teams variable
     *
     * @var array
     */
    public array $teams = array();
    /**
     * Matches variable
     *
     * @var array
     */
    public array $matches = array();
    /**
     * Results variable
     *
     * @var array
     */
    public array $results = array();
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
     * Player variable
     *
     * @var ?object
     */
    public ?object $player = null;
    /**
     * Single instance variable
     *
     * @var boolean
     */
    public bool $single = false;
    /**
     * Entry
     *
     * @var array
     */
    public array $entry = array();
    /**
     * Competitions variable
     *
     * @var array
     */
    public array $competitions = array();
    /**
     * Competition variable
     *
     * @var ?object
     */
    public ?object $competition = null;
    /**
     * Team variable
     *
     * @var ?object
     */
    public ?object $team = null;
    /**
     * Event variable
     *
     * @var ?object
     */
    public ?object $event = null;
    /**
     * Player stats variable
     *
     * @var array
     */
    public array $player_stats = array();
    /**
     * Constructor
     *
     * @param object|null $club Club object.
     */
    public function __construct( ?object $club = null ) {
        if ( is_null( $club ) ) {
            return;
        }
        $this->id         = isset( $club->id ) ? (int) $club->id : null;
        $this->name       = (string) ( $club->name ?? '' );
        $this->website    = $club->website ?? null;
        $this->type       = $club->type ?? null;
        $this->address    = $club->address ?? null;
        $this->contactno  = $club->contactno ?? null;
        $this->founded    = isset( $club->founded ) && is_numeric( $club->founded ) ? (int) $club->founded : null;
        $this->facilities = $club->facilities ?? null;
        $this->shortcode  = $club->shortcode ?? null;
    }

    /**
     * Get id
     *
     * @return int|null
     */
    public function get_id(): ?int{
        return $this->id;
    }
    /**
     * Get name
     *
     * @return string|null
     */
    public function get_name(): ?string{
        return $this->name;
    }
    /**
     * Get website
     *
     * @return string|null
     */
    public function get_website(): ?string{
        return $this->website;
    }
    /**
     * Get type
     *
     * @return string|null
     */
    public function get_type(): ?string{
        return $this->type;
    }
    /**
     * Get address
     *
     * @return string|null
     */
    public function get_address(): ?string{
        return $this->address;
    }
    /**
     * Get contact no
     *
     * @return string|null
     */
    public function get_contact_no(): ?string{
        return $this->contactno;
    }
    /**
     * Get founded
     *
     * @return string|null
     */
    public function get_founded(): ?string{
        return $this->founded;
    }
    /**
     * Get facilities
     *
     * @return string|null
     */
    public function get_facilities(): ?string{
        return $this->facilities;
    }
    /**
     * Get shortcode
     *
     * @return string|null
     */
    public function get_shortcode(): ?string{
        return $this->shortcode;
    }

    /**
     * Set id
     *
     * @param int $insert_id
     *
     * @return void
     */
    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }
    /**
     * Set name
     *
     * @param string $name
     */
    public function set_name( string $name ): void {
        $this->name = $name;
    }
    /**
     * Set name website
     *
     * @param string $website
     */
    public function set_website( string $website ): void {
        $this->website = $website;
    }
    /**
     * Set type
     *
     * @param string $type
     */
    public function set_type( string $type ): void {
        $this->type = $type;
    }
    /**
     * Set address
     *
     * @param string $address
     */
    public function set_address( string $address ): void {
        $this->address = $address;
    }
    /**
     * Set contact no
     *
     * @param string $contactno
     */
    public function set_contact_no( string $contactno ): void {
        $this->contactno = $contactno;
    }
    /**
     * Set founded
     *
     * @param string $founded
     */
    public function set_founded( string $founded ): void {
        $this->founded = $founded;
    }
    /**
     * Set facilities
     *
     * @param string $facilities
     */
    public function set_facilities( string $facilities ): void {
        $this->facilities = $facilities;
    }
    /**
     * Set shortcode
     *
     * @param string $shortcode
     */
    public function set_shortcode( string $shortcode ): void {
        $this->shortcode = $shortcode;
    }

}
