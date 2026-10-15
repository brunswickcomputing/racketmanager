<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Competition\Competition_Hydration_DTO;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\DTO\Tournament\Tournament_Request_DTO;
use Racketmanager\Domain\View_Models\Tournament_Email_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use stdClass;

    require_once __DIR__ . '/../../wp-stubs.php';

    use function Racketmanager\seo_url;

final class Tournament_Test extends TestCase {
    use Manages_Wordpress_Globals;

    public function test_set_tournament_info_sets_correct_data(): void {
        global $racketmanager;
        // Ensure $racketmanager has the expected properties to avoid dynamic property warnings in PHP 8.2+
        if ( ! isset( $racketmanager ) || ! property_exists( $racketmanager, 'site_url' ) ) {
            $racketmanager = new class() {
                public $site_url;
                public $date_format;
                public function get_options() { return []; }
            };
        }
        $racketmanager->site_url = 'http://example.com';
        $racketmanager->date_format = 'Y-m-d';

        $data = new stdClass();
        $data->id = 1;
        $data->name = 'Test Tournament';
        $data->date_end = '2026-12-31';
        $data->date_start = '2026-12-01';
        $data->date_open = '2026-11-01';
        $data->date_closing = '2026-11-30';
        $data->date_withdrawal = '2026-12-05';
        $data->season = '2026';

        $tournament = new Tournament($data);
        $view_model = new Tournament_Email_View_Model($tournament);

        $this->assertEquals('/tournament/test-tournament/', $tournament->link);
        $this->assertEquals('http://example.com/entry-form/test-tournament-tournament/', $tournament->entry_link);
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
        $this->service_calls = [];
        $this->register_competitions( [] );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Provide a container whose competition service knows the given competitions.
     * Unknown ids throw Competition_Not_Found_Exception like the real service.
     *
     * @param Competition[] $competitions Competitions keyed by id.
     */
    private function register_competitions( array $competitions ): void {
        $service = new class( $competitions, $this->service_calls ) {
            public function __construct( private array $competitions, private array &$calls ) {}

            public function get_by_id( null|string|int $id ): Competition {
                $this->calls[] = (int) $id;
                if ( ! isset( $this->competitions[ (int) $id ] ) ) {
                    throw new Competition_Not_Found_Exception( 'Competition not found' );
                }
                return $this->competitions[ (int) $id ];
            }
        };

        $GLOBALS['racketmanager'] = new class( $service ) {
            public $container;
            public $site_url    = 'http://example.com';
            public $date_format = 'Y-m-d';

            public function get_options(): array {
                return [];
            }

            public function __construct( $service ) {
                $this->container = new class( $service ) {
                    public function __construct( private $service ) {}

                    public function has( string $name ): bool {
                        return 'competition_service' === $name;
                    }

                    public function get( string $name ) {
                        return $this->service;
                    }
                };
            }
        };
    }

    /**
     * Register events as database rows for the wpdb double and as hydrated Event objects.
     *
     * @param Event[] $events Events.
     */
    private function register_events( array $events ): void {
        $rows = [];
        $map  = [];
        foreach ( $events as $event ) {
            $rows[]            = (object) [ 'id' => $event->id ];
            $map[ $event->id ] = $event;
        }
        $GLOBALS['wp_stubs_events'] = $map;

        $wpdb                       = new \wpdb();
        $wpdb->get_results_callback = function ( string $query ) use ( $rows ): array {
            $this->queries[] = $query;
            return str_contains( $query, 'racketmanager_events' ) ? $rows : [];
        };
        $GLOBALS['wpdb'] = $wpdb;
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
        return new Tournament( $data );
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

    public function test_is_event_allowed_resolves_competition_through_service_once(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ), $this->create_event( 102, 'Ladies Doubles' ) ] );
        $tournament = $this->create_tournament( [ 'entry_events' => [ 101 ] ] );

        $this->assertTrue( $tournament->is_event_allowed( 101, true ) );
        $this->assertTrue( $tournament->is_event_allowed( 101, false ) );
        $this->assertFalse( $tournament->is_event_allowed( 102, true ) );
        $this->assertFalse( $tournament->is_event_allowed( 102, false ) );
        $this->assertSame( [ 10 ], $this->service_calls );
    }

    public function test_is_event_allowed_entry_closed_but_viewing_open_for_empty_allowlist(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ) ] );
        $tournament = $this->create_tournament( [ 'entry_events' => [] ] );

        $this->assertTrue( $tournament->is_event_allowed( 101, false ) );
        $this->assertFalse( $tournament->is_event_allowed( 101, true ) );
    }

    public function test_is_event_allowed_unrestricted_when_information_absent(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ) ] );
        $tournament = $this->create_tournament( null );

        $this->assertTrue( $tournament->is_event_allowed( 101, true ) );
        $this->assertTrue( $tournament->is_event_allowed( 101, false ) );
    }

    public function test_is_event_allowed_rejects_foreign_competition_and_out_of_season_events(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $foreign   = $this->create_event( 101, 'Men Doubles', 999 );
        $old       = $this->create_event( 102, 'Old Event', 10, [ '2024' => [ 'name' => '2024' ] ] );
        $this->register_events( [ $foreign, $old ] );
        $tournament = $this->create_tournament( [ 'entry_events' => [ 101, 102 ] ] );

        $this->assertFalse( $tournament->is_event_allowed( $foreign, true ) );
        $this->assertFalse( $tournament->is_event_allowed( 101, true ) );
        $this->assertFalse( $tournament->is_event_allowed( $old, true ) );
        $this->assertFalse( $tournament->is_event_allowed( 102, false ) );
    }

    public function test_is_event_allowed_fails_closed_when_competition_is_not_found(): void {
        $this->register_competitions( [] );
        $this->register_events( [ $this->create_event( 101 ) ] );
        $tournament = $this->create_tournament( null );

        $this->assertNull( $tournament->get_competition_object() );
        $this->assertFalse( $tournament->is_event_allowed( 101, false ) );
        $this->assertFalse( $tournament->is_event_allowed( 101, true ) );
        $this->assertSame( [], $tournament->get_entry_events() );
    }

    public function test_competition_cache_is_not_reused_for_a_different_competition_id(): void {
        $this->register_competitions( [ 100 => $this->create_competition( 100 ), 200 => $this->create_competition( 200 ) ] );
        $own_event = $this->create_event( 101, 'Men Doubles', 100 );
        $this->register_events( [ $own_event ] );
        $tournament = $this->create_tournament( null, 100 );

        $this->assertTrue( $tournament->is_event_allowed( $own_event, true ) );

        $tournament->set_competition_id( 200 );

        $this->assertFalse( $tournament->is_event_allowed( $own_event, true ) );
        $this->assertFalse( $tournament->is_event_allowed( 101, true ) );
        $this->assertSame( 200, $tournament->get_competition_object()->id );
        $this->assertSame( [ 100, 200 ], $this->service_calls );
    }

    public function test_competition_cache_is_revalidated_when_competition_id_is_assigned_directly(): void {
        $this->register_competitions( [ 100 => $this->create_competition( 100 ), 200 => $this->create_competition( 200 ) ] );
        $tournament = $this->create_tournament( null, 100 );
        $this->assertSame( 100, $tournament->get_competition_object()->id );

        $tournament->competition_id = 200;

        $this->assertSame( 200, $tournament->get_competition_object()->id );
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

        $events = $tournament->get_entry_events();

        $this->assertSame( [ 101, 103 ], array_map( static fn( Event $event ): int => $event->id, $events ) );
        $this->assertCount( 1, $this->queries );
        $this->assertStringNotContainsString( 'league_teams', $this->queries[0] );
    }

    public function test_get_entry_events_returns_all_season_events_when_unrestricted_and_none_when_closed(): void {
        $this->register_competitions( [ 10 => $this->create_competition() ] );
        $this->register_events( [ $this->create_event( 101 ), $this->create_event( 102, 'Ladies Doubles' ) ] );

        $this->assertCount( 2, $this->create_tournament( null )->get_entry_events() );
        $this->assertSame( [], $this->create_tournament( [ 'entry_events' => [] ] )->get_entry_events() );
    }

    public function test_set_entry_events_updates_information_and_allowlist(): void {
        $tournament = $this->create_tournament( [ 'other' => 'kept' ] );

        $this->assertTrue( $tournament->set_entry_events( [ '5', 7, 5 ] ) );

        $this->assertSame( [ 5, 7 ], $tournament->get_allowlist_event_ids( true ) );
        $this->assertSame( 'kept', $tournament->information->other );
    }
}
