<?php
/**
 * Competition season action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Competition
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Competition;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Validator\Validator_Config;
use Racketmanager\Util\Util;
use stdClass;

/**
 * Handles competition season mutations (add season to competition, modify competition season).
 */
readonly final class Competition_Season_Action_Dispatcher {

    public function __construct(
        private Competition_Service $competition_service,
        private Finance_Service $finance_service,
        private Action_Guard_Interface $action_guard,
        private Competition_Season_Service $competition_season_service,
    ) {
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

        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : ( isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null );
        $season         = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : ( isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : null );

        $validator = new Validator_Config();
        $validator = $validator->competition( $competition_id );
        if ( empty( $validator->error ) ) {
            $validator = $validator->season( $season !== null && '' !== $season ? (string) $season : null );
        }

        if ( ! empty( $validator->error ) ) {
            return new Action_Result_DTO(
                message: $validator->err_msgs[0] ?? __( 'Invalid competition or season', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $competition = $this->competition_service->get_competition( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $current_season = $this->get_season_input( strval( $season ), $post );
        $dates_result   = $this->set_competition_dates( $current_season, $competition );

        if ( ! empty( $dates_result->error ) ) {
            $err_flds = $dates_result->err_flds ?? array();
            $err_msgs = $dates_result->err_msgs ?? array();
            $errors   = array();
            foreach ( $err_flds as $i => $field ) {
                $errors[ $field ] = $err_msgs[ $i ] ?? __( 'Invalid field', 'racketmanager' );
            }

            return new Action_Result_DTO(
                message: __( 'Errors found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'errors'    => $errors,
                    'form_data' => $this->sanitize_form_data( $post ),
                ),
            );
        }

        $this->schedule_open_activities( (int) $competition->id, $current_season );

        return new Action_Result_DTO(
            message: __( 'Season added to competition', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
            query_params: array(
                'season' => $season,
                'view'   => 'modify',
            ),
        );
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

        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : ( isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null );
        $season         = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : ( isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : null );

        $validator = new Validator_Config();
        $validator = $validator->competition( $competition_id );
        if ( empty( $validator->error ) ) {
            $validator = $validator->season( $season !== null && '' !== $season ? (string) $season : null );
        }

        if ( ! empty( $validator->error ) ) {
            return new Action_Result_DTO(
                message: $validator->err_msgs[0] ?? __( 'Invalid competition or season', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $competition = $this->competition_service->get_competition( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $current_season = $this->get_season_input( strval( $season ), $post );
        $dates_result   = $this->set_competition_dates( $current_season, $competition );

        if ( ! empty( $dates_result->error ) ) {
            $err_flds = $dates_result->err_flds ?? array();
            $err_msgs = $dates_result->err_msgs ?? array();
            $errors   = array();
            foreach ( $err_flds as $i => $field ) {
                $errors[ $field ] = $err_msgs[ $i ] ?? __( 'Invalid field', 'racketmanager' );
            }

            return new Action_Result_DTO(
                message: __( 'Errors found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
                flash_data: array(
                    'errors'    => $errors,
                    'form_data' => $this->sanitize_form_data( $post ),
                ),
            );
        }

        if ( ! empty( $dates_result->updates ) ) {
            return new Action_Result_DTO(
                message: __( 'Season updated', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
                query_params: array(
                    'season' => $season,
                    'view'   => 'modify',
                ),
            );
        }

        return new Action_Result_DTO(
            message: __( 'No updates', 'racketmanager' ),
            message_type: Admin_Message_Type::WARNING,
            query_params: array(
                'season' => $season,
                'view'   => 'modify',
            ),
        );
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
     * @param object      $current_season
     * @param Competition $competition
     */
    private function set_competition_dates( object $current_season, Competition $competition ): object {
        $return    = new stdClass();
        $validator = new Validator_Config();
        $validator = $validator->season( $current_season->name ? (string) $current_season->name : null );
        $validator = $validator->grade( $current_season->grade );
        $validator = $validator->date( $current_season->date_open, 'open' );
        $validator = $validator->date( $current_season->date_end, 'end' );
        $validator = $validator->date( $current_season->date_closing, 'closing' );
        $validator = $validator->date( $current_season->date_start, 'start' );
        if ( $competition->is_league ) {
            $validator = $validator->max_teams( $current_season->max_teams );
            $validator = $validator->teams_per_club( $current_season->teams_per_club );
            $validator = $validator->teams_prom_relg( $current_season->teams_prom_relg, $current_season->teams_per_club );
            $validator = $validator->lowest_promotion( $current_season->lowest_promotion );
            $validator = $validator->num_match_days( $current_season->num_match_days );
        } else {
            $validator = $validator->venue( $current_season->venue );
        }
        $validator = $validator->match_date_option( $current_season->fixed_match_dates );
        $validator = $validator->fixture_type( $current_season->home_away );
        $validator = $validator->round_length( $current_season->round_length );
        $validator = $validator->fixture_gap( $current_season->home_away_diff );
        $validator = $validator->filler_weeks( $current_season->filler_weeks );
        $validator = $validator->fees(
            null === $current_season->fee_lead_time ? null : (string) $current_season->fee_lead_time,
            null === $current_season->fee_competition ? null : (string) $current_season->fee_competition,
            null === $current_season->fee_event ? null : (string) $current_season->fee_event
        );

        if ( empty( $validator->error ) ) {
            $return->updates = $this->update_competition_season_settings( $current_season, $competition );
        } else {
            $return = $validator->get_details();
        }

        return $return;
    }

    /**
     * @param object      $current_season
     * @param Competition $competition
     */
    private function update_competition_season_settings( object $current_season, Competition $competition ): bool {
        $updates = false;
        if ( ! empty( $current_season->fee_lead_time ) ) {
            $fee_lead_time = $current_season->fee_lead_time * 7;
            $fee_date      = Util::amend_date( $current_season->date_start, $fee_lead_time, '-' );
        } else {
            $fee_date = $current_season->date_start;
        }

        if ( ! empty( $current_season->fee_id ) ) {
            try {
                $charge        = $this->finance_service->get_charge( $current_season->fee_id );
                $charge_update = false;
                if ( floatval( $charge->fee_competition ) !== $current_season->fee_competition ) {
                    $charge->set_fee_competition( $current_season->fee_competition );
                    $charge_update = true;
                }
                if ( floatval( $charge->fee_event ) !== $current_season->fee_event ) {
                    $charge->set_fee_event( $current_season->fee_event );
                    $charge_update = true;
                }
                if ( $charge->date !== $fee_date ) {
                    $charge->set_date( $fee_date );
                    $charge_update = true;
                }
                if ( $charge_update ) {
                    $this->schedule_invoice_send( (int) $charge->id );
                }
            } catch ( Charge_Not_Found_Exception ) {
                $charge_create = true;
            }
        } elseif ( ! empty( $current_season->fee_competition ) || ! empty( $current_season->fee_event ) ) {
            $charge_create = true;
        }

        $season = empty( $competition->get_season_by_name( $current_season->name ) ) ? null : $competition->get_season_by_name( $current_season->name );
        if ( $season ) {
            if ( empty( $season['date_open'] ) || $season['date_open'] !== $current_season->date_open ) {
                $updates             = true;
                $season['date_open'] = $current_season->date_open;
            }
            if ( empty( $season['date_end'] ) || $season['date_end'] !== $current_season->date_end ) {
                $updates            = true;
                $season['date_end'] = $current_season->date_end;
            }
            if ( empty( $season['date_start'] ) || $season['date_start'] !== $current_season->date_start ) {
                $updates              = true;
                $season['date_start'] = $current_season->date_start;
            }
            if ( empty( $season['date_closing'] ) || $season['date_closing'] !== $current_season->date_closing ) {
                $updates                = true;
                $season['date_closing'] = $current_season->date_closing;
            }
            if ( $competition->is_league ) {
                if ( empty( $season['max_teams'] ) || $season['max_teams'] !== $current_season->max_teams ) {
                    $updates             = true;
                    $season['max_teams'] = $current_season->max_teams;
                }
                if ( empty( $season['teams_per_club'] ) || $season['teams_per_club'] !== $current_season->teams_per_club ) {
                    $updates                  = true;
                    $season['teams_per_club'] = $current_season->teams_per_club;
                }
                if ( empty( $season['teams_prom_relg'] ) || $season['teams_prom_relg'] !== $current_season->teams_prom_relg ) {
                    $updates                   = true;
                    $season['teams_prom_relg'] = $current_season->teams_prom_relg;
                }
                if ( empty( $season['lowest_promotion'] ) || $season['lowest_promotion'] !== $current_season->lowest_promotion ) {
                    $updates                    = true;
                    $season['lowest_promotion'] = $current_season->lowest_promotion;
                }
                if ( empty( $season['num_match_days'] ) || $season['num_match_days'] !== $current_season->num_match_days ) {
                    $match_days_change        = true;
                    $updates                  = true;
                    $season['num_match_days'] = $current_season->num_match_days;
                }
                if ( empty( $season['filler_weeks'] ) || $season['filler_weeks'] !== $current_season->filler_weeks ) {
                    $updates                = true;
                    $season['filler_weeks'] = $current_season->filler_weeks;
                }
            } elseif ( ( $season['venue'] ?? null ) !== $current_season->venue ) {
                $updates         = true;
                $season['venue'] = $current_season->venue;
            }
            if ( ( $competition->is_league || $competition->is_cup ) && ( empty( $season['home_away_diff'] ) || $season['home_away_diff'] !== $current_season->home_away_diff ) ) {
                $updates                  = true;
                $season['home_away_diff'] = $current_season->home_away_diff;
            }
            if ( empty( $season['round_length'] ) || $season['round_length'] !== $current_season->round_length ) {
                $updates                = true;
                $season['round_length'] = $current_season->round_length;
            }
            if ( empty( $season['competition_code'] ) ) {
                if ( ! empty( $current_season->competition_code ) ) {
                    $updates                    = true;
                    $season['competition_code'] = $current_season->competition_code;
                } else {
                    $season['competition_code'] = null;
                }
            } elseif ( $season['competition_code'] !== $current_season->competition_code ) {
                $updates                    = true;
                $season['competition_code'] = $current_season->competition_code;
            }
            if ( empty( $season['fixed_match_dates'] ) || $season['fixed_match_dates'] !== $current_season->fixed_match_dates ) {
                $updates                     = true;
                $season['fixed_match_dates'] = $current_season->fixed_match_dates;
            }
            if ( empty( $season['home_away'] ) || $season['home_away'] !== $current_season->home_away ) {
                $updates             = true;
                $season['home_away'] = $current_season->home_away;
            }
            if ( empty( $season['grade'] ) || $season['grade'] !== $current_season->grade ) {
                $updates         = true;
                $season['grade'] = $current_season->grade;
            }
            if ( $updates ) {
                if ( ! empty( $match_days_change ) ) {
                    $season['match_dates'] = $this->set_match_dates( $current_season );
                }
                $updates = $this->competition_service->update_season( $competition, $season );
                $events  = $this->competition_service->get_events_for_competition( (int) $competition->get_id() );
                if ( $events ) {
                    $event_season                   = array();
                    $event_season['name']           = $season['name'];
                    $event_season['home_away']      = $season['home_away'];
                    $event_season['num_match_days'] = $season['num_match_days'];
                    $event_season['match_dates']    = $season['match_dates'];
                    foreach ( $events as $event ) {
                        $event->update_season( $event_season );
                    }
                }
            }
        } else {
            $current_season->match_dates = $this->set_match_dates( $current_season );
            $season                      = $current_season;
            if ( $this->competition_service->add_season( $competition, $season ) ) {
                $this->competition_season_service->cascade_season_to_events( $competition, $season );
            }
        }

        if ( ! empty( $charge_create ) ) {
            $charge                  = new stdClass();
            $charge->competition_id  = $competition->id;
            $charge->season          = $current_season->name;
            $charge->date            = $fee_date;
            $charge->fee_competition = $current_season->fee_competition;
            $charge->fee_event       = $current_season->fee_event;
            $charge                  = $this->finance_service->add_charge( $charge );
            if ( is_object( $charge ) && isset( $charge->id ) ) {
                $this->schedule_invoice_send( (int) $charge->id );
            }
            $updates = true;
        } elseif ( ! empty( $charge_update ) ) {
            $updates = true;
        }

        return (bool) $updates;
    }

    /**
     * @param object $season
     * @return array<int, string>
     */
    private function set_match_dates( object $season ): array {
        $match_dates  = array();
        $date_start   = $season->date_start;
        $round_length = $season->round_length;
        if ( ! empty( $season->home_away ) ) {
            $halfway = (int) ( ( $season->num_match_days ?? 0 ) / 2 );
        } else {
            $halfway = null;
        }

        $num_match_days = (int) ( $season->num_match_days ?? 0 );
        for ( $i = 0; $i < $num_match_days; ++$i ) {
            if ( $i === $halfway && ! empty( $season->home_away_diff ) ) {
                $days_diff  = (int) $season->home_away_diff * 7;
                $date_start = Util::amend_date( $date_start, $days_diff );
            }
            $match_dates[ $i ] = $date_start;
            $date_start        = Util::amend_date( $date_start, $round_length );
        }

        return $match_dates;
    }

    /**
     * @param int    $competition_id
     * @param object $season
     */
    private function schedule_open_activities( int $competition_id, object $season ): void {
        try {
            $competition = $this->competition_service->get_competition( $competition_id );
        } catch ( Competition_Not_Found_Exception ) {
            return;
        }

        $this->schedule_team_competition_emails( $competition_id, $season );
        if ( ! empty( $competition->is_team_entry ) ) {
            $this->schedule_team_ratings( $competition_id, $season );
        }
    }

    /**
     * @param int    $competition_id
     * @param object $season
     */
    private function schedule_team_competition_emails( int $competition_id, object $season ): void {
        $today           = gmdate( 'Y-m-d' );
        $schedule_args   = array( $competition_id, intval( $season->name ) );

        if ( ! empty( $season->date_open ) && $today <= $season->date_open ) {
            $schedule_date   = strtotime( $season->date_open );
            $day             = intval( gmdate( 'd', $schedule_date ) );
            $month           = intval( gmdate( 'm', $schedule_date ) );
            $year            = intval( gmdate( 'Y', $schedule_date ) );
            $schedule_start  = mktime( 0, 0, 1, $month, $day, $year );
            $schedule_name   = 'rm_notify_team_entry_open';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            $res = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
            if ( false === $res || is_wp_error( $res ) ) {
                error_log( sprintf( 'Failed to schedule %s event', $schedule_name ) );
            }
        }

        if ( ! empty( $season->date_closing ) && $today <= $season->date_closing ) {
            $chase_date     = Util::amend_date( $season->date_closing, 7, '-' );
            $day            = (int) substr( $chase_date, 8, 2 );
            $month          = (int) substr( $chase_date, 5, 2 );
            $year           = (int) substr( $chase_date, 0, 4 );
            $schedule_start = mktime( 0, 0, 1, $month, $day, $year );
            $schedule_name  = 'rm_notify_team_entry_reminder';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            $res = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
            if ( false === $res || is_wp_error( $res ) ) {
                error_log( sprintf( 'Failed to schedule %s event', $schedule_name ) );
            }
        }
    }

    /**
     * @param int    $competition_id
     * @param object $season
     */
    private function schedule_team_ratings( int $competition_id, object $season ): void {
        if ( empty( $season->date_closing ) ) {
            $day            = intval( gmdate( 'd' ) );
            $month          = intval( gmdate( 'm' ) );
            $year           = intval( gmdate( 'Y' ) );
            $hour           = intval( gmdate( 'H' ) );
            $schedule_start = mktime( $hour, 0, 0, $month, $day, $year );
        } else {
            $schedule_date  = strtotime( $season->date_closing );
            $day            = intval( gmdate( 'd', $schedule_date ) );
            $month          = intval( gmdate( 'm', $schedule_date ) );
            $year           = intval( gmdate( 'Y', $schedule_date ) );
            $schedule_start = mktime( 23, 59, 0, $month, $day, $year );
        }

        $schedule_name   = 'rm_calculate_team_ratings';
        $schedule_args   = array( $competition_id, intval( $season->name ) );
        Util::clear_scheduled_event( $schedule_name, $schedule_args );
        wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
    }

    /**
     * @param int $charge_id
     */
    private function schedule_invoice_send( int $charge_id ): void {
        try {
            $charge = $this->finance_service->get_charge( $charge_id );
        } catch ( Charge_Not_Found_Exception ) {
            return;
        }

        $today = gmdate( 'Y-m-d' );
        if ( $today >= $charge->date ) {
            return;
        }

        $schedule_date   = strtotime( $charge->date );
        $day             = intval( gmdate( 'd', $schedule_date ) );
        $month           = intval( gmdate( 'm', $schedule_date ) );
        $year            = intval( gmdate( 'Y', $schedule_date ) );
        $schedule_start  = mktime( 0, 0, 1, $month, $day, $year );
        $schedule_name   = 'rm_send_invoices';
        $schedule_args   = array( $charge_id );
        Util::clear_scheduled_event( $schedule_name, $schedule_args );
        wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
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
