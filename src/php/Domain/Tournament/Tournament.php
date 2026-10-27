<?php
declare( strict_types=1 );
/**
 * Tournament API: tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Tournament
 */
namespace Racketmanager\Domain\Tournament;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Tournament\Tournament_Hydration_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Update_DTO;
use Racketmanager\Domain\Value_Objects\Date_Range;
use Racketmanager\Util\Util;
use stdClass;

/**
 * Class to implement the Tournament object
 */
class Tournament {

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
    public string $name = '';
    /**
     * Competition id
     *
     * @var int|null
     */
    public ?int $competition_id = null;
    /**
     * Tournament season
     *
     * @var string|null
     */
    public ?string $season = null;
    /**
     * Number of courts available for fixtures
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
     * Date end
     *
     * @var string
     */
    public string $date_end = '';
    /**
     * Closing Date
     *
     * @var ?string
     */
    public ?string $date_closing = null;
    /**
     * Date withdrawal variable
     *
     * @var string|null
     */
    public ?string $date_withdrawal = null;
    /**
     * Date open variable
     *
     * @var string|null
     */
    public ?string $date_open = null;
    /**
     * Date start variable
     *
     * @var string|null
     */
    public ?string $date_start = null;
    /**
     * Venue
     *
     * @var int|null
     */
    public ?int $venue = null;
    /**
     * Venue name
     *
     * @var string
     */
    public string $venue_name = '';
    /**
     * Is the tournament active?
     *
     * @var boolean
     */
    public bool $is_active = false;
    /**
     * Order of play
     *
     * @var string|array|null
     */
    public string|array|null $order_of_play = null;
    /**
     * Time increment for finals day fixtures
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
     * Associated competition object
     *
     * @var Competition|null
     */
    public ?Competition $competition = null;
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
     * Matches
     *
     * @var array
     */
    public array $matches;
    /**
     * Payments
     *
     * @var float|null
     */
    public ?float $payments;
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
     * Information.
     *
     * @var object|null
     */
    public null|object $information = null;
    /** @var array<string, mixed> Storage for non-table data */
    private array $meta = [];

    /**
     * Constructor
     *
     * @param Tournament_Hydration_DTO|object|null $tournament Hydration DTO or raw tournament row.
     */
    public function __construct( ?object $tournament = null ) {
        if ( is_null( $tournament ) ) {
            return;
        }

        $dto = $tournament instanceof Tournament_Hydration_DTO ? $tournament : Tournament_Hydration_DTO::from_object( $tournament );

        $this->id               = $dto->id;
        $this->name             = $dto->name;
        $this->competition_id   = $dto->competition_id;
        $this->season           = $dto->season;
        $this->competition_code = $dto->competition_code;
        $this->grade            = $dto->grade;
        $this->date_end         = $dto->date_end;
        $this->date_closing     = $dto->date_closing;
        $this->date_start       = $dto->date_start;
        $this->date_open        = $dto->date_open;
        $this->date_withdrawal  = $dto->date_withdrawal;
        $this->venue            = $dto->venue;
        $this->num_entries      = $dto->num_entries;
        $this->information      = $dto->information;
        $order_of_play          = $dto->order_of_play;
        $this->order_of_play    = is_array( $order_of_play ) ? $order_of_play : ( null === $order_of_play ? array() : (array) $order_of_play );
        $this->time_increment   = $dto->time_increment;
        $this->num_courts       = $dto->num_courts;
        $this->start_time       = $dto->start_time;
        $this->set_tournament_info();
    }

    public function set_tournament_info(): void {
        $this->set_tournament_phase();
        $this->set_tournament_finals_config();
    }

    private function set_tournament_phase(): void {
        $today_str = gmdate( 'Y-m-d' );
        $today_dt  = new \DateTimeImmutable( $today_str . ' 00:00:00', new \DateTimeZone( 'UTC' ) );

        $this->is_complete   = false;
        $this->is_started    = false;
        $this->is_withdrawal = false;
        $this->is_closed     = false;
        $this->is_open       = false;
        $this->is_active     = false;

        $check_elapsed = function( ?string $date, bool $inclusive = false ) use ( $today_dt, $today_str ): bool {
            if ( empty( $date ) ) {
                return false;
            }
            $dt = Date_Range::try_parse_date( $date );
            if ( null !== $dt ) {
                return $inclusive ? ( $dt <= $today_dt ) : ( $dt < $today_dt );
            }
            return $inclusive ? ( $today_str >= $date ) : ( $today_str > $date );
        };

        $tournament_window = $this->get_tournament_window();
        $is_end_elapsed    = ( null !== $tournament_window )
            ? $tournament_window->is_past( $today_dt )
            : $this->is_end_date_elapsed( $today_str, $today_dt );

        if ( $is_end_elapsed ) {
            $this->current_phase = 'end';
            $this->is_complete   = true;
        } else {
            $this->current_phase = '';
            if ( $check_elapsed( $this->date_start, true ) ) {
                $this->current_phase = 'start';
                $this->is_started    = true;
            } elseif ( $check_elapsed( $this->date_withdrawal, false ) ) {
                $this->current_phase = 'withdraw';
                $this->is_withdrawal = true;
            } elseif ( $check_elapsed( $this->date_closing, false ) ) {
                $this->current_phase = 'close';
                $this->is_closed     = true;
            } elseif ( $check_elapsed( $this->date_open, true ) ) {
                $this->current_phase = 'open';
                $this->is_open       = true;
            }
        }

        if ( isset( $this->date_closing ) ) {
            $closing_dt = Date_Range::try_parse_date( $this->date_closing );
            if ( null !== $closing_dt ) {
                $this->is_active = ( $closing_dt <= $today_dt );
            } else {
                $this->is_active = ( $this->date_closing <= $today_str );
            }
        }
    }

    /**
     * Legacy end-date check, preserved from the original behaviour: a missing/empty/non-standard end date
     * is compared as a raw string (so an empty end date counts as elapsed).
     */
    private function is_end_date_elapsed( string $today_str, \DateTimeImmutable $today_dt ): bool {
        $end    = (string) $this->date_end;
        $end_dt = Date_Range::try_parse_date( $end );
        if ( null !== $end_dt ) {
            return $end_dt < $today_dt;
        }
        return $today_str > $end;
    }

    /**
     * Get the tournament match play window as a Date_Range Value Object.
     */
    public function get_tournament_window(): ?Date_Range {
        return Date_Range::try_create( $this->date_start, $this->date_end );
    }

    /**
     * Get the tournament entry registration window as a Date_Range Value Object.
     */
    public function get_entry_window(): ?Date_Range {
        return Date_Range::try_create( $this->date_open, $this->date_closing );
    }

    /**
     * Get the tournament withdrawal window as a Date_Range Value Object.
     */
    public function get_withdrawal_window(): ?Date_Range {
        return Date_Range::try_create( $this->date_open, $this->date_withdrawal );
    }

    public function is_active(): bool {
        return $this->is_active;
    }

    public function is_complete(): bool {
        return $this->is_complete;
    }

    public function is_started(): bool {
        return $this->is_started;
    }

    public function is_open(): bool {
        return $this->is_open;
    }

    public function is_closed(): bool {
        return $this->is_closed;
    }

    public function is_withdrawal(): bool {
        return $this->is_withdrawal;
    }

    public function get_current_phase(): string {
        return $this->current_phase;
    }

    private function set_tournament_finals_config(): void {
        $this->order_of_play = (array) $this->order_of_play;
        $finals              = array();
        $max_rounds          = 6;
        $r                   = $max_rounds;
        for ( $round = 1; $round <= $max_rounds; ++ $round ) {
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
            -- $r;
        }
        $this->finals = $finals;
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function set_id( int $id ): void {
        $this->id = $id;
    }

    public function get_name(): string {
        return $this->name;
    }

    public function set_name( string $name ): void {
        $this->name = $name;
    }

    public function get_competition_id(): int {
        return $this->competition_id;
    }

    public function set_competition_id( int $competition_id ): void {
        if ( isset( $this->competition ) && (int) $this->competition->id !== $competition_id ) {
            $this->competition = null;
        }
        $this->competition_id = $competition_id;
    }

    public function get_season(): int {
        return (int) $this->season;
    }

    public function set_season( ?string $season ): void {
        $this->season = $season;
    }

    public function get_venue(): ?int {
        return $this->venue;
    }

    public function set_venue( ?int $venue ): void {
        $this->venue = $venue;
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

    public function set_competition_code( ?string $competition_code ): void {
        $this->competition_code = $competition_code;
    }

    public function get_grade(): ?string {
        return $this->grade;
    }

    public function set_grade( ?string $grade ): void {
        $this->grade = $grade;
    }

    public function get_num_entries(): ?int {
        return $this->num_entries;
    }

    public function set_num_entries( ?int $num_entries ): void {
        $this->num_entries = $num_entries;
    }

    public function get_num_courts(): ?int {
        return $this->num_courts;
    }

    public function set_num_courts( ?int $num_courts ): void {
        $this->num_courts = $num_courts;
    }

    public function get_start_time(): ?string {
        return $this->start_time;
    }

    public function set_start_time( ?string $start_time ): void {
        $this->start_time = $start_time;
    }

    public function get_time_increment(): ?string {
        return $this->time_increment;
    }

    public function set_time_increment( ?string $start_time ): void {
        $this->time_increment = $start_time;
    }

    public function get_order_of_play(): ?array {
        return $this->order_of_play;
    }

    public function set_order_of_play( ?array $order_of_play = null ): void {
        $this->order_of_play = $order_of_play;
    }

    public function get_information(): ?object {
        return $this->information;
    }

    /**
     * Set information
     *
     * @param object|null $information information.
     */
    public function set_information( ?object $information ): void {
        $this->information = $information;
    }

    public function __serialize(): array {
        $data = get_object_vars( $this );
        unset( $data['competition'] );
        return $data;
    }

    public function __unserialize( array $data ): void {
        foreach ( $data as $key => $value ) {
            if ( property_exists( $this, $key ) ) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Get associated Competition object.
     *
     * @return Competition|null
     */
    public function get_competition_object(): ?Competition {
        if ( empty( $this->competition_id ) ) {
            return null;
        }
        if ( isset( $this->competition ) ) {
            if ( (int) $this->competition->id === (int) $this->competition_id ) {
                return $this->competition;
            }
            return null;
        }
        return null;
    }

    /**
     * Set associated Competition entity.
     *
     * @param Competition|null $competition
     * @return void
     */
    public function set_competition( ?Competition $competition ): void {
        $this->competition = $competition;
    }

    /**
     * Get associated Competition entity.
     *
     * @return Competition|null
     */
    public function get_competition(): ?Competition {
        return $this->get_competition_object();
    }

    /**
     * Retrieve raw allowed event IDs for this tournament.
     *
     * @param bool $for_entry Whether checking for entry submission.
     * @return int[]|null Null indicates unrestricted; array indicates explicit allowlist.
     */
    public function get_allowlist_event_ids( bool $for_entry = false ): ?array {
        if ( empty( $this->season ) ) {
            return array();
        }

        $information = $this->information;
        if ( ! is_object( $information ) || ! property_exists( $information, 'entry_events' ) ) {
            return null;
        }

        $entry_events = $information->entry_events;

        if ( $for_entry ) {
            // Entry validation: null/unset means all season events; explicit [] means closed/none
            if ( is_null( $entry_events ) ) {
                return null;
            }
            return is_array( $entry_events ) ? array_map( 'intval', $entry_events ) : array();
        }

        // Frontend viewing: null/unset or empty [] means all season events allowed
        if ( empty( $entry_events ) || ! is_array( $entry_events ) ) {
            return null;
        }

        return array_map( 'intval', $entry_events );
    }

    /**
     * Check if an event is allowed in this tournament.
     *
     * Fails closed: raw IDs and slugs are only accepted when they resolve to an in-scope Event
     * within the supplied candidate events (see Competition_Service::resolve_competition_event()).
     *
     * @param int|string|object $event Event ID, event name/slug, or Event object.
     * @param bool              $for_entry Whether checking for entry submission (where [] denies all) vs frontend viewing (where [] allows all).
     * @param iterable          $events Optional in-memory event collection.
     * @return bool
     */
    public function is_event_allowed( int|string|object $event, bool $for_entry = false, iterable $events = [] ): bool {
        $competition = $this->get_competition_object();
        if ( ! $competition || empty( $this->season ) ) {
            return false;
        }

        $event_obj = $competition->resolve_event( $event, $this->season, $events );
        if ( ! $event_obj ) {
            return false;
        }

        if ( ! $competition->is_event_allowed( $event_obj, $this->season, $for_entry, $events ) ) {
            return false;
        }

        $allowed_ids = $this->get_allowlist_event_ids( $for_entry );
        if ( null === $allowed_ids ) {
            return true;
        }

        return in_array( (int) $event_obj->id, $allowed_ids, true );
    }

    /**
     * Save the tournament-specific entry event selection.
     *
     * @param array $event_ids selected event IDs.
     * @return bool
     */
    public function set_entry_events( array $event_ids ): bool {
        $information = is_object( $this->information ) ? clone $this->information : new stdClass();
        $information->entry_events = array_values( array_unique( array_map( 'intval', $event_ids ) ) );
        $this->set_information( $information );
        return true;
    }

    /**
     * Get events available for entry in this tournament.
     *
     * @param iterable $events Optional in-memory event collection.
     * @return array
     */
    public function get_entry_events( iterable $events = [] ): array {
        $competition = $this->get_competition_object();
        if ( ! $competition || empty( $this->season ) ) {
            return array();
        }
        $allowed_ids = $this->get_allowlist_event_ids( true );
        $events_list = $competition->get_events_for_season( $this->season, $events );
        if ( null === $allowed_ids ) {
            return $events_list;
        }

        return array_values(
            array_filter(
                $events_list,
                static fn( $event ): bool => in_array( (int) ( $event->id ?? 0 ), $allowed_ids, true )
            )
        );
    }

    /**
     * Retrieve a transient metadata property.
     */
    public function get_meta( string $key, mixed $default = null ): mixed {
        return $this->meta[ $key ] ?? $default;
    }

    /**
     * Set a transient metadata property.
     */
    public function set_meta( string $key, mixed $value ): void {
        $this->meta[ $key ] = $value;
    }

    /**
     * Update tournament state from already-sanitised request values.
     *
     * @param Tournament_Update_DTO $request The update DTO containing the new attribute values.
     *
     * @return self
     */
    public function update_from_request( Tournament_Update_DTO $request ): self {
        $this->set_name( $request->name );
        $this->set_competition_id( $request->competition_id );
        $this->set_season( $request->season );
        $this->set_venue( $request->venue );
        $this->set_end_date( $request->date_end );
        $this->set_closing_date( $request->date_closing );
        $this->set_withdrawal_date( $request->date_withdrawal );
        $this->set_opening_date( $request->date_open );
        $this->set_start_date( $request->date_start );
        $this->set_competition_code( $request->competition_code );
        $this->set_grade( $request->grade );
        $this->set_num_entries( $request->num_entries );

        return $this;
    }

    public function set_end_date( ?string $date_end ): void {
        $this->date_end = $date_end;
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

    /**
     * Calculate default match dates for the tournament rounds.
     *
     * @param int $round_length The length of each round in days.
     *
     * @return array<int, string>
     */
    public function calculate_default_match_dates( int $round_length = 7 ): array {
        $match_dates = array();
        $match_date  = null;
        $i           = 0;

        $finals = $this->finals;
        uasort(
            $finals,
            function ( $a, $b ) {
                return $a['round'] <=> $b['round'];
            }
        );

        foreach ( $finals as $final ) {
            $r = $final['round'] - 1;
            if ( 0 === $i ) {
                $match_date = $this->date_end;
            } elseif ( 1 === $i ) {
                $match_date = Util::amend_date( $this->date_end, 7, '-' );
            } else {
                $match_date = Util::amend_date( $match_date, $round_length, '-' );
            }
            $match_dates[ $r ] = $match_date;
            ++ $i;
        }

        return $match_dates;
    }

}
