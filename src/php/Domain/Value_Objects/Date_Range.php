<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Value_Objects;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable Value Object representing a temporal date range window (e.g. tournament or season dates).
 */
final readonly class Date_Range implements JsonSerializable {

    public function __construct(
        private DateTimeImmutable $start,
        private DateTimeImmutable $end
    ) {
        if ( $this->start > $this->end ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid date range: start date "%s" cannot be after end date "%s".',
                    $this->start->format( 'Y-m-d' ),
                    $this->end->format( 'Y-m-d' )
                )
            );
        }
    }

    /**
     * Create a Date_Range from strings or DateTimeInterface instances.
     */
    public static function create( DateTimeInterface|string $start, DateTimeInterface|string $end ): self {
        $start_dt = self::parse_date( $start, 'start' );
        $end_dt   = self::parse_date( $end, 'end' );

        return new self( $start_dt, $end_dt );
    }

    /**
     * Safely attempt to create a Date_Range from strings or DateTimeInterface instances.
     * Returns null if either date is empty, zero ('0000-00-00'), invalid, or if start > end.
     */
    public static function try_create( DateTimeInterface|string|null $start, DateTimeInterface|string|null $end ): ?self {
        if ( null === $start || null === $end ) {
            return null;
        }
        $start_dt = self::try_parse_date( $start );
        $end_dt   = self::try_parse_date( $end );

        if ( null === $start_dt || null === $end_dt || $start_dt > $end_dt ) {
            return null;
        }

        return new self( $start_dt, $end_dt );
    }

    public function get_start(): DateTimeImmutable {
        return $this->start;
    }

    public function get_end(): DateTimeImmutable {
        return $this->end;
    }

    public function get_start_string( string $format = 'Y-m-d' ): string {
        return $this->start->format( $format );
    }

    public function get_end_string( string $format = 'Y-m-d' ): string {
        return $this->end->format( $format );
    }

    /**
     * Check if a given date falls within the range (inclusive).
     */
    public function contains( DateTimeInterface|string $date ): bool {
        $dt = self::parse_date( $date, 'contains' );
        return $dt >= $this->start && $dt <= $this->end;
    }

    /**
     * Check if the date range has already elapsed (based on current date).
     */
    public function is_past( ?DateTimeImmutable $now = null ): bool {
        $now ??= new DateTimeImmutable( gmdate( 'Y-m-d' ) . ' 00:00:00', new DateTimeZone( 'UTC' ) );
        return $this->end < $now;
    }

    public function equals( self $other ): bool {
        return $this->start->format( 'Y-m-d' ) === $other->start->format( 'Y-m-d' )
            && $this->end->format( 'Y-m-d' ) === $other->end->format( 'Y-m-d' );
    }

    /**
     * Parse and strictly validate a date in Y-m-d format or from DateTimeInterface.
     */
    public static function parse_date( DateTimeInterface|string $date, string $field_name ): DateTimeImmutable {
        $dt = self::try_parse_date( $date );
        if ( null === $dt ) {
            $val_str = $date instanceof DateTimeInterface ? $date->format( 'Y-m-d' ) : (string) $date;
            throw new InvalidArgumentException( sprintf( 'Invalid date value for %s: "%s".', $field_name, $val_str ) );
        }
        return $dt;
    }

    /**
     * Safely parse a date, enforcing strict Y-m-d roundtrip validation without exception.
     */
    public static function try_parse_date( DateTimeInterface|string|null $date ): ?DateTimeImmutable {
        if ( null === $date ) {
            return null;
        }
        if ( $date instanceof DateTimeImmutable ) {
            return $date;
        }
        if ( $date instanceof DateTimeInterface ) {
            return DateTimeImmutable::createFromInterface( $date );
        }
        $trimmed = trim( (string) $date );
        if ( '' === $trimmed || '0000-00-00' === $trimmed ) {
            return null;
        }

        $dt = DateTimeImmutable::createFromFormat( '!Y-m-d', $trimmed, new DateTimeZone( 'UTC' ) );
        if ( false !== $dt && $dt->format( 'Y-m-d' ) === $trimmed ) {
            return $dt;
        }

        return null;
    }

    public function jsonSerialize(): array {
        return [
            'start' => $this->get_start_string(),
            'end'   => $this->get_end_string(),
        ];
    }
}
