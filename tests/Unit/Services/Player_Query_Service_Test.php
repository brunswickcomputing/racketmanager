<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Statistics_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use RuntimeException;

final class Player_Query_Service_Test extends TestCase {
	private function make_player(): \Racketmanager\Domain\Player\Player {
		return Player_Factory::from_object(
			(object) array(
				'ID'           => '7',
				'display_name' => 'Query Player',
			)
		);
	}

	private function make_match( string $rubber_type, string $player_side, int $player_slot, array $sets ): object {
		$home_players = array(
			'1' => (object) array( 'display_name' => 'Home Player One' ),
			'2' => (object) array( 'display_name' => 'Home Player Two' ),
		);
		$away_players = array(
			'1' => (object) array( 'display_name' => 'Away Player One' ),
			'2' => (object) array( 'display_name' => 'Away Player Two' ),
		);
		$side_players = 'home' === $player_side ? $home_players : $away_players;
		$side_players[ (string) $player_slot ]->display_name = 'Query Player';
		$rubber = (object) array(
			'title'     => 'Rubber 1',
			'type'      => $rubber_type,
			'winner_id' => '10',
			'players'   => array(
				'home' => $home_players,
				'away' => $away_players,
			),
			'custom'    => array( 'sets' => $sets ),
		);

		return (object) array(
			'home_team' => '10',
			'away_team' => '20',
			'winner_id' => '10',
			'season'    => '2026',
			'league'    => (object) array(
				'title'       => 'Division 2',
				'num_rubbers' => 3,
				'event'       => (object) array(
					'age_limit'      => 'open',
					'reverse_rubbers' => false,
				),
			),
			'rubbers'   => array( $rubber ),
		);
	}

	private function get_matches_with_global_manager( \Racketmanager\Domain\Player\Player $player, array $matches ): array {
		$repository = $this->createMock( Fixture_Repository_Interface::class );
		$repository->expects( $this->once() )
			->method( 'find_player_matches' )
			->with(
				(int) $player->id,
				$this->callback(
					static fn( array $args ): bool => '2026' === $args['season'] && 'Y' === $args['status'] && array_key_exists( 'time', $args ) && array_key_exists( 'type', $args )
				)
			)
			->willReturn( $matches );

		$service = new Player_Query_Service( $this->make_statistics_repository(), null, $repository );
		return $service->get_matches( $player, null, '2026', 'all' );
	}

	private function make_statistics_repository( array $rows = array() ): Player_Statistics_Repository_Interface {
		$repository = $this->createStub( Player_Statistics_Repository_Interface::class );
		$repository->method( 'find_competitions_played' )->willReturn( $rows );
		$repository->method( 'find_team_stats' )->willReturn( $rows );
		$repository->method( 'find_player_stats' )->willReturn( $rows );

		return $repository;
	}

	public function test_get_stats_teams_returns_db_shaped_rows(): void {
		$rows = array(
			(object) array(
				'stat_type'   => 'teams',
				'season'      => '2024',
				'type'        => 'MD',
				'player_team' => 'home',
				'home_team'   => '10',
				'away_team'   => '20',
				'winner_id'   => '10',
				'loser_id'    => '20',
			),
		);
		$repository = $this->createMock( Player_Statistics_Repository_Interface::class );
		$repository->expects( $this->once() )
			->method( 'find_team_stats' )
			->with( 7, null )
			->willReturn( $rows );
		$service = new Player_Query_Service( $repository );

		$result = $service->get_stats_teams( $this->make_player() );

		$this->assertCount( 1, $result );
		$this->assertSame( 'teams', $result[0]->stat_type );
		$this->assertSame( '2024', $result[0]->season );
		$this->assertSame( 'MD', $result[0]->type );
		$this->assertSame( 'home', $result[0]->player_team );
		$this->assertSame( '10', $result[0]->home_team );
		$this->assertSame( '10', $result[0]->winner_id );
	}

	public function test_get_stats_players_returns_db_shaped_rows(): void {
		$rows = array(
			(object) array(
				'stat_type' => 'players',
				'season'    => '2025',
				'type'      => 'MS',
				'team_id'   => '5',
				'home_team' => '5',
				'away_team' => '8',
				'winner_id' => '5',
				'loser_id'  => '8',
			),
		);
		$repository = $this->createMock( Player_Statistics_Repository_Interface::class );
		$repository->expects( $this->once() )
			->method( 'find_player_stats' )
			->with( 7, '2025' )
			->willReturn( $rows );
		$service = new Player_Query_Service( $repository );

		$result = $service->get_stats_players( $this->make_player(), array( 'season' => 2025 ) );

		$this->assertCount( 1, $result );
		$this->assertSame( 'players', $result[0]->stat_type );
		$this->assertSame( '2025', $result[0]->season );
		$this->assertSame( '5', $result[0]->team_id );
		$this->assertSame( '5', $result[0]->winner_id );
	}

	public function test_get_competitions_returns_db_shaped_rows(): void {
		$rows = array(
			(object) array(
				'id'     => '3',
				'name'   => 'Summer League',
				'season' => '2026',
			),
		);
		$repository = $this->createMock( Competition_Repository_Interface::class );
		$repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 3 )
			->willReturn( null );

		$statistics = $this->createMock( Player_Statistics_Repository_Interface::class );
		$statistics->expects( $this->once() )
			->method( 'find_competitions_played' )
			->with( 7, null, null )
			->willReturn( $rows );

		$service = new Player_Query_Service( $statistics, $repository );
		$result  = $service->get_competitions( $this->make_player() );

		$this->assertCount( 1, $result );
		$this->assertSame( '3', $result[0]->id );
		$this->assertSame( 'Summer League', $result[0]->name );
		$this->assertSame( '2026', $result[0]->season );
	}

	public function test_get_competitions_uses_season_collection_for_dates(): void {
		$player = Player_Factory::from_array(
			array(
				'ID'           => '43107',
				'display_name' => 'Query Player',
			)
		);
		$rows = array( (object) array( 'id' => '31', 'name' => 'Summer League', 'season' => '2026' ) );
		$competition = Competition_Test_Factory::create(
			id: 31,
			seasons: Competition_Test_Factory::season_map( '2026', array( 'date_start' => '2026-01-10', 'date_end' => '2026-12-20' ) )
		);
		$repository = $this->createMock( Competition_Repository_Interface::class );
		$repository->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 31 )
			->willReturn( $competition );

		$statistics = $this->createMock( Player_Statistics_Repository_Interface::class );
		$statistics->expects( $this->once() )
			->method( 'find_competitions_played' )
			->with( 43107, 'MS', '2026' )
			->willReturn( $rows );

		$service = new Player_Query_Service( $statistics, $repository );
		$result  = $service->get_competitions( $player, array( 'type' => 'MS', 'season' => '2026' ) );

		$this->assertCount( 1, $result );
		$this->assertSame( 2026, $result[0]->season );
		$this->assertSame( '2026-01-10', $result[0]->date_start );
		$this->assertSame( '2026-12-20', $result[0]->date_end );
	}

	public function test_get_competitions_uses_competition_service_when_supplied(): void {
		$rows        = array( (object) array( 'id' => '31', 'name' => 'Summer League', 'season' => '2026' ) );
		$competition = Competition_Test_Factory::create(
			id: 31,
			seasons: Competition_Test_Factory::season_map( '2026', array( 'date_start' => '2026-01-10', 'date_end' => '2026-12-20' ) )
		);
		$competition_service = $this->createMock( Competition_Service::class );
		$competition_service->expects( $this->once() )
			->method( 'get_competition' )
			->with( 31 )
			->willReturn( $competition );
		$repository = $this->createMock( Competition_Repository_Interface::class );
		$repository->expects( $this->never() )->method( 'find_by_id' );

		$service = new Player_Query_Service( $this->make_statistics_repository( $rows ), $repository );
		$result  = $service->get_competitions( $this->make_player(), array(), $competition_service );

		$this->assertCount( 1, $result );
		$this->assertSame( $competition, $result[0] );
		$this->assertSame( '2026-01-10', $result[0]->date_start );
	}

	public function test_get_competitions_drops_competitions_with_unknown_season(): void {
		$rows        = array(
			(object) array( 'id' => '31', 'name' => 'Summer League', 'season' => '2019' ),
			(object) array( 'id' => '32', 'name' => 'Winter League', 'season' => '2026' ),
		);
		$unknown     = Competition_Test_Factory::create( id: 31, seasons: Competition_Test_Factory::season_map( '2026', array() ) );
		$known       = Competition_Test_Factory::create( id: 32, seasons: Competition_Test_Factory::season_map( '2026', array( 'date_start' => '2026-01-10' ) ) );
		$repository  = $this->createStub( Competition_Repository_Interface::class );
		$repository->method( 'find_by_id' )->willReturnMap( array( array( 31, $unknown ), array( 32, $known ) ) );

		$service = new Player_Query_Service( $this->make_statistics_repository( $rows ), $repository );
		$result  = $service->get_competitions( $this->make_player() );

		$this->assertCount( 1, $result );
		$this->assertSame( $known, $result[0] );
		$this->assertSame( 2026, $result[0]->season );
	}

	public function test_empty_and_false_filters_are_passed_as_null(): void {
		$statistics = $this->createMock( Player_Statistics_Repository_Interface::class );
		$statistics->expects( $this->once() )
			->method( 'find_competitions_played' )
			->with( 7, null, null )
			->willReturn( array() );
		$statistics->expects( $this->once() )
			->method( 'find_team_stats' )
			->with( 7, null )
			->willReturn( array() );
		$statistics->expects( $this->once() )
			->method( 'find_player_stats' )
			->with( 7, null )
			->willReturn( array() );

		$service = new Player_Query_Service( $statistics, $this->createStub( Competition_Repository_Interface::class ) );

		$this->assertSame( array(), $service->get_competitions( $this->make_player(), array( 'type' => '', 'season' => false ) ) );
		$this->assertSame( array(), $service->get_stats_teams( $this->make_player(), array( 'season' => '' ) ) );
		$this->assertSame( array(), $service->get_stats_players( $this->make_player(), array( 'season' => null ) ) );
	}

	public function test_get_matches_aggregates_singles_for_home_player(): void {
		$player = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );
		$match  = $this->make_match(
			'MS',
			'home',
			1,
			array(
				array( 'player1' => 6, 'player2' => 3 ),
				array( 'player1' => 4, 'player2' => 6 ),
			)
		);

		$result = $this->get_matches_with_global_manager( $player, array( $match ) );

		$this->assertSame( array( $match ), $result );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['played']['winner'] );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['sets']['winner'] );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['sets']['loser'] );
		$this->assertSame( 10, $player->statistics['2-3-open-true']['Rubber 1']['games']['winner'] );
		$this->assertSame( 9, $player->statistics['2-3-open-true']['Rubber 1']['games']['loser'] );
	}

	public function test_get_matches_aggregates_doubles_for_away_player(): void {
		$player = Player_Factory::from_array( array( 'ID' => '8', 'display_name' => 'Query Player' ) );
		$match  = $this->make_match(
			'MD',
			'away',
			2,
			array(
				array( 'player1' => 6, 'player2' => 4 ),
				array( 'player1' => 4, 'player2' => 6 ),
			)
		);

		$result = $this->get_matches_with_global_manager( $player, array( $match ) );

		$this->assertSame( array( $match ), $result );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['played']['loser'] );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['sets']['winner'] );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['sets']['loser'] );
		$this->assertSame( 10, $player->statistics['2-3-open-true']['Rubber 1']['games']['winner'] );
		$this->assertSame( 10, $player->statistics['2-3-open-true']['Rubber 1']['games']['loser'] );
	}

	public function test_get_matches_groups_competition_matches_by_league(): void {
		$player  = Player_Factory::from_array( array( 'ID' => '9', 'display_name' => 'Query Player' ) );
		$match   = $this->make_match( 'MS', 'home', 1, array() );
		$competition = Competition_Test_Factory::create( id: 31 );
		$repository  = $this->createMock( Competition_Repository_Interface::class );
		$repository->expects( $this->once() )
			->method( 'find_matches' )
			->with(
				31,
				array(
					'season'  => '2026',
					'player'  => $player->id,
					'orderby' => array(
						'date'      => 'ASC',
						'league_id' => 'DESC',
					),
				)
			)
			->willReturn( array( $match ) );
		$service = new Player_Query_Service( $this->make_statistics_repository(), $repository );

		$result = $service->get_matches( $player, $competition, '2026', 'competition' );

		$this->assertSame( array( $match ), $result['Division 2']['matches'] );
		$this->assertSame( '2026', $result['Division 2']['league']->season );
		$this->assertSame( 'Division 2', $player->matches['Division 2']['league']->title );
	}

	public function test_get_stats_teams_throws_when_statistics_repository_missing(): void {
		$service = new Player_Query_Service();
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Player_Query_Service requires a Player_Statistics_Repository_Interface.' );
		$service->get_stats_teams( $this->make_player() );
	}

	public function test_get_matches_all_throws_without_fixture_repository(): void {
		$service = new Player_Query_Service( $this->make_statistics_repository() );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Player_Query_Service requires a Fixture_Repository_Interface.' );
		$service->get_matches( $this->make_player(), null, '2026', 'all' );
	}

	public function test_get_matches_competition_throws_without_competition_repository(): void {
		$service = new Player_Query_Service( $this->make_statistics_repository() );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Player_Query_Service requires a Competition_Repository_Interface.' );
		$service->get_matches( $this->make_player(), Competition_Test_Factory::create( id: 31 ), '2026', 'competition' );
	}

	public function test_get_matches_unknown_source_returns_empty(): void {
		$service = new Player_Query_Service( $this->make_statistics_repository() );

		$this->assertSame( array(), $service->get_matches( $this->make_player(), null, '2026', 'unknown' ) );
	}
}
