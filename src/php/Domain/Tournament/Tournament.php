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

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
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
    public private(set) ?int $id = null;
    /**
     * Tournament name
     *
     * @var string
     */
    public private(set) string $name = '';
    /**
     * Competition id
     *
     * @var int|null
     */
    public private(set) ?int $competition_id = null;
    /**
     * Tournament season
     *
     * @var string|null
     */
    public private(set) ?string $season = null;
    /**
     * Number of courts available for fixtures
     *
     * @var int|null
     */
    public private(set) ?int $num_courts = null;
    /**
     * Start time
     *
     * @var string|null
     */
    public private(set) ?string $start_time = null;
    /**
     * Date end
     *
     * @var string
     */
    public private(set) string $date_end = '';
    /**
     * Closing Date
     *
     * @var ?string
     */
    public private(set) ?string $date_closing = null;
    /**
     * Date withdrawal variable
     *
     * @var string|null
     */
    public private(set) ?string $date_withdrawal = null;
    /**
     * Date open variable
     *
     * @var string|null
     */
    public private(set) ?string $date_open = null;
    /**
     * Date start variable
     *
     * @var string|null
     */
    public private(set) ?string $date_start = null;
    /**
     * Venue
     *
     * @var int|null
     */
    public private(set) ?int $venue = null;
    /**
     * Venue name
     *
     * @var string
     */
    public private(set) string $venue_name = '';
    /**
     * Is the tournament active?
     *
     * @var boolean
     */
    public private(set) bool $is_active = false;
    /**
     * Order of play
     *
     * @var string|array|null
     */
    public private(set) string|array|null $order_of_play = null;
    /**
     * Time increment for finals day fixtures
     *
     * @var string|null
     */
    public private(set) ?string $time_increment = null;
    /**
     * Competitions variable
     *
     * @var array
     */
    public private(set) array $competitions;
    /**
     * Associated competition object
     *
     * @var Competition|null
     */
    public private(set) ?Competition $competition = null;
    /**
     * Events variable
     *
     * @var array
     */
    public private(set) array $events = array();
    /**
     * Players variable
     *
     * @var array
     */
    public private(set) array $players = array();
    /**
     * Current phase variable
     *
     * @var string
     */
    public private(set) string $current_phase;
    /**
     * Is complete
     *
     * @var boolean
     */
    public private(set) bool $is_complete = false;
    /**
     * Is started
     *
     * @var boolean
     */
    public private(set) bool $is_started = false;
    /**
     * Is closed
     *
     * @var boolean
     */
    public private(set) bool $is_closed = false;
    /**
     * Is withdrawal
     *
     * @var boolean
     */
    public private(set) bool $is_withdrawal = false;
    /**
     * Is open
     *
     * @var boolean
     */
    public private(set) bool $is_open = false;
    /**
     * Competition code variable
     *
     * @var string|null
     */
    public private(set) ?string $competition_code = null;
    /**
     * Finals variable
     *
     * @var array
     */
    public private(set) array $finals;
    /**
     * Grade variable
     *
     * @var string|null
     */
    public private(set) ?string $grade;
    /**
     * Fees
     *
     * @var object
     */
    public private(set) object $fees;
    /**
     * Number of entries
     *
     * @var int|null
     */
    public private(set) ?int $num_entries;
    /**
     * Type
     *
     * @var string
     */
    public private(set) string $type;
    /**
     * Matches
     *
     * @var array
     */
    public private(set) array $matches;
    /**
     * Payments
     *
     * @var float|null
     */
    public private(set) ?float $payments;
    /**
     * Entries
     *
     * @var array
     */
    public private(set) array $entries;
    /**
     * Match dates
     *
     * @var array
     */
    public private(set) array $match_dates;
    /**
     * Information.
     *
     * @var object|null
     */
    public private(set) null|object $information = null;
    /** @var array<string, mixed> Storage for non-table data */
    private array $meta = [];

    /**
     * Constructor
     *
     * @param Tournament_Hydration_DTO|null $dto Hydration DTO.
     *
     * @throws DateMalformedStringException In case of malformed date string.
     */
    public function __construct( ?Tournament_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }

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
        $this->order_of_play    = (array) ( $dto->order_of_play ?? array() );
        $this->time_increment   = $dto->time_increment;
        $this->num_courts       = $dto->num_courts;
        $this->start_time       = $dto->start_time;
        $this->set_tournament_info();
    }

    /**
     * Create a tournament instance from a data object.
     *
     * @param object $row The source object containing tournament data.
     *
     * @return self
     */
    public static function from_object( object $row ): self {
        return Tournament_Factory::from_object( $row );
    }

    /**
     * Create a tournament instance from array data.
     *
     * @param array<string, mixed> $data Tournament data used to create the instance.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return Tournament_Factory::from_array( $data );
    }

    /**
     * Set the tournament phase and finals configuration.
     *
     * @return void
     * @throws DateMalformedStringException Invalid date.
     */
    public function set_tournament_info(): void {
        $this->set_tournament_phase();
        $this->set_tournament_finals_config();
    }

    /**
     * Determine and set the current tournament phase.
     *
     * Resets all phase flags, compares the current UTC date against the tournament
     * schedule, and updates the current phase and status flags for end, start,
     * withdrawal, close, open, and active states.
     *
     * @return void
     * @throws DateMalformedStringException Invalid date.
     */
    private function set_tournament_phase(): void {
        $today_str = gmdate( 'Y-m-d' );
        $today_dt  = new DateTimeImmutable( $today_str . ' 00:00:00', new DateTimeZone( 'UTC' ) );

        $this->is_complete   = false;
        $this->is_started    = false;
        $this->is_withdrawal = false;
        $this->is_closed     = false;
        $this->is_open       = false;
        $this->is_active     = false;

        $check_elapsed = function ( ?string $date, bool $inclusive = false ) use ( $today_dt, $today_str ): bool {
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
     * Determines whether the configured end date has elapsed.
     *
     * @param string            $today_str The current date as a string.
     * @param DateTimeImmutable $today_dt The current date as a DateTimeImmutable instance.
     *
     * @return bool True if the end date has elapsed, false otherwise.
     */
    private function is_end_date_elapsed( string $today_str, DateTimeImmutable $today_dt ): bool {
        $end    = $this->date_end;
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

    /**
     * Determines whether the instance is active.
     *
     * @return bool True if the instance is active, false otherwise.
     */
    public function is_active(): bool {
        return $this->is_active;
    }

    /**
     * Determines whether the current item is complete.
     *
     * @return bool True if the current item is complete, false otherwise.
     */
    public function is_complete(): bool {
        return $this->is_complete;
    }

    /**
     * Determines whether the process has started.
     *
     * @return bool True if the process has started, false otherwise.
     */
    public function is_started(): bool {
        return $this->is_started;
    }

    /**
     * Determines whether the item is open.
     *
     * @return bool True if the item is open, false otherwise.
     */
    public function is_open(): bool {
        return $this->is_open;
    }

    /**
     * Determines whether the item is closed.
     *
     * @return bool True if the item is closed, false otherwise.
     */
    public function is_closed(): bool {
        return $this->is_closed;
    }

    /**
     * Determines whether the transaction is a withdrawal.
     *
     * @return bool True if the transaction is a withdrawal, false otherwise.
     */
    public function is_withdrawal(): bool {
        return $this->is_withdrawal;
    }

    /**
     * Retrieves the current phase.
     *
     * @return string The current phase.
     */
    public function get_current_phase(): string {
        return $this->current_phase;
    }

    /**
     * Configures tournament finals metadata for supported final rounds.
     *
     * @return void
     */
    private function set_tournament_finals_config(): void {
        $this->order_of_play = (array) $this->order_of_play;
        $finals              = array();
        $max_rounds          = 6;
        $r                   = $max_rounds;
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
    }

    /**
     * Retrieves the ID.
     *
     * @return int|null The ID, or null if not set.
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Sets the ID.
     *
     * @param int|null $id The ID to set.
     *
     * @return void
     */
    public function set_id( ?int $id ): void {
        $this->id = $id;
    }

    /**
     * Retrieves the name.
     *
     * @return string The name.
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Sets the name.
     *
     * @param string $name The name to set.
     *
     * @return void
     */
    public function set_name( string $name ): void {
        $this->name = $name;
    }

    /**
     * Retrieves the competition ID.
     *
     * @return int The competition ID.
     */
    public function get_competition_id(): int {
        return $this->competition_id;
    }

    /**
     * Set the competition ID for the instance.
     *
     * @param int $competition_id The competition ID to assign.
     *
     * @return void
     */
    public function set_competition_id( int $competition_id ): void {
        if ( isset( $this->competition ) && (int) $this->competition->id !== $competition_id ) {
            $this->competition = null;
        }
        $this->competition_id = $competition_id;
    }

    /**
     * Retrieves the season value.
     *
     * @return int The season value.
     */
    public function get_season(): int {
        return (int) $this->season;
    }

    /**
     * Sets the season value.
     *
     * @param string|null $season The season value, or null to clear it.
     *
     * @return void
     */
    public function set_season( ?string $season ): void {
        $this->season = $season;
    }

    /**
     * Retrieves the venue identifier.
     *
     * @return int|null The venue identifier, or null if no venue is set.
     */
    public function get_venue(): ?int {
        return $this->venue;
    }

    /**
     * Sets the venue identifier.
     *
     * @param int|null $venue The venue identifier, or null to unset it.
     *
     * @return void
     */
    public function set_venue( ?int $venue ): void {
        $this->venue = $venue;
    }

    /**
     * Retrieves the end date.
     *
     * @return string|null The end date, or null if not set.
     */
    public function get_end_date(): ?string {
        return $this->date_end;
    }

    /**
     * Retrieves the closing date.
     *
     * @return string|null The closing date, or null if not set.
     */
    public function get_closing_date(): ?string {
        return $this->date_closing;
    }

    /**
     * Retrieves the withdrawal date.
     *
     * @return string|null The withdrawal date, or null if not set.
     */
    public function get_withdrawal_date(): ?string {
        return $this->date_withdrawal;
    }

    /**
     * Retrieves the open date.
     *
     * @return string|null The open date, or null if not set.
     */
    public function get_open_date(): ?string {
        return $this->date_open;
    }

    /**
     * Retrieves the start date.
     *
     * @return string|null The start date, or null if not set.
     */
    public function get_start_date(): ?string {
        return $this->date_start;
    }

    /**
     * Retrieves the competition code.
     *
     * @return string|null The competition code, or null if not set.
     */
    public function get_competition_code(): ?string {
        return $this->competition_code;
    }

    /**
     * Sets the competition code.
     *
     * @param string|null $competition_code The competition code to set, or null to clear it.
     *
     * @return void
     */
    public function set_competition_code( ?string $competition_code ): void {
        $this->competition_code = $competition_code;
    }

    /**
     * Retrieves the grade.
     *
     * @return string|null The grade, or null if no grade is set.
     */
    public function get_grade(): ?string {
        return $this->grade;
    }

    /**
     * Set the grade.
     *
     * @param string|null $grade The grade to assign.
     *
     * @return void
     */
    public function set_grade( ?string $grade ): void {
        $this->grade = $grade;
    }

    /**
     * Get the number of entries.
     *
     * @return int|null The number of entries, or null if not set.
     */
    public function get_num_entries(): ?int {
        return $this->num_entries;
    }

    /**
     * Set the number of entries.
     *
     * @param int|null $num_entries The number of entries, or null to unset it.
     *
     * @return void
     */
    public function set_num_entries( ?int $num_entries ): void {
        $this->num_entries = $num_entries;
    }

    /**
     * Get the number of courts.
     *
     * @return int|null The number of courts, or null if not set.
     */
    public function get_num_courts(): ?int {
        return $this->num_courts;
    }

    /**
     * Set the number of courts available for the tournament.
     *
     * @param int|null $num_courts The number of courts available, or null if not set.
     *
     * @return void
     */
    public function set_num_courts( ?int $num_courts ): void {
        $this->num_courts = $num_courts;
    }

    /**
     * Set type.
     *
     * @param string $type Tournament type.
     *
     * @return void
     */
    public function set_type( string $type ): void {
        $this->type = $type;
    }

    /**
     * Retrieves the start time.
     *
     * @return string|null The start time, or null if not set.
     */
    public function get_start_time(): ?string {
        return $this->start_time;
    }

    /**
     * Sets the start time.
     *
     * @param string|null $start_time The start time, or null to unset it.
     *
     * @return void
     */
    public function set_start_time( ?string $start_time ): void {
        $this->start_time = $start_time;
    }

    /**
     * Get the time increment setting.
     *
     * @return string|null The time increment, or null if not set.
     */
    public function get_time_increment(): ?string {
        return $this->time_increment;
    }

    /**
     * Set the time increment start time.
     *
     * @param string|null $start_time The start time to use for the time increment.
     *
     * @return void
     */
    public function set_time_increment( ?string $start_time ): void {
        $this->time_increment = $start_time;
    }

    /**
     * Get the order of play for the tournament.
     *
     * @return array|null
     */
    public function get_order_of_play(): ?array {
        return $this->order_of_play;
    }

    /**
     * Set the order of play for the tournament.
     *
     * @param string|array|null $order_of_play The order of play data, or null to clear it.
     *
     * @return void
     */
    public function set_order_of_play( string|array|null $order_of_play = null ): void {
        $this->order_of_play = (array) $order_of_play;
    }

    /**
     * Get the stored information for the current instance.
     *
     * @return object|null The information object, or null if no information is available.
     */
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

    /**
     * Prepares the object data for serialization, excluding the competition reference.
     *
     * @return array The object data to serialize.
     */
    public function __serialize(): array {
        $data = get_object_vars( $this );
        unset( $data['competition'] );

        return $data;
    }

    /**
     * Restores the object's state from serialized data.
     *
     * @param array $data The serialized data used to restore object properties.
     *
     * @return void
     */
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
        if ( isset( $this->competition ) && (int) $this->competition->id === (int) $this->competition_id ) {
            return $this->competition;
        }

        return null;
    }

    /**
     * Set the competition associated with this instance.
     *
     * @param Competition|null $competition The competition to assign, or null to clear the current competition.
     *
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
     *
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
            // Entry validation: null/unset means all season events; explicit [] means closed/none.
            if ( is_null( $entry_events ) ) {
                return null;
            }

            return is_array( $entry_events ) ? array_map( 'intval', $entry_events ) : array();
        }

        // Frontend viewing: null/unset or empty [] means all season events allowed.
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
     *
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

        return in_array( $event_obj->id, $allowed_ids, true );
    }

    /**
     * Save the tournament-specific entry event selection.
     *
     * @param array $event_ids selected event IDs.
     *
     * @return bool
     */
    public function set_entry_events( array $event_ids ): bool {
        $information               = is_object( $this->information ) ? clone $this->information : new stdClass();
        $information->entry_events = array_values( array_unique( array_map( 'intval', $event_ids ) ) );
        $this->set_information( $information );

        return true;
    }

    /**
     * Get events available for entry in this tournament.
     *
     * @param iterable $events Optional in-memory event collection.
     *
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
     * Get a meta value by key.
     *
     * @param string $key The meta key to retrieve.
     * @param mixed  $default_value The default value to return if the key does not exist.
     *
     * @return mixed
     */
    public function get_meta( string $key, mixed $default_value = null ): mixed {
        return $this->meta[ $key ] ?? $default_value;
    }

    /**
     * Set a meta value by key.
     *
     * @param string $key The meta key to set.
     * @param mixed  $value The meta value to store.
     *
     * @return void
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

    /**
     * Set the tournament end date.
     *
     * @param string|null $date_end The tournament end date, or null to unset it.
     *
     * @return void
     */
    public function set_end_date( ?string $date_end ): void {
        $this->date_end = $date_end;
    }

    /**
     * Set the closing date.
     *
     * @param string|null $date_closing The closing date, or null to unset it.
     *
     * @return void
     */
    public function set_closing_date( ?string $date_closing ): void {
        $this->date_closing = $date_closing;
    }

    /**
     * Set the withdrawal date.
     *
     * @param string|null $date_withdrawal The withdrawal date, or null to clear it.
     *
     * @return void
     */
    public function set_withdrawal_date( ?string $date_withdrawal ): void {
        $this->date_withdrawal = $date_withdrawal;
    }

    /**
     * Set the opening date.
     *
     * @param string|null $date_open The opening date, or null to unset it.
     *
     * @return void
     */
    public function set_opening_date( ?string $date_open ): void {
        $this->date_open = $date_open;
    }

    /**
     * Set the tournament start date.
     *
     * @param string|null $date_start The start date to assign, or null to unset it.
     *
     * @return void
     */
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
            ++$i;
        }

        return $match_dates;
    }

    /**
     * Set the venue name.
     *
     * @param string|null $venue_name The venue name, or null to unset it.
     *
     * @return void
     */
    public function set_venue_name( ?string $venue_name ): void {
        $this->venue_name = $venue_name;
    }

    /**
     * Get the venue name.
     *
     * @return string|null The venue name, or null if no venue name is set.
     */
    public function get_venue_name(): ?string {
        return $this->venue_name;
    }

    /**
     * Set whether the object is active.
     *
     * @param bool $is_active Whether the object should be active.
     *
     * @return void
     */
    public function set_is_active( bool $is_active ): void {
        $this->is_active = $is_active;
    }

    /**
     * Set the competitions for the tournament.
     *
     * @param array $competitions The competitions to assign.
     *
     * @return void
     */
    public function set_competitions( array $competitions ): void {
        $this->competitions = $competitions;
    }

    /**
     * Get the competitions associated with the instance.
     *
     * @return array
     */
    public function get_competitions(): array {
        return $this->competitions;
    }

    /**
     * Set the events.
     *
     * @param array $events The events to set.
     *
     * @return void
     */
    public function set_events( array $events ): void {
        $this->events = $events;
    }

    /**
     * Get the events.
     *
     * @return array The events.
     */
    public function get_events(): array {
        return $this->events;
    }

    /**
     * Set the players for the tournament.
     *
     * @param array $players The players to assign.
     *
     * @return void
     */
    public function set_players( array $players ): void {
        $this->players = $players;
    }

    /**
     * Get the players.
     *
     * @return array The players.
     */
    public function get_players(): array {
        return $this->players;
    }

    /**
     * Set the current phase.
     *
     * @param string $current_phase The current phase to set.
     *
     * @return void
     */
    public function set_current_phase( string $current_phase ): void {
        $this->current_phase = $current_phase;
    }

    /**
     * Set whether the tournament is complete.
     *
     * @param bool $is_complete Whether the tournament is complete.
     *
     * @return void
     */
    public function set_is_complete( bool $is_complete ): void {
        $this->is_complete = $is_complete;
    }

    /**
     * Set whether the tournament is closed.
     *
     * @param bool $is_closed Whether the tournament is closed.
     *
     * @return void
     */
    public function set_is_closed( bool $is_closed ): void {
        $this->is_closed = $is_closed;
    }

    /**
     * Set whether the tournament has started.
     *
     * @param bool $is_started Whether the tournament has started.
     *
     * @return void
     */
    public function set_is_started( bool $is_started ): void {
        $this->is_started = $is_started;
    }

    /**
     * Set whether the entry is marked as a withdrawal.
     *
     * @param bool $is_withdrawal Whether the entry is a withdrawal.
     *
     * @return void
     */
    public function set_is_withdrawal( bool $is_withdrawal ): void {
        $this->is_withdrawal = $is_withdrawal;
    }

    /**
     * Set whether the tournament is open.
     *
     * @param bool $is_open Whether the tournament is open.
     *
     * @return void
     */
    public function set_is_open( bool $is_open ): void {
        $this->is_open = $is_open;
    }

    /**
     * Sets the final states or values.
     *
     * @param array $finals Final states or values to assign.
     *
     * @return void
     */
    public function set_finals( array $finals ): void {
        $this->finals = $finals;
    }

    /**
     * Gets the final states or values.
     *
     * @return array Final states or values.
     */
    public function get_finals(): array {
        return $this->finals;
    }

    /**
     * Sets the fees data.
     *
     * @param object $fees Fees data to assign.
     *
     * @return void
     */
    public function set_fees( object $fees ): void {
        $this->fees = $fees;
    }

    /**
     * Retrieves the fees object.
     *
     * @return object The fees object.
     */
    public function get_fees(): object {
        return $this->fees;
    }

    /**
     * Sets the matches data.
     *
     * @param array $matches The matches data to set.
     *
     * @return void
     */
    public function set_matches( array $matches ): void {
        $this->matches = $matches;
    }

    /**
     * Retrieves the matches array.
     *
     * @return array The matches array.
     */
    public function get_matches(): array {
        return $this->matches;
    }

    /**
     * Sets the payments amount.
     *
     * @param float|null $payments The payments amount, or null if not set.
     *
     * @return void
     */
    public function set_payments( float|null $payments ): void {
        $this->payments = $payments;
    }

    /**
     * Retrieves the payments amount.
     *
     * @return float|null The payments amount, or null if not set.
     */
    public function get_payments(): float|null {
        return $this->payments;
    }

    /**
     * Sets the entries collection.
     *
     * @param array $entries The entries to set.
     *
     * @return void
     */
    public function set_entries( array $entries ): void {
        $this->entries = $entries;
    }

    /**
     * Retrieves the entries array.
     *
     * @return array The entries array.
     */
    public function get_entries(): array {
        return $this->entries;
    }

    /**
     * Sets the match dates.
     *
     * @param array $match_dates The match dates to set.
     *
     * @return void
     */
    public function set_match_dates( array $match_dates ): void {
        $this->match_dates = $match_dates;
    }

    /**
     * Retrieves the match dates.
     *
     * @return array The match dates.
     */
    public function get_match_dates(): array {
        return $this->match_dates;
    }

    /**
     * Retrieves the type.
     *
     * @return string The type.
     */
    public function get_type(): string {
        return $this->type;
    }
}
