<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Finance;

use InvalidArgumentException;
use Racketmanager\Domain\Value_Objects\Money;

/**
 * Normalizer utility for monetary fee amounts across financial domain models.
 */
final class Fee_Normaliser {
    /**
     * Normalise a fee value to a non-negative float rounded to two decimal places.
     *
     * @param float|int|string|Money|null $fee   Fee value to normalize.
     * @param string                      $label Label used in exception messages.
     *
     * @return float|null Normalized fee amount, or null when no fee is provided.
     * @throws InvalidArgumentException If the fee is negative.
     */
    public static function normalise( float|int|string|Money|null $fee, string $label ): ?float {
        $normalized = null;

        if ( null !== $fee && '' !== $fee ) {
            if ( $fee instanceof Money ) {
                if ( $fee->is_negative() ) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new InvalidArgumentException( sprintf( '%s must be non-negative.', $label ) );
                }
                $normalized = $fee->get_amount();
            } else {
                $numeric = (float) $fee;
                if ( $numeric < 0.0 ) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    throw new InvalidArgumentException( sprintf( '%s must be non-negative.', $label ) );
                }
                $normalized = (float) number_format( $numeric, 2, '.', '' );
            }
        }

        return $normalized;
    }
}
