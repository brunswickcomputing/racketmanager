<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\View_Models\Club_Competition_View_Model;
use Racketmanager\Presentation\View_Models\Club_Event_View_Model;
use Racketmanager\Presentation\View_Models\Club_Roster_Player_View_Model;
use Racketmanager\Presentation\View_Models\Club_View_Model;
use Racketmanager\Util\Util_Lookup;

/**
 * Presenter for Club domain objects and templates.
 */
readonly class Club_Presenter {

    public function __construct(
        private ?Option_Gateway_Interface $option_gateway = null
    ) {}

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
        $slug      = Seo_Slug::encode( $shortcode );
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
        $slug      = Seo_Slug::encode( (string) $shortcode );

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

    /**
     * Format player rating / WTN dictionary into formatted string "[S:X] - [D:Y]".
     */
    public function format_rating_display( array $rating ): string {
        $parts = [];
        foreach ( array_keys( Util_Lookup::get_match_types() ) as $match_type ) {
            $value   = $rating[ $match_type ] ?? '-';
            $parts[] = '[' . $match_type . ':' . $value . ']';
        }

        return implode( ' - ', $parts );
    }

    /**
     * Present a registered club player as an immutable roster row.
     *
     * Registrations only carry WTN data. There is no source for rating points, so that mode
     * renders placeholders until one is introduced.
     */
    public function present_roster_player( Club_Player_DTO $club_player, object $club, bool $use_wtn = false ): Club_Roster_Player_View_Model {
        $display_name = (string) $club_player->display_name;
        $slug         = Seo_Slug::encode( $display_name );

        return new Club_Roster_Player_View_Model(
            registration_id: $club_player->registration_id,
            user_id: $club_player->user_id ?? $club_player->id,
            display_name: $display_name,
            gender: (string) $club_player->gender,
            player_url: '' !== $slug ? $this->get_link( $club, 'players' ) . $slug . '/' : '',
            btm: (string) $club_player->btm,
            rating_display: $this->format_rating_display( $use_wtn ? $club_player->wtn : [] ),
        );
    }

    /**
     * Present one event of a club's competition page.
     *
     * @param array<int, mixed> $team_rows Team rows for the club within the event.
     */
    public function present_club_event( object $event, object $club, array $team_rows = [] ): Club_Event_View_Model {
        return new Club_Event_View_Model(
            id: $event->id ?? 0,
            name: (string) ( $event->name ?? '' ),
            url: isset( $event->current_season['name'] ) ? $this->get_club_event_link( $event, $club ) : '',
            team_rows: $team_rows
        );
    }

    /**
     * Present a competition card of a club's competition page.
     *
     * @param array<int, Club_Event_View_Model> $events
     */
    public function present_club_competition( object $competition, object $club, array $events ): Club_Competition_View_Model {
        return new Club_Competition_View_Model(
            name: (string) ( $competition->name ?? '' ),
            url: $this->get_club_competition_link( $competition, $club ),
            events: array_values( $events )
        );
    }

    /**
     * Generate canonical link for club within a competition and season.
     */
    public function get_club_competition_link( object|string $competition, object|string $club, ?string $season_name = null ): string {
        $comp_name = is_object( $competition ) ? ( $competition->name ?? '' ) : (string) $competition;
        $comp_slug = Seo_Slug::encode( (string) $comp_name );

        $club_shortcode = is_object( $club ) ? ( $club->shortcode ?? ( method_exists( $club, 'get_shortcode' ) ? $club->get_shortcode() : '' ) ) : (string) $club;
        $club_slug      = Seo_Slug::encode( (string) $club_shortcode );

        $season = $season_name ?? ( is_object( $competition ) ? ( $competition->current_season['name'] ?? '' ) : '' );

        if ( '' === $comp_slug || '' === $club_slug ) {
            return '';
        }

        return '/' . $comp_slug . '/' . $season . '/club/' . $club_slug . '/';
    }

    /**
     * Generate canonical link for club event subpage.
     */
    public function get_club_event_link( object|string $event, object|string $club ): string {
        $club_shortcode = is_object( $club ) ? ( $club->shortcode ?? ( method_exists( $club, 'get_shortcode' ) ? $club->get_shortcode() : '' ) ) : (string) $club;
        $club_slug      = Seo_Slug::encode( (string) $club_shortcode );

        $event_name = is_object( $event ) ? ( $event->name ?? '' ) : (string) $event;
        $event_slug = Seo_Slug::encode( (string) $event_name );

        if ( '' === $club_slug || '' === $event_slug ) {
            return '';
        }

        return '/clubs/' . $club_slug . '/event/' . $event_slug . '/';
    }
}
