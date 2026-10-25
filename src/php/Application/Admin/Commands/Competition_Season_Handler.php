<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Season_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Validator\Validator_Config;
use Racketmanager\Util\Util;
use stdClass;

final readonly class Competition_Season_Handler {
    public function __construct(
        private Competition_Service $competition_service,
        private Finance_Service $finance_service,
        private Competition_Season_Service $competition_season_service,
        private Event_Repository_Interface $event_repository,
    ) {
    }

    public function handle_add_season( Add_Competition_Season_Command $command ): Action_Result_DTO {
        $competition_id = $command->competition_id;
        $season         = $command->season;

        $validator = new Validator_Config( $this->competition_service );
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

        $current_season = (object) $command->season_data;
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
                    'form_data' => $command->form_data,
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

    public function handle_edit_season( Edit_Competition_Season_Command $command ): Action_Result_DTO {
        $competition_id = $command->competition_id;
        $season         = $command->season;

        $validator = new Validator_Config( $this->competition_service );
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

        $current_season = (object) $command->season_data;
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
                    'form_data' => $command->form_data,
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

    private function set_competition_dates( object $current_season, Competition $competition ): object {
        $return    = new stdClass();
        $validator = new Validator_Config( $this->competition_service );
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
                    $event_season['match_dates']    = $season['match_dates'] ?? array();
                    foreach ( $events as $event ) {
                        if ( $event->update_season( $event_season ) && ! empty( $event->id ) ) {
                            $this->event_repository->update_seasons( (int) $event->id, $event->get_seasons() );
                        }
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
}
