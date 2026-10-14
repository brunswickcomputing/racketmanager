<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Admin\League\Match_Date_Round_Validator;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Match_Date_Round_Validator_Test extends TestCase {

    public function test_valid_rounds_collect_dates_in_order(): void {
        $result = Match_Date_Round_Validator::validate(
            array(
                array( 'round' => '1', 'match_date' => '2026-04-01' ),
                array( 'round' => '2', 'match_date' => '2026-04-08' ),
            )
        );

        self::assertTrue( $result['valid'] );
        self::assertSame( array( '2026-04-01', '2026-04-08' ), $result['dates'] );
        self::assertNull( $result['message'] );
    }

    public function test_missing_and_out_of_order_dates_are_invalid(): void {
        $result = Match_Date_Round_Validator::validate(
            array(
                array( 'round' => '1', 'match_date' => '' ),
                array( 'round' => '2', 'match_date' => '2026-04-01' ),
                array( 'round' => '3', 'match_date' => '2026-03-20' ),
            )
        );

        self::assertFalse( $result['valid'] );
        self::assertStringContainsString( 'Match date missing for round 1', strval( $result['message'] ) );
        self::assertStringContainsString( 'Match date for round 3 after next round date', strval( $result['message'] ) );
    }
}
