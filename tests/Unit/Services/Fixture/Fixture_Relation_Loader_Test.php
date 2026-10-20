<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Fixture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;

final class Fixture_Relation_Loader_Test extends TestCase {

	private function details( string $name, int $club_id, string $shortcode ): Team_Details_DTO {
		$team = $this->createStub( Team::class );
		$team->method( 'get_name' )->willReturn( $name );
		$team->method( 'get_team_type' )->willReturn( 'T' );
		$club = $this->createStub( Club::class );
		$club->method( 'get_id' )->willReturn( $club_id );
		$club->method( 'get_shortcode' )->willReturn( $shortcode );
		$dto           = new Team_Details_DTO( $team, $club );
		$dto->captain  = 'Captain ' . $name;
		$dto->players  = array( 5, 6 );
		$dto->match_day = 'Monday';

		return $dto;
	}

	private function fixture( string $home = '11', string $away = '22' ): Fixture {
		return Fixture_Factory::from_array(
			array(
				'id'        => 1,
				'league_id' => 7,
				'home_team' => $home,
				'away_team' => $away,
			)
		);
	}

	public function test_load_attaches_real_teams_and_league(): void {
		$league      = $this->createStub( League::class );
		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 7 )->willReturn( $league );

		$resolver = $this->createStub( Fixture_Team_Resolver_Interface::class );
		$resolver->method( 'get_team_details' )->willReturnMap(
			array(
				array( 11, $this->details( 'Westcliff Men 1', 3, 'WES' ) ),
				array( 22, $this->details( 'Leigh Men 1', 4, 'LEI' ) ),
			)
		);

		$fixture = $this->fixture();
		( new Fixture_Relation_Loader( $league_repo, $resolver ) )->load( $fixture );

		$teams = $fixture->get_teams();
		$this->assertSame( 'Westcliff Men 1', $teams['home']->title );
		$this->assertSame( 'Leigh Men 1', $teams['away']->name );
		$this->assertSame( 3, $teams['home']->club->id );
		$this->assertSame( 'LEI', $teams['away']->club->shortcode );
		$this->assertSame( array( 5, 6 ), $teams['home']->players );
		$this->assertSame( 'Captain Westcliff Men 1', $teams['home']->captain );
		$this->assertSame( 'Monday', $teams['home']->match_day );
		$this->assertSame( $league, $fixture->get_league() );
		$this->assertSame( 'Westcliff Men 1 - Leigh Men 1', $fixture->get_title() );
	}

	public function test_placeholder_references_use_derived_details_and_bye_is_labelled(): void {
		$resolver = $this->createMock( Fixture_Team_Resolver_Interface::class );
		$resolver->expects( $this->once() )
			->method( 'derive_team_details' )
			->with( '1_final_1' )
			->willReturn( $this->details( 'Winner of Semi 1', 9, 'WIN' ) );
		$resolver->expects( $this->once() )
			->method( 'get_team_details' )
			->with( -1 )
			->willReturn( $this->details( 'Bye team', 0, '' ) );

		$fixture = $this->fixture( '1_final_1', '-1' );
		( new Fixture_Relation_Loader( null, $resolver ) )->load_teams( $fixture );

		$teams = $fixture->get_teams();
		$this->assertSame( 'Winner of Semi 1', $teams['home']->title );
		$this->assertSame( 'Bye', $teams['away']->title );
	}

	public function test_unresolvable_team_falls_back_to_unknown_without_failing(): void {
		$resolver = $this->createStub( Fixture_Team_Resolver_Interface::class );
		$resolver->method( 'get_team_details' )->willThrowException( new Team_Not_Found_Exception( 'missing' ) );

		$fixture = $this->fixture();
		( new Fixture_Relation_Loader( null, $resolver ) )->load( $fixture );

		$this->assertSame( 'Unknown - Unknown', $fixture->get_title() );
		$this->assertNull( $fixture->get_teams()['home']->club->id );
	}

	public function test_missing_role_or_secretary_falls_back_safely_without_throwing(): void {
		$resolver = $this->createStub( Fixture_Team_Resolver_Interface::class );
		$resolver->method( 'get_team_details' )->willThrowException( new Role_Assignment_Not_Found_Exception( 'no secretary' ) );

		$fixture = $this->fixture();
		( new Fixture_Relation_Loader( null, $resolver ) )->load( $fixture );

		$this->assertSame( 'Unknown - Unknown', $fixture->get_title() );
		$this->assertNull( $fixture->get_teams()['home']->club->id );
	}

	public function test_missing_player_user_falls_back_safely_without_throwing(): void {
		$resolver = $this->createStub( Fixture_Team_Resolver_Interface::class );
		$resolver->method( 'get_team_details' )->willThrowException( new Player_Not_Found_Exception( 'user deleted' ) );

		$fixture = $this->fixture();
		( new Fixture_Relation_Loader( null, $resolver ) )->load( $fixture );

		$this->assertSame( 'Unknown - Unknown', $fixture->get_title() );
		$this->assertNull( $fixture->get_teams()['home']->club->id );
	}

	public function test_loader_without_collaborators_leaves_fixture_unloaded_and_unplaceholdered(): void {
		$fixture = $this->fixture();
		( new Fixture_Relation_Loader() )->load( $fixture );

		$this->assertNull( $fixture->get_league() );
		$this->assertSame( array(), $fixture->get_loaded_teams() );
		// Reading the fallback must not cache a placeholder that blocks later loading.
		$fixture->get_teams();
		$this->assertSame( array(), $fixture->get_loaded_teams() );
	}

	public function test_existing_league_and_teams_are_not_overwritten(): void {
		$league      = $this->createStub( League::class );
		$league_repo = $this->createMock( League_Repository_Interface::class );
		$league_repo->expects( $this->never() )->method( 'find_by_id' );
		$resolver = $this->createMock( Fixture_Team_Resolver_Interface::class );
		$resolver->expects( $this->never() )->method( 'get_team_details' );

		$fixture = $this->fixture();
		$fixture->set_league( $league );
		$fixture->set_teams( array( 'home' => (object) array( 'title' => 'A' ), 'away' => (object) array( 'title' => 'B' ) ) );
		( new Fixture_Relation_Loader( $league_repo, $resolver ) )->load( $fixture );

		$this->assertSame( $league, $fixture->get_league() );
		$this->assertSame( 'A - B', $fixture->get_title() );
	}
}
