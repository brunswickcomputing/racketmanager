<?php
/**
 * Tournament API: tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Tournament
 */

namespace Racketmanager\Domain;

use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util;
use function Racketmanager\seo_url;

/**
 * Class to implement the Tournament object
 */
final class Tournament {

    /**
     * Id
     *
     * @var int|null
     */
    public ?int $id = null;
    /**
     * Tournament name
     *
     * @var string
     */
    public string $name;
    /**
     * Competition id
     *
     * @var int|null
     */
    public ? int $competition_id;
    /**
     * Competition
     *
     * @var object|null
     */
    public null|object $competition;
    /**
     * Tournament season
     *
     * @var string|null
     */
    public ? string $season;
    /**
     * Number of courts available for matches
     *
     * @var int|null
     */
    public ?int $num_courts = null;
    /**
     * Start time
     *
     * @var string|null
     */
    public ?string $start_time = null;
    /**
     * Date
     *
     * @var string
     */
    public string $date;
    /**
     * Date display
     *
     * @var string|int|false
     */
    public string|int|false $date_display;
    /**
     * Closing Date
     *
     * @var ?string
     */
    public ?string $date_closing;
    /**
     * Closing Date display
     *
     * @var string|false
     */
    public string|false $date_closing_display;
    /**
     * Date withdrawal variable
     *
     * @var string|null
     */
    public ?string $date_withdrawal;
    /**
     * Date withdrawal display
     *
     * @var string|false
     */
    public string|false $date_withdrawal_display;
    /**
     * Date open variable
     *
     * @var string|null
     */
    public ?string $date_open;
    /**
     * Date open display variable
     *
     * @var string|false
     */
    public string|false $date_open_display;
    /**
     * Date start variable
     *
     * @var string|null
     */
    public ?string $date_start;
    /**
     * Date start display variable
     *
     * @var string|false
     */
    public string|false $date_start_display;
    /**
     * Venue
     *
     * @var int|null
     */
    public ? int $venue;
    /**
     * Venue name
     *
     * @var string
     */
    public string $venue_name;
    /**
     * Is the tournament active?
     *
     * @var boolean
     */
    public bool $is_active;
    /**
     * Order of play
     *
     * @var string|array|null
     */
    public string|array|null $order_of_play = null;
    /**
     * Time increment for finals day matches
     *
     * @var string|null
     */
    public ?string $time_increment = null;
    /**
     * Competitions variable
     *
     * @var array
     */
    public array $competitions;
    /**
     * Events variable
     *
     * @var array
     */
    public array $events = array();
    /**
     * Players variable
     *
     * @var array
     */
    public array $players = array();
    /**
     * Current phase variable
     *
     * @var string
     */
    public string $current_phase;
    /**
     * Is complete
     *
     * @var boolean
     */
    public bool $is_complete = false;
    /**
     * Is started
     *
     * @var boolean
     */
    public bool $is_started = false;
    /**
     * Is closed
     *
     * @var boolean
     */
    public bool $is_closed = false;
    /**
     * Is withdrawal
     *
     * @var boolean
     */
    public bool $is_withdrawal = false;
    /**
     * Is open
     *
     * @var boolean
     */
    public bool $is_open = false;
    /**
     * Link variable
     *
     * @var string
     */
    public string $link;
    /**
     * Competition code variable
     *
     * @var string|null
     */
    public ?string $competition_code = null;
    /**
     * Finals variable
     *
     * @var array
     */
    public array $finals;
    /**
     * Grade variable
     *
     * @var string|null
     */
    public ?string $grade;
    /**
     * Fees
     *
     * @var object
     */
    public object $fees;
    /**
     * Charge object
     *
     * @var object
     */
    public object $charge;
    /**
     * Number of entries
     *
     * @var int|null
     */
    public ?int $num_entries;
    /**
     * Type
     *
     * @var string
     */
    public string $type;
    /**
     * Date end
     *
     * @var string
     */
    public string $date_end;
    /**
     * Matches
     *
     * @var array
     */
    public array $matches;
    /**
     * Payments
     *
     * @var int|null
     */
    public ?int $payments;
    /**
     * Entries
     *
     * @var array
     */
    public array $entries;
    /**
     * Match dates
     *
     * @var array
     */
    public array $match_dates;
    /**
     * Entry link.
     *
     * @var string
     */
    public string $entry_link;
    /** @var array<string, mixed> Storage for non-table data */
    private array $meta = [];
    /**
     * Information.
     *
     * @var object|null
     */
    public null|object $information = null;
    private Competition_Service $competition_service;

    /**
     * Constructor
     *
     * @param object|null $tournament Tournament object.
     */
    public function __construct( object $tournament = null ) {
        global $racketmanager;
        if ( is_null( $tournament ) ) {
            return;
        }
        $c                         = $racketmanager->container;
        $this->competition_service = $c->get( 'competition_service' );

        $this->id               = $tournament->id;
        $this->name             = $tournament->name;
        $this->competition_id   = $tournament->competition_id;
        $this->season           = $tournament->season;
        $this->competition_code = $tournament->competition_code;
        $this->grade            = $tournament->grade;
        $this->date_end         = $tournament->date;
        $this->date_closing     = $tournament->date_closing;
        $this->date_start       = $tournament->date_start;
        $this->date_open        = $tournament->date_open;
        $this->date_withdrawal  = $tournament->date_withdrawal;
        $this->venue            = $tournament->venue;
        $this->num_entries      = $tournament->num_entries;
        $this->information      = empty( $tournament->information ) ? null : json_decode( $tournament->information );
        $this->order_of_play    = isset( $tournament->orderofplay ) ? maybe_unserialize( $tournament->orderofplay ) : null;
        $this->time_increment   = $tournament->timeincrement ?? null;
        $this->num_courts       = $tournament->numcourts ?? null;
        $this->start_time       = $tournament->starttime ?? null;
        $this->set_tournament_info();
    }

    public function get_id(): ?int {
        return $this->id;
    }


    public function get_name(): string {
        return $this->name;
    }

    public function get_competition_id(): int {
        return $this->competition_id;
    }

    public function get_season(): int {
        return $this->season;
    }

    public function get_venue(): ?int {
        return $this->venue;
    }

    public function get_end_date(): ?string {
        return $this->date_end;
    }

    public function get_closing_date(): ?string {
        return $this->date_closing;
    }

    public function get_withdrawal_date(): ?string {
        return $this->date_withdrawal;
    }

    public function get_open_date(): ?string {
        return $this->date_open;
    }

    public function get_start_date(): ?string {
        return $this->date_start;
    }

    public function get_competition_code(): ?string {
        return $this->competition_code;
    }

    public function get_grade(): ?string {
        return $this->grade;
    }

    public function get_num_entries(): ?int {
        return $this->num_entries;
    }

    public function get_num_courts(): ?int {
        return $this->num_courts;
    }

    public function get_start_time(): ?string {
        return $this->start_time;
    }

    public function get_time_increment(): ?string {
        return $this->time_increment;
    }

    public function get_order_of_play(): ?array {
        return $this->order_of_play;
    }

    public function get_information(): ?object {
        return $this->information;
    }

    public function set_id( int $id ): void {
        $this->id = $id;
    }

    public function set_name( string $name ): void {
        $this->name = $name;
    }

    public function set_competition_id( int $competition_id ): void {
        $this->competition_id = $competition_id;
    }

    public function set_season( ?string $season ): void {
        $this->season = $season;
    }

    public function set_venue( ?int $venue ): void {
        $this->venue = $venue;
    }

    public function set_end_date( ?string $date ): void {
        $this->date_end = $date;
    }

    public function set_closing_date( ?string $date_closing ): void {
        $this->date_closing = $date_closing;
    }

    public function set_withdrawal_date( ?string $date_withdrawal ): void {
        $this->date_withdrawal = $date_withdrawal;
    }

    public function set_opening_date( ?string $date_open ): void {
        $this->date_open = $date_open;
    }

    public function set_start_date( ?string $date_start ): void {
        $this->date_start = $date_start;
    }

    public function set_competition_code( ?string $competition_code ): void {
        $this->competition_code = $competition_code;
    }

    public function set_grade( ?string $grade ): void {
        $this->grade = $grade;
    }

    public function set_num_entries( ?int $num_entries ): void {
        $this->num_entries = $num_entries;
    }

    public function set_num_courts( ?int $num_courts ): void {
        $this->num_courts = $num_courts;
    }

    public function set_start_time( ?string $start_time ): void {
        $this->start_time = $start_time;
    }

    public function set_time_increment( ?string $start_time ): void {        $this->time_increment = $start_time;
    }

    public function set_order_of_play( ?array $order_of_play= null ): void {
        $this->order_of_play = $order_of_play;
    }


    /**
     * Set information
     *
     * @param object|null $information information.
     */
    public function set_information( ?object $information ): void {
        $this->information = $information;
    }

    /**
     * Set a transient metadata property.
     */
    public function set_meta( string $key, mixed $value ): void {
        $this->meta[ $key ] = $value;
    }

    /**
     * Retrieve a transient metadata property.
     */
    public function get_meta( string $key, mixed $default = null ): mixed {
        return $this->meta[ $key ] ?? $default;
    }

    public function set_tournament_info(): void {
        global $racketmanager, $wp;
        $this->link                    = '/tournament/' . seo_url( $this->name ) . '/';
        $this->entry_link              = $racketmanager->site_url . '/entry-form/' . seo_url( $this->name ) . '-tournament/';
        $this->date_display            = empty( $this->date_end ) ? 'TBC' : mysql2date( $racketmanager->date_format, $this->date_end );
        $this->date_closing_display    = empty( $this->date_closing ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_closing );
        $this->date_withdrawal_display = empty( $this->date_withdrawal ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_withdrawal );
        $this->date_open_display       = empty( $this->date_open ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_open );
        $this->date_start_display      = empty( $this->date_start ) ? 'N/A' : mysql2date( $racketmanager->date_format, $this->date_start );
        $today                         = gmdate( 'Y-m-d' );
        if ( $today > $this->date_end ) {
            $this->current_phase = 'end';
            $this->is_complete   = true;
        } else {
            $this->current_phase = '';
            if ( ! empty( $this->date_start ) && $today >= $this->date_start ) {
                $this->current_phase = 'start';
                $this->is_started    = true;
            } elseif ( ! empty( $this->date_withdrawal ) && $today > $this->date_withdrawal ) {
                $this->current_phase = 'withdraw';
                $this->is_withdrawal = true;
            } elseif ( ! empty( $this->date_closing ) && $today > $this->date_closing ) {
                $this->current_phase = 'close';
                $this->is_closed     = true;
            } elseif ( ! empty( $this->date_open ) && $today >= $this->date_open ) {
                $this->current_phase = 'open';
                $this->is_open       = true;
            }
        }
        if ( isset( $this->date_closing ) && $this->date_closing <= gmdate( 'Y-m-d' ) ) {
            $this->is_active = true;
        } else {
            $this->is_active = false;
        }
        $this->order_of_play = (array) maybe_unserialize( $this->order_of_play );
        if ( $this->competition_id ) {
            $this->competition = $this->competition_service->get_by_id( $this->competition_id );
        }
        $finals     = array();
        $max_rounds = 6;
        $r          = $max_rounds;
        for ( $round = 1; $round <= $max_rounds; ++$round ) {
            $num_teams      = pow( 2, $round );
            $num_matches    = $num_teams / 2;
            $key            = Util::get_final_key( $num_teams );
            $name           = Util::get_final_name( $key );
            $finals[ $key ] = array(
                'key'         => $key,
                'name'        => $name,
                'num_matches' => $num_matches,
                'num_teams'   => $num_teams,
                'round'       => $r,
            );
            --$r;
        }
        $this->finals = $finals;
        $wp->set_query_var( 'season', $this->season );
    }

}
