<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Enums;

/**
 * Backed Enum representing charge status stored in database.
 */
enum Charge_Status: string {
    case DRAFT = 'draft';
    case FINAL = 'final';

    public function is_draft(): bool {
        return self::DRAFT === $this;
    }

    public function is_final(): bool {
        return self::FINAL === $this;
    }

    /**
     * Create safely from string or self.
     */
    public static function from_value( string|self $value ): self {
        if ( $value instanceof self ) {
            return $value;
        }
        return self::from( strtolower( trim( $value ) ) );
    }

    public static function try_from_value( string|self|null $value ): ?self {
        if ( null === $value ) {
            return null;
        }
        if ( $value instanceof self ) {
            return $value;
        }
        return self::tryFrom( strtolower( trim( $value ) ) );
    }
}
