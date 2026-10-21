<?php
/**
 * Currency Formatter
 *
 * @package RacketManager
 * @subpackage Presentation/Formatters
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\Formatters;

use NumberFormatter;
use Racketmanager\Util\Util;

/**
 * Currency formatting for presentation templates, view models, and views.
 */
final readonly class Currency_Formatter {

    /**
     * Format a currency amount according to locale and currency code.
     *
     * @param string|float|int|null $amount        Amount to format.
     * @param string|null           $currency_code Optional ISO currency code override.
     * @param string|null           $locale        Optional locale string override.
     * @return string Formatted currency string.
     */
    public static function format_currency( string|float|int|null $amount, ?string $currency_code = null, ?string $locale = null ): string {
        $numeric_amount = ( null === $amount || '' === (string) $amount || ! is_numeric( (string) $amount ) ) ? 0.0 : (float) $amount;
        $loc            = $locale ?? ( function_exists( 'get_locale' ) ? get_locale() : 'en_GB' );
        $code           = $currency_code ?? Util::get_currency_code();

        if ( null !== $locale && '' !== $locale ) {
            $formatter = numfmt_create( $locale, NumberFormatter::CURRENCY );
            if ( false !== $formatter && null !== $formatter ) {
                $formatted = numfmt_format_currency( $formatter, $numeric_amount, $code );
                if ( false !== $formatted ) {
                    return $formatted;
                }
            }
        } else {
            $formatter = Util::get_currency_format();
            if ( is_object( $formatter ) && $formatter instanceof NumberFormatter ) {
                $formatted = numfmt_format_currency( $formatter, $numeric_amount, $code );
                if ( false !== $formatted ) {
                    return $formatted;
                }
            }
        }

        $fallback_formatter = numfmt_create( $loc, NumberFormatter::CURRENCY );
        if ( false !== $fallback_formatter && null !== $fallback_formatter ) {
            $formatted = numfmt_format_currency( $fallback_formatter, $numeric_amount, $code );
            if ( false !== $formatted ) {
                return $formatted;
            }
        }

        return $code . ' ' . number_format( $numeric_amount, 2 );
    }
}
