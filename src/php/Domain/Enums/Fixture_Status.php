<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Enums;

/**
 * Backed Enum representing fixture (match) status codes stored in database.
 *
 * Rubber statuses are written by Rubber_Result_Manager using the same codes
 * (1 = walkover, 2 = retired, 3 = shared, 6 = abandoned, 8 = cancelled, 9 = invalid).
 */
enum Fixture_Status: int {
    case COMPLETE     = 0;
    case WALKOVER     = 1;
    case RETIRED      = 2;
    case SHARED       = 3;
    case RESCHEDULED  = 5;
    case ABANDONED    = 6;
    case WITHDRAWN    = 7;
    case CANCELLED    = 8;
    case INVALID      = 9;

    public function is_complete(): bool {
        return self::COMPLETE === $this;
    }

    public function is_walkover(): bool {
        return self::WALKOVER === $this;
    }

    public function is_retired(): bool {
        return self::RETIRED === $this;
    }

    public function is_shared(): bool {
        return self::SHARED === $this;
    }

    public function is_rescheduled(): bool {
        return self::RESCHEDULED === $this;
    }

    public function is_abandoned(): bool {
        return self::ABANDONED === $this;
    }

    public function is_withdrawn(): bool {
        return self::WITHDRAWN === $this;
    }

    public function is_cancelled(): bool {
        return self::CANCELLED === $this;
    }

    public function is_invalid(): bool {
        return self::INVALID === $this;
    }

    /**
     * Create safely from integer, string, or self.
     */
    public static function from_value( int|string|self $value ): self {
        if ( $value instanceof self ) {
            return $value;
        }
        $status = self::try_from_value( $value );
        if ( null === $status ) {
            throw new \ValueError( sprintf( 'Invalid fixture status: "%s"', $value ) );
        }
        return $status;
    }

    public static function try_from_value( int|string|self|null $value ): ?self {
        if ( null === $value ) {
            return null;
        }
        if ( $value instanceof self ) {
            return $value;
        }
        if ( is_string( $value ) ) {
            $trimmed = trim( $value );
            if ( '' === $trimmed || ! preg_match( '/^-?\d+$/', $trimmed ) ) {
                return null;
            }
            return self::tryFrom( (int) $trimmed );
        }
        return self::tryFrom( $value );
    }
}
