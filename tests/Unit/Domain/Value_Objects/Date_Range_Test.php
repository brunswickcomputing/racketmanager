<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Value_Objects;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Value_Objects\Date_Range;

/**
 * @covers \Racketmanager\Domain\Value_Objects\Date_Range
 */
final class Date_Range_Test extends TestCase {

    public function test_create_and_accessors(): void {
        $range = Date_Range::create( '2026-05-01', '2026-05-10' );

        self::assertSame( '2026-05-01', $range->get_start_string() );
        self::assertSame( '2026-05-10', $range->get_end_string() );
        self::assertInstanceOf( DateTimeImmutable::class, $range->get_start() );
        self::assertInstanceOf( DateTimeImmutable::class, $range->get_end() );
    }

    public function test_invalid_range_throws_exception(): void {
        $this->expectException( InvalidArgumentException::class );
        Date_Range::create( '2026-06-10', '2026-06-01' );
    }

    public function test_invalid_date_format_throws_exception(): void {
        $this->expectException( InvalidArgumentException::class );
        Date_Range::create( '2026-02-30', '2026-03-10' );
    }

    public function test_relative_date_string_throws_exception(): void {
        $this->expectException( InvalidArgumentException::class );
        Date_Range::create( 'next monday', '2026-03-10' );
    }

    public function test_contains_date(): void {
        $range = Date_Range::create( '2026-01-01', '2026-01-31' );

        self::assertTrue( $range->contains( '2026-01-01' ) );
        self::assertTrue( $range->contains( '2026-01-15' ) );
        self::assertTrue( $range->contains( '2026-01-31' ) );
        self::assertFalse( $range->contains( '2025-12-31' ) );
        self::assertFalse( $range->contains( '2026-02-01' ) );
    }

    public function test_past_status(): void {
        $fixed_now = new DateTimeImmutable( '2026-06-15 00:00:00' );

        $past = Date_Range::create( '2026-01-01', '2026-01-31' );
        self::assertTrue( $past->is_past( $fixed_now ) );

        $current = Date_Range::create( '2026-06-01', '2026-06-30' );
        self::assertFalse( $current->is_past( $fixed_now ) );

        $future = Date_Range::create( '2026-09-01', '2026-09-30' );
        self::assertFalse( $future->is_past( $fixed_now ) );
    }

    public function test_equality_and_json(): void {
        $a = Date_Range::create( '2026-03-01', '2026-03-05' );
        $b = Date_Range::create( '2026-03-01', '2026-03-05' );
        $c = Date_Range::create( '2026-03-01', '2026-03-06' );

        self::assertTrue( $a->equals( $b ) );
        self::assertFalse( $a->equals( $c ) );

        $json = $a->jsonSerialize();
        self::assertSame( '2026-03-01', $json['start'] );
        self::assertSame( '2026-03-05', $json['end'] );
    }

    public function test_try_create_safe_factory(): void {
        $valid = Date_Range::try_create( '2026-04-01', '2026-04-30' );
        self::assertNotNull( $valid );
        self::assertSame( '2026-04-01', $valid->get_start_string() );
        self::assertSame( '2026-04-30', $valid->get_end_string() );

        // Nulls and empty values
        self::assertNull( Date_Range::try_create( null, '2026-04-30' ) );
        self::assertNull( Date_Range::try_create( '2026-04-01', null ) );
        self::assertNull( Date_Range::try_create( '', '2026-04-30' ) );
        self::assertNull( Date_Range::try_create( '0000-00-00', '2026-04-30' ) );
        self::assertNull( Date_Range::try_create( '2026-04-01', '0000-00-00' ) );

        // Inverted dates (start > end)
        self::assertNull( Date_Range::try_create( '2026-05-01', '2026-04-01' ) );

        // Malformed / unparseable strings
        self::assertNull( Date_Range::try_create( 'invalid', '2026-04-01' ) );
        self::assertNull( Date_Range::try_create( '2026-04-01', 'not-a-date' ) );

        // Overflow dates and relative dates rejected strictly
        self::assertNull( Date_Range::try_create( '2026-02-30', '2026-03-10' ) );
        self::assertNull( Date_Range::try_create( 'next monday', '2026-03-10' ) );
    }

    public function test_try_parse_date(): void {
        self::assertNull( Date_Range::try_parse_date( null ) );
        self::assertNull( Date_Range::try_parse_date( '' ) );
        self::assertNull( Date_Range::try_parse_date( '0000-00-00' ) );
        self::assertNull( Date_Range::try_parse_date( '2026-02-30' ) );
        self::assertNull( Date_Range::try_parse_date( 'invalid' ) );

        $dt = Date_Range::try_parse_date( '2026-10-17' );
        self::assertNotNull( $dt );
        self::assertSame( '2026-10-17', $dt->format( 'Y-m-d' ) );
    }
}
