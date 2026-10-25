<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Domain\Tournament;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Domain\DTO\Tournament\Tournament_Request_DTO;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use stdClass;

    require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

    use function Racketmanager\seo_url;

final class Tournament_Test extends TestCase {
    use Manages_Wordpress_Globals;

    public function test_set_tournament_info_sets_correct_data(): void {
        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Test Tournament';
        $data->date_end = '2026-12-31';
        $data->date_start = '2026-12-01';
        $data->date_open = '2026-11-01';
        $data->date_closing = '2026-11-30';
        $data->date_withdrawal = '2026-12-05';
        $data->season = '2026';

        $tournament = new Tournament( $data );
        $presenter = new \Racketmanager\Presentation\Presenters\Tournament_Presenter( 'Y-m-d' );
        $view_model = $presenter->present_dates( $tournament );

        $this->assertEquals('/tournament/test-tournament/', $presenter->get_link( $tournament ));
        $this->assertEquals('http://example.com/entry-form/test-tournament-tournament/', $presenter->get_entry_link( $tournament, 'http://example.com' ));
        $this->assertEquals('2026-12-31', $view_model->date_display);
        $this->assertEquals('2026-11-30', $view_model->date_closing_display);
        $this->assertEquals('2026-12-05', $view_model->date_withdrawal_display);
        $this->assertEquals('2026-11-01', $view_model->date_open_display);
        $this->assertEquals('2026-12-01', $view_model->date_start_display);
        
        // $this->assertNotEmpty($tournament->current_phase);
        $this->assertIsArray($tournament->finals);
        $this->assertCount(6, $tournament->finals);
    }

    public function test_update_from_request(): void {
        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Original Name';
        $data->date = '2026-12-31';
        $tournament = new Tournament($data);

        $request_data = [
            'tournamentName'   => 'Updated Name',
            'competition_id'   => 10,
            'season'           => '2027',
            'venue'            => 5,
            'dateEnd'          => '2027-12-31',
            'dateClose'        => '2027-11-30',
            'dateWithdraw'     => '2027-12-05',
            'dateOpen'         => '2027-11-01',
            'dateStart'        => '2027-12-01',
            'competition_code' => 'COMP123',
            'grade'            => 'A',
            'num_entries'      => 32,
        ];
        $request = new Tournament_Request_DTO($request_data);

        $tournament->update_from_request($request);

        $this->assertEquals('Updated Name', $tournament->name);
        $this->assertEquals(10, $tournament->competition_id);
        $this->assertEquals('2027', $tournament->season);
        $this->assertEquals(5, $tournament->venue);
        $this->assertEquals('2027-12-31', $tournament->date_end);
        $this->assertEquals('2027-11-30', $tournament->date_closing);
        $this->assertEquals('2027-12-05', $tournament->date_withdrawal);
        $this->assertEquals('2027-11-01', $tournament->date_open);
        $this->assertEquals('2027-12-01', $tournament->date_start);
        $this->assertEquals('COMP123', $tournament->competition_code);
        $this->assertEquals('A', $tournament->grade);
        $this->assertEquals(32, $tournament->num_entries);
    }

    public function test_calculate_default_match_dates(): void {
        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Test';
        $data->date_end = '2026-12-31';
        $tournament = new Tournament($data);

        // finals[R1] = round 6
        // finals[R2] = round 5
        // finals[R4] = round 4
        // finals[QF] = round 3
        // finals[SF] = round 2
        // finals[F]  = round 1

        $match_dates = $tournament->calculate_default_match_dates(7);
        ksort($match_dates);

        // Final (round 1 - 1 = 0) should be date_end
        $this->assertEquals('2026-12-31', $match_dates[0]);
        // Semi-final (round 2 - 1 = 1) should be date_end - 7 days
        $this->assertEquals('2026-12-24', $match_dates[1]);
        // Quarter-final (round 3 - 1 = 2) should be previous - 7 days
        $this->assertEquals('2026-12-17', $match_dates[2]);
    }

    private array $queries = [];

    /**
     * Competition lookups made through the container's competition service.
     *
     * @var int[]
     */
    private array $service_calls = [];

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->queries       = [];
        $this->registered_events = [];
        $this->service_calls = [];
        $this->register_competitions( [] );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private array $competitions_by_id = [];

    /**
     * Candidate events a caller (application service) would load and pass in explicitly.
     *
     * @var Event[]
     */
    private array $registered_events = [];
    private ?Competition_Service $competition_service = null;

    /**
     * Provide a container whose competition service knows the given competitions.
     * Unknown ids throw Competition_Not_Found_Exception like the real service.
     *
     * @param Competition[] $competitions Competitions keyed by id.
     */
    private function register_competitions( array $competitions ): void {
        $this->competitions_by_id = $competitions;
        $this->competition_service = new class( $competitions, $this->service_calls ) extends Competition_Service {
            public function __construct( private array $competitions, private array &$calls ) {}

            public function get_by_id( null|string|int $id ): Competition {
                $this->calls[] = (int) $id;
                if ( ! isset( $this->competitions[ (int) $id ] ) ) {
                    throw new Competition_Not_Found_Exception( 'Competition not found' );
                }
                return $this->competitions[ (int) $id ];
            }
        };
    }

    /**
     * Register events as database rows for the wpdb double and as hydrated Event objects.
     *
     * @param Event[] $events Events.
     */
    private function register_events( array $events ): void {
        $map = [];
        foreach ( $events as $event ) {
            $map[ $event->id ] = $event;
        }
        $GLOBALS['wp_stubs_events'] = $map;

        $this->registered_events = $events;
    }

    private function create_competition( int $id = 10, array $seasons = [ '2026' => [ 'name' => '2026' ] ] ): Competition {
        return Competition_Factory::create_from_dto(
            new Competition_Hydration_DTO(
                id: $id,
                name: 'Test Tournament Competition ' . $id,
                type: Competition_Type::TOURNAMENT,
                age_group: 'Adults',
                seasons: $seasons,
                settings: []
            )
        );
    }

    private function create_event(
        int $id,
        string $name = 'Men Doubles',
        int $competition_id = 10,
        array $seasons = [ '2026' => [ 'name' => '2026' ] ]
    ): Event {
        $event                 = ( new ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $event->id             = $id;
        $event->name           = $name;
        $event->competition_id = $competition_id;
        $event->seasons        = json_encode( $seasons );
        return $event;
    }

    /**
     * Build a tournament from a database-style row, where information is a JSON string.
     */
    private function create_tournament( ?array $information, int $competition_id = 10, ?string $season = '2026' ): Tournament {
        $data                 = new stdClass();
        $data->id             = 1;
        $data->name           = 'Test Tournament';
        $data->competition_id = $competition_id;
        $data->season         = $season;
        if ( null !== $information ) {
            $data->information = json_encode( $information );
        }
        $tournament = new Tournament( $data );
        if ( isset( $this->competitions_by_id[ $competition_id ] ) ) {
            $tournament->set_competition( $this->competitions_by_id[ $competition_id ] );
        }

        return $tournament;
    }

    public function test_get_allowlist_event_ids_semantics(): void {
        $absent    = $this->create_tournament( null );
        $unset_key = $this->create_tournament( [ 'other' => 1 ] );
        $null      = $this->create_tournament( [ 'entry_events' => null ] );
        $empty     = $this->create_tournament( [ 'entry_events' => [] ] );
        $populated = $this->create_tournament( [ 'entry_events' => [ '101', 103 ] ] );

        foreach ( [ $absent, $unset_key, $null ] as $unrestricted ) {
            $this->assertNull( $unrestricted->get_allowlist_event_ids( false ) );
            $this->assertNull( $unrestricted->get_allowlist_event_ids( true ) );
        }

        $this->assertNull( $empty->get_allowlist_event_ids( false ), 'Viewing is unrestricted for []' );
        $this->assertSame( [], $empty->get_allowlist_event_ids( true ), 'Entry is closed for []' );

        $this->assertSame( [ 101, 103 ], $populated->get_allowlist_event_ids( false ) );
        $this->assertSame( [ 101, 103 ], $populated->get_allowlist_event_ids( true ) );
    }

    public function test_get_allowlist_event_ids_fails_closed_without_season(): void {
        $tournament = $this->create_tournament( [ 'entry_events' => [ 101 ] ], 10, null );

        $this->assertSame( [], $tournament->get_allowlist_event_ids( false ) );
        $this->assertSame( [], $tournament->get_allowlist_event_ids( true ) );
    }

    public function test_is_event_allowed_with_attached_competition(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ), $this->create_event( 102, 'Ladies Doubles' ) ] );
        $tournament = $this->create_tournament( [ 'entry_events' => [ 101 ] ] );

        $this->assertTrue( $tournament->is_event_allowed( 101, true, $this->registered_events ) );
        $this->assertTrue( $tournament->is_event_allowed( 101, false, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 102, true, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 102, false, $this->registered_events ) );
    }

    public function test_is_event_allowed_entry_closed_but_viewing_open_for_empty_allowlist(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ) ] );
        $tournament = $this->create_tournament( [ 'entry_events' => [] ] );

        $this->assertTrue( $tournament->is_event_allowed( 101, false, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 101, true, $this->registered_events ) );
    }

    public function test_is_event_allowed_unrestricted_when_information_absent(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ) ] );
        $tournament = $this->create_tournament( null );

        $this->assertTrue( $tournament->is_event_allowed( 101, true, $this->registered_events ) );
        $this->assertTrue( $tournament->is_event_allowed( 101, false, $this->registered_events ) );
    }

    public function test_is_event_allowed_rejects_foreign_competition_and_out_of_season_events(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $foreign   = $this->create_event( 101, 'Men Doubles', 999 );
        $old       = $this->create_event( 102, 'Old Event', 10, [ '2024' => [ 'name' => '2024' ] ] );
        $this->register_events( [ $foreign, $old ] );
        $tournament = $this->create_tournament( [ 'entry_events' => [ 101, 102 ] ] );

        $this->assertFalse( $tournament->is_event_allowed( $foreign, true, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 101, true, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( $old, true, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 102, false, $this->registered_events ) );
    }

    public function test_is_event_allowed_fails_closed_without_candidate_events(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ), $this->create_event( 102, 'Ladies Doubles' ) ] );
        $unrestricted = $this->create_tournament( null );

        $this->assertFalse( $unrestricted->is_event_allowed( 101, true ) );
        $this->assertFalse( $unrestricted->is_event_allowed( 101, false ) );
        $this->assertFalse( $unrestricted->is_event_allowed( '101', true ) );
        $this->assertFalse( $unrestricted->is_event_allowed( 'men-doubles', true ) );
        $this->assertFalse( $unrestricted->is_event_allowed( 999999, true ) );
        $this->assertFalse( $unrestricted->is_event_allowed( 101, true, [] ) );
        $this->assertFalse( $unrestricted->is_event_allowed( (object) array( 'id' => 101 ), true ) );
    }

    public function test_is_event_allowed_rejects_foreign_and_out_of_season_numeric_ids_against_loaded_events(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $local   = $this->create_event( 101, 'Men Doubles' );
        $foreign = $this->create_event( 1, 'Foreign Event', 999 );
        $old     = $this->create_event( 102, 'Old Event', 10, [ '2024' => [ 'name' => '2024' ] ] );
        $loaded  = [ $local, $foreign, $old ];
        $tournament = $this->create_tournament( null );

        $this->assertTrue( $tournament->is_event_allowed( 101, true, $loaded ) );
        $this->assertTrue( $tournament->is_event_allowed( '101', true, $loaded ) );
        $this->assertTrue( $tournament->is_event_allowed( 'men-doubles', true, $loaded ) );
        $this->assertFalse( $tournament->is_event_allowed( 1, true, $loaded ), 'Foreign competition event' );
        $this->assertFalse( $tournament->is_event_allowed( 102, true, $loaded ), 'Out of season event' );
        $this->assertFalse( $tournament->is_event_allowed( 999999, true, $loaded ), 'Unknown event' );
        $this->assertFalse( $tournament->is_event_allowed( 'foreign-event', true, $loaded ) );
        $this->assertFalse( $tournament->is_event_allowed( 1, false, $loaded ) );
    }

    public function test_is_event_allowed_does_not_read_hidden_competition_events_state(): void {
        $competition = $this->create_competition();
        $this->register_competitions( [ 10 => $competition ] );
        $competition->events = [ $this->create_event( 101 ) ];
        $tournament          = $this->create_tournament( null );

        $this->assertFalse( $tournament->is_event_allowed( 101, true ) );
        $this->assertSame( [], $tournament->get_entry_events() );
    }

    public function test_is_event_allowed_fails_closed_when_competition_is_not_found(): void {
        $this->register_competitions( [] );
        $this->register_events( [ $this->create_event( 101 ) ] );
        $tournament = $this->create_tournament( null );

        $this->assertNull( $tournament->get_competition_object() );
        $this->assertFalse( $tournament->is_event_allowed( 101, false, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 101, true, $this->registered_events ) );
        $this->assertSame( [], $tournament->get_entry_events( $this->registered_events ) );
    }

    public function test_competition_is_cleared_when_competition_id_changed(): void {
        $this->register_competitions( [ 100 => $this->create_competition( 100 ), 200 => $this->create_competition( 200 ) ] );
        $own_event = $this->create_event( 101, 'Men Doubles', 100 );
        $this->register_events( [ $own_event ] );
        $tournament = $this->create_tournament( null, 100 );

        $this->assertTrue( $tournament->is_event_allowed( $own_event, true, $this->registered_events ) );

        $tournament->set_competition_id( 200 );

        $this->assertFalse( $tournament->is_event_allowed( $own_event, true, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 101, true, $this->registered_events ) );
        $this->assertNull( $tournament->get_competition_object() );

        $tournament->set_competition( $this->competitions_by_id[200] );
        $this->assertSame( 200, $tournament->get_competition_object()->id );
    }

    public function test_is_event_allowed_handles_numeric_id_and_string_slug(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events(
            [
                $this->create_event( 101, 'Men Doubles' ),
                $this->create_event( 102, 'Ladies Doubles' ),
            ]
        );
        $tournament = $this->create_tournament( [ 'entry_events' => [ 101 ] ] );

        $this->assertTrue( $tournament->is_event_allowed( 101, true, $this->registered_events ) );
        $this->assertTrue( $tournament->is_event_allowed( 'men-doubles', true, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 102, true, $this->registered_events ) );
        $this->assertFalse( $tournament->is_event_allowed( 'ladies-doubles', true, $this->registered_events ) );
    }

    public function test_competition_cache_is_revalidated_when_competition_id_is_assigned_directly(): void {
        $this->register_competitions( [ 100 => $this->create_competition( 100 ), 200 => $this->create_competition( 200 ) ] );
        $tournament = $this->create_tournament( null, 100 );
        $this->assertSame( 100, $tournament->get_competition_object()->id );

        $tournament->competition_id = 200;

        $this->assertNull( $tournament->get_competition_object() );
    }

    public function test_get_entry_events_lists_configured_events_before_any_team_has_entered(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events(
            [
                $this->create_event( 101, 'Men Doubles' ),
                $this->create_event( 102, 'Ladies Doubles' ),
                $this->create_event( 103, 'Mixed Doubles' ),
                $this->create_event( 104, 'Old Event', 10, [ '2024' => [ 'name' => '2024' ] ] ),
            ]
        );
        $tournament = $this->create_tournament( [ 'entry_events' => [ 101, 103, 104 ] ] );

        $events = $tournament->get_entry_events( $this->registered_events );

        $this->assertSame( [ 101, 103 ], array_map( static fn( Event $event ): int => $event->id, $events ) );
    }

    public function test_get_entry_events_returns_all_season_events_when_unrestricted_and_none_when_closed(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ), $this->create_event( 102, 'Ladies Doubles' ) ] );

        $this->assertCount( 2, $this->create_tournament( null )->get_entry_events( $this->registered_events ) );
        $this->assertSame( [], $this->create_tournament( [ 'entry_events' => [] ] )->get_entry_events( $this->registered_events ) );
    }

    public function test_set_entry_events_updates_information_and_allowlist(): void {
        $tournament = $this->create_tournament( [ 'other' => 'kept' ] );

        $this->assertTrue( $tournament->set_entry_events( [ '5', 7, 5 ] ) );

        $this->assertSame( [ 5, 7 ], $tournament->get_allowlist_event_ids( true ) );
        $this->assertSame( 'kept', $tournament->information->other );
    }

    public function test_tournament_windows_return_date_range_value_objects(): void {
        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Summer Championship';
        $data->date_start = '2026-07-01';
        $data->date_end = '2026-07-15';
        $data->date_open = '2026-05-01';
        $data->date_closing = '2026-06-15';
        $data->date_withdrawal = '2026-06-20';
        $data->season = '2026';

        $tournament = new Tournament( $data );

        $tournament_window = $tournament->get_tournament_window();
        $this->assertInstanceOf( \Racketmanager\Domain\Value_Objects\Date_Range::class, $tournament_window );
        $this->assertSame( '2026-07-01', $tournament_window->get_start_string() );
        $this->assertSame( '2026-07-15', $tournament_window->get_end_string() );

        $entry_window = $tournament->get_entry_window();
        $this->assertInstanceOf( \Racketmanager\Domain\Value_Objects\Date_Range::class, $entry_window );
        $this->assertSame( '2026-05-01', $entry_window->get_start_string() );
        $this->assertSame( '2026-06-15', $entry_window->get_end_string() );
        $this->assertTrue( $entry_window->contains( '2026-05-15' ) );

        $withdrawal_window = $tournament->get_withdrawal_window();
        $this->assertInstanceOf( \Racketmanager\Domain\Value_Objects\Date_Range::class, $withdrawal_window );
        $this->assertSame( '2026-05-01', $withdrawal_window->get_start_string() );
        $this->assertSame( '2026-06-20', $withdrawal_window->get_end_string() );
    }

    public function test_tournament_windows_handle_null_zero_and_inverted_dates(): void {
        $data = new stdClass();
        $data->id = 2;
        $data->date_start = null;
        $data->date_end = '0000-00-00';
        $data->date_open = '';
        $data->date_closing = 'invalid-date';
        $data->date_withdrawal = null;

        $tournament = new Tournament( $data );

        $this->assertNull( $tournament->get_tournament_window() );
        $this->assertNull( $tournament->get_entry_window() );
        $this->assertNull( $tournament->get_withdrawal_window() );
        $this->assertFalse( $tournament->is_active() );
        $this->assertTrue( $tournament->is_complete() ); // HEAD equivalence: '0000-00-00' is treated as elapsed

        // HEAD equivalence: a null/missing end date is normalised to '' and treated as elapsed
        $data_null = new stdClass();
        $data_null->date_end = null;
        $tournament_null = new Tournament( $data_null );
        $this->assertTrue( $tournament_null->is_complete() );
        $this->assertSame( 'end', $tournament_null->get_current_phase() );

        // Inverted dates (start > end)
        $data_inverted = new stdClass();
        $data_inverted->date_start = '2026-08-01';
        $data_inverted->date_end = '2026-07-01';
        $tournament_inverted = new Tournament( $data_inverted );
        $this->assertNull( $tournament_inverted->get_tournament_window() );
    }

    public function test_tournament_phase_boundary_transitions(): void {
        // Build dates around today
        $today = gmdate( 'Y-m-d' );
        $yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
        $tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day' ) );
        $past_2 = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
        $past_10 = gmdate( 'Y-m-d', strtotime( '-10 days' ) );
        $future_10 = gmdate( 'Y-m-d', strtotime( '+10 days' ) );
        $future_20 = gmdate( 'Y-m-d', strtotime( '+20 days' ) );

        // 1. Ended phase: today > date_end
        $t_end = new Tournament( (object) [
            'date_open' => $past_10,
            'date_closing' => $past_10,
            'date_withdrawal' => $past_2,
            'date_start' => $past_2,
            'date_end' => $yesterday,
        ] );
        $this->assertSame( 'end', $t_end->get_current_phase() );
        $this->assertTrue( $t_end->is_complete() );

        // 2. Started phase: today >= date_start and today <= date_end
        $t_start = new Tournament( (object) [
            'date_open' => $past_10,
            'date_closing' => $past_10,
            'date_withdrawal' => $past_2,
            'date_start' => $today,
            'date_end' => $future_10,
        ] );
        $this->assertSame( 'start', $t_start->get_current_phase() );
        $this->assertTrue( $t_start->is_started() );
        $this->assertFalse( $t_start->is_complete() );

        // 3. Withdraw phase: today > date_withdrawal, before date_start
        $t_withdraw = new Tournament( (object) [
            'date_open' => $past_10,
            'date_closing' => $past_10,
            'date_withdrawal' => $yesterday,
            'date_start' => $tomorrow,
            'date_end' => $future_10,
        ] );
        $this->assertSame( 'withdraw', $t_withdraw->get_current_phase() );
        $this->assertTrue( $t_withdraw->is_withdrawal() );

        // 4. Closed phase: today > date_closing, before date_withdrawal
        $t_close = new Tournament( (object) [
            'date_open' => $past_10,
            'date_closing' => $yesterday,
            'date_withdrawal' => $tomorrow,
            'date_start' => $future_10,
            'date_end' => $future_20,
        ] );
        $this->assertSame( 'close', $t_close->get_current_phase() );
        $this->assertTrue( $t_close->is_closed() );
        $this->assertTrue( $t_close->is_active() );

        // 5. Open phase: today >= date_open and today <= date_closing
        $t_open = new Tournament( (object) [
            'date_open' => $today,
            'date_closing' => $tomorrow,
            'date_withdrawal' => $future_10,
            'date_start' => $future_10,
            'date_end' => $future_20,
        ] );
        $this->assertSame( 'open', $t_open->get_current_phase() );
        $this->assertTrue( $t_open->is_open() );
        $this->assertFalse( $t_open->is_active() );

        // 6. Before open / unconfigured
        $t_future = new Tournament( (object) [
            'date_open' => $tomorrow,
            'date_closing' => $future_10,
            'date_end' => $future_20,
        ] );
        $this->assertSame( '', $t_future->get_current_phase() );
        $this->assertFalse( $t_future->is_open() );
    }

    public function test_tournament_phase_full_head_equivalence_matrix(): void {
        $today = gmdate( 'Y-m-d' );
        $yesterday = gmdate( 'Y-m-d', strtotime( '-1 day' ) );
        $tomorrow = gmdate( 'Y-m-d', strtotime( '+1 day' ) );

        $values = [ null, '', '0000-00-00', $yesterday, $today, $tomorrow ];

        $head_evaluator = function( array $dates ) use ( $today ): array {
            $date_end = $dates['date_end'] ?? ( $dates['date'] ?? '' );
            $date_end = $date_end ?? '';
            $date_start = $dates['date_start'] ?? null;
            $date_withdrawal = $dates['date_withdrawal'] ?? null;
            $date_closing = $dates['date_closing'] ?? null;
            $date_open = $dates['date_open'] ?? null;

            $phase = '';
            $is_complete = false;
            $is_started = false;
            $is_withdrawal = false;
            $is_closed = false;
            $is_open = false;
            $is_active = false;

            if ( $today > $date_end ) {
                $phase = 'end';
                $is_complete = true;
            } else {
                if ( ! empty( $date_start ) && $today >= $date_start ) {
                    $phase = 'start';
                    $is_started = true;
                } elseif ( ! empty( $date_withdrawal ) && $today > $date_withdrawal ) {
                    $phase = 'withdraw';
                    $is_withdrawal = true;
                } elseif ( ! empty( $date_closing ) && $today > $date_closing ) {
                    $phase = 'close';
                    $is_closed = true;
                } elseif ( ! empty( $date_open ) && $today >= $date_open ) {
                    $phase = 'open';
                    $is_open = true;
                }
            }
            if ( isset( $date_closing ) && $date_closing <= $today ) {
                $is_active = true;
            }

            return [
                'phase' => $phase,
                'is_complete' => $is_complete,
                'is_started' => $is_started,
                'is_withdrawal' => $is_withdrawal,
                'is_closed' => $is_closed,
                'is_open' => $is_open,
                'is_active' => $is_active,
            ];
        };

        foreach ( $values as $v_end ) {
            foreach ( $values as $v_start ) {
                foreach ( $values as $v_with ) {
                    foreach ( $values as $v_close ) {
                        foreach ( $values as $v_open ) {
                            $dates = [
                                'date_end' => $v_end,
                                'date_start' => $v_start,
                                'date_withdrawal' => $v_with,
                                'date_closing' => $v_close,
                                'date_open' => $v_open,
                            ];
                            $expected = $head_evaluator( $dates );
                            $tournament = new Tournament( (object) $dates );

                            $this->assertSame( $expected['phase'], $tournament->get_current_phase() );
                            $this->assertSame( $expected['is_complete'], $tournament->is_complete() );
                            $this->assertSame( $expected['is_started'], $tournament->is_started() );
                            $this->assertSame( $expected['is_withdrawal'], $tournament->is_withdrawal() );
                            $this->assertSame( $expected['is_closed'], $tournament->is_closed() );
                            $this->assertSame( $expected['is_open'], $tournament->is_open() );
                            $this->assertSame( $expected['is_active'], $tournament->is_active() );
                        }
                    }
                }
            }
        }
    }
}
