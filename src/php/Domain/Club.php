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
final class Club {
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
    public ?object $match_secretary;
    /**
     * Name
     *
     * @var string
     */
    public string $name;
    /**
     * Short code
     *
     * @var ?string
     */
    public ?string $shortcode = null;
    /**
     * Type
     *
     * @var string
     */
    public string $type;
    /**
     * Website
     *
     * @var string
     */
    public string $website;
    /**
     * Contact number
     *
     * @var string
     */
    public string $contactno;
    /**
     * Founded
     *
     * @var int|null
     */
    public ?int $founded;
    /**
     * Facilities
     *
     * @var string
     */
    public string $facilities;
    /**
     * Address
     *
     * @var string
     */
    public string $address;
    /**
     * Number of players.
     *
     * @var int
     */
    public int $num_players;
    /**
     * Url link.
     *
     * @var string
     */
    public string $link;
    /**
     * Team count
     *
     * @var int
     */
    public int $team_count;
    /**
     * Player count
     *
     * @var int
     */
    public int $player_count;
    /**
     * Players variable
     *
     * @var array
     */
    public array $players;
    /**
     * Teams variable
     *
     * @var array
     */
    public array $teams;
    /**
     * Matches variable
     *
     * @var array
     */
    public array $matches;
    /**
     * Results variable
     *
     * @var array
     */
    public array $results;
    /**
     * Created date variable
     *
     * @var string|null
     */
    public ?string $created_date;
    /**
     * Removed date variable
     *
     * @var string|null
     */
    public ?string $removed_date;
    /**
     * Player variable
     *
     * @var object
     */
    public object $player;
    /**
     * Single instance variable
     *
     * @var boolean
     */
    public bool $single;
    /**
     * Invoices variable
     *
     * @var array
     */
    public array $invoices;
    /**
     * Invoice variable
     *
     * @var object
     */
    public object $invoice;
    /**
     * Entry
     *
     * @var array
     */
    public array $entry;
    /**
     * Competitions variable
     *
     * @var array
     */
    public array $competitions;
    /**
     * Competition variable
     *
     * @var object
     */
    public object $competition;
    /**
     * Team variable
     *
     * @var object
     */
    public object $team;
    /**
     * Event variable
     *
     * @var object
     */
    public object $event;
    /**
     * Player stats variable
     *
     * @var array
     */
    public array $player_stats;
    /**
     * Player club player id
     *
     * @var int
     */
    public int $club_player_id;
    /**
     * Constructor
     *
     * @param object|null $club Club object.
     */
    public function __construct( ?object $club = null ) {
        if ( ! is_null( $club ) ) {
            foreach ( get_object_vars( $club ) as $key => $value ) {
                $this->$key = $value;
            }

            $this->link = '/clubs/' . seo_url( $this->shortcode ) . '/';
        }
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
     * Get link
     *
     * @return string|null
     */
    public function get_link(): ?string{
        return $this->link;
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

    /**
     * Check if a player is a captain
     *
     * @param int $player player id.
     * @return string|null
     */
    public function is_player_captain( int $player ): ?string {
        global $wpdb;
        return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT count(*) FROM $wpdb->racketmanager_team_events te, $wpdb->racketmanager_teams t, $wpdb->racketmanager_clubs c WHERE c.`id` = %d AND c.`id` = t.`club_id` AND (t.`team_type` IS NULL OR t.`team_type` != 'P') AND t.`id` = te.`team_id` AND te.`captain` = %d",
                $this->id,
                $player
            )
        );
    }
    /**
     * Can user update players.
     * @return bool
     */
    public function can_user_update_players(): bool {
        global $racketmanager;
        $user_can_update = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_can_update = true;
            } else {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( intval( $this->match_secretary->id ) === $userid ) {
                    $user_can_update = true;
                } elseif ( $this->is_player_captain( $userid ) ) {
                    $options = $racketmanager->get_options( 'rosters' );
                    if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] ) {
                        $user_can_update = true;
                    }
                }
            }
        }
        return $user_can_update;
    }
    /**
     * Can a user update as a team captain in addition to as match secretary or admin user?
     * @return bool
     */
    public function can_user_update_as_captain(): bool {
        $user_can_update     = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_can_update = true;
            } else {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( intval( $this->match_secretary->id ) === $userid || $this->is_player_captain( $userid ) ) {
                    $user_can_update = true;
                }
            }
        }
        return $user_can_update;
    }
    /**
     * Can user update as match secretary or admin user?
     * @return bool
     */
    public function can_user_update(): bool {
        $user_can_update     = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_can_update = true;
            } else {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( intval( $this->match_secretary->id ) === $userid ) {
                    $user_can_update = true;
                }
            }
        }
        return $user_can_update;
    }
}
