<?php
/**
 * Tournament competition config admin controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator_Config;
use stdClass;

final class Tournament_Competition_Config_Admin_Controller {

    /**
     * @param Tournament_Service $tournament_service
     * @param Competition_Service $competition_service
     * @param Club_Service $club_service
     * @param Action_Guard_Interface $action_guard
     */
    public function __construct(
        private readonly Tournament_Service $tournament_service,
        private readonly Competition_Service $competition_service,
        private readonly Club_Service $club_service,
        private readonly Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle the display of the tournament competition config page.
     *
     * @return array{view_model?:Tournament_Competition_Config_Page_View_Model, message?:string, message_type?:bool|string, redirect?:string}
     */
    public function handle(): array {
        $validator = new Validator_Config();
        try {
            $this->action_guard->assert_capability( 'edit_leagues' );
        } catch ( \Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        if ( ! $competition_id ) {
            return array(
                'message'      => __( 'Competition not specified', 'racketmanager' ),
                'message_type' => true,
            );
        }

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

        if ( isset( $_POST['updateCompetitionConfig'] ) ) {
            return $this->handle_update( $competition );
        }

        // Logic for display only
        $competition->config            = (object) $competition->get_settings();
        $competition->config->type      = $competition->type;
        $competition->config->age_group = $competition->age_group;

        return array(
            'view_model' => $this->build_view_model( $competition, $tournament ),
        );
    }

    /**
     * Handle the update of the competition config.
     *
     * @param object $competition
     * @return array{message?:string, message_type?:bool|string, redirect?:string, view_model?:Tournament_Competition_Config_Page_View_Model}
     */
    private function handle_update( object $competition ): array {
        $validator = new Validator_Config();
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-competition-config', 'edit_leagues' );
        } catch ( \Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        $competition_id_passed = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
        if ( $competition_id_passed !== $competition->id ) {
            return array(
                'message'      => __( 'Competition mismatch', 'racketmanager' ),
                'message_type' => true,
            );
        }

        $config = $this->get_config_input();
        try {
            $updates = $this->competition_service->amend_details( $competition->id, $config );
            if ( is_wp_error( $updates ) ) {
                return array(
                    'message'      => __( 'Error with competition details', 'racketmanager' ),
                    'message_type' => true,
                );
            } elseif ( $updates ) {
                return array(
                    'message'      => __( 'Competition updated', 'racketmanager' ),
                    'message_type' => 'success',
                    'redirect'     => add_query_arg( array( 'message' => rawurlencode( __( 'Competition updated', 'racketmanager' ) ), 'message_type' => 'success' ) ),
                );
            } else {
                return array(
                    'message'      => __( 'No updates', 'racketmanager' ),
                    'message_type' => 'warning',
                );
            }
        } catch ( Competition_Not_Updated_Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => 'warning',
            );
        } catch ( Competition_Not_Found_Exception $e ) {
            return array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }
    }

    /**
     * Get config input from POST.
     *
     * @return stdClass
     */
    private function get_config_input(): stdClass {
        $config                               = new stdClass();
        $config->name                         = isset( $_POST['competition_title'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_title'] ) ) : null;
        $config->sport                        = isset( $_POST['sport'] ) ? sanitize_text_field( wp_unslash( $_POST['sport'] ) ) : null;
        $config->type                         = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
        $config->mode                         = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : null;
        $config->entry_type                   = isset( $_POST['entry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entry_type'] ) ) : null;
        $config->age_group                    = isset( $_POST['age_group'] ) ? sanitize_text_field( wp_unslash( $_POST['age_group'] ) ) : null;
        $config->competition_code             = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
        $config->grade                        = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : null;
        $config->max_teams                    = isset( $_POST['max_teams'] ) ? intval( $_POST['max_teams'] ) : null;
        $config->num_entries                  = isset( $_POST['num_entries'] ) ? intval( $_POST['num_entries'] ) : null;
        $config->teams_per_club               = isset( $_POST['teams_per_club'] ) ? intval( $_POST['teams_per_club'] ) : null;
        $config->teams_prom_relg              = isset( $_POST['teams_prom_relg'] ) ? intval( $_POST['teams_prom_relg'] ) : null;
        $config->lowest_promotion             = isset( $_POST['lowest_promotion'] ) ? intval( $_POST['lowest_promotion'] ) : null;
        $config->teams_per_league             = isset( $_POST['teams_per_league'] ) ? intval( $_POST['teams_per_league'] ) : null;
        $config->max_leagues                  = isset( $_POST['max_leagues'] ) ? intval( $_POST['max_leagues'] ) : null;
        $config->is_championship              = isset( $_POST['is_championship'] ) ? filter_var( wp_unslash( $_POST['is_championship'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_prom_relg                 = isset( $_POST['is_prom_relg'] ) ? filter_var( wp_unslash( $_POST['is_prom_relg'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_ratings                   = isset( $_POST['is_ratings'] ) ? filter_var( wp_unslash( $_POST['is_ratings'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_invitations               = isset( $_POST['is_invitations'] ) ? filter_var( wp_unslash( $_POST['is_invitations'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_online_entries            = isset( $_POST['is_online_entries'] ) ? filter_var( wp_unslash( $_POST['is_online_entries'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_registration_allowed      = isset( $_POST['is_registration_allowed'] ) ? filter_var( wp_unslash( $_POST['is_registration_allowed'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_manual_entry              = isset( $_POST['is_manual_entry'] ) ? filter_var( wp_unslash( $_POST['is_manual_entry'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_fees                      = isset( $_POST['is_fees'] ) ? filter_var( wp_unslash( $_POST['is_fees'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_invoice_send_enabled      = isset( $_POST['is_invoice_send_enabled'] ) ? filter_var( wp_unslash( $_POST['is_invoice_send_enabled'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_public                    = isset( $_POST['is_public'] ) ? filter_var( wp_unslash( $_POST['is_public'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_hidden                    = isset( $_POST['is_hidden'] ) ? filter_var( wp_unslash( $_POST['is_hidden'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_player_entry              = isset( $_POST['is_player_entry'] ) ? filter_var( wp_unslash( $_POST['is_player_entry'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_player_registration       = isset( $_POST['is_player_registration'] ) ? filter_var( wp_unslash( $_POST['is_player_registration'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_consolation               = isset( $_POST['is_consolation'] ) ? filter_var( wp_unslash( $_POST['is_consolation'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_points_carried_forward    = isset( $_POST['is_points_carried_forward'] ) ? filter_var( wp_unslash( $_POST['is_points_carried_forward'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_multiple_groups_entry     = isset( $_POST['is_multiple_groups_entry'] ) ? filter_var( wp_unslash( $_POST['is_multiple_groups_entry'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->is_external_id_required      = isset( $_POST['is_external_id_required'] ) ? filter_var( wp_unslash( $_POST['is_external_id_required'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->rules                        = isset( $_POST['rules'] ) ? sanitize_text_field( wp_unslash( $_POST['rules'] ) ) : null;
        $config->point_for_win                = isset( $_POST['point_for_win'] ) ? floatval( $_POST['point_for_win'] ) : null;
        $config->point_for_draw               = isset( $_POST['point_for_draw'] ) ? floatval( $_POST['point_for_draw'] ) : null;
        $config->point_for_loss               = isset( $_POST['point_for_loss'] ) ? floatval( $_POST['point_for_loss'] ) : null;
        $config->point_for_overtime_win       = isset( $_POST['point_for_overtime_win'] ) ? floatval( $_POST['point_for_overtime_win'] ) : null;
        $config->point_for_overtime_loss      = isset( $_POST['point_for_overtime_loss'] ) ? floatval( $_POST['point_for_overtime_loss'] ) : null;
        $config->rubber_point_for_win         = isset( $_POST['rubber_point_for_win'] ) ? floatval( $_POST['rubber_point_for_win'] ) : null;
        $config->rubber_point_for_draw        = isset( $_POST['rubber_point_for_draw'] ) ? floatval( $_POST['rubber_point_for_draw'] ) : null;
        $config->rubber_point_for_loss        = isset( $_POST['rubber_point_for_loss'] ) ? floatval( $_POST['rubber_point_for_loss'] ) : null;
        $config->set_point_for_win            = isset( $_POST['set_point_for_win'] ) ? floatval( $_POST['set_point_for_win'] ) : null;
        $config->set_point_for_draw           = isset( $_POST['set_point_for_draw'] ) ? floatval( $_POST['set_point_for_draw'] ) : null;
        $config->set_point_for_loss           = isset( $_POST['set_point_for_loss'] ) ? floatval( $_POST['set_point_for_loss'] ) : null;
        $config->game_point_for_win           = isset( $_POST['game_point_for_win'] ) ? floatval( $_POST['game_point_for_win'] ) : null;
        $config->game_point_for_draw          = isset( $_POST['game_point_for_draw'] ) ? floatval( $_POST['game_point_for_draw'] ) : null;
        $config->game_point_for_loss          = isset( $_POST['game_point_for_loss'] ) ? floatval( $_POST['game_point_for_loss'] ) : null;
        $config->points_per_match_won         = isset( $_POST['points_per_match_won'] ) ? floatval( $_POST['points_per_match_won'] ) : null;
        $config->points_per_rubber_won        = isset( $_POST['points_per_rubber_won'] ) ? floatval( $_POST['points_per_rubber_won'] ) : null;
        $config->points_per_set_won           = isset( $_POST['points_per_set_won'] ) ? floatval( $_POST['points_per_set_won'] ) : null;
        $config->points_per_game_won          = isset( $_POST['points_per_game_won'] ) ? floatval( $_POST['points_per_game_won'] ) : null;
        $config->points_deducted_per_match_lost  = isset( $_POST['points_deducted_per_match_lost'] ) ? floatval( $_POST['points_deducted_per_match_lost'] ) : null;
        $config->points_deducted_per_rubber_lost = isset( $_POST['points_deducted_per_rubber_lost'] ) ? floatval( $_POST['points_deducted_per_rubber_lost'] ) : null;
        $config->points_deducted_per_set_lost    = isset( $_POST['points_deducted_per_set_lost'] ) ? floatval( $_POST['points_deducted_per_set_lost'] ) : null;
        $config->points_deducted_per_game_lost   = isset( $_POST['points_deducted_per_game_lost'] ) ? floatval( $_POST['points_deducted_per_game_lost'] ) : null;
        $config->is_bonus_points_allowed      = isset( $_POST['is_bonus_points_allowed'] ) ? filter_var( wp_unslash( $_POST['is_bonus_points_allowed'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        $config->fee_description              = isset( $_POST['fee_description'] ) ? sanitize_text_field( wp_unslash( $_POST['fee_description'] ) ) : null;
        $config->fee                          = isset( $_POST['fee'] ) ? floatval( $_POST['fee'] ) : null;
        $config->fee_player                   = isset( $_POST['fee_player'] ) ? floatval( $_POST['fee_player'] ) : null;
        $config->wtn_min                      = isset( $_POST['wtn_min'] ) ? floatval( $_POST['wtn_min'] ) : null;
        $config->wtn_max                      = isset( $_POST['wtn_max'] ) ? floatval( $_POST['wtn_max'] ) : null;
        $config->is_team_ranking              = isset( $_POST['is_team_ranking'] ) ? filter_var( wp_unslash( $_POST['is_team_ranking'] ), FILTER_VALIDATE_BOOLEAN ) : null;
        return $config;
    }

    /**
     * Build view model.
     *
     * @param object $competition
     * @param object|null $tournament
     * @return Tournament_Competition_Config_Page_View_Model
     */
    private function build_view_model( object $competition, ?object $tournament ): Tournament_Competition_Config_Page_View_Model {
        $tab           = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
        $rules_options = $this->competition_service->get_rules_options( $competition->type );
        $clubs         = $this->club_service->get_clubs( array( 'type' => 'affiliated' ) );

        return new Tournament_Competition_Config_Page_View_Model(
            $competition,
            $tournament,
            $rules_options,
            $clubs,
            $tab
        );
    }
}
