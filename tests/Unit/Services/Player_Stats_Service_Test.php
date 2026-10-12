<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player_Factory;
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
