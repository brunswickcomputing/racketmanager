<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Admin\Commands;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Admin\Commands\Delete_League_Matches_Command;
use Racketmanager\Application\Admin\Commands\Delete_League_Teams_Command;
use Racketmanager\Application\Admin\Commands\League_Team_Handler;
use Racketmanager\Application\Admin\Commands\Moderate_Results_Checker_Command;
use Racketmanager\Application\Admin\Commands\Rank_League_Teams_Command;
use Racketmanager\Application\Admin\Commands\Results_Checker_Handler;
use Racketmanager\Application\Admin\Commands\Set_League_Match_Dates_Command;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Result\Results_Checker_Data;
use Racketmanager\Domain\Result\Results_Checker;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Schedule_Service;
use Racketmanager\Services\Competition\Promotion_Relegation_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Result\Results_Checker_Manager;
use Racketmanager\Services\Standings\Standings_Service;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Commands_Test extends TestCase {
    #[DataProvider( 'invalid_commands' )]
    public function test_commands_reject_invalid_selected_ids_before_execution( string $class, array $arguments ): void {
        $this->expectException( InvalidArgumentException::class );
        self::assertInstanceOf( $class, new $class( ...$arguments ) );
    }

    public static function invalid_commands(): array {
        return [
            'nested team' => [ Delete_League_Teams_Command::class, [ '2026', [ [ 10 ] ] ] ],
            'invalid team' => [ Delete_League_Teams_Command::class, [ '2026', [ '10oops' ] ] ],
            'invalid match' => [ Delete_League_Matches_Command::class, [ [ 'no-id' ] ] ],
            'invalid moderation id' => [ Moderate_Results_Checker_Command::class, [ 'approve', [ 0 ] ] ],
            'unknown moderation action' => [ Moderate_Results_Checker_Command::class, [ 'publish', [ 1 ] ] ],
            'empty moderation selection' => [ Moderate_Results_Checker_Command::class, [ 'approve', [] ] ],
        ];
    }

    public function test_direct_team_handler_uses_command_without_request_globals(): void {
        $league = $this->createStub( League::class );
        $service = $this->createMock( League_Service::class );
        $service->expects( self::once() )->method( 'delete_team' )->with( $league, 10, '2026' );
        $handler = new League_Team_Handler( $service, $this->createStub( Standings_Service::class ), $this->createStub( Team_Repository_Interface::class ) );
        $result = $handler->delete_teams( new Delete_League_Teams_Command( '2026', [ '10' ] ), $league );
        self::assertSame( '10 deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_match_dates_command_rejects_out_of_order_rounds(): void {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Match date for round 2 after next round date' );
        self::assertInstanceOf( Set_League_Match_Dates_Command::class, new Set_League_Match_Dates_Command( 3, 2026, [
            [ 'round' => '1', 'match_date' => '2026-06-08' ],
            [ 'round' => '2', 'match_date' => '2026-06-01' ],
        ] ) );
    }

    public function test_match_dates_command_exposes_only_validated_dates(): void {
        $command = new Set_League_Match_Dates_Command( 3, 2026, [
            [ 'round' => '1', 'match_date' => '2026-06-01' ],
            [ 'round' => '2', 'match_date' => '2026-06-08' ],
        ] );
        self::assertSame( [ '2026-06-01', '2026-06-08' ], $command->match_dates );
    }

    public function test_manual_standings_preserves_sparse_keys_decimals_and_nested_custom_values(): void {
        $league = $this->createStub( League::class );
        $standings = $this->createMock( Standings_Service::class );
        $points = [ 'points_plus' => [ 10 => '5.50' ], 'points_minus' => [ 10 => '2.25' ], 'add_points' => [ 10 => '-1' ] ];
        $matches = [ 'num_done_matches' => [ 10 => '3' ], 'num_won_matches' => [ 10 => '2' ], 'num_draw_matches' => [ 10 => '0' ], 'num_lost_matches' => [ 10 => '1' ] ];
        $custom = [ 10 => [ 'sets' => '4.0' ] ];
        $standings->expects( self::once() )->method( 'save_standings_manually' )->with( $league, [ 10 ], $points, $matches, $custom );
        $dispatcher = new League_Team_Action_Dispatcher( $this->createStub( League_Service::class ), $this->createStub( Action_Guard_Interface::class ), $standings, team_repository: $this->createStub( Team_Repository_Interface::class ) );
        $result = $dispatcher->handle( [], $points + $matches + [ 'updateLeague' => 'teams_manual', 'team_id' => [ 10 ], 'custom' => $custom, 'rating_points' => 'unused' ], $league );
        self::assertSame( 'Standings Table updated', $result->message );
    }

    public function test_result_updates_preserve_empty_scores_and_custom_data(): void {
        $league = $this->createStub( League::class );
        $manager = $this->createMock( Fixture_Result_Manager::class );
        $manager->expects( self::once() )->method( 'update_match_results' )
            ->with( $league, [ '7' ], [ 7 => '0.0' ], [ 7 => '' ], [ 7 => [ 'sets' => '2' ] ], '2026', false )->willReturn( 1 );
        $dispatcher = new League_Match_Action_Dispatcher( $this->createStub( Fixture_Repository_Interface::class ), $this->createStub( Fixture_Maintenance_Service::class ), $this->createStub( Action_Guard_Interface::class ), $manager, $this->createStub( Fixture_Service::class ), league_query_service: $this->createStub( League_Query_Service::class ) );
        $result = $dispatcher->handle( [], [ 'updateLeague' => 'results', 'matches' => [ '7' ], 'season' => '2026', 'home_points' => [ 7 => '0.0' ], 'away_points' => [ 7 => '' ], 'custom' => [ 7 => [ 'sets' => '2' ] ], 'myDatePicker' => 'unused' ], $league );
        self::assertSame( 'Updated Results of 1 matches', $result->message );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_ranking_column_lengths_are_validated_before_service_sort(): void {
        $this->expectException( InvalidArgumentException::class );
        self::assertInstanceOf( Rank_League_Teams_Command::class, new Rank_League_Teams_Command( 'ratings', [ 1, 2 ], null, [ 12 ], false ) );
    }

    #[DataProvider( 'ranking_modes' )]
    public function test_ranking_validates_only_fields_used_by_selected_mode( array $post, string $mode, array $expected ): void {
        $league = $this->createStub( League::class );
        $service = $this->createMock( League_Service::class );
        $service->expects( self::once() )->method( 'rank_teams' )
            ->with( $league, $mode, $expected, [ '10', '20' ] )->willReturn( true );
        $dispatcher = new League_Team_Action_Dispatcher( $service, $this->createStub( Action_Guard_Interface::class ), $this->createStub( Standings_Service::class ), team_repository: $this->createStub( Team_Repository_Interface::class ) );
        $result = $dispatcher->handle( [], $post + [ 'table_id' => [ '10', '20' ] ], $league );
        self::assertSame( 'Team ranking saved', $result->message );
    }

    public static function ranking_modes(): array {
        return [
            'random ignores other columns' => [ [ 'randomRanking' => 1, 'rank' => [ [ 1 ] ], 'rating_points' => [ [ 2 ] ], 'js-active' => [] ], 'random', [ 'js-active' => 0 ] ],
            'javascript manual ignores ranks and ratings' => [ [ 'saveRanking' => 1, 'js-active' => '1', 'rank' => [ [ 1 ] ], 'rating_points' => 'unused' ], 'manual', [ 'js-active' => 1 ] ],
            'manual wins over ratings' => [ [ 'saveRanking' => 1, 'ratingPointsRanking' => 1, 'rank' => [ '2', '1' ], 'rating_points' => 'unused' ], 'manual', [ 'rank' => [ '2', '1' ], 'js-active' => 0 ] ],
            'ratings ignores javascript flag' => [ [ 'ratingPointsRanking' => 1, 'rating_points' => [ '2.5', '1.0' ], 'rank' => 'unused', 'js-active' => [] ], 'ratings', [ 'rating_points' => [ '2.5', '1.0' ], 'js-active' => 0 ] ],
        ];
    }

    public function test_setup_ratings_preserves_post_source_and_ignores_rounds(): void {
        $service = $this->createMock( Competition_Service::class );
        $service->expects( self::once() )->method( 'calculate_team_ratings' )->with( 3, 2026 );
        $dispatcher = new League_Setup_Action_Dispatcher( $service, $this->createStub( Action_Guard_Interface::class ), $this->createStub( Event_Repository_Interface::class ) );
        $result = $dispatcher->handle( [ 'competition_id' => 99 ], [ 'rank' => 1, 'competition_id' => '3', 'season' => '2026', 'rounds' => 'ignored' ] );
        self::assertSame( 'League ratings set', $result->message );
    }

    public function test_setup_guard_precedes_validation_and_service_execution(): void {
        $service = $this->createMock( Competition_Service::class );
        $service->expects( self::never() )->method( 'update_seasons' );
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add_championship-fixtures', 'edit_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'Denied' ) );
        $dispatcher = new League_Setup_Action_Dispatcher( $service, $guard, $this->createStub( Event_Repository_Interface::class ) );
        $result = $dispatcher->handle( [ 'competition_id' => [] ], [ 'action' => 'dates', 'rounds' => new \stdClass() ] );
        self::assertSame( 'Denied', $result->message );
    }

    public function test_malformed_selected_team_is_rejected_before_any_team_is_deleted(): void {
        $service = $this->createMock( League_Service::class );
        $service->expects( self::never() )->method( 'delete_team' );
        $dispatcher = new League_Team_Action_Dispatcher( $service, $this->createStub( Action_Guard_Interface::class ), $this->createStub( Standings_Service::class ), team_repository: $this->createStub( Team_Repository_Interface::class ) );
        $result = $dispatcher->handle( [], [ 'doAction' => 1, 'action' => 'delete', 'team' => [ '10', [ '20' ] ] ], $this->createStub( League::class ) );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'Invalid field: team', $result->message );
    }

    public function test_match_delete_ignores_unrelated_edit_fields(): void {
        $maintenance = $this->createMock( Fixture_Maintenance_Service::class );
        $maintenance->expects( self::once() )->method( 'delete_fixture' )->with( 7 );
        $dispatcher = new League_Match_Action_Dispatcher( $this->createStub( Fixture_Repository_Interface::class ), $maintenance, $this->createStub( Action_Guard_Interface::class ), $this->createStub( Fixture_Result_Manager::class ), $this->createStub( Fixture_Service::class ), league_query_service: $this->createStub( League_Query_Service::class ) );
        $result = $dispatcher->handle( [], [ 'delMatches' => 1, 'match' => [ '7' ], 'season' => [], 'home_points' => [ new \stdClass() ] ], $this->createStub( League::class ) );
        self::assertSame( 'Match id 7 deleted', $result->message );
        self::assertSame( 'matches', $result->tab_override );
    }

    public function test_match_creation_sanitizes_once_and_ignores_edit_only_date_columns(): void {
        $service = $this->createMock( Fixture_Service::class );
        $service->expects( self::once() )->method( 'add_match' )->with( self::callback( static fn( object $fixture ): bool => "O'Brien" === $fixture->location && '2026-06-01 19:30:00' === $fixture->date ), self::isInstanceOf( League::class ) );
        $dispatcher = new League_Match_Action_Dispatcher( $this->createStub( Fixture_Repository_Interface::class ), $this->createStub( Fixture_Maintenance_Service::class ), $this->createStub( Action_Guard_Interface::class ), $this->createStub( Fixture_Result_Manager::class ), $service, league_query_service: $this->createStub( League_Query_Service::class ) );
        $result = $dispatcher->handle( [], [
            'updateLeague' => 'match', 'mode' => 'add', 'match' => [ 0 ], 'league_id' => '5', 'season' => '2026',
            'home_team' => [ '10' ], 'away_team' => [ '20' ], 'myDatePicker' => [ '2026-06-01' ],
            'begin_hour' => [ '19' ], 'begin_minutes' => [ '30' ], 'location' => [ "<b>O\\'Brien</b>" ],
            'year' => [ [ 'ignored' ] ],
        ], $this->createStub( League::class ) );
        self::assertSame( '1 Match added', $result->message );
    }

    public function test_constitution_generation_preserves_counts_in_flash_and_ignores_team_columns(): void {
        $event = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $schedule = $this->createMock( League_Schedule_Service::class );
        $counts = [ 'scheduled' => 2, 'skipped' => 1 ];
        $schedule->expects( self::once() )->method( 'generate_box_league_matches' )->with( $event )->willReturn( $counts );
        $dispatcher = new League_Constitution_Action_Dispatcher(
            $this->createStub( Action_Guard_Interface::class ), $this->createStub( League_Team_Repository_Interface::class ),
            $this->createStub( League_Service::class ), $schedule, $this->createStub( League_Repository_Interface::class ),
            $this->createStub( Event_Repository_Interface::class ), $this->createStub( Team_Repository_Interface::class ), $this->createStub( Promotion_Relegation_Service::class ),
        );
        $result = $dispatcher->handle( [ 'event_id' => '7' ], [ 'generate_matches' => 1, 'latest_season' => '2026', 'team_id' => 'unused', 'league_id' => [] ], $event );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( $counts, $result->flash_data );
        self::assertSame( 'Matches generated (1 league skipped)', $result->message );
    }

    public function test_direct_results_handler_preserves_partial_success(): void {
        $manager = $this->createMock( Results_Checker_Manager::class );
        $repository = $this->createMock( Results_Checker_Repository_Interface::class );
        $checker = new Results_Checker( new Results_Checker_Data( id: 7 ) );
        $repository->expects( self::exactly( 2 ) )->method( 'find_by_id' )->willReturnMap( [ [ 7, $checker ], [ 8, null ] ] );
        $manager->expects( self::once() )->method( 'approve' )->with( $checker );
        $result = ( new Results_Checker_Handler( $manager, $repository ) )->handle( new Moderate_Results_Checker_Command( 'approve', [ '7', '8' ] ) );
        self::assertSame( 'Result check(s) approved<br>Result check not found', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( 'resultschecker', $result->tab_override );
    }

    public function test_results_malformed_action_returns_error_without_repository_calls(): void {
        $repository = $this->createMock( Results_Checker_Repository_Interface::class );
        $repository->expects( self::never() )->method( 'find_by_id' );
        $dispatcher = new Results_Checker_Action_Dispatcher( $this->createStub( Results_Checker_Manager::class ), $repository, $this->createStub( Action_Guard_Interface::class ) );
        $result = $dispatcher->handle( [ 'doResultsChecker' => 1, 'action' => [], 'resultsChecker' => [ 7 ] ] );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertSame( 'resultschecker', $result->tab_override );
        self::assertSame( 'Invalid field: action', $result->message );
    }
}
