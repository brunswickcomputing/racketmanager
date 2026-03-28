<?php
/**
 * Tournament competition config admin controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Exception;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;

final readonly class Tournament_Competition_Config_Admin_Controller {

    /**
     * @param Tournament_Service $tournament_service
     * @param Competition_Service $competition_service
     * @param Club_Service $club_service
     * @param Action_Guard_Interface $action_guard
     */
    public function __construct(
        private Tournament_Service $tournament_service,
        private Competition_Service $competition_service,
        private Club_Service $club_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle the display of the tournament competition config page.
     *
     * @return array{view_model?:Tournament_Competition_Config_Page_View_Model, message?:string, message_type?:bool|string, redirect?:string}
     */
    public function handle(): array {
        $result = array();

        try {
            $this->action_guard->assert_capability( 'edit_leagues' );

            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            if ( ! $competition_id ) {
                throw new Competition_Not_Found_Exception( __( 'Competition not specified', 'racketmanager' ) );
            }

            $competition = $this->competition_service->get_by_id( $competition_id );

            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
            $tournament    = null;
            if ( $tournament_id ) {
                $tournament = $this->tournament_service->get_tournament( $tournament_id );
            }

            if ( isset( $_POST['updateCompetitionConfig'] ) ) {
                $result = $this->handle_update( $competition );
                if ( isset( $result['redirect'] ) ) {
                    return $result;
                }
            }

            // Logic for display
            $competition->config            = (object) $competition->get_settings();
            $competition->config->type      = $competition->type;
            $competition->config->age_group = $competition->age_group;

            $result['view_model'] = $this->build_view_model( $competition, $tournament );

        } catch ( Exception $e ) {
            $result = array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        return $result;
    }

    /**
     * Handle the update of the competition config.
     *
     * @param object $competition
     * @return array{message?:string, message_type?:bool|string, redirect?:string, view_model?:Tournament_Competition_Config_Page_View_Model}
     */
    private function handle_update( object $competition ): array {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_manage-competition-config', 'edit_leagues' );

            $competition_id_passed = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
            if ( $competition_id_passed !== $competition->id ) {
                throw new Invalid_Argument_Exception( __( 'Competition mismatch', 'racketmanager' ) );
            }

            $config  = $this->get_config_input();
            $updates = $this->competition_service->amend_details( $competition->id, $config );

            if ( is_wp_error( $updates ) ) {
                $result = array(
                    'message'      => $updates->get_error_message(),
                    'message_type' => true,
                );
            } elseif ( $updates ) {
                $result = array(
                    'message'      => __( 'Competition updated', 'racketmanager' ),
                    'message_type' => 'success',
                    'redirect'     => add_query_arg( array( 'message' => rawurlencode( __( 'Competition updated', 'racketmanager' ) ), 'message_type' => 'success' ) ),
                );
            } else {
                $result = array(
                    'message'      => __( 'No updates', 'racketmanager' ),
                    'message_type' => 'warning',
                );
            }
        } catch ( Competition_Not_Updated_Exception $e ) {
            $result = array(
                'message'      => $e->getMessage(),
                'message_type' => 'warning',
            );
        } catch ( Exception $e ) {
            $result = array(
                'message'      => $e->getMessage(),
                'message_type' => true,
            );
        }

        return $result;
    }

    /**
     * Get config input from POST.
     *
     * @return stdClass
     */
    private function get_config_input(): stdClass {
        $config = new stdClass();

        // General settings
        $config->name             = $this->get_text_input( 'competition_title' );
        $config->sport            = $this->get_text_input( 'sport' );
        $config->type             = $this->get_text_input( 'type' );
        $config->mode             = $this->get_text_input( 'mode' );
        $config->entry_type       = $this->get_text_input( 'entry_type' );
        $config->age_group        = $this->get_text_input( 'age_group' );
        $config->competition_code = $this->get_text_input( 'competition_code' );
        $config->grade            = $this->get_text_input( 'grade' );
        $config->max_teams        = $this->get_int_input( 'max_teams' );
        $config->num_entries      = $this->get_int_input( 'num_entries' );
        $config->teams_per_club   = $this->get_int_input( 'teams_per_club' );
        $config->teams_prom_relg  = $this->get_int_input( 'teams_prom_relg' );
        $config->lowest_promotion = $this->get_int_input( 'lowest_promotion' );
        $config->teams_per_league = $this->get_int_input( 'teams_per_league' );
        $config->max_leagues      = $this->get_int_input( 'max_leagues' );

        // Flags
        $config->is_championship         = $this->get_bool_input( 'is_championship' );
        $config->is_prom_relg            = $this->get_bool_input( 'is_prom_relg' );
        $config->is_ratings              = $this->get_bool_input( 'is_ratings' );
        $config->is_invitations          = $this->get_bool_input( 'is_invitations' );
        $config->is_online_entries       = $this->get_bool_input( 'is_online_entries' );
        $config->is_registration_allowed = $this->get_bool_input( 'is_registration_allowed' );
        $config->is_manual_entry         = $this->get_bool_input( 'is_manual_entry' );
        $config->is_fees                 = $this->get_bool_input( 'is_fees' );
        $config->is_invoice_send_enabled = $this->get_bool_input( 'is_invoice_send_enabled' );
        $config->is_public               = $this->get_bool_input( 'is_public' );
        $config->is_hidden               = $this->get_bool_input( 'is_hidden' );
        $config->is_player_entry         = $this->get_bool_input( 'is_player_entry' );
        $config->is_player_registration  = $this->get_bool_input( 'is_player_registration' );
        $config->is_consolation          = $this->get_bool_input( 'is_consolation' );
        $config->is_points_carried_forward = $this->get_bool_input( 'is_points_carried_forward' );
        $config->is_multiple_groups_entry = $this->get_bool_input( 'is_multiple_groups_entry' );
        $config->is_external_id_required  = $this->get_bool_input( 'is_external_id_required' );
        $config->is_bonus_points_allowed  = $this->get_bool_input( 'is_bonus_points_allowed' );
        $config->is_team_ranking         = $this->get_bool_input( 'is_team_ranking' );

        $config->rules = $this->get_text_input( 'rules' );

        // Points
        $config->point_for_win                = $this->get_float_input( 'point_for_win' );
        $config->point_for_draw               = $this->get_float_input( 'point_for_draw' );
        $config->point_for_loss               = $this->get_float_input( 'point_for_loss' );
        $config->point_for_overtime_win       = $this->get_float_input( 'point_for_overtime_win' );
        $config->point_for_overtime_loss      = $this->get_float_input( 'point_for_overtime_loss' );
        $config->rubber_point_for_win         = $this->get_float_input( 'rubber_point_for_win' );
        $config->rubber_point_for_draw        = $this->get_float_input( 'rubber_point_for_draw' );
        $config->rubber_point_for_loss        = $this->get_float_input( 'rubber_point_for_loss' );
        $config->set_point_for_win            = $this->get_float_input( 'set_point_for_win' );
        $config->set_point_for_draw           = $this->get_float_input( 'set_point_for_draw' );
        $config->set_point_for_loss           = $this->get_float_input( 'set_point_for_loss' );
        $config->game_point_for_win           = $this->get_float_input( 'game_point_for_win' );
        $config->game_point_for_draw          = $this->get_float_input( 'game_point_for_draw' );
        $config->game_point_for_loss          = $this->get_float_input( 'game_point_for_loss' );
        $config->points_per_match_won         = $this->get_float_input( 'points_per_match_won' );
        $config->points_per_rubber_won        = $this->get_float_input( 'points_per_rubber_won' );
        $config->points_per_set_won           = $this->get_float_input( 'points_per_set_won' );
        $config->points_per_game_won          = $this->get_float_input( 'points_per_game_won' );
        $config->points_deducted_per_match_lost  = $this->get_float_input( 'points_deducted_per_match_lost' );
        $config->points_deducted_per_rubber_lost = $this->get_float_input( 'points_deducted_per_rubber_lost' );
        $config->points_deducted_per_set_lost    = $this->get_float_input( 'points_deducted_per_set_lost' );
        $config->points_deducted_per_game_lost   = $this->get_float_input( 'points_deducted_per_game_lost' );

        // Fees
        $config->fee_description = $this->get_text_input( 'fee_description' );
        $config->fee             = $this->get_float_input( 'fee' );
        $config->fee_player      = $this->get_float_input( 'fee_player' );

        // Ratings
        $config->wtn_min = $this->get_float_input( 'wtn_min' );
        $config->wtn_max = $this->get_float_input( 'wtn_max' );

        // Matches
        $config->scoring                  = $this->get_text_input( 'scoring' );
        $config->num_sets                 = $this->get_int_input( 'num_sets' );
        $config->num_rubbers              = $this->get_int_input( 'num_rubbers' );
        $config->reverse_rubbers          = $this->get_bool_input( 'reverse_rubbers' );
        $config->fixed_match_dates        = $this->get_bool_input( 'fixed_match_dates' );
        $config->home_away                = $this->get_bool_input( 'home_away' );
        $config->round_length             = $this->get_int_input( 'round_length' );
        $config->home_away_diff           = $this->get_int_input( 'home_away_diff' );
        $config->filler_weeks             = $this->get_int_input( 'filler_weeks' );
        $config->match_day_restriction    = $this->get_bool_input( 'match_day_restriction' );
        $config->match_day_weekends       = $this->get_bool_input( 'match_day_weekends' );
        $config->match_days_allowed       = $this->get_array_input( 'match_days_allowed' );
        $config->default_match_start_time = $this->get_text_input( 'default_match_start_time' );

        $config->start_time = [
            'weekday' => [
                'min' => $this->get_text_input( 'min_start_time_weekday' ),
                'max' => $this->get_text_input( 'max_start_time_weekday' ),
            ],
            'weekend' => [
                'min' => $this->get_text_input( 'min_start_time_weekend' ),
                'max' => $this->get_text_input( 'max_start_time_weekend' ),
            ],
        ];

        $config->point_format         = $this->get_text_input( 'point_format' );
        $config->point_2_format       = $this->get_text_input( 'point_2_format' );
        $config->num_matches_per_page = $this->get_int_input( 'num_matches_per_page' );
        $config->standings            = $this->get_array_input( 'standings' );
        $config->team_ranking         = $this->get_text_input( 'team_ranking' );
        $config->point_rule           = $this->get_text_input( 'point_rule' );
        $config->num_courts_available = $this->get_array_input( 'num_courts_available' );

        return $config;
    }

    /**
     * Get text input from POST.
     *
     * @param string $key
     * @return string|null
     */
    private function get_text_input( string $key ): ?string {
        return isset( $_POST[$key] ) ? sanitize_text_field( wp_unslash( $_POST[$key] ) ) : null;
    }

    /**
     * Get int input from POST.
     *
     * @param string $key
     * @return int|null
     */
    private function get_int_input( string $key ): ?int {
        return isset( $_POST[$key] ) ? intval( $_POST[$key] ) : null;
    }

    /**
     * Get bool input from POST.
     *
     * @param string $key
     * @return bool|null
     */
    private function get_bool_input( string $key ): ?bool {
        return isset( $_POST[$key] ) ? filter_var( wp_unslash( $_POST[$key] ), FILTER_VALIDATE_BOOLEAN ) : null;
    }

    /**
     * Get float input from POST.
     *
     * @param string $key
     * @return float|null
     */
    private function get_float_input( string $key ): ?float {
        return isset( $_POST[$key] ) ? floatval( $_POST[$key] ) : null;
    }

    /**
     * Get array input from POST.
     *
     * @param string $key
     * @return array|null
     */
    private function get_array_input( string $key ): ?array {
        return isset( $_POST[$key] ) && is_array( $_POST[$key] ) ? array_map( 'intval', $_POST[$key] ) : null;
    }

    /**
     * Build view model.
     *
     * @param Competition $competition
     * @param Tournament|null $tournament
     *
     * @return Tournament_Competition_Config_Page_View_Model
     */
    private function build_view_model( Competition $competition, ?Tournament $tournament ): Tournament_Competition_Config_Page_View_Model {
        $tab           = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
        $rules_options = $this->competition_service->get_rules_options( $competition->type );
        $clubs         = $this->club_service->get_clubs( array( 'type' => 'affiliated' ) );
        $events        = $this->competition_service->get_events_for_competition( $competition->get_id() );

        return new Tournament_Competition_Config_Page_View_Model(
            $competition,
            $tournament,
            $rules_options,
            $clubs,
            $events,
            $tab
        );
    }
}
