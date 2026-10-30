<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\League\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Query;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Handler;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Query;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Query;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Query;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Query;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

class League_Query_Handler_Delegation_Test extends TestCase {
	private function league(): League {
		$event              = $this->createStub( Event::class );
		$event->competition = (object) [
			'type'            => 'league',
			'is_team_entry'   => true,
			'is_player_entry' => false,
		];

		$league                 = $this->createStub( League::class );
		$league->id             = 7;
		$league->title          = 'Division 1';
		$league->current_season = [ 'name' => '2026' ];
		$league->event          = $event;

		return $league;
	}

	private function league_service( League $league ): League_Service {
		$league_service = $this->createStub( League_Service::class );
		$league_service->method( 'get_league' )->willReturn( $league );

		return $league_service;
	}

	private function team_details_presenter(): Team_Details_Presenter {
		return new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );
	}

	private function template_renderer(): Shortcode_Template_Renderer {
		$renderer = $this->createStub( Shortcode_Template_Renderer::class );
		$renderer->method( 'template_exists' )->willReturn( false );

		return $renderer;
	}

	public function test_teams_handler_list_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$team         = (object) [ 'id' => 101, 'title' => 'Westcliff 1' ];
		$info         = (object) [ 'captain' => 'Jane Smith' ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_league_teams' )
			->with(
				$league,
				[ 'orderby' => [ 'title' => 'ASC' ] ]
			)
			->willReturn( [ $team ] );
		$league_query->expects( $this->once() )
			->method( 'get_team_dtls' )
			->with( $league, 101 )
			->willReturn( $info );

		$handler = new Get_League_Teams_Handler(
			$this->league_service( $league ),
			$this->createStub( Team_Service::class ),
			$this->createStub( Player_Stats_Service::class ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Teams_Query( league_id: 7 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertFalse( $result->is_team_details );
		$this->assertSame( $league, $result->league );
		$this->assertCount( 1, $result->league_teams );
	}

	public function test_teams_handler_skips_info_assignment_when_team_details_missing(): void {
		$league = $this->league();
		$team   = League_Team::from_object(
			(object) [
				'id'             => 101,
				'table_id'       => 1,
				'title'          => 'Westcliff 1',
				'points_plus'    => 0,
				'points_minus'   => 0,
				'points_2_plus'  => 0,
				'points_2_minus' => 0,
				'custom'         => '',
				'roster'         => '',
			]
		);
		$this->assertNull( $team->info );

		$league_query = $this->createStub( League_Query_Service::class );
		$league_query->method( 'get_league_teams' )->willReturn( [ $team ] );
		$league_query->method( 'get_team_dtls' )->willReturn( false );

		$handler = new Get_League_Teams_Handler(
			$this->league_service( $league ),
			$this->createStub( Team_Service::class ),
			$this->createStub( Player_Stats_Service::class ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Teams_Query( league_id: 7 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertNull( $team->info );
		$this->assertCount( 1, $result->league_teams );
	}

	public function test_teams_handler_details_delegates_to_league_query_service(): void {
		$league = $this->league();
		$team   = $this->createStub( Team::class );
		$team->id = 12;

		$team_service = $this->createStub( Team_Service::class );
		$team_service->method( 'get_team_details' )->willReturn( new Team_Details_DTO( $team ) );

		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_team_dtls' )
			->with( $league, 12 )
			->willReturn( (object) [ 'captain' => 'Jane' ] );
		$league_query->expects( $this->once() )
			->method( 'get_league_team' )
			->with( $league, 12 )
			->willReturn( (object) [ 'rank' => 1 ] );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static function ( array $args ): bool {
						return 12 === ( $args['team_id'] ?? null )
							&& false === ( $args['match_day'] ?? null )
							&& 'false' === ( $args['limit'] ?? null )
							&& true === ( $args['reset_query_args'] ?? null );
					}
				)
			)
			->willReturn( [] );
		$league_query->expects( $this->once() )
			->method( 'get_players' )
			->with( $league, [ 'team' => 12 ] )
			->willReturn( [] );

		$stats = $this->createStub( Player_Stats_Service::class );
		$stats->method( 'populate_players_stats' )->willReturn( [] );

		$handler = new Get_League_Teams_Handler(
			$this->league_service( $league ),
			$team_service,
			$stats,
			$league_query
		);

		$result = $handler->handle( new Get_League_Teams_Query( league_id: 7, team_id: '12' ) );

		$this->assertFalse( $result->is_error() );
		$this->assertTrue( $result->is_team_details );
		$this->assertSame( 12, $result->team_id );
		$this->assertSame( $team, $result->team );
	}

	public function test_standings_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$teams        = [ (object) [ 'id' => 101, 'title' => 'Westcliff 1' ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'set_season' )
			->with( $league, false );
		$league_query->expects( $this->once() )
			->method( 'get_league_teams' )
			->with(
				$league,
				[ 'orderby' => [ 'rank' => 'ASC' ], 'active' => true ]
			)
			->willReturn( $teams );

		$handler = new Get_League_Standings_Handler(
			$this->league_service( $league ),
			null,
			$league_query
		);

		$vm = $handler->handle( new Get_League_Standings_Query( league_id: 7 ) );

		$this->assertNotNull( $vm );
		$this->assertSame( $league, $vm->league );
		$this->assertSame( $teams, $vm->teams );
	}

	public function test_crosstable_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$teams        = [ (object) [ 'id' => 101, 'title' => 'Westcliff 1' ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'set_season' )
			->with( $league, false );
		$league_query->expects( $this->once() )
			->method( 'get_league_teams' )
			->with( $league, [ 'orderby' => [ 'rank' => 'ASC' ] ] )
			->willReturn( $teams );
		// $league->teams stays empty (the mock does not populate it): no columns, hence no cell queries.
		$league_query->expects( $this->never() )->method( 'get_crosstable_field' );

		$handler = new Get_League_Crosstable_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Crosstable_Query( league_id: 7 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( '', $result->template );
		$this->assertSame( $teams, $result->teams );
		$this->assertSame( [], $result->columns );
		$this->assertSame( [], $result->cells );
	}

	public function test_crosstable_handler_precomputes_cells_for_every_row_and_column(): void {
		$league       = $this->league();
		$teams        = [
			(object) [ 'id' => 101, 'title' => 'Westcliff 1', 'rank' => 1 ],
			(object) [ 'id' => 202, 'title' => 'Leigh 2', 'rank' => 2 ],
		];
		// League order differs from the ranked order and carries a third team that has no ranked row.
		$league_order = [ $teams[1], $teams[0], (object) [ 'id' => 303, 'title' => 'Thorpe 3', 'rank' => 3 ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->method( 'get_league_teams' )
			->willReturnCallback( static function ( League $l ) use ( $teams, $league_order ): array {
				$l->teams = $league_order;
				return $teams;
			} );
		$league_query->expects( $this->exactly( 4 ) )
			->method( 'get_crosstable_field' )
			->willReturnCallback( static fn( League $l, int $team_id, int $opponent_id ): string => $team_id === $opponent_id ? '&nbsp;' : "{$team_id}v{$opponent_id}" );

		$handler = new Get_League_Crosstable_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Crosstable_Query( league_id: 7 ) );

		// Columns follow the league order, capped to the number of ranked rows the template renders.
		$this->assertSame( [ $teams[1], $teams[0] ], $result->columns );
		$this->assertSame(
			[
				101 => [ 202 => '101v202', 101 => '&nbsp;' ],
				202 => [ 202 => '&nbsp;', 101 => '202v101' ],
			],
			$result->cells
		);
	}

	public function test_last_5_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'set_season' )
			->with( $league );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static function ( array $args ): bool {
						return 'prev' === ( $args['time'] ?? null )
							&& 12 === ( $args['team_id'] ?? null )
							&& -1 === ( $args['match_day'] ?? null )
							&& 5 === ( $args['limit'] ?? null );
					}
				)
			)
			->willReturn( [] );

		$handler = new Get_League_Last_5_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Last_5_Query( league_id: 7, team_id: 12 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( '', $result->template );
		$this->assertSame( [], $result->matches );
		$this->assertSame( 12, $result->team_id );
	}

	public function test_match_dropdown_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$matches      = [ (object) [ 'id' => 44 ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				[
					'limit'            => false,
					'match_day'        => -1,
					'season'           => '2026',
					'reset_query_args' => true,
				]
			)
			->willReturn( $matches );

		$handler = new Get_League_Match_Dropdown_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Match_Dropdown_Query( league_id: 7, season: '2026', match_id: 44 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( '', $result->template );
		$this->assertSame( $matches, $result->matches );
		$this->assertSame( 44, $result->match_id );
	}

	public function test_matches_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$teams        = [ (object) [ 'id' => 101, 'title' => 'Westcliff 1' ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'set_season' )
			->with( $league, false );
		$league_query->expects( $this->once() )
			->method( 'set_match_day' )
			->with( $league, 'current' );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static function ( array $args ): bool {
						return '' === ( $args['final'] ?? null )
							&& '' === ( $args['time'] ?? null )
							&& false === ( $args['home_only'] ?? null );
					}
				)
			)
			->willReturn( [] );
		$league_query->expects( $this->once() )
			->method( 'set_num_matches' )
			->with( $league );
		$league_query->expects( $this->once() )
			->method( 'get_league_teams' )
			->with(
				$league,
				[
					'season'  => '2026',
					'orderby' => [ 'title' => 'ASC' ],
				]
			)
			->willReturn( $teams );

		$handler = new Get_League_Matches_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Matches_Query( league_id: 7 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( 'daily', $result->template );
		$this->assertSame( $teams, $result->teams );
		$this->assertSame( '2026', $result->season );
	}

	public function test_championship_handler_delegates_get_matches_for_finals(): void {
		$league       = $this->league();
		$championship = $this->createStub( Championship::class );
		$championship->method( 'get_finals' )->willReturn(
			[
				'final' => [
					'key'         => 'final',
					'name'        => 'Final',
					'num_matches' => 1,
					'round'       => 1,
				],
			]
		);
		$league->championship = $championship;

		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				[
					'final'   => 'final',
					'orderby' => [ 'id' => 'ASC' ],
				]
			)
			->willReturn( [] );

		$handler = new Get_League_Championship_Handler(
			$this->league_service( $league ),
			$this->createStub( Fixture_Repository_Interface::class ),
			$this->createStub( Rubber_Repository_Interface::class ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Championship_Query( league_id: 7, season: '2026' ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( '', $result->template );
		$this->assertSame( $league, $result->league );
	}
}
