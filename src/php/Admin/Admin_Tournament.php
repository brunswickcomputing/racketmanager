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
use Racketmanager\Admin\Controllers\Tournament_Contact_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Draw_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Information_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Match_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Matches_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Overview_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Plan_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Setup_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Setup_Event_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Teams_Admin_Controller;
use Racketmanager\Admin\Controllers\Tournament_Tournaments_Admin_Controller;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Tournament_Contact_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Information_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Match_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Matches_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Setup_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Tournaments_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use function Racketmanager\debug_to_console;

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
     * @param array{redirect?:string,message?:string,message_type?:bool|string} $result
     * @return void
     */
    private function redirect_with_flash_if_needed( array $result ): void {
        if ( empty( $result['redirect'] ) ) {
            return;
        }

        $this->store_flash_message( $result );
        $this->redirect_or_js_fallback( strval( $result['redirect'] ) );
    }

    private function apply_flash_message(): void {
        $flash = ( new Admin_Flash_Message_Store() )->pop();
        if ( ! empty( $flash['message'] ) ) {
            $this->set_message(
                strval( $flash['message'] ),
                $flash['message_type'] ?? false
            );
        }
    }

    /**
     * @param array{message?:string,message_type?:bool|string} $result
     * @return void
     */
    private function apply_result_message( array $result ): void {
        if ( ! empty( $result['message'] ) ) {
            $this->set_message(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }
    }

    /**
     * @param array{message?:string,message_type?:bool|string} $result
     * @return void
     */
    private function store_flash_message( array $result ): void {
        if ( ! empty( $result['message'] ) ) {
            ( new Admin_Flash_Message_Store() )->set(
                strval( $result['message'] ),
                $result['message_type'] ?? false
            );
        }
    }

    /**
     * @param mixed $view_model
     * @param string $expected_class
     * @return void
     */
    private function assert_view_model_instance( mixed $view_model, string $expected_class ): void {
        if ( ! ( $view_model instanceof $expected_class ) ) {
            throw new Invalid_Status_Exception( $this->msg_invalid_view_model() );
        }
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
            'tournament'   => [ $this, 'display_tournament_overview_page' ],
            'modify'       => [ $this, 'display_tournament_page' ],
            'plan'         => [ $this, 'display_plan_page' ],
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
            $callback = $view_map[ $view ] ?? [ $this, 'display_tournaments_page' ];
            $callback();
        } catch ( Tournament_Not_Found_Exception | Invalid_Status_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
        }
    }

    /**
     * Tournament teams list (add teams) — PRG + flash + controller-service.
     */
    public function display_teams_list(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_teams_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Teams_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->teams_page( $_GET, $_POST );

        $this->redirect_with_flash_if_needed( $result );

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Teams_List_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/includes/teams-list.php';
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

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Tournaments_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/show-tournaments.php';
    }

    /**
     * Display tournament overview
     */
    public function display_tournament_overview_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_overview_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Overview_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->overview_page( $_GET, $_POST );

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Overview_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/show-tournament.php';
    }

    /**
     * Display tournament draw
     */
    public function display_draw_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_draw_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Draw_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->draw_page( $_GET, $_POST );

        if ( $this->is_post_request() ) {
            $this->redirect_with_flash_if_needed( $result );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Draw_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/draw.php';
    }

    /**
     * Display tournament setup
     */
    public function display_setup_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_setup_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Setup_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->setup_page( $_GET, $_POST );

        if ( $this->is_post_request() ) {
            $this->redirect_with_flash_if_needed( $result );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Setup_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/setup.php';
    }

    /**
     * Display event setup
     */
    public function display_setup_event_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_setup_event_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Setup_Event_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->setup_event_page( $_GET, $_POST );

        if ( $this->is_post_request() ) {
            $this->redirect_with_flash_if_needed( $result );
        }

        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Setup_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/setup.php';
    }

    /**
     * Display tournament page
     */
    public function display_tournament_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->modify_page( $_GET, $_POST );

        $this->redirect_with_flash_if_needed( $result );

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Modify_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament-edit.php';
    }

    /**
     * Display tournament plan page
     */
    public function display_plan_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_plan_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Plan_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->plan_page( $_GET, $_POST );

        $this->redirect_with_flash_if_needed( $result );

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Plan_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/plan.php';
    }

    /**
     * Display tournament matches page
     */
    public function display_matches_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_matches_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Matches_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->matches_page( $_GET, $_POST );

        $this->redirect_with_flash_if_needed( $result );

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Matches_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
    }

    /**
     * Display tournament match page
     */
    public function display_match_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_match_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Match_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->match_page( $_GET, $_POST );

        $this->redirect_with_flash_if_needed( $result );

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Match_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/includes/match.php';
    }

    /**
     * Display tournament contact page
     */
    public function display_contact_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_contact_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Contact_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->contact_page( $_GET, $_POST );

        $this->redirect_with_flash_if_needed( $result );

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Contact_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/includes/contact.php';
    }

    /**
     * Display tournament information page
     */
    public function display_information_page(): void {
        $this->apply_flash_message();

        $controller = $this->racketmanager->container->get( 'tournament_information_admin_controller' );
        if ( ! ( $controller instanceof Tournament_Information_Admin_Controller ) ) {
            throw new Invalid_Status_Exception( $this->msg_controller_not_available() );
        }

        $result = $controller->information_page( $_GET, $_POST );

        $this->redirect_with_flash_if_needed( $result );

        $this->apply_result_message( $result );
        $this->show_message();

        $vm = $result['view_model'] ?? null;
        $this->assert_view_model_instance( $vm, Tournament_Information_Page_View_Model::class );

        require_once RACKETMANAGER_PATH . 'templates/admin/tournament/information.php';
    }
}
