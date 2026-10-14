<?php
/**
 * Admin date/time presenter
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\Presenters;

/**
 * Formats MySQL datetimes for admin screens using WordPress date/time formats.
 */
final class Admin_Date_Presenter {

    /**
     * Format a MySQL datetime as a localized admin date.
     */
    public function format_date( ?string $date, ?string $format = null ): string {
        if ( null === $date || '' === $date || str_starts_with( $date, '0000-00-00' ) ) {
            return 'N/A';
        }

        $format    = $format ?? $this->date_format();
        $formatted = $this->mysql2date( $format, $date );

        return '' === $formatted ? 'N/A' : $formatted;
    }

    /**
     * Format a MySQL datetime as a localized admin time.
     */
    public function format_time( ?string $date, ?string $format = null ): string {
        if ( null === $date || '' === $date || str_starts_with( $date, '0000-00-00' ) ) {
            return 'N/A';
        }

        $format    = $format ?? $this->time_format();
        $formatted = $this->mysql2date( $format, $date );

        return '' === $formatted ? 'N/A' : $formatted;
    }

    /**
     * WordPress date format, falling back to the plugin option then ISO date.
     */
    public function date_format(): string {
        return $this->option_format( 'date_format', 'Y-m-d' );
    }

    /**
     * WordPress time format, falling back to the plugin option then 24-hour time.
     */
    public function time_format(): string {
        return $this->option_format( 'time_format', 'H:i' );
    }

    private function option_format( string $option, string $default ): string {
        if ( function_exists( 'get_option' ) ) {
            $format = get_option( $option, $default );
            if ( is_string( $format ) && '' !== $format ) {
                return $format;
            }
        }

        global $racketmanager;
        $property = 'date_format' === $option ? 'date_format' : 'time_format';
        if ( is_object( $racketmanager ) && isset( $racketmanager->{$property} ) && is_string( $racketmanager->{$property} ) && '' !== $racketmanager->{$property} ) {
            return $racketmanager->{$property};
        }

        return $default;
    }

    private function mysql2date( string $format, string $date ): string {
        if ( ! function_exists( 'mysql2date' ) ) {
            $timestamp = strtotime( $date );

            return $timestamp ? date( $format, $timestamp ) : '';
        }

        $formatted = mysql2date( $format, $date );

        return is_string( $formatted ) && '' !== $formatted ? $formatted : '';
    }
}
