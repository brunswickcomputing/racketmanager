<?php
declare( strict_types=1 );
/**
 * Fixture API: Fixture_Time_Parser class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain\Fixture
 */

namespace Racketmanager\Domain\Fixture;

use InvalidArgumentException;

/**
 * Utility for parsing, validating, normalizing, and merging fixture dates and start times.
 */
final class Fixture_Time_Parser {

    /**
     * Check if a time string represents midnight, empty, or null (i.e. no specific start time).
     *
     * @param string|null $time
     * @return bool
     */
    public static function is_midnight_or_empty( ?string $time ): bool {
        if ( null === $time ) {
            return true;
        }

        $trimmed = trim( $time );
        return '' === $trimmed || '00:00' === $trimmed || '00:00:00' === $trimmed;
    }

    /**
     * Check if a string matches valid 24-hour time format (HH:MM or HH:MM:SS).
     *
     * @param string $time
     * @return bool
     */
    public static function is_valid_time_format( string $time ): bool {
        $trimmed = trim( $time );
        return (bool) preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $trimmed );
    }

    /**
     * Check if a string is a valid date, datetime, or zero-date.
     *
     * @param string|null $datetime
     * @return bool
     */
    public static function is_valid_datetime( ?string $datetime ): bool {
        if ( null === $datetime ) {
            return true;
        }

        $trimmed = trim( $datetime );
        if ( '' === $trimmed ) {
            return true;
        }

        if ( str_starts_with( $trimmed, '0000-00-00' ) ) {
            return true;
        }

        if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?(?:Z|[+-]\d{2}:?\d{2})?)?$/', $trimmed, $matches ) ) {
            return false;
        }

        $year  = (int) $matches[1];
        $month = (int) $matches[2];
        $day   = (int) $matches[3];

        if ( ! checkdate( $month, $day, $year ) ) {
            return false;
        }

        if ( isset( $matches[4] ) && isset( $matches[5] ) ) {
            $hour   = (int) $matches[4];
            $minute = (int) $matches[5];
            $second = isset( $matches[6] ) && '' !== $matches[6] ? (int) $matches[6] : 0;

            if ( $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59 ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extract the H:i time component from a valid datetime string.
     * Returns null if date is empty, zero-date, date-only, midnight, or malformed.
     *
     * @param string|null $datetime
     * @return string|null
     */
    public static function extract_time_from_datetime( ?string $datetime ): ?string {
        if ( null === $datetime ) {
            return null;
        }

        $trimmed = trim( $datetime );
        if ( '' === $trimmed || str_starts_with( $trimmed, '0000-00-00' ) ) {
            return null;
        }

        if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?(?:Z|[+-]\d{2}:?\d{2})?$/', $trimmed, $matches ) ) {
            $year   = (int) $matches[1];
            $month  = (int) $matches[2];
            $day    = (int) $matches[3];
            $hour   = (int) $matches[4];
            $minute = (int) $matches[5];
            $second = isset( $matches[6] ) && '' !== $matches[6] ? (int) $matches[6] : 0;

            if ( checkdate( $month, $day, $year ) && $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59 && $second >= 0 && $second <= 59 ) {
                $time = sprintf( '%02d:%02d', $hour, $minute );
                if ( '00:00' !== $time ) {
                    return $time;
                }
            }
        }

        return null;
    }

    /**
     * Normalize a start time string to H:i format or null.
     *
     * Throws InvalidArgumentException for invalid or malformed non-empty time strings.
     *
     * @param string|null $start_time
     * @return string|null
     * @throws InvalidArgumentException
     */
    public static function normalize_start_time( ?string $start_time ): ?string {
        if ( self::is_midnight_or_empty( $start_time ) ) {
            return null;
        }

        $trimmed = trim( (string) $start_time );
        if ( self::is_valid_time_format( $trimmed ) ) {
            return substr( $trimmed, 0, 5 );
        }

        throw new InvalidArgumentException(
            sprintf( 'Invalid fixture start time: "%s". Expected format HH:MM or HH:MM:SS.', $start_time )
        );
    }

    /**
     * Merge a normalized start time into a date/datetime string.
     *
     * @param string|null $date
     * @param string|null $start_time Normalized start time (H:i) or null.
     * @return string|null
     */
    public static function merge_time_into_date( ?string $date, ?string $start_time ): ?string {
        if ( null === $date ) {
            return null;
        }

        $trimmed_date = trim( $date );
        if ( '' === $trimmed_date || str_starts_with( $trimmed_date, '0000-00-00' ) ) {
            return $date;
        }

        if ( preg_match( '/^(\d{4}-\d{2}-\d{2})/', $trimmed_date, $matches ) ) {
            $date_part = $matches[1];
            if ( null !== $start_time ) {
                return sprintf( '%s %s:00', $date_part, $start_time );
            }

            if ( strlen( $trimmed_date ) > 10 ) {
                return sprintf( '%s 00:00:00', $date_part );
            }

            return $date_part;
        }

        return $date;
    }

    /**
     * Convert any date, datetime, or ISO string to canonical MySQL datetime format (Y-m-d H:i:s).
     * Preserves exact date and time without timezone conversion.
     * Returns null for null or empty strings.
     * Returns '0000-00-00 00:00:00' for zero dates.
     *
     * Throws InvalidArgumentException for invalid or malformed dates/times.
     *
     * @param string|null $datetime
     * @return string|null
     * @throws InvalidArgumentException
     */
    public static function to_mysql_datetime( ?string $datetime ): ?string {
        if ( null === $datetime ) {
            return null;
        }

        $trimmed = trim( $datetime );
        if ( '' === $trimmed ) {
            return null;
        }

        if ( str_starts_with( $trimmed, '0000-00-00' ) ) {
            return '0000-00-00 00:00:00';
        }

        if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?(?:Z|[+-]\d{2}:?\d{2})?)?$/', $trimmed, $matches ) ) {
            $year  = (int) $matches[1];
            $month = (int) $matches[2];
            $day   = (int) $matches[3];

            if ( ! checkdate( $month, $day, $year ) ) {
                throw new InvalidArgumentException(
                    sprintf( 'Invalid fixture datetime: "%s". Date is not a valid calendar date.', $datetime )
                );
            }

            $hour   = isset( $matches[4] ) ? (int) $matches[4] : 0;
            $minute = isset( $matches[5] ) ? (int) $matches[5] : 0;
            $second = isset( $matches[6] ) && '' !== $matches[6] ? (int) $matches[6] : 0;

            if ( $hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59 ) {
                throw new InvalidArgumentException(
                    sprintf( 'Invalid fixture datetime: "%s". Time component is out of range.', $datetime )
                );
            }

            return sprintf( '%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second );
        }

        throw new InvalidArgumentException(
            sprintf( 'Invalid fixture datetime: "%s". Expected format YYYY-MM-DD or YYYY-MM-DD HH:MM:SS.', $datetime )
        );
    }
}
