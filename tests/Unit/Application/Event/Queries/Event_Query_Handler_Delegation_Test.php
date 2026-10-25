<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Event\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\League_Service;

class Event_Query_Handler_Delegation_Test extends TestCase {
	private function league_with_finals(): League {
		$championship = $this->createStub( Championship::class );
		$championship->method( 'get_finals' )->willReturn(
			[
				'final' => [
					'key'         => 'final',
					'name'        => 'Final',
					'num_matches' => 1,
				],
			]
		);

		$league               = $this->createStub( League::class );
		$league->id           = 9;
		$league->championship = $championship;

		return $league;
	}

	public function test_event_matches_draw_delegates_get_matches_to_league_query_service(): void {
		$league = $this->league_with_finals();
		$event  = $this->createStub( Event::class );

		$event_query = $this->createStub( Event_Query_Service::class );
		$event_query->method( 'get_leagues' )->willReturn( [ $league ] );

		$league_service = $this->createStub( League_Service::class );
		$league_service->method( 'get_league' )->willReturn( $league );

		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static function ( array $args ): bool {
						return 'final' === ( $args['final'] ?? null )
							&& isset( $args['orderby']['id'] )
							&& 'ASC' === $args['orderby']['id'];
					}
				)
			)
			->willReturn( [ (object) [ 'id' => 88 ] ] );

		$tournament = $this->createStub( Tournament_Presenter::class );
		$tournament->method( 'present_match_list' )->willReturn( [] );
		$tournament->method( 'is_tournament_competition' )->willReturn( false );

		$handler = new Get_Event_Matches_Handler(
			$this->createStub( Competition_Service::class ),
			$tournament,
			$league_service,
			$event_query,
			$league_query
		);

		$leagues = $handler->get_draw( $event, '2026' );

		$this->assertCount( 1, $leagues );
		$this->assertSame( $league, $leagues[0] );
		$this->assertArrayHasKey( 'final', $leagues[0]->finals );
	}

	public function test_event_draw_handler_delegates_get_matches_to_league_query_service(): void {
		$league = $this->league_with_finals();
		$event  = $this->createStub( Event::class );

		$event_query = $this->createStub( Event_Query_Service::class );
		$event_query->method( 'get_leagues' )->willReturn( [ $league ] );

		$league_service = $this->createStub( League_Service::class );
		$league_service->method( 'get_league' )->willReturn( $league );

		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static function ( array $args ): bool {
						return 'final' === ( $args['final'] ?? null )
							&& isset( $args['orderby']['id'] )
							&& 'ASC' === $args['orderby']['id'];
					}
				)
			)
			->willReturn( [] );

		$handler = new Get_Event_Draw_Handler(
			$this->createStub( Competition_Service::class ),
			$league_service,
			$this->createStub( Competition_Shortcode_Presenter::class ),
			$this->createStub( Fixture_Detail_Service::class ),
			$event_query,
			$league_query
		);

		$leagues = $handler->get_draw( $event, '2026' );

		$this->assertCount( 1, $leagues );
		$this->assertSame( $league, $leagues[0] );
		$this->assertSame( [], $leagues[0]->finals );
	}
}
