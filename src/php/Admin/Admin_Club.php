<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-admin-club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Club
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Club_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Players_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Roles_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Team_Admin_Controller;
use Racketmanager\Admin\Controllers\Club_Teams_Admin_Controller;
use Racketmanager\Admin\Controllers\Clubs_Admin_Controller;
use Racketmanager\Admin\View_Models\Club_Page_View_Model;
use Racketmanager\Admin\View_Models\Club_Players_Page_View_Model;
use Racketmanager\Admin\View_Models\Club_Roles_Page_View_Model;
use Racketmanager\Admin\View_Models\Club_Teams_Page_View_Model;
use Racketmanager\Admin\View_Models\Clubs_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Team_Page_View_Model;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Container\Simple_Container;

final readonly class Admin_Club {
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

    public function handle_display( ?string $view ): void {
        $view_map = array(
            'clubs'   => array( $this, 'display_clubs_page' ),
            'club'    => array( $this, 'display_club_page' ),
            'players' => array( $this, 'display_club_players_page' ),
            'teams'   => array( $this, 'display_teams_page' ),
            'team'    => array( $this, 'display_team_page' ),
            'roles'   => array( $this, 'display_roles_page' ),
            'player'  => array( $this, 'display_player_page' ),
        );

        try {
            $callback = $view_map[ $view ] ?? array( $this, 'display_clubs_page' );
            $callback();
        } catch ( Club_Not_Found_Exception | Tournament_Not_Found_Exception | Invalid_Status_Exception | Player_Not_Found_Exception $e ) {
            $this->message_service->set_message( $e->getMessage(), true );
            $this->message_service->show_message();
        }
    }

    public function display_clubs_page(): void {
        $this->screens->apply_flash();

        /** @var Clubs_Admin_Controller $controller */
        $controller = $this->container->get( 'clubs_admin_controller' );

        $this->complete_request(
            $controller->clubs_page( $_GET, $_POST ),
            Clubs_Page_View_Model::class,
            'admin/show-clubs'
        );
    }

    public function display_club_page(): void {
        $this->screens->apply_flash();

        /** @var Club_Admin_Controller $controller */
        $controller = $this->container->get( 'club_admin_controller' );
        $this->complete_request( $controller->club_page( $_GET, $_POST ), Club_Page_View_Model::class, 'admin/includes/club' );
    }

    public function display_club_players_page(): void {
        $this->screens->apply_flash();

        /** @var Club_Players_Admin_Controller $controller */
        $controller = $this->container->get( 'club_players_admin_controller' );
        $this->complete_request( $controller->club_players_page( $_GET, $_POST ), Club_Players_Page_View_Model::class, 'admin/club/show-club-players' );
    }

    public function display_teams_page(): void {
        $this->screens->apply_flash();

        /** @var Club_Teams_Admin_Controller $controller */
        $controller = $this->container->get( 'club_teams_admin_controller' );
        $this->complete_request( $controller->club_teams_page( $_GET, $_POST ), Club_Teams_Page_View_Model::class, 'admin/club/show-teams' );
    }

    public function display_team_page(): void {
        $this->screens->apply_flash();

        /** @var Club_Team_Admin_Controller $controller */
        $controller = $this->container->get( 'club_team_admin_controller' );

        $this->complete_request(
            $controller->team_page( $_GET, $_POST ),
            Tournament_Team_Page_View_Model::class,
            static fn( object $vm ): string => 'admin/includes/teams/' . preg_replace( '/\.php$/', '', $vm->file ),
            strict: false
        );
    }

    public function display_roles_page(): void {
        $this->screens->apply_flash();

        /** @var Club_Roles_Admin_Controller $controller */
        $controller = $this->container->get( 'club_roles_admin_controller' );
        $this->complete_request( $controller->club_roles_page( $_GET, $_POST ), Club_Roles_Page_View_Model::class, 'admin/club/show-roles' );
    }

    public function display_player_page(): void {
        /** @var Admin_Player $admin_player */
        $admin_player = $this->container->get( 'admin_player_screen' );

        $admin_player->handle_display( 'player' );
    }
}
