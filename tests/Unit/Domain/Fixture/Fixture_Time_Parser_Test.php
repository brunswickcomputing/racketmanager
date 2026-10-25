<?php

namespace Racketmanager\Tests\Unit\Domain\Fixture;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture\Fixture_Time_Parser;

class Fixture_Time_Parser_Test extends TestCase {

    public function test_is_midnight_or_empty(): void {
        $this->assertTrue( Fixture_Time_Parser::is_midnight_or_empty( null ) );
        $this->assertTrue( Fixture_Time_Parser::is_midnight_or_empty( '' ) );
        $this->assertTrue( Fixture_Time_Parser::is_midnight_or_empty( '   ' ) );
        $this->assertTrue( Fixture_Time_Parser::is_midnight_or_empty( '00:00' ) );
        $this->assertTrue( Fixture_Time_Parser::is_midnight_or_empty( '00:00:00' ) );

        $this->assertFalse( Fixture_Time_Parser::is_midnight_or_empty( '19:30' ) );
        $this->assertFalse( Fixture_Time_Parser::is_midnight_or_empty( '00:01' ) );
    }

    public function test_is_valid_time_format(): void {
        $this->assertTrue( Fixture_Time_Parser::is_valid_time_format( '00:00' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_time_format( '09:15' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_time_format( '19:30' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_time_format( '23:59' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_time_format( '19:30:00' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_time_format( '  18:45  ' ) );

        $this->assertFalse( Fixture_Time_Parser::is_valid_time_format( '24:00' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_time_format( '12:60' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_time_format( '7pm' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_time_format( '19.30' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_time_format( 'abc' ) );
    }

    public function test_extract_time_from_datetime(): void {
        $this->assertNull( Fixture_Time_Parser::extract_time_from_datetime( null ) );
        $this->assertNull( Fixture_Time_Parser::extract_time_from_datetime( '' ) );
        $this->assertNull( Fixture_Time_Parser::extract_time_from_datetime( '0000-00-00 00:00:00' ) );
        $this->assertNull( Fixture_Time_Parser::extract_time_from_datetime( '2026-05-15' ) );
        $this->assertNull( Fixture_Time_Parser::extract_time_from_datetime( '2026-05-15 00:00:00' ) );
        $this->assertNull( Fixture_Time_Parser::extract_time_from_datetime( '2026-05-15 00:00' ) );
        $this->assertNull( Fixture_Time_Parser::extract_time_from_datetime( '2026-05-15T00:00:00' ) );

        $this->assertSame( '19:30', Fixture_Time_Parser::extract_time_from_datetime( '2026-05-15 19:30:00' ) );
        $this->assertSame( '19:30', Fixture_Time_Parser::extract_time_from_datetime( '2026-05-15 19:30' ) );
        $this->assertSame( '19:30', Fixture_Time_Parser::extract_time_from_datetime( '2026-05-15T19:30:00' ) );
        $this->assertSame( '08:00', Fixture_Time_Parser::extract_time_from_datetime( '2026-05-15 08:00:00' ) );
    }

    public function test_normalize_start_time(): void {
        $this->assertNull( Fixture_Time_Parser::normalize_start_time( null ) );
        $this->assertNull( Fixture_Time_Parser::normalize_start_time( '' ) );
        $this->assertNull( Fixture_Time_Parser::normalize_start_time( '   ' ) );
        $this->assertNull( Fixture_Time_Parser::normalize_start_time( '00:00' ) );
        $this->assertNull( Fixture_Time_Parser::normalize_start_time( '00:00:00' ) );

        $this->assertSame( '19:30', Fixture_Time_Parser::normalize_start_time( '19:30' ) );
        $this->assertSame( '19:30', Fixture_Time_Parser::normalize_start_time( '19:30:00' ) );
        $this->assertSame( '19:30', Fixture_Time_Parser::normalize_start_time( '  19:30:00  ' ) );
    }

    public function test_normalize_start_time_throws_on_invalid_input(): void {
        $this->expectException( InvalidArgumentException::class );
        Fixture_Time_Parser::normalize_start_time( '7pm' );
    }

    public function test_merge_time_into_date(): void {
        $this->assertNull( Fixture_Time_Parser::merge_time_into_date( null, '18:30' ) );
        $this->assertSame( '', Fixture_Time_Parser::merge_time_into_date( '', '18:30' ) );
        $this->assertSame( '0000-00-00 00:00:00', Fixture_Time_Parser::merge_time_into_date( '0000-00-00 00:00:00', '18:30' ) );

        // Date-only with time
        $this->assertSame( '2026-06-15 18:30:00', Fixture_Time_Parser::merge_time_into_date( '2026-06-15', '18:30' ) );

        // Datetime with new time
        $this->assertSame( '2026-06-15 18:30:00', Fixture_Time_Parser::merge_time_into_date( '2026-06-15 10:00:00', '18:30' ) );

        // Datetime with null time (resets time to 00:00:00)
        $this->assertSame( '2026-06-15 00:00:00', Fixture_Time_Parser::merge_time_into_date( '2026-06-15 10:00:00', null ) );

        // Date-only with null time (preserves date-only)
        $this->assertSame( '2026-06-15', Fixture_Time_Parser::merge_time_into_date( '2026-06-15', null ) );
    }

    public function test_to_mysql_datetime(): void {
        $this->assertNull( Fixture_Time_Parser::to_mysql_datetime( null ) );
        $this->assertNull( Fixture_Time_Parser::to_mysql_datetime( '' ) );
        $this->assertNull( Fixture_Time_Parser::to_mysql_datetime( '   ' ) );
        $this->assertSame( '0000-00-00 00:00:00', Fixture_Time_Parser::to_mysql_datetime( '0000-00-00' ) );
        $this->assertSame( '0000-00-00 00:00:00', Fixture_Time_Parser::to_mysql_datetime( '0000-00-00 00:00:00' ) );

        // Standard datetimes
        $this->assertSame( '2026-05-15 19:30:00', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15 19:30:00' ) );
        $this->assertSame( '2026-05-15 19:30:45', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15 19:30:45' ) );
        $this->assertSame( '2026-05-15 19:30:00', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15 19:30' ) );
        $this->assertSame( '2026-05-15 00:00:00', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15' ) );
        $this->assertSame( '2026-05-15 00:00:00', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15 00:00:00' ) );

        // ISO formats without timezone conversion
        $this->assertSame( '2026-05-15 19:30:00', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15T19:30' ) );
        $this->assertSame( '2026-05-15 19:30:00', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15T19:30:00' ) );
        $this->assertSame( '2026-05-15 19:30:00', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15T19:30:00+01:00' ) );
        $this->assertSame( '2026-05-15 19:30:00', Fixture_Time_Parser::to_mysql_datetime( '2026-05-15T19:30:00Z' ) );
    }

    public function test_is_valid_datetime(): void {
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( null ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '   ' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '0000-00-00' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '0000-00-00 00:00:00' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '2026-05-15' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '2026-05-15 19:30' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '2026-05-15 19:30:00' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '2026-05-15T19:30:00' ) );
        $this->assertTrue( Fixture_Time_Parser::is_valid_datetime( '2026-05-15T19:30:00+01:00' ) );

        $this->assertFalse( Fixture_Time_Parser::is_valid_datetime( 'garbage' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_datetime( '2026-13-45 25:99' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_datetime( '15/05/2026 19:30' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_datetime( '2026-05-15 19:30:00junk' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_datetime( '2026-02-30 10:00:00' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_datetime( '2026-05-15 24:00:00' ) );
        $this->assertFalse( Fixture_Time_Parser::is_valid_datetime( '2026-05-15 12:60:00' ) );
    }

    #[DataProvider( 'invalid_datetime_provider' )]
    public function test_to_mysql_datetime_throws_on_invalid_input( string $invalid_datetime ): void {
        $this->expectException( InvalidArgumentException::class );
        Fixture_Time_Parser::to_mysql_datetime( $invalid_datetime );
    }

    public static function invalid_datetime_provider(): array {
        return [
            'garbage text'          => [ 'garbage' ],
            'invalid month and day' => [ '2026-13-45 25:99' ],
            'uk date format'        => [ '15/05/2026 19:30' ],
            'trailing junk'         => [ '2026-05-15 19:30:00junk' ],
            'non-existent leap day' => [ '2026-02-29 12:00:00' ],
            'invalid hour'          => [ '2026-05-15 24:00:00' ],
            'invalid minute'        => [ '2026-05-15 12:60:00' ],
            'invalid second'        => [ '2026-05-15 12:00:60' ],
        ];
    }
}
