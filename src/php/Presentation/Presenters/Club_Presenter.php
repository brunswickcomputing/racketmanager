<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Presentation\View_Models\Club_Competition_View_Model;
use Racketmanager\Presentation\View_Models\Club_Event_View_Model;
use Racketmanager\Presentation\View_Models\Club_Roster_Player_View_Model;
use Racketmanager\Presentation\View_Models\Club_View_Model;
use Racketmanager\Util\Util_Lookup;

/**
 * Presenter for Club domain objects and templates.
 */
readonly class Club_Presenter {
    private const string CLUB_BASE_PATH       = '/clubs/';
    private const string SUBPAGE_ROLES        = 'roles';
    private const string SUBPAGE_COMPETITIONS = 'competitions';
    private const string SUBPAGE_PLAYERS      = 'players';
    private const string EVENT_PATH           = '/event/';
    private const string CLUB_PATH            = '/club/';

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
        $shortcode = $this->resolve_shortcode( $club );
        $secretary = $this->resolve_match_secretary_contact( $this->read( $club, 'match_secretary', 'get_match_secretary' ) );

        return new Club_View_Model(
            template: $template,
            id: (int) ( $this->read( $club, 'id', 'get_id' ) ?? 0 ),
            name: $this->get_display_name( $club ),
            shortcode: $shortcode,
            slug: Seo_Slug::encode( $shortcode ),
            address: $this->read_string( $club, 'address', 'get_address' ),
            link: $this->get_link( $club ),
            subpage_links: $this->get_subpage_links( $club ),
            map_url: $this->get_map_url( $club, $google_maps_key ),
            initials: $this->get_initials( $shortcode ),
            heading_tag: $this->get_heading_tag( $standalone, $header_level ),
            roles: $this->read_array( $club, 'roles' ),
            teams: $this->read_array( $club, 'teams' ),
            google_maps_key: $google_maps_key ?? '',
            user_can_update_club: $user_can_update_club,
            standalone: $standalone,
            match_secretary_name: $secretary['name'],
            match_secretary_id: $secretary['id'],
            match_secretary_email: $secretary['email'],
            match_secretary_contact_no: $secretary['contact_no'],
            contact_no: $this->read_string( $club, 'contact_no', 'get_contact_no' ),
            website: $this->read_string( $club, 'website', 'get_website' ),
            founded: $this->read_string( $club, 'founded', 'get_founded' ),
            facilities: $this->read_string( $club, 'facilities', 'get_facilities' ),
            club: $club
        );
    }

    /**
     * Extract initials from the club shortcode or name.
     */
    public function get_initials( object|string $club_or_shortcode ): string {
        $shortcode = $this->resolve_shortcode( $club_or_shortcode );

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
     * Generate a canonical club link or subpage link.
     */
    public function get_link( object $club, ?string $subpage = null ): string {
        $slug = Seo_Slug::encode( $this->resolve_shortcode( $club ) );

        if ( ! empty( $subpage ) ) {
            return self::CLUB_BASE_PATH . $slug . '/' . trim( $subpage, '/' ) . '/';
        }

        return self::CLUB_BASE_PATH . $slug . '/';
    }

    /**
     * Get a display name for the club.
     */
    public function get_display_name( object $club ): string {
        $name = $this->read( $club, 'name' );
        if ( null === $name ) {
            $name = $this->read( $club, 'club_name', 'get_name' );
        }

        return (string) ( $name ?? '' );
    }

    /**
     * Get heading tag based on standalone mode and optional custom level.
     */
    public function get_heading_tag( bool $standalone = false, ?int $header_level = null ): string {
        if ( ! empty( $header_level ) ) {
            return 'h' . $header_level;
        }

        return $standalone ? 'h1' : 'h2';
    }

    /**
     * Generate Google Maps embed URL for club address.
     */
    public function get_map_url( object $club, ?string $google_maps_key = null ): ?string {
        $address = (string) ( $this->read( $club, 'address', 'get_address' ) ?? '' );
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
            self::SUBPAGE_ROLES        => $this->get_link( $club, self::SUBPAGE_ROLES ),
            self::SUBPAGE_COMPETITIONS => $this->get_link( $club, self::SUBPAGE_COMPETITIONS ),
            self::SUBPAGE_PLAYERS      => $this->get_link( $club, self::SUBPAGE_PLAYERS ),
        );
    }

    /**
     * Format player rating / WTN dictionary into a formatted string "[S:X] - [D:Y]".
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
            player_url: '' !== $slug ? $this->get_link( $club, self::SUBPAGE_PLAYERS ) . $slug . '/' : '',
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
            name: $this->resolve_name( $event ),
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
            name: $this->resolve_name( $competition ),
            url: $this->get_club_competition_link( $competition, $club ),
            events: array_values( $events )
        );
    }

    /**
     * Generate the canonical link for a club within a competition and season.
     */
    public function get_club_competition_link( object|string $competition, object|string $club, ?string $season_name = null ): string {
        $comp_slug = Seo_Slug::encode( $this->resolve_name( $competition ) );
        $club_slug = Seo_Slug::encode( $this->resolve_shortcode( $club ) );
        $season    = $season_name ?? $this->resolve_season_name( $competition );

        if ( '' === $comp_slug || '' === $club_slug ) {
            return '';
        }

        return '/' . $comp_slug . '/' . $season . self::CLUB_PATH . $club_slug . '/';
    }

    /**
     * Generate the canonical link for the club event subpage.
     */
    public function get_club_event_link( object|string $event, object|string $club ): string {
        $club_slug  = Seo_Slug::encode( $this->resolve_shortcode( $club ) );
        $event_slug = Seo_Slug::encode( $this->resolve_name( $event ) );

        if ( '' === $club_slug || '' === $event_slug ) {
            return '';
        }

        return self::CLUB_BASE_PATH . $club_slug . self::EVENT_PATH . $event_slug . '/';
    }

    /**
     * Read a value from a loosely typed source: public property first, then getter.
     */
    private function read( ?object $source, string $property, ?string $getter = null ): mixed {
        if ( null === $source ) {
            return null;
        }
        if ( isset( $source->$property ) ) {
            return $source->$property;
        }
        if ( null !== $getter && method_exists( $source, $getter ) ) {
            return $source->$getter();
        }

        return null;
    }

    /**
     * Read a value as a nullable string.
     */
    private function read_string( ?object $source, string $property, ?string $getter = null ): ?string {
        $value = $this->read( $source, $property, $getter );

        return null === $value ? null : (string) $value;
    }

    /**
     * Read a value as an array, defaulting to an empty array.
     */
    private function read_array( ?object $source, string $property ): array {
        $value = $this->read( $source, $property );

        return is_array( $value ) ? $value : [];
    }

    /**
     * Resolve to match secretary contact details from a loosely typed player object.
     *
     * @return array{name: ?string, id: int|string|null, email: ?string, contact_no: ?string}
     */
    private function resolve_match_secretary_contact( mixed $secretary ): array {
        $source = is_object( $secretary ) ? $secretary : null;

        return array(
            'name'       => $this->read( $source, 'display_name', 'get_display_name' ),
            'id'         => $this->read( $source, 'id', 'get_id' ),
            'email'      => $this->read( $source, 'email', 'get_email' ),
            'contact_no' => $this->read( $source, 'contactno', 'get_contactno' ),
        );
    }

    /**
     * Resolve a club shortcode from an object or a raw string.
     */
    private function resolve_shortcode( object|string $club ): string {
        if ( ! is_object( $club ) ) {
            return $club;
        }

        return (string) ( $this->read( $club, 'shortcode', 'get_shortcode' ) ?? '' );
    }

    /**
     * Resolve a name from an object or a raw string.
     */
    private function resolve_name( object|string $subject ): string {
        if ( ! is_object( $subject ) ) {
            return $subject;
        }

        return (string) ( $this->read( $subject, 'name', 'get_name' ) ?? '' );
    }

    /**
     * Resolve the current season name of a competition, or an empty string.
     */
    private function resolve_season_name( object|string $competition ): string {
        if ( ! is_object( $competition ) ) {
            return '';
        }

        return (string) ( $competition->current_season['name'] ?? '' );
    }
}
