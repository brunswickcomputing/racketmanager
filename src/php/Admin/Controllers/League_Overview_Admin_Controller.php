<?php
declare( strict_types=1 );
/**
 * League Overview Admin Controller
 *
 * Handles display and actions for league season overview view.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Entry_Link_Presenter;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\League_Overview_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

final readonly class League_Overview_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        private Admin_Entry_Link_Presenter $entry_link_presenter,
        private League_Contact_Action_Dispatcher $league_contact_dispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?: League_Overview_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string}
     *
     * @throws Competition_Not_Found_Exception
     * @throws Season_Not_Found_Exception
     */
    public function overview_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_leagues' );

        if ( isset( $post['contactTeam'] ) ) {
            $result = $this->league_contact_dispatcher->handle( $post );

            $redirect = Admin_Redirect_Url_Builder::league_screen( $query );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
            );
        }

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $competition = $this->competition_service->get_competition( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $season  = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : null;
        $seasons = $competition->get_seasons();

        if ( empty( $season ) || ( ! empty( $seasons ) && empty( $seasons[ $season ] ) && ! in_array( $season, $seasons, true ) ) ) {
            throw new Season_Not_Found_Exception( __( 'Season not found', 'racketmanager' ) );
        }

        $competition_overview = $this->competition_service->get_competition_overview( $competition_id, (int) $season );
        $competition_events   = $this->competition_service->get_events_with_details_for_competition( $competition_id, (string) $season );
        $tab                  = 'overview';

        $current_season_data = $competition->get_season_by_name( (string) $season );
        $current_season      = is_object( $current_season_data ) ? $current_season_data : ( (object) ( $current_season_data ?? array() ) );

        if ( isset( $current_season->date_closing ) && $current_season->date_closing <= gmdate( 'Y-m-d' ) ) {
            $current_season->is_active = true;
        } else {
            $current_season->is_active = false;
        }
        $current_season->is_open = false;
        $current_season->entries = $this->competition_service->get_clubs_for_competition( $competition_id, (int) $season );

        $entry_urls = $this->entry_link_presenter->club_entry_urls(
            $competition,
            (string) $season,
            (array) ( $current_season->entries ?? array() )
        );

        $vm = new League_Overview_Page_View_Model(
            competition: $competition,
            season: (string) $season,
            competition_overview: $competition_overview,
            competition_events: $competition_events,
            current_season: $current_season,
            entry_urls: $entry_urls,
            tab: $tab,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
