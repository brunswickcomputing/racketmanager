<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Formatters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Formatters\Email_Date_Formatter;

final class Email_Date_Formatter_Test extends TestCase {

    public function test_date_returns_empty_string_for_null_empty_and_zero_dates(): void {
        $formatter = new Email_Date_Formatter();

        $this->assertSame( '', $formatter->date( null ) );
        $this->assertSame( '', $formatter->date( '' ) );
        $this->assertSame( '', $formatter->date( '0000-00-00' ) );
        $this->assertSame( '', $formatter->date( '0000-00-00 00:00:00' ) );
    }

    public function test_date_uses_option_gateway_format_and_explicit_format(): void {
        $gateway = $this->createStub( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $option, mixed $default = false ) => 'date_format' === $option ? 'Y-m-d' : $default
        );

        $formatter = new Email_Date_Formatter( $gateway );

        $this->assertSame( '2026-10-02', $formatter->date( '2026-10-02 19:30:00' ) );
        $this->assertSame( '02 Oct 26', $formatter->date( '2026-10-02', Email_Date_Formatter::FIXTURE_LIST_DATE_FORMAT ) );
        $this->assertSame( 'Fri', $formatter->date( '2026-10-02', Email_Date_Formatter::FIXTURE_LIST_DAY_FORMAT ) );
    }

    public function test_time_returns_empty_for_zero_dates_and_formatted_time_for_valid_dates(): void {
        $gateway = $this->createStub( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $option, mixed $default = false ) => 'time_format' === $option ? 'H:i' : $default
        );

        $formatter = new Email_Date_Formatter( $gateway );

        $this->assertSame( '', $formatter->time( null ) );
        $this->assertSame( '', $formatter->time( '0000-00-00 00:00:00' ) );
        $this->assertSame( '19:30', $formatter->time( '2026-10-02 19:30:00' ) );
    }

    public function test_date_change_uses_site_date_format_when_no_start_time_and_date_time_format_when_present(): void {
        $gateway = $this->createStub( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturnCallback(
            fn( string $option, mixed $default = false ) => 'date_format' === $option ? 'd/m/Y' : $default
        );

        $formatter = new Email_Date_Formatter( $gateway );

        // No start time => site date format
        $this->assertSame( '02/10/2026', $formatter->date_change( '2026-10-02 00:00:00', null ) );
        $this->assertSame( '02/10/2026', $formatter->date_change( '2026-10-02', null ) );
        $this->assertSame( '02/10/2026', $formatter->date_change( '2026-10-02' ) );

        // Start time present in datetime and omitted/null => DATE_TIME_FORMAT ('j F Y H:i')
        $this->assertSame( '2 October 2026 19:30', $formatter->date_change( '2026-10-02 19:30:00', null ) );
        $this->assertSame( '2 October 2026 19:30', $formatter->date_change( '2026-10-02 19:30:00' ) );

        // Explicit valid start time overrides datetime
        $this->assertSame( '2 October 2026 18:00', $formatter->date_change( '2026-10-02', '18:00' ) );
        $this->assertSame( '2 October 2026 18:00', $formatter->date_change( '2026-10-02 19:30:00', '18:00' ) );
        $this->assertSame( '2 October 2026 18:00', $formatter->date_change( '2026-10-02 19:30:00', ' 18:00:00 ' ) );

        // Explicit midnight or empty clears time component even on timed datetimes
        $this->assertSame( '02/10/2026', $formatter->date_change( '2026-10-02 19:30:00', '' ) );
        $this->assertSame( '02/10/2026', $formatter->date_change( '2026-10-02 19:30:00', '00:00' ) );
        $this->assertSame( '02/10/2026', $formatter->date_change( '2026-10-02 19:30:00', '00:00:00' ) );
        $this->assertSame( '02/10/2026', $formatter->date_change( '2026-10-02 19:30:00', ' 00:00 ' ) );

        // Explicit invalid time falls back to date-derived time or date-only without blanking date
        $this->assertSame( '02/10/2026', $formatter->date_change( '2026-10-02', '7pm' ) );
        $this->assertSame( '2 October 2026 19:30', $formatter->date_change( '2026-10-02 19:30:00', '7pm' ) );
        $this->assertSame( '2 October 2026 19:30', $formatter->date_change( '2026-10-02 19:30:00', 'invalid' ) );
        $this->assertSame( '2 October 2026 19:30', $formatter->date_change( '2026-10-02 19:30:00', '25:00' ) );

        // Null/zero date => empty string
        $this->assertSame( '', $formatter->date_change( null, '19:30' ) );
        $this->assertSame( '', $formatter->date_change( null ) );
        $this->assertSame( '', $formatter->date_change( '0000-00-00 00:00:00', '19:30' ) );
        $this->assertSame( '', $formatter->date_change( '0000-00-00 00:00:00' ) );
    }

    public function test_start_time_hides_empty_and_midnight_markers(): void {
        $formatter = new Email_Date_Formatter();

        $this->assertSame( '', $formatter->start_time( null ) );
        $this->assertSame( '', $formatter->start_time( '' ) );
        $this->assertSame( '', $formatter->start_time( '00:00' ) );
        $this->assertSame( '19:30', $formatter->start_time( '19:30' ) );
        $this->assertSame( '10:00:00', $formatter->start_time( '10:00:00' ) );
    }

    public function test_empty_option_value_falls_back_to_defaults(): void {
        $gateway = $this->createStub( Option_Gateway_Interface::class );
        $gateway->method( 'get_option' )->willReturn( '' );

        $formatter = new Email_Date_Formatter( $gateway );

        $this->assertSame( 'd/m/Y', $formatter->date_format() );
        $this->assertSame( 'H:i', $formatter->time_format() );
    }
}
