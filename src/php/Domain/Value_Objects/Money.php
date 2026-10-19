<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Value_Objects;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * Immutable Value Object representing monetary amounts in minor units (cents/pence).
 */
final readonly class Money implements Stringable, JsonSerializable {

    public function __construct(
        private int $cents,
        private Currency $currency = Currency::GBP
    ) {}

    /**
     * Create Money from minor units (pence/cents).
     */
    public static function from_cents( int $cents, Currency|string|null $currency = null ): self {
        $currency_enum = match ( true ) {
            $currency instanceof Currency => $currency,
            is_string( $currency )       => Currency::from_symbol_or_code( $currency ),
            default                      => Currency::GBP,
        };
        return new self( $cents, $currency_enum );
    }

    /**
     * Create Money from major units (pounds/dollars as float).
     */
    public static function from_float( float $amount, Currency|string|null $currency = null ): self {
        $currency_enum = match ( true ) {
            $currency instanceof Currency => $currency,
            is_string( $currency )       => Currency::from_symbol_or_code( $currency ),
            default                      => Currency::GBP,
        };
        $cents = (int) round( $amount * ( 10 ** $currency_enum->decimals() ) );
        return new self( $cents, $currency_enum );
    }

    /**
     * Create Money from a string representation (e.g. "12.50", "£12.50", "-£5.00", "-5.00").
     */
    public static function from_string( string $amount, Currency|string|null $currency = null ): self {
        $clean = trim( $amount );
        if ( '' === $clean ) {
            throw new InvalidArgumentException( 'Monetary amount string cannot be empty.' );
        }

        $is_negative = false;
        $has_sign    = false;
        if ( str_starts_with( $clean, '-' ) ) {
            $is_negative = true;
            $has_sign    = true;
            $clean       = trim( substr( $clean, 1 ) );
        } elseif ( str_starts_with( $clean, '+' ) ) {
            $has_sign = true;
            $clean    = trim( substr( $clean, 1 ) );
        }

        $detected_currency = null;
        if ( str_starts_with( $clean, '£' ) || str_starts_with( $clean, '€' ) || str_starts_with( $clean, '$' ) ) {
            $symbol            = mb_substr( $clean, 0, 1 );
            $detected_currency = Currency::from_symbol_or_code( $symbol );
            $clean             = trim( mb_substr( $clean, 1 ) );
        } elseif ( preg_match( '/^(GBP|EUR|USD)\s*/i', $clean, $matches ) ) {
            $detected_currency = Currency::from_symbol_or_code( $matches[1] );
            $clean             = trim( substr( $clean, strlen( $matches[0] ) ) );
        }

        if ( str_starts_with( $clean, '-' ) || str_starts_with( $clean, '+' ) ) {
            if ( $has_sign ) {
                throw new InvalidArgumentException( sprintf( 'Invalid monetary amount string: "%s"', $amount ) );
            }
            if ( str_starts_with( $clean, '-' ) ) {
                $is_negative = true;
            }
            $clean = trim( substr( $clean, 1 ) );
        }

        if ( str_contains( $clean, ',' ) && ! str_contains( $clean, '.' ) ) {
            if ( preg_match( '/^\d+,\d{1,2}$/', $clean ) ) {
                $clean = str_replace( ',', '.', $clean );
            } else {
                $clean = str_replace( ',', '', $clean );
            }
        } else {
            $clean = str_replace( ',', '', $clean );
        }

        if ( ! preg_match( '/^\d+(\.\d+)?$/', $clean ) ) {
            throw new InvalidArgumentException( sprintf( 'Invalid monetary amount string: "%s"', $amount ) );
        }

        $explicit_currency = match ( true ) {
            $currency instanceof Currency => $currency,
            is_string( $currency ) && '' !== trim( $currency ) => Currency::from_symbol_or_code( $currency ),
            default => null,
        };

        if ( $detected_currency !== null && $explicit_currency !== null && $detected_currency !== $explicit_currency ) {
            throw new InvalidArgumentException(
                sprintf(
                    'Currency mismatch: amount uses %s but %s was specified.',
                    $detected_currency->value,
                    $explicit_currency->value
                )
            );
        }

        $currency_enum = $detected_currency ?? $explicit_currency ?? Currency::GBP;

        $cents = self::parse_decimal_cents( $clean, $currency_enum->decimals() );
        if ( $is_negative ) {
            $cents = -$cents;
        }

        return new self( $cents, $currency_enum );
    }

    /**
     * Parse decimal string into minor units (cents) exactly without float rounding inaccuracies.
     */
    private static function parse_decimal_cents( string $clean, int $decimals = 2 ): int {
        $multiplier = 10 ** $decimals;
        $parts      = explode( '.', $clean );
        $whole_raw  = $parts[0];
        if ( strlen( ltrim( $whole_raw, '0' ) ) > 16 ) {
            throw new InvalidArgumentException( 'Monetary amount is too large.' );
        }
        $whole = (int) $whole_raw;
        if ( $multiplier > 0 && $whole > intdiv( PHP_INT_MAX, $multiplier ) ) {
            throw new InvalidArgumentException( 'Monetary amount is too large.' );
        }
        if ( 1 === count( $parts ) ) {
            return $whole * $multiplier;
        }

        $frac = $parts[1];
        if ( 0 === strlen( $frac ) ) {
            return $whole * $multiplier;
        }

        if ( strlen( $frac ) <= $decimals ) {
            $frac_padded = str_pad( $frac, $decimals, '0', STR_PAD_RIGHT );
            return ( $whole * $multiplier ) + (int) $frac_padded;
        }

        $base_frac  = (int) substr( $frac, 0, $decimals );
        $rest_frac  = (float) ( '0.' . substr( $frac, $decimals ) );
        $extra_unit = (int) round( $rest_frac );

        return ( $whole * $multiplier ) + $base_frac + $extra_unit;
    }

    /**
     * Create a zero Money value.
     */
    public static function zero( Currency|string|null $currency = null ): self {
        return self::from_cents( 0, $currency );
    }

    public function get_cents(): int {
        return $this->cents;
    }

    public function get_currency(): Currency {
        return $this->currency;
    }

    /**
     * Get monetary value as float (e.g. 12.50).
     */
    public function get_amount(): float {
        return $this->cents / ( 10 ** $this->currency->decimals() );
    }

    /**
     * Get formatted amount number string without currency symbol (e.g. "12.50").
     */
    public function get_amount_string(): string {
        return number_format( $this->get_amount(), $this->currency->decimals(), '.', '' );
    }

    /**
     * Format money string with or without symbol (e.g. "£12.50", "-£5.00").
     */
    public function format( bool $include_symbol = true ): string {
        $formatted_number = number_format( abs( $this->get_amount() ), $this->currency->decimals(), '.', ',' );
        $sign             = $this->is_negative() ? '-' : '';
        if ( $include_symbol ) {
            return $sign . $this->currency->symbol() . $formatted_number;
        }
        return $sign . $formatted_number;
    }

    public function add( self $other ): self {
        $this->ensure_same_currency( $other );
        return new self( $this->cents + $other->cents, $this->currency );
    }

    public function subtract( self $other ): self {
        $this->ensure_same_currency( $other );
        return new self( $this->cents - $other->cents, $this->currency );
    }

    public function multiply( float|int $multiplier ): self {
        $cents = (int) round( $this->cents * $multiplier );
        return new self( $cents, $this->currency );
    }

    public function divide( float|int $divisor ): self {
        if ( 0 === $divisor || 0.0 === (float) $divisor ) {
            throw new InvalidArgumentException( 'Cannot divide Money by zero.' );
        }
        $cents = (int) round( $this->cents / $divisor );
        return new self( $cents, $this->currency );
    }

    public function abs(): self {
        return new self( abs( $this->cents ), $this->currency );
    }

    public function is_zero(): bool {
        return 0 === $this->cents;
    }

    public function is_positive(): bool {
        return $this->cents > 0;
    }

    public function is_negative(): bool {
        return $this->cents < 0;
    }

    public function equals( self $other ): bool {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }

    public function is_greater_than( self $other ): bool {
        $this->ensure_same_currency( $other );
        return $this->cents > $other->cents;
    }

    public function is_less_than( self $other ): bool {
        $this->ensure_same_currency( $other );
        return $this->cents < $other->cents;
    }

    private function ensure_same_currency( self $other ): void {
        if ( $this->currency !== $other->currency ) {
            throw new InvalidArgumentException(
                sprintf( 'Currency mismatch: cannot perform operation between %s and %s', $this->currency->value, $other->currency->value )
            );
        }
    }

    public function __toString(): string {
        return $this->format();
    }

    public function jsonSerialize(): array {
        return [
            'cents'     => $this->cents,
            'amount'    => $this->get_amount(),
            'currency'  => $this->currency->value,
            'formatted' => $this->format(),
        ];
    }
}
