<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-admin-tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Tournament
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Admin_Page_Context;
use Racketmanager\Admin\Controllers\Tournament_Competitions_Admin_Controller;
use Racketmanager\Admin\View_Models\Competitions_Page_View_Model;
use Racketmanager\Admin\View_Models\Contact_Page_View_Model;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Information_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Fixtures_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Setup_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Admin\View_Models\Teams_List_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Tournaments_Page_View_Model;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Application\Ports\View_Renderer_Interface;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final readonly class Admin_Tournament {
    use Completes_Admin_Requests;

    /**
     * Initialise the admin controller with required services and collaborators.
     *
     * @param RacketManager           $racketmanager Main plugin/service container access object.
     * @param View_Renderer_Interface $renderer View renderer used to generate admin output.
     * @param Admin_Message_Service   $message_service Service for handling admin messages.
     * @param Admin_Action_Adapter    $actions Adapter for executing admin actions.
     * @param Admin_Screen_Presenter  $screens Presenter for preparing admin screen data.
     *
     * @return void
     */
    public function __construct(
        private RacketManager $racketmanager,
        View_Renderer_Interface $renderer,
        private Admin_Message_Service $message_service,
        private Admin_Action_Adapter $actions,
        private Admin_Screen_Presenter $screens
    ) {
    }

    /**
     * Handle display routing for the requested tournament admin view.
     *
     * @param string|null $view Requested view key used to select the page display callback.
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = [
            'tournament'   => [ $this, 'display_tournament_overview_page' ],
            'modify'       => [ $this, 'display_tournament_page' ],
            'competitions' => [ $this, 'display_competitions_page' ],
            'plan'         => [ $this, 'display_plan_page' ],
            'draw'         => [ $this, 'display_draw_page' ],
            'setup'        => [ $this, 'display_setup_page' ],
            'setup-event'  => [ $this, 'display_setup_event_page' ],
            'fixtures'     => [ $this, 'display_fixtures_page' ],
            'fixture'      => [ $this, 'display_fixtures_page' ],
            'teams'        => [ $this, 'display_teams_list' ],
            'contact'      => [ $this, 'display_contact_page' ],
            'information'  => [ $this, 'display_information_page' ],
            // Views handled by new dedicated controllers.
            'config'       => [ $this, 'display_competition_config_page' ],
            'event-config' => [ $this, 'display_event_config_page' ],
            'team'         => [ $this, 'display_team_page' ],
        ];

        try {
            $callback = $view_map[ $view ] ?? [ $this, 'display_tournaments_page' ];
            $callback();
        } catch ( Tournament_Not_Found_Exception | Invalid_Status_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }

    /**
     * Display the teams list page for the tournament admin context.
     *
     * @return void
     */
    public function display_teams_list(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'teams_admin_controller' );

        $this->complete_request(
            $controller->teams_page( $_GET, $_POST, Admin_Page_Context::tournament() ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Teams_List_Page_View_Model::class,
            'admin/includes/teams-list'
        );
    }

    /**
     * Display the competition configuration page and apply any queued flash messages.
     *
     * @return void
     */
    public function display_competition_config_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_competition_config_admin_controller' );

        $this->complete_request(
            $controller->handle(),
            Tournament_Competition_Config_Page_View_Model::class,
            'admin/includes/competition-config',
            strict: false
        );
    }

    /**
     * Display event config page.
     *
     * @return void
     */
    public function display_event_config_page(): void {
        $this->racketmanager->container->get( Admin_Event::class )->display_config_page();
    }

    /**
     * Display team page.
     *
     * @return void
     */
    public function display_team_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_team_admin_controller' );

        $this->complete_request(
            $controller->handle(),
            Tournament_Team_Page_View_Model::class,
            static fn( object $vm ): string => 'admin/includes/teams/' . preg_replace( '/\.php$/', '', $vm->file ),
            strict: false
        );
    }

    /**
     * Display tournaments page (read-only list: no flash, no PRG redirect).
     */
    public function display_tournaments_page(): void {
        $controller = $this->racketmanager->container->get( 'tournament_tournaments_admin_controller' );

        $this->complete_request(
            $controller->tournaments_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Tournaments_Page_View_Model::class,
            'admin/show-tournaments',
            redirect: false
        );
    }

    /**
     * Display tournament competitions page (add and manage tournament competitions).
     */
    public function display_competitions_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = is_array( $flash['flash_data'] ?? null ) ? $flash['flash_data'] : array();

        /** @var Tournament_Competitions_Admin_Controller $controller */
        $controller = $this->racketmanager->container->get( Tournament_Competitions_Admin_Controller::class );

        $this->complete_request(
            $controller->competitions_page( $_GET, $_POST, $flash_data ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Competitions_Page_View_Model::class,
            'admin/show-competitions'
        );
    }

    /**
     * Display tournament overview (no PRG redirect).
     */
    public function display_tournament_overview_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_overview_admin_controller' );

        $this->complete_request(
            $controller->overview_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Overview_Page_View_Model::class,
            'admin/tournament/show-tournament',
            redirect: false
        );
    }

    /**
     * Display tournament draw
     */
    public function display_draw_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_draw_admin_controller' );

        $this->complete_request(
            $controller->draw_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Draw_Page_View_Model::class,
            'admin/tournament/draw',
            post_only: true
        );
    }

    /**
     * Display tournament setup
     */
    public function display_setup_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_setup_admin_controller' );

        $this->complete_request(
            $controller->setup_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Setup_Page_View_Model::class,
            'admin/tournament/setup',
            post_only: true
        );
    }

    /**
     * Display tournament setup event
     */
    public function display_setup_event_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_setup_event_admin_controller' );

        $this->complete_request(
            $controller->setup_event_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Setup_Page_View_Model::class,
            'admin/tournament/setup-event',
            post_only: true
        );
    }

    /**
     * Display tournament page
     */
    public function display_tournament_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_admin_controller' );

        $this->complete_request(
            $controller->modify_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Modify_Page_View_Model::class,
            'admin/tournament-edit'
        );
    }

    /**
     * Display tournament plan page
     */
    public function display_plan_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_plan_admin_controller' );

        $this->complete_request(
            $controller->plan_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Plan_Page_View_Model::class,
            'admin/tournament/plan'
        );
    }

    /**
     * Display tournament fixtures or single fixture page
     */
    public function display_fixtures_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_fixtures_admin_controller' );

        $this->complete_request(
            $controller->fixtures_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Fixtures_Page_View_Model::class,
            'admin/includes/match'
        );
    }

    /**
     * Display tournament contact page
     */
    public function display_contact_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'contact_admin_controller' );

        $this->complete_request(
            $controller->contact_page( $_GET, $_POST, Admin_Page_Context::tournament() ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Contact_Page_View_Model::class,
            'admin/includes/contact'
        );
    }

    /**
     * Display tournament information page
     */
    public function display_information_page(): void {
        $this->screens->apply_flash();

        $controller = $this->racketmanager->container->get( 'tournament_information_admin_controller' );

        $this->complete_request(
            $controller->information_page( $_GET, $_POST ), // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
            Tournament_Information_Page_View_Model::class,
            'admin/tournament/information'
        );
    }
}
