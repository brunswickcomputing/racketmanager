<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Rest\Rest_Resources;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

require_once __DIR__ . '/../../wp-stubs.php';

class Rest_Resources_Test extends TestCase {
    private $plugin;
    private $competition_service;
    private $club_service;
    private $season_service;
    private $finance_service;
    private $adapter;
    private $rest_resources;

    protected function setUp(): void {
        parent::setUp();

        $this->plugin              = $this->createStub( RacketManager::class );
        $this->competition_service = $this->createStub( Competition_Service::class );
        $this->club_service        = $this->createStub( Club_Service::class );
        $this->season_service      = $this->createStub( Season_Service::class );
        $this->finance_service     = $this->createStub( Finance_Service::class );
        $this->adapter             = new Rest_Controller_Adapter();

        $container = new Simple_Container();
        $container->set( 'competition_service', fn() => $this->competition_service );
        $container->set( 'club_service', fn() => $this->club_service );
        $container->set( 'season_service', fn() => $this->season_service );
        $container->set( 'player_service', fn() => $this->createStub( Player_Service::class ) );
        $container->set( 'registration_service', fn() => $this->createStub( Registration_Service::class ) );
        $container->set( 'finance_service', fn() => $this->finance_service );
        $container->set( 'rest_controller_adapter', fn() => $this->adapter );
        $container->set( 'league_query_service', new League_Query_Service(
            $this->createStub( League_Repository_Interface::class ),
            $this->createStub( Fixture_Repository_Interface::class ),
            $this->createStub( Rubber_Repository_Interface::class )
        ) );

        $this->plugin->container = $container;

        global $racketmanager;
        $racketmanager = $this->plugin;

        $this->rest_resources = new Rest_Resources(
            $this->plugin,
            $this->competition_service,
            $this->club_service,
            $this->season_service,
            $this->adapter
        );
    }

    private function bind_league_query_service( League_Repository_Interface $league_repository, Fixture_Repository_Interface $fixture_repository ): void {
        $this->plugin->container->set( 'league_query_service', new League_Query_Service(
            $league_repository,
            $fixture_repository,
            $this->createStub( Rubber_Repository_Interface::class )
        ) );
    }

    private function configure_league_match_fixtures( array $fixtures, ?League_Repository_Interface $league_repository = null ): League_Repository_Interface {
        $league_repository ??= $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'query_matches' )->willReturn(
            array_map( static fn( Fixture $fixture ): object => (object) [ 'id' => $fixture->get_id() ], $fixtures )
        );

        $fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repository->method( 'find_by_id' )->willReturnCallback(
            static function ( int $id ) use ( $fixtures ): ?Fixture {
                foreach ( $fixtures as $fixture ) {
                    if ( $fixture->get_id() === $id ) {
                        return $fixture;
                    }
                }
                return null;
            }
        );

        $this->bind_league_query_service( $league_repository, $fixture_repository );

        return $league_repository;
    }

    public function test_register_routes_registers_all_expected_endpoints(): void {
        $GLOBALS['wp_stubs_registered_rest_routes'] = array();

        $this->rest_resources->register_routes();

        $routes = $GLOBALS['wp_stubs_registered_rest_routes']['racketmanager/v1'] ?? array();
        self::assertArrayHasKey( '/stripe', $routes );
        self::assertArrayHasKey( '/standings', $routes );
        self::assertArrayHasKey( '/fixtures', $routes );
        self::assertArrayHasKey( '/results', $routes );
        self::assertArrayHasKey( '/results/schema', $routes );
    }

    public function test_get_item_permissions_check_allows_public_requests(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        self::assertTrue( $this->rest_resources->get_item_permissions_check( $request ) );
    }

    public function test_get_matches_uses_competition_service(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        $request->set_param( 'competition', 'test-competition' );
        $request->set_param( 'season', '2024' );

        $competition = $this->createStub( Competition::class );
        $competition->id = 123;
        $competition->method( 'get_seasons' )->willReturn( array( '2024' => array( 'id' => 1 ) ) );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::atLeastOnce() )
            ->method( 'get_competition' )
            ->with( 'test competition', 'name' )
            ->willReturn( $competition );

        $this->plugin->container->set( 'competition_service', fn() => $competition_service );
        $this->plugin->method( 'get_matches' )->willReturn( array() );

        $rest_resources = new Rest_Resources(
            $this->plugin,
            $competition_service,
            $this->club_service,
            $this->season_service,
            $this->adapter
        );

        $response = $rest_resources->get_matches( $request );
        if ( $response instanceof WP_Error ) {
            $this->fail( 'REST request failed with WP_Error: ' . $response->get_error_message() );
        }
        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );
    }

    public function test_get_matches_with_league_and_days_parameter(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/results' );
        $request->set_param( 'league', 'division-1' );
        $request->set_param( 'days', '10' );

        $league = new League( (object) array( 'id' => 3, 'title' => 'Division 1' ) );
        $match = new Fixture();
        $match->id = 31;
        $match->league_id = 3;
        $match->league = (object) array( 'title' => 'Division 1' );
        $match->teams = array(
            'home' => (object) array( 'title' => 'Team A' ),
            'away' => (object) array( 'title' => 'Team B' ),
        );
        $match->date = '2026-05-10 14:00:00';
        $match->start_time = '14:00';
        $match->winner_id = 1;
        $match->status = null;

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with( 3, self::callback( function( array $args ) {
                return ( $args['time'] ?? null ) === 'latest'
                    && ( $args['days'] ?? null ) === 10;
            } ), false )
            ->willReturn( array( (object) [ 'id' => $match->get_id() ] ) );

        $fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repository->method( 'find_by_id' )->willReturn( $match );
        $this->bind_league_query_service( $league_repository, $fixture_repository );

        $GLOBALS['wp_stubs_leagues']['division 1'] = $league;

        $response = $this->rest_resources->get_matches( $request );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );
        $data = $response->get_data();
        self::assertCount( 1, $data );
        self::assertSame( 'Division 1', $data[0]->league );

        unset( $GLOBALS['wp_stubs_leagues']['division 1'] );
    }

    public function test_get_matches_with_missing_grouping_returns_wp_error(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        $request->set_param( 'season', '2024' );

        $response = $this->rest_resources->get_matches( $request );
        self::assertInstanceOf( WP_Error::class, $response );
        self::assertSame( 'rest_invalid_param', $response->get_error_code() );
        self::assertSame( 400, $response->get_error_data()['status'] );
    }

    public function test_get_matches_with_invalid_club_returns_wp_error(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        $request->set_param( 'club', 'unknown-club' );

        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( self::once() )
            ->method( 'get_club_by_shortcode' )
            ->with( 'unknown club' )
            ->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );

        $rest_resources = new Rest_Resources(
            $this->plugin,
            $this->competition_service,
            $club_service,
            $this->season_service,
            $this->adapter
        );

        $response = $rest_resources->get_matches( $request );
        self::assertInstanceOf( WP_Error::class, $response );
        self::assertSame( 'rest_invalid_param', $response->get_error_code() );
        self::assertSame( 400, $response->get_error_data()['status'] );
    }

    public function test_get_matches_with_real_fixture_returns_populated_fields_and_score(): void {
        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Home Tennis Club' );

        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Away Tennis Club' );

        $team_repo = $this->createStub( Team_Repository_Interface::class );
        $team_repo->method( 'find_by_id' )->willReturnCallback(
            fn( int $id ) => 5 === $id ? $home_team : ( 6 === $id ? $away_team : null )
        );

        $league = new League( (object) array( 'id' => 3, 'title' => 'Premier "Division"' ) );
        $league->seasons = [ '2026' ];
        $league_repo = $this->createStub( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );
        $this->competition_service->method( 'get_league_repository' )->willReturn( $league_repo );

        $container = $this->plugin->container;
        $container->set( 'team_repository', fn() => $team_repo );

        $fixture              = new Fixture();
        $fixture->id          = 42;
        $fixture->league_id   = 3;
        $fixture->home_team   = '5';
        $fixture->away_team   = '6';
        $fixture->winner_id   = 5;
        $fixture->home_points = '6';
        $fixture->away_points = '3';
        $fixture->status      = 0;
        $fixture->date        = '2026-05-01 19:30:00';
        $fixture->start_time  = '19:30';

        $this->configure_league_match_fixtures( [ $fixture ], $league_repo );
        $GLOBALS['wp_stubs_leagues']['premier division'] = $league;

        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        $request->set_param( 'league', 'premier-division' );
        $request->set_param( 'season', '2026' );

        $response = $this->rest_resources->get_matches( $request );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );

        $data = $response->get_data();
        self::assertIsArray( $data );
        self::assertCount( 1, $data );

        $item = $data[0];
        self::assertSame( 'Premier Division', $item->league );
        self::assertSame( 'Home Tennis Club', $item->home_team );
        self::assertSame( 'Away Tennis Club', $item->away_team );
        self::assertSame( '2026-05-01', $item->match_date );
        self::assertSame( '19:30', $item->match_time );
        self::assertSame( '6 - 3', $item->score );
        self::assertSame( 'Complete', $item->status );

        unset( $GLOBALS['wp_stubs_leagues']['premier division'] );
    }

    public function test_get_matches_with_missing_team_or_club_continues_and_populates_other_rows(): void {
        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Active Team 5' );

        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Active Team 6' );

        $empty_title_team = $this->createStub( Team::class );
        $empty_title_team->method( 'get_name' )->willReturn( '' );
        $empty_title_team->title = '';

        $team_repo = $this->createStub( Team_Repository_Interface::class );
        $team_repo->method( 'find_by_id' )->willReturnCallback( function( int $id ) use ( $home_team, $away_team, $empty_title_team ) {
            if ( 5 === $id ) {
                return $home_team;
            }
            if ( 6 === $id ) {
                return $away_team;
            }
            if ( 777 === $id ) {
                return $empty_title_team;
            }
            if ( 999 === $id ) {
                return null;
            }
            if ( 888 === $id ) {
                throw new Team_Not_Found_Exception( 'Missing team 888' );
            }
            return null;
        } );

        $league = new League( (object) array( 'id' => 3, 'title' => 'Division 1' ) );
        $league_repo = $this->createStub( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );
        $this->competition_service->method( 'get_league_repository' )->willReturn( $league_repo );

        $container = $this->plugin->container;
        $container->set( 'team_repository', fn() => $team_repo );

        $fixture1            = new Fixture();
        $fixture1->id        = 1;
        $fixture1->league_id = 3;
        $fixture1->home_team = '5';
        $fixture1->away_team = '6';

        $fixture2            = new Fixture();
        $fixture2->id        = 2;
        $fixture2->league_id = 3;
        $fixture2->home_team = '999';
        $fixture2->away_team = '6';

        $fixture3            = new Fixture();
        $fixture3->id        = 3;
        $fixture3->league_id = 3;
        $fixture3->home_team = '888';
        $fixture3->away_team = '5';

        $fixture4            = new Fixture();
        $fixture4->id        = 4;
        $fixture4->league_id = 3;
        $fixture4->home_team = '777';
        $fixture4->away_team = 'Winner of Semi 1';

        $this->configure_league_match_fixtures( [ $fixture1, $fixture2, $fixture3, $fixture4 ], $league_repo );
        $GLOBALS['wp_stubs_leagues']['division 1'] = $league;
        $league->seasons = [ '2026' ];

        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        $request->set_param( 'league', 'division-1' );
        $request->set_param( 'season', '2026' );

        $response = $this->rest_resources->get_matches( $request );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );
        $data = $response->get_data();
        self::assertCount( 4, $data );

        self::assertSame( 'Active Team 5', $data[0]->home_team );
        self::assertSame( 'Active Team 6', $data[0]->away_team );
        self::assertSame( '', $data[1]->home_team );
        self::assertSame( 'Active Team 6', $data[1]->away_team );
        self::assertSame( '', $data[2]->home_team );
        self::assertSame( 'Active Team 5', $data[2]->away_team );
        self::assertSame( '', $data[3]->home_team );
        self::assertSame( 'Winner of Semi 1', $data[3]->away_team );

        unset( $GLOBALS['wp_stubs_leagues']['division 1'] );
    }

    public function test_get_matches_memoizes_league_and_team_queries_across_collection(): void {
        $teams_data = array(
            10 => 'Park Tennis Club',
            20 => 'Grove Tennis Club',
            30 => 'Oak Tennis Club',
        );

        $team_lookup_count = array();
        $team_repo = $this->createStub( Team_Repository_Interface::class );
        $team_repo->method( 'find_by_id' )->willReturnCallback( function( int $id ) use ( &$teams_data, &$team_lookup_count ) {
            $team_lookup_count[ $id ] = ( $team_lookup_count[ $id ] ?? 0 ) + 1;
            if ( isset( $teams_data[ $id ] ) ) {
                $team = $this->createStub( Team::class );
                $team->method( 'get_name' )->willReturn( $teams_data[ $id ] );
                return $team;
            }
            return null;
        } );

        $league_lookup_count = 0;
        $league = new League( (object) array( 'id' => 100, 'title' => 'Memoized League' ) );
        $league->seasons = [ '2026' ];
        $league_repo = $this->createStub( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturnCallback( function( int $id ) use ( $league, &$league_lookup_count ) {
            $league_lookup_count++;
            return $league;
        } );
        $this->competition_service->method( 'get_league_repository' )->willReturn( $league_repo );

        $container = $this->plugin->container;
        $container->set( 'team_repository', fn() => $team_repo );

        $fixtures = array();
        for ( $i = 1; $i <= 5; $i++ ) {
            $f = new Fixture();
            $f->id = $i;
            $f->league_id = 100;
            $f->home_team = '10';
            $f->away_team = '20';
            $fixtures[] = $f;
        }

        $matches_to_return = $fixtures;
        $league_repo->method( 'query_matches' )->willReturnCallback(
            static function () use ( &$matches_to_return ): array {
                return array_map( static fn( Fixture $fixture ): object => (object) [ 'id' => $fixture->get_id() ], $matches_to_return );
            }
        );
        $fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repository->method( 'find_by_id' )->willReturnCallback(
            static function ( int $id ) use ( &$matches_to_return ): ?Fixture {
                foreach ( $matches_to_return as $fixture ) {
                    if ( $fixture->get_id() === $id ) {
                        return $fixture;
                    }
                }
                return null;
            }
        );
        $this->bind_league_query_service( $league_repo, $fixture_repository );
        $GLOBALS['wp_stubs_leagues']['memoized league'] = $league;

        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        $request->set_param( 'league', 'memoized-league' );
        $request->set_param( 'season', '2026' );

        $response = $this->rest_resources->get_matches( $request );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );
        self::assertCount( 5, $response->get_data() );

        // Assert memoization: 1 lookup for league 100, 1 for team 10, 1 for team 20 across 5 fixtures
        self::assertSame( 1, $league_lookup_count );
        self::assertSame( 1, $team_lookup_count[10] ?? 0 );
        self::assertSame( 1, $team_lookup_count[20] ?? 0 );

        // Second call: update team name and set fixture with team 30 to verify per-call cache reset
        $teams_data[10]    = 'Park Tennis Club Renamed';
        $f_new             = new Fixture();
        $f_new->id         = 6;
        $f_new->league_id  = 100;
        $f_new->home_team  = '10';
        $f_new->away_team  = '30';
        $matches_to_return = array( $f_new );

        $response2 = $this->rest_resources->get_matches( $request );
        self::assertSame( 200, $response2->get_status() );
        $data2 = $response2->get_data();
        self::assertCount( 1, $data2 );
        self::assertSame( 'Park Tennis Club Renamed', $data2[0]->home_team );
        self::assertSame( 'Oak Tennis Club', $data2[0]->away_team );

        // Verify lookups were triggered freshly for the second get_matches call
        self::assertSame( 2, $team_lookup_count[10] );
        self::assertSame( 1, $team_lookup_count[30] );

        unset( $GLOBALS['wp_stubs_leagues']['memoized league'] );
    }

    public function test_get_standings_with_league(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/standings' );
        $request->set_param( 'league', 'premier-league' );
        $request->set_param( 'season', '2026' );

        $event = $this->createStub( Event::class );
        $event->id = 50;

        $league = new League( (object) [ 'id' => 10, 'title' => 'Premier League', 'seasons' => [ '2026' => [ 'name' => '2026' ] ] ] );
        $league->current_season = [ 'name' => '2026' ];
        $league->event = $event;

        $team_item = new \stdClass();
        $team_item->table_id = 1;
        $team_item->id = 1;
        $team_item->title = 'First Team';
        $team_item->season = '2026';
        $team_item->rank = 1;
        $team_item->status = 'active';
        $team_item->done_matches = 4;
        $team_item->won_matches = 4;
        $team_item->draw_matches = 0;
        $team_item->lost_matches = 0;
        $team_item->points_plus = 12;
        $team_item->points_minus = 0;
        $league_team = new League_Team( $team_item );

        $league_repo = $this->createMock( League_Repository_Interface::class );
        $league_repo->expects( self::once() )
            ->method( 'query_league_teams' )
            ->with( 10, self::callback( fn( $args ) => '2026' === ( $args['season'] ?? null ) && 0 === ( $args['club'] ?? null ) ), false )
            ->willReturn( array( $team_item ) );
        $league_team_repo = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface::class );
        $league_team_repo->expects( self::once() )->method( 'find_by_id' )->with( 1 )->willReturn( $league_team );
        $this->plugin->container->set( 'league_repository', fn() => $league_repo );
        $this->plugin->container->set( 'league_team_repository', fn() => $league_team_repo );
        $this->plugin->container->set( 'league_query_service', fn() => new \Racketmanager\Services\Competition\League_Query_Service( $league_repo, league_team_repository: $league_team_repo ) );

        $GLOBALS['wp_stubs_leagues']['premier league'] = $league;
        $GLOBALS['wp_stubs_leagues'][10] = $league;
        $GLOBALS['wp_stubs_events'][50] = $event;

        $response = $this->rest_resources->get_standings( $request );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );
        $data = $response->get_data();
        self::assertCount( 1, $data );
        self::assertSame( 'Premier League', $data[0]->league );
        self::assertSame( 'First Team', $data[0]->team );
        self::assertSame( 12.0, $data[0]->points );

        unset( $GLOBALS['wp_stubs_leagues']['premier league'], $GLOBALS['wp_stubs_leagues'][10], $GLOBALS['wp_stubs_events'][50] );
    }

    public function test_get_standings_with_competition_and_season_casts_season_to_int(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/standings' );
        $request->set_param( 'competition', 'test-competition' );
        $request->set_param( 'season', '2024' );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 'test competition', 2024 )
            ->willReturn( array() );

        $rest_resources = new Rest_Resources(
            $this->plugin,
            $competition_service,
            $this->club_service,
            $this->season_service,
            $this->adapter
        );

        $response = $rest_resources->get_standings( $request );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );
    }

    public function test_get_standings_with_invalid_club_returns_wp_error(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/standings' );
        $request->set_param( 'club', 'non-existent' );

        $club_service = $this->createMock( Club_Service::class );
        $club_service->expects( self::once() )
            ->method( 'get_club_by_shortcode' )
            ->with( 'non existent' )
            ->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );

        $rest_resources = new Rest_Resources(
            $this->plugin,
            $this->competition_service,
            $club_service,
            $this->season_service,
            $this->adapter
        );

        $response = $rest_resources->get_standings( $request );
        self::assertInstanceOf( WP_Error::class, $response );
        self::assertSame( 'rest_invalid_param', $response->get_error_code() );
        self::assertSame( 400, $response->get_error_data()['status'] );
    }

    public function test_get_standings_missing_grouping_returns_wp_error(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/standings' );
        $request->set_param( 'season', '2026' );

        $response = $this->rest_resources->get_standings( $request );
        self::assertInstanceOf( WP_Error::class, $response );
        self::assertSame( 'rest_invalid_param', $response->get_error_code() );
        self::assertSame( 400, $response->get_error_data()['status'] );
    }

    public function test_get_public_item_schema_returns_valid_match_schema(): void {
        $schema = $this->rest_resources->get_public_item_schema();
        self::assertIsArray( $schema );
        self::assertSame( 'match', $schema['title'] ?? null );
        self::assertArrayHasKey( 'properties', $schema );
    }

    public function test_get_collection_params_returns_expected_structure(): void {
        $params = $this->rest_resources->get_collection_params();
        self::assertArrayHasKey( 'page', $params );
        self::assertArrayHasKey( 'per_page', $params );
        self::assertArrayHasKey( 'search', $params );
    }

    public function test_stripe_event_without_payload_returns_400_response(): void {
        $request = new WP_REST_Request( 'POST', '/racketmanager/v1/stripe' );
        $request->set_body( '' );

        ob_start();
        $response = $this->rest_resources->stripe_event( $request );
        ob_end_clean();

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 400, $response->get_status() );
    }

    private function stripe_payload( string $type = 'payment_intent.succeeded' ): string {
        return (string) json_encode( array(
            'id'   => 'evt_test_1',
            'type' => $type,
            'data' => array(
                'object' => array(
                    'id'     => 'pi_test_123',
                    'object' => 'payment_intent',
                ),
            ),
        ) );
    }

    public function test_stripe_event_with_payment_intent_succeeded_calls_update_payment(): void {
        $finance_service = $this->createMock( Finance_Service::class );
        $container = new Simple_Container();
        $container->set( 'competition_service', fn() => $this->competition_service );
        $container->set( 'club_service', fn() => $this->club_service );
        $container->set( 'season_service', fn() => $this->season_service );
        $container->set( 'finance_service', fn() => $finance_service );
        $container->set( 'rest_controller_adapter', fn() => $this->adapter );

        $plugin = $this->createStub( RacketManager::class );
        $plugin->container = $container;
        $plugin->method( 'get_options' )->willReturn( array(
            'stripe_is_live'      => false,
            'api_secret_key_test' => 'sk_test_123',
        ) );
        $resources = new Rest_Resources( $plugin, $this->competition_service, $this->club_service, $this->season_service, $this->adapter );

        $invoice = new class() {
            public string $status = 'unpaid';
            public ?string $set_to = null;
            public function set_status( string $status ): void {
                $this->set_to = $status;
            }
        };
        $finance_service->expects( self::once() )
            ->method( 'get_invoices_by_criteria' )
            ->with( array( 'reference' => 'pi_test_123' ) )
            ->willReturn( array( $invoice ) );

        $request = new WP_REST_Request( 'POST', '/racketmanager/v1/stripe' );
        $request->set_body( $this->stripe_payload() );

        $response = $resources->stripe_event( $request );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );
        self::assertSame( 'paid', $invoice->set_to );
    }

    public function test_stripe_event_with_invalid_signature_prevents_update(): void {
        $finance_service = $this->createMock( Finance_Service::class );
        $container = new Simple_Container();
        $container->set( 'competition_service', fn() => $this->competition_service );
        $container->set( 'club_service', fn() => $this->club_service );
        $container->set( 'season_service', fn() => $this->season_service );
        $container->set( 'finance_service', fn() => $finance_service );
        $container->set( 'rest_controller_adapter', fn() => $this->adapter );

        $plugin = $this->createStub( RacketManager::class );
        $plugin->container = $container;
        $plugin->method( 'get_options' )->willReturn( array(
            'stripe_is_live'        => false,
            'api_secret_key_test'   => 'sk_test_123',
            'api_endpoint_key_test' => 'whsec_test',
        ) );
        $resources = new Rest_Resources( $plugin, $this->competition_service, $this->club_service, $this->season_service, $this->adapter );

        $finance_service->expects( self::never() )->method( 'get_invoices_by_criteria' );

        $request = new WP_REST_Request( 'POST', '/racketmanager/v1/stripe' );
        $request->set_body( $this->stripe_payload() );
        $request->set_header( 'stripe_signature', 't=1,v1=bad' );

        ob_start();
        $response = $resources->stripe_event( $request );
        $output   = (string) ob_get_clean();

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 400, $response->get_status() );
        self::assertStringContainsString( 'validating signature', $output );
    }

    public function test_stripe_event_with_unhandled_event_type_returns_400(): void {
        $request = new WP_REST_Request( 'POST', '/racketmanager/v1/stripe' );
        $payload = json_encode( array(
            'id'   => 'evt_test_unhandled',
            'type' => 'customer.created',
            'data' => array(
                'object' => array(
                    'id' => 'cus_123',
                ),
            ),
        ) );
        $request->set_body( (string) $payload );

        $response = $this->rest_resources->stripe_event( $request );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 400, $response->get_status() );
    }
}
