<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Competition\Competition_Summary_DTO;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Competition_Repository_Summary_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_find_competitions_with_summary_returns_hydrated_dtos(): void {
		$row1 = (object) array(
			'id'           => 1,
			'name'         => 'Summer League',
			'age_group'    => 'adults',
			'type'         => 'league',
			'season_count' => 3,
			'event_count'  => 5,
		);

		$row2 = (object) array(
			'id'           => 2,
			'name'         => 'Junior Cup',
			'age_group'    => 'juniors',
			'type'         => 'cup',
			'season_count' => 1,
			'event_count'  => 2,
		);

		$wpdb = new class( array( $row1, $row2 ) ) extends wpdb {
			public array $queries = array();

			public function __construct( private readonly array $rows ) {
				$this->prefix = 'wp_';
			}

			public function prepare( $query, ...$args ): string {
				$this->queries[] = array(
					'query' => $query,
					'args'  => $args,
				);
				return (string) $query;
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				return $this->rows;
			}
		};

		$GLOBALS['wpdb'] = $wpdb;

		$repository   = new Competition_Repository();
		$competitions = $repository->find_competitions_with_summary();

		$this->assertCount( 2, $competitions );
		$this->assertContainsOnlyInstancesOf( Competition_Summary_DTO::class, $competitions );

		$this->assertSame( 1, $competitions[0]->get_id() );
		$this->assertSame( 'Summer League', $competitions[0]->get_name() );
		$this->assertSame( 'adults', $competitions[0]->get_age_group() );
		$this->assertSame( 'league', $competitions[0]->get_type() );
		$this->assertSame( 3, $competitions[0]->get_season_count() );
		$this->assertSame( 5, $competitions[0]->get_event_count() );

		$this->assertSame( 2, $competitions[1]->get_id() );
		$this->assertSame( 'Junior Cup', $competitions[1]->get_name() );
		$this->assertSame( 'juniors', $competitions[1]->get_age_group() );
		$this->assertSame( 'cup', $competitions[1]->get_type() );
		$this->assertSame( 1, $competitions[1]->get_season_count() );
		$this->assertSame( 2, $competitions[1]->get_event_count() );
	}

	public function test_find_competitions_with_summary_applies_where_filters(): void {
		$captured_query = '';
		$captured_args  = array();

		$wpdb = new class( $captured_query, $captured_args ) extends wpdb {
			public function __construct(
				public string &$captured_query,
				public array &$captured_args
			) {
				$this->prefix = 'wp_';
			}

			public function prepare( $query, ...$args ): string {
				$this->captured_query = (string) $query;
				$this->captured_args  = $args;
				return (string) $query;
			}

			public function get_results( $query = null, $output = 'OBJECT' ): array {
				return array();
			}
		};

		$GLOBALS['wpdb'] = $wpdb;

		$repository   = new Competition_Repository();
		$competitions = $repository->find_competitions_with_summary( 'juniors', 'tournament' );

		$this->assertSame( array(), $competitions );
		$this->assertStringContainsString( 'c.age_group = %s', $captured_query );
		$this->assertStringContainsString( 'c.type = %s', $captured_query );
		$this->assertSame( array( array( 'juniors', 'tournament' ) ), $captured_args );
	}

	public function test_find_competitions_with_summary_handles_empty_or_null_results(): void {
		$wpdb = new class extends wpdb {
			public function __construct() {
				$this->prefix = 'wp_';
			}

			public function get_results( $query = null, $output = 'OBJECT' ): ?array {
				return null;
			}
		};

		$GLOBALS['wpdb'] = $wpdb;

		$repository   = new Competition_Repository();
		$competitions = $repository->find_competitions_with_summary();

		$this->assertSame( array(), $competitions );
	}
}
