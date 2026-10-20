<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Presentation\View_Models\Competition_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use function __;

/**
 * Query handler for resolving [competition] shortcode data into a view model.
 */
class Get_Competition_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Club_Service $club_service,
        private readonly Player_Service $player_service,
        private readonly Security_Service_Interface $security_service,
        private readonly Settings_Service $settings_service,
        private readonly Competition_Season_Service $competition_season_service
    ) {}

    public function handle( Get_Competition_Query $query ): Competition_View_Model {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return new Competition_View_Model(
                error_message: __( 'Competition not found', 'racketmanager' )
            );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = Util::un_seo_url( $comp_id );
        }

        try {
            $competition = $this->competition_service->get_by_id( $comp_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Competition_View_Model(
                error_message: $e->getMessage()
            );
        }

        if ( ! $competition ) {
            return new Competition_View_Model(
                error_message: __( 'Competition not found', 'racketmanager' )
            );
        }

        $seasons = $competition->get_seasons();
        if ( empty( $seasons ) ) {
            return new Competition_View_Model(
                error_message: __( 'No seasons found for competition', 'racketmanager' )
            );
        }

        $competition_season = $this->competition_season_service->resolve_current_season( $competition, $query->season ?? '' );
        if ( empty( array_diff_key( $competition_season, [ 'venue_name' => true ] ) ) ) {
            return new Competition_View_Model(
                error_message: __( 'Season not found for competition', 'racketmanager' )
            );
        }

        $season = $competition_season['name'] ?? '';

        $tab                  = $query->tab ?: ( Util_Lookup::get_tab() ?: 'overview' );
        $is_singular          = $query->is_singular;
        $competition_seo_name = Util::seo_url( (string) ( $competition->name ?? '' ) );
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
        $menu_options['winners']  = [
            'name'        => 'winners',
            'selected'    => 'winners' === $tab,
            'available'   => empty( $competition_season['date_end'] ) || gmdate( 'Y-m-d' ) >= $competition_season['date_end'],
            'description' => __( 'Winners', 'racketmanager' ),
        ];

        $is_logged_in = $this->security_service->is_user_logged_in();

        if ( ! empty( $competition->is_open ) && $is_logged_in ) {
            $comp_name  = (string) ( $competition->name ?? '' );
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

        return new Competition_View_Model(
            template: $template,
            competition: $competition,
            competition_season: $competition_season,
            tab: $tab,
            seasons: $seasons,
            menu_options: $menu_options,
            is_singular: $is_singular,
            post_id: $post_id,
            pagename: $pagename,
            competition_seo_name: $competition_seo_name
        );
    }

    private function club_selection_available( Competition $competition, false|int $club_id = false ): object|int|bool|array {
        $userid       = $this->security_service->get_current_user_id();
        $can_manage   = $this->security_service->current_user_can( 'manage_racketmanager' );
        $args['type'] = 'affiliated';
        if ( $club_id ) {
            $args['club']  = $club_id;
            $args['count'] = true;
        }
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
                    $clubs = $this->player_service->find_all_associated_clubs( $userid, (string) $entry_option, $club_id ?: null );
                }
            }
        }

        return $clubs ?: false;
    }
}
