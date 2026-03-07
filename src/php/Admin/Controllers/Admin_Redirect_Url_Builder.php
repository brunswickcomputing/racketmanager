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
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array<string, string>
     */
    private static function preserve_optional_context_params( array $query, array $post ): array {
        $keys     = array( 'leg', 'match_day', 'mode', 'season' );
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
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     * @param string               $view  'draw'|'setup-event'
     * @param int|null             $tournament_id
     * @param int|null             $league_id
     * @param string               $tab
     *
     * @return string
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
     * Build redirect URL for the tournament setup view (admin.php?page=racketmanager-tournaments&view=setup).
     *
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     * @param int|null             $tournament_id
     *
     * @return string
     */
    public static function tournament_setup_view(
        array $query,
        array $post,
        ?int $tournament_id
    ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            // For PRG, force the target view explicitly (do not carry over a stale query value).
            'view'       => 'setup',
            'tournament' => $tournament_id,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );
        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    /**
     * Build redirect URL for a tournament match edit view.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param int|null             $tournament_id
     * @param int|null             $league_id
     * @param string|null          $final_key
     * @param int|null             $match_id
     *
     * @return string
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
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param int|null             $tournament_id
     * @param int|null             $league_id
     * @param string|null          $final_key
     *
     * @return string
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

    /**
     * Build redirect URL for the tournament information view (admin.php?page=racketmanager-tournaments&view=information).
     *
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     * @param int|null             $tournament_id
     *
     * @return string
     */
    public static function tournament_information_view(
        array $query,
        array $post,
        ?int $tournament_id
    ): string {
        $args = array(
            'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
            // For PRG, force the target view explicitly (do not carry over a stale query value).
            'view'       => 'information',
            'tournament' => $tournament_id,
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );
        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    /**
     * Build redirect URL for the tournament plan view (admin.php?page=racketmanager-tournaments&view=plan).
     *
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     * @param int|null             $tournament_id
     * @param array<string, mixed> $flags
     *
     * @return string
     */
    public static function tournament_plan_view(
        array $query,
        array $post,
        ?int $tournament_id,
        array $flags = array()
    ): string {
        $args = array_merge(
            array(
                'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
                'view'       => 'plan',
                'tournament' => $tournament_id,
            ),
            $flags
        );

        $args = array_merge( $args, self::preserve_optional_context_params( $query, $post ) );

        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }

    /**
     * Build redirect URL for tournament modify view.
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param int|null             $tournament_id
     * @param array<string, mixed> $flags
     *
     * @return string
     */
    public static function tournament_modify( array $query, array $post, ?int $tournament_id, array $flags = array() ): string {
        $args = array_merge(
            array(
                'page'       => isset( $query['page'] ) ? sanitize_text_field( wp_unslash( strval( $query['page'] ) ) ) : 'racketmanager-tournaments',
                'view'       => 'modify',
                'tournament' => $tournament_id,
            ),
            $flags
        );

        return add_query_arg( $args, admin_url( 'admin.php' ) );
    }
}
