<?php
/**
 * League import mapper unit tests
 *
 * @package RacketManager
 * @subpackage Tests/Unit/Services/Admin/Import
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Import;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Admin\Import\League_Import_Mapper;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../../wp-stubs.php';

/**
 * Test case for League_Import_Mapper.
 */
final class League_Import_Mapper_Test extends TestCase {
	use Manages_Wordpress_Globals;

	/**
	 * Instance under test.
	 *
	 * @var League_Import_Mapper
	 */
	private League_Import_Mapper $mapper;

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		$this->mapper = new League_Import_Mapper();
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	/**
	 * Tests mapping matches with single-column keyed fields.
	 */
	public function test_map_matches_with_single_column_keyed_fields(): void {
		$fields = array(
			'score' => array(
				'keys' => array( 'home', 'away' ),
			),
		);
		$line   = array( 'Match 1', '2026-05-01', '6-4' );
		$custom = array();

		$result = $this->mapper->map_matches( $custom, $line, 101, 2, $fields );

		$this->assertSame(
			array(
				101 => array(
					'score' => array(
						'home' => '6',
						'away' => '4',
					),
				),
			),
			$result
		);
	}

	/**
	 * Tests mapping matches with multi-column nested keys.
	 */
	public function test_map_matches_with_multi_column_nested_keys(): void {
		$fields = array(
			'sets' => array(
				'keys' => array(
					'set_1' => array( 'home', 'away' ),
					'set_2' => array( 'home', 'away' ),
					'set_3' => array( 'home', 'away' ),
				),
			),
		);
		$line   = array( 'Match 1', '6-4', '3-6', '7-5' );
		$custom = array();

		$result = $this->mapper->map_matches( $custom, $line, 102, 1, $fields );

		$this->assertSame(
			array(
				102 => array(
					'sets' => array(
						'set_1' => array(
							'home' => '6',
							'away' => '4',
						),
						'set_2' => array(
							'home' => '3',
							'away' => '6',
						),
						'set_3' => array(
							'home' => '7',
							'away' => '5',
						),
					),
				),
			),
			$result
		);
	}

	/**
	 * Tests mapping matches with unkeyed fields.
	 */
	public function test_map_matches_with_unkeyed_fields(): void {
		$fields = array(
			'notes' => array(
				'label' => 'Match Notes',
			),
		);
		$line   = array( 'M1', 'Court 1', 'Delayed due to rain' );
		$custom = array();

		$result = $this->mapper->map_matches( $custom, $line, 103, 2, $fields );

		$this->assertSame(
			array(
				103 => array(
					'notes' => 'Delayed due to rain',
				),
			),
			$result
		);
	}

	/**
	 * Tests mapping matches with missing columns.
	 */
	public function test_map_matches_handles_missing_columns_gracefully(): void {
		$fields = array(
			'sets'  => array(
				'keys' => array(
					'set_1' => array( 'home', 'away' ),
					'set_2' => array( 'home', 'away' ),
				),
			),
			'notes' => array(
				'label' => 'Notes',
			),
		);
		// CSV line is shorter than expected columns starting at index 2.
		$line   = array( 'M1', 'Day 1' );
		$custom = array();

		$result = $this->mapper->map_matches( $custom, $line, 104, 2, $fields );

		$this->assertSame(
			array(
				104 => array(
					'sets'  => array(
						'set_1' => array(
							'home' => '',
							'away' => '',
						),
						'set_2' => array(
							'home' => '',
							'away' => '',
						),
					),
					'notes' => '',
				),
			),
			$result
		);
	}

	/**
	 * Tests sport filter hook application on matches mapping.
	 */
	public function test_map_matches_applies_sport_filter_hook(): void {
		$fields = array(
			'score' => array(
				'keys' => array( 'home', 'away' ),
			),
		);
		$line   = array( 'M1', '6-3' );

		add_filter(
			'racketmanager_import_matches_tennis',
			function ( array $custom, array $csv_line, int $match_id, int $col ): array {
				$custom[ $match_id ]['custom_hook_data'] = 'applied';
				$custom[ $match_id ]['col_at_hook']      = $col;
				return $custom;
			},
			10,
			4
		);

		$result = $this->mapper->map_matches( array(), $line, 201, 1, $fields, 'tennis' );

		$this->assertSame(
			array(
				201 => array(
					'score'            => array(
						'home' => '6',
						'away' => '3',
					),
					'custom_hook_data' => 'applied',
					'col_at_hook'      => 2,
				),
			),
			$result
		);
	}

	/**
	 * Tests preserving existing custom data in matches mapping.
	 */
	public function test_map_matches_preserves_existing_custom_data(): void {
		$fields         = array(
			'notes' => array( 'label' => 'Notes' ),
		);
		$initial_custom = array(
			99 => array( 'notes' => 'Existing match data' ),
		);
		$line           = array( 'M2', 'New match data' );

		$result = $this->mapper->map_matches( $initial_custom, $line, 100, 1, $fields );

		$this->assertSame( 'Existing match data', $result[99]['notes'] );
		$this->assertSame( 'New match data', $result[100]['notes'] );
	}

	/**
	 * Tests mapping teams with keyed fields.
	 */
	public function test_map_teams_with_keyed_fields(): void {
		$fields = array(
			'games' => array(
				'keys' => array( 'won', 'lost' ),
			),
		);
		$line   = array( 'Team Alpha', '12-4' );
		$custom = array();

		$result = $this->mapper->map_teams( $custom, $line, 1, $fields );

		$this->assertSame(
			array(
				'games' => array(
					'won'  => '12',
					'lost' => '4',
				),
			),
			$result
		);
	}

	/**
	 * Tests mapping teams with unkeyed fields.
	 */
	public function test_map_teams_with_unkeyed_fields(): void {
		$fields = array(
			'bonus_points' => array(
				'label' => 'Bonus',
			),
		);
		$line   = array( 'Team Beta', '3' );
		$custom = array();

		$result = $this->mapper->map_teams( $custom, $line, 1, $fields );

		$this->assertSame(
			array(
				'bonus_points' => '3',
			),
			$result
		);
	}

	/**
	 * Tests mapping teams with missing columns and fallback default values.
	 */
	public function test_map_teams_handles_missing_columns_with_default_values(): void {
		$fields = array(
			'games'   => array(
				'keys' => array( 'won', 'lost' ),
			),
			'penalty' => array(
				'label' => 'Penalty Points',
			),
		);
		// Line has no columns at offset 1 or 2.
		$line   = array( 'Team Gamma' );
		$custom = array();

		$result = $this->mapper->map_teams( $custom, $line, 1, $fields );

		$this->assertSame(
			array(
				'games'   => array(
					'won'  => '',
					'lost' => '',
				),
				'penalty' => 0,
			),
			$result
		);
	}

	/**
	 * Tests sport filter hook application on teams mapping.
	 */
	public function test_map_teams_applies_sport_filter_hook(): void {
		$fields = array(
			'points' => array( 'label' => 'Pts' ),
		);
		$line   = array( 'Team Delta', '10' );

		add_filter(
			'racketmanager_import_teams_tennis',
			function ( array $custom, array $csv_line, int $col ): array {
				$custom['tennis_special'] = 'active';
				$custom['final_col']      = $col;
				return $custom;
			},
			10,
			3
		);

		$result = $this->mapper->map_teams( array(), $line, 1, $fields, 'tennis' );

		$this->assertSame(
			array(
				'points'         => '10',
				'tennis_special' => 'active',
				'final_col'      => 2,
			),
			$result
		);
	}

	/**
	 * Tests empty fields definitions returning unmodified custom array.
	 */
	public function test_empty_fields_returns_unmodified_custom_when_no_sport(): void {
		$line   = array( 'Team Epsilon' );
		$custom = array( 'pre_existing' => 'value' );

		$result_teams = $this->mapper->map_teams( $custom, $line, 1, array() );
		$this->assertSame( $custom, $result_teams );

		$result_matches = $this->mapper->map_matches( array( 1 => $custom ), $line, 1, 1, array() );
		$this->assertSame( array( 1 => $custom ), $result_matches );
	}
}
