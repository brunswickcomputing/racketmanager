<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Statistics_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
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

	private function make_rubber( string $rubber_type, string $player_side, int $player_slot, array $sets ): object {
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

		return (object) array(
			'title'     => 'Rubber 1',
			'type'      => $rubber_type,
			'winner_id' => '10',
			'players'   => array(
				'home' => $home_players,
				'away' => $away_players,
			),
			'custom'    => array( 'sets' => $sets ),
		);
	}

	private function make_match( string $rubber_type, string $player_side, int $player_slot, array $sets ): object {
		$rubber = $this->make_rubber( $rubber_type, $player_side, $player_slot, $sets );

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

	public function test_get_matches_competition_hydrates_rubbers_when_not_attached_and_populates_statistics(): void {
		$player      = Player_Factory::from_array( array( 'ID' => '9', 'display_name' => 'Query Player' ) );
		$competition = Competition_Test_Factory::create( id: 31 );
		$rubber      = $this->make_rubber(
			'MS',
			'home',
			1,
			array(
				array( 'player1' => 6, 'player2' => 3 ),
				array( 'player1' => 6, 'player2' => 4 ),
			)
		);

		$fixture = new Fixture();
		$fixture->set_id( 101 );
		$fixture->home_team = '10';
		$fixture->away_team = '20';
		$fixture->winner_id = 10;
		$fixture->season    = '2026';
		$fixture->league    = (object) array(
			'title'       => 'Division 2',
			'num_rubbers' => 3,
			'event'       => (object) array(
				'age_limit'       => 'open',
				'reverse_rubbers' => false,
			),
		);
		$this->assertNull( $fixture->rubbers );

		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
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
			->willReturn( array( $fixture ) );

		$rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
		$rubber_repository->expects( $this->once() )
			->method( 'find_by_fixture_id' )
			->with( 101, 9 )
			->willReturn( array( $rubber ) );

		$service = new Player_Query_Service(
			$this->make_statistics_repository(),
			$competition_repository,
			null,
			null,
			$rubber_repository
		);

		$result = $service->get_matches( $player, $competition, '2026', 'competition' );

		$this->assertSame( array( $fixture ), $result['Division 2']['matches'] );
		$this->assertSame( array( $rubber ), $fixture->get_rubbers() );
		$this->assertSame( 1, $player->statistics['Rubber 1']['played']['winner'] );
		$this->assertSame( 2, $player->statistics['Rubber 1']['sets']['winner'] );
		$this->assertSame( 12, $player->statistics['Rubber 1']['games']['winner'] );
		$this->assertSame( 7, $player->statistics['Rubber 1']['games']['loser'] );
	}

	public function test_get_matches_competition_avoids_redundant_rubber_fetching_when_rubbers_already_attached(): void {
		$player      = Player_Factory::from_array( array( 'ID' => '9', 'display_name' => 'Query Player' ) );
		$competition = Competition_Test_Factory::create( id: 31 );
		$rubber      = $this->make_rubber( 'MS', 'home', 1, array( array( 'player1' => 6, 'player2' => 2 ) ) );

		$fixture = new Fixture();
		$fixture->set_id( 102 );
		$fixture->home_team = '10';
		$fixture->away_team = '20';
		$fixture->winner_id = 10;
		$fixture->season    = '2026';
		$fixture->league    = (object) array(
			'title'       => 'Division 2',
			'num_rubbers' => 1,
			'event'       => (object) array(
				'age_limit'       => 'open',
				'reverse_rubbers' => false,
			),
		);
		$fixture->set_rubbers( array( $rubber ) );

		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_matches' )
			->willReturn( array( $fixture ) );

		$rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
		$rubber_repository->expects( $this->never() )
			->method( 'find_by_fixture_id' );

		$service = new Player_Query_Service(
			$this->make_statistics_repository(),
			$competition_repository,
			null,
			null,
			$rubber_repository
		);

		$result = $service->get_matches( $player, $competition, '2026', 'competition' );

		$this->assertSame( array( $fixture ), $result['Division 2']['matches'] );
		$this->assertSame( 1, $player->statistics['Rubber 1']['played']['winner'] );
	}

	public function test_get_matches_competition_avoids_redundant_rubber_fetching_when_rubbers_explicitly_empty(): void {
		$player      = Player_Factory::from_array( array( 'ID' => '9', 'display_name' => 'Query Player' ) );
		$competition = Competition_Test_Factory::create( id: 31 );

		$fixture = new Fixture();
		$fixture->set_id( 103 );
		$fixture->home_team = '10';
		$fixture->away_team = '20';
		$fixture->winner_id = 10;
		$fixture->season    = '2026';
		$fixture->league    = (object) array(
			'title'       => 'Division 2',
			'num_rubbers' => 1,
			'event'       => (object) array(
				'age_limit'       => 'open',
				'reverse_rubbers' => false,
			),
		);
		$fixture->set_rubbers( array() );

		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_matches' )
			->willReturn( array( $fixture ) );

		$rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
		$rubber_repository->expects( $this->never() )
			->method( 'find_by_fixture_id' );

		$service = new Player_Query_Service(
			$this->make_statistics_repository(),
			$competition_repository,
			null,
			null,
			$rubber_repository
		);

		$result = $service->get_matches( $player, $competition, '2026', 'competition' );

		$this->assertSame( array( $fixture ), $result['Division 2']['matches'] );
		$this->assertSame( array(), $player->statistics );
	}

	public function test_get_matches_competition_without_rubber_repository_safely_preserves_matches_when_rubbers_uninitialized(): void {
		$player      = Player_Factory::from_array( array( 'ID' => '9', 'display_name' => 'Query Player' ) );
		$competition = Competition_Test_Factory::create( id: 31 );

		$fixture = new Fixture();
		$fixture->set_id( 104 );
		$fixture->home_team = '10';
		$fixture->away_team = '20';
		$fixture->winner_id = 10;
		$fixture->season    = '2026';
		$fixture->league    = (object) array(
			'title'       => 'Division 2',
			'num_rubbers' => 1,
			'event'       => (object) array(
				'age_limit'       => 'open',
				'reverse_rubbers' => false,
			),
		);

		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_matches' )
			->willReturn( array( $fixture ) );

		$service = new Player_Query_Service(
			$this->make_statistics_repository(),
			$competition_repository,
			null,
			null,
			null
		);

		$result = $service->get_matches( $player, $competition, '2026', 'competition' );

		$this->assertSame( array( $fixture ), $result['Division 2']['matches'] );
		$this->assertNull( $fixture->rubbers );
		$this->assertSame( array(), $player->statistics );
	}

	/**
	 * Verifies that get_matches() delegates player filtering to Competition_Repository::find_matches()
	 * with 'player' => 7, groups the returned matches by league, and attaches player rubbers and statistics.
	 * (Note: Exclusion of other players' matches occurs at the repository/database boundary, while the service
	 * groups and hydrates whatever matches the repository returns.)
	 */
	public function test_get_matches_competition_delegates_player_filter_to_repository_groups_matches_and_attaches_rubbers(): void {
		$player      = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );
		$competition = Competition_Test_Factory::create( id: 10 );

		// Fixture 101 in Premier Division: player 7 participated in rubber 1.
		$fixture_101 = new Fixture();
		$fixture_101->set_id( 101 );
		$fixture_101->home_team = '10';
		$fixture_101->away_team = '20';
		$fixture_101->winner_id = 10;
		$fixture_101->season    = '2026';
		$fixture_101->league    = (object) array(
			'title'       => 'Premier Division',
			'num_rubbers' => 2,
			'event'       => (object) array(
				'age_limit'       => 'open',
				'reverse_rubbers' => false,
			),
		);

		// Fixture 201 in Division 1: player 7 participated in rubber 1.
		$fixture_201 = new Fixture();
		$fixture_201->set_id( 201 );
		$fixture_201->home_team = '30';
		$fixture_201->away_team = '40';
		$fixture_201->winner_id = 40;
		$fixture_201->season    = '2026';
		$fixture_201->league    = (object) array(
			'title'       => 'Division 1',
			'num_rubbers' => 2,
			'event'       => (object) array(
				'age_limit'       => 'open',
				'reverse_rubbers' => false,
			),
		);

		// Rubber for Fixture 101: MD won by home team (Query Player is home #1).
		$rubber_101 = $this->make_rubber(
			'MD',
			'home',
			1,
			array(
				array( 'player1' => 6, 'player2' => 2 ),
				array( 'player1' => 6, 'player2' => 3 ),
			)
		);
		$rubber_101->title = 'Men Doubles 1';

		// Rubber for Fixture 201: XD won by away team (Query Player is away #1).
		$rubber_201 = $this->make_rubber(
			'XD',
			'away',
			1,
			array(
				array( 'player1' => 3, 'player2' => 6 ),
				array( 'player1' => 4, 'player2' => 6 ),
			)
		);
		$rubber_201->title     = 'Mixed Doubles 1';
		$rubber_201->winner_id = '40';

		// The service delegates player filtering to Competition_Repository with 'player' => 7.
		// The mock returns the pre-filtered fixtures (101 and 201) supplied by the repository.
		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_matches' )
			->with(
				10,
				$this->callback(
					static fn( array $args ): bool =>
						'2026' === $args['season']
						&& 7 === (int) $args['player']
						&& array( 'date' => 'ASC', 'league_id' => 'DESC' ) === $args['orderby']
				)
			)
			->willReturn( array( $fixture_101, $fixture_201 ) );

		$rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
		$rubber_repository->expects( $this->exactly( 2 ) )
			->method( 'find_by_fixture_id' )
			->willReturnCallback(
				static fn( int $fixture_id, int $player_id ): array => match ( array( $fixture_id, $player_id ) ) {
					array( 101, 7 ) => array( $rubber_101 ),
					array( 201, 7 ) => array( $rubber_201 ),
					default         => array(),
				}
			);

		$service = new Player_Query_Service(
			$this->make_statistics_repository(),
			$competition_repository,
			null,
			null,
			$rubber_repository
		);

		$result = $service->get_matches( $player, $competition, '2026', 'competition' );

		// Verifies the service groups the repository's filtered match inputs by league.
		$this->assertSame( array( 'Premier Division', 'Division 1' ), array_keys( $result ) );
		$this->assertArrayNotHasKey( 'Division 2', $result );

		// Verifies each league contains the matches returned by the repository for that league.
		$this->assertSame( array( $fixture_101 ), $result['Premier Division']['matches'] );
		$this->assertSame( array( $fixture_201 ), $result['Division 1']['matches'] );

		// Verifies rubbers are attached onto the returned fixtures.
		$this->assertSame( array( $rubber_101 ), $fixture_101->get_rubbers() );
		$this->assertSame( array( $rubber_201 ), $fixture_201->get_rubbers() );

		// Verifies statistics are compiled for the player's rubbers only.
		$this->assertSame( 1, $player->statistics['Men Doubles 1']['played']['winner'] );
		$this->assertSame( 2, $player->statistics['Men Doubles 1']['sets']['winner'] );
		$this->assertSame( 12, $player->statistics['Men Doubles 1']['games']['winner'] );
		$this->assertSame( 5, $player->statistics['Men Doubles 1']['games']['loser'] );

		$this->assertSame( 1, $player->statistics['Mixed Doubles 1']['played']['winner'] );
		$this->assertSame( 2, $player->statistics['Mixed Doubles 1']['sets']['winner'] );
		$this->assertSame( 12, $player->statistics['Mixed Doubles 1']['games']['winner'] );
		$this->assertSame( 7, $player->statistics['Mixed Doubles 1']['games']['loser'] );
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

	private function league_query_service_expecting_player_matches( object $league, int $player_id ): League_Query_Service {
		$league_query_service = $this->createMock( League_Query_Service::class );
		$league_query_service->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static fn( array $args ): bool => '2026' === $args['season'] && $player_id === $args['player'] && false === $args['match_day'] && 'all' === $args['final']
				)
			)
			->willReturn( array() );

		return $league_query_service;
	}

	public function test_get_matches_league_uses_injected_league_query_service_instance(): void {
		$player  = $this->make_player();
		$league  = $this->createStub( League::class );
		$service = new Player_Query_Service(
			$this->make_statistics_repository(),
			null,
			null,
			null,
			null,
			null,
			$this->league_query_service_expecting_player_matches( $league, (int) $player->id )
		);

		$this->assertSame( array(), $service->get_matches( $player, $league, '2026', 'league' ) );
	}

	public function test_get_matches_league_resolves_lazy_league_query_service_once(): void {
		$player      = $this->make_player();
		$league      = $this->createStub( League::class );
		$resolutions = 0;
		$resolved    = $this->createMock( League_Query_Service::class );
		$resolved->expects( $this->exactly( 2 ) )->method( 'get_matches' )->with( $league, $this->isArray() )->willReturn( array() );

		$service = new Player_Query_Service(
			$this->make_statistics_repository(),
			null,
			null,
			null,
			null,
			null,
			function () use ( &$resolutions, $resolved ): League_Query_Service {
				++$resolutions;
				return $resolved;
			}
		);

		$this->assertSame( 0, $resolutions );
		$service->get_matches( $player, $league, '2026', 'league' );
		$service->get_matches( $player, $league, '2026', 'league' );
		$this->assertSame( 1, $resolutions, 'closure must be resolved once and cached' );
	}

	public function test_get_matches_league_throws_without_league_query_service(): void {
		$service = new Player_Query_Service( $this->make_statistics_repository() );
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'Player_Query_Service requires a League_Query_Service.' );
		$service->get_matches( $this->make_player(), $this->createStub( League::class ), '2026', 'league' );
	}

	public function test_get_matches_league_without_grouping_does_not_need_league_query_service(): void {
		$service = new Player_Query_Service( $this->make_statistics_repository() );

		$this->assertSame( array(), $service->get_matches( $this->make_player(), null, '2026', 'league' ) );
	}

	public function test_get_matches_legacy_null_outcome_quirk_when_no_winner_and_unmatched_player(): void {
		$player = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );
		$rubber = (object) array(
			'title'     => 'Rubber 1',
			'type'      => 'MD',
			'winner_id' => null,
			'players'   => array(
				'home' => array(
					'1' => (object) array( 'display_name' => 'Home Player One' ),
					'2' => (object) array( 'display_name' => 'Home Player Two' ),
				),
				'away' => array(
					'1' => (object) array( 'display_name' => 'Away Player One' ),
					'2' => (object) array( 'display_name' => 'Away Player Two' ),
				),
			),
			'custom'    => array(
				'sets' => array(
					array( 'player1' => '6', 'player2' => '4' ),
				),
			),
		);
		$match  = (object) array(
			'home_team' => '10',
			'away_team' => '20',
			'winner_id' => null,
			'season'    => '2026',
			'league'    => (object) array(
				'title'       => 'Division 2',
				'num_rubbers' => 3,
				'event'       => (object) array(
					'age_limit'       => 'open',
					'reverse_rubbers' => false,
				),
			),
			'rubbers'   => array( $rubber ),
		);

		$result = $this->get_matches_with_global_manager( $player, array( $match ) );

		$this->assertSame( array( $match ), $result );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['played']['winner'] );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['sets']['loser'] );
		$this->assertSame( 10, $player->statistics['2-3-open-true']['Rubber 1']['games']['loser'] );
		$this->assertArrayNotHasKey( 'winner', $player->statistics['2-3-open-true']['Rubber 1']['games'] ?? array() );
	}

	public function test_get_matches_unmatched_player_with_winner_evaluates_to_draw(): void {
		$player = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );
		$rubber = (object) array(
			'title'     => 'Rubber 1',
			'type'      => 'MD',
			'winner_id' => '10',
			'players'   => array(
				'home' => array(
					'1' => (object) array( 'display_name' => 'Home Player One' ),
					'2' => (object) array( 'display_name' => 'Home Player Two' ),
				),
				'away' => array(
					'1' => (object) array( 'display_name' => 'Away Player One' ),
					'2' => (object) array( 'display_name' => 'Away Player Two' ),
				),
			),
			'custom'    => array(
				'sets' => array(),
			),
		);
		$match  = (object) array(
			'home_team' => '10',
			'away_team' => '20',
			'winner_id' => '10',
			'season'    => '2026',
			'league'    => (object) array(
				'title'       => 'Division 2',
				'num_rubbers' => 3,
				'event'       => (object) array(
					'age_limit'       => 'open',
					'reverse_rubbers' => false,
				),
			),
			'rubbers'   => array( $rubber ),
		);

		$result = $this->get_matches_with_global_manager( $player, array( $match ) );

		$this->assertSame( array( $match ), $result );
		$this->assertSame( 1, $player->statistics['2-3-open-true']['Rubber 1']['played']['draw'] );
	}

	public function test_get_matches_tolerates_loose_types_for_titles_sets_and_rubbers(): void {
		$player = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );

		$rubber_int = (object) array(
			'title'     => 123,
			'type'      => 'MS',
			'winner_id' => '10',
			'players'   => array(
				'home' => array( '1' => (object) array( 'display_name' => 'Query Player' ) ),
				'away' => array( '1' => (object) array( 'display_name' => 'Away Player' ) ),
			),
			'custom'    => array(
				'sets' => array(
					array( 'player1' => '6', 'player2' => '2' ),
					'not-an-array-set',
					array( 'player1' => '', 'player2' => '6' ),
				),
			),
		);

		$rubber_null = (object) array(
			'title'     => null,
			'type'      => 'MS',
			'winner_id' => '10',
			'players'   => array(
				'home' => array( '1' => (object) array( 'display_name' => 'Query Player' ) ),
				'away' => array( '1' => (object) array( 'display_name' => 'Away Player' ) ),
			),
			'custom'    => array(
				'sets' => null,
			),
		);

		$match = (object) array(
			'home_team' => '10',
			'away_team' => '20',
			'winner_id' => '10',
			'season'    => '2026',
			'league'    => (object) array(
				'title'       => 'Division 1',
				'num_rubbers' => 2,
				'event'       => (object) array(
					'age_limit'       => 'open',
					'reverse_rubbers' => false,
				),
			),
			'rubbers'   => array(
				'scalar-rubber-entry',
				$rubber_int,
				$rubber_null,
			),
		);

		$result = $this->get_matches_with_global_manager( $player, array( $match ) );

		$this->assertSame( array( $match ), $result );
		$stats = $player->statistics['1-2-open-true'];

		$this->assertSame( 1, $stats[123]['played']['winner'] );
		$this->assertSame( 1, $stats[123]['sets']['winner'] );
		$this->assertSame( 6, $stats[123]['games']['winner'] );
		$this->assertSame( 2, $stats[123]['games']['loser'] );

		// Legacy behavior: both $rubber_null and scalar rubber entry evaluate to '' title and 'winner' played status.
		$this->assertSame( 2, $stats['']['played']['winner'] );
	}

	public function test_get_matches_accumulates_statistics_with_set_score_objects(): void {
		$player = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );

		$rubber = (object) array(
			'title'     => '1st Mens Singles',
			'type'      => 'MS',
			'winner_id' => '10',
			'players'   => array(
				'home' => array( '1' => (object) array( 'display_name' => 'Query Player' ) ),
				'away' => array( '1' => (object) array( 'display_name' => 'Away Player' ) ),
			),
			'custom'    => array(
				'sets' => array(
					new Set_Score( 6, 3 ),
					new Set_Score( 4, 6 ),
					new Set_Score( 7, 5 ),
				),
			),
		);

		$match = (object) array(
			'home_team' => '10',
			'away_team' => '20',
			'winner_id' => '10',
			'season'    => '2026',
			'league'    => (object) array(
				'title'       => 'Division 1',
				'num_rubbers' => 2,
				'event'       => (object) array(
					'age_limit'       => 'open',
					'reverse_rubbers' => false,
				),
			),
			'rubbers'   => array( $rubber ),
		);

		$result = $this->get_matches_with_global_manager( $player, array( $match ) );

		$this->assertSame( array( $match ), $result );
		$stats = $player->statistics['1-2-open-true']['1st Mens Singles'];

		$this->assertSame( 1, $stats['played']['winner'] );
		$this->assertSame( 2, $stats['sets']['winner'] );
		$this->assertSame( 1, $stats['sets']['loser'] );
		$this->assertSame( 17, $stats['games']['winner'] );
		$this->assertSame( 14, $stats['games']['loser'] );
	}

	public function test_get_matches_competition_resolves_league_grouping_fallbacks(): void {
		$player      = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );
		$competition = Competition_Test_Factory::create( id: 10 );

		$match1 = (object) array(
			'id'        => 1,
			'home_team' => '1',
			'away_team' => '2',
			'season'    => '2026',
			'league'    => (object) array( 'title' => 'Title League' ),
			'rubbers'   => array(),
		);

		$league_with_get_name = new class() {
			public ?string $season = null;

			public function get_name(): string {
				return 'Name League';
			}
		};
		$match2 = (object) array(
			'id'        => 2,
			'home_team' => '1',
			'away_team' => '2',
			'season'    => '2026',
			'league'    => $league_with_get_name,
			'rubbers'   => array(),
		);

		$match3 = new class() {
			public int $id = 3;
			public string $home_team = '1';
			public string $away_team = '2';
			public string $season = '2026';
			public object $league;
			public array $rubbers = array();

			public function __construct() {
				$this->league = (object) array( 'title' => 'Fallback Property League' );
			}

			public function get_league(): ?object {
				return null;
			}
		};

		$match4 = (object) array(
			'id'        => 4,
			'home_team' => '1',
			'away_team' => '2',
			'season'    => '2026',
			'league'    => (object) array( 'title' => 42 ),
			'rubbers'   => array(),
		);

		$competition_repository = $this->createMock( Competition_Repository_Interface::class );
		$competition_repository->expects( $this->once() )
			->method( 'find_matches' )
			->willReturn( array( $match1, $match2, $match3, $match4 ) );

		$service = new Player_Query_Service(
			$this->make_statistics_repository(),
			$competition_repository
		);

		$result = $service->get_matches( $player, $competition, '2026', 'competition' );

		$this->assertArrayHasKey( 'Title League', $result );
		$this->assertSame( array( $match1 ), $result['Title League']['matches'] );

		$this->assertArrayHasKey( 'Name League', $result );
		$this->assertSame( array( $match2 ), $result['Name League']['matches'] );

		$this->assertArrayHasKey( 'Fallback Property League', $result );
		$this->assertSame( array( $match3 ), $result['Fallback Property League']['matches'] );

		$this->assertArrayHasKey( 42, $result );
		$this->assertSame( array( $match4 ), $result[42]['matches'] );
	}

	public function test_get_matches_all_keys_statistics_by_league_ref_across_multiple_matches(): void {
		$player = Player_Factory::from_array( array( 'ID' => '7', 'display_name' => 'Query Player' ) );

		$match1 = (object) array(
			'home_team' => '10',
			'away_team' => '20',
			'winner_id' => '10',
			'season'    => '2026',
			'league'    => (object) array(
				'title'       => 'Division 1',
				'num_rubbers' => 2,
				'event'       => (object) array(
					'age_limit'       => 'open',
					'reverse_rubbers' => false,
				),
			),
			'rubbers'   => array(
				(object) array(
					'title'     => 'Rubber D1',
					'type'      => 'MS',
					'winner_id' => '10',
					'players'   => array(
						'home' => array( '1' => (object) array( 'display_name' => 'Query Player' ) ),
						'away' => array( '1' => (object) array( 'display_name' => 'Opponent' ) ),
					),
					'custom'    => array(
						'sets' => array( array( 'player1' => '6', 'player2' => '4' ) ),
					),
				),
			),
		);

		$match2 = (object) array(
			'home_team' => '10',
			'away_team' => '20',
			'winner_id' => '10',
			'season'    => '2026',
			'league'    => (object) array(
				'title'       => 'Division 3',
				'num_rubbers' => 4,
				'event'       => (object) array(
					'age_limit'       => '45',
					'reverse_rubbers' => true,
				),
			),
			'rubbers'   => array(
				(object) array(
					'title'     => 'Rubber D3',
					'type'      => 'MS',
					'winner_id' => '10',
					'players'   => array(
						'home' => array( '1' => (object) array( 'display_name' => 'Query Player' ) ),
						'away' => array( '1' => (object) array( 'display_name' => 'Opponent' ) ),
					),
					'custom'    => array(
						'sets' => array( array( 'player1' => '6', 'player2' => '2' ) ),
					),
				),
			),
		);

		$result = $this->get_matches_with_global_manager( $player, array( $match1, $match2 ) );

		$this->assertSame( array( $match1, $match2 ), $result );
		$this->assertArrayHasKey( '1-2-open-true', $player->statistics );
		$this->assertArrayHasKey( '3-4-45-false', $player->statistics );
		$this->assertSame( 1, $player->statistics['1-2-open-true']['Rubber D1']['played']['winner'] );
		$this->assertSame( 1, $player->statistics['3-4-45-false']['Rubber D3']['played']['winner'] );
	}

	public function test_get_matches_with_null_display_name_player_and_rubber_evaluates_to_draw_without_exception(): void {
		$player = Player_Factory::from_array( array( 'ID' => '7' ) );
		$this->assertNull( $player->display_name );

		$rubber = (object) array(
			'title'     => 'Rubber 1',
			'type'      => 'MS',
			'winner_id' => '10',
			'players'   => array(
				'home' => array(
					'1' => (object) array( 'display_name' => 'Home Player One' ),
				),
				'away' => array(
					'1' => (object) array( 'display_name' => 'Away Player One' ),
				),
			),
			'custom'    => array(
				'sets' => array(),
			),
		);
		$match  = (object) array(
			'home_team' => '10',
			'away_team' => '20',
			'winner_id' => '10',
			'season'    => '2026',
			'league'    => (object) array(
				'title'       => 'Division 1',
				'num_rubbers' => 1,
				'event'       => (object) array(
					'age_limit'       => 'open',
					'reverse_rubbers' => false,
				),
			),
			'rubbers'   => array( $rubber ),
		);

		$result = $this->get_matches_with_global_manager( $player, array( $match ) );

		$this->assertSame( array( $match ), $result );
		$this->assertArrayHasKey( '1-1-open-true', $player->statistics );
		$this->assertSame( 1, $player->statistics['1-1-open-true']['Rubber 1']['played']['draw'] );
	}
}
