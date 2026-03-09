<?php
/**
 * Tournament event config admin controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Domain\Event;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Config;
use stdClass;

final class Tournament_Event_Config_Admin_Controller {

    /**
     * @param Tournament_Service $tournament_service
     * @param Competition_Service $competition_service
     * @param Action_Guard_Interface $action_guard
     */
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Competition_Service $competition_service,
        private readonly Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle the display of the tournament event config page.
     *
     * @return array{view_model?:Tournament_Event_Config_Page_View_Model, message?:string, message_type?:bool|string, redirect?:string}
     */
    public function handle(): array {
        try {
            $this->action_guard->assert_capability( 'edit_leagues' );
        } catch ( \Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        $tournament    = null;
        if ( $tournament_id ) {
            try {
                $tournament = $this->tournament_service->get_tournament( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                return array(
                    'message'      => $e->getMessage(),
                    'message_type' => true,
                );
            }
        }

        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null;
        try {
            $event     = $this->competition_service->get_event_by_id( $event_id );
            $new_event = false;
        } catch ( Event_Not_Found_Exception ) {
            $event     = new stdClass();
            $new_event = true;
        }

        if ( $new_event ) {
            if ( isset( $_POST['addEventConfig'] ) ) {
                return $this->handle_add( $competition, $tournament );
            }
        } else {
            $event->config              = (object) $event->settings;
            $event->config->num_sets    = $event->num_sets;
            $event->config->num_rubbers = $event->num_rubbers;

            if ( isset( $_POST['updateEventConfig'] ) ) {
                return $this->handle_update( $event, $competition, $tournament );
            }
        }

        return array(
            'view_model' => new Tournament_Event_Config_Page_View_Model(
                $competition,
                $event,
                $tournament,
                $new_event
            ),
        );
    }

    /**
     * Handle adding a new event.
     *
     * @param object $competition
     * @param object|null $tournament
     * @return array
     */
    private function handle_add( object $competition, ?object $tournament ): array {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-event-config', 'edit_leagues' );
        } catch ( \Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
        if ( ! empty( $event_id ) ) {
            return array(
                'message'      => __( 'Event id invalid for new event', 'racketmanager' ),
                'message_type' => true,
            );
        }

        $config = $this->get_config_input();
        
        // Legacy validation and creation logic from Admin_Event::display_config_page
        // Note: For now, we are keeping the logic similar to legacy for safety, but in a Controller.
        $validator = new Validator_Config();
        $validator = $this->handle_config_update_validation( $config, $competition, true );

        if ( ! empty( $validator->error ) ) {
            return array(
                'message'      => $validator->msg ?: __( 'Errors found', 'racketmanager' ),
                'message_type' => true,
                'view_model'   => new Tournament_Event_Config_Page_View_Model( $competition, (object)['config' => $config], $tournament, true )
            );
        }

        // Concrete creation logic
        $event_data = new stdClass();
        $event_data->name           = $config->name;
        $event_data->competition_id = $competition->id;
        $event_data->num_sets       = $config->num_sets;
        $event_data->num_rubbers    = $config->num_rubbers;
        $event_data->type           = $config->type;
        $event = new Event( $event_data );

        // Add seasons
        $add_season = false;
        foreach ( $competition->get_seasons() as $competition_season ) {
            if ( $competition_season['name'] === $competition->current_season['name'] ) {
                $add_season = true;
            }
            if ( $add_season ) {
                $event_season = array(
                    'name'           => $competition_season['name'],
                    'home_away'      => $competition_season['home_away'],
                    'num_match_days' => $competition_season['num_match_days'],
                    'match_dates'    => $competition_season['match_dates'],
                );
                $event->add_season( $event_season );
            }
        }

        $league_id = $event->add_league( $event->name );
        if ( $league_id && $competition->is_championship ) {
            $config->primary_league = $league_id;
            $league_title = $event->name . ' ' . __( 'Plate', 'racketmanager' );
            $event->add_league( $league_title );
        }

        $event->config = $config;
        $updates       = $event->set_config( $config );

        if ( $updates ) {
            return array(
                'message'      => __( 'Event added', 'racketmanager' ),
                'message_type' => 'success',
                'redirect'     => add_query_arg( array( 'event_id' => $event->id, 'message' => rawurlencode( __( 'Event added', 'racketmanager' ) ), 'message_type' => 'success' ) ),
            );
        } else {
            return array(
                'message'      => __( 'No updates', 'racketmanager' ),
                'message_type' => 'warning',
                'redirect'     => add_query_arg( array( 'event_id' => $event->id ) ),
            );
        }
    }

    /**
     * Handle updating an existing event.
     *
     * @param Event $event
     * @param object $competition
     * @param object|null $tournament
     * @return array
     */
    private function handle_update( Event $event, object $competition, ?object $tournament ): array {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-event-config', 'edit_leagues' );
        } catch ( \Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        $event_id_passed = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : null;
        if ( $event_id_passed !== $event->id ) {
            return array(
                'message'      => __( 'Event mismatch', 'racketmanager' ),
                'message_type' => true,
            );
        }

        $config = $this->get_config_input();
        $validator = $this->handle_config_update_validation( $config, $competition );

        if ( ! empty( $validator->error ) ) {
            return array(
                'message'      => $validator->msg ?: __( 'Errors found', 'racketmanager' ),
                'message_type' => true,
            );
        }

        $event->config = $config;
        $updates       = $event->set_config( $config );

        if ( $updates ) {
            return array(
                'message'      => __( 'Event updated', 'racketmanager' ),
                'message_type' => 'success',
                'redirect'     => add_query_arg( array( 'message' => rawurlencode( __( 'Event updated', 'racketmanager' ) ), 'message_type' => 'success' ) ),
            );
        } else {
            return array(
                'message'      => __( 'No updates', 'racketmanager' ),
                'message_type' => 'warning',
            );
        }
    }

    /**
     * Get config input from POST.
     *
     * @return stdClass
     */
    private function get_config_input(): stdClass {
        $config              = new stdClass();
        $config->name        = isset( $_POST['event_title'] ) ? sanitize_text_field( wp_unslash( $_POST['event_title'] ) ) : null;
        $config->type        = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
        $config->num_sets    = isset( $_POST['num_sets'] ) ? intval( $_POST['num_sets'] ) : null;
        $config->num_rubbers = isset( $_POST['num_rubbers'] ) ? intval( $_POST['num_rubbers'] ) : null;
        $config->is_active   = isset( $_POST['is_active'] ) ? filter_var( wp_unslash( $_POST['is_active'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        return $config;
    }

    /**
     * Validation logic ported from Admin_Event::handle_config_update
     *
     * @param object $config
     * @param object $competition
     * @param bool $new
     * @return Validator_Config
     */
    private function handle_config_update_validation( object $config, object $competition, bool $new = false ): Validator_Config {
        $validator = new Validator_Config();
        if ( empty( $config->name ) ) {
            $validator->error = true;
            $validator->msg   = __( 'Event name required', 'racketmanager' );
        } elseif ( $new && $competition->get_event( $config->name ) ) {
            $validator->error = true;
            $validator->msg   = __( 'Event already exists', 'racketmanager' );
        }
        return $validator;
    }
}
