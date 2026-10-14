<?php

namespace Racketmanager\Tests\Unit\Repositories {

	use PHPUnit\Framework\TestCase;
	use Racketmanager\Repositories\Fixture_Repository;
	use Racketmanager\Services\Export\DTO\Export_Criteria;
	use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
	use wpdb;

	class Fixture_Repository_Criteria_Test extends TestCase {
		use Manages_Wordpress_Globals;

		private $wpdb;
		private Fixture_Repository $repository;

		protected function setUp(): void {
			parent::setUp();
			$this->backup_globals();
			$this->wpdb = $this->createMock( wpdb::class );
			$this->wpdb->prefix = 'wp_';
			$GLOBALS['wpdb']    = $this->wpdb;

			$this->repository = new Fixture_Repository();
		}

		protected function tearDown(): void {
			$this->restore_globals();
			parent::tearDown();
		}

	public function test_find_by_criteria_generates_correct_sql() {
		$criteria = new Export_Criteria( array(
			'league_id' => 123,
			'season'    => '2023-24',
		) );

		// Mock prepare to return the SQL as is for simplicity in this test
		$this->wpdb->method( 'prepare' )->willReturnCallback( function( $sql, ...$args ) {
			return vsprintf( str_replace( '%s', "'%s'", str_replace( '%d', '%d', $sql ) ), $args );
		} );

		$expected_sql = "SELECT id FROM wp_racketmanager_matches AS m WHERE 1=1 AND m.league_id = 123 AND m.season = '2023-24' ORDER BY m.match_day ASC, m.date ASC";

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->with( $this->equalTo( $expected_sql ) )
			->willReturn( array() );

		$this->repository->find_by_criteria( $criteria );
	}

	public function test_find_by_criteria_with_club_id() {
		$criteria = new Export_Criteria( array(
			'club_id' => 789,
		) );

		$this->wpdb->method( 'prepare' )->willReturnCallback( function( $sql, ...$args ) {
			return vsprintf( str_replace( '%s', "'%s'", str_replace( '%d', '%d', $sql ) ), $args );
		} );

		$expected_sql = "SELECT id FROM wp_racketmanager_matches AS m WHERE 1=1 AND  (m.home_team IN (SELECT id FROM wp_racketmanager_teams WHERE club_id = 789) OR m.away_team IN (SELECT id FROM wp_racketmanager_teams WHERE club_id = 789)) ORDER BY m.match_day ASC, m.date ASC";

		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->with( $this->equalTo( $expected_sql ) )
			->willReturn( array() );

		$this->repository->find_by_criteria( $criteria );
	}
}
}
