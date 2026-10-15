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

    public function test_create_fixture_creates_associated_rubbers_for_mixed_team_event(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 555 );
        $fixture->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture->method( 'get_final' )->willReturn( null );

        $league                   = new stdClass();
        $league->is_championship  = false;
        $league->num_rubbers      = 6;
        $league->type             = 'XT'; // Mixed Team
        $league->event            = new stdClass();
        $league->event->scoring   = 'standard';
        $league->event->reverse_rubbers = '0';
        $league->current_season   = [ 'home_away' => false ];

        $created_types = [];
        $this->mock_wpdb_rubber_captor( $created_types );

        $this->fixture_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $fixture );

        $this->service->create_fixture( $fixture, $league );

        $this->assertCount( 6, $created_types );
        $this->assertSame( [ 'MS', 'MS', 'FS', 'FS', 'XD', 'XD' ], $created_types );
    }

    public function test_create_fixture_creates_associated_rubbers_for_mens_team_event(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 557 );
        $fixture->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture->method( 'get_final' )->willReturn( null );

        $league                   = new stdClass();
        $league->is_championship  = false;
        $league->num_rubbers      = 6;
        $league->type             = 'MT'; // Men's Team
        $league->event            = new stdClass();
        $league->event->scoring   = 'standard';
        $league->event->reverse_rubbers = '0';
        $league->current_season   = [ 'home_away' => false ];

        $created_types = [];
        $this->mock_wpdb_rubber_captor( $created_types );

        $this->service->create_fixture( $fixture, $league );

        $this->assertCount( 6, $created_types );
        $this->assertSame( [ 'MS', 'MS', 'MS', 'MS', 'MD', 'MD' ], $created_types );
    }

    public function test_create_fixture_creates_associated_rubbers_for_womens_team_event(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 558 );
        $fixture->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture->method( 'get_final' )->willReturn( null );

        $league                   = new stdClass();
        $league->is_championship  = false;
        $league->num_rubbers      = 6;
        $league->type             = 'WT'; // Women's Team
        $league->event            = new stdClass();
        $league->event->scoring   = 'standard';
        $league->event->reverse_rubbers = '0';
        $league->current_season   = [ 'home_away' => false ];

        $created_types = [];
        $this->mock_wpdb_rubber_captor( $created_types );

        $this->service->create_fixture( $fixture, $league );

        $this->assertCount( 6, $created_types );
        $this->assertSame( [ 'WS', 'WS', 'WS', 'WS', 'WD', 'WD' ], $created_types );
    }

    public function test_create_fixture_creates_associated_rubbers_for_open_team_event(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 559 );
        $fixture->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture->method( 'get_final' )->willReturn( null );

        $league                   = new stdClass();
        $league->is_championship  = false;
        $league->num_rubbers      = 6;
        $league->type             = 'OT'; // Open Team
        $league->event            = new stdClass();
        $league->event->scoring   = 'standard';
        $league->event->reverse_rubbers = '0';
        $league->current_season   = [ 'home_away' => false ];

        $created_types = [];
        $this->mock_wpdb_rubber_captor( $created_types );

        $this->service->create_fixture( $fixture, $league );

        $this->assertCount( 6, $created_types );
        $this->assertSame( [ 'OS', 'OS', 'OS', 'OS', 'OD', 'OD' ], $created_types );
    }

    public function test_create_fixture_creates_associated_rubbers_for_team_event_non_multiples_of_3(): void {
        // Test XT with 4 rubbers: 2 singles (1 MS, 1 FS) and 2 doubles (XD, XD)
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 560 );
        $fixture->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );

        $league                   = new stdClass();
        $league->is_championship  = false;
        $league->num_rubbers      = 4;
        $league->type             = 'XT';
        $league->event            = new stdClass();
        $league->event->scoring   = 'standard';
        $league->event->reverse_rubbers = '0';
        $league->current_season   = [ 'home_away' => false ];

        $created_types = [];
        $this->mock_wpdb_rubber_captor( $created_types );

        $this->service->create_fixture( $fixture, $league );

        $this->assertCount( 4, $created_types );
        $this->assertSame( [ 'MS', 'FS', 'XD', 'XD' ], $created_types );

        // Test XT with 5 rubbers: 3 singles (1 MS, 2 FS) and 2 doubles (XD, XD)
        $fixture5 = $this->createMock( Fixture::class );
        $fixture5->method( 'get_id' )->willReturn( 561 );
        $fixture5->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );

        $league5                   = clone $league;
        $league5->num_rubbers      = 5;

        $created_types5 = [];
        $this->mock_wpdb_rubber_captor( $created_types5 );

        $this->service->create_fixture( $fixture5, $league5 );

        $this->assertCount( 5, $created_types5 );
        $this->assertSame( [ 'MS', 'FS', 'FS', 'XD', 'XD' ], $created_types5 );

        // Test OT with 5 rubbers: 3 singles (OS, OS, OS) and 2 doubles (OD, OD)
        $fixture_ot = $this->createMock( Fixture::class );
        $fixture_ot->method( 'get_id' )->willReturn( 562 );
        $fixture_ot->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );

        $league_ot                   = clone $league;
        $league_ot->num_rubbers      = 5;
        $league_ot->type             = 'OT';

        $created_types_ot = [];
        $this->mock_wpdb_rubber_captor( $created_types_ot );

        $this->service->create_fixture( $fixture_ot, $league_ot );

        $this->assertCount( 5, $created_types_ot );
        $this->assertSame( [ 'OS', 'OS', 'OS', 'OD', 'OD' ], $created_types_ot );
    }

    public function test_create_fixture_increments_rubbers_for_championship_final_even_rubbers(): void {
        // MD Championship final with 6 rubbers -> 7 rubbers
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 556 );
        $fixture->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture->method( 'get_final' )->willReturn( 'final' );

        $league                   = new stdClass();
        $league->is_championship  = true;
        $league->num_rubbers      = 6;
        $league->type             = 'MD';
        $league->event            = new stdClass();
        $league->event->scoring   = 'standard';
        $league->event->reverse_rubbers = '0';
        $league->current_season   = [ 'home_away' => false ];

        $created_types = [];
        $this->mock_wpdb_rubber_captor( $created_types );

        $this->service->create_fixture( $fixture, $league );

        $this->assertCount( 7, $created_types );
        $this->assertSame( [ 'MD', 'MD', 'MD', 'MD', 'MD', 'MD', 'MD' ], $created_types );

        // XT Championship final with 6 rubbers -> 7 rubbers (4 singles, 3 doubles where 7th is XD)
        $fixture_xt = $this->createMock( Fixture::class );
        $fixture_xt->method( 'get_id' )->willReturn( 563 );
        $fixture_xt->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture_xt->method( 'get_final' )->willReturn( 'Final' ); // Mixed case check

        $league_xt                   = clone $league;
        $league_xt->type             = 'XT';

        $created_types_xt = [];
        $this->mock_wpdb_rubber_captor( $created_types_xt );

        $this->service->create_fixture( $fixture_xt, $league_xt );

        $this->assertCount( 7, $created_types_xt );
        $this->assertSame( [ 'MS', 'MS', 'FS', 'FS', 'XD', 'XD', 'XD' ], $created_types_xt );

        // OT Championship final with 6 rubbers -> 7 rubbers (4 OS, 3 OD)
        $fixture_ot = $this->createMock( Fixture::class );
        $fixture_ot->method( 'get_id' )->willReturn( 564 );
        $fixture_ot->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture_ot->method( 'get_final' )->willReturn( 'final' );

        $league_ot                   = clone $league;
        $league_ot->type             = 'OT';

        $created_types_ot = [];
        $this->mock_wpdb_rubber_captor( $created_types_ot );

        $this->service->create_fixture( $fixture_ot, $league_ot );

        $this->assertCount( 7, $created_types_ot );
        $this->assertSame( [ 'OS', 'OS', 'OS', 'OS', 'OD', 'OD', 'OD' ], $created_types_ot );
    }

    public function test_create_fixture_does_not_increment_rubbers_for_non_final_or_odd_rubbers(): void {
        // Championship semi-final with 6 rubbers should NOT increment
        $fixture_semi = $this->createMock( Fixture::class );
        $fixture_semi->method( 'get_id' )->willReturn( 565 );
        $fixture_semi->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture_semi->method( 'get_final' )->willReturn( 'semi' );

        $league                   = new stdClass();
        $league->is_championship  = true;
        $league->num_rubbers      = 6;
        $league->type             = 'MD';
        $league->event            = new stdClass();
        $league->event->scoring   = 'standard';
        $league->event->reverse_rubbers = '0';
        $league->current_season   = [ 'home_away' => false ];

        $created_types = [];
        $this->mock_wpdb_rubber_captor( $created_types );

        $this->service->create_fixture( $fixture_semi, $league );

        $this->assertCount( 6, $created_types );

        // Non-championship fixture marked final should NOT increment
        $fixture_non_champ = $this->createMock( Fixture::class );
        $fixture_non_champ->method( 'get_id' )->willReturn( 566 );
        $fixture_non_champ->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture_non_champ->method( 'get_final' )->willReturn( 'final' );

        $league_non_champ                  = clone $league;
        $league_non_champ->is_championship = false;

        $created_types_nc = [];
        $this->mock_wpdb_rubber_captor( $created_types_nc );

        $this->service->create_fixture( $fixture_non_champ, $league_non_champ );

        $this->assertCount( 6, $created_types_nc );

        // Championship final with odd (5) rubbers should NOT increment
        $fixture_odd = $this->createMock( Fixture::class );
        $fixture_odd->method( 'get_id' )->willReturn( 567 );
        $fixture_odd->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture_odd->method( 'get_final' )->willReturn( 'final' );

        $league_odd                  = clone $league;
        $league_odd->num_rubbers     = 5;

        $created_types_odd = [];
        $this->mock_wpdb_rubber_captor( $created_types_odd );

        $this->service->create_fixture( $fixture_odd, $league_odd );

        $this->assertCount( 5, $created_types_odd );
    }

    public function test_create_fixture_creates_associated_rubbers_for_ladies_doubles_league(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 568 );
        $fixture->method( 'get_date' )->willReturn( '2026-06-01 19:00:00' );
        $fixture->method( 'get_final' )->willReturn( null );

        $league                   = new stdClass();
        $league->is_championship  = false;
        $league->num_rubbers      = 3;
        $league->type             = 'LD'; // Ladies Doubles special mapping (WD, MD, XD)
        $league->event            = new stdClass();
        $league->event->scoring   = 'standard';
        $league->event->reverse_rubbers = '0';
        $league->current_season   = [ 'home_away' => false ];

        $created_types = [];
        $this->mock_wpdb_rubber_captor( $created_types );

        $this->service->create_fixture( $fixture, $league );

        $this->assertCount( 3, $created_types );
        $this->assertSame( [ 'WD', 'MD', 'XD' ], $created_types );
    }
}
