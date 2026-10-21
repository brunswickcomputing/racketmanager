<?php
/**
 * Currency Formatter Test
 *
 * @package RacketManager
 * @subpackage Tests/Unit/Presentation/Formatters
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Formatters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Formatters\Currency_Formatter;

final class Currency_Formatter_Test extends TestCase {

    public function test_formats_currency_amount_properly(): void {
        $formatted = Currency_Formatter::format_currency( '25.00', 'GBP', 'en_GB' );
        self::assertSame( '£25.00', $formatted );
    }

    public function test_formats_non_default_locale_and_currency_explicitly(): void {
        $formatted_de = Currency_Formatter::format_currency( 1234.5, 'EUR', 'de_DE' );
        // de_DE formats as "1.234,50\xc2\xa0€"
        self::assertSame( "1.234,50\xc2\xa0€", $formatted_de );

        $formatted_us = Currency_Formatter::format_currency( 1234.5, 'USD', 'en_US' );
        self::assertSame( '$1,234.50', $formatted_us );
    }

    public function test_formats_null_or_empty_or_malformed_amount_as_zero(): void {
        $formatted_null = Currency_Formatter::format_currency( null, 'GBP', 'en_GB' );
        self::assertSame( '£0.00', $formatted_null );

        $formatted_empty = Currency_Formatter::format_currency( '', 'GBP', 'en_GB' );
        self::assertSame( '£0.00', $formatted_empty );

        $formatted_malformed = Currency_Formatter::format_currency( 'abc', 'GBP', 'en_GB' );
        self::assertSame( '£0.00', $formatted_malformed );
    }

    public function test_formats_numeric_string_and_float_and_int_amounts(): void {
        $formatted_str = Currency_Formatter::format_currency( '12.5', 'GBP', 'en_GB' );
        self::assertSame( '£12.50', $formatted_str );

        $formatted_float = Currency_Formatter::format_currency( 15.50, 'GBP', 'en_GB' );
        self::assertSame( '£15.50', $formatted_float );

        $formatted_int = Currency_Formatter::format_currency( 100, 'GBP', 'en_GB' );
        self::assertSame( '£100.00', $formatted_int );
    }

    public function test_formats_default_currency_amount(): void {
        $formatted = Currency_Formatter::format_currency( '12.5' );
        self::assertSame( '£12.50', $formatted );
    }
}
