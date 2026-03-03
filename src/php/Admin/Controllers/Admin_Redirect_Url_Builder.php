<?php
/**
 * Admin redirect URL builder
 *
 * Centralizes PRG redirect URL construction for admin controllers.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

final class Admin_Redirect_Url_Builder {

    /**
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     *
     * @return array<string,string>
     */
    private static function preserve_optional_context_params( array $query, array $post ): array {
        $keys     = array( 'leg', 'match_day', 'mode' );
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

    /**
     * Build redirect URL for tournament draw-like views.
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @param string $view 'draw'|'setup-event'
     */
    public static function tournament_draw_view(
        array $query,
        array $post,
        string $view,
        ?int $tournament_id,
        ?int $league_id,
        string $tab
    ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            // For PRG, force the target view explicitly (do not carry over a stale query value).
            'view'       => $view,
            'tournament' => $tournament_id,
            'league'     => $league_id,
            'league-tab' => $tab,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    /**
     * Build redirect URL for a tournament match edit view.
     */
    public static function tournament_match( array $query, array $post, ?int $tournament_id, ?int $league_id, ?string $final_key, ?int $match_id ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            'view'       => 'match',
            'tournament' => $tournament_id,
            'league'     => $league_id,
            'final'      => $final_key,
            'edit'       => $match_id,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    /**
     * Build redirect URL for a tournament matches list view.
     */
    public static function tournament_matches( array $query, array $post, ?int $tournament_id, ?int $league_id, ?string $final_key ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            'view'       => 'matches',
            'tournament' => $tournament_id,
            'league_id'  => $league_id,
            'final'      => $final_key,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }
}
