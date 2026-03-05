<?php
/**
 * RacketManager-Admin API: RacketManager-admin-tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Tournament
 */

namespace Racketmanager\Admin;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Admin\Controllers\Tournament_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Information_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Match_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Matches_Admin_Controller;
use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Admin\Controllers\Tournament_Setup_Event_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Information_Page_View_Model;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Tournament_Match_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Matches_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Modify_Page_View_Model;
use Racketmanager\Admin\Controllers\Tournament_Plan_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Admin\Controllers\Tournament_Tournaments_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Tournaments_Page_View_Model;
use Racketmanager\Admin\Controllers\Tournament_Overview_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;
use Racketmanager\Admin\Controllers\Tournament_Setup_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Setup_Page_View_Model;
use Racketmanager\Admin\Controllers\Tournament_Draw_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_event;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_Tournament extends Admin_Championship {
    private function msg_controller_not_available(): string {
        return __( 'Controller not available', 'racketmanager' );
    }

    private function msg_invalid_view_model(): string {
        return __( 'Invalid view model', 'racketmanager' );
    }

    /**
     * @return bool True when the current request is a POST.
     */
    private function is_post_request(): bool {
        return 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) );
    }

    /**
     * Redirect helper with a "headers already sent" JS fallback.
     *
     * @param string $redirect_url
     * @return void
     */
    #[NoReturn]
    private function redirect_or_js_fallback( string $redirect_url ): void {
        if ( headers_sent() ) {
            $js_url   = esc_url_raw( $redirect_url );
            $html_url = esc_url( $redirect_url );

            $js = 'window.location.replace(' . wp_json_encode( $js_url ) . ');';
            echo '<script>' . $js . '</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_attr( $html_url ) . '"></noscript>';
            exit;
        }

        wp_safe_redirect( $redirect_url );
        exit;
    }

    /**
     * Function to handle administration tournament displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = [
                'modify'       => [ $this, 'display_tournament_page' ],
                'plan'         => [ $this, 'display_plan_page' ],
                'tournament'   => [ $this, 'display_tournament_overview_page' ],
                'draw'         => [ $this, 'display_draw_page' ],
                'setup'        => [ $this, 'display_setup_page' ],
                'setup-event'  => [ $this, 'display_setup_event_page' ],
                'matches'      => [ $this, 'display_matches_page' ],
                'match'        => [ $this, 'display_match_page' ],
                'teams'        => [ $this, 'display_teams_list' ],
                'contact'      => [ $this, 'display_contact_page' ],
                'information'  => [ $this, 'display_information_page' ],
            // Views handled by external sub-controllers
                'config'       => [ $this->get_admin_competition(), 'display_config_page' ],
                'event-config' => [ $this->get_admin_event(), 'display_config_page' ],
                'team'         => [ $this->get_admin_club(), 'display_team_page' ],
        ];

        try {
            // Resolve the callback or fall back to default
            $callback = $view_map[ $view ] ?? [ $this, 'display_tournaments_page' ];

            call_user_func( $callback );

        } catch ( Tournament_Not_Found_Exception | Invalid_Status_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
        }
    }

    private function get_admin_competition(): Admin_Competition {
        return $this->admin_competition ??= new Admin_Competition( $this->racketmanager );
    }

    private function get_admin_event(): Admin_Event {
        return $this->admin_event ??= new Admin_Event( $this->racketmanager );
    }

    private function get_admin_club(): Admin_Club {
        return $this->admin_club ??= new Admin_Club( $this->racketmanager );
    }

    /**
     * Display tournaments page
     */
    public function display_tournaments_page(): void {
        $controller = $this->racketmanager->container->get( 'tournament_tournaments_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Tournaments_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->tournaments_page( $_GET, $_POST );

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Tournaments_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        $vars = $vm->to_template_vars();
        foreach ( $vars as $key => $value ) {
            ${$key} = $value;
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/show-tournaments.php';
    }

    /**
     * Display tournament overview
     */
    public function display_tournament_overview_page(): void {
        $controller = $this->racketmanager->container->get( 'tournament_overview_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Overview_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->overview_page( $_GET, $_POST );

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Overview_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/show-tournament.php';
    }

    /**
     * Display tournament draw
     */
    public function display_draw_page(): void {
        $is_post = $this->is_post_request();

        $flash = ( new Admin_Flash_Message_Store() )->pop();
        if ( ! empty( $flash['message'] ) ) {
            $this->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }

        $controller = $this->racketmanager->container->get( 'tournament_draw_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Draw_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->draw_page( $_GET, $_POST );

        // PRG: if this request is a POST, store the message (if any) and redirect to GET.
        if ( $is_post ) {
            if ( ! empty( $result['message'] ) ) {
                ( new Admin_Flash_Message_Store() )->set(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
                );
            }

            $tab = isset( $result['redirect_tab'] ) ? strval( $result['redirect_tab'] ) : ( isset( $_GET['league-tab'] ) ? strval( $_GET['league-tab'] ) : 'finalResults' );
            $this->redirect_or_js_fallback(
                Admin_Redirect_Url_Builder::tournament_draw_view(
                    $_GET,
                    $_POST,
                    'draw',
                    isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null,
                    isset( $_GET['league'] ) ? intval( $_GET['league'] ) : null,
                    $tab
                )
            );
        }
        
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Draw_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/draw.php';
    }

    /**
     * Display tournament setup
     */
    public function display_setup_page(): void {
        $is_post = $this->is_post_request();

        $flash = ( new Admin_Flash_Message_Store() )->pop();
        if ( ! empty( $flash['message'] ) ) {
            $this->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }

        $controller = $this->racketmanager->container->get( 'tournament_setup_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Setup_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->setup_page( $_GET, $_POST );

        // PRG: if this request is a POST, store the message (if any) and redirect to GET.
        if ( $is_post ) {
            if ( ! empty( $result['message'] ) ) {
                ( new Admin_Flash_Message_Store() )->set(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
                );
            }

            // Preserve context and redirect back to setup screen.
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : ( isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null );
            // phpcs:enable WordPress.Security.NonceVerification.Recommended

            $this->redirect_or_js_fallback(
                Admin_Redirect_Url_Builder::tournament_setup_view(
                    $_GET,
                    $_POST,
                    $tournament_id
                )
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Setup_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/setup.php';
    }

    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        $is_post = $this->is_post_request();

        $flash = ( new Admin_Flash_Message_Store() )->pop();
        if ( ! empty( $flash['message'] ) ) {
            $this->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }

        $controller = $this->racketmanager->container->get( 'tournament_setup_event_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Setup_Event_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->setup_event_page( $_GET, $_POST );

        // PRG: if this request is a POST, store the message (if any) and redirect to GET.
        if ( $is_post ) {
            if ( ! empty( $result['message'] ) ) {
                ( new Admin_Flash_Message_Store() )->set(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
                );
            }

            if ( ! empty( $result['redirect'] ) ) {
                $this->redirect_or_js_fallback( strval( $result['redirect'] ) );
            }
        }
        
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Setup_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/setup.php';
    }

    /**
     * Display tournament page
     */
    public function display_tournament_page(): void {
        $controller = $this->racketmanager->container->get( 'tournament_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( __( 'Controller not available', 'racketmanager' ) );
        }

        $result = $controller->modify_page( $_GET, $_POST );

        if ( ! empty( $result['redirect'] ) ) {
            $this->redirect_or_js_fallback( strval( $result['redirect'] ) );
        }

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Modify_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        $vars = $vm->to_template_vars();
        foreach ( $vars as $key => $value ) {
            ${$key} = $value;
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament-edit.php';
    }

    /**
     * Display tournament plan page
     */
    public function display_plan_page(): void {
        $controller = $this->racketmanager->container->get( 'tournament_plan_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Plan_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->plan_page( $_GET, $_POST );

        if ( ! empty( $result['redirect'] ) ) {
            $redirect_url = strval( $result['redirect'] );
            $this->redirect_or_js_fallback( $redirect_url );
        }

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Plan_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/plan.php';
    }

    /**
     * Display tournament matches page
     */
    public function display_matches_page(): void {
        $flash = ( new Admin_Flash_Message_Store() )->pop();
        if ( ! empty( $flash['message'] ) ) {
            $this->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }

        $controller = $this->racketmanager->container->get( 'tournament_matches_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Matches_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->matches_page( $_GET, $_POST );

        if ( ! empty( $result['redirect'] ) ) {
            if ( ! empty( $result['message'] ) ) {
                ( new Admin_Flash_Message_Store() )->set(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
                );
            }
            $this->redirect_or_js_fallback( strval( $result['redirect'] ) );
        }

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Matches_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
    }

    /**
     * Display tournament match page
     */
    public function display_match_page(): void {
        $flash = ( new Admin_Flash_Message_Store() )->pop();
        if ( ! empty( $flash['message'] ) ) {
            $this->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }

        $controller = $this->racketmanager->container->get( 'tournament_match_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Match_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->match_page( $_GET, $_POST );

        if ( ! empty( $result['redirect'] ) ) {
            if ( ! empty( $result['message'] ) ) {
                ( new Admin_Flash_Message_Store() )->set(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
                );
            }
            $this->redirect_or_js_fallback( strval( $result['redirect'] ) );
        }

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Match_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        require_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
    }

    /**
     * Contact teams in tournament in admin screen
     */
    private function contact_tournament_teams(): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_contact-teams-preview' );
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->error, true );
            return;
        }
        $tournament_id = isset( $_POST['tournament_id'] ) ? intval( $_POST['tournament_id'] ) : null;
        $message       = isset( $_POST['emailMessage'] ) ? htmlspecialchars_decode( $_POST['emailMessage'] ) : null;
        $active        = isset( $_POST['contactTeamActive'] );
        try {
            $sent = $this->tournament_service->contact_teams( $tournament_id, $message, $active );
            if ( $sent ) {
                $this->set_message( __( 'Email sent to players', 'racketmanager' ) );
            } else {
                $this->set_message( __( 'Unable to send email', 'racketmanager' ), true );
            }
        } catch ( Tournament_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
        }
    }
    /**
     * Display tournament information page
     */
    public function display_information_page(): void {
        $flash = ( new Admin_Flash_Message_Store() )->pop();
        if ( ! empty( $flash['message'] ) ) {
            $this->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }

        $controller = $this->racketmanager->container->get( 'tournament_information_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Information_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->information_page( $_GET, $_POST );

        if ( ! empty( $result['redirect'] ) ) {
            if ( ! empty( $result['message'] ) ) {
                ( new Admin_Flash_Message_Store() )->set(
                    strval( $result['message'] ),
                    $result['message_type'] ?? false
                );
            }

            $this->redirect_or_js_fallback( strval( $result['redirect'] ) );
        }

        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        if ( ! ( $vm instanceof Tournament_Information_Page_View_Model ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }

        // Preferred: templates use $vm. Kept locals for BC with older templates/includes.
        $tournament = $vm->tournament;
        $errors     = $vm->errors;

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/information.php';
    }
    /**
     * Calculate team ratings function
     *
     * @param object $league league object.
     *
     * @return void
     */
    private function edit_player_team( object $league ): void {

    }
    /**
     * Add a new season to competition
     *
     * @param string $season season.
     * @param int $competition_id competition id.
     * @param int|null $num_match_days number of match days.
     *
     * @return array|boolean
     */
    public function add_season_to_competition( string $season, int $competition_id, ?int $num_match_days = null ): bool|array {
        try {
            $competition = $this->competition_service->get_competition( $competition_id );
        } catch ( Competition_Not_Found_Exception ) {
            return false;
        }
        if ( ! $num_match_days ) {
            $num_match_days = Util::get_default_match_days( $competition->type );
        }
        if ( ! $num_match_days ) {
            $this->set_message( __( 'Number of match days not specified', 'racketmanager' ), 'error' );
            return false;
        }
        $seasons            = empty( $competition->get_seasons() ) ? array() : $competition->get_seasons();
        $seasons[ $season ] = array(
            'name'           => $season,
            'num_match_days' => $num_match_days,
            'status'         => 'draft',
        );
        ksort( $seasons );
        $competition->update_seasons( $seasons );
        $events = $this->competition_service->get_events_for_competition( $competition_id );
        foreach ( $events as $event ) {
            if ( empty( $event->get_season_by_name( $season ) ) ) {
                $this->add_season_to_event( $season, $event->id, $num_match_days );
            }
        }
        /* translators: %s: season name */
        $this->set_message( sprintf( __( 'Season %s added', 'racketmanager' ), $season ) );

        return $competition->get_season_by_name( $season );
    }
    /**
     * Edit season in object - competition or event
     *
     * @param object $season_data season data.
     */
    private function edit_season( object $season_data ): void {
        $competition = null;
        $event       = null;
        if ( 'competition' === $season_data->type ) {
            try {
                $competition = $this->competition_service->get_by_id( $season_data->object_id );
                $object      = $competition;
            } catch ( Competition_Not_Found_Exception ) {
                $object = null;
            }
        } elseif ( 'event' === $season_data->type ) {
            $event  = get_event( $season_data->object_id );
            $object = $event;
        } else {
            $object      = null;
        }
        $seasons                         = $object->seasons;
        $seasons[ $season_data->season ] = array(
            'name'              => $season_data->season,
            'num_match_days'    => $season_data->num_match_days,
            'match_dates'       => $season_data->match_dates,
            'home_away'         => $season_data->home_away,
            'fixed_match_dates' => $season_data->fixed_dates,
            'status'            => $season_data->status,
            'date_closing'      => $season_data->date_closing,
        );
        if ( 'competition' === $season_data->type ) {
            $seasons[ $season_data->season ]['date_open']        = $season_data->date_open;
            $seasons[ $season_data->season ]['date_start']       = $season_data->date_start;
            $seasons[ $season_data->season ]['date_end']         = $season_data->date_end;
            $seasons[ $season_data->season ]['competition_code'] = $season_data->competition_code;
            $seasons[ $season_data->season ]['venue']            = $season_data->venue ?? null;
            $seasons[ $season_data->season ]['grade']            = $season_data->grade ?? null;
        }
        ksort( $seasons );
        if ( 'competition' === $season_data->type ) {
            $competition->update_seasons( $seasons );
        } elseif ( 'event' === $season_data->type ) {
            $event->update_seasons(  $seasons );
        }
        if ( 'competition' === $season_data->type ) {
            $events = $this->competition_service->get_events_for_competition( $competition->id );
            foreach ( $events as $event ) {
                $event_season                 = new stdClass();
                $event_season->object_id      = $event->id;
                $event_season->type           = 'event';
                $event_season->season         = $season_data->season;
                $event_season->num_match_days = $season_data->num_match_days;
                $event_season->match_dates    = $season_data->match_dates;
                $event_season->home_away      = $season_data->home_away;
                $event_season->fixed_dates    = $season_data->fixed_dates;
                $event_season->status         = $season_data->status;
                $event_season->date_closing   = $season_data->date_closing;
                $this->edit_season( $event_season );
            }
        }
    }
    /**
     * Add a new season to event
     *
     * @param string $season season.
     * @param int $event_id event_id.
     * @param int|null $num_match_days number of match days.
     *
     * @return void
     */
    private function add_season_to_event( string $season, int $event_id, ?int $num_match_days ): void {
        global $event;

        $event = get_event( $event_id );
        if ( empty( $event->get_seasons() ) ) {
            $event_seasons = array();
        } else {
            $event_seasons = $event->get_seasons();
        }
        if ( $event->is_box ) {
            $event_seasons[ $season ] = array(
                'name'           => $season,
                'num_match_days' => 0,
                'status'         => 'draft',
            );
        } else {
            if ( ! $num_match_days ) {
                $num_match_days = Util::get_default_match_days( $event->competition->type );
            }
            if ( ! $num_match_days ) {
                $this->set_message( __( 'Number of match days not specified', 'racketmanager' ), 'error' );
                return;
            }
            $event_seasons[ $season ] = array(
                'name'           => $season,
                'num_match_days' => $num_match_days,
                'status'         => 'draft',
            );
        }
        $seasons = $event->get_seasons();
        ksort( $seasons );
        $event->update_seasons( $seasons );
    }
}
