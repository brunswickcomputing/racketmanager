<?php

namespace Racketmanager\Domain\Enums;

enum Team_Profile: int {
    case PENDING = 0;
    case ACTIVE = 1;
    case NEW = 2;
    case WITHDRAWN = 3;

    public function is_active(): bool {
        return $this === self::ACTIVE;
    }

    public function is_withdrawn(): bool {
        return $this === self::WITHDRAWN;
    }

    public function is_new(): bool {
        return $this === self::NEW;
    }

    public function is_pending(): bool {
        return $this === self::PENDING;
    }

    /**
     * Safely creates an enum from a raw value with a fallback.
     */
    public static function from_value( int $value ): self {
        // try_from returns NULL if the value (e.g. 99) isn't found.
        return self::tryFrom( $value ) ?? self::ACTIVE;
    }

}
