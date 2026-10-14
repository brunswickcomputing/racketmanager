<?php
declare( strict_types=1 );

namespace Racketmanager\Presenters;

use function Racketmanager\seo_url;

/**
 * Presenter for Club domain objects and templates.
 */
readonly class Club_Presenter {

    /**
     * Generate canonical club link or subpage link.
     */
    public function get_link( object $club, ?string $subpage = null ): string {
        $shortcode = $club->shortcode ?? ( method_exists( $club, 'get_shortcode' ) ? $club->get_shortcode() : '' );
        $slug      = function_exists( 'Racketmanager\seo_url' ) ? seo_url( (string) $shortcode ) : ( function_exists( 'seo_url' ) ? \seo_url( (string) $shortcode ) : sanitize_title( (string) $shortcode ) );

        if ( ! empty( $subpage ) ) {
            return '/clubs/' . $slug . '/' . trim( $subpage, '/' ) . '/';
        }

        return '/clubs/' . $slug . '/';
    }

    /**
     * Get display name for club.
     */
    public function get_display_name( object $club ): string {
        return (string) ( $club->name ?? $club->club_name ?? ( method_exists( $club, 'get_name' ) ? $club->get_name() : '' ) );
    }

    /**
     * Get heading tag based on standalone mode and optional custom level.
     */
    public function get_heading_tag( bool $standalone = false, ?int $header_level = null ): string {
        if ( ! empty( $header_level ) ) {
            return 'h' . (int) $header_level;
        }

        return $standalone ? 'h1' : 'h2';
    }

    /**
     * Generate Google Maps embed URL for club address.
     */
    public function get_map_url( object $club, ?string $google_maps_key = null ): ?string {
        $address = (string) ( $club->address ?? ( method_exists( $club, 'get_address' ) ? $club->get_address() : '' ) );
        if ( empty( $address ) || empty( $google_maps_key ) ) {
            return null;
        }

        return 'https://www.google.com/maps/embed/v1/search?key=' . rawurlencode( $google_maps_key ) . '&zoom=15&maptype=roadmap&q=' . rawurlencode( $address );
    }

    /**
     * Get subpage navigation links for club.
     */
    public function get_subpage_links( object $club ): array {
        return array(
            'roles'        => $this->get_link( $club, 'roles' ),
            'competitions' => $this->get_link( $club, 'competitions' ),
            'players'      => $this->get_link( $club, 'players' ),
        );
    }
}
