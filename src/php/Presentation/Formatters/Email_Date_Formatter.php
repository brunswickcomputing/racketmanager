<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Formatters;

use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use function get_option;
use function mysql2date;

/**
 * Central date/time formatting for email presentation data.
 *
 * Email templates receive presentation-ready strings produced here, so they never read
 * WordPress date options or call mysql2date() themselves.
 */
final readonly class Email_Date_Formatter {
    public const string FIXTURE_LIST_DATE_FORMAT = 'd M y';
    public const string FIXTURE_LIST_DAY_FORMAT  = 'D';
    public const string DATE_TIME_FORMAT         = 'j F Y H:i';

    public function __construct( private ?Option_Gateway_Interface $option_gateway = null ) {
    }

    /**
     * Format a MySQL date/datetime using the given format or the site date format.
     */
    public function date( ?string $date, ?string $format = null ): string {
        if ( self::is_empty_date( $date ) ) {
            return '';
        }
        $formatted = mysql2date( $format ?? $this->date_format(), (string) $date );

        return is_string( $formatted ) ? $formatted : '';
    }

    /**
     * Format the time part of a MySQL datetime using the site time format.
     */
    public function time( ?string $date ): string {
        return $this->date( $date, $this->time_format() );
    }

    /**
     * Format a fixture date for date-change notifications: date and time when a start time is set,
     * otherwise just the site date format.
     */
    public function date_change( ?string $date, ?string $start_time ): string {
        return $this->date( $date, empty( $start_time ) ? $this->date_format() : self::DATE_TIME_FORMAT );
    }

    /**
     * Display a fixture start time, hiding the "no time set" marker.
     */
    public function start_time( ?string $start_time ): string {
        if ( empty( $start_time ) || '00:00' === $start_time ) {
            return '';
        }

        return $start_time;
    }

    public function date_format(): string {
        return $this->option( 'date_format', 'd/m/Y' );
    }

    public function time_format(): string {
        return $this->option( 'time_format', 'H:i' );
    }

    public static function is_empty_date( ?string $date ): bool {
        return empty( $date ) || str_starts_with( $date, '0000-00-00' );
    }

    private function option( string $option, string $default ): string {
        $value = $this->option_gateway
            ? $this->option_gateway->get_option( $option, $default )
            : ( function_exists( 'get_option' ) ? get_option( $option, $default ) : $default );

        return is_string( $value ) && '' !== $value ? $value : $default;
    }
}
