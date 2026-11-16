<?php
declare( strict_types=1 );
/**
 * Admin page router
 *
 * @package RacketManager
 * @subpackage Admin
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\View_Models\Documentation_Page_View_Model;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;

/**
 * Class Admin_Page_Router
 *
 * Coordinates top-level WordPress admin menu page routing, version verification,
 * and documentation presentation.
 */
final readonly class Admin_Page_Router {

    /**
     * @param Simple_Container       $container        Dependency injection container.
     * @param RacketManager          $racketmanager    Main plugin application instance.
     * @param Admin_Screen_Presenter $screen_presenter Screen presenter for GET rendering.
     */
    public function __construct(
        private Simple_Container $container,
        private RacketManager $racketmanager,
        private Admin_Screen_Presenter $screen_presenter,
    ) {
    }

    /**
     * Dispatch the current admin page request to its corresponding screen bridge or documentation view.
     */
    public function display(): void {
        $options = $this->racketmanager->options ?? array();

        // Update Plugin Version.
        if ( ! isset( $options['version'] ) || RACKETMANAGER_VERSION !== $options['version'] ) {
            flush_rewrite_rules();
            $options['version'] = RACKETMANAGER_VERSION;
            $this->racketmanager->update_plugin_options( $options );
        }

        // Update database.
        if ( ! isset( $options['dbversion'] ) || RACKETMANAGER_DBVERSION !== $options['dbversion'] ) {
            $admin_upgrade = $this->container->get( Admin_Upgrade::class );
            $admin_upgrade->handle_display();
            return;
        }

        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

        switch ( $page ) {
            case 'racketmanager-documentation':
            case 'racketmanager-doc':
                $this->screen_presenter->present(
                    array( 'view_model' => new Documentation_Page_View_Model() ),
                    Documentation_Page_View_Model::class,
                    'admin/documentation'
                );
                break;
            case 'racketmanager-leagues':
                $admin_league = $this->container->get( Admin_League::class );
                $admin_league->handle_display( $view );
                break;
            case 'racketmanager-cups':
                $admin_cup = $this->container->get( Admin_Cup::class );
                $admin_cup->handle_display( $view );
                break;
            case 'racketmanager-tournaments':
                $admin_tournament = $this->container->get( Admin_Tournament::class );
                $admin_tournament->handle_display( $view );
                break;
            case 'racketmanager-clubs':
                $admin_club = $this->container->get( Admin_Club::class );
                $admin_club->handle_display( $view );
                break;
            case 'racketmanager-results':
                $admin_results = $this->container->get( Admin_Result::class );
                $admin_results->handle_display( $view );
                break;
            case 'racketmanager-seasons':
                $admin_seasons = $this->container->get( Admin_Season::class );
                $admin_seasons->handle_display( $view );
                break;
            case 'racketmanager-players':
                $admin_players = $this->container->get( Admin_Player::class );
                $admin_players->handle_display( $view );
                break;
            case 'racketmanager-finances':
                $admin_finances = $this->container->get( Admin_Finances::class );
                $admin_finances->handle_display( $view );
                break;
            case 'racketmanager-settings':
                $admin_options = $this->container->get( Admin_Options::class );
                $admin_options->handle_display( $view );
                break;
            case 'racketmanager-import':
                $admin_import = $this->container->get( Admin_Import::class );
                $admin_import->handle_display( $view );
                break;
            case 'racketmanager':
            default:
                $admin_index = $this->container->get( Admin_Index::class );
                $admin_index->display_index_page();
                break;
        }
    }
}
