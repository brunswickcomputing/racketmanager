<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Security;

use function function_exists;
use function in_array;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function json_last_error;
use function method_exists;
use function preg_match;
use const JSON_ERROR_NONE;

/**
 * Validates target seasons against competition, tournament, or league configuration.
 */
final class Target_Season_Validator {
    /**
     * Validate and sanitize a target season against a target object.
     *
     * @param object      $target      Target entity (Competition, Tournament, League, or Event).
     * @param string      $target_name Name of target type ('competition', 'tournament', 'league', 'event').
     * @param string|null $season      Raw season string to validate.
     * @return string|null Validated season string, or null if invalid/mismatched.
     */
    public static function validate( object $target, string $target_name, ?string $season ): ?string {
        if ( null === $season || ! preg_match( '/^[A-Za-z0-9 _-]+$/', $season ) ) {
            return null;
        }

        if ( 'tournament' === $target_name ) {
            $target_season = $target->season ?? ( method_exists( $target, 'get_season' ) ? $target->get_season() : null );
            if ( ! empty( $target_season ) && (string) $target_season !== $season ) {
                return null;
            }
            return $season;
        }

        $seasons = null;
        if ( 'league' === $target_name ) {
            $event = null;
            if ( isset( $target->event ) && is_object( $target->event ) ) {
                $event = $target->event;
            } elseif ( method_exists( $target, 'get_event' ) && $target->get_event() ) {
                $event = $target->get_event();
            } elseif ( isset( $target->event_id ) && $target->event_id && function_exists( 'Racketmanager\get_event' ) ) {
                $event = \Racketmanager\get_event( (int) $target->event_id );
            }

            if ( $event ) {
                $seasons = method_exists( $event, 'get_seasons' ) ? $event->get_seasons() : ( $event->seasons ?? null );
            }
        }

        if ( null === $seasons ) {
            $seasons = method_exists( $target, 'get_seasons' ) ? $target->get_seasons() : ( $target->seasons ?? null );
        }

        if ( is_string( $seasons ) ) {
            $decoded = json_decode( $seasons, true );
            $seasons = ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) ? $decoded : null;
        }

        if ( is_object( $seasons ) && method_exists( $seasons, 'all' ) ) {
            $seasons = $seasons->all();
        }

        if ( is_array( $seasons ) && ! empty( $seasons ) ) {
            if ( isset( $seasons[ $season ] ) ) {
                return $season;
            }
            foreach ( $seasons as $key => $conf ) {
                if ( (string) $key === $season ) {
                    return $season;
                }
                if ( ( is_array( $conf ) && isset( $conf['name'] ) && (string) $conf['name'] === $season ) ||
                    ( is_object( $conf ) && isset( $conf->name ) && (string) $conf->name === $season ) ) {
                    return $season;
                }
            }
            return null;
        }

        return null;
    }
}
