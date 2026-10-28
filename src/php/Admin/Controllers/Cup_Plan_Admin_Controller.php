<?php
declare( strict_types=1 );
/**
 * Cup Plan Admin Controller
 *
 * Handles view=plan on cup screens (order of play / finals planning).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Error_Bag_Mapper;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\Presenters\Admin_Plan_Presenter;
use Racketmanager\Admin\View_Models\Cup_Plan_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Services\Admin\Cup\Cup_Plan_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Validator\Validator_Plan;

final readonly class Cup_Plan_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private Club_Repository_Interface $club_repository,
        private Admin_Plan_Presenter $admin_plan_presenter,
        private Cup_Plan_Action_Dispatcher $cup_plan_action_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query      Typically $_GET
     * @param array<string, mixed> $post       Typically $_POST
     * @param array<string, mixed> $flash_data Flash data from bridge
     *
     * @return array{view_model?:Cup_Plan_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string, flash_data?:array<string, mixed>}
     *
     * @throws Invalid_Status_Exception
     * @throws Competition_Not_Found_Exception
     * @throws Season_Not_Found_Exception
     */
    public function plan_page( array $query, array $post, array $flash_data = array() ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method );

        if ( $is_post ) {
            try {
                $result = $this->cup_plan_action_dispatcher->handle( $query, $post );
            } catch ( Invalid_Status_Exception $e ) {
                $result = new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
            }

            if ( null !== $result->message || null !== $result->message_type || null !== $result->tab_override || ! empty( $result->flash_data ) || ! empty( $result->query_params ) ) {
                $extra_args = array();
                if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                    $extra_args['tab'] = $result->tab_override;
                }
                $extra_args = array_merge( $extra_args, $result->query_params );

                return array(
                    'redirect'     => Admin_Redirect_Url_Builder::cup_screen( $query, $extra_args ),
                    'message'      => $result->message,
                    'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                    'flash_data'   => $result->flash_data,
                );
            }
        }

        $validator = new Validator_Plan();
        if ( ! empty( $flash_data['err_flds'] ) && is_array( $flash_data['err_flds'] ) ) {
            $validator->error    = true;
            $validator->err_flds = $flash_data['err_flds'];
            $validator->err_msgs = is_array( $flash_data['err_msgs'] ?? null ) ? $flash_data['err_msgs'] : array();
        }

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : ( isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : null );
        $season         = isset( $query['season'] ) ? intval( $query['season'] ) : ( isset( $post['season'] ) ? intval( $post['season'] ) : null );

        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }
        if ( ! $season ) {
            throw new Season_Not_Found_Exception( __( 'Season not found', 'racketmanager' ) );
        }

        $competition        = $this->competition_service->get_competition_by_season( $competition_id, $season );
        $competition_events = $this->competition_service->get_events_for_competition( $competition_id, $season );
        $num_events         = count( $competition_events );
        $cup_season_data    = $competition->get_season_by_name( (string) $season );
        $cup_season         = is_object( $cup_season_data ) ? $cup_season_data : ( (object) ( $cup_season_data ?? array() ) );
        $cup_season->venue_name = null;
        if ( isset( $cup_season->venue ) ) {
            $venue_club = $cup_season->venue ? $this->club_repository->find_by_id( (int) $cup_season->venue ) : null;
            if ( $venue_club ) {
                $cup_season->venue_name = $venue_club->shortcode;
            }
        }
        if ( ! empty( $flash_data['form_data'] ) && is_array( $flash_data['form_data'] ) ) {
            $form = $flash_data['form_data'];
            if ( isset( $form['startTime'] ) ) {
                $cup_season->starttime = $form['startTime'];
            }
            if ( isset( $form['timeIncrement'] ) ) {
                $cup_season->time_increment = $form['timeIncrement'];
            }
            if ( isset( $form['numCourtsAvailable'] ) ) {
                $cup_season->num_courts = $form['numCourtsAvailable'];
            }
        }
        if ( ! isset( $cup_season->orderofplay ) ) {
            $cup_season->orderofplay = array();
        }
        if ( ! isset( $cup_season->time_increment ) ) {
            $cup_season->time_increment = null;
        }
        if ( ! isset( $cup_season->num_courts ) ) {
            $cup_season->num_courts = null;
        }
        if ( ! isset( $cup_season->starttime ) ) {
            $cup_season->starttime = null;
        }

        $final_matches = $this->competition_service->get_matches_for_competition(
            $competition,
            array(
                'season' => $season,
                'final'  => 'final',
            )
        );
        $tab = isset( $query['tab'] ) ? sanitize_text_field( wp_unslash( strval( $query['tab'] ) ) ) : 'matches';

        $errors = Admin_Error_Bag_Mapper::from_validator( $validator );

        $vm = $this->admin_plan_presenter->present_cup_plan(
            competition: $competition,
            cup_season: $cup_season,
            season: (string) $season,
            final_matches: is_array( $final_matches ) ? $final_matches : array(),
            tab: $tab,
            num_events: $num_events,
            errors: $errors,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
