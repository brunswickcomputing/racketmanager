<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Enums;

/**
 * Backed Enum representing the outcome / side of a match or set score.
 */
enum Match_Outcome: string {
    case HOME = 'home';
    case AWAY = 'away';

    public function is_home(): bool {
        return self::HOME === $this;
    }

    public function is_away(): bool {
        return self::AWAY === $this;
    }

    public function opponent(): self {
        return self::HOME === $this ? self::AWAY : self::HOME;
    }

    /**
     * Create safely from string or self.
     *
     * @throws \ValueError If the value does not match a valid match outcome.
     */
    public static function from_value( string|self $value ): self {
        if ( $value instanceof self ) {
            return $value;
        }
        $outcome = self::try_from_value( $value );
        if ( null === $outcome ) {
            throw new \ValueError( sprintf( 'Invalid match outcome: "%s"', $value ) );
        }
        return $outcome;
    }

    public static function try_from_value( string|self|null $value ): ?self {
        if ( null === $value ) {
            return null;
        }
        if ( $value instanceof self ) {
            return $value;
        }
        return self::tryFrom( trim( strtolower( $value ) ) );
    }
}
