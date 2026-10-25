<?php
/**
 * Competition season action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Competition
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Competition;

use InvalidArgumentException;
use Racketmanager\Application\Admin\Commands\Add_Competition_Season_Command;
use Racketmanager\Application\Admin\Commands\Competition_Season_Handler;
use Racketmanager\Application\Admin\Commands\Edit_Competition_Season_Command;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Admin_Command_Input as Input;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use stdClass;

/**
 * Handles competition season mutations (add season to competition, modify competition season).
 */
readonly final class Competition_Season_Action_Dispatcher {

    private Competition_Season_Handler $commands;

    public function __construct(
        Competition_Service $competition_service,
        Finance_Service $finance_service,
        private Action_Guard_Interface $action_guard,
        Competition_Season_Service $competition_season_service,
        Event_Repository_Interface $event_repository,
        ?Competition_Season_Handler $commands = null,
    ) {
        $this->commands = $commands ?? new Competition_Season_Handler(
            $competition_service,
            $finance_service,
            $competition_season_service,
            $event_repository
        );
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['addSeason'] ) ) {
            return $this->handle_add_season( $query, $post );
        }

        if ( isset( $post['editSeason'] ) ) {
            return $this->handle_edit_season( $query, $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_add_season( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed(
                'racketmanager_nonce',
                'racketmanager_add-season',
                'edit_seasons'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $competition_id = Input::integer( $post, 'competition_id' ) ?? Input::integer( $query, 'competition_id' );
            $season         = Input::text( $post, 'season' ) ?? Input::text( $query, 'season' );

            $current_season = $this->get_season_input( strval( $season ), $post );
            $form_data      = $this->sanitize_form_data( $post );

            $command = new Add_Competition_Season_Command(
                competition_id: $competition_id,
                season: $season,
                season_data: (array) $current_season,
                form_data: $form_data,
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_add_season( $command );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_edit_season( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed(
                'racketmanager_nonce',
                'racketmanager_manage-season',
                'edit_seasons'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $competition_id = Input::integer( $post, 'competition_id' ) ?? Input::integer( $query, 'competition_id' );
            $season         = Input::text( $post, 'season' ) ?? Input::text( $query, 'season' );

            $current_season = $this->get_season_input( strval( $season ), $post );
            $form_data      = $this->sanitize_form_data( $post );

            $command = new Edit_Competition_Season_Command(
                competition_id: $competition_id,
                season: $season,
                season_data: (array) $current_season,
                form_data: $form_data,
            );
        } catch ( InvalidArgumentException $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        return $this->commands->handle_edit_season( $command );
    }

    /**
     * @param string               $season
     * @param array<string, mixed> $post
     */
    private function get_season_input( string $season, array $post ): stdClass {
        $current_season                    = new stdClass();
        $current_season->name              = $season;
        $current_season->venue             = isset( $post['venue'] ) ? intval( $post['venue'] ) : null;
        $current_season->date_end          = isset( $post['dateEnd'] ) ? sanitize_text_field( wp_unslash( strval( $post['dateEnd'] ) ) ) : null;
        $current_season->date_open         = isset( $post['dateOpen'] ) ? sanitize_text_field( wp_unslash( strval( $post['dateOpen'] ) ) ) : null;
        $current_season->date_closing      = isset( $post['dateClose'] ) ? sanitize_text_field( wp_unslash( strval( $post['dateClose'] ) ) ) : null;
        $current_season->date_start        = isset( $post['dateStart'] ) ? sanitize_text_field( wp_unslash( strval( $post['dateStart'] ) ) ) : null;
        $current_season->competition_code  = isset( $post['competition_code'] ) ? sanitize_text_field( wp_unslash( strval( $post['competition_code'] ) ) ) : null;
        $current_season->fixed_match_dates = isset( $post['fixedMatchDates'] ) && 'true' === $post['fixedMatchDates'];
        $current_season->home_away         = isset( $post['homeAway'] ) && 'true' === $post['homeAway'];
        $current_season->grade             = isset( $post['grade'] ) ? sanitize_text_field( wp_unslash( strval( $post['grade'] ) ) ) : null;
        $current_season->max_teams         = isset( $post['max_teams'] ) ? intval( $post['max_teams'] ) : null;
        $current_season->teams_per_club    = isset( $post['teams_per_club'] ) ? intval( $post['teams_per_club'] ) : null;
        $current_season->teams_prom_relg   = isset( $post['teams_prom_relg'] ) ? intval( $post['teams_prom_relg'] ) : null;
        $current_season->lowest_promotion  = isset( $post['lowest_promotion'] ) ? intval( $post['lowest_promotion'] ) : null;
        $current_season->num_match_days    = isset( $post['num_match_days'] ) ? intval( $post['num_match_days'] ) : null;
        $current_season->round_length      = isset( $post['round_length'] ) ? intval( $post['round_length'] ) : null;
        $current_season->home_away_diff    = isset( $post['home_away_diff'] ) ? intval( $post['home_away_diff'] ) : 0;
        $current_season->filler_weeks      = isset( $post['filler_weeks'] ) ? intval( $post['filler_weeks'] ) : 0;
        $current_season->fee_competition   = isset( $post['feeClub'] ) ? floatval( $post['feeClub'] ) : null;
        $current_season->fee_event         = isset( $post['feeTeam'] ) ? floatval( $post['feeTeam'] ) : null;
        $current_season->fee_lead_time     = isset( $post['feeLeadTime'] ) ? intval( $post['feeLeadTime'] ) : null;
        $current_season->fee_id            = isset( $post['feeId'] ) ? intval( $post['feeId'] ) : null;

        return $current_season;
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
            'competition_id', 'season', 'venue', 'grade', 'dateOpen', 'dateEnd', 'dateClose',
            'dateStart', 'max_teams', 'teams_per_club', 'teams_prom_relg', 'lowest_promotion',
            'num_match_days', 'round_length', 'home_away_diff', 'filler_weeks',
            'homeAway', 'fixedMatchDates', 'feeClub', 'feeTeam', 'feeLeadTime',
            'entry_fee', 'entry_charge_id', 'event_fee', 'event_charge_id', 'match_dates',
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
