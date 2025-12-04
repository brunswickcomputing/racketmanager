<?php
/**
 * Club API: Club class (moved to PSR-4)
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club
 */

namespace Racketmanager\Domain;

use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_team;
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
     * Create team in database
     *
     * @param string $type type of team to create.
     * @return object|boolean
     */
    public function add_team( string $type ): object|bool {
        global $racketmanager;

        $type_name = match (substr($type, 0, 1)) {
            'B' => 'Boys',
            'G' => 'Girls',
            'W' => 'Ladies',
            'M' => 'Mens',
            'X' => 'Mixed',
            default => 'error',
        };

        if ( 'error' === $type_name ) {
            $racketmanager->set_message( __( 'Type not selected', 'racketmanager' ), true );
            return false;
        }
        $team_count = $this->get_teams( array( 'count' => true, 'type' => $type ) );
        ++$team_count;
        $team          = new stdClass();
        $team->title   = $this->shortcode . ' ' . $type_name . ' ' . $team_count;
        $team->stadium = $this->name;
        $team->club_id = $this->id;
        $team->type    = $type;
        return new Team( $team );
    }
    /**
     * Get teams for club
     *
     * @param array $args query arguments.
     *
     * @return array|int
     */
    public function get_teams( array $args = array() ): array|int {
        global $wpdb;

        $defaults = array(
            'count'   => false,
            'players' => false,
            'type'    => false,
        );
        $args     = array_merge( $defaults, $args );
        $count    = $args['count'];
        $players  = $args['players'];
        $type     = $args['type'];

        $search_terms = array();
        $sql    = " FROM $wpdb->racketmanager_teams WHERE `club_id` = '%d'";
        $search_terms[] = $this->id;
        if ( ! $players ) {
            $sql .= " AND (`team_type` is null OR `team_type` != 'P')";
        } else {
            $sql .= " AND `team_type` = 'P'";
        }
        if ( $type ) {
            if ( 'OS' === $type ) {
                $sql   .= " AND `type` like '%%%s%%'";
                $search_terms[] = 'S';
            } else {
                $sql   .= " AND `type` = '%s'";
                $search_terms[] = $type;
            }
        }
        if ( $count ) {
            $sql = 'SELECT COUNT(*) ' . $sql;
            return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql,
                    $search_terms
                )
            );
        }
        $sql  = 'SELECT `id` ' . $sql . ' ORDER BY `title`';
        $sql  = $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql,
            $search_terms
        );

        $teams = wp_cache_get( md5( $sql ), 'teams' );
        if ( ! $teams ) {
            $teams = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            ); // db call ok.
            wp_cache_set( md5( $sql ), $teams, 'teams' );
        }

        $class = '';
        foreach ( $teams as $i => $team ) {
            $class       = ( 'alternate' === $class ) ? '' : 'alternate';
            $team        = get_team( $team->id );
            $teams[ $i ] = $team;
        }

        return $teams;
    }

    /**
     * Check if player is captain
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
     * Can user update as a team captain in addition to as match secretary or admin user.
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
     * Can user update as match secretary or admin user.
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
    /**
     * Cup entry function
     *
     * @param object $club_entry club cup entry object.
     */
    public function cup_entry( object $club_entry ): void {
        global $racketmanager;
        $cup_entries = array();
        foreach ( $club_entry->events as $event_entry ) {
            $cup_entry = array();
            $event     = get_event( $event_entry->id );
            if ( isset( $event->primary_league ) ) {
                $league = get_league( $event->primary_league );
            } else {
                $league = get_league( array_key_first( $event->league_index ) );
            }
            $team_id   = $event_entry->team_id;
            $team      = get_team( $team_id );
            $team_info = $event->get_team_info( $team_id );
            $match_day = Util_Lookup::get_match_day( $event_entry->match_day );
            if ( ! $team_info ) {
                $team->add_event( $event->id, $event_entry->captain_id, $event_entry->telephone, $event_entry->email, $event_entry->match_day, $event_entry->match_time );
            } else {
                $team->update_event( $event->id, $event_entry->captain_id, $event_entry->telephone, $event_entry->email, $event_entry->match_day, $event_entry->match_time );
            }
            $league->add_team( $team_id, $club_entry->season );
            $cup_entry['event']        = $event->name;
            $cup_entry['teamName']     = $team->title;
            $cup_entry['captain']      = $event_entry->captain;
            $cup_entry['contactno']    = $event_entry->telephone;
            $cup_entry['contactemail'] = $event_entry->email;
            $cup_entry['matchday']     = $match_day;
            $cup_entry['matchtime']    = $event_entry->match_time;
            $cup_entries[]             = $cup_entry;
        }
        $email_to        = $this->match_secretary->display_name . ' <' . $this->match_secretary->email . '>';
        $email_from      = $racketmanager->get_confirmation_email( 'cup' );
        $email_subject   = $racketmanager->site_name . ' - ' . ucfirst( $club_entry->competition->name ) . ' ' . $club_entry->season . ' ' . __('Entry', 'racketmanager' ) . ' - ' . $this->shortcode;
        $headers         = array();
        $secretary_email = __( 'Cup Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
        $headers[]       = RACKETMANAGER_FROM_EMAIL . $secretary_email;
        $headers[]       = RACKETMANAGER_CC_EMAIL . $secretary_email;

        $template                     = 'cup-entry';
        $template_args['cup_entries'] = $cup_entries;
        $this->entry_form_send( $template_args, $club_entry, $email_from, $template, $email_to, $email_subject, $headers );
    }

    /**
     * League entry function
     *
     * @param object $club_entry club league entry object.
     */
    public function league_entry( object $club_entry ): void {
        global $racketmanager;
        $event_details = array();
        $competition   = get_competition( $club_entry->competition );
        foreach ( $club_entry->event as $event_entry ) {
            $event                       = get_event( $event_entry->id );
            $league_event_entry['event'] = $event_entry->name;
            $league_entries              = array();
            foreach ( $event_entry->team as $team_entry ) {
                $match_day = Util_Lookup::get_match_day( $team_entry->match_day );
                if ( empty( $team_entry->id ) ) {
                    $team = $this->add_team( $event->type );
                } else {
                    $team = get_team( $team_entry->id );
                }
                $team_info = $event->get_team_info( $team->id );
                if ( ! $team_info ) {
                    $team->add_event( $event_entry->id, $team_entry->captain_id, $team_entry->telephone, $team_entry->email, $team_entry->match_day, $team_entry->match_time );
                } else {
                    $team->update_event( $event_entry->id, $team_entry->captain_id, $team_entry->telephone, $team_entry->email, $team_entry->match_day, $team_entry->match_time );
                }
                if ( $team_entry->existing ) {
                    $event->mark_teams_entered( $team->id, $club_entry->season );
                } else {
                    $event->add_team_to_event( $team->id, $club_entry->season );
                }
                $league_entry                 = array();
                $league_entry['teamName']     = $team->title;
                $league_entry['captain']      = $team_entry->captain;
                $league_entry['contactno']    = $team_entry->telephone;
                $league_entry['contactemail'] = $team_entry->email;
                $league_entry['matchday']     = $match_day;
                $league_entry['matchtime']    = substr( $team_entry->match_time, 0, 5 );
                $league_entries[]             = $league_entry;
            }
            if ( ! empty( $event_entry->withdrawn_teams ) ) {
                foreach ( $event_entry->withdrawn_teams as $team ) {
                    if ( $team ) {
                        $event->mark_teams_withdrawn( $club_entry->season, $this->id, $team );
                    }
                }
            }
            $league_event_entry['teams'] = $league_entries;
            $event_details[]             = $league_event_entry;
        }
        $event_entries['events']               = $event_details;
        $event_entries['num_courts_available'] = $club_entry->num_courts_available;
        if ( ! empty( $club_entry->withdrawn_events ) ) {
            foreach ( $club_entry->withdrawn_events as $event_id ) {
                $event = get_event( $event_id );
                $event->mark_teams_withdrawn( $club_entry->season, $this->id );
            }
        }
        $competition->settings['num_courts_available'][ $this->id ] = $club_entry->num_courts_available;
        $competition->set_settings( $competition->settings );
        $email_to        = $this->match_secretary->display_name . ' <' . $this->match_secretary->email . '> ';
        $email_from      = $racketmanager->get_confirmation_email( 'league' );
        $email_subject   = $racketmanager->site_name . ' - ' . ucfirst( $club_entry->competition->name ) . ' ' . $club_entry->season . ' League Entry - ' . $this->shortcode;
        $headers         = array();
        $secretary_email = __( 'League Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
        $headers[]       = RACKETMANAGER_FROM_EMAIL . $secretary_email;
        $headers[]       = RACKETMANAGER_CC_EMAIL . $secretary_email;

        $template                       = 'league-entry';
        $template_args['event_entries'] = $event_entries;
        $this->entry_form_send( $template_args, $club_entry, $email_from, $template, $email_to, $email_subject, $headers );
    }
    /**
     * Send entry form
     * *
     * @param array  $template_args
     * @param object $club_entry
     * @param string $email_from
     * @param string $template
     * @param string $email_to
     * @param string $email_subject
     * @param array $headers
     * @return void
     */
    public function entry_form_send( array $template_args, object $club_entry, string $email_from, string $template, string $email_to, string $email_subject, array $headers ): void {
        global $racketmanager;
        $template_args['organisation']     = $racketmanager->site_name;
        $template_args['season']           = $club_entry->season;
        $template_args['competition_name'] = $club_entry->competition->name;
        $template_args['club']             = $this->name;
        $template_args['contact_email']    = $email_from;
        $template_args['comments']         = $club_entry->comments;
        $racketmanager->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
    }
}
