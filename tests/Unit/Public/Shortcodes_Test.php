<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Public;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;
use wpdb;

class Shortcodes_Test extends TestCase {
    private $original_wpdb;
    private $original_racketmanager;

    protected function setUp(): void {
        parent::setUp();
        if ( ! defined( 'RACKETMANAGER_PATH' ) ) {
            define( 'RACKETMANAGER_PATH', dirname( __DIR__, 3 ) . '/' );
        }
        if ( ! defined( 'RACKETMANAGER_URL' ) ) {
            define( 'RACKETMANAGER_URL', 'https://example.test/wp-content/plugins/racketmanager/' );
        }

        global $wpdb, $racketmanager;
        $this->original_wpdb = $wpdb ?? null;
        $this->original_racketmanager = $racketmanager ?? null;

        $wpdb_mock = $this->createStub( wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        $wpdb = $wpdb_mock;
    }

    protected function tearDown(): void {
        global $wpdb, $racketmanager;
        $wpdb = $this->original_wpdb;
        $racketmanager = $this->original_racketmanager;
        parent::tearDown();
    }

    public function test_show_daily_matches_with_null_league_on_fixture(): void {
        global $racketmanager;

        $dto = new Fixture_Hydration_DTO(
            id: 201,
            fixture_title: 'Team 1 vs Team 2',
            link: 'https://example.test/match/201/',
            group: 'Group 1',
            date: '2026-10-12',
            date_original: '2026-10-12',
            home_team: '1',
            away_team: '2',
            match_day: 1,
            location: 'Court 1',
            host: null,
            league_id: null,
            season: '2026',
            status: null,
            start_time: '18:00',
            winner_id: null,
            loser_id: null,
            home_points: null,
            away_points: null,
            custom: []
        );

        $fixture = new Fixture( $dto );
        $this->assertNull( $fixture->league );

        $container = new Simple_Container();
        $competition_service = $this->createStub( \Racketmanager\Services\Competition_Service::class );
        $club_service = $this->createStub( \Racketmanager\Services\Club_Service::class );
        $finance_service = $this->createStub( \Racketmanager\Services\Finance_Service::class );
        $player_service = $this->createStub( \Racketmanager\Services\Player_Service::class );
        $registration_service = $this->createStub( \Racketmanager\Services\Registration_Service::class );
        $team_service = $this->createStub( \Racketmanager\Services\Team_Service::class );
        $team1_entity = $this->createStub( \Racketmanager\Domain\Team::class );
        $team1_entity->method( 'get_name' )->willReturn( 'Red Club 1st Team' );
        $team1_entity->method( 'get_team_type' )->willReturn( 'T' );
        $team2_entity = $this->createStub( \Racketmanager\Domain\Team::class );
        $team2_entity->method( 'get_name' )->willReturn( 'Blue Club 2nd Team' );
        $team2_entity->method( 'get_team_type' )->willReturn( 'T' );

        $team1_dtls = new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( team: $team1_entity );
        $team2_dtls = new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( team: $team2_entity );

        $team_service->method( 'get_team_details' )->willReturnCallback( function( $id ) use ( $team1_dtls, $team2_dtls ) {
            return ( (int) $id === 1 ) ? $team1_dtls : $team2_dtls;
        } );

        $tournament_service = $this->createStub( \Racketmanager\Services\Tournament_Service::class );
        $competition_entry_service = $this->createStub( \Racketmanager\Services\Competition_Entry_Service::class );
        $fixture_service = $this->createStub( \Racketmanager\Services\Fixture_Service::class );
        $fixture_detail_service = $this->createStub( \Racketmanager\Services\Fixture\Fixture_Detail_Service::class );
        $competition_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface::class );
        $fixture_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class );

        $container->set( 'competition_service', fn() => $competition_service );
        $container->set( 'club_service', fn() => $club_service );
        $container->set( 'finance_service', fn() => $finance_service );
        $container->set( 'player_service', fn() => $player_service );
        $container->set( 'registration_service', fn() => $registration_service );
        $container->set( 'team_service', fn() => $team_service );
        $container->set( 'tournament_service', fn() => $tournament_service );
        $container->set( 'competition_entry_service', fn() => $competition_entry_service );
        $container->set( 'fixture_service', fn() => $fixture_service );
        $container->set( 'fixture_detail_service', fn() => $fixture_detail_service );
        $container->set( 'competition_repository', fn() => $competition_repo );
        $container->set( 'fixture_repository', fn() => $fixture_repo );

        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->container = $container;
        $app_mock->date_format = 'Y-m-d';
        $app_mock->method( 'get_matches' )->willReturn( [ $fixture ] );
        $racketmanager = $app_mock;

        $shortcodes = new Shortcodes( $app_mock );
        $output = $shortcodes->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Daily matches', $output );
        $this->assertStringContainsString( 'Red Club 1st Team', $output );
        $this->assertStringContainsString( 'Blue Club 2nd Team', $output );
        $this->assertStringContainsString( 'href="https://example.test/match/201/"', $output );
    }

    public function test_show_daily_matches_with_update_allowed_shows_result_link(): void {
        global $racketmanager;

        $dto = new Fixture_Hydration_DTO(
            id: 201,
            fixture_title: 'Team 1 vs Team 2',
            link: 'https://example.test/match/201/',
            group: 'Group 1',
            date: '2026-10-12',
            date_original: '2026-10-12',
            home_team: '1',
            away_team: '2',
            match_day: 1,
            location: 'Court 1',
            host: null,
            league_id: null,
            season: '2026',
            status: null,
            start_time: '18:00',
            winner_id: null,
            loser_id: null,
            home_points: null,
            away_points: null,
            custom: []
        );

        $fixture = new Fixture( $dto );

        $container = new Simple_Container();
        $competition_service = $this->createStub( \Racketmanager\Services\Competition_Service::class );
        $club_service = $this->createStub( \Racketmanager\Services\Club_Service::class );
        $finance_service = $this->createStub( \Racketmanager\Services\Finance_Service::class );
        $player_service = $this->createStub( \Racketmanager\Services\Player_Service::class );
        $registration_service = $this->createStub( \Racketmanager\Services\Registration_Service::class );
        $team_service = $this->createStub( \Racketmanager\Services\Team_Service::class );
        $team1_entity = $this->createStub( \Racketmanager\Domain\Team::class );
        $team1_entity->method( 'get_name' )->willReturn( 'Red Club 1st Team' );
        $team1_entity->method( 'get_team_type' )->willReturn( 'T' );
        $team2_entity = $this->createStub( \Racketmanager\Domain\Team::class );
        $team2_entity->method( 'get_name' )->willReturn( 'Blue Club 2nd Team' );
        $team2_entity->method( 'get_team_type' )->willReturn( 'T' );

        $team1_dtls = new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( team: $team1_entity );
        $team2_dtls = new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( team: $team2_entity );

        $team_service->method( 'get_team_details' )->willReturnCallback( function( $id ) use ( $team1_dtls, $team2_dtls ) {
            return ( (int) $id === 1 ) ? $team1_dtls : $team2_dtls;
        } );

        $tournament_service = $this->createStub( \Racketmanager\Services\Tournament_Service::class );
        $competition_entry_service = $this->createStub( \Racketmanager\Services\Competition_Entry_Service::class );
        $fixture_service = $this->createStub( \Racketmanager\Services\Fixture_Service::class );
        $fixture_detail_service = $this->createStub( \Racketmanager\Services\Fixture\Fixture_Detail_Service::class );
        $competition_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface::class );
        $fixture_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class );

        $permission_service = $this->createStub( \Racketmanager\Services\Fixture\Fixture_Permission_Service::class );
        $permission_service->method( 'is_update_allowed' )->willReturn( (object) array( 'user_can_update' => true ) );

        $container->set( 'competition_service', fn() => $competition_service );
        $container->set( 'club_service', fn() => $club_service );
        $container->set( 'finance_service', fn() => $finance_service );
        $container->set( 'player_service', fn() => $player_service );
        $container->set( 'registration_service', fn() => $registration_service );
        $container->set( 'team_service', fn() => $team_service );
        $container->set( 'tournament_service', fn() => $tournament_service );
        $container->set( 'competition_entry_service', fn() => $competition_entry_service );
        $container->set( 'fixture_service', fn() => $fixture_service );
        $container->set( 'fixture_detail_service', fn() => $fixture_detail_service );
        $container->set( 'competition_repository', fn() => $competition_repo );
        $container->set( 'fixture_repository', fn() => $fixture_repo );
        $container->set( 'fixture_permission_service', fn() => $permission_service );

        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->container = $container;
        $app_mock->date_format = 'Y-m-d';
        $app_mock->method( 'get_matches' )->willReturn( [ $fixture ] );
        $racketmanager = $app_mock;

        $shortcodes = new Shortcodes( $app_mock );
        $output = $shortcodes->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertIsString( $output );
        $this->assertStringContainsString( 'href="https://example.test/match/201/"', $output );
        $this->assertStringContainsString( 'href="https://example.test/match/201/result/"', $output );
        $this->assertStringContainsString( 'class="btn match__btn"', $output );
    }

    public function test_show_daily_matches_with_update_not_allowed_hides_result_link(): void {
        global $racketmanager;

        $dto = new Fixture_Hydration_DTO(
            id: 201,
            fixture_title: 'Team 1 vs Team 2',
            link: 'https://example.test/match/201/',
            group: 'Group 1',
            date: '2026-10-12',
            date_original: '2026-10-12',
            home_team: '1',
            away_team: '2',
            match_day: 1,
            location: 'Court 1',
            host: null,
            league_id: null,
            season: '2026',
            status: null,
            start_time: '18:00',
            winner_id: null,
            loser_id: null,
            home_points: null,
            away_points: null,
            custom: []
        );

        $fixture = new Fixture( $dto );

        $container = new Simple_Container();
        $competition_service = $this->createStub( \Racketmanager\Services\Competition_Service::class );
        $club_service = $this->createStub( \Racketmanager\Services\Club_Service::class );
        $finance_service = $this->createStub( \Racketmanager\Services\Finance_Service::class );
        $player_service = $this->createStub( \Racketmanager\Services\Player_Service::class );
        $registration_service = $this->createStub( \Racketmanager\Services\Registration_Service::class );
        $team_service = $this->createStub( \Racketmanager\Services\Team_Service::class );
        $team1_entity = $this->createStub( \Racketmanager\Domain\Team::class );
        $team1_entity->method( 'get_name' )->willReturn( 'Red Club 1st Team' );
        $team1_entity->method( 'get_team_type' )->willReturn( 'T' );
        $team2_entity = $this->createStub( \Racketmanager\Domain\Team::class );
        $team2_entity->method( 'get_name' )->willReturn( 'Blue Club 2nd Team' );
        $team2_entity->method( 'get_team_type' )->willReturn( 'T' );

        $team1_dtls = new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( team: $team1_entity );
        $team2_dtls = new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( team: $team2_entity );

        $team_service->method( 'get_team_details' )->willReturnCallback( function( $id ) use ( $team1_dtls, $team2_dtls ) {
            return ( (int) $id === 1 ) ? $team1_dtls : $team2_dtls;
        } );

        $tournament_service = $this->createStub( \Racketmanager\Services\Tournament_Service::class );
        $competition_entry_service = $this->createStub( \Racketmanager\Services\Competition_Entry_Service::class );
        $fixture_service = $this->createStub( \Racketmanager\Services\Fixture_Service::class );
        $fixture_detail_service = $this->createStub( \Racketmanager\Services\Fixture\Fixture_Detail_Service::class );
        $competition_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface::class );
        $fixture_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class );

        $permission_service = $this->createStub( \Racketmanager\Services\Fixture\Fixture_Permission_Service::class );
        $permission_service->method( 'is_update_allowed' )->willReturn( (object) array( 'user_can_update' => false ) );

        $container->set( 'competition_service', fn() => $competition_service );
        $container->set( 'club_service', fn() => $club_service );
        $container->set( 'finance_service', fn() => $finance_service );
        $container->set( 'player_service', fn() => $player_service );
        $container->set( 'registration_service', fn() => $registration_service );
        $container->set( 'team_service', fn() => $team_service );
        $container->set( 'tournament_service', fn() => $tournament_service );
        $container->set( 'competition_entry_service', fn() => $competition_entry_service );
        $container->set( 'fixture_service', fn() => $fixture_service );
        $container->set( 'fixture_detail_service', fn() => $fixture_detail_service );
        $container->set( 'competition_repository', fn() => $competition_repo );
        $container->set( 'fixture_repository', fn() => $fixture_repo );
        $container->set( 'fixture_permission_service', fn() => $permission_service );

        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->container = $container;
        $app_mock->date_format = 'Y-m-d';
        $app_mock->method( 'get_matches' )->willReturn( [ $fixture ] );
        $racketmanager = $app_mock;

        $shortcodes = new Shortcodes( $app_mock );
        $output = $shortcodes->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertIsString( $output );
        $this->assertStringContainsString( 'href="https://example.test/match/201/"', $output );
        $this->assertStringNotContainsString( 'href="https://example.test/match/201/result/"', $output );
        $this->assertStringNotContainsString( 'class="btn match__btn"', $output );
    }

    public function test_show_latest_results_with_null_league_on_fixture(): void {
        global $racketmanager;

        $dto = new Fixture_Hydration_DTO(
            id: 202,
            fixture_title: 'Team 3 vs Team 4',
            link: 'https://example.test/match/202/',
            group: 'Group 2',
            date: '2026-10-10',
            date_original: '2026-10-10',
            home_team: '3',
            away_team: '4',
            match_day: 1,
            location: 'Court 2',
            host: null,
            league_id: 10,
            season: '2026',
            status: null,
            start_time: '19:00',
            winner_id: null,
            loser_id: null,
            home_points: null,
            away_points: null,
            custom: []
        );

        $fixture = new Fixture( $dto );

        $container = new Simple_Container();
        $competition_service = $this->createStub( \Racketmanager\Services\Competition_Service::class );
        $league_repo = $this->createStub( \Racketmanager\Repositories\League_Repository::class );
        $league_mock = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $event_mock = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $comp_mock = (object) [ 'type' => Competition_Type::LEAGUE ];
        $event_mock->competition = $comp_mock;
        $league_mock->method( 'get_name' )->willReturn( 'Division 2' );
        $league_mock->title = 'Division 2';
        $league_mock->id = 10;
        $league_mock->event = $event_mock;
        $league_repo->method( 'find_by_id' )->willReturn( $league_mock );
        $competition_service->method( 'get_league_repository' )->willReturn( $league_repo );

        $club_service = $this->createStub( \Racketmanager\Services\Club_Service::class );
        $finance_service = $this->createStub( \Racketmanager\Services\Finance_Service::class );
        $player_service = $this->createStub( \Racketmanager\Services\Player_Service::class );
        $registration_service = $this->createStub( \Racketmanager\Services\Registration_Service::class );
        $team_service = $this->createStub( \Racketmanager\Services\Team_Service::class );
        $team3_entity = $this->createStub( \Racketmanager\Domain\Team::class );
        $team3_entity->method( 'get_name' )->willReturn( 'Green Team' );
        $team3_entity->method( 'get_team_type' )->willReturn( 'T' );
        $team4_entity = $this->createStub( \Racketmanager\Domain\Team::class );
        $team4_entity->method( 'get_name' )->willReturn( 'Yellow Team' );
        $team4_entity->method( 'get_team_type' )->willReturn( 'T' );

        $team3_dtls = new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( team: $team3_entity );
        $team4_dtls = new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( team: $team4_entity );

        $team_service->method( 'get_team_details' )->willReturnCallback( function( $id ) use ( $team3_dtls, $team4_dtls ) {
            return ( (int) $id === 3 ) ? $team3_dtls : $team4_dtls;
        } );

        $tournament_service = $this->createStub( \Racketmanager\Services\Tournament_Service::class );
        $competition_entry_service = $this->createStub( \Racketmanager\Services\Competition_Entry_Service::class );
        $fixture_service = $this->createStub( \Racketmanager\Services\Fixture_Service::class );
        $fixture_detail_service = $this->createStub( \Racketmanager\Services\Fixture\Fixture_Detail_Service::class );
        $competition_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface::class );
        $fixture_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class );

        $container->set( 'competition_service', fn() => $competition_service );
        $container->set( 'club_service', fn() => $club_service );
        $container->set( 'finance_service', fn() => $finance_service );
        $container->set( 'player_service', fn() => $player_service );
        $container->set( 'registration_service', fn() => $registration_service );
        $container->set( 'team_service', fn() => $team_service );
        $container->set( 'tournament_service', fn() => $tournament_service );
        $container->set( 'competition_entry_service', fn() => $competition_entry_service );
        $container->set( 'fixture_service', fn() => $fixture_service );
        $container->set( 'fixture_detail_service', fn() => $fixture_detail_service );
        $container->set( 'competition_repository', fn() => $competition_repo );
        $container->set( 'fixture_repository', fn() => $fixture_repo );

        $app_mock = $this->createStub( RacketManager::class );
        $app_mock->container = $container;
        $app_mock->date_format = 'Y-m-d';
        $app_mock->method( 'get_matches' )->willReturn( [ $fixture ] );
        $racketmanager = $app_mock;

        $shortcodes = new Shortcodes( $app_mock );
        $output = $shortcodes->show_latest_results( [ 'days' => 7 ] );

        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Latest Results', $output );
        $this->assertStringContainsString( 'Division 2', $output );
        $this->assertStringContainsString( 'Green Team', $output );
        $this->assertStringContainsString( 'Yellow Team', $output );
    }
}
