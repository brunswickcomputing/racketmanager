<?php
/**
 * Redirect context params helper
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

final class Redirect_Context_Params {

    /**
     * Preserve optional context params that affect rendering.
     *
     * @param array $query
     * @param array $post
     * @param array<int,string> $keys
     * @return array<string,string>
     */
    public static function from( array $query, array $post, array $keys = array( 'leg', 'match_day', 'mode' ) ): array {
        $optional = array();

        foreach ( $keys as $key ) {
            if ( isset( $query[ $key ] ) && '' !== strval( $query[ $key ] ) ) {
                $optional[ $key ] = sanitize_text_field( wp_unslash( strval( $query[ $key ] ) ) );
                continue;
            }

            if ( isset( $post[ $key ] ) && '' !== strval( $post[ $key ] ) ) {
                $optional[ $key ] = sanitize_text_field( wp_unslash( strval( $post[ $key ] ) ) );
            }
        }

        return $optional;
    }
}
