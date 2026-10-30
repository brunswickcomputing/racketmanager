<?php
/**
 * Event action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Event
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Event;

use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Validator\Validator_Config;
use stdClass;

/**
 * Handles event configuration mutations (add event config, update event config).
 */
readonly final class Event_Action_Dispatcher {

    public function __construct(
        private Competition_Service $competition_service,
        private Competition_Season_Service $competition_season_service,
        private Action_Guard_Interface $action_guard,
        private Event_Repository_Interface $event_repository,
        private League_Service $league_service,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addEventConfig'] ) ) {
            return $this->handle_add_event_config( $query, $post );
        }

        if ( isset( $post['updateEventConfig'] ) ) {
            return $this->handle_update_event_config( $query, $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_add_event_config( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed(
                'racketmanager_nonce',
                'racketmanager_manage-event-config',
                'edit_leagues'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $event_id = isset( $post['event_id'] ) ? intval( $post['event_id'] ) : null;
        if ( ! empty( $event_id ) ) {
            return new Action_Result_DTO(
                message: __( 'Event id invalid for new event', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : ( isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : null );
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $config    = $this->get_config_input( $post );
        $validator = $this->handle_config_update( $config, $competition, true );

        if ( ! empty( $validator->error ) ) {
            $err_flds = $validator->err_flds ?? array();
            $err_msgs = $validator->err_msgs ?? array();
            $errors   = array();
            foreach ( $err_flds as $i => $field ) {
                $errors[ $field ] = $err_msgs[ $i ] ?? __( 'Invalid field', 'racketmanager' );
            }

            return new Action_Result_DTO(
                message: $validator->msg ?? __( 'Errors found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'errors'    => $errors,
                    'form_data' => $this->sanitize_form_data( $post ),
                ),
            );
        }

        $event_data                 = new stdClass();
        $event_data->name           = $config->name;
        $event_data->competition_id = $competition->id;
        $event_data->num_sets       = $config->num_sets;
        $event_data->num_rubbers    = $config->num_rubbers;
        $event_data->type           = $config->type;
        $event                      = Event_Factory::from_object( $event_data, $competition );
        $event_id                   = $this->event_repository->insert_event( $event );
        if ( ! $event_id ) {
            return new Action_Result_DTO(
                __( 'Could not create event', 'racketmanager' ),
                Admin_Message_Type::ERROR
            );
        }
        $event->set_id( (int) $event_id );

        $add_season = false;
        foreach ( $competition->get_seasons() as $competition_season ) {
            if ( isset( $competition->current_season['name'] ) && $competition_season['name'] === $competition->current_season['name'] ) {
                $add_season = true;
            }
            if ( $add_season ) {
                $event_season                   = array();
                $event_season['name']           = $competition_season['name'];
                $event_season['home_away']      = $competition_season['home_away'] ?? false;
                $event_season['num_match_days'] = $competition_season['num_match_days'] ?? 0;
                $event_season['match_dates']    = $competition_season['match_dates'] ?? array();
                $this->competition_season_service->add_season_to_event( $event, $event_season );
            }
        }

        try {
            $league    = $this->league_service->add_league_to_event( (int) $event->id, $event->name );
            $league_id = $league->id ?? false;
        } catch ( Event_Not_Found_Exception ) {
            $league_id = false;
        }
        if ( false !== $league_id ) {
            $config->primary_league = $league_id;
        }

        $league_error = null;
        if ( false === $league_id ) {
            $league_error = __( 'Could not create league for event', 'racketmanager' );
        } elseif ( ! empty( $competition->is_championship ) ) {
            try {
                $plate_league    = $this->league_service->add_league_to_event( (int) $event->id, $event->name . ' ' . __( 'Plate', 'racketmanager' ) );
                $plate_league_id = $plate_league->id ?? false;
            } catch ( Event_Not_Found_Exception ) {
                $plate_league_id = false;
            }
            if ( false === $plate_league_id ) {
                $league_error = __( 'Could not create plate league for event', 'racketmanager' );
            }
        }

        $updates = $event->set_config( $config );
        if ( $updates && ! empty( $event->id ) ) {
            $this->event_repository->update_settings_and_rules(
                (int) $event->id,
                $event->settings,
                $event->num_rubbers,
                $event->num_sets,
                $event->type
            );
            $this->event_repository->update_name( (int) $event->id, $event->name );
        }

        if ( null !== $league_error ) {
            return new Action_Result_DTO(
                message: $league_error,
                message_type: Admin_Message_Type::ERROR,
                entity_id: (int) $event->id,
                query_params: array(
                    'event_id' => $event->id,
                    'view'     => $query['view'] ?? 'event-config',
                ),
            );
        }

        return new Action_Result_DTO(
            message: __( 'Event added', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
            entity_id: (int) $event->id,
            query_params: array(
                'event_id' => $event->id,
                'view'     => $query['view'] ?? 'event-config',
            ),
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_update_event_config( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed(
                'racketmanager_nonce',
                'racketmanager_manage-event-config',
                'edit_leagues'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $event_id_passed = isset( $post['event_id'] ) ? intval( $post['event_id'] ) : null;
        $event_id_query  = isset( $query['event_id'] ) ? intval( $query['event_id'] ) : null;
        $event_id        = $event_id_query ?? $event_id_passed;

        if ( empty( $event_id ) || ( null !== $event_id_passed && $event_id !== $event_id_passed ) ) {
            return new Action_Result_DTO(
                message: __( 'Invalid event id', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $event = $this->competition_service->get_event_by_id( $event_id );
        } catch ( Event_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : ( isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : (int) $event->competition_id );
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $config    = $this->get_config_input( $post );
        $validator = $this->handle_config_update( $config, $competition, false );

        if ( ! empty( $validator->error ) ) {
            $err_flds = $validator->err_flds ?? array();
            $err_msgs = $validator->err_msgs ?? array();
            $errors   = array();
            foreach ( $err_flds as $i => $field ) {
                $errors[ $field ] = $err_msgs[ $i ] ?? __( 'Invalid field', 'racketmanager' );
            }

            return new Action_Result_DTO(
                message: $validator->msg ?? __( 'Errors found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'errors'    => $errors,
                    'form_data' => $this->sanitize_form_data( $post ),
                ),
            );
        }

        $updates = $event->set_config( $config );
        if ( $updates && ! empty( $event->id ) ) {
            $this->event_repository->update_settings_and_rules(
                (int) $event->id,
                $event->settings,
                $event->num_rubbers,
                $event->num_sets,
                $event->type
            );
            $this->event_repository->update_name( (int) $event->id, $event->name );
        }

        if ( $updates ) {
            return new Action_Result_DTO(
                message: __( 'Event config updated', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
                query_params: array(
                    'event_id' => $event->id,
                    'view'     => $query['view'] ?? 'event-config',
                ),
            );
        }

        return new Action_Result_DTO(
            message: __( 'No updates', 'racketmanager' ),
            message_type: Admin_Message_Type::WARNING,
            query_params: array(
                'event_id' => $event->id,
                'view'     => $query['view'] ?? 'event-config',
            ),
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function get_config_input( array $post ): stdClass {
        $config                     = new stdClass();
        $config->name               = isset( $post['event_name'] ) ? sanitize_text_field( wp_unslash( strval( $post['event_name'] ) ) ) : null;
        $config->type               = isset( $post['type'] ) ? sanitize_text_field( wp_unslash( strval( $post['type'] ) ) ) : null;
        $config->age_limit          = isset( $post['age_limit'] ) ? sanitize_text_field( wp_unslash( strval( $post['age_limit'] ) ) ) : null;
        $config->age_offset         = isset( $post['age_offset'] ) ? sanitize_text_field( wp_unslash( strval( $post['age_offset'] ) ) ) : null;
        $config->scoring            = isset( $post['scoring'] ) ? sanitize_text_field( wp_unslash( strval( $post['scoring'] ) ) ) : null;
        $config->num_sets           = isset( $post['num_sets'] ) ? intval( $post['num_sets'] ) : null;
        $config->num_rubbers        = isset( $post['num_rubbers'] ) ? intval( $post['num_rubbers'] ) : null;
        $config->reverse_rubbers    = isset( $post['reverse_rubbers'] ) ? intval( $post['reverse_rubbers'] ) : null;
        $config->match_days_allowed = isset( $post['match_days_allowed'] ) ? wp_unslash( $post['match_days_allowed'] ) : null;
        $config->offset             = isset( $post['offset'] ) ? sanitize_text_field( wp_unslash( strval( $post['offset'] ) ) ) : null;
        $config->primary_league     = isset( $post['primary_league'] ) ? intval( $post['primary_league'] ) : null;

        return $config;
    }

    /**
     * Recursively sanitize input values
     *
     * @param mixed $value
     * @return mixed
     */
    private function sanitize_recursive( mixed $value ): mixed {
        if ( is_array( $value ) ) {
            $cleaned = array();
            foreach ( $value as $k => $v ) {
                $clean_key             = is_scalar( $k ) ? sanitize_text_field( wp_unslash( strval( $k ) ) ) : $k;
                $cleaned[ $clean_key ] = $this->sanitize_recursive( $v );
            }
            return $cleaned;
        }
        if ( is_scalar( $value ) ) {
            return sanitize_text_field( wp_unslash( strval( $value ) ) );
        }
        return null;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function sanitize_form_data( array $post ): array {
        $whitelist = array(
            'event_id', 'event_name', 'type', 'age_limit', 'age_offset',
            'scoring', 'num_sets', 'num_rubbers', 'reverse_rubbers',
            'match_days_allowed', 'offset', 'primary_league',
        );

        $sanitized = array();
        foreach ( $whitelist as $field ) {
            if ( ! isset( $post[ $field ] ) ) {
                continue;
            }
            $sanitized[ $field ] = $this->sanitize_recursive( $post[ $field ] );
        }

        return $sanitized;
    }

    /**
     * @param object $config
     * @param object $competition
     * @param bool   $new
     */
    private function handle_config_update( object $config, object $competition, bool $new = false ): object {
        $validator = new Validator_Config();
        $validator = $validator->name( $config->name );
        $validator = $validator->type( $config->type );
        $validator = $validator->age_limit( $config->age_limit );
        $validator = $validator->age_offset( $config->age_offset );
        $validator = $validator->scoring( $config->scoring );
        $validator = $validator->num_sets( $config->num_sets );
        if ( ! empty( $competition->is_team_entry ) ) {
            $validator = $validator->num_rubbers( $config->num_rubbers );
        }
        if ( empty( $competition->is_tournament ) ) {
            $validator = $validator->offset( $config->offset );
        }
        if ( ! empty( $competition->is_championship ) && ! $new ) {
            $validator = $validator->primary_league( $config->primary_league );
        }

        return $validator->get_details();
    }
}
