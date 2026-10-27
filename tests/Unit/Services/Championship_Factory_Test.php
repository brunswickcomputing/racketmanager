<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\Tournament\Championship_Settings;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Championship_Factory;
use Racketmanager\Services\Competition\League_Query_Service;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Championship_Factory_Test extends TestCase {

	private array $original_get = array();

	protected function setUp(): void {
		parent::setUp();
		$this->original_get = $_GET;
		unset( $_GET['final'] );
	}

	protected function tearDown(): void {
		$_GET = $this->original_get;
		parent::tearDown();
	}

	/**
	 * Build a knockout league object as produced by League hydration.
	 */
	private function make_league(
		int $id,
		int $num_teams_total,
		array|object $season = array( 'name' => '2026', 'num_match_days' => 3 ),
		bool $is_active = false,
		bool $is_complete = false,
		?int $primary_league = null
	): object {
		return (object) array(
			'id'              => $id,
			'num_teams_total' => $num_teams_total,
			'current_season'  => $season,
			'event'           => (object) array(
				'primary_league' => $primary_league,
				'competition'    => (object) array(
					'is_active'   => $is_active,
					'is_complete' => $is_complete,
				),
			),
		);
	}

	private function factory( ?League_Repository_Interface $repo = null, League_Query_Service|\Closure|null $qs = null ): Championship_Factory {
		return new Championship_Factory( $repo, $qs );
	}

	private function settings(): Championship_Settings {
		return new Championship_Settings();
	}

	/**
	 * Build a primary league that already carries a 16-team championship (4 rounds, first round 'last-16').
	 */
	private function make_primary_league_with_championship(): object {
		$source                = $this->make_league( 1, 16, array( 'name' => '2026', 'num_match_days' => 4 ), true );
		$primary               = $this->createMock( League::class );
		$primary->championship = $this->factory()->create( $source, $this->settings() );
		return $primary;
	}

	private function consolation_league( int $num_teams ): object {
		return $this->make_league( 2, $num_teams, array( 'name' => '2026', 'num_match_days' => 3 ), false, false, 1 );
	}

	private function repo_returning( ?object $primary ): League_Repository_Interface {
		$repo = $this->createMock( League_Repository_Interface::class );
		$repo->expects( $this->once() )->method( 'find_by_id' )->with( 1 )->willReturn( $primary );
		return $repo;
	}

	// ---------------------------------------------------------------- standard progression.

	public function test_standard_inactive_competition_uses_match_days_when_teams_fit(): void {
		$league = $this->make_league( 1, 6 ); // 2^3 = 8 >= 6, inactive.

		$championship = $this->factory()->create( $league, $this->settings() );

		$this->assertInstanceOf( Championship::class, $championship );
		$this->assertSame( 1, $championship->league_id );
		$this->assertFalse( $championship->is_consolation );
		$this->assertSame( 8, $championship->num_advance );
		$this->assertSame( 3, $championship->num_rounds );
		$this->assertSame( 6, $championship->num_teams );
		$this->assertSame( 8, $championship->num_teams_first_round );
		$this->assertSame( array( 1 => 'quarter', 2 => 'semi', 3 => 'final' ), $this->keys_by_round( $championship ) );
		$this->assertSame( 'quarter', $championship->current_final );
	}

	public function test_standard_inactive_competition_derives_rounds_from_teams_when_exceeding_match_days(): void {
		$league = $this->make_league( 1, 10 ); // 10 > 8, inactive.

		$championship = $this->factory()->create( $league, $this->settings() );

		$this->assertSame( 8, $championship->num_advance );
		$this->assertSame( 4, $championship->num_rounds );
		$this->assertSame( 16, $championship->num_teams_first_round );
		$this->assertSame( 'last-16', $championship->current_final );
	}

	public function test_standard_active_competition_derives_rounds_from_teams(): void {
		$league = $this->make_league( 1, 3, array( 'name' => '2026', 'num_match_days' => 3 ), true );

		$championship = $this->factory()->create( $league, $this->settings() );

		$this->assertSame( 8, $championship->num_advance );
		$this->assertSame( 2, $championship->num_rounds );
		$this->assertSame( 4, $championship->num_teams_first_round );
		$this->assertSame( array( 1 => 'semi', 2 => 'final' ), $this->keys_by_round( $championship ) );
	}

	public function test_standard_complete_competition_derives_rounds_from_teams(): void {
		$league = $this->make_league( 1, 5, array( 'name' => '2026', 'num_match_days' => 1 ), false, true );

		$championship = $this->factory()->create( $league, $this->settings() );

		$this->assertSame( 2, $championship->num_advance );
		$this->assertSame( 3, $championship->num_rounds );
		$this->assertSame( 8, $championship->num_teams_first_round );
	}

	public function test_standard_progression_accepts_object_current_season(): void {
		$league = $this->make_league( 1, 3, (object) array( 'name' => '2026', 'num_match_days' => 2 ) );

		$championship = $this->factory()->create( $league, $this->settings() );

		$this->assertSame( 4, $championship->num_advance );
		$this->assertSame( 2, $championship->num_rounds );
		$this->assertSame( 4, $championship->num_teams_first_round );
	}

	public function test_standard_builds_finals_and_placeholder_teams(): void {
		$league = $this->make_league( 1, 4, array( 'name' => '2026', 'num_match_days' => 2 ) );

		$championship = $this->factory()->create( $league, $this->settings() );

		$finals = $championship->finals;
		$this->assertSame( array( 'final', 'semi' ), array_keys( $finals ) );
		$this->assertSame( 2, $finals['semi']['num_matches'] );
		$this->assertSame( 4, $finals['semi']['num_teams'] );
		$this->assertSame( 1, $finals['semi']['round'] );
		$this->assertTrue( $finals['final']['is_final'] );
		$this->assertSame( 2, $finals['final']['round'] );

		$final_teams = $championship->final_teams;
		$this->assertSame( array( '1_', '2_', '3_', '4_' ), array_keys( $final_teams['semi'] ) );
		$this->assertSame( array( '1_semi_1', '1_semi_2' ), array_keys( $final_teams['final'] ) );
	}

	// ---------------------------------------------------------------- consolation.

	public function test_consolation_without_outstanding_matches_sizes_draw_from_teams(): void {
		$primary = $this->make_primary_league_with_championship();
		$qs      = $this->createMock( League_Query_Service::class );
		$qs->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$primary,
				array(
					'pending'          => true,
					'final'            => 'last-16',
					'count'            => true,
					'season'           => '2026',
					'reset_query_args' => true,
				)
			)
			->willReturn( 0 );

		$championship = $this->factory( $this->repo_returning( $primary ), $qs )->create( $this->consolation_league( 5 ), $this->settings() );

		$this->assertTrue( $championship->is_consolation );
		$this->assertSame( 5, $championship->num_teams );
		$this->assertSame( 8, $championship->num_advance );
		$this->assertSame( 3, $championship->num_rounds );
		$this->assertSame( 8, $championship->num_teams_first_round );
		$this->assertSame( 0, $championship->num_seeds );
	}

	public function test_consolation_with_outstanding_primary_matches_uses_max_rounds_and_zero_teams(): void {
		$primary = $this->make_primary_league_with_championship();
		$qs      = $this->createMock( League_Query_Service::class );
		$qs->method( 'get_matches' )->willReturn( 3 );

		$championship = $this->factory( $this->repo_returning( $primary ), $qs )->create( $this->consolation_league( 2 ), $this->settings() );

		$this->assertTrue( $championship->is_consolation );
		$this->assertSame( 0, $championship->num_teams );
		$this->assertSame( 3, $championship->num_rounds );
		$this->assertSame( 8, $championship->num_teams_first_round );
		$this->assertSame( 8, $championship->num_advance );
	}

	public function test_consolation_with_more_teams_than_first_round_capacity_uses_max_rounds(): void {
		$primary = $this->make_primary_league_with_championship();
		$qs      = $this->createMock( League_Query_Service::class );
		$qs->method( 'get_matches' )->willReturn( 0 );

		$championship = $this->factory( $this->repo_returning( $primary ), $qs )->create( $this->consolation_league( 9 ), $this->settings() );

		$this->assertSame( 0, $championship->num_teams );
		$this->assertSame( 3, $championship->num_rounds );
		$this->assertSame( 8, $championship->num_teams_first_round );
	}

	public function test_consolation_falls_back_to_team_count_when_primary_league_missing(): void {
		$championship = $this->factory( $this->repo_returning( null ), $this->createMock( League_Query_Service::class ) )
			->create( $this->consolation_league( 5 ), $this->settings() );

		$this->assertTrue( $championship->is_consolation );
		$this->assertSame( 5, $championship->num_teams );
		$this->assertSame( 3, $championship->num_rounds );
		$this->assertSame( 8, $championship->num_teams_first_round );
		$this->assertSame( 8, $championship->num_advance );
	}

	public function test_consolation_falls_back_when_primary_league_has_no_championship(): void {
		$primary = $this->createMock( League::class );

		$championship = $this->factory( $this->repo_returning( $primary ), $this->createMock( League_Query_Service::class ) )
			->create( $this->consolation_league( 3 ), $this->settings() );

		$this->assertSame( 3, $championship->num_teams );
		$this->assertSame( 2, $championship->num_rounds );
		$this->assertSame( 4, $championship->num_teams_first_round );
	}

	public function test_lazy_league_query_resolver_is_resolved_once_and_drives_consolation_progression(): void {
		$primary = $this->make_primary_league_with_championship();
		$qs      = $this->createMock( League_Query_Service::class );
		$qs->expects( $this->exactly( 2 ) )
			->method( 'get_matches' )
			->with( $primary, $this->arrayHasKey( 'pending' ) )
			->willReturn( 3 );
		$repo = $this->createMock( League_Repository_Interface::class );
		$repo->expects( $this->exactly( 2 ) )->method( 'find_by_id' )->with( 1 )->willReturn( $primary );

		$resolutions = 0;
		$resolver    = function () use ( &$resolutions, $qs ): League_Query_Service {
			++$resolutions;
			return $qs;
		};

		$factory = $this->factory( $repo, $resolver );
		$this->assertSame( 0, $resolutions, 'resolver must not run at construction time' );

		$first  = $factory->create( $this->consolation_league( 2 ), $this->settings() );
		$second = $factory->create( $this->consolation_league( 2 ), $this->settings() );

		$this->assertSame( 1, $resolutions, 'resolver must be invoked exactly once across repeated create() calls' );
		foreach ( array( $first, $second ) as $championship ) {
			$this->assertSame( 0, $championship->num_teams );
			$this->assertSame( 3, $championship->num_rounds );
			$this->assertSame( 8, $championship->num_teams_first_round );
		}
	}

	public function test_resolver_returning_non_service_skips_outstanding_matches_check(): void {
		$primary = $this->make_primary_league_with_championship();

		$championship = $this->factory( $this->repo_returning( $primary ), fn() => null )
			->create( $this->consolation_league( 2 ), $this->settings() );

		// Documented degraded behaviour: without a query service the check is skipped and the draw is sized from the teams.
		$this->assertSame( 2, $championship->num_teams );
		$this->assertSame( 1, $championship->num_rounds );
		$this->assertSame( 2, $championship->num_teams_first_round );
	}

	public function test_null_league_query_service_skips_outstanding_matches_check(): void {
		$primary = $this->make_primary_league_with_championship();

		$championship = $this->factory( $this->repo_returning( $primary ), null )
			->create( $this->consolation_league( 2 ), $this->settings() );

		$this->assertSame( 2, $championship->num_teams );
		$this->assertSame( 1, $championship->num_rounds );
	}

	public function test_league_that_is_its_own_primary_is_not_consolation(): void {
		$league = $this->make_league( 1, 6, array( 'name' => '2026', 'num_match_days' => 3 ), false, false, 1 );

		$championship = $this->factory()->create( $league, $this->settings() );

		$this->assertFalse( $championship->is_consolation );
		$this->assertSame( 2, $championship->num_seeds );
	}

	// ---------------------------------------------------------------- seeds.

	public static function seed_provider(): array {
		return array(
			'10 teams'  => array( 10, 2 ),
			'11 teams'  => array( 11, 4 ),
			'20 teams'  => array( 20, 4 ),
			'21 teams'  => array( 21, 8 ),
			'40 teams'  => array( 40, 8 ),
			'41 teams'  => array( 41, 16 ),
			'80 teams'  => array( 80, 16 ),
			'81 teams'  => array( 81, 32 ),
			'132 teams' => array( 132, 32 ),
			'133 teams' => array( 133, 0 ),
		);
	}

	#[DataProvider( 'seed_provider' )]
	public function test_standard_seed_thresholds( int $num_teams, int $expected_seeds ): void {
		$league = $this->make_league( 1, $num_teams, array( 'name' => '2026', 'num_match_days' => 3 ), true );

		$championship = $this->factory()->create( $league, $this->settings() );

		$this->assertSame( $expected_seeds, $championship->num_seeds );
	}

	public function test_consolation_never_has_seeds(): void {
		$primary = $this->make_primary_league_with_championship();
		$qs      = $this->createMock( League_Query_Service::class );
		$qs->method( 'get_matches' )->willReturn( 0 );

		$championship = $this->factory( $this->repo_returning( $primary ), $qs )->create( $this->consolation_league( 6 ), $this->settings() );

		$this->assertSame( 0, $championship->num_seeds );
	}

	// ---------------------------------------------------------------- groups.

	public function test_group_mode_derives_rounds_from_group_advancers_and_adds_third_place(): void {
		$settings = new Championship_Settings( array( 'A', 'B' ), 4, 2, true );
		$league   = $this->make_league( 1, 8 );

		$championship = $this->factory()->create( $league, $settings );

		$this->assertSame( 2, $championship->num_advance );
		$this->assertSame( 2, $championship->num_rounds );
		$this->assertSame( 4, $championship->num_teams_first_round );
		$this->assertSame( array( 1 => 'semi', 2 => 'final' ), $this->keys_by_round( $championship ) );

		$finals = $championship->finals;
		$this->assertSame( array( 'final', 'third', 'semi' ), array_keys( $finals ) );
		$this->assertSame( 2, $finals['third']['round'] );

		$final_teams = $championship->final_teams;
		$this->assertSame( array( '1_A', '2_A', '1_B', '2_B' ), array_keys( $final_teams['semi'] ) );
		$this->assertSame( '1. Group A', $final_teams['semi']['1_A']->title );
		$this->assertSame( array( '1_semi_1', '1_semi_2' ), array_keys( $final_teams['final'] ) );
		$this->assertSame( array( '2_semi_1', '2_semi_2' ), array_keys( $final_teams['third'] ) );
	}

	// ---------------------------------------------------------------- current final.

	public function test_requested_final_takes_precedence_over_get_and_default(): void {
		$_GET['final'] = 'semi';
		$league        = $this->make_league( 1, 6 );

		$this->assertSame( 'final', $this->factory()->create( $league, $this->settings(), 'final' )->current_final );
	}

	public function test_get_final_is_used_when_no_requested_final(): void {
		$_GET['final'] = 'semi';
		$league        = $this->make_league( 1, 6 );

		$this->assertSame( 'semi', $this->factory()->create( $league, $this->settings() )->current_final );
		$this->assertSame( 'semi', $this->factory()->create( $league, $this->settings(), '' )->current_final );
	}

	public function test_default_final_is_first_round_key(): void {
		$league = $this->make_league( 1, 6 );

		$this->assertSame( 'quarter', $this->factory()->create( $league, $this->settings() )->current_final );
	}

	/**
	 * Rebuild keys-by-round from the finals (Championship exposes keys through get_final_keys()).
	 */
	private function keys_by_round( Championship $championship ): array {
		$keys = array();
		for ( $round = 1; $round <= $championship->num_rounds; $round++ ) {
			$keys[ $round ] = $championship->get_final_keys( $round );
		}
		return $keys;
	}
}
