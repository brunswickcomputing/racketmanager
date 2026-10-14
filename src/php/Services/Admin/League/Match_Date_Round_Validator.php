<?php
/**
 * Match date round validator for league/event setup screens.
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

/**
 * Validates sequential match dates posted from the championship setup form.
 */
final class Match_Date_Round_Validator {

    /**
     * @param array<int|string, mixed> $rounds Posted `rounds` rows.
     *
     * @return array{valid:bool,dates:array<int,string>,message:?string}
     */
    public static function validate( array $rounds ): array {
        $dates           = array();
        $messages        = array();
        $valid           = true;
        $next_round_date = null;

        foreach ( $rounds as $round ) {
            if ( ! is_array( $round ) ) {
                continue;
            }

            $round_number = $round['round'] ?? '';
            $match_date   = isset( $round['match_date'] ) ? strval( $round['match_date'] ) : '';

            if ( '' === $match_date ) {
                /* translators: %s: round number */
                $messages[] = sprintf( __( 'Match date missing for round %s', 'racketmanager' ), $round_number );
                $valid      = false;
            } elseif ( ! empty( $next_round_date ) && $match_date <= $next_round_date ) {
                /* translators: %s: round number */
                $messages[] = sprintf( __( 'Match date for round %s after next round date', 'racketmanager' ), $round_number );
                $valid      = false;
            } else {
                $dates[]         = $match_date;
                $next_round_date = $match_date;
            }
        }

        return array(
            'valid'   => $valid,
            'dates'   => $dates,
            'message' => $valid ? null : implode( '<br>', $messages ),
        );
    }
}
