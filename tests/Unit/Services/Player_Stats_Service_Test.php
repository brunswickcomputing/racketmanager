<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;

final class Player_Stats_Service_Test extends TestCase {
	private Player_Stats_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$this->service = new Player_Stats_Service();
	}

	public function test_get_stats_totals_win_pct_and_walkover(): void {
		$player = Player_Factory::from_object( (object) array( 'ID' => '1', 'display_name' => 'Pat Player' ) );
		$stats  = array(
			'2024' => array(
				'played'    => array(
					'winner' => 2,
					'loser'  => 1,
					'draw'   => 0,
				),
				'sets'      => array(
					'winner' => 6,
					'loser'  => 3,
				),
				'games'     => array(
					'winner' => 36,
					'loser'  => 24,
				),
				'walkover'  => 1,
			),
			'2025' => array(
				'played' => array(
					'winner' => 1,
					'loser'  => 0,
					'draw'   => 1,
				),
			),
		);

		$result = $this->service->get_stats( $player, $stats );

		$this->assertSame( 3, $result['total']->matches_won );
		$this->assertSame( 1, $result['total']->matches_lost );
		$this->assertSame( 1, $result['total']->matches_tie );
		$this->assertSame( 5, $result['total']->played );
		$this->assertSame( 60.0, (float) $result['total']->win_pct );
		$this->assertSame( 6, $result['total']->sets_won );
		$this->assertSame( 3, $result['total']->sets_lost );
		$this->assertSame( 36, $result['total']->games_won );
		$this->assertSame( 24, $result['total']->games_lost );
		$this->assertSame( 1, $result['total']->walkover );
		$this->assertArrayHasKey( '2024', $result['detail'] );
		$this->assertArrayHasKey( '2025', $result['detail'] );
	}

	public function test_get_stats_empty_walkover_is_empty_string(): void {
		$player = Player_Factory::from_object( (object) array( 'ID' => '2', 'display_name' => 'Empty Stats' ) );

		$result = $this->service->get_stats( $player, array() );

		$this->assertSame( 0, $result['total']->played );
		$this->assertSame( 0, $result['total']->matches_won );
		$this->assertSame( '', $result['total']->walkover );
		$this->assertObjectNotHasProperty( 'win_pct', $result['total'] );
	}

	public function test_get_stats_ceils_win_pct(): void {
		$player = Player_Factory::from_object( (object) array( 'ID' => '3', 'display_name' => 'Ceil Player' ) );
		$stats  = array(
			'2026' => array(
				'played' => array(
					'winner' => 2,
					'loser'  => 1,
					'draw'   => 0,
				),
			),
		);

		$result = $this->service->get_stats( $player, $stats );

		$this->assertSame( 3, $result['total']->played );
		$this->assertSame( 67.0, (float) $result['total']->win_pct );
	}

	public function test_populate_player_stats_maps_totals_and_handles_no_matches(): void {
		$player         = Player_Factory::from_array( [ 'id' => 4, 'display_name' => 'No Matches' ] );
		$player->statistics = [];
		$query_service  = $this->createMock( Player_Query_Service::class );
		$query_service->expects( $this->once() )
			->method( 'get_matches' )
			->with( $player, null, '2025', 'league' )
			->willReturn( [] );
		$service = new Player_Stats_Service( $query_service );

		$service->populate_player_stats( $player, null, '2025', 'league' );

		$this->assertSame( [], $player->matches );
		$this->assertSame( 0, $player->matches_won );
		$this->assertSame( 0, $player->matches_lost );
		$this->assertSame( 0, $player->played );
		$this->assertSame( 0.0, $player->win_pct );
	}

	public function test_populate_players_stats_sorts_by_wins_then_played(): void {
		$query_service = $this->createStub( Player_Query_Service::class );
		$query_service->method( 'get_matches' )->willReturn( [] );
		$service = new Player_Stats_Service( $query_service );

		$three_wins_five_played = Player_Factory::from_array( [ 'id' => 1, 'display_name' => 'A' ] );
		$three_wins_four_played = Player_Factory::from_array( [ 'id' => 2, 'display_name' => 'B' ] );
		$two_wins_three_played  = Player_Factory::from_array( [ 'id' => 3, 'display_name' => 'C' ] );
		$three_wins_five_played->statistics = [ 'season' => [ 'played' => [ 'winner' => 3, 'loser' => 2 ] ] ];
		$three_wins_four_played->statistics = [ 'season' => [ 'played' => [ 'winner' => 3, 'loser' => 1 ] ] ];
		$two_wins_three_played->statistics  = [ 'season' => [ 'played' => [ 'winner' => 2, 'loser' => 1 ] ] ];

		$players = $service->populate_players_stats(
			[ $three_wins_five_played, $two_wins_three_played, $three_wins_four_played ],
			null,
			null,
			'league'
		);

		$this->assertSame( [ $three_wins_four_played, $three_wins_five_played, $two_wins_three_played ], $players );
		$this->assertSame( 3, $three_wins_four_played->matches_won );
		$this->assertSame( 4, $three_wins_four_played->played );
	}

	public function test_calculate_career_stats_maps_results_types_and_sorts_seasons(): void {
		$team_matches = array(
			(object) array(
				'stat_type'   => 'teams',
				'player_team' => 'home',
				'winner_id'   => 10,
				'home_team'   => 10,
				'away_team'   => 20,
				'type'        => 'MD',
				'season'      => '2024',
			),
			(object) array(
				'stat_type'   => 'teams',
				'player_team' => 'home',
				'winner_id'   => 20,
				'home_team'   => 10,
				'away_team'   => 20,
				'type'        => 'WD',
				'season'      => '2024',
			),
			(object) array(
				'stat_type'   => 'teams',
				'player_team' => 'home',
				'winner_id'   => 99,
				'home_team'   => 10,
				'away_team'   => 20,
				'type'        => 'XD',
				'season'      => '2025',
			),
			(object) array(
				'stat_type'   => 'teams',
				'player_team' => 'away',
				'winner_id'   => 20,
				'home_team'   => 10,
				'away_team'   => 20,
				'type'        => 'LD',
				'season'      => '2025',
			),
			(object) array(
				'stat_type'   => 'teams',
				'player_team' => 'away',
				'winner_id'   => 10,
				'home_team'   => 10,
				'away_team'   => 20,
				'type'        => 'MS',
				'season'      => '2025',
			),
			(object) array(
				'stat_type'   => 'teams',
				'player_team' => 'away',
				'winner_id'   => 99,
				'home_team'   => 10,
				'away_team'   => 20,
				'type'        => 'LS',
				'season'      => '2024',
			),
		);
		$player_matches = array(
			(object) array(
				'stat_type' => 'players',
				'winner_id' => 5,
				'loser_id'  => 6,
				'team_id'   => 5,
				'type'      => 'MS',
				'season'    => '2025',
			),
			(object) array(
				'stat_type' => 'players',
				'winner_id' => 6,
				'loser_id'  => 5,
				'team_id'   => 5,
				'type'      => 'MD',
				'season'    => '2025',
			),
			(object) array(
				'stat_type' => 'players',
				'winner_id' => 9,
				'loser_id'  => 8,
				'team_id'   => 5,
				'type'      => 'XD',
				'season'    => '2024',
			),
		);

		$result  = $this->service->calculate_career_stats( $team_matches, $player_matches );
		$seasons = array_keys( $result['seasons'] );

		$this->assertSame( array( 2025, 2024 ), $seasons );
		$this->assertSame( 1, $result['seasons']['2024']['breakdown']['D']['win'] );
		$this->assertSame( 1, $result['seasons']['2024']['breakdown']['D']['loss'] );
		$this->assertSame( 1, $result['seasons']['2024']['breakdown']['S']['tie'] );
		$this->assertSame( 1, $result['seasons']['2024']['breakdown']['X']['tie'] );
		$this->assertSame( 1, $result['seasons']['2025']['breakdown']['X']['tie'] );
		$this->assertSame( 1, $result['seasons']['2025']['breakdown']['X']['win'] );
		$this->assertSame( 1, $result['seasons']['2025']['breakdown']['S']['loss'] );
		$this->assertSame( 1, $result['seasons']['2025']['breakdown']['S']['win'] );
		$this->assertSame( 1, $result['seasons']['2025']['breakdown']['D']['loss'] );
		$this->assertSame( 2, $result['totals']['breakdown']['X']['tie'] );
		$this->assertSame( 3, $result['totals']['total']['win'] );
		$this->assertSame( 3, $result['totals']['total']['loss'] );
		$this->assertSame( 3, $result['totals']['total']['tie'] );
	}
}
