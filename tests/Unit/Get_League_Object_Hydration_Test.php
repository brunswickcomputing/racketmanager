<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Runs the real production get_league() (the in-process test stubs replace it) in a subprocess.
 */
class Get_League_Object_Hydration_Test extends TestCase {

	public function test_get_league_object_path_refreshes_team_count_and_championship(): void {
		$script = dirname( __DIR__ ) . '/Fixtures/production-get-league-object-path.php';
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ) . ' 2>&1', $output, $exit_code );

		$this->assertSame( 0, $exit_code, implode( "\n", $output ) );
		$this->assertSame( 'ok', implode( "\n", $output ) );
	}
}
