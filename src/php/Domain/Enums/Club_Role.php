<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Enums;

/**
 * Backed Enum representing club role IDs stored in database.
 */
enum Club_Role: int {
    case MATCH_SECRETARY = 1;
    case COACH           = 2;
    case TREASURER       = 3;

    public function is_match_secretary(): bool {
        return self::MATCH_SECRETARY === $this;
    }

    public function is_coach(): bool {
        return self::COACH === $this;
    }

    public function is_treasurer(): bool {
        return self::TREASURER === $this;
    }

    public function limit(): ?int {
        return match ( $this ) {
            self::MATCH_SECRETARY => 1,
            self::COACH           => null,
            self::TREASURER       => 1,
        };
    }

    /**
     * Create safely from integer, string, or self.
     */
    public static function from_value( int|string|self $value ): self {
        if ( $value instanceof self ) {
            return $value;
        }
        return self::from( (int) $value );
    }

    public static function try_from_value( int|string|self|null $value ): ?self {
        if ( null === $value ) {
            return null;
        }
        if ( $value instanceof self ) {
            return $value;
        }
        return self::tryFrom( (int) $value );
    }
}
