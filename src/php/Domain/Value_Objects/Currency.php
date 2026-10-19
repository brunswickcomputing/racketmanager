<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Value_Objects;

/**
 * Currency Backed Enum representing ISO 4217 currencies supported in RacketManager.
 */
enum Currency: string {
    case GBP = 'GBP';
    case EUR = 'EUR';
    case USD = 'USD';

    /**
     * Get the currency symbol.
     */
    public function symbol(): string {
        return match ( $this ) {
            self::GBP => '£',
            self::EUR => '€',
            self::USD => '$',
        };
    }

    /**
     * Get default number of decimal places.
     */
    public function decimals(): int {
        return 2;
    }

    /**
     * Resolve currency from symbol or string code.
     */
    public static function from_symbol_or_code( string $value ): self {
        $trimmed = trim( $value );
        return match ( $trimmed ) {
            '£', 'GBP', 'gbp' => self::GBP,
            '€', 'EUR', 'eur' => self::EUR,
            '$', 'USD', 'usd' => self::USD,
            default           => self::from( strtoupper( $trimmed ) ),
        };
    }

    /**
     * Safely attempt to resolve currency from symbol or string code.
     */
    public static function try_from_symbol_or_code( ?string $value ): ?self {
        if ( null === $value || '' === trim( $value ) ) {
            return null;
        }
        $trimmed = trim( $value );
        return match ( $trimmed ) {
            '£', 'GBP', 'gbp' => self::GBP,
            '€', 'EUR', 'eur' => self::EUR,
            '$', 'USD', 'usd' => self::USD,
            default           => self::tryFrom( strtoupper( $trimmed ) ),
        };
    }
}
