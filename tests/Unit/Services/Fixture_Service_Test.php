<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Fixture\Service_Provider;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Fixture_Service_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $fixture_repository;
    private $rubber_repository;
    private $notification_service;
    private $registration_service;
    private $competition_service;
    private $team_service;
    private $repository_provider;
    private $service_provider;
    private Fixture_Service $service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $GLOBALS['racketmanager']              = new stdClass();
        $GLOBALS['racketmanager']->date_format = 'Y-m-d';
        $GLOBALS['racketmanager']->time_format = 'H:i';

        $GLOBALS['wpdb'] = new class {
            public string $racketmanager_rubbers = 'wp_racketmanager_rubbers';
            public string $racketmanager_rubber_players = 'wp_racketmanager_rubber_players';
            public int $insert_id = 1;

            public function prepare( string $query, ...$args ): string {
                return $query;
            }

            public function query( string $query ): int {
                return 1;
            }

            public function get_results( string $query ): array {
                return [];
            }

            public function get_row( string $query ): ?object {
                return null;
            }
        };

        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
        $this->notification_service = $this->createMock( Notification_Service::class );
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->team_service = $this->createMock( Team_Service::class );

        $this->repository_provider = $this->createMock( Repository_Provider::class );
        $this->repository_provider->method( 'get_fixture_repository' )->willReturn( $this->fixture_repository );
        $this->repository_provider->method( 'get_rubber_repository' )->willReturn( $this->rubber_repository );
        $this->repository_provider->method( 'get_league_repository' )->willReturn( $this->createMock( League_Repository_Interface::class ) );
        $this->repository_provider->method( 'get_team_repository' )->willReturn( $this->createMock( Team_Repository_Interface::class ) );
        $this->repository_provider->method( 'get_club_repository' )->willReturn( $this->createMock( Club_Repository_Interface::class ) );

        $permission_service = new Fixture_Permission_Service( $this->repository_provider, $this->registration_service, [] );
        $detail_service = new Fixture_Detail_Service(
            $this->repository_provider,
            $this->competition_service,
            $this->team_service,
            $permission_service,
            $this->createMock( Fixture_Link_Service::class )
        );

        $this->service = new Fixture_Service(
            $this->repository_provider,
            $this->service_provider_mock( $permission_service, $detail_service ),
            $permission_service,
            $detail_service
        );

        $container = new class( $this->fixture_repository, $this->registration_service ) {
            private $repo;
            private $registration_service;
            public function __construct( $repo, $reg_service ) {
                $this->repo = $repo;
                $this->registration_service = $reg_service;
            }
            public function has( $name ): bool {
                return in_array( $name, [ 'fixture_repository', 'registration_service' ], true );
            }
            public function get( $name ) {
                if ( 'fixture_repository' === $name ) {
                    return $this->repo;
                }
                if ( 'registration_service' === $name ) {
                    return $this->registration_service;
                }
                return null;
            }
        };
        $GLOBALS['racketmanager']->container = $container;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function service_provider_mock( $permission_service, $detail_service ) {
        $service_provider = $this->createMock( Service_Provider::class );
        $service_provider->method( 'get_notification_service' )->willReturn( $this->notification_service );
        $service_provider->method( 'get_registration_service' )->willReturn( $this->registration_service );
        $service_provider->method( 'get_competition_service' )->willReturn( $this->competition_service );
        $service_provider->method( 'get_team_service' )->willReturn( $this->team_service );

        $service_provider->method( 'get_fixture_permission_service' )->willReturn( $permission_service );
        $service_provider->method( 'get_fixture_detail_service' )->willReturn( $detail_service );

        return $service_provider;
    }

    public function test_update_fixture_location_updates_and_saves(): void {
        $fixture = $this->createMock( Fixture::class );
        $location = 'New Location';

        $fixture->expects( $this->once() )
            ->method( 'set_location' )
            ->with( $location );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $fixture );

        $this->service->update_fixture_location( $fixture, $location );
    }

    public function test_update_fixture_date_updates_fixture_and_rubbers(): void {
        $fixture_id = 123;
        $new_date = '2026-04-01 19:00:00';
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( $fixture_id );

        $rubber1 = $this->createMock( Rubber::class );
        $rubber2 = $this->createMock( Rubber::class );
        $rubbers = [ $rubber1, $rubber2 ];

        $fixture->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $new_date );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $fixture );

        $this->rubber_repository->expects( $this->once() )
            ->method( 'find_by_fixture_id' )
            ->with( $fixture_id )
            ->willReturn( $rubbers );

        $rubber1->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $new_date );
        $rubber2->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $new_date );

        $this->rubber_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Rubber::class ) );

        $this->service->update_fixture_date( $fixture, $new_date );
    }

    public function test_update_fixture_date_sets_original_date_and_sends_notification(): void {
        $fixture_id = 456;
        $new_date = '2026-05-01 18:30:00';
        $original_date = '2026-05-01 10:00:00';
        
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( $fixture_id );
        // First call to get_date_original returns null, then it returns the value
        $fixture->method( 'get_date_original' )->willReturnOnConsecutiveCalls( null, $original_date );

        $fixture->expects( $this->once() )
            ->method( 'set_date_original' )
            ->with( $original_date );

        $this->rubber_repository->method( 'find_by_fixture_id' )->willReturn( [] );

        $this->notification_service->expects( $this->once() )
            ->method( 'send_date_change_notification' )
            ->with( $fixture );

        $this->service->update_fixture_date( $fixture, $new_date, $original_date );
    }

    public function test_set_fixture_date_calculates_correct_date_and_time(): void {
        $fixture = $this->createMock( Fixture::class );
        $start_date = '2026-06-01'; // Monday
        $match_day = 'Wednesday';   // +2 days
        $match_time = '19:30';
        
        // Mock set_date and other methods since set_fixture_date calls update_fixture_date
        $fixture->method( 'get_id' )->willReturn( 789 );
        $this->rubber_repository->method( 'find_by_fixture_id' )->willReturn( [] );

        // Wed June 3rd 2026 19:30:00
        $expected_date = '2026-06-03 19:30';

        $fixture->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $expected_date );

        $this->service->set_fixture_date( $fixture, $start_date, $match_day, $match_time );
    }

    public function test_set_fixture_date_handles_long_start_date_string(): void {
        $fixture = $this->createMock( Fixture::class );
        $start_date = '2026-06-01T00:00:00Z'; // Longer than 10 chars
        $match_day = null;
        $match_time = '10:00';
        
        $fixture->method( 'get_id' )->willReturn( 111 );
        $this->rubber_repository->method( 'find_by_fixture_id' )->willReturn( [] );

        $expected_date = '2026-06-01 10:00';

        $fixture->expects( $this->once() )
            ->method( 'set_date' )
            ->with( $expected_date );

        $this->service->set_fixture_date( $fixture, $start_date, $match_day, $match_time );
    }

    private function mock_wpdb_rubber_captor( array &$created_types ): void {
        $GLOBALS['wpdb'] = new class( $created_types ) {
            public string $racketmanager_rubbers = 'wp_racketmanager_rubbers';
            public string $racketmanager_rubber_players = 'wp_racketmanager_rubber_players';
            public int $insert_id = 1;
            public array $types;

            public function __construct( array &$types ) {
                $this->types = &$types;
            }

            public function prepare( string $query, ...$args ): string {
                if ( str_contains( $query, 'racketmanager_rubbers' ) && str_contains( $query, 'INSERT' ) ) {
                    $this->types[] = $args[3] ?? '';
                }
                return vsprintf( str_replace( '%s', "'%s'", str_replace( '%d', '%d', $query ) ), $args );
            }

            public function query( string $query ): int {
                return 1;
            }

            public function get_results( string $query ): array {
                return [];
            }

            public function get_row( string $query ): ?object {
                return null;
            }
        };
    }

    private function create_league_fixture( int $id = 555, ?string $final = null, string $date = '2026-06-01 19:00:00' ): Fixture {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( $id );
        $fixture->method( 'get_date' )->willReturn( $date );
        $fixture->method( 'get_final' )->willReturn( $final );
        return $fixture;
    }

    private function create_league_fixture_structure(
        string $type = 'MT',
        int $num_rubbers = 6,
        bool $is_championship = false,
        string $scoring = 'standard',
        string $reverse_rubbers = '0',
        bool $home_away = false
    ): stdClass {
        $league                         = new stdClass();
        $league->is_championship        = $is_championship;
        $league->num_rubbers            = $num_rubbers;
        $league->type                   = $type;
        $league->event                  = new stdClass();
        $league->event->scoring         = $scoring;
        $league->event->reverse_rubbers = $reverse_rubbers;
        $league->current_season         = [ 'home_away' => $home_away ];
        return $league;
    }

    private function assert_created_rubbers( Fixture $fixture, stdClass $league, array $expected_types ): void {
        $created_types = [];
        $this->rubber_repository->method( 'save' )->willReturnCallback( function ( $rubber ) use ( &$created_types ) {
            $created_types[] = $rubber->get_type();
            return 1;
        } );
        $this->service->create_fixture( $fixture, $league );
        $this->assertCount( count( $expected_types ), $created_types );
        $this->assertSame( $expected_types, $created_types );
    }

    public function test_create_fixture_creates_associated_rubbers_for_mixed_team_event(): void {
        $fixture = $this->create_league_fixture( 555 );
        $league  = $this->create_league_fixture_structure( type: 'XT', num_rubbers: 6 );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $fixture );

        $this->assert_created_rubbers( $fixture, $league, [ 'MS', 'MS', 'FS', 'FS', 'XD', 'XD' ] );
    }

    public function test_create_fixture_creates_associated_rubbers_for_mens_team_event(): void {
        $fixture = $this->create_league_fixture( 557 );
        $league  = $this->create_league_fixture_structure( type: 'MT', num_rubbers: 6 );

        $this->assert_created_rubbers( $fixture, $league, [ 'MS', 'MS', 'MS', 'MS', 'MD', 'MD' ] );
    }

    public function test_create_fixture_creates_associated_rubbers_for_womens_team_event(): void {
        $fixture = $this->create_league_fixture( 558 );
        $league  = $this->create_league_fixture_structure( type: 'WT', num_rubbers: 6 );

        $this->assert_created_rubbers( $fixture, $league, [ 'WS', 'WS', 'WS', 'WS', 'WD', 'WD' ] );
    }

    public function test_create_fixture_creates_associated_rubbers_for_open_team_event(): void {
        $fixture = $this->create_league_fixture( 559 );
        $league  = $this->create_league_fixture_structure( type: 'OT', num_rubbers: 6 );

        $this->assert_created_rubbers( $fixture, $league, [ 'OS', 'OS', 'OS', 'OS', 'OD', 'OD' ] );
    }

    public function test_create_fixture_creates_associated_rubbers_for_team_event_non_multiples_of_3(): void {
        // Test XT with 4 rubbers: 2 singles (1 MS, 1 FS) and 2 doubles (XD, XD)
        $fixture = $this->create_league_fixture( 560 );
        $league  = $this->create_league_fixture_structure( type: 'XT', num_rubbers: 4 );
        $this->assert_created_rubbers( $fixture, $league, [ 'MS', 'FS', 'XD', 'XD' ] );

        // Test XT with 5 rubbers: 3 singles (1 MS, 2 FS) and 2 doubles (XD, XD)
        $fixture5 = $this->create_league_fixture( 561 );
        $league5  = $this->create_league_fixture_structure( type: 'XT', num_rubbers: 5 );
        $this->assert_created_rubbers( $fixture5, $league5, [ 'MS', 'FS', 'FS', 'XD', 'XD' ] );

        // Test OT with 5 rubbers: 3 singles (OS, OS, OS) and 2 doubles (OD, OD)
        $fixture_ot = $this->create_league_fixture( 562 );
        $league_ot  = $this->create_league_fixture_structure( type: 'OT', num_rubbers: 5 );
        $this->assert_created_rubbers( $fixture_ot, $league_ot, [ 'OS', 'OS', 'OS', 'OD', 'OD' ] );
    }

    public function test_create_fixture_increments_rubbers_for_championship_final_even_rubbers(): void {
        // MD Championship final with 6 rubbers -> 7 rubbers
        $fixture = $this->create_league_fixture( 556, final: 'final' );
        $league  = $this->create_league_fixture_structure( type: 'MD', num_rubbers: 6, is_championship: true );
        $this->assert_created_rubbers( $fixture, $league, [ 'MD', 'MD', 'MD', 'MD', 'MD', 'MD', 'MD' ] );

        // XT Championship final with 6 rubbers -> 7 rubbers (4 singles, 3 doubles where 7th is XD)
        $fixture_xt = $this->create_league_fixture( 563, final: 'Final' );
        $league_xt  = $this->create_league_fixture_structure( type: 'XT', num_rubbers: 6, is_championship: true );
        $this->assert_created_rubbers( $fixture_xt, $league_xt, [ 'MS', 'MS', 'FS', 'FS', 'XD', 'XD', 'XD' ] );

        // OT Championship final with 6 rubbers -> 7 rubbers (4 OS, 3 OD)
        $fixture_ot = $this->create_league_fixture( 564, final: 'final' );
        $league_ot  = $this->create_league_fixture_structure( type: 'OT', num_rubbers: 6, is_championship: true );
        $this->assert_created_rubbers( $fixture_ot, $league_ot, [ 'OS', 'OS', 'OS', 'OS', 'OD', 'OD', 'OD' ] );
    }

    public function test_create_fixture_does_not_increment_rubbers_for_non_final_or_odd_rubbers(): void {
        // Championship semi-final with 6 rubbers should NOT increment
        $fixture_semi = $this->create_league_fixture( 565, final: 'semi' );
        $league       = $this->create_league_fixture_structure( type: 'MD', num_rubbers: 6, is_championship: true );
        $this->assert_created_rubbers( $fixture_semi, $league, [ 'MD', 'MD', 'MD', 'MD', 'MD', 'MD' ] );

        // Non-championship fixture marked final should NOT increment
        $fixture_non_champ = $this->create_league_fixture( 566, final: 'final' );
        $league_non_champ  = $this->create_league_fixture_structure( type: 'MD', num_rubbers: 6, is_championship: false );
        $this->assert_created_rubbers( $fixture_non_champ, $league_non_champ, [ 'MD', 'MD', 'MD', 'MD', 'MD', 'MD' ] );

        // Championship final with odd (5) rubbers should NOT increment
        $fixture_odd = $this->create_league_fixture( 567, final: 'final' );
        $league_odd  = $this->create_league_fixture_structure( type: 'MD', num_rubbers: 5, is_championship: true );
        $this->assert_created_rubbers( $fixture_odd, $league_odd, [ 'MD', 'MD', 'MD', 'MD', 'MD' ] );
    }

    public function test_create_fixture_creates_associated_rubbers_for_ladies_doubles_league(): void {
        $fixture = $this->create_league_fixture( 568 );
        $league  = $this->create_league_fixture_structure( type: 'LD', num_rubbers: 3 );
        $this->assert_created_rubbers( $fixture, $league, [ 'WD', 'MD', 'XD' ] );
    }

    public function test_add_match_hydrates_and_creates_fixture(): void {
        $match_data            = new stdClass();
        $match_data->id        = 777;
        $match_data->league_id = 12;
        $match_data->home_team = '10';
        $match_data->away_team = '20';
        $match_data->date      = '2026-07-01 18:30:00';

        $league = $this->create_league_fixture_structure( type: 'MD', num_rubbers: 0 );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->isInstanceOf( Fixture::class ) );

        $result = $this->service->add_match( $match_data, $league );

        $this->assertInstanceOf( Fixture::class, $result );
        $this->assertSame( 777, $result->get_id() );
        $this->assertSame( '10', $result->get_home_team() );
        $this->assertSame( '20', $result->get_away_team() );
        $this->assertSame( '2026-07-01 18:30:00', $result->get_date() );
    }

    public function test_update_match_synchronizes_linked_fixture(): void {
        $primary_raw               = new stdClass();
        $primary_raw->id           = 10;
        $primary_raw->home_team    = '100';
        $primary_raw->away_team    = '200';
        $primary_raw->date         = '2026-06-01 19:00:00';
        $primary_raw->host         = 'home';
        $primary_raw->linked_match = 20;
        $primary_fixture           = \Racketmanager\Domain\Fixture\Fixture_Factory::from_object( $primary_raw );

        $linked_raw         = new stdClass();
        $linked_raw->id     = 20;
        $linked_raw->host   = 'home';
        $linked_fixture     = \Racketmanager\Domain\Fixture\Fixture_Factory::from_object( $linked_raw );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 20 )
            ->willReturn( $linked_fixture );

        $this->fixture_repository->expects( $this->exactly( 2 ) )
            ->method( 'save' );

        $this->service->update_match( $primary_fixture );

        $this->assertSame( '100', $linked_fixture->get_home_team() );
        $this->assertSame( '200', $linked_fixture->get_away_team() );
        $this->assertSame( 'away', $linked_fixture->get_host() );
        $this->assertSame( '2026-06-15 19:00:00', $linked_fixture->get_date() );
        $this->assertSame( '19:00', $linked_fixture->get_start_time() );
    }

    public function test_create_fixture_championship_legs_preserves_start_time(): void {
        $fixture = \Racketmanager\Domain\Fixture\Fixture_Factory::from_object( (object) [
            'id'     => 570,
            'date'   => '2026-05-15 19:30:00',
            'season' => '2026',
            'host'   => 'home',
        ] );

        $league = $this->create_league_fixture_structure( type: 'MD', num_rubbers: 6, is_championship: true, home_away: true );
        $league->event->current_season = [ 'home_away' => true ];
        $league->event->competition = new class {
            public function get_season_by_name( string $season ): array {
                return [ 'home_away_diff' => 2 ];
            }
        };

        $saved_fixtures = [];
        $this->fixture_repository->method( 'save' )->willReturnCallback( function ( Fixture $f ) use ( &$saved_fixtures ) {
            if ( ! $f->get_id() ) {
                $f->set_id( 571 );
            }
            $saved_fixtures[] = clone $f;
            return $f->get_id();
        } );

        $this->service->create_fixture( $fixture, $league );

        $this->assertSame( 1, $fixture->get_leg() );
        $this->assertSame( '2026-05-15 19:30:00', $fixture->get_date() );
        $this->assertSame( '19:30', $fixture->get_start_time() );
        $this->assertSame( 571, $fixture->get_linked_fixture() );

        $leg2 = null;
        foreach ( $saved_fixtures as $sf ) {
            if ( 2 === $sf->get_leg() ) {
                $leg2 = $sf;
                break;
            }
        }

        $this->assertNotNull( $leg2 );
        $this->assertSame( '2026-05-29 19:30:00', $leg2->get_date() );
        $this->assertSame( '19:30', $leg2->get_start_time() );
        $this->assertSame( 'away', $leg2->get_host() );
    }

    public function test_update_match_flips_linked_host_from_away_to_home(): void {
        $primary_raw               = new stdClass();
        $primary_raw->id           = 12;
        $primary_raw->home_team    = '100';
        $primary_raw->away_team    = '200';
        $primary_raw->date         = '2026-06-01 19:00:00';
        $primary_raw->host         = 'away';
        $primary_raw->linked_match = 21;
        $primary_fixture           = \Racketmanager\Domain\Fixture\Fixture_Factory::from_object( $primary_raw );

        $linked_raw       = new stdClass();
        $linked_raw->id   = 21;
        $linked_raw->host = 'away';
        $linked_fixture   = \Racketmanager\Domain\Fixture\Fixture_Factory::from_object( $linked_raw );

        $this->fixture_repository->expects( $this->once() )->method( 'find_by_id' )->with( 21 )->willReturn( $linked_fixture );
        $this->fixture_repository->expects( $this->exactly( 2 ) )->method( 'save' );

        $this->service->update_match( $primary_fixture );

        $this->assertSame( 'home', $linked_fixture->get_host() );
    }

    public function test_update_match_synchronizes_linked_fixture_for_std_class_match(): void {
        $match               = new stdClass();
        $match->home_team    = '300';
        $match->away_team    = '400';
        $match->date         = '2026-09-01 10:00:00';
        $match->host         = 'home';
        $match->linked_match = '30';

        $linked_raw       = new stdClass();
        $linked_raw->id   = 30;
        $linked_raw->host = 'home';
        $linked_fixture   = \Racketmanager\Domain\Fixture\Fixture_Factory::from_object( $linked_raw );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 30 )
            ->willReturn( $linked_fixture );
        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $linked_fixture );

        $this->service->update_match( $match );

        $this->assertSame( '300', $linked_fixture->get_home_team() );
        $this->assertSame( '400', $linked_fixture->get_away_team() );
        $this->assertSame( 'away', $linked_fixture->get_host() );
        $this->assertSame( '2026-09-15 10:00:00', $linked_fixture->get_date() );
    }

    public function test_update_match_without_linked_fixture_saves_only_primary(): void {
        $primary_raw            = new stdClass();
        $primary_raw->id        = 11;
        $primary_raw->home_team = '100';
        $primary_raw->away_team = '200';
        $primary_raw->date      = '2026-06-01 19:00:00';
        $primary_fixture        = \Racketmanager\Domain\Fixture\Fixture_Factory::from_object( $primary_raw );

        $this->fixture_repository->expects( $this->never() )
            ->method( 'find_by_id' );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $primary_fixture );

        $this->service->update_match( $primary_fixture );
    }

    public function test_update_match_calls_update_method_if_available(): void {
        $updated = false;
        $custom_match = new class( $updated ) {
            public function __construct( public bool &$updated ) {}
            public function update(): void {
                $this->updated = true;
            }
        };

        $this->service->update_match( $custom_match );
        $this->assertTrue( $updated );
    }
}
