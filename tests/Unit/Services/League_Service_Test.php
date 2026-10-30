<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Enums\Team_Profile;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Has_Matches_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Notification_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Standings\Standings_Service;
use RuntimeException;
use stdClass;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class League_Service_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;

    private League_Service $service;
    private $league_repository;
    private $event_repository;
    private $league_team_repository;
    private $team_repository;
    private $fixture_repository;
    private Standings_Service $standings_service;

    protected function setUp(): void {
        parent::setUp();
        $this->league_repository      = $this->createMock( League_Repository_Interface::class );
        $this->event_repository       = $this->createMock( Event_Repository_Interface::class );
        $this->league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $this->team_repository        = $this->createMock( Team_Repository_Interface::class );
        $this->fixture_repository     = $this->createMock( Fixture_Repository_Interface::class );
        $this->standings_service      = $this->createMock( Standings_Service::class );
        $plugin_instance              = $this->createMock( RacketManager::class );

        $this->service = new League_Service(
            $plugin_instance,
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            standings_service: $this->standings_service,
            league_query_service: $this->league_query_service( $this->league_repository, null, $this->fixture_repository )
        );
    }

    public function test_add_team_to_league_returns_existing_if_already_exists(): void {
        $team_id   = 1;
        $league_id = 2;
        $season    = 2024;

        $team = $this->createMock( Team::class );
        $this->team_repository->method( 'find_by_id' )->willReturn( $team );

        $league = $this->createMock( League::class );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $existing_league_team = $this->createMock( League_Team::class );

        $this->league_team_repository->expects( self::once() )
                                     ->method( 'find_by_team_league_and_season' )
                                     ->with( $team_id, $league_id, $season )
                                     ->willReturn( $existing_league_team );

        $this->league_team_repository->expects( self::never() )
                                     ->method( 'save' );

        $result = $this->service->add_team_to_league( $team_id, $league_id, $season );

        self::assertSame( $existing_league_team, $result );
    }

    public function test_add_team_to_league_creates_active_league_team_via_factory(): void {
        $team_id   = 11;
        $league_id = 22;
        $season    = 2025;

        $this->team_repository->method( 'find_by_id' )->willReturn( $this->createMock( Team::class ) );
        $this->league_repository->method( 'find_by_id' )->willReturn( $this->createMock( League::class ) );
        $this->league_team_repository->method( 'find_by_team_league_and_season' )->willReturn( null );

        $this->league_team_repository->expects( self::once() )
            ->method( 'save' )
            ->with(
                self::callback(
                    static fn( League_Team $league_team ): bool =>
                        11 === $league_team->get_team_id() &&
                        22 === $league_team->get_league_id() &&
                        '2025' === $league_team->get_season() &&
                        Team_Profile::ACTIVE === $league_team->get_profile_enum()
                )
            );

        $result = $this->service->add_team_to_league( $team_id, $league_id, $season );

        self::assertInstanceOf( League_Team::class, $result );
        self::assertSame( '2025', $result->get_season() );
    }

    public function test_remove_team_from_league_throws_exception_if_matches_exist(): void {
        $team_id   = 1;
        $league_id = 2;
        $season    = 2024;

        $league_team = $this->createMock( League_Team::class );
        $this->league_team_repository->method( 'find_by_team_league_and_season' )
                                     ->willReturn( $league_team );

        $this->league_repository->expects( $this->once() )->method( 'query_matches' )
            ->with(
                $league_id,
                $this->callback( static fn( array $args ): bool => 1 === (int) $args['team_id'] && '2024' === (string) $args['season'] && 'all' === $args['final'] ),
                false
            )
            ->willReturn( [ (object) ['id' => 101] ] );

        $league = League_Test_Factory::from_object( (object) [ 'title' => 'Division 1' ] );
        $league->id = $league_id;
        $league->seasons = [ '2024' => [ 'name' => '2024' ] ];
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $this->expectException( Team_Has_Matches_Exception::class );

        $this->service->remove_team_from_league( $team_id, $league_id, $season );
    }

    public function test_league_team_constructor_handles_string_profile(): void {
        $data = new stdClass();
        $data->profile = '1'; // ACTIVE
        $data->id = 123;
        $data->team_id = 456;
        $data->league_id = 1;
        $data->season = '2024';
        $data->add_points = 0;
        $data->points_plus = 0;
        $data->points_minus = 0;
        $data->points_2_plus = 0;
        $data->points_2_minus = 0;
        $data->diff = 0;
        $data->done_matches = 0;
        $data->won_matches = 0;
        $data->lost_matches = 0;
        $data->draw_matches = 0;

        $league_team = League_Team::from_object( $data );

        self::assertInstanceOf( Team_Profile::class, $league_team->get_profile_enum() );
        self::assertEquals( Team_Profile::ACTIVE, $league_team->get_profile_enum() );
        self::assertEquals( 1, $league_team->get_profile() );
    }

    public function test_remove_teams_from_league_collects_messages_and_errors(): void {
        $league_id = 1;
        $season = 2024;
        $ids = [101, 102];

        $league_team1 = $this->createMock( League_Team::class );
        $league_team1->method('get_team_id')->willReturn(456);
        $league_team1->method('get_id')->willReturn(101);

        $league_team2 = $this->createMock( League_Team::class );
        $league_team2->method('get_team_id')->willReturn(789);
        $league_team2->method('get_id')->willReturn(102);

        $this->league_team_repository->method('find_by_id')
            ->willReturnMap([
                [101, $league_team1],
                [102, $league_team2]
            ]);

        $this->league_team_repository->method('find_by_team_league_and_season')
            ->willReturnMap([
                [456, $league_id, $season, $league_team1],
                [789, $league_id, $season, $league_team2]
            ]);

        $league = League_Test_Factory::from_object( (object) [ 'title' => 'Division 1' ] );
        $league->id = $league_id;
        $league->seasons = [ (string) $season => [ 'name' => (string) $season ] ];
        $this->league_repository->method('find_by_id')->willReturn($league);

        // First team has matches
        $this->league_repository->method( 'query_matches' )
            ->willReturnCallback( function ( int $query_league_id, array $args ) use ( $league_id, $season ): array {
                if ( $query_league_id === $league_id && 456 === (int) $args['team_id'] && (string) $season === (string) $args['season'] ) {
                    return [ (object) [ 'id' => 1 ] ];
                }
                return [];
            } );

        $result = $this->service->remove_teams_from_league($ids, $league_id, $season);

        self::assertTrue($result['any_error']);
        self::assertCount(2, $result['messages']);
        self::assertStringContainsString('cannot be deleted', $result['messages'][0]);
        self::assertStringContainsString('deleted', $result['messages'][1]);
    }

    public function test_rank_teams_by_points(): void {
        $team1 = new stdClass();
        $team1->points = ['plus' => 10];
        $team1->sets_won = 5;
        $team1->sets_allowed = 2;
        $team1->games_won = 30;
        $team1->games_allowed = 20;
        $team1->title = 'Team B';

        $team2 = new stdClass();
        $team2->points = ['plus' => 10];
        $team2->sets_won = 6;
        $team2->sets_allowed = 2;
        $team2->games_won = 35;
        $team2->games_allowed = 20;
        $team2->title = 'Team A';

        $teams = [$team1, $team2];
        $ranked = $this->service->rank_teams_by_points($teams);

        self::assertSame($team2, $ranked[0]); // More sets won
        self::assertSame($team1, $ranked[1]);
    }

    public function test_rank_teams_manual(): void {
        $league = $this->createMock(League::class);
        $team1_id = 101;
        $team2_id = 102;

        $team1_data = new stdClass();
        $team1_data->id = $team1_id;
        $team1_data->team_id = 456;
        $team1_data->league_id = 1;
        $team1_data->season = '2024';
        $team1_data->profile = '1';
        $team1 = League_Team::from_object($team1_data);

        $team2_data = new stdClass();
        $team2_data->id = $team2_id;
        $team2_data->team_id = 789;
        $team2_data->league_id = 1;
        $team2_data->season = '2024';
        $team2_data->profile = '1';
        $team2 = League_Team::from_object($team2_data);

        $this->league_team_repository->method('find_by_id')
            ->willReturnMap([
                [$team1_id, $team1],
                [$team2_id, $team2]
            ]);

        $post = [
            'rank' => [2, 1],
            'table_id' => [$team1_id, $team2_id]
        ];

        $this->league_team_repository->expects(self::exactly(2))
            ->method('save');
        $this->standings_service->expects( self::once() )->method( 'is_tie' )
            ->with( $team1, $team2 )->willReturn( false );

        $result = $this->service->rank_teams($league, 'manual', $post, [$team1_id, $team2_id]);

        self::assertTrue($result);
        self::assertEquals(2, $team1->get_rank());
        self::assertEquals(1, $team2->get_rank());
    }

    public function test_delete_league_deletes_fixtures_teams_and_league_row(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $service      = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo
        );

        $league = $this->createMock( League::class );
        $league->id = 15;
        $league->method( 'get_id' )->willReturn( 15 );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with(
                15,
                self::callback( static fn( array $criteria ): bool =>
                    in_array( $criteria['season'] ?? null, array( 'any', 'all' ), true ) &&
                    'all' === ( $criteria['final'] ?? null ) &&
                    0 === ( $criteria['limit'] ?? null ) &&
                    false === ( $criteria['cache'] ?? null )
                ),
                false
            )
            ->willReturn( array( (object) array( 'id' => 101 ), (object) array( 'id' => 102 ) ) );

        $deleted_fixtures = array();
        $fixture_repo->expects( self::exactly( 2 ) )
            ->method( 'delete' )
            ->willReturnCallback( static function( int $id ) use ( &$deleted_fixtures ): bool {
                $deleted_fixtures[] = $id;
                return true;
            } );

        $this->league_repository->expects( self::once() )
            ->method( 'delete_league_teams' )
            ->with( 15 );

        $this->league_repository->expects( self::once() )
            ->method( 'delete' )
            ->with( 15 );

        $service->delete_league( $league );

        self::assertSame( array( 101, 102 ), $deleted_fixtures );
    }

    public function test_delete_league_with_int_id(): void {
        $service = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository
        );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with( 22 )
            ->willReturn( array() );

        $this->league_repository->expects( self::once() )
            ->method( 'delete_league_teams' )
            ->with( 22 );

        $this->league_repository->expects( self::once() )
            ->method( 'delete' )
            ->with( 22 );

        $service->delete_league( 22 );
    }

    public function test_delete_league_throws_when_fixture_repository_missing_and_fixtures_exist(): void {
        $service = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository
        );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with( 25 )
            ->willReturn( array( (object) array( 'id' => 500 ) ) );

        $this->league_repository->expects( self::never() )->method( 'delete_league_teams' );
        $this->league_repository->expects( self::never() )->method( 'delete' );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Cannot delete League: Fixture_Repository_Interface collaborator is required.' );

        $service->delete_league( 25 );
    }

    public function test_delete_league_throws_and_aborts_when_fixture_delete_fails(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $service      = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo
        );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with( 30 )
            ->willReturn( array( (object) array( 'id' => 101 ), (object) array( 'id' => 102 ) ) );

        $fixture_repo->expects( self::exactly( 2 ) )
            ->method( 'delete' )
            ->willReturnMap( array(
                array( 101, true ),
                array( 102, false ),
            ) );

        $this->league_repository->expects( self::never() )->method( 'delete_league_teams' );
        $this->league_repository->expects( self::never() )->method( 'delete' );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Failed to delete fixture(s) [102] for league 30' );

        $service->delete_league( 30 );
    }

    public function test_delete_team_success(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $service      = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo
        );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with(
                10,
                self::callback( static fn( array $criteria ): bool =>
                    100 === ( $criteria['team_id'] ?? null ) &&
                    '2026' === ( $criteria['season'] ?? null )
                ),
                false
            )
            ->willReturn( array( (object) array( 'id' => 201 ) ) );

        $fixture_repo->expects( self::once() )
            ->method( 'delete' )
            ->with( 201 )
            ->willReturn( true );

        $this->league_repository->expects( self::once() )
            ->method( 'delete_team' )
            ->with( 10, 100, '2026' );

        $service->delete_team( 10, 100, '2026' );
    }

    public function test_delete_team_throws_when_fixture_delete_fails(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $service      = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo
        );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->willReturn( array( (object) array( 'id' => 201 ) ) );

        $fixture_repo->expects( self::once() )
            ->method( 'delete' )
            ->with( 201 )
            ->willReturn( false );

        $this->league_repository->expects( self::never() )->method( 'delete_team' );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Failed to delete fixture(s) [201] for team 100 in league 10' );

        $service->delete_team( 10, 100, '2026' );
    }

    public function test_delete_season_fixtures_success(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $service      = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo
        );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with(
                12,
                self::callback( static fn( array $criteria ): bool =>
                    '2025' === ( $criteria['season'] ?? null )
                ),
                false
            )
            ->willReturn( array( (object) array( 'id' => 301 ) ) );

        $fixture_repo->expects( self::once() )
            ->method( 'delete' )
            ->with( 301 )
            ->willReturn( true );

        $service->delete_season_fixtures( 12, '2025' );
    }

    public function test_delete_season_fixtures_throws_when_fixture_repository_missing(): void {
        $service = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository
        );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Cannot delete season matches from League: Fixture_Repository_Interface collaborator is required.' );

        $service->delete_season_fixtures( 12, '2025' );
    }

    public function test_delete_season_fixtures_throws_when_fixture_delete_fails(): void {
        $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $service      = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo
        );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->willReturn( array( (object) array( 'id' => 301 ) ) );

        $fixture_repo->expects( self::once() )
            ->method( 'delete' )
            ->with( 301 )
            ->willReturn( false );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Failed to delete fixture(s) [301] for season 2025 in league 12' );

        $service->delete_season_fixtures( 12, '2025' );
    }

    public function test_withdraw_team_regular_league_cancels_matches_and_updates_standings(): void {
        $fixture_repo           = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_result_manager = $this->createMock( Fixture_Result_Manager::class );
        $standings_service      = $this->createMock( Standings_Service::class );
        $notification_service   = $this->createMock( Competition_Notification_Service::class );

        $service = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo,
            $fixture_result_manager,
            $standings_service,
            null,
            $notification_service
        );

        $league = $this->createMock( League::class );
        $league->id              = 5;
        $league->is_championship = false;
        $league->method( 'get_id' )->willReturn( 5 );

        $this->league_repository->expects( self::atLeastOnce() )
            ->method( 'find_by_id' )
            ->with( 5 )
            ->willReturn( $league );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with(
                5,
                self::callback( static fn( array $criteria ): bool =>
                    50 === ( $criteria['team_id'] ?? null ) &&
                    '2026' === ( $criteria['season'] ?? null ) &&
                    '' === ( $criteria['final'] ?? null ) &&
                    ! isset( $criteria['pending'] )
                ),
                false
            )
            ->willReturn( array( (object) array( 'id' => 401 ) ) );

        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_status' )->willReturn( null );
        $fixture->method( 'get_home_team' )->willReturn( '50' );
        $fixture->method( 'get_away_team' )->willReturn( '60' );

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 401 )
            ->willReturn( $fixture );

        $fixture_result_manager->expects( self::once() )
            ->method( 'confirm_result' )
            ->with( $fixture, '', null, self::isInstanceOf( \Racketmanager\Domain\Result\Result::class ) );

        $this->league_repository->expects( self::once() )
            ->method( 'withdraw_team' )
            ->with( 5, 50, '2026' );

        $standings_service->expects( self::once() )
            ->method( 'update_standings' )
            ->with( $league, '2026' );

        $notification_service->expects( self::once() )
            ->method( 'notify_withdrawn_team' )
            ->with( $league, 50, '2026' );

        $service->withdraw_team( 5, 50, '2026' );
    }

    public function test_withdraw_team_championship_league_handles_walkovers_and_notifications(): void {
        $fixture_repo           = $this->createMock( Fixture_Repository_Interface::class );
        $fixture_result_manager = $this->createMock( Fixture_Result_Manager::class );
        $standings_service      = $this->createMock( Standings_Service::class );
        $notification_service   = $this->createMock( Notification_Service::class );

        $service = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo,
            $fixture_result_manager,
            $standings_service,
            $notification_service
        );

        $league = $this->createMock( League::class );
        $league->id              = 8;
        $league->is_championship = true;
        $league->method( 'get_id' )->willReturn( 8 );

        $this->league_repository->expects( self::atLeastOnce() )
            ->method( 'find_by_id' )
            ->with( 8 )
            ->willReturn( $league );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->with(
                8,
                self::callback( static fn( array $criteria ): bool =>
                    70 === ( $criteria['team_id'] ?? null ) &&
                    '2026' === ( $criteria['season'] ?? null ) &&
                    true === ( $criteria['pending'] ?? null )
                ),
                false
            )
            ->willReturn( array( (object) array( 'id' => 501 ) ) );

        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_status' )->willReturn( null );
        $fixture->method( 'get_home_team' )->willReturn( '70' );
        $fixture->method( 'get_leg' )->willReturn( 2 );
        $fixture->method( 'get_id' )->willReturn( 501 );
        $fixture->method( 'get_confirmed' )->willReturn( 'confirmed' );

        $fixture_repo->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 501 )
            ->willReturn( $fixture );

        $notification_service->expects( self::once() )
            ->method( 'notify_team_withdrawal' )
            ->with( $fixture, 70 );

        $fixture_result_manager->expects( self::once() )
            ->method( 'handle_fixture_result_update' )
            ->with(
                $fixture,
                self::callback( static fn( Fixture_Result_Update_Request $req ): bool =>
                    501 === $req->fixture_id &&
                    'walkover_player2' === $req->match_status &&
                    'confirmed' === $req->confirmed
                )
            );

        $this->league_repository->expects( self::once() )
            ->method( 'withdraw_team' )
            ->with( 8, 70, '2026' );

        $standings_service->expects( self::never() )
            ->method( 'update_standings' );

        $service->withdraw_team( 8, 70, '2026' );
    }

    public function test_withdraw_team_throws_when_fixture_result_manager_missing(): void {
        $fixture_repo      = $this->createMock( Fixture_Repository_Interface::class );
        $standings_service = $this->createMock( Standings_Service::class );
        $service           = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $fixture_repo,
            null,
            $standings_service
        );

        $league = $this->createMock( League::class );
        $league->id              = 8;
        $league->is_championship = false;
        $league->method( 'get_id' )->willReturn( 8 );

        $this->league_repository->expects( self::atLeastOnce() )
            ->method( 'find_by_id' )
            ->with( 8 )
            ->willReturn( $league );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->willReturn( array( (object) array( 'id' => 501 ) ) );

        $this->league_repository->expects( self::never() )
            ->method( 'withdraw_team' );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Cannot withdraw team from League: Fixture_Result_Manager collaborator is required.' );

        $service->withdraw_team( 8, 70, '2026' );
    }

    public function test_withdraw_team_throws_when_league_not_found(): void {
        $service = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository
        );

        $this->league_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 999 )
            ->willReturn( null );

        $this->league_repository->expects( self::never() )
            ->method( 'withdraw_team' );

        $this->expectException( League_Not_Found_Exception::class );
        $this->expectExceptionMessage( 'Cannot withdraw team from League: League not found.' );

        $service->withdraw_team( 999, 70, '2026' );
    }

    public function test_withdraw_team_throws_when_standings_service_missing_for_regular_league_before_mutations(): void {
        $service = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            $this->createMock( Fixture_Repository_Interface::class ),
            $this->createMock( Fixture_Result_Manager::class ),
            null
        );

        $league = $this->createMock( League::class );
        $league->id              = 8;
        $league->is_championship = false;
        $league->method( 'get_id' )->willReturn( 8 );

        $this->league_repository->expects( self::atLeastOnce() )
            ->method( 'find_by_id' )
            ->with( 8 )
            ->willReturn( $league );

        $this->league_repository->expects( self::never() )
            ->method( 'withdraw_team' );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Cannot update standings after team withdrawal: Standings_Service collaborator is required.' );

        $service->withdraw_team( 8, 70, '2026' );
    }

    public function test_withdraw_team_throws_when_fixture_repository_missing(): void {
        $standings_service      = $this->createMock( Standings_Service::class );
        $fixture_result_manager = $this->createMock( Fixture_Result_Manager::class );
        $service                = new League_Service(
            $this->createMock( RacketManager::class ),
            $this->league_repository,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository,
            null,
            $fixture_result_manager,
            $standings_service
        );

        $league = $this->createMock( League::class );
        $league->id              = 8;
        $league->is_championship = false;
        $league->method( 'get_id' )->willReturn( 8 );

        $this->league_repository->expects( self::atLeastOnce() )
            ->method( 'find_by_id' )
            ->with( 8 )
            ->willReturn( $league );

        $this->league_repository->expects( self::once() )
            ->method( 'query_matches' )
            ->willReturn( array( (object) array( 'id' => 501 ) ) );

        $this->league_repository->expects( self::never() )
            ->method( 'withdraw_team' );

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Cannot withdraw team from League: Fixture_Repository_Interface collaborator is required.' );

        $service->withdraw_team( 8, 70, '2026' );
    }

    public function test_add_league_to_event_returns_created_league(): void {
        $event = \Racketmanager\Domain\Competition\Event::from_object( (object) array(
            'id'             => 5,
            'name'           => 'Men Singles',
            'type'           => 'singles',
            'competition_id' => 1,
        ) );
        $this->event_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 5 )
            ->willReturn( $event );

        $this->league_repository->expects( self::once() )
            ->method( 'save' )
            ->with( self::callback( static fn( $league ): bool => 'Men Singles' === $league->title && 5 === $league->event_id ) );

        $created = $this->service->add_league_to_event( 5, 'Men Singles' );
        self::assertSame( 5, $created->event_id );
        self::assertSame( 'Men Singles', $created->title );
        self::assertSame( $event, $created->event );
    }

    public function test_add_league_to_event_with_empty_name_generates_sequenced_title(): void {
        $event = \Racketmanager\Domain\Competition\Event::from_object( (object) array(
            'id'             => 5,
            'name'           => 'Men Singles',
            'type'           => 'singles',
            'competition_id' => 1,
        ) );
        $this->event_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 5 )
            ->willReturn( $event );

        $this->league_repository->expects( self::once() )
            ->method( 'find_next_sequence_number' )
            ->with( 'Men Singles' )
            ->willReturn( 2 );

        $this->league_repository->expects( self::once() )
            ->method( 'save' )
            ->with( self::callback( static fn( $league ): bool => 'Men Singles 2' === $league->title && 5 === $league->event_id ) );

        $created = $this->service->add_league_to_event( 5 );
        self::assertSame( 5, $created->event_id );
        self::assertSame( 'Men Singles 2', $created->title );
        self::assertSame( $event, $created->event );
    }

    public function test_add_league_to_event_throws_when_event_not_found(): void {
        $this->event_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 999 )
            ->willReturn( null );

        $this->expectException( Event_Not_Found_Exception::class );
        $this->service->add_league_to_event( 999, 'Men Singles' );
    }

    public function test_add_league_to_event_with_real_league_repository(): void {
        global $wpdb;
        $real_repo = new \Racketmanager\Repositories\League_Repository();
        $event     = \Racketmanager\Domain\Competition\Event::from_object( (object) array(
            'id'             => 10,
            'name'           => 'Ladies Doubles',
            'type'           => 'doubles',
            'competition_id' => 2,
        ) );
        $this->event_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( $event );

        $service = new League_Service(
            $this->createMock( RacketManager::class ),
            $real_repo,
            $this->event_repository,
            $this->league_team_repository,
            $this->team_repository
        );

        $created = $service->add_league_to_event( 10, 'Ladies Doubles Premier' );
        self::assertSame( 10, $created->event_id );
        self::assertSame( 'Ladies Doubles Premier', $created->title );
        self::assertGreaterThan( 0, $created->id );
        self::assertSame( $wpdb->insert_id, $created->id );
        self::assertSame( $event, $created->event );
    }
}
