<?php
/**
 * RacketManager-Admin API: RacketManager-admin-competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Competition
 */

namespace Racketmanager\Admin;

use Exception;
use Racketmanager\Domain\Charge;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Config;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_charge;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_tournament;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Competition panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_Competition extends Admin_Display {
    /**
     * Handle config page function
     *
     * @return void
     */
    public function display_config_page(): void {
        global $racketmanager;
        $validator = new Validator_Config();
        $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $validator->competition( $competition_id );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->err_msgs[0], true );
            $this->show_message();
            return;
        }
        $competition_id = intval( $_GET['competition_id'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        $tournament = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        if ( $tournament ) {
            $tournament = get_tournament( $tournament );
        }
        if ( isset( $_POST['updateCompetitionConfig'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-competition-config' );
            if ( empty( $validator->error ) ) {
                $competition_id_passed = isset( $_POST['competition_id'] ) ?  intval( $_POST['competition_id'] ) : null;
                $validator             = $validator->compare( $competition_id_passed, $competition->id );
            }
            if ( ! empty( $validator->error ) ) {
                if ( empty( $validator->msg ) ) {
                    $msg = $validator->err_msgs[0];
                } else {
                    $msg = $validator->msg;
                }
                $this->set_message( $msg, true );
            } else {
                $config = $this->get_config_input();
                try {
                    $updates = $this->competition_service->amend_details( $competition_id, $config );
                    if ( is_wp_error( $updates ) ) {
                        $validator->error    = true;
                        $validator->err_flds = $updates->get_error_codes();
                        $validator->err_msgs = $updates->get_error_messages();
                        $this->set_message( __( 'Error with competition details', 'racketmanager' ), true );
                    } elseif( $updates) {
                        $this->set_message( __( 'Competition updated', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
                    }
                } catch ( Competition_Not_Updated_Exception $e ) {
                    $this->set_message( $e->getMessage(), 'warning' );
                } catch ( Competition_Not_Found_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
        } elseif ( isset( $_POST['doActionEvent'] ) ) {
            $validator = new Validator();
            $validator = $validator->check_security_token( 'racketmanager_event_nonce', 'racketmanager_events-bulk' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } elseif ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
                $this->delete_events();
            } else {
                $tab = 'events';
                $this->set_message( __( 'No action specified', 'racketmanager' ), 'warning' );
            }
        } else {
            $competition->config            = (object) $competition->get_settings();
            $competition->config->type      = $competition->type;
            $competition->config->age_group = $competition->age_group;
        }
        $this->show_message();
        if ( empty( $tab ) ) {
            $tab = 'general';
        }
        $forwin           = 0;
        $fordraw          = 0;
        $forloss          = 0;
        $forwin_overtime  = 0;
        $forloss_overtime = 0;
        $is_invalid       = false;
        $rules_options    = $this->competition_service->get_rules_options( $competition->type );
        $clubs            = $this->club_service->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        require_once RACKETMANAGER_PATH . 'templates/admin/includes/competition-config.php';
    }
    private function get_config_input(): object {
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
        $config->team_ranking                 = isset( $_POST['team_ranking'] ) ? sanitize_text_field( wp_unslash( $_POST['team_ranking'] ) ) : null;
        $config->point_rule                   = isset( $_POST['point_rule'] ) ? sanitize_text_field( wp_unslash( $_POST['point_rule'] ) ) : null;
        $config->scoring                      = isset( $_POST['scoring'] ) ? sanitize_text_field( wp_unslash( $_POST['scoring'] ) ) : null;
        $config->num_sets                     = isset( $_POST['num_sets'] ) ? intval( $_POST['num_sets'] ) : null;
        $config->num_rubbers                  = isset( $_POST['num_rubbers'] ) ? intval( $_POST['num_rubbers'] ) : null;
        $config->reverse_rubbers              = isset( $_POST['reverse_rubbers'] ) && 'true' === $_POST['reverse_rubbers'];
        $config->fixed_match_dates            = isset( $_POST['fixed_match_dates'] ) && 'true' === $_POST['fixed_match_dates'];
        $config->home_away                    = isset( $_POST['home_away'] ) && 'true' === $_POST['home_away'];
        $config->round_length                 = isset( $_POST['round_length'] ) ? intval( $_POST['round_length'] ) : null;
        $config->home_away_diff               = isset( $_POST['home_away_diff'] ) ? intval( $_POST['home_away_diff'] ) : null;
        $config->filler_weeks                 = isset( $_POST['filler_weeks'] ) ? intval( $_POST['filler_weeks'] ) : null;
        $config->match_day_restriction        = isset( $_POST['match_day_restriction'] );
        $config->match_day_weekends           = isset( $_POST['match_day_weekends'] ) && 'true' === $_POST['match_day_weekends'];
        $config->match_days_allowed           = isset( $_POST['match_days_allowed'] ) ? array_map( 'intval', $_POST['match_days_allowed'] ) : null;
        $default_match_start_time_string      = isset( $_POST['default_match_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['default_match_start_time'] ) ) : null;
        $default_match_start_time             = explode( ':', $default_match_start_time_string );
        if ( count( $default_match_start_time ) !== 2 ) {
            $config->default_match_start_time['hour'] = '0';
            $config->default_match_start_time['minutes'] = '00';
        } else {
            $config->default_match_start_time['hour'] = intval( $default_match_start_time[0] );
            $config->default_match_start_time['minutes'] = intval( $default_match_start_time[1] );
        }
        $config->start_time['weekday']['min'] = isset( $_POST['min_start_time_weekday'] ) ? sanitize_text_field( wp_unslash( $_POST['min_start_time_weekday'] ) ) : null;
        $config->start_time['weekday']['max'] = isset( $_POST['max_start_time_weekday'] ) ? sanitize_text_field( wp_unslash( $_POST['max_start_time_weekday'] ) ) : null;
        $config->start_time['weekend']['min'] = isset( $_POST['min_start_time_weekend'] ) ? sanitize_text_field( wp_unslash( $_POST['min_start_time_weekend'] ) ) : null;
        $config->start_time['weekend']['max'] = isset( $_POST['max_start_time_weekend'] ) ? sanitize_text_field( wp_unslash( $_POST['max_start_time_weekend'] ) ) : null;
        $config->point_format                 = isset( $_POST['point_format'] ) ? sanitize_text_field( wp_unslash( $_POST['point_format'] ) ) : null;
        $config->point_2_format               = isset( $_POST['point_2_format'] ) ? sanitize_text_field( wp_unslash( $_POST['point_2_format'] ) ) : null;
        $config->num_matches_per_page         = isset( $_POST['num_matches_per_page'] ) ? intval( $_POST['num_matches_per_page'] ) : null;
        $config->rules                        = isset( $_POST['rules'] ) ? array_map( 'intval', $_POST['rules'] ) : null;
        $config->standings                    = isset( $_POST['standings'] ) ? wp_unslash( $_POST['standings'] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $config->num_courts_available         = isset( $_POST['num_courts_available'] ) ? array_map( 'intval', $_POST['num_courts_available'] ) : array();
        return $config;
    }
    /**
     * Function to delete events for competition
     * @return void
     */
    private function delete_events(): void {
        $events = isset( $_POST['event'] ) ? wp_unslash( $_POST['event'] ) : null;
        if ( $events ) {
            $msg = array();
            foreach ( $events as $event_id ) {
                $event = get_event( $event_id );
                if ( $event ) {
                    $event->delete();
                    $msg[] = sprintf( __( '%s has been deleted', 'racketmanager' ), $event->name );
                }
            }
            $message = implode( '<br>', $msg );
            $this->set_message( $message );
        } else {
            $this->set_message( __( 'No events were found to delete', 'racketmanager' ), 'warning' );
        }

    }
    /**
     *
     * Display create/edit season page
     */
    public function display_season_modify_page(): void {
        global $racketmanager;
        $edit_mode = true;
        $validator = new Validator_Config();
        $validator = $validator->capability( 'edit_seasons' );
        if ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
            $validator      = $validator->competition( $competition_id );
            if ( empty( $validator->error ) ) {
                $competition = get_competition( $competition_id );
                if ( $season ) {
                    $validator = $validator->season_set( $season, $competition->get_seasons() );
                    if ( ! empty( $validator->error ) ) {
                        $this->set_message( $validator->err_msgs[0], true );
                    }
                } else {
                    $edit_mode = false;
                }
            } else {
                $this->set_message( $validator->err_msgs[0], true );
            }
        } else {
            $this->set_message( $validator->msg, true );
        }
        if ( ! empty( $validator->error ) ) {
            $this->show_message();
            return;
        }
        if ( isset( $_POST['addSeason'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-season' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
                $this->show_message();
            } else {
                $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
                $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                $validator      = $validator->competition( $competition_id );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->season( $season );
                }
                if ( ! empty( $validator->error ) ) {
                    $this->set_message( $validator->err_msgs[0], true );
                } else {
                    $current_season = $this->get_season_input( $season );
                    $validator      = $this->set_competition_dates( $current_season, $competition );
                    if ( empty( $validator->error ) ) {
                        $this->schedule_open_activities( $competition->id, $current_season );
                        $this->set_message( __( 'Season added to competition', 'racketmanager' ) );
                        $edit_mode = true;
                    }
                }
            }
        } elseif ( isset( $_POST['editSeason'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-season' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $competition_id = isset( $_POST['competition_id'] ) ? intval( $_POST['competition_id'] ) : null;
                $season         = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
                $validator      = $validator->competition( $competition_id );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->season( $season );
                }
                if ( ! empty( $validator->error ) ) {
                    $this->set_message( $validator->err_msgs[0], true );
                } else {
                    $current_season = $this->get_season_input( $season );
                    $validator      = $this->set_competition_dates( $current_season, $competition );
                    if ( empty( $validator->error ) ) {
                        if ( $validator->updates ) {
                            $this->set_message( __( 'Season updated', 'racketmanager' ) );
                        } else {
                            $this->set_message( $this->no_updates, 'warning' );
                        }
                    }
                }
            }
        } elseif ( $season ) {
            $current_season = (object) $competition->get_season_by_name( $season );
            if ( $current_season ) {
                $fee_competition = 0;
                $fee_event       = 0;
                $fee_status      = null;
                $fee_id          = null;
                $charges         = $this->finance_service->get_charges_by_criteria(
                    array(
                        'competition' => $competition_id,
                        'season'      => $season,
                    )
                );
                switch ( count( $charges ) ) {
                    case 1:
                        $fee_competition = $charges[0]->fee_competition;
                        $fee_event       = $charges[0]->fee_event;
                        $fee_status      = $charges[0]->status;
                        $fee_id          = $charges[0]->id;
                        break;
                    case 0:
                        break;
                    default:
                        foreach ( $charges as $charge ) {
                            $fee_competition += $charge->fee_competition;
                            $fee_event       += $charge->fee_event;
                            $fee_status      = $charge->status;
                        }
                        break;
                }
                $current_season->fee_competition = $fee_competition;
                $current_season->fee_event       = $fee_event;
                $current_season->fee_status      = $fee_status;
                $current_season->fee_id          = $fee_id;
            } else {
                $this->set_message( __( 'Season not found for competition', 'racketmanager' ), true );
            }
        } else {
            $this->set_message( __( 'New season', 'racketmanager' ), 'info' );
        }
        $this->show_message();
        $seasons = $racketmanager->get_seasons( 'DESC' );
        $clubs   = $this->club_service->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        require_once RACKETMANAGER_PATH . 'templates/admin/includes/season-edit.php';
    }

    /**
     * Function to get season input
     *
     * @param string $season
     *
     * @return object
     */
    private function get_season_input( string $season ): object {
        $current_season                    = new stdClass();
        $current_season->name              = $season;
        $current_season->venue             = isset( $_POST['venue'] ) ? intval( $_POST['venue'] ) : null;
        $current_season->date_end          = isset( $_POST['dateEnd'] ) ? sanitize_text_field( wp_unslash( $_POST['dateEnd'] ) ) : null;
        $current_season->date_open         = isset( $_POST['dateOpen'] ) ? sanitize_text_field( wp_unslash( $_POST['dateOpen'] ) ) : null;
        $current_season->date_closing      = isset( $_POST['dateClose'] ) ? sanitize_text_field( wp_unslash( $_POST['dateClose'] ) ) : null;
        $current_season->date_start        = isset( $_POST['dateStart'] ) ? sanitize_text_field( wp_unslash( $_POST['dateStart'] ) ) : null;
        $current_season->competition_code  = isset( $_POST['competition_code'] ) ? sanitize_text_field( wp_unslash( $_POST['competition_code'] ) ) : null;
        $current_season->fixed_match_dates = isset( $_POST['fixedMatchDates']) && 'true' === $_POST['fixedMatchDates'];
        $current_season->home_away         = isset( $_POST['homeAway']) && 'true' === $_POST['homeAway'];
        $current_season->grade             = isset( $_POST['grade'] ) ? sanitize_text_field( wp_unslash( $_POST['grade'] ) ) : null;
        $current_season->max_teams         = isset( $_POST['max_teams'] ) ? intval( $_POST['max_teams'] ) : null;
        $current_season->teams_per_club    = isset( $_POST['teams_per_club'] ) ? intval( $_POST['teams_per_club'] ) : null;
        $current_season->teams_prom_relg   = isset( $_POST['teams_prom_relg'] ) ? intval( $_POST['teams_prom_relg'] ) : null;
        $current_season->lowest_promotion  = isset( $_POST['lowest_promotion'] ) ? intval( $_POST['lowest_promotion'] ) : null;
        $current_season->num_match_days    = isset( $_POST['num_match_days'] ) ? intval( $_POST['num_match_days'] ) : null;
        $current_season->round_length      = isset( $_POST['round_length'] ) ? intval( $_POST['round_length'] ) : null;
        $current_season->home_away_diff    = isset( $_POST['home_away_diff'] ) ? intval( $_POST['home_away_diff'] ) : 0;
        $current_season->filler_weeks      = isset( $_POST['filler_weeks'] ) ? intval( $_POST['filler_weeks'] ) : 0;
        $current_season->fee_competition   = isset( $_POST['feeClub'] ) ? floatval( $_POST['feeClub'] ) : null;
        $current_season->fee_event         = isset( $_POST['feeTeam'] ) ? floatval( $_POST['feeTeam'] ) : null;
        $current_season->fee_lead_time     = isset( $_POST['feeLeadTime'] ) ? intval( $_POST['feeLeadTime'] ) : null;
        $current_season->fee_id            = isset( $_POST['feeId'] ) ? intval( $_POST['feeId'] ) : null;
        return $current_season;
    }
    /**
     * Set season dates for competition season function
     *
     * @param object $current_season season details.
     * @param object $competition competition details.
     * @return object
     */
    private function set_competition_dates( object $current_season, object $competition ): object {
        $return    = new stdClass();
        $validator = new Validator_Config();
        $validator = $validator->season( $current_season->name );
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
        $validator = $validator->match_date_option( $current_season->fixed_match_dates);
        $validator = $validator->fixture_type( $current_season->home_away );
        $validator = $validator->round_length( $current_season->round_length );
        $validator = $validator->fixture_gap( $current_season->home_away_diff );
        $validator = $validator->filler_weeks( $current_season->filler_weeks );
        $validator = $validator->fees( $current_season->fee_lead_time, $current_season->fee_competition, $current_season->fee_event );
        if ( empty( $validator->error ) ) {
            $return->updates = $this->update_competition_season_settings( $current_season, $competition );
        } else {
            $return = $validator->get_details();
            $this->set_message( __( 'Errors found', 'racketmanager' ), true );
        }
        return $return;
    }

    /**
     * Function to update competition season settings
     *
     * @param object $current_season
     * @param object $competition
     *
     * @return bool
     */
    private function update_competition_season_settings( object $current_season, object $competition ): bool {
        $updates = false;
        if ( ! empty( $current_season->fee_lead_time ) ) {
            $fee_lead_time = $current_season->fee_lead_time * 7;
            $fee_date      = Util::amend_date( $current_season->date_start, $fee_lead_time, '-' );
        } else {
            $fee_date = $current_season->date_start;
        }
        if ( ! empty( $current_season->fee_id ) ) {
            $charge = get_charge( $current_season->fee_id );
            if ( $charge ) {
                $charge_update = false;
                if ( floatval( $charge->fee_competition ) !== $current_season->fee_competition ) {
                    $charge->set_club_fee( $current_season->fee_competition );
                    $charge_update = true;
                }
                if ( floatval( $charge->fee_event ) !== $current_season->fee_event ) {
                    $charge->set_team_fee( $current_season->fee_event );
                    $charge_update = true;
                }
                if ( $charge->date !== $fee_date ) {
                    $charge->set_date( $fee_date );
                    $charge_update = true;
                }
                if ( $charge_update ) {
                    $this->schedule_invoice_send( $charge->id );
                }
            } elseif ( ! empty( $current_season->fee_competition ) || ! empty( $current_season->fee_event ) ) {
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
            } elseif ( $season['venue'] !== $current_season->venue ) {
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
                $updates = $competition->update_season( $season );
                $events = $competition->get_events();
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
            $competition->add_season( $season );
        }
        if ( ! empty( $charge_create ) ) {
            $charge                  = new stdClass();
            $charge->competition_id  = $competition->id;
            $charge->season          = $current_season->name;
            $charge->date            = $fee_date;
            $charge->fee_competition = $current_season->fee_competition;
            $charge->fee_event       = $current_season->fee_event;
            $charge                  = new Charge( $charge );
            $this->schedule_invoice_send( $charge->id );
            $updates = true;
        } elseif( ! empty( $charge_update ) ) {
            $updates = true;
        }
        return $updates;
    }
    /**
     * Set match dates function
     *
     * @param object $season season details.
     * @return array of match dates.
     */
    private function set_match_dates( object $season ): array {
        $match_dates  = array();
        $date_start   = $season->date_start;
        $round_length = $season->round_length;
        if ( $season->home_away ) {
            $halfway = $season->num_match_days / 2;
        } else {
            $halfway = null;
        }
        for ( $i = 0; $i < $season->num_match_days; ++$i ) {
            if ( $i === $halfway && $season->home_away_diff ) {
                $days_diff  = $season->home_away_diff * 7;
                $date_start = Util::amend_date( $date_start, $days_diff );
            }
            $match_dates[ $i ] = $date_start;
            $date_start        = Util::amend_date( $date_start, $round_length );
        }
        return $match_dates;
    }
    /**
     * Schedule opening activities function
     *
     * @param int $competition_id competition id.
     * @param object $season season name.
     * @return void
     */
    private function schedule_open_activities( int $competition_id, object $season ): void {
        $competition = get_competition( $competition_id );
        if ( $competition ) {
            $this->schedule_team_competition_emails( $competition_id, $season );
            if ( $competition->is_team_entry ) {
                $this->schedule_team_ratings( $competition_id, $season );
            }
        }
    }
    /**
     * Schedule emails function
     *
     * @param int $competition_id competition id.
     * @param object $season season name.
     * @return void
     */
    private function schedule_team_competition_emails( int $competition_id, object $season ): void {
        $today           = gmdate( 'Y-m-d' );
        $schedule_args[] = $competition_id;
        $schedule_args[] = intval( $season->name );
        if ( $today <= $season->date_open ) {
            $schedule_date   = strtotime( $season->date_open );
            $day             = intval( gmdate( 'd', $schedule_date ) );
            $month           = intval( gmdate( 'm', $schedule_date ) );
            $year            = intval( gmdate( 'Y', $schedule_date ) );
            $schedule_start  = mktime( 00, 00, 01, $month, $day, $year );
            $schedule_name   = 'rm_notify_team_entry_open';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            $success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
            if ( ! $success ) {
                error_log( __( 'Error scheduling team competition open emails', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        if ( $today <= $season->date_closing ) {
            $chase_date     = Util::amend_date( $season->date_closing, 7, '-' );
            $day            = substr( $chase_date, 8, 2 );
            $month          = substr( $chase_date, 5, 2 );
            $year           = substr( $chase_date, 0, 4 );
            $schedule_start = mktime( 00, 00, 01, $month, $day, $year );
            $schedule_name  = 'rm_notify_team_entry_reminder';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            $success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
            if ( ! $success ) {
                error_log( __( 'Error scheduling team competition reminder emails', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
    }
    /**
     * Schedule team ratings setting function
     *
     * @param int $competition_id competition id.
     * @param object $season season name.
     * @return void
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
        $schedule_args[] = $competition_id;
        $schedule_args[] = intval( $season->name );
        Util::clear_scheduled_event( $schedule_name, $schedule_args );
        $success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
        if ( ! $success ) {
            $this->set_message( __( 'Error scheduling team ratings calculation', 'racketmanager' ), true );
        }
    }
    /**
     * Schedule invoice send function
     *
     * @param int $charge_id charge id.
     * @return void
     */
    private function schedule_invoice_send( int $charge_id ): void {
        $charge = get_charge( $charge_id );
        if ( $charge ) {
            $today = gmdate( 'Y-m-d' );
            if ( $today < $charge->date ) {
                $schedule_date   = strtotime( $charge->date );
                $day             = intval( gmdate( 'd', $schedule_date ) );
                $month           = intval( gmdate( 'm', $schedule_date ) );
                $year            = intval( gmdate( 'Y', $schedule_date ) );
                $schedule_start  = mktime( 00, 00, 01, $month, $day, $year );
                $schedule_name   = 'rm_send_invoices';
                $schedule_args[] = $charge_id;
                Util::clear_scheduled_event( $schedule_name, $schedule_args );
                $success = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
                if ( ! $success ) {
                    error_log( __( 'Error scheduling invoice sending', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }
            }
        }
    }
}
