<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-admin-cup class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Cup
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Admin_Page_Context;
use Racketmanager\Admin\Controllers\Competition_Seasons_Admin_Controller;
use Racketmanager\Admin\Controllers\Contact_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Competitions_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Draw_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Match_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Matches_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Overview_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Plan_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Setup_Admin_Controller;
use Racketmanager\Admin\Controllers\Cup_Setup_Event_Admin_Controller;
use Racketmanager\Admin\Controllers\Teams_Admin_Controller;
use Racketmanager\Admin\View_Models\Competition_Seasons_Page_View_Model;
use Racketmanager\Admin\View_Models\Competitions_Page_View_Model;
use Racketmanager\Admin\View_Models\Contact_Page_View_Model;
use Racketmanager\Admin\View_Models\Cup_Draw_Page_View_Model;
use Racketmanager\Admin\View_Models\Cup_Overview_Page_View_Model;
use Racketmanager\Admin\View_Models\Cup_Plan_Page_View_Model;
use Racketmanager\Admin\View_Models\Setup_Page_View_Model;
use Racketmanager\Admin\View_Models\Teams_List_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Bridge for the cup administration screens.
 *
 * Routes every view to a dedicated admin controller and completes the request (PRG redirect or render).
 */
final readonly class Admin_Cup {
    use Completes_Admin_Requests;

    public function __construct(
        private Simple_Container $container,
        private Admin_Message_Service $message_service,
        Admin_Action_Adapter $actions,
        Admin_Screen_Presenter $screens,
    ) {
        $this->actions = $actions;
        $this->screens = $screens;
    }

    /**
     * Handle cup screen displays.
     *
     * @param string|null $view View name.
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = array(
            'seasons'      => array( $this, 'display_seasons_page' ),
            'overview'     => array( $this, 'display_cup_overview_page' ),
            'setup'        => array( $this, 'display_cup_setup_page' ),
            'setup-event'  => array( $this, 'display_setup_event_page' ),
            'modify'       => array( $this, 'display_modify_page' ),
            'config'       => array( $this, 'display_config_page' ),
            'event'        => array( $this, 'display_event_config_page' ),
            'event-config' => array( $this, 'display_event_config_page' ),
            'draw'         => array( $this, 'display_cup_draw_page' ),
            'matches'      => array( $this, 'display_cup_matches_page' ),
            'match'        => array( $this, 'display_cup_match_page' ),
            'plan'         => array( $this, 'display_cup_plan_page' ),
            'teams'        => array( $this, 'display_teams_list' ),
            'team'         => array( $this, 'display_team_page' ),
            'contact'      => array( $this, 'display_contact_page' ),
        );

        try {
            $callback = $view_map[ $view ?? '' ] ?? array( $this, 'display_cups_page' );
            $callback();
        } catch ( Invalid_Status_Exception | League_Not_Found_Exception | Competition_Not_Found_Exception | Season_Not_Found_Exception | Event_Not_Found_Exception | Fixture_Not_Found_Exception | Tournament_Not_Found_Exception | Club_Not_Found_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }

    public function display_cups_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Competitions_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_competitions_admin_controller' );
        $this->complete_request( $controller->competitions_page( $_GET, $_POST ), Competitions_Page_View_Model::class, 'admin/show-competitions' );
    }

    public function display_cup_overview_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Overview_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_overview_admin_controller' );
        $this->complete_request( $controller->overview_page( $_GET, $_POST ), Cup_Overview_Page_View_Model::class, 'admin/cup/show-season' );
    }

    public function display_overview_page(): void {
        $this->display_cup_overview_page();
    }

    public function display_cup_setup_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Setup_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_setup_admin_controller' );
        $this->complete_request( $controller->setup_page( $_GET, $_POST ), Setup_Page_View_Model::class, 'admin/cup/setup' );
    }

    public function display_setup_page(): void {
        $this->display_cup_setup_page();
    }

    public function display_setup_event_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Setup_Event_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_setup_event_admin_controller' );
        $this->complete_request( $controller->setup_event_page( $_GET, $_POST ), Setup_Page_View_Model::class, 'admin/cup/setup' );
    }

    public function display_cup_draw_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Draw_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_draw_admin_controller' );
        $this->complete_request( $controller->draw_page( $_GET, $_POST ), Cup_Draw_Page_View_Model::class, 'admin/cup/draw' );
    }

    public function display_draw_page(): void {
        $this->display_cup_draw_page();
    }

    public function display_cup_matches_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Matches_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_matches_admin_controller' );
        $this->complete_request( $controller->matches_page( $_GET, $_POST ), Tournament_Fixtures_Page_View_Model::class, 'admin/includes/match' );
    }

    public function display_matches_page(): void {
        $this->display_cup_matches_page();
    }

    public function display_cup_match_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Match_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_match_admin_controller' );
        $this->complete_request( $controller->match_page( $_GET, $_POST ), Tournament_Fixtures_Page_View_Model::class, 'admin/includes/match' );
    }

    public function display_match_page(): void {
        $this->display_cup_match_page();
    }

    public function display_cup_plan_page(): void {
        $flash = $this->screens->apply_flash();

        /** @var Cup_Plan_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_plan_admin_controller' );
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();
        $this->complete_request( $controller->plan_page( $_GET, $_POST, $flash_data ), Cup_Plan_Page_View_Model::class, 'admin/cup/plan' );
    }

    public function display_plan_page(): void {
        $this->display_cup_plan_page();
    }

    public function display_teams_list(): void {
        $this->screens->apply_flash();

        /** @var Teams_Admin_Controller $controller */
        $controller = $this->container->get( 'teams_admin_controller' );
        $this->complete_request( $controller->teams_page( $_GET, $_POST, Admin_Page_Context::cup() ), Teams_List_Page_View_Model::class, 'admin/includes/teams-list' );
    }

    public function display_teams_page(): void {
        $this->display_teams_list();
    }

    public function display_contact_page(): void {
        $this->screens->apply_flash();

        /** @var Contact_Admin_Controller $controller */
        $controller = $this->container->get( 'contact_admin_controller' );
        $this->complete_request( $controller->contact_page( $_GET, $_POST, Admin_Page_Context::cup() ), Contact_Page_View_Model::class, 'admin/includes/contact' );
    }

    public function display_seasons_page(): void {
        $this->screens->apply_flash();

        /** @var Competition_Seasons_Admin_Controller $controller */
        $controller = $this->container->get( 'competition_seasons_admin_controller' );
        $this->complete_request( $controller->seasons_page( $_GET, $_POST ), Competition_Seasons_Page_View_Model::class, 'admin/includes/show-seasons' );
    }

    public function display_modify_page(): void {
        $this->container->get( 'admin_competition_screen' )->handle_display( 'modify' );
    }

    public function display_config_page(): void {
        $this->container->get( 'admin_competition_screen' )->handle_display( 'config' );
    }

    public function display_team_page(): void {
        $this->container->get( 'admin_club_screen' )->handle_display( 'team' );
    }

    public function display_event_config_page(): void {
        $this->container->get( 'admin_event_screen' )->display_config_page();
    }
}
