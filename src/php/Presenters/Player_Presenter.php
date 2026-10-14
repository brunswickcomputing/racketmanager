<?php
declare( strict_types=1 );

namespace Racketmanager\Presenters;

use Racketmanager\Domain\Player;
use Racketmanager\Util\Util_Lookup;
use function Racketmanager\seo_url;

/**
 * Presenter for Player domain objects and templates.
 */
readonly class Player_Presenter {

    /**
     * Get player initials (e.g. "JD" for John Doe).
     */
    public function get_initials( ?string $firstname, ?string $surname ): string {
        $first_initial = ! empty( $firstname ) ? mb_substr( trim( (string) $firstname ), 0, 1 ) : '';
        $last_initial  = ! empty( $surname ) ? mb_substr( trim( (string) $surname ), 0, 1 ) : '';

        return mb_strtoupper( $first_initial . $last_initial );
    }

    /**
     * Get heading level for player header.
     */
    public function get_heading_level( bool $is_page_header = false, mixed $header_level = null ): int {
        if ( ! empty( $header_level ) ) {
            return (int) $header_level;
        }

        return $is_page_header ? 1 : 3;
    }

    /**
     * Format WTN ratings into badge presentation structures.
     */
    public function get_wtn_badges( mixed $wtn ): array {
        if ( empty( $wtn ) || ! is_array( $wtn ) ) {
            return array();
        }

        $help_text   = function_exists( '__' ) ? __( 'World Tennis Number for', 'racketmanager' ) : 'World Tennis Number for';
        $match_types = class_exists( Util_Lookup::class ) ? Util_Lookup::get_match_types() : array(
            'singles' => 'Singles',
            'doubles' => 'Doubles',
        );

        $badges = array();
        foreach ( $match_types as $match_type => $description ) {
            $val = $wtn[ $match_type ] ?? $wtn[ strtolower( $description ) ] ?? $wtn[ $description ] ?? null;
            if ( null !== $val ) {
                $badges[] = array(
                    'type'        => $match_type,
                    'description' => $description,
                    'value'       => (string) $val,
                    'tooltip'     => trim( $help_text . ' ' . $description ),
                );
            }
        }

        return $badges;
    }

    /**
     * Format win/loss summary statistics into presentation structures.
     */
    public function get_win_loss_summary( mixed $statistics ): ?array {
        if ( ! is_array( $statistics ) && ! is_object( $statistics ) ) {
            return null;
        }

        $stats_array = (array) $statistics;
        if ( empty( $stats_array['total'] ) ) {
            return null;
        }

        $total   = (object) $stats_array['total'];
        $played  = (int) ( $total->played ?? 0 );
        if ( $played <= 0 ) {
            return null;
        }

        $won     = (int) ( $total->matches_won ?? 0 );
        $lost    = (int) ( $total->matches_lost ?? 0 );
        $win_pct = $total->win_pct ?? ( $played > 0 ? round( ( $won / $played ) * 100, 1 ) : 0 );

        $won_text = function_exists( '__' ) ? __( 'won', 'racketmanager' ) : 'won';

        return array(
            'played'         => $played,
            'matches_won'    => $won,
            'matches_lost'   => $lost,
            'win_pct'        => $win_pct,
            'record_text'    => sprintf( '%d-%d (%d)', $won, $lost, $played ),
            'progress_title' => sprintf( '%s %s%%', $win_pct, $won_text ),
        );
    }

    /**
     * Generate canonical player profile link.
     */
    public function get_player_link( string $player_name, ?string $club_shortcode = null, ?string $base_url = null ): string {
        $slug = function_exists( 'Racketmanager\seo_url' ) ? seo_url( $player_name ) : sanitize_title( $player_name );

        if ( ! empty( $base_url ) ) {
            return rtrim( $base_url, '/' ) . '/' . $slug . '/';
        }

        if ( ! empty( $club_shortcode ) ) {
            $club_slug = function_exists( 'Racketmanager\seo_url' ) ? seo_url( $club_shortcode ) : sanitize_title( $club_shortcode );
            return '/clubs/' . $club_slug . '/players/' . $slug . '/';
        }

        return '/players/' . $slug . '/';
    }

    /**
     * Generate alphabet navigation array.
     */
    public function get_alphabet_navigation( array $active_keys ): array {
        $navigation = array();
        foreach ( range( 'A', 'Z' ) as $char ) {
            $is_active = in_array( $char, $active_keys, true );
            $navigation[] = array(
                'letter'      => $char,
                'is_active'   => $is_active,
                'class'       => $is_active ? null : 'is-disabled',
            );
        }

        return $navigation;
    }
}
