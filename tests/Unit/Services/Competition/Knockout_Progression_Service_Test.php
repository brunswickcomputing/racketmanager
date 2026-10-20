<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Stage;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition\Knockout_Progression_Service;

final class Knockout_Progression_Service_Test extends TestCase {
	public function test_progress_winner_resolves_league_via_injected_league_repository_when_not_passed(): void {
		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$league_repo  = $this->createMock( League_Repository_Interface::class );
		$event_repo   = $this->createStub( Event_Repository_Interface::class );

		$ref_champ = new \ReflectionClass( Championship::class );
		$championship = $ref_champ->newInstanceWithoutConstructor();
		$prop_id = $ref_champ->getProperty( 'league_id' );
		$prop_id->setValue( $championship, 99 );
		$prop_rounds = $ref_champ->getProperty( 'num_rounds' );
		$prop_rounds->setValue( $championship, 3 );
		$prop_finals = $ref_champ->getProperty( 'finals_by_key' );
		$prop_finals->setValue( $championship, array(
			'quarter' => array( 'round' => 1 ),
			'semi'    => array( 'round' => 2 ),
		) );
		$prop_keys = $ref_champ->getProperty( 'keys_by_round' );
		$prop_keys->setValue( $championship, array(
			1 => 'quarter',
			2 => 'semi',
			3 => 'final',
		) );

		$stage = new Stage( 1, 'Stage 1', 'draw', 10 );
		$stage->set_championship( $championship );

		$fixture = ( new \ReflectionClass( Fixture::class ) )->newInstanceWithoutConstructor();
		$fixture->set_id( 101 );
		$fixture->set_final( 'quarter' );
		$fixture->set_season( '2026' );
		$fixture->set_winner_id( 50 );

		$event = ( new \ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
		$event->seasons = array( '2026' => array( 'name' => '2026' ) );

		$league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$league->id = 99;
		$league->season = '2026';
		$league->current_season = array( 'name' => '2026', 'home_away' => false );
		$league->championship = $championship;
		$league->event = $event;

		$league_repo->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 99 )
			->willReturn( $league );

		$fixture_repo->expects( $this->exactly( 2 ) )
			->method( 'find_by_league_and_final' )
			->willReturnCallback( function ( int $league_id, string $season, string $final_key ) use ( $fixture ) {
				if ( 'quarter' === $final_key ) {
					return array( $fixture );
				}
				if ( 'semi' === $final_key ) {
					$next_fixture = ( new \ReflectionClass( Fixture::class ) )->newInstanceWithoutConstructor();
					$next_fixture->set_id( 202 );
					$next_fixture->set_home_team( 'quarter_1_winner' );
					$next_fixture->set_away_team( 'quarter_2_winner' );
					return array( $next_fixture );
				}
				return array();
			} );

		$fixture_repo->expects( $this->once() )
			->method( 'save' )
			->with( $this->callback( function ( Fixture $f ) {
				return '50' === $f->get_home_team();
			} ) );

		$service = new Knockout_Progression_Service(
			$fixture_repo,
			$league_repo,
			$event_repo
		);

		$service->progress_winner( $stage, $fixture );
	}

	public function test_handle_consolation_resolves_league_and_event_via_injected_repositories(): void {
		$fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
		$league_repo  = $this->createMock( League_Repository_Interface::class );
		$event_repo   = $this->createMock( Event_Repository_Interface::class );

		$ref_champ = new \ReflectionClass( Championship::class );
		$championship = $ref_champ->newInstanceWithoutConstructor();
		$prop_consolation = $ref_champ->getProperty( 'is_consolation' );
		$prop_consolation->setValue( $championship, false );
		$prop_finals = $ref_champ->getProperty( 'finals_by_key' );
		$prop_finals->setValue( $championship, array(
			'round_1' => array( 'round' => 1 ),
		) );
		$stage = new Stage( 1, 'Stage 1', 'draw', 10 );
		$stage->set_championship( $championship );

		$fixture = ( new \ReflectionClass( Fixture::class ) )->newInstanceWithoutConstructor();
		$fixture->set_id( 501 );
		$fixture->set_league_id( 77 );
		$fixture->set_final( 'round_1' );
		$fixture->set_season( '2026' );
		$fixture->set_loser_id( 33 );

		$event = $this->createMock( Event::class );
		$event->expects( $this->once() )
			->method( 'get_leagues' )
			->with( array( 'consolation' => true ) )
			->willReturn( array( 999 ) );

		$main_league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$main_league->id = 77;
		$main_league->event_id = 88;
		$main_league->season = '2026';
		$main_league->current_season = array( 'name' => '2026', 'home_away' => false );
		$main_league->championship = $championship;
		$main_league->event = $event;

		$consolation_league = ( new \ReflectionClass( League::class ) )->newInstanceWithoutConstructor();
		$consolation_league->id = 999;
		$consolation_league->season = '2026';
		$consolation_league->current_season = array( 'name' => '2026', 'home_away' => false );
		$consolation_league->event = $event;

		$league_repo->expects( $this->exactly( 2 ) )
			->method( 'find_by_id' )
			->willReturnCallback( function ( int $id ) use ( $main_league, $consolation_league ) {
				if ( 77 === $id ) {
					return $main_league;
				}
				if ( 999 === $id ) {
					return $consolation_league;
				}
				return null;
			} );

		$event_repo->expects( $this->once() )
			->method( 'find_by_id' )
			->with( 88 )
			->willReturn( $event );

		$consolation_fixture = ( new \ReflectionClass( Fixture::class ) )->newInstanceWithoutConstructor();
		$consolation_fixture->set_id( 801 );
		$consolation_fixture->set_home_team( '2_round_1_501' );

		$fixture_repo->expects( $this->once() )
			->method( 'find_by_league_and_team' )
			->with( 999, '2026', '2_round_1_501' )
			->willReturn( array( $consolation_fixture ) );

		$fixture_repo->expects( $this->once() )
			->method( 'save' )
			->with( $this->callback( function ( Fixture $f ) {
				return '33' === $f->get_home_team();
			} ) );

		$service = new Knockout_Progression_Service(
			$fixture_repo,
			$league_repo,
			$event_repo
		);

		$service->handle_consolation( $stage, $fixture );
	}
}
