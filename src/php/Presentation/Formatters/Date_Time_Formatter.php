<?php
/**
 * Date Time Formatter
 *
 * @package RacketManager
 * @subpackage Presentation/Formatters
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\Formatters;

/**
 * Date and time formatting for presentation templates, view models, and views.
 */
final readonly class Date_Time_Formatter {

    /**
     * Format match start time.
     *
     * @param mixed $start_time Match start time string or object (e.g. '19:30' or '00:00').
     * @return string Formatted time string or empty string when 00:00 or empty.
     */
    public static function format_match_time( mixed $start_time ): string {
        if ( null === $start_time || '' === $start_time || '00:00' === $start_time ) {
            return '';
        }

        return (string) $start_time;
    }
}
