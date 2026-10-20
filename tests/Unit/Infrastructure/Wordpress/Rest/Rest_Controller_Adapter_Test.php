<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Rest;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Export\Formatters\Export_Formatter_Interface;
use Racketmanager\Services\Team_Service;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Rest_Controller_Adapter_Test extends TestCase {

    private Rest_Controller_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->adapter = new Rest_Controller_Adapter();
    }

    public function test_prepare_criteria_from_request_populates_all_fields(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/results' );
        $request->set_param( 'league_id', '15' );
        $request->set_param( 'season', '2026' );
        $request->set_param( 'club_id', '3' );
        $request->set_param( 'competition_id', '8' );
        $request->set_param( 'team_id', '42' );
        $request->set_param( 'date_from', '2026-01-01' );
        $request->set_param( 'date_to', '2026-12-31' );
        $request->set_param( 'format', 'csv' );

        $criteria = $this->adapter->prepare_criteria_from_request( $request );

        self::assertSame( 15, $criteria->league_id );
        self::assertSame( '2026', $criteria->season );
        self::assertSame( 3, $criteria->club_id );
        self::assertSame( 8, $criteria->competition_id );
        self::assertSame( 42, $criteria->team_id );
        self::assertSame( '2026-01-01', $criteria->date_from );
        self::assertSame( '2026-12-31', $criteria->date_to );
        self::assertSame( 'csv', $criteria->format );
    }

    public function test_check_export_permissions_allows_public_types(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/calendar' );

        self::assertTrue( $this->adapter->check_export_permissions( $request, 'calendar' ) );
        self::assertTrue( $this->adapter->check_export_permissions( $request, 'fixtures' ) );
        self::assertTrue( $this->adapter->check_export_permissions( $request, 'results' ) );
        self::assertTrue( $this->adapter->check_export_permissions( $request, 'public' ) );
    }

    public function test_check_export_permissions_denies_report_results_without_capability(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/report-results' );

        $GLOBALS['wp_stubs_current_user_can'] = false;
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;

        $result = $this->adapter->check_export_permissions( $request, 'report_results' );

        self::assertInstanceOf( \WP_Error::class, $result );
        self::assertSame( 'rest_forbidden', $result->get_error_code() );
        self::assertSame( 401, $result->get_error_data()['status'] ?? null );

        // If user is logged in but lacks cap, return 403
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $result_403 = $this->adapter->check_export_permissions( $request, 'report_results' );
        self::assertInstanceOf( \WP_Error::class, $result_403 );
        self::assertSame( 403, $result_403->get_error_data()['status'] ?? null );

        unset( $GLOBALS['wp_stubs_current_user_can'], $GLOBALS['wp_stubs_is_user_logged_in'] );
    }

    public function test_check_export_permissions_allows_report_results_with_capability(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/report-results' );

        $GLOBALS['wp_stubs_current_user_can'] = true;

        $result = $this->adapter->check_export_permissions( $request, 'report_results' );

        self::assertTrue( $result );

        unset( $GLOBALS['wp_stubs_current_user_can'] );
    }

    public function test_check_export_permissions_denies_unknown_export_types(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/unknown' );

        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $result_401 = $this->adapter->check_export_permissions( $request, 'unknown_type' );
        self::assertInstanceOf( \WP_Error::class, $result_401 );
        self::assertSame( 'rest_forbidden', $result_401->get_error_code() );
        self::assertSame( 401, $result_401->get_error_data()['status'] ?? null );

        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $result_403 = $this->adapter->check_export_permissions( $request, 'unknown_type' );
        self::assertInstanceOf( \WP_Error::class, $result_403 );
        self::assertSame( 'rest_forbidden', $result_403->get_error_code() );
        self::assertSame( 403, $result_403->get_error_data()['status'] ?? null );

        unset( $GLOBALS['wp_stubs_is_user_logged_in'] );
    }

    public function test_prepare_export_response_sets_headers_and_status(): void {
        $content = 'Sample export content';
        $response = $this->adapter->prepare_export_response( $content, 'report.csv', Export_Formatter_Interface::CONTENT_TYPE_CSV );

        self::assertInstanceOf( WP_REST_Response::class, $response );
        self::assertSame( 200, $response->get_status() );
        self::assertSame( $content, $response->get_data() );

        $headers = $response->get_headers();
        self::assertSame( Export_Formatter_Interface::CONTENT_TYPE_CSV, $headers['Content-Type'] );
        self::assertSame( 'attachment; filename="report.csv"', $headers['Content-Disposition'] );
    }

    public function test_serve_raw_export_serves_csv_and_ics_streams(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/export/report-results' );
        $request->set_route( '/racketmanager/v1/export/report-results' );

        $response = new WP_REST_Response( 'Tournament,Winner', 200, array( 'Content-Type' => Export_Formatter_Interface::CONTENT_TYPE_CSV ) );

        $server = $this->createMock( WP_REST_Server::class );
        $server->expects( self::once() )->method( 'send_headers' );

        ob_start();
        $served = $this->adapter->serve_raw_export( false, $response, $request, $server );
        $output = (string) ob_get_clean();

        self::assertTrue( $served );
        self::assertSame( 'Tournament,Winner', $output );
    }

    public function test_serve_raw_export_ignores_non_export_routes(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/standings' );
        $request->set_route( '/racketmanager/v1/standings' );

        $response = new WP_REST_Response( array( 'data' => 1 ), 200 );
        $server = $this->createMock( WP_REST_Server::class );
        $server->expects( self::never() )->method( 'send_headers' );

        $served = $this->adapter->serve_raw_export( false, $response, $request, $server );

        self::assertFalse( $served );
    }

    public function test_prepare_standings_query_from_request(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/standings' );
        $request->set_param( 'club', 'westcliff' );
        $request->set_param( 'competition', 'summer-league' );
        $request->set_param( 'event', 'mens-doubles' );
        $request->set_param( 'league', 'division-1' );
        $request->set_param( 'season', '2026' );

        $query = $this->adapter->prepare_standings_query_from_request( $request );

        self::assertSame( 'westcliff', $query->club );
        self::assertSame( 'summer-league', $query->competition );
        self::assertSame( 'mens-doubles', $query->event );
        self::assertSame( 'division-1', $query->league );
        self::assertSame( '2026', $query->season );
    }

    public function test_prepare_matches_query_from_request(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/fixtures' );
        $request->set_param( 'club', 'leigh' );
        $request->set_param( 'competition', 'winter-cup' );
        $request->set_param( 'event', 'mixed-doubles' );
        $request->set_param( 'league', 'premier' );
        $request->set_param( 'season', '2025' );
        $request->set_param( 'home', 1 );
        $request->set_param( 'days', '14' );

        $query = $this->adapter->prepare_matches_query_from_request( $request );

        self::assertSame( 'leigh', $query->club );
        self::assertSame( 'winter-cup', $query->competition );
        self::assertSame( 'mixed-doubles', $query->event );
        self::assertSame( 'premier', $query->league );
        self::assertSame( '2025', $query->season );
        self::assertTrue( $query->home );
        self::assertSame( 14, $query->days );
    }

    public function test_prepare_stripe_webhook_command_from_request(): void {
        $request = new WP_REST_Request( 'POST', '/racketmanager/v1/stripe' );
        $request->set_body( '{"id":"evt_123"}' );
        $request->set_header( 'http_stripe_signature', 't=123,v1=sig_abc' );

        $command = $this->adapter->prepare_stripe_webhook_command_from_request( $request );

        self::assertSame( '{"id":"evt_123"}', $command->payload );
        self::assertSame( 't=123,v1=sig_abc', $command->signature_header );
    }

    public function test_check_resource_permissions(): void {
        $request = new WP_REST_Request( 'GET', '/racketmanager/v1/standings' );

        self::assertTrue( $this->adapter->check_resource_permissions( $request, 'standings' ) );
        self::assertTrue( $this->adapter->check_resource_permissions( $request, 'fixtures' ) );
        self::assertTrue( $this->adapter->check_resource_permissions( $request, 'results' ) );
        self::assertTrue( $this->adapter->check_resource_permissions( $request, 'schema' ) );
        self::assertTrue( $this->adapter->check_resource_permissions( $request, 'stripe' ) );
        self::assertTrue( $this->adapter->check_resource_permissions( $request, 'public' ) );

        $GLOBALS['wp_stubs_current_user_can'] = false;
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $unauth = $this->adapter->check_resource_permissions( $request, 'private_resource' );
        self::assertInstanceOf( \WP_Error::class, $unauth );
        self::assertSame( 401, $unauth->get_error_data()['status'] ?? null );

        unset( $GLOBALS['wp_stubs_current_user_can'], $GLOBALS['wp_stubs_is_user_logged_in'] );
    }

    public function test_prepare_match_for_response(): void {
        $match = new \stdClass();
        $match->league = (object) array( 'title' => 'Division "1"' );
        $match->teams = array(
            'home' => (object) array( 'title' => 'Club "A"' ),
            'away' => (object) array( 'title' => 'Club "B"' ),
        );
        $match->date = '2026-06-15 18:30:00';
        $match->start_time = '18:30';
        $match->winner_id = 1;
        $match->score = '6-4 6-2';
        $match->status = null;

        $formatted = $this->adapter->prepare_match_for_response( $match );

        self::assertSame( 'Division 1', $formatted->league );
        self::assertSame( 'Club A', $formatted->home_team );
        self::assertSame( 'Club B', $formatted->away_team );
        self::assertSame( '2026-06-15', $formatted->match_date );
        self::assertSame( '18:30', $formatted->match_time );
        self::assertSame( '6-4 6-2', $formatted->score );
        self::assertNull( $formatted->status );
    }

    public function test_prepare_match_for_response_with_status(): void {
        $match = new \stdClass();
        $match->league = (object) array( 'title' => 'Division "1"' );
        $match->teams = array(
            'home' => (object) array( 'title' => 'Club "A"' ),
            'away' => (object) array( 'title' => 'Club "B"' ),
        );
        $match->date = '2026-06-15 18:30:00';
        $match->start_time = '18:30';
        $match->winner_id = 1;
        $match->score = '6-4 6-2';
        $match->status = '1';

        $formatted = $this->adapter->prepare_match_for_response( $match );

        self::assertIsString( $formatted->status );
        self::assertSame( 'Not played', $formatted->status );
    }

    public function test_build_match_response_with_real_fixture_entity(): void {
        global $racketmanager;
        $plugin = $this->createStub( RacketManager::class );
        $container = new Simple_Container();

        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Park Tennis Club' );

        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Grove Tennis Club' );

        $team_repo = $this->createStub( Team_Repository_Interface::class );
        $team_repo->method( 'find_by_id' )->willReturnCallback(
            fn( int $id ) => 10 === $id ? $home_team : ( 20 === $id ? $away_team : null )
        );

        $league = $this->createStub( League::class );
        $league->title = 'Men\'s Division 2';
        $league_repo = $this->createStub( League_Repository_Interface::class );
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_league_repository' )->willReturn( $league_repo );

        $container->set( 'team_repository', fn() => $team_repo );
        $container->set( 'competition_service', fn() => $comp_service );
        $plugin->container = $container;
        $racketmanager     = $plugin;

        $fixture              = new Fixture();
        $fixture->id          = 99;
        $fixture->league_id   = 7;
        $fixture->home_team   = '10';
        $fixture->away_team   = '20';
        $fixture->winner_id   = 10;
        $fixture->home_points = '5.5';
        $fixture->away_points = '3.5';
        $fixture->status      = 0;
        $fixture->date        = '2026-07-01 18:00:00';
        $fixture->start_time  = '18:00';

        $dto = $this->adapter->build_match_response( $fixture );

        self::assertSame( 'Men\'s Division 2', $dto->league );
        self::assertSame( 'Park Tennis Club', $dto->home_team );
        self::assertSame( 'Grove Tennis Club', $dto->away_team );
        self::assertSame( '2026-07-01', $dto->match_date );
        self::assertSame( '18:00', $dto->match_time );
        self::assertSame( '5.5 - 3.5', $dto->score );
        self::assertSame( 'Complete', $dto->status );

        $array = $dto->to_array();
        self::assertSame( '5.5 - 3.5', $array['score'] );
        self::assertSame( 'Complete', $array['status'] );
    }

    public function test_build_match_response_memoizes_and_handles_missing_teams(): void {
        global $racketmanager;
        $plugin = $this->createStub( RacketManager::class );
        $container = new Simple_Container();

        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Park Tennis Club' );

        $empty_title_team = $this->createStub( Team::class );
        $empty_title_team->method( 'get_name' )->willReturn( '' );
        $empty_title_team->title = '';

        $lookup_count = 0;
        $team_repo = $this->createStub( Team_Repository_Interface::class );
        $team_repo->method( 'find_by_id' )->willReturnCallback( function( int $id ) use ( $home_team, $empty_title_team, &$lookup_count ) {
            $lookup_count++;
            if ( 10 === $id ) {
                return $home_team;
            }
            if ( 777 === $id ) {
                return $empty_title_team;
            }
            if ( 999 === $id ) {
                return null;
            }
            throw new Team_Not_Found_Exception( 'Team 888 not found' );
        } );

        $container->set( 'team_repository', fn() => $team_repo );
        $plugin->container = $container;
        $racketmanager     = $plugin;

        $adapter = new Rest_Controller_Adapter();

        $f1 = new Fixture();
        $f1->home_team = '10';
        $f1->away_team = '999';

        $dto1 = $adapter->build_match_response( $f1 );
        self::assertSame( 'Park Tennis Club', $dto1->home_team );
        self::assertSame( '', $dto1->away_team );

        $f2 = new Fixture();
        $f2->home_team = '10';
        $f2->away_team = '999';

        $dto2 = $adapter->build_match_response( $f2 );
        self::assertSame( 'Park Tennis Club', $dto2->home_team );
        self::assertSame( '', $dto2->away_team );

        // Team 10 looked up once, team 999 looked up once (both cached for subsequent fixture)
        self::assertSame( 2, $lookup_count );

        // Test empty title team safely resolves without throwing undefined get_title()
        $f3 = new Fixture();
        $f3->home_team = '777';
        $f3->away_team = 'Winner of Match 4';

        $dto3 = $adapter->build_match_response( $f3 );
        self::assertSame( '', $dto3->home_team );
        self::assertSame( 'Winner of Match 4', $dto3->away_team );
    }

    public function test_build_match_response_with_unplayed_fixture_omits_score_and_status(): void {
        global $racketmanager;
        $plugin = $this->createStub( RacketManager::class );
        $container = new Simple_Container();
        $plugin->container = $container;
        $racketmanager     = $plugin;

        $fixture             = new Fixture();
        $fixture->id         = 100;
        $fixture->league_id  = null;
        $fixture->home_team  = 'Club A';
        $fixture->away_team  = 'Club B';
        $fixture->winner_id  = null;
        $fixture->date       = '2026-08-10 10:00:00';
        $fixture->start_time = '10:00';

        $dto = $this->adapter->build_match_response( $fixture );

        self::assertSame( 'Club A', $dto->home_team );
        self::assertSame( 'Club B', $dto->away_team );
        self::assertNull( $dto->score );
        self::assertNull( $dto->status );

        $array = $dto->to_array();
        self::assertArrayNotHasKey( 'score', $array );
        self::assertArrayNotHasKey( 'status', $array );
    }

    public function test_prepare_standings_response_and_matches_response(): void {
        $data = array( array( 'team' => 'Team 1', 'points' => 10 ) );
        $standings_resp = $this->adapter->prepare_standings_response( $data, 200 );
        self::assertInstanceOf( WP_REST_Response::class, $standings_resp );
        self::assertSame( 200, $standings_resp->get_status() );
        self::assertSame( $data, $standings_resp->get_data() );

        $matches_resp = $this->adapter->prepare_matches_response( $data, 200 );
        self::assertInstanceOf( WP_REST_Response::class, $matches_resp );
        self::assertSame( 200, $matches_resp->get_status() );
        self::assertSame( $data, $matches_resp->get_data() );
    }

    public function test_get_item_schema(): void {
        $schema = $this->adapter->get_item_schema();
        self::assertIsArray( $schema );
        self::assertSame( 'match', $schema['title'] ?? null );
        self::assertArrayHasKey( 'properties', $schema );
        self::assertArrayHasKey( 'league', $schema['properties'] );
        self::assertArrayHasKey( 'home_team', $schema['properties'] );
        self::assertArrayHasKey( 'away_team', $schema['properties'] );
        self::assertArrayHasKey( 'match_date', $schema['properties'] );
        self::assertArrayHasKey( 'match_time', $schema['properties'] );
        self::assertArrayHasKey( 'score', $schema['properties'] );
        self::assertArrayHasKey( 'status', $schema['properties'] );
    }
}
