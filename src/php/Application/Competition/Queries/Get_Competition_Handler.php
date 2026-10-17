<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Presentation\View_Models\Competition_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Util\Util_Lookup;
use function Racketmanager\seo_url;
use function Racketmanager\un_seo_url;

/**
 * Query handler for resolving [competition] shortcode data into a view model.
 */
class Get_Competition_Handler {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Club_Service $club_service,
        private readonly Player_Service $player_service,
        private readonly Security_Service_Interface $security_service,
        private readonly Settings_Service $settings_service
    ) {}

    public function handle( Get_Competition_Query $query ): Competition_View_Model {
        $comp_id = $query->competition_id ?? $query->competition_name;
        if ( null === $comp_id || '' === $comp_id ) {
            return new Competition_View_Model(
                error_message: __( 'Competition not found', 'racketmanager' )
            );
        }

        if ( is_string( $comp_id ) ) {
            $comp_id = un_seo_url( $comp_id );
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

        $competition->set_current_season( $query->season ?? '' );
        if ( empty( $competition->current_season ) ) {
            return new Competition_View_Model(
                error_message: __( 'Season not found for competition', 'racketmanager' )
            );
        }

        $season             = $competition->current_season['name'] ?? '';
        $competition_season = $competition->current_season;

        if ( ! empty( $competition_season['venue'] ) ) {
            try {
                $venue_club = $this->club_service->get_club( (int) $competition_season['venue'] );
                if ( $venue_club ) {
                    $competition_season['venue_name'] = $venue_club->shortcode ?? ( method_exists( $venue_club, 'get_shortcode' ) ? $venue_club->get_shortcode() : null );
                }
            } catch ( Club_Not_Found_Exception ) {
                // Ignore missing venue club gracefully
            }
        }

        $tab = Util_Lookup::get_tab();

        $is_logged_in = $this->security_service->is_user_logged_in();

        if ( ! empty( $competition->is_open ) && $is_logged_in ) {
            $comp_name  = (string) ( $competition->name ?? '' );
            $entry_link = '/entry-form/' . seo_url( $comp_name ) . '/' . $season . '/';
            $clubs      = $this->club_selection_available( $competition );
            if ( $clubs ) {
                if ( is_array( $clubs ) ) {
                    if ( 1 === count( $clubs ) ) {
                        $first_club = reset( $clubs );
                        if ( is_object( $first_club ) && isset( $first_club->shortcode ) ) {
                            $entry_link .= seo_url( (string) $first_club->shortcode ) . '/';
                        }
                    }
                } elseif ( is_object( $clubs ) && isset( $clubs->shortcode ) ) {
                    $entry_link .= seo_url( (string) $clubs->shortcode ) . '/';
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
            seasons: $seasons
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
