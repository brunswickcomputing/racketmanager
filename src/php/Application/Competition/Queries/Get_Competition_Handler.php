<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Competition\DTOs\Competition_Page_Result;
use Racketmanager\Application\Ports\Settings_Service_Interface;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function __;

/**
 * Query handler for resolving [competition] shortcode data into a view model.
 */
readonly class Get_Competition_Handler {
    /**
     * Constructor.
     *
     * @param Competition_Service        $competition_service Competition service.
     * @param Club_Service_Interface     $club_service Club service.
     * @param Player_Service             $player_service Player service.
     * @param Security_Service_Interface $security_service Security service.
     * @param Settings_Service_Interface $settings_service Settings service.
     * @param Competition_Season_Service $competition_season_service Competition season service.
     */
    public function __construct(
        private Competition_Service $competition_service,
        private Club_Service_Interface $club_service,
        private Player_Service $player_service,
        private Security_Service_Interface $security_service,
        private Settings_Service_Interface $settings_service,
        private Competition_Season_Service $competition_season_service
    ) {}

    /**
     * Handle query.
     *
     * @param Get_Competition_Query $query Query.
     *
     * @return Competition_Page_Result
     */
    public function handle( Get_Competition_Query $query ): Competition_Page_Result {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return Competition_Page_Result::error(
                __( 'Competition not found', 'racketmanager' )
            );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = Util::un_seo_url( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Competition_Page_Result::error(
                $e->getMessage()
            );
        }

        $seasons = $competition->get_seasons();
        if ( empty( $seasons ) ) {
            return Competition_Page_Result::error(
                __( 'No seasons found for competition', 'racketmanager' )
            );
        }

        $competition_season = $this->competition_season_service->resolve_current_season( $competition, $query->season ?? '' );
        if ( empty( array_diff_key( $competition_season, [ 'venue_name' => true ] ) ) ) {
            return Competition_Page_Result::error(
                __( 'Season not found for competition', 'racketmanager' )
            );
        }

        $season = $competition_season['name'] ?? '';

        $tab                  = $query->tab ?: ( Util_Lookup::get_tab() ?: 'overview' );
        $is_singular          = $query->is_singular;
        $competition_seo_name = Util::seo_url( $competition->name ?? '' );
        $post_id              = $query->post_id;
        $pagename             = $query->pagename ?: $competition_seo_name;

        $menu_options             = [];
        $menu_options['overview'] = [
            'name'        => 'overview',
            'selected'    => 'overview' === $tab,
            'available'   => true,
            'description' => __( 'Overview', 'racketmanager' ),
        ];
        $menu_options['events']   = [
            'name'        => 'events',
            'selected'    => 'events' === $tab,
            'available'   => true,
            'description' => __( 'Events', 'racketmanager' ),
        ];
        $menu_options['clubs']    = [
            'name'        => 'clubs',
            'selected'    => 'clubs' === $tab,
            'available'   => true,
            'description' => __( 'Clubs', 'racketmanager' ),
        ];
        $menu_options['teams']    = [
            'name'        => 'teams',
            'selected'    => 'teams' === $tab,
            'available'   => true,
            'description' => __( 'Teams', 'racketmanager' ),
        ];
        $menu_options['players']  = [
            'name'        => 'players',
            'selected'    => 'players' === $tab,
            'available'   => true,
            'description' => __( 'Players', 'racketmanager' ),
        ];
        if ( ! empty( $competition->is_cup ) ) {
            $menu_options['order_of_play'] = [
                'name'        => 'order_of_play',
                'selected'    => 'order_of_play' === $tab,
                'available'   => ! empty( $competition_season['orderofplay'] ),
                'description' => __( 'Order of play', 'racketmanager' ),
            ];
        }
        $menu_options['winners'] = [
            'name'        => 'winners',
            'selected'    => 'winners' === $tab,
            'available'   => empty( $competition_season['date_end'] ) || gmdate( 'Y-m-d' ) >= $competition_season['date_end'],
            'description' => __( 'Winners', 'racketmanager' ),
        ];

        $is_logged_in = $this->security_service->is_user_logged_in();

        if ( ! empty( $competition->is_open ) && $is_logged_in ) {
            $comp_name  = $competition->name ?? '';
            $entry_link = '/entry-form/' . Util::seo_url( $comp_name ) . '/' . $season . '/';
            $clubs      = $this->club_selection_available( $competition );
            if ( $clubs ) {
                if ( is_array( $clubs ) ) {
                    if ( 1 === count( $clubs ) ) {
                        $first_club = reset( $clubs );
                        if ( is_object( $first_club ) && isset( $first_club->shortcode ) ) {
                            $entry_link .= Util::seo_url( (string) $first_club->shortcode ) . '/';
                        }
                    }
                } elseif ( is_object( $clubs ) && isset( $clubs->shortcode ) ) {
                    $entry_link .= Util::seo_url( (string) $clubs->shortcode ) . '/';
                }
                $competition->entry_link = $entry_link;
            }
        }

        $template = ! empty( $query->template ) ? 'competition-' . $query->template : 'competition';

        return new Competition_Page_Result(
            competition: $competition,
            competition_season: $competition_season,
            tab: $tab,
            seasons: $seasons,
            menu_options: $menu_options,
            is_singular: $is_singular,
            post_id: $post_id,
            pagename: $pagename,
            competition_seo_name: $competition_seo_name,
            template: $template
        );
    }

    /**
     * Check if club selection is available.
     *
     * @param Competition $competition Competition.
     *
     * @return object|int|bool|array
     */
    private function club_selection_available( Competition $competition ): object|int|bool|array {
        $userid        = $this->security_service->get_current_user_id();
        $can_manage    = $this->security_service->current_user_can( 'manage_racketmanager' );
        $args['type']  = 'affiliated';
        $args['club']  = false;
        $args['count'] = true;
        if ( $can_manage ) {
            $clubs = $this->club_service->get_clubs( $args );
        } else {
            $comp_type           = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? '' );
            $comp_type_str       = is_object( $comp_type ) && isset( $comp_type->value ) ? $comp_type->value : (string) $comp_type;
            $competition_options = $this->settings_service->get_category( $comp_type_str );
            $clubs               = null;
            if ( $competition_options ) {
                $entry_option = $competition_options['entry_level'] ?? null;
                if ( $entry_option ) {
                    $clubs = $this->player_service->find_all_associated_clubs( $userid, (string) $entry_option, null );
                }
            }
        }

        return $clubs ?: false;
    }
}
