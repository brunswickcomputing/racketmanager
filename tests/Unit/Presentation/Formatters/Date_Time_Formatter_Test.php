<?php
/**
 * Date Time Formatter Test
 *
 * @package RacketManager
 * @subpackage Tests/Unit/Presentation/Formatters
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Formatters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Formatters\Date_Time_Formatter;

final class Date_Time_Formatter_Test extends TestCase {

    public function test_formats_valid_match_start_time(): void {
        self::assertSame( '19:30', Date_Time_Formatter::format_match_time( '19:30' ) );
        self::assertSame( '10:00:00', Date_Time_Formatter::format_match_time( '10:00:00' ) );
    }

    public function test_formats_zero_or_empty_time_as_empty_string(): void {
        self::assertSame( '', Date_Time_Formatter::format_match_time( '00:00' ) );
        self::assertSame( '', Date_Time_Formatter::format_match_time( null ) );
        self::assertSame( '', Date_Time_Formatter::format_match_time( '' ) );
    }
}
