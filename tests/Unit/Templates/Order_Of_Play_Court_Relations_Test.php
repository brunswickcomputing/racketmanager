<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Presentation\View_Models\Order_Of_Play_Match_View_Model;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

/**
 * Verifies fixtures coming out of the real Fixture_Repository carry real team and league data
 * (loaded outside the entity) when rendered by live templates.
 */
final class Order_Of_Play_Court_Relations_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	private function details( string $name, int $club_id ): Team_Details_DTO {
		$team = $this->createStub( Team::class );
		$team->method( 'get_name' )->willReturn( $name );
		$team->method( 'get_team_type' )->willReturn( 'T' );
		$club = $this->createStub( Club::class );
		$club->method( 'get_id' )->willReturn( $club_id );
		$club->method( 'get_shortcode' )->willReturn( 'C' . $club_id );

		return new Team_Details_DTO( $team, $club );
	}

	private function repository(): Fixture_Repository {
		$league = $this->createStub( League::class );
		$league->title = 'Division 1';
		$league_repo = $this->createStub( League_Repository_Interface::class );
		$league_repo->method( 'find_by_id' )->willReturn( $league );

		$resolver = $this->createStub( Fixture_Team_Resolver_Interface::class );
		$resolver->method( 'get_team_details' )->willReturnMap(
			array(
				array( 11, $this->details( 'Westcliff Men 1', 3 ) ),
				array( 22, $this->details( 'Leigh Men 1', 4 ) ),
			)
		);

		$wpdb         = $this->createStub( wpdb::class );
		$wpdb->prefix = 'wp_';
		$wpdb->method( 'prepare' )->willReturnCallback( static fn( string $query ): string => $query );
		$wpdb->method( 'get_row' )->willReturn(
			(object) array(
				'id'        => 998124,
				'league_id' => 7,
				'home_team' => '11',
				'away_team' => '22',
			)
		);
		$GLOBALS['wpdb'] = $wpdb;

		return new Fixture_Repository( $wpdb, new Fixture_Relation_Loader( $league_repo, $resolver ) );
	}

	public function test_order_of_play_court_shows_real_team_names_and_league_title(): void {
		$court_times = array( array( new Order_Of_Play_Match_View_Model(
			id: 998124,
			league_title: 'Division 1',
			home_match_title: 'Westcliff Men 1',
			away_match_title: 'Leigh Men 1',
			home_team: '10',
			away_team: '20',
		) ) );
		$is_expanded = false;

		ob_start();
		include dirname( __DIR__, 3 ) . '/templates/includes/order-of-play-court.php';
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'Division 1', $output );
		$this->assertStringContainsString( 'Westcliff Men 1', $output );
		$this->assertStringContainsString( 'Leigh Men 1', $output );
		$this->assertStringNotContainsString( 'Unknown', $output );
	}

	public function test_repository_hydrated_fixture_exposes_club_ids_for_list_highlighting(): void {
		$fixture = $this->repository()->find_by_id_with_relations( 998124 );

		$teams = $fixture->get_teams();
		$this->assertSame( 3, $teams['home']->club->id );
		$this->assertSame( 4, $teams['away']->club->id );
		$this->assertSame( 'Division 1', $fixture->get_league()->title );
		$this->assertSame( 'Westcliff Men 1 - Leigh Men 1', $fixture->get_title() );
	}

	public function test_order_of_play_court_renders_via_view_model_without_repository(): void {
		unset( $GLOBALS['racketmanager'] );

		$match_vm = new Order_Of_Play_Match_View_Model(
			id: 101,
			league_title: 'Championship Final',
			home_match_title: 'Winner Semi 1',
			away_match_title: 'Westcliff 1',
			is_home_pending: true,
			is_away_pending: false,
		);

		$court_times = array( array( $match_vm ) );
		$is_expanded = true;

		ob_start();
		include dirname( __DIR__, 3 ) . '/templates/includes/order-of-play-court.php';
		$output = (string) ob_get_clean();

		$this->assertStringContainsString( 'is-expanded', $output );
		$this->assertStringContainsString( 'Championship Final', $output );
		$this->assertStringContainsString( 'Winner Semi 1', $output );
		$this->assertStringContainsString( 'Westcliff 1', $output );
		$this->assertStringContainsString( 'is_pending', $output );
		$this->assertStringContainsString( '<div class="team-separator">vs</div>', $output );
	}
}
