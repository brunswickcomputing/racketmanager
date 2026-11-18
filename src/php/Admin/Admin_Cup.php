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

    /**
     * Initialises the admin component with its container, message service, actions, and screen presenter.
     *
     * @param Simple_Container       $container The service container instance.
     * @param Admin_Message_Service  $message_service The admin message service instance.
     * @param Admin_Action_Adapter   $actions The admin action adapter instance.
     * @param Admin_Screen_Presenter $screens The admin screen presenter instance.
     *
     * @return void
     */
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

    /**
     * Displays the cup competitions admin page using the current request data and any available flash data.
     *
     * @return void
     */
    public function display_cups_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();

        /** @var Cup_Competitions_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_competitions_admin_controller' );
        $this->complete_request( $controller->competitions_page( $_GET, $_POST, $flash_data ), Competitions_Page_View_Model::class, 'admin/show-competitions' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the cup overview admin page and completes the request with the generated view model.
     *
     * @return void
     */
    public function display_cup_overview_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Overview_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_overview_admin_controller' );
        $this->complete_request( $controller->overview_page( $_GET, $_POST ), Cup_Overview_Page_View_Model::class, 'admin/cup/show-season' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the overview page by rendering the CUP overview screen.
     *
     * @return void
     */
    public function display_overview_page(): void {
        $this->display_cup_overview_page();
    }

    /**
     * Displays the cup setup administration page.
     *
     * Applies pending screen flash messages, resolves the cup setup controller,
     * handles the current request data, and renders the cup setup page view.
     *
     * @return void
     */
    public function display_cup_setup_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Setup_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_setup_admin_controller' );
        $this->complete_request( $controller->setup_page( $_GET, $_POST ), Setup_Page_View_Model::class, 'admin/cup/setup' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the setup page by rendering the CUP setup page.
     *
     * @return void
     */
    public function display_setup_page(): void {
        $this->display_cup_setup_page();
    }

    /**
     * Displays the setup event admin page, applying flash messages and completing the setup page request.
     *
     * @return void
     */
    public function display_setup_event_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Setup_Event_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_setup_event_admin_controller' );
        $this->complete_request( $controller->setup_event_page( $_GET, $_POST ), Setup_Page_View_Model::class, 'admin/cup/setup' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the cup draw administration page.
     *
     * Applies any pending screen flash messages, resolves the cup draw controller,
     * processes the current request data, and renders the cup draw page view.
     *
     * @return void
     */
    public function display_cup_draw_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Draw_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_draw_admin_controller' );
        $this->complete_request( $controller->draw_page( $_GET, $_POST ), Cup_Draw_Page_View_Model::class, 'admin/cup/draw' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the draw administration page.
     *
     * Delegates rendering to the cup draw page display handler.
     *
     * @return void
     */
    public function display_draw_page(): void {
        $this->display_cup_draw_page();
    }

    /**
     * Displays the cup matches administration page.
     *
     * Applies any pending screen flash messages, resolves the cup matches controller,
     * processes the current request data, and renders the tournament fixtures match view.
     *
     * @return void
     */
    public function display_cup_matches_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Matches_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_matches_admin_controller' );
        $this->complete_request( $controller->matches_page( $_GET, $_POST ), Tournament_Fixtures_Page_View_Model::class, 'admin/includes/match' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the matches administration page.
     *
     * Delegates rendering to the cup matches page display handler.
     *
     * @return void
     */
    public function display_matches_page(): void {
        $this->display_cup_matches_page();
    }

    /**
     * Displays the cup match administration page.
     *
     * Applies any pending screen flash messages, resolves the cup match controller,
     * processes the current request data, and renders the match fixtures view.
     *
     * @return void
     */
    public function display_cup_match_page(): void {
        $this->screens->apply_flash();

        /** @var Cup_Match_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_match_admin_controller' );
        $this->complete_request( $controller->match_page( $_GET, $_POST ), Tournament_Fixtures_Page_View_Model::class, 'admin/includes/match' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the match administration page.
     *
     * Delegates rendering to the cup match page display handler.
     *
     * @return void
     */
    public function display_match_page(): void {
        $this->display_cup_match_page();
    }

    /**
     * Displays the cup plan administration page.
     *
     * Applies pending screen flash messages, resolves the cup plan controller,
     * extracts any available flash data, processes the current request data, and
     * renders the cup plan page view.
     *
     * @return void
     */
    public function display_cup_plan_page(): void {
        $flash = $this->screens->apply_flash();

        /** @var Cup_Plan_Admin_Controller $controller */
        $controller = $this->container->get( 'cup_plan_admin_controller' );
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();
        $this->complete_request( $controller->plan_page( $_GET, $_POST, $flash_data ), Cup_Plan_Page_View_Model::class, 'admin/cup/plan' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the cup plan administration page.
     *
     * Delegates rendering to the cup plan page display handler.
     *
     * @return void
     */
    public function display_plan_page(): void {
        $this->display_cup_plan_page();
    }

    /**
     * Displays the teams list administration page.
     *
     * Applies any pending screen flash messages, resolves the teams administration
     * controller, processes the current request data in the cup admin context, and
     * renders the teams list view.
     *
     * @return void
     */
    public function display_teams_list(): void {
        $this->screens->apply_flash();

        /** @var Teams_Admin_Controller $controller */
        $controller = $this->container->get( 'teams_admin_controller' );
        $this->complete_request( $controller->teams_page( $_GET, $_POST, Admin_Page_Context::cup() ), Teams_List_Page_View_Model::class, 'admin/includes/teams-list' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the teams administration page.
     *
     * Delegates rendering to the teams list display handler.
     *
     * @return void
     */
    public function display_teams_page(): void {
        $this->display_teams_list();
    }

    /**
     * Displays the contact administration page.
     *
     * Applies any pending screen flash messages, resolves the contact controller,
     * processes the current request data within the cup administration context,
     * and renders the contact page view.
     *
     * @return void
     */
    public function display_contact_page(): void {
        $this->screens->apply_flash();

        /** @var Contact_Admin_Controller $controller */
        $controller = $this->container->get( 'contact_admin_controller' );
        $this->complete_request( $controller->contact_page( $_GET, $_POST, Admin_Page_Context::cup() ), Contact_Page_View_Model::class, 'admin/includes/contact' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the competition seasons administration page.
     *
     * Applies any pending screen flash messages, resolves the competition seasons
     * controller, processes the current request data, and renders the seasons page view.
     *
     * @return void
     */
    public function display_seasons_page(): void {
        $this->screens->apply_flash();

        /** @var Competition_Seasons_Admin_Controller $controller */
        $controller = $this->container->get( 'competition_seasons_admin_controller' );
        $this->complete_request( $controller->seasons_page( $_GET, $_POST ), Competition_Seasons_Page_View_Model::class, 'admin/includes/show-seasons' ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
    }

    /**
     * Displays the competition modification administration page.
     *
     * Resolves the competition admin screen service and delegates rendering of the
     * modify screen to its display handler.
     *
     * @return void
     */
    public function display_modify_page(): void {
        $this->container->get( 'admin_competition_screen' )->handle_display( 'modify' );
    }

    /**
     * Displays the competition configuration administration page.
     *
     * Resolves the competition administration screen service and delegates rendering
     * of the configuration screen to it.
     *
     * @return void
     */
    public function display_config_page(): void {
        $this->container->get( 'admin_competition_screen' )->handle_display( 'config' );
    }

    /**
     * Displays the team administration page.
     *
     * Resolves the admin club screen service and delegates rendering for the team view.
     *
     * @return void
     */
    public function display_team_page(): void {
        $this->container->get( 'admin_club_screen' )->handle_display( 'team' );
    }

    /**
     * Displays the event configuration administration page.
     *
     * Resolves the admin event screen service from the container and delegates
     * rendering of the configuration page to it.
     *
     * @return void
     */
    public function display_event_config_page(): void {
        $this->container->get( 'admin_event_screen' )->display_config_page();
    }
}
