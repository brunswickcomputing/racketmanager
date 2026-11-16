<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Formatters;

use InvalidArgumentException;
use Racketmanager\Domain\Fixture\Fixture_Time_Parser;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
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

    /**
     * Initialise the instance with an optional option gateway.
     *
     * @param Option_Gateway_Interface|null $option_gateway Option gateway to use, or null to use the default handling.
     *
     * @return void
     */
    public function __construct( private ?Option_Gateway_Interface $option_gateway = null ) {
    }

    /**
     * Format a stored date for display, returning an empty string when no date is set.
     *
     * @param string|null $date Date value to format.
     * @param string|null $format Optional date format. Defaults to the configured date format.
     *
     * @return string Formatted date, or an empty string when the date is empty or cannot be formatted.
     */
    public function date( ?string $date, ?string $format = null ): string {
        if ( self::is_empty_date( $date ) ) {
            return '';
        }
        $formatted = mysql2date( $format ?? $this->date_format(), (string) $date );

        return is_string( $formatted ) ? $formatted : '';
    }

    /**
     * Format a date value using the configured time format.
     *
     * @param string|null $date Date value to format.
     *
     * @return string Formatted time string.
     */
    public function time( ?string $date ): string {
        return $this->date( $date, $this->time_format() );
    }

    /**
     * Format a fixture date for date-change notifications: date and time when a start time is set,
     * otherwise just the site date format.
     *
     * When $start_time is omitted or null, the start time is derived from the datetime string.
     * If an explicit $start_time is passed:
     * - An empty string, '00:00', or midnight clears any time component (formatting as date-only).
     * - A valid time string overrides the time component from $date.
     * - An invalid time string falls back to the time derived from $date (or date-only if no time).
     *
     * @param string|null $date Date value to format.
     * @param string|null $start_time Optional explicit start time override or null to derive from $date.
     * @return string
     */
    public function date_change( ?string $date, ?string $start_time = null ): string {
        if ( self::is_empty_date( $date ) ) {
            return '';
        }

        if ( null === $start_time ) {
            $effective_time = Fixture_Time_Parser::extract_time_from_datetime( $date );
        } elseif ( Fixture_Time_Parser::is_midnight_or_empty( $start_time ) ) {
            $effective_time = null;
        } else {
            try {
                $effective_time = Fixture_Time_Parser::normalize_start_time( $start_time );
            } catch ( InvalidArgumentException ) {
                $effective_time = Fixture_Time_Parser::extract_time_from_datetime( $date );
            }
        }

        $target_date = ! empty( $effective_time )
            ? Fixture_Time_Parser::merge_time_into_date( $date, $effective_time )
            : $date;

        return $this->date( $target_date, empty( $effective_time ) ? $this->date_format() : self::DATE_TIME_FORMAT );
    }

    /**
     * Returns the provided start time, or an empty string when no valid start time is set.
     *
     * @param string|null $start_time Start time value to evaluate.
     *
     * @return string The start time, or an empty string when the value is empty or equals 00:00.
     */
    public function start_time( ?string $start_time ): string {
        if ( empty( $start_time ) || '00:00' === $start_time ) {
            return '';
        }

        return $start_time;
    }

    /**
     * Returns the configured date format.
     *
     * @return string The configured date format, or the default d/m/Y format when not set.
     */
    public function date_format(): string {
        return $this->option( 'date_format', 'd/m/Y' );
    }

    /**
     * Retrieves the configured time format.
     *
     * @return string Time format option value, or the default format when not set.
     */
    public function time_format(): string {
        return $this->option( 'time_format', 'H:i' );
    }

    /**
     * Determines whether the given date value is empty or represents a zero date.
     *
     * @param string|null $date Date value to check.
     *
     * @return bool True when the date is empty or starts with a zero date, false otherwise.
     */
    public static function is_empty_date( ?string $date ): bool {
        return empty( $date ) || str_starts_with( $date, '0000-00-00' );
    }

    /**
     * Retrieves a string option value, falling back to the default when unavailable or empty.
     *
     * @param string $option Option name to retrieve.
     * @param string $default_value Default value to return when the option is not set or is empty.
     *
     * @return string Retrieved option value, or the default value when not available.
     */
    private function option( string $option, string $default_value ): string {
        $value = $this->option_gateway?->get_option( $option, $default_value );

        if ( is_string( $value ) && '' !== $value ) {
            return $value;
        }

        return $default_value;
    }
}
