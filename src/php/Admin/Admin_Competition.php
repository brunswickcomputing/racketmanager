<?php
/**
 * RacketManager-Admin API: RacketManager-admin-competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Competition
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use stdClass;
use Racketmanager\Services\Admin\Competition\Competition_Action_Dispatcher;
use Racketmanager\Services\Admin\Competition\Competition_Season_Action_Dispatcher;
use Racketmanager\Services\Validator\Validator_Config;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Competition panel
 */
final class Admin_Competition extends Admin_Display {

    private Admin_Action_Adapter $actions;
    private Admin_Screen_Presenter $screens;
    private Competition_Action_Dispatcher $competition_action_dispatcher;
    private Competition_Season_Action_Dispatcher $competition_season_action_dispatcher;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Competition_Action_Dispatcher $competition_action_dispatcher = null,
        ?Competition_Season_Action_Dispatcher $competition_season_action_dispatcher = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                                          = $this->racketmanager->container;
        $this->actions                              = $actions ?? $c->get( 'admin_action_adapter' );
        $this->screens                              = $screens ?? $c->get( 'admin_screen_presenter' );
        $this->competition_action_dispatcher        = $competition_action_dispatcher ?? $c->get( 'competition_action_dispatcher' );
        $this->competition_season_action_dispatcher = $competition_season_action_dispatcher ?? $c->get( 'competition_season_action_dispatcher' );
        $this->admin_action_adapter                 = $this->actions;
    }

    /**
     * Handle config page function
     *
     * @return void
     */
    public function display_config_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();
        $errors     = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        if ( isset( $_POST['updateCompetitionConfig'] ) || isset( $_POST['doActionEvent'] ) ) {
            $result = $this->competition_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_competition_action_result( $result, $_GET ) ) {
                return;
            }
        }

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

        $competition_id = intval( $_GET['competition_id'] );
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }

        $tournament    = null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        if ( $tournament_id ) {
            try {
                $tournament = $this->tournament_service->get_tournament( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
        }

        $competition->config            = (object) $competition->get_settings();
        $competition->config->type      = $competition->get_type();
        $competition->config->age_group = isset( $competition->age_group ) ? $competition->age_group : ( method_exists( $competition, 'get_age_group' ) ? $competition->get_age_group() : null );

        $form_data = $flash_data['form_data'] ?? array();
        if ( ! empty( $form_data ) ) {
            if ( isset( $form_data['competition_title'] ) ) {
                $competition->name = sanitize_text_field( wp_unslash( strval( $form_data['competition_title'] ) ) );
            }
            if ( ! isset( $competition->config->start_time ) || ! is_array( $competition->config->start_time ) ) {
                $competition->config->start_time = array(
                    'weekday' => array( 'min' => null, 'max' => null ),
                    'weekend' => array( 'min' => null, 'max' => null ),
                );
            }
            if ( isset( $form_data['min_start_time_weekday'] ) ) {
                $competition->config->start_time['weekday']['min'] = $form_data['min_start_time_weekday'];
            }
            if ( isset( $form_data['max_start_time_weekday'] ) ) {
                $competition->config->start_time['weekday']['max'] = $form_data['max_start_time_weekday'];
            }
            if ( isset( $form_data['min_start_time_weekend'] ) ) {
                $competition->config->start_time['weekend']['min'] = $form_data['min_start_time_weekend'];
            }
            if ( isset( $form_data['max_start_time_weekend'] ) ) {
                $competition->config->start_time['weekend']['max'] = $form_data['max_start_time_weekend'];
            }
            if ( isset( $form_data['default_match_start_time'] ) ) {
                $dmst = explode( ':', strval( $form_data['default_match_start_time'] ) );
                if ( 2 === count( $dmst ) ) {
                    $competition->config->default_match_start_time = array(
                        'hour'    => intval( $dmst[0] ),
                        'hours'   => intval( $dmst[0] ),
                        'minutes' => intval( $dmst[1] ),
                    );
                } else {
                    $competition->config->default_match_start_time = $form_data['default_match_start_time'];
                }
            }

            $config_fields = array(
                'sport', 'mode', 'entry_type', 'age_group',
                'competition_code', 'grade', 'max_teams', 'num_entries', 'teams_per_club',
                'teams_prom_relg', 'lowest_promotion', 'team_ranking', 'point_rule',
                'scoring', 'num_sets', 'num_rubbers', 'reverse_rubbers', 'fixed_match_dates',
                'home_away', 'round_length', 'home_away_diff', 'filler_weeks',
                'match_day_restriction', 'match_day_weekends', 'match_days_allowed',
                'point_format', 'point_2_format', 'num_matches_per_page', 'rules', 'standings', 'num_courts_available',
            );

            foreach ( $config_fields as $key ) {
                if ( array_key_exists( $key, $form_data ) ) {
                    $competition->config->$key = $form_data[ $key ];
                }
            }
            // Unchecked checkboxes are absent from a submission, so clear them when rehydrating.
            foreach ( array( 'reverse_rubbers', 'match_day_restriction', 'match_day_weekends' ) as $key ) {
                if ( ! array_key_exists( $key, $form_data ) ) {
                    $competition->config->$key = false;
                }
            }
            foreach ( array( 'match_days_allowed', 'rules' ) as $key ) {
                if ( ! array_key_exists( $key, $form_data ) ) {
                    $competition->config->$key = array();
                }
            }
        }

        $tab           = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['tab'] ) ) ) : 'general';
        $rules_options = $this->competition_service->get_rules_options( $competition->get_type() );
        $clubs         = $this->club_service->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        $clubs         = is_array( $clubs ) ? $clubs : array();
        $events        = $this->competition_service->get_events_for_competition( (int) $competition->get_id() ) ?? array();

        $vm = new Tournament_Competition_Config_Page_View_Model(
            competition: $competition,
            tournament: $tournament,
            rules_options: $rules_options,
            clubs: $clubs,
            events: $events,
            tab: $tab,
            errors: $errors,
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Tournament_Competition_Config_Page_View_Model::class,
            'admin/includes/competition-config'
        );
    }

    /**
     * Display create/edit season page
     */
    public function display_season_modify_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();
        $errors     = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        if ( isset( $_POST['addSeason'] ) || isset( $_POST['editSeason'] ) ) {
            $result = $this->competition_season_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_competition_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $edit_mode = true;
        $validator = new Validator_Config();
        $validator = $validator->capability( 'edit_seasons' );
        if ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $season         = isset( $_GET['season'] ) ? sanitize_text_field( (string) $_GET['season'] ) : null;
            $validator      = $validator->competition( $competition_id );
            if ( empty( $validator->error ) ) {
                try {
                    $competition = $this->competition_service->get_competition( $competition_id );
                    if ( null !== $season && '' !== $season ) {
                        $validator = $validator->season_set( $season, $competition->get_seasons() );
                        if ( ! empty( $validator->error ) ) {
                            $this->set_message( $validator->err_msgs[0], true );
                        }
                    } else {
                        $edit_mode = false;
                    }
                } catch ( Competition_Not_Found_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                    $this->show_message();
                    return;
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

        $current_season = null;
        if ( null !== $season && '' !== $season ) {
            $season_data = $competition->get_season_by_name( $season );
            if ( $season_data ) {
                $current_season  = (object) $season_data;
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

        if ( ! empty( $flash_data['form_data'] ) ) {
            $form = $flash_data['form_data'];
            if ( null === $current_season ) {
                $current_season       = new stdClass();
                $current_season->name = $season ?? ( $form['season'] ?? null );
            }
            if ( isset( $form['venue'] ) ) {
                $current_season->venue = intval( $form['venue'] );
            }
            if ( isset( $form['dateStart'] ) ) {
                $current_season->date_start = sanitize_text_field( wp_unslash( strval( $form['dateStart'] ) ) );
            }
            if ( isset( $form['dateEnd'] ) ) {
                $current_season->date_end = sanitize_text_field( wp_unslash( strval( $form['dateEnd'] ) ) );
            }
            if ( isset( $form['dateOpen'] ) ) {
                $current_season->date_open = sanitize_text_field( wp_unslash( strval( $form['dateOpen'] ) ) );
            }
            if ( isset( $form['dateClose'] ) ) {
                $current_season->date_closing = sanitize_text_field( wp_unslash( strval( $form['dateClose'] ) ) );
            }
            if ( isset( $form['grade'] ) ) {
                $current_season->grade = sanitize_text_field( wp_unslash( strval( $form['grade'] ) ) );
            }
            if ( isset( $form['max_teams'] ) ) {
                $current_season->max_teams = intval( $form['max_teams'] );
            }
            if ( isset( $form['teams_per_club'] ) ) {
                $current_season->teams_per_club = intval( $form['teams_per_club'] );
            }
            if ( isset( $form['teams_prom_relg'] ) ) {
                $current_season->teams_prom_relg = intval( $form['teams_prom_relg'] );
            }
            if ( isset( $form['lowest_promotion'] ) ) {
                $current_season->lowest_promotion = intval( $form['lowest_promotion'] );
            }
            if ( isset( $form['num_match_days'] ) ) {
                $current_season->num_match_days = intval( $form['num_match_days'] );
            }
            if ( isset( $form['round_length'] ) ) {
                $current_season->round_length = intval( $form['round_length'] );
            }
            if ( isset( $form['home_away_diff'] ) ) {
                $current_season->home_away_diff = intval( $form['home_away_diff'] );
            }
            if ( isset( $form['filler_weeks'] ) ) {
                $current_season->filler_weeks = intval( $form['filler_weeks'] );
            }
            if ( isset( $form['homeAway'] ) ) {
                $current_season->home_away = 'true' === $form['homeAway'];
            }
            if ( isset( $form['fixedMatchDates'] ) ) {
                $current_season->fixed_match_dates = 'true' === $form['fixedMatchDates'];
            }
            if ( isset( $form['feeClub'] ) ) {
                $current_season->fee_competition = floatval( $form['feeClub'] );
            }
            if ( isset( $form['feeTeam'] ) ) {
                $current_season->fee_event = floatval( $form['feeTeam'] );
            }
            if ( isset( $form['feeLeadTime'] ) ) {
                $current_season->fee_lead_time = intval( $form['feeLeadTime'] );
            }
        }

        $this->show_message();

        $seasons = $this->season_service->get_all_seasons();
        $clubs   = $this->club_service->get_clubs();

        $vm = new Season_Modify_Page_View_Model(
            competition: $competition,
            season: $season,
            current_season: $current_season,
            seasons: $seasons,
            clubs: $clubs,
            edit_mode: $edit_mode,
            errors: $errors,
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Season_Modify_Page_View_Model::class,
            'admin/includes/season-edit'
        );
    }
}
