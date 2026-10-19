<?php
/**
 * RacketManager-Admin API: RacketManager-admin-cup class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Cup
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\Presenters\Admin_Error_Bag_Mapper;
use Racketmanager\Admin\Presenters\Admin_Plan_Presenter;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Cup\Cup_Plan_Action_Dispatcher;
use Racketmanager\Services\Admin\Cup\Cup_Setup_Action_Dispatcher;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Plan;
use Racketmanager\Util\Util;
use function Racketmanager\get_club;
use function Racketmanager\get_league;
use function Racketmanager\get_team;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Cup panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_Cup extends Admin_Championship {

    private Cup_Setup_Action_Dispatcher $cup_setup_action_dispatcher;
    private Cup_Plan_Action_Dispatcher $cup_plan_action_dispatcher;
    private Draw_Action_Dispatcher $draw_action_dispatcher;
    private Admin_Plan_Presenter $admin_plan_presenter;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Cup_Setup_Action_Dispatcher $cup_setup_action_dispatcher = null,
        ?Cup_Plan_Action_Dispatcher $cup_plan_action_dispatcher = null,
        ?Draw_Action_Dispatcher $draw_action_dispatcher = null,
        ?Admin_Plan_Presenter $admin_plan_presenter = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                                 = $this->racketmanager->container;
        $this->admin_action_adapter        = $actions ?? $c->get( 'admin_action_adapter' );
        $this->cup_setup_action_dispatcher = $cup_setup_action_dispatcher ?? $c->get( 'cup_setup_action_dispatcher' );
        $this->cup_plan_action_dispatcher  = $cup_plan_action_dispatcher ?? $c->get( 'cup_plan_action_dispatcher' );
        $this->draw_action_dispatcher      = $draw_action_dispatcher ?? $c->get( 'draw_action_dispatcher' );
        $this->admin_plan_presenter        = $admin_plan_presenter ?? $c->get( 'admin_plan_presenter' );
    }

    /**
     * Function to handle administration cup displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $this->admin_competition = new Admin_Competition( $this->racketmanager );
        $this->admin_club        = new Admin_Club( $this->racketmanager );
        $this->admin_event       = new Admin_Event( $this->racketmanager );
        if ( 'seasons' === $view ) {
            $this->display_seasons_page();
        } elseif ( 'modify' === $view ) {
            $this->admin_competition->display_season_modify_page();
        } elseif ( 'overview' === $view ) {
            $this->display_cup_overview_page();
        } elseif ( 'setup' === $view ) {
            $this->display_cup_setup_page();
        } elseif ( 'setup-event' === $view ) {
            $this->display_setup_event_page();
        } elseif ( 'draw' === $view ) {
            $this->display_cup_draw_page();
        } elseif ( 'matches' === $view ) {
            $this->display_cup_matches_page();
        } elseif ( 'match' === $view ) {
            $this->display_cup_match_page();
        } elseif ( 'plan' === $view ) {
            $this->display_cup_plan_page();
        } elseif ( 'teams' === $view ) {
            $this->display_teams_list();
        } elseif ( 'team' === $view ) {
            $this->admin_club->display_team_page();
        } elseif ( 'config' === $view ) {
            $this->admin_competition->display_config_page();
        } elseif ( 'event' === $view || 'event-config' === $view ) {
            $this->admin_event->display_config_page();
        } else {
            $this->display_cups_page();
        }
    }

    /**
     * Display cups page
     */
    public function display_cups_page(): void {
        $this->apply_flash_message();
        $validator = new Validator();
        $validator = $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg ?? $this->no_permission, true );
            $this->show_message();
            return;
        }
        $this->show_message();
        $competition_type  = 'cup';
        $type              = '';
        $season            = '';
        $standalone        = true;
        $competition_query = array( 'type' => $competition_type );
        $page_title        = ucfirst( $competition_type ) . ' ' . __( 'Competitions', 'racketmanager' );
        include RACKETMANAGER_PATH . 'templates/admin/show-competitions.php';
    }

    /**
     * Display cup overview page
     *
     * @return void
     */
    public function display_cup_overview_page(): void {
        $this->apply_flash_message();
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        try {
            $competition = $this->competition_service->get_competition_by_season( $competition_id, $season );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        $competition_events = $this->competition_service->get_events_for_competition( $competition_id, $season );
        $i                  = 0;
        foreach ( $competition_events as $event ) {
            $leagues = $event->get_leagues();
            if ( $leagues ) {
                $competition_events[ $i ]->leagues = $leagues;
            }
            ++$i;
        }
        $tab        = 'overview';
        $cup_season = (object) $competition->get_season_by_name( (string) $season );
        if ( isset( $cup_season->date_closing ) && $cup_season->date_closing <= gmdate( 'Y-m-d' ) ) {
            $cup_season->is_active = true;
        } else {
            $cup_season->is_active = false;
        }
        $cup_season->is_open    = false;
        $cup_season->venue_name = null;
        if ( isset( $cup_season->venue ) ) {
            try {
                $venue_club             = $this->club_service->get_club( $cup_season->venue );
                $cup_season->venue_name = $venue_club->shortcode;
            } catch ( Club_Not_Found_Exception ) {
                $cup_season->venue_name = null;
            }
        }
        $cup_season->entries  = $this->competition_service->get_clubs_for_competition( $competition_id, $season );
        $competition_overview = $this->competition_service->get_competition_overview( $competition_id, $season );

        $this->show_message();
        require RACKETMANAGER_PATH . 'templates/admin/cup/show-season.php';
    }

    /**
     * Display cup draw
     */
    public function display_cup_draw_page(): void {
        global $tab;
        $this->apply_flash_message();

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
            $league_id = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : ( isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : ( isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null ) );
            $season    = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( strval( $_POST['season'] ) ) ) : ( isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['season'] ) ) ) : null );

            $dto = new Draw_Action_Request_DTO(
                tournament_id: 0,
                league_id: $league_id ?? 0,
                season: $season,
                post: $_POST
            );

            try {
                $response = $this->draw_action_dispatcher->handle( $dto );
            } catch ( Invalid_Status_Exception $e ) {
                $response = new Draw_Action_Response_DTO(
                    message: $e->getMessage(),
                    message_type: Admin_Message_Type::ERROR
                );
            }

            if ( $response->is_handled() ) {
                $extra_args = array();
                if ( null !== $response->tab_override && '' !== $response->tab_override ) {
                    $extra_args['tab'] = $response->tab_override;
                }
                $action_result = new Action_Result_DTO(
                    message: $response->message,
                    message_type: $response->message_type,
                    tab_override: $response->tab_override
                );
                if ( $this->deliver_cup_action_result( $action_result, $_GET, $extra_args ) ) {
                    return;
                }
            }
        }

        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        $league_id      = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : ( isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null );
        $tab            = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'finalResults';

        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        $league = get_league( $league_id );
        if ( $league ) {
            if ( count( $league->championship->groups ) > 0 ) {
                $league->set_group( $league->championship->groups[0] );
            }
            $finals = $this->present_league_finals( $league, (string) $season );
            $this->show_message();
            require RACKETMANAGER_PATH . 'templates/admin/cup/draw.php';
        }
    }

    /**
     * Display cup setup
     */
    public function display_cup_setup_page(): void {
        $this->apply_flash_message();

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
            try {
                $result = $this->cup_setup_action_dispatcher->handle( $_GET, $_POST );
            } catch ( Invalid_Status_Exception $e ) {
                $result = new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
            }
            if ( $this->deliver_cup_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg ?? $this->no_permission, true );
            $this->show_message();
            return;
        }

        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        if ( $competition_id ) {
            try {
                $competition = $this->competition_service->get_by_id( $competition_id );
            } catch ( Competition_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
                $this->show_message();
                return;
            }
            $season_data = $competition->get_season_by_name( (string) $season );
            $match_dates = $season_data['match_dates'] ?? array();
            if ( empty( $match_dates ) ) {
                $date_end     = date_create( $season_data['date_end'] ?? '' );
                $day_end      = $date_end ? date_format( $date_end, 'N' ) : 1;
                $day_adjust   = (int) $day_end - 1;
                $end_date     = Util::amend_date( (string) ( $season_data['date_end'] ?? '' ), $day_adjust, '-' );
                $round_length = $season_data['round_length'] ?? 7;
                $match_date   = null;
                $i            = 0;
                foreach ( $competition->finals as $final ) {
                    $r = $final['round'] - 1;
                    if ( 0 === $i ) {
                        $match_date = $season_data['date_end'] ?? '';
                    } elseif ( 1 === $i ) {
                        if ( $competition->settings['fixed_match_dates'] ) {
                            $match_date = Util::amend_date( (string) $end_date, (int) $round_length, '-' );
                        } else {
                            $match_date = Util::amend_date( (string) ( $season_data['date_end'] ?? '' ), 7 );
                        }
                    } elseif ( 0 === $r && $competition->settings['fixed_match_dates'] ) {
                        $match_date = $season_data['date_start'] ?? '';
                    } else {
                        $match_date = Util::amend_date( (string) $match_date, (int) $round_length, '-' );
                    }
                    $match_dates[ $r ] = $match_date;
                    ++$i;
                }
            }
            $this->show_message();
            require RACKETMANAGER_PATH . 'templates/admin/cup/setup.php';
        }
    }

    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        $this->apply_flash_message();

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
            try {
                $result = $this->cup_setup_action_dispatcher->handle( $_GET, $_POST );
            } catch ( Invalid_Status_Exception $e ) {
                $result = new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
            }
            if ( $this->deliver_cup_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator();
        $validator = $validator->capability( 'edit_matches' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg ?? $this->no_permission, true );
            $this->show_message();
            return;
        }

        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        $league_id = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : ( isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null );
        $league    = get_league( $league_id );
        if ( $league ) {
            $match_count = $league->get_matches(
                array(
                    'count' => true,
                    'final' => 'all',
                )
            );
            $tab                = 'matches';
            $league_season      = $league->seasons[ $season ] ?? array();
            $event_season       = $league->event->get_season_by_name( (string) $season );
            $competition_season = $competition->get_season_by_name( (string) $season );
            $match_dates        = array();
            if ( empty( $league_season['rounds'] ) ) {
                if ( empty( $event_season['match_dates'] ) ) {
                    if ( empty( $league->event->offset ) ) {
                        $match_dates = $competition_season['match_dates'] ?? array();
                    } elseif ( isset( $competition_season['match_dates'] ) && is_array( $competition_season['match_dates'] ) ) {
                        $i               = 0;
                        $num_match_dates = count( $competition_season['match_dates'] );
                        foreach ( $competition_season['match_dates'] as $match_date ) {
                            if ( $i === $num_match_dates - 1 ) {
                                $match_dates[ $i ] = $match_date;
                            } else {
                                $match_dates[ $i ] = Util::amend_date( (string) $match_date, (int) $league->event->offset, '+', 'week' );
                            }
                            ++$i;
                        }
                    }
                } else {
                    $match_dates = $event_season['match_dates'];
                }
            } else {
                foreach ( array_reverse( $league_season['rounds'] ) as $round ) {
                    $match_dates[] = $round->date;
                }
            }
            $this->show_message();
            require RACKETMANAGER_PATH . 'templates/admin/cup/setup.php';
        }
    }

    /**
     * Display cup plan page
     */
    public function display_cup_plan_page(): void {
        $flash      = $this->apply_flash_message();
        $flash_data = $flash['flash_data'] ?? array();

        if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
            try {
                $result = $this->cup_plan_action_dispatcher->handle( $_GET, $_POST );
            } catch ( Invalid_Status_Exception $e ) {
                $result = new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
            }
            if ( $this->deliver_cup_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator_Plan();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg ?? $this->no_permission, true );
            $this->show_message();
            return;
        }

        if ( ! empty( $flash_data['err_flds'] ) && is_array( $flash_data['err_flds'] ) ) {
            $validator->error    = true;
            $validator->err_flds = $flash_data['err_flds'];
            $validator->err_msgs = is_array( $flash_data['err_msgs'] ?? null ) ? $flash_data['err_msgs'] : array();
        }

        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        try {
            $competition = $this->competition_service->get_competition_by_season( $competition_id, $season );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        $competition_events     = $this->competition_service->get_events_for_competition( $competition_id, $season );
        $num_events             = count( $competition_events );
        $cup_season             = (object) $competition->get_season_by_name( (string) $season );
        $cup_season->venue_name = null;
        if ( isset( $cup_season->venue ) ) {
            $venue_club = get_club( $cup_season->venue );
            if ( $venue_club ) {
                $cup_season->venue_name = $venue_club->shortcode;
            }
        }
        if ( ! empty( $flash_data['form_data'] ) && is_array( $flash_data['form_data'] ) ) {
            $form = $flash_data['form_data'];
            if ( isset( $form['startTime'] ) ) {
                $cup_season->starttime = $form['startTime'];
            }
            if ( isset( $form['timeIncrement'] ) ) {
                $cup_season->time_increment = $form['timeIncrement'];
            }
            if ( isset( $form['numCourtsAvailable'] ) ) {
                $cup_season->num_courts = $form['numCourtsAvailable'];
            }
        }
        if ( ! isset( $cup_season->orderofplay ) ) {
            $cup_season->orderofplay = array();
        }
        if ( ! isset( $cup_season->time_increment ) ) {
            $cup_season->time_increment = null;
        }
        if ( ! isset( $cup_season->num_courts ) ) {
            $cup_season->num_courts = null;
        }
        if ( ! isset( $cup_season->starttime ) ) {
            $cup_season->starttime = null;
        }
        $final_matches = $competition->get_matches(
            array(
                'season' => $season,
                'final'  => 'final',
            )
        );
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'matches';

        $errors = Admin_Error_Bag_Mapper::from_validator( $validator );

        $vm = $this->admin_plan_presenter->present_cup_plan(
            competition: $competition,
            cup_season: $cup_season,
            season: (string) $season,
            final_matches: $final_matches,
            tab: $tab,
            num_events: $num_events,
            errors: $errors,
        );

        $this->show_message();
        require RACKETMANAGER_PATH . 'templates/admin/cup/plan.php';
    }

    /**
     * Display cup matches page
     */
    public function display_cup_matches_page(): void {
        global $competition;
        $this->apply_flash_message();

        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->no_permission, true );
            $this->show_message();
            return;
        }

        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        $league_id      = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : ( isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null );
        $final_key      = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;

        if ( $competition_id ) {
            $competition = $this->competition_service->get_competition( $competition_id );
            if ( $competition && $league_id ) {
                $league = get_league( $league_id );
                if ( $league ) {
                    $is_finals       = false;
                    $single_cup_game = false;
                    $bulk            = false;
                    $matches         = array();
                    if ( $final_key ) {
                        $is_finals = true;
                        $mode      = 'edit';
                        $edit      = true;

                        $final           = $league->championship->get_finals( $final_key );
                        $num_first_round = $league->championship->num_teams_first_round;

                        $max_matches = $final['num_matches'] ?? 0;

                        /* translators: %s: round name */
                        $form_title = sprintf( __( 'Edit Matches - %s', 'racketmanager' ), Util::get_final_name( (string) $final_key ) );
                        $match_args = array(
                            'final'   => $final_key,
                            'orderby' => array(
                                'id' => 'ASC',
                            ),
                        );
                        if ( 'final' !== $final_key && ! empty( $league->current_season['home_away'] ) && 'true' === $league->current_season['home_away'] ) {
                            $match_args['leg'] = 1;
                        }
                        $matches      = $league->get_matches( $match_args );
                        $teams        = $league->championship->get_final_teams( $final_key );
                        $submit_title = $form_title;
                    }
                    $this->show_message();
                    require RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
                }
            }
        }
    }

    /**
     * Display cup match page
     */
    public function display_cup_match_page(): void {
        $this->apply_flash_message();

        if ( ! current_user_can( 'edit_matches' ) ) {
            $this->set_message( $this->no_permission, true );
            $this->show_message();
            return;
        }

        $season         = isset( $_GET['season'] ) ? intval( $_GET['season'] ) : null;
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        $league_id      = isset( $_GET['league'] ) ? intval( $_GET['league'] ) : ( isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null );
        $final_key      = isset( $_GET['final'] ) ? sanitize_text_field( wp_unslash( $_GET['final'] ) ) : null;
        $match_id       = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;

        if ( $competition_id ) {
            $competition = $this->competition_service->get_competition( $competition_id );
            if ( $competition && $league_id && $match_id ) {
                $league = get_league( $league_id );
                if ( $league ) {
                    $match = $this->fixture_repository->find_by_id( (int) $match_id );
                    if ( $match ) {
                        $is_finals       = true;
                        $single_cup_game = true;
                        $bulk            = false;
                        $mode            = 'edit';
                        $edit            = true;
                        $form_title      = __( 'Edit Match', 'racketmanager' );
                        $submit_title    = $form_title;
                        $matches[0]      = $match;
                        $match_day       = $match->match_day;
                        $max_matches     = 1;
                        $final           = $league->championship->get_finals( $final_key );
                        $final_teams     = $league->championship->get_final_teams( $final['key'] ?? '' );
                        if ( is_numeric( $match->home_team ) ) {
                            $home_team  = get_team( $match->home_team );
                            $home_title = $home_team?->title;
                        } else {
                            $home_team  = $final_teams[ $match->home_team ] ?? null;
                            $home_title = $home_team ? $home_team->title : null;
                        }
                        if ( is_numeric( $match->away_team ) ) {
                            $away_team  = get_team( $match->away_team );
                            $away_title = $away_team?->title;
                        } else {
                            $away_team  = $final_teams[ $match->away_team ] ?? null;
                            $away_title = $away_team ? $away_team->title : null;
                        }
                        $this->show_message();
                        require RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
                    }
                }
            }
        }
    }
}
