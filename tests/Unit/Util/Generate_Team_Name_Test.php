<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Util;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Util\Util;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
final class Generate_Team_Name_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_stubs_translate_callback'] );
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_generate_team_name_uses_fixture_relations_for_placeholder_title(): void {
		$fixture         = Fixture_Factory::from_object( (object) array( 'id' => 145957 ) );
		$fixture->teams  = array(
			'home' => (object) array( 'title' => 'Westcliff Lawn Mixed 1' ),
			'away' => (object) array( 'title' => 'Rayleigh Mixed 1' ),
		);

		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id_with_relations' )
			->with( 145957 )
			->willReturn( $fixture );
		$fixture_repository->expects( $this->never() )
			->method( 'find_by_id' );

		$this->assertSame(
			'Loser of Westcliff Lawn Mixed 1 vs Rayleigh Mixed 1',
			Util::generate_team_name( '2_last-16_145957', $fixture_repository )
		);
	}

	public function test_generate_team_name_winner_placeholder_title(): void {
		$fixture        = Fixture_Factory::from_object( (object) array( 'id' => 145958 ) );
		$fixture->teams = array(
			'home' => (object) array( 'title' => 'Team Alpha' ),
			'away' => (object) array( 'title' => 'Team Beta' ),
		);

		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id_with_relations' )
			->with( 145958 )
			->willReturn( $fixture );

		$this->assertSame(
			'Winner of Team Alpha vs Team Beta',
			Util::generate_team_name( '1_final_145958', $fixture_repository )
		);
	}

	public function test_generate_team_name_returns_null_when_prefix_is_invalid(): void {
		$fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
		$this->assertNull( Util::generate_team_name( 'invalid_format', $fixture_repository ) );
		$this->assertNull( Util::generate_team_name( '3_round_145957', $fixture_repository ) );
	}

	public function test_get_placeholder_team_title(): void {
		$this->assertSame( 'Winner of', Util::get_placeholder_team_title( '1_semi_1' ) );
		$this->assertSame( 'Loser of', Util::get_placeholder_team_title( '2_semi_1' ) );
		$this->assertNull( Util::get_placeholder_team_title( '3_semi_1' ) );
		$this->assertNull( Util::get_placeholder_team_title( 'invalid' ) );
	}

	public function test_generate_team_name_returns_prefix_only_when_match_not_found(): void {
		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id_with_relations' )
			->with( 999 )
			->willReturn( null );

		$this->assertSame(
			'Winner of',
			Util::generate_team_name( '1_semi_999', $fixture_repository )
		);
	}

	public function test_placeholder_translation_uses_wordpress_localization(): void {
		$GLOBALS['wp_stubs_translate_callback'] = static function( string $text, string $domain ): string {
			if ( 'racketmanager' === $domain ) {
				if ( 'Winner of' === $text ) {
					return 'Vainqueur de';
				}
				if ( 'Loser of' === $text ) {
					return 'Perdant de';
				}
				if ( 'vs' === $text ) {
					return 'c.';
				}
			}
			return $text;
		};

		$this->assertSame( 'Vainqueur de', Util::get_placeholder_team_title( '1_semi_1' ) );
		$this->assertSame( 'Perdant de', Util::get_placeholder_team_title( '2_final_2' ) );

		$fixture        = Fixture_Factory::from_object( (object) array( 'id' => 145959 ) );
		$fixture->teams = array(
			'home' => (object) array( 'title' => 'Alpha' ),
			'away' => (object) array( 'title' => 'Beta' ),
		);
		$fixture_repo_with_match = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repo_with_match->expects( $this->once() )
			->method( 'find_by_id_with_relations' )
			->with( 145959 )
			->willReturn( $fixture );

		$this->assertSame(
			'Vainqueur de Alpha c. Beta',
			Util::generate_team_name( '1_semi_145959', $fixture_repo_with_match )
		);
	}

	public function test_generate_team_name_breaks_recursive_cycle_gracefully(): void {
		$fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
		$fixture_repository->expects( $this->once() )
			->method( 'find_by_id_with_relations' )
			->with( 200 )
			->willReturnCallback( function ( int $id ) use ( $fixture_repository ) {
				// Re-entrant call while resolving fixture 200 (cycle scenario)
				$inner_title = Util::generate_team_name( '1_semi_200', $fixture_repository );
				$fixture        = Fixture_Factory::from_object( (object) array( 'id' => 200 ) );
				$fixture->teams = array(
					'home' => (object) array( 'title' => $inner_title ),
					'away' => (object) array( 'title' => 'Team Gamma' ),
				);
				return $fixture;
			} );

		$result = Util::generate_team_name( '1_semi_200', $fixture_repository );
		$this->assertSame( 'Winner of Winner of vs Team Gamma', $result );
	}
}
