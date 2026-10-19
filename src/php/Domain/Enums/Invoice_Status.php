<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Enums;

/**
 * Backed Enum representing invoice lifecycle status stored in database.
 */
enum Invoice_Status: string {
    case DRAFT     = 'draft';
    case NEW       = 'new';
    case FINAL     = 'final';
    case SENT      = 'sent';
    case PAID      = 'paid';
    case CANCELLED = 'cancelled';

    public function is_paid(): bool {
        return self::PAID === $this;
    }

    public function is_draft(): bool {
        return self::DRAFT === $this;
    }

    public function is_new(): bool {
        return self::NEW === $this;
    }

    public function is_final(): bool {
        return self::FINAL === $this;
    }

    public function is_sent(): bool {
        return self::SENT === $this;
    }

    public function is_cancelled(): bool {
        return self::CANCELLED === $this;
    }

    public function can_be_sent(): bool {
        return self::DRAFT === $this || self::NEW === $this || self::FINAL === $this || self::SENT === $this;
    }

    /**
     * Create safely from string or self, normalizing legacy action keywords like 'resend'.
     */
    public static function from_value( string|self $value ): self {
        if ( $value instanceof self ) {
            return $value;
        }
        $normalized = strtolower( trim( $value ) );
        if ( 'resend' === $normalized ) {
            return self::SENT;
        }
        return self::from( $normalized );
    }

    public static function try_from_value( string|self|null $value ): ?self {
        if ( null === $value ) {
            return null;
        }
        if ( $value instanceof self ) {
            return $value;
        }
        $normalized = strtolower( trim( $value ) );
        if ( 'resend' === $normalized ) {
            return self::SENT;
        }
        return self::tryFrom( $normalized );
    }
}
