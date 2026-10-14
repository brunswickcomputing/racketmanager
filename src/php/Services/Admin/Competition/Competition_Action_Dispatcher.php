<?php
/**
 * Competition action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Competition
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Competition;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use stdClass;
use function Racketmanager\get_event;

/**
 * Handles competition configuration mutations and bulk event deletions for the competition admin screens.
 */
readonly final class Competition_Action_Dispatcher {

    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['updateCompetitionConfig'] ) ) {
            return $this->handle_update_competition_config( $query, $post );
        }

        if ( isset( $post['doActionEvent'] ) ) {
            return $this->handle_do_action_event( $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_update_competition_config( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed(
                'racketmanager_nonce',
                'racketmanager_manage-competition-config',
                'edit_leagues'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $competition_id_passed = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : null;
        $competition_id_query  = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
        $competition_id        = $competition_id_query ?? $competition_id_passed;

        if ( empty( $competition_id ) || ( null !== $competition_id_passed && $competition_id !== $competition_id_passed ) ) {
            return new Action_Result_DTO(
                message: __( 'Invalid competition id', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $config = $this->get_config_input( $post );

        try {
            $updates = $this->competition_service->amend_details( $competition_id, $config );
            if ( is_wp_error( $updates ) ) {
                $err_flds = $updates->get_error_codes();
                $err_msgs = $updates->get_error_messages();
                $errors   = array();
                foreach ( $err_flds as $i => $field ) {
                    $errors[ $field ] = $err_msgs[ $i ] ?? __( 'Invalid field', 'racketmanager' );
                }

                return new Action_Result_DTO(
                    message: __( 'Error with competition details', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                    flash_data: array(
                        'errors'    => $errors,
                        'form_data' => $this->sanitize_form_data( $post ),
                    ),
                );
            }

            if ( $updates ) {
                return new Action_Result_DTO(
                    message: __( 'Competition updated', 'racketmanager' ),
                    message_type: Admin_Message_Type::SUCCESS,
                );
            }

            return new Action_Result_DTO(
                message: __( 'No updates', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        } catch ( Competition_Not_Updated_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
            );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    /**
     * @param array<string, mixed> $post
     */
    private function handle_do_action_event( array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed(
                'racketmanager_event_nonce',
                'racketmanager_events-bulk',
                'edit_leagues'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
                tab_override: 'events',
            );
        }

        if ( isset( $post['action'] ) && 'delete' === $post['action'] ) {
            $events = isset( $post['event'] ) ? (array) wp_unslash( $post['event'] ) : null;
            if ( ! empty( $events ) ) {
                $msg = array();
                foreach ( $events as $event_id ) {
                    $event = get_event( (int) $event_id );
                    if ( null !== $event ) {
                        $event->delete();
                        /* translators: %s: event name */
                        $msg[] = sprintf( __( '%s has been deleted', 'racketmanager' ), $event->name );
                    }
                }

                if ( ! empty( $msg ) ) {
                    return new Action_Result_DTO(
                        message: implode( '<br>', $msg ),
                        message_type: Admin_Message_Type::SUCCESS,
                        tab_override: 'events',
                    );
                }
            }

            return new Action_Result_DTO(
                message: __( 'No events were found to delete', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
                tab_override: 'events',
            );
        }

        return new Action_Result_DTO(
            message: __( 'No action specified', 'racketmanager' ),
            message_type: Admin_Message_Type::WARNING,
            tab_override: 'events',
        );
    }

    /**
     * @param array<string, mixed> $post
     */
    private function get_config_input( array $post ): stdClass {
        $config                               = new stdClass();
        $config->name                         = isset( $post['competition_title'] ) ? sanitize_text_field( wp_unslash( strval( $post['competition_title'] ) ) ) : null;
        $config->sport                        = isset( $post['sport'] ) ? sanitize_text_field( wp_unslash( strval( $post['sport'] ) ) ) : null;
        $config->type                         = isset( $post['type'] ) ? sanitize_text_field( wp_unslash( strval( $post['type'] ) ) ) : null;
        $config->mode                         = isset( $post['mode'] ) ? sanitize_text_field( wp_unslash( strval( $post['mode'] ) ) ) : null;
        $config->entry_type                   = isset( $post['entry_type'] ) ? sanitize_text_field( wp_unslash( strval( $post['entry_type'] ) ) ) : null;
        $config->age_group                    = isset( $post['age_group'] ) ? sanitize_text_field( wp_unslash( strval( $post['age_group'] ) ) ) : null;
        $config->competition_code             = isset( $post['competition_code'] ) ? sanitize_text_field( wp_unslash( strval( $post['competition_code'] ) ) ) : null;
        $config->grade                        = isset( $post['grade'] ) ? sanitize_text_field( wp_unslash( strval( $post['grade'] ) ) ) : null;
        $config->max_teams                    = isset( $post['max_teams'] ) ? intval( $post['max_teams'] ) : null;
        $config->num_entries                  = isset( $post['num_entries'] ) ? intval( $post['num_entries'] ) : null;
        $config->teams_per_club               = isset( $post['teams_per_club'] ) ? intval( $post['teams_per_club'] ) : null;
        $config->teams_prom_relg              = isset( $post['teams_prom_relg'] ) ? intval( $post['teams_prom_relg'] ) : null;
        $config->lowest_promotion             = isset( $post['lowest_promotion'] ) ? intval( $post['lowest_promotion'] ) : null;
        $config->team_ranking                 = isset( $post['team_ranking'] ) ? sanitize_text_field( wp_unslash( strval( $post['team_ranking'] ) ) ) : null;
        $config->point_rule                   = isset( $post['point_rule'] ) ? sanitize_text_field( wp_unslash( strval( $post['point_rule'] ) ) ) : null;
        $config->scoring                      = isset( $post['scoring'] ) ? sanitize_text_field( wp_unslash( strval( $post['scoring'] ) ) ) : null;
        $config->num_sets                     = isset( $post['num_sets'] ) ? intval( $post['num_sets'] ) : null;
        $config->num_rubbers                  = isset( $post['num_rubbers'] ) ? intval( $post['num_rubbers'] ) : null;
        $config->reverse_rubbers              = isset( $post['reverse_rubbers'] ) && 'true' === $post['reverse_rubbers'];
        $config->fixed_match_dates            = isset( $post['fixed_match_dates'] ) && 'true' === $post['fixed_match_dates'];
        $config->home_away                    = isset( $post['home_away'] ) && 'true' === $post['home_away'];
        $config->round_length                 = isset( $post['round_length'] ) ? intval( $post['round_length'] ) : null;
        $config->home_away_diff               = isset( $post['home_away_diff'] ) ? intval( $post['home_away_diff'] ) : null;
        $config->filler_weeks                 = isset( $post['filler_weeks'] ) ? intval( $post['filler_weeks'] ) : null;
        $config->match_day_restriction        = isset( $post['match_day_restriction'] );
        $config->match_day_weekends           = isset( $post['match_day_weekends'] ) && 'true' === $post['match_day_weekends'];
        $config->match_days_allowed           = isset( $post['match_days_allowed'] ) && is_array( $post['match_days_allowed'] ) ? array_map( 'intval', $post['match_days_allowed'] ) : null;
        $default_match_start_time_string      = isset( $post['default_match_start_time'] ) ? sanitize_text_field( wp_unslash( strval( $post['default_match_start_time'] ) ) ) : null;
        $default_match_start_time             = $default_match_start_time_string ? explode( ':', $default_match_start_time_string ) : array();
        if ( 2 !== count( $default_match_start_time ) ) {
            $config->default_match_start_time['hour']    = '0';
            $config->default_match_start_time['minutes'] = '00';
        } else {
            $config->default_match_start_time['hour']    = intval( $default_match_start_time[0] );
            $config->default_match_start_time['minutes'] = intval( $default_match_start_time[1] );
        }
        $config->start_time['weekday']['min'] = isset( $post['min_start_time_weekday'] ) ? sanitize_text_field( wp_unslash( strval( $post['min_start_time_weekday'] ) ) ) : null;
        $config->start_time['weekday']['max'] = isset( $post['max_start_time_weekday'] ) ? sanitize_text_field( wp_unslash( strval( $post['max_start_time_weekday'] ) ) ) : null;
        $config->start_time['weekend']['min'] = isset( $post['min_start_time_weekend'] ) ? sanitize_text_field( wp_unslash( strval( $post['min_start_time_weekend'] ) ) ) : null;
        $config->start_time['weekend']['max'] = isset( $post['max_start_time_weekend'] ) ? sanitize_text_field( wp_unslash( strval( $post['max_start_time_weekend'] ) ) ) : null;
        $config->point_format                 = isset( $post['point_format'] ) ? sanitize_text_field( wp_unslash( strval( $post['point_format'] ) ) ) : null;
        $config->point_2_format               = isset( $post['point_2_format'] ) ? sanitize_text_field( wp_unslash( strval( $post['point_2_format'] ) ) ) : null;
        $config->num_matches_per_page         = isset( $post['num_matches_per_page'] ) ? intval( $post['num_matches_per_page'] ) : null;
        $config->rules                        = isset( $post['rules'] ) && is_array( $post['rules'] ) ? array_map( 'intval', $post['rules'] ) : null;
        $config->standings                    = isset( $post['standings'] ) ? wp_unslash( $post['standings'] ) : null;
        $config->num_courts_available         = isset( $post['num_courts_available'] ) && is_array( $post['num_courts_available'] ) ? array_map( 'intval', $post['num_courts_available'] ) : array();

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
            'competition_title', 'sport', 'type', 'mode', 'entry_type', 'age_group',
            'competition_code', 'grade', 'max_teams', 'num_entries', 'teams_per_club',
            'teams_prom_relg', 'lowest_promotion', 'team_ranking', 'point_rule',
            'scoring', 'num_sets', 'num_rubbers', 'reverse_rubbers', 'fixed_match_dates',
            'home_away', 'round_length', 'home_away_diff', 'filler_weeks',
            'match_day_restriction', 'match_day_weekends', 'match_days_allowed',
            'default_match_start_time', 'min_start_time_weekday', 'max_start_time_weekday',
            'min_start_time_weekend', 'max_start_time_weekend', 'point_format',
            'point_2_format', 'num_matches_per_page', 'rules', 'standings', 'num_courts_available',
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
}
