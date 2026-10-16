<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Presentation\View_Models\Club_View_Model;
use function Racketmanager\seo_url;

/**
 * Presenter for Club domain objects and templates.
 */
readonly class Club_Presenter {

    /**
     * Present full immutable Club view model.
     */
    public function present(
        object $club,
        bool $standalone = true,
        ?string $google_maps_key = null,
        bool $user_can_update_club = false,
        ?int $header_level = null,
        string $template = 'club'
    ): Club_View_Model {
        $shortcode = (string) ( $club->shortcode ?? ( method_exists( $club, 'get_shortcode' ) ? $club->get_shortcode() : '' ) );
        $address   = isset( $club->address ) ? (string) $club->address : ( method_exists( $club, 'get_address' ) ? $club->get_address() : null );
        $slug      = function_exists( 'Racketmanager\seo_url' ) ? seo_url( $shortcode ) : ( function_exists( 'seo_url' ) ? \seo_url( $shortcode ) : sanitize_title( $shortcode ) );
        $roles     = isset( $club->roles ) && is_array( $club->roles ) ? $club->roles : [];
        $teams     = isset( $club->teams ) && is_array( $club->teams ) ? $club->teams : [];

        $match_secretary            = $club->match_secretary ?? ( method_exists( $club, 'get_match_secretary' ) ? $club->get_match_secretary() : null );
        $match_secretary_name       = is_object( $match_secretary ) ? ( $match_secretary->display_name ?? ( method_exists( $match_secretary, 'get_display_name' ) ? $match_secretary->get_display_name() : null ) ) : null;
        $match_secretary_id         = is_object( $match_secretary ) ? ( $match_secretary->id ?? ( method_exists( $match_secretary, 'get_id' ) ? $match_secretary->get_id() : null ) ) : null;
        $match_secretary_email      = is_object( $match_secretary ) ? ( $match_secretary->email ?? ( method_exists( $match_secretary, 'get_email' ) ? $match_secretary->get_email() : null ) ) : null;
        $match_secretary_contact_no = is_object( $match_secretary ) ? ( $match_secretary->contactno ?? ( method_exists( $match_secretary, 'get_contactno' ) ? $match_secretary->get_contactno() : null ) ) : null;
        $contact_no                 = isset( $club->contact_no ) ? (string) $club->contact_no : ( method_exists( $club, 'get_contact_no' ) ? $club->get_contact_no() : null );
        $website                    = isset( $club->website ) ? (string) $club->website : ( method_exists( $club, 'get_website' ) ? $club->get_website() : null );
        $founded                    = isset( $club->founded ) ? (string) $club->founded : ( method_exists( $club, 'get_founded' ) ? $club->get_founded() : null );
        $facilities                 = isset( $club->facilities ) ? (string) $club->facilities : ( method_exists( $club, 'get_facilities' ) ? $club->get_facilities() : null );

        return new Club_View_Model(
            template: $template,
            id: (int) ( $club->id ?? ( method_exists( $club, 'get_id' ) ? $club->get_id() : 0 ) ),
            name: $this->get_display_name( $club ),
            shortcode: $shortcode,
            slug: $slug,
            address: $address,
            link: $this->get_link( $club ),
            subpage_links: $this->get_subpage_links( $club ),
            map_url: $this->get_map_url( $club, $google_maps_key ),
            initials: $this->get_initials( $shortcode ),
            heading_tag: $this->get_heading_tag( $standalone, $header_level ),
            roles: $roles,
            teams: $teams,
            google_maps_key: $google_maps_key ?? '',
            user_can_update_club: $user_can_update_club,
            standalone: $standalone,
            match_secretary_name: $match_secretary_name,
            match_secretary_id: $match_secretary_id,
            match_secretary_email: $match_secretary_email,
            match_secretary_contact_no: $match_secretary_contact_no,
            contact_no: $contact_no,
            website: $website,
            founded: $founded,
            facilities: $facilities,
            club: $club
        );
    }

    /**
     * Extract initials from club shortcode or name.
     */
    public function get_initials( object|string $club_or_shortcode ): string {
        $shortcode = is_object( $club_or_shortcode )
            ? (string) ( $club_or_shortcode->shortcode ?? ( method_exists( $club_or_shortcode, 'get_shortcode' ) ? $club_or_shortcode->get_shortcode() : '' ) )
            : (string) $club_or_shortcode;

        $words    = explode( ' ', $shortcode );
        $initials = '';
        foreach ( $words as $w ) {
            if ( '' !== $w ) {
                $initials .= $w[0];
            }
        }

        return $initials;
    }

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
