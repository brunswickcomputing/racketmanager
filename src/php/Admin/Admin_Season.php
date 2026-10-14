<?php
/**
 * RacketManager-Admin API: RacketManager-season class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Season
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\View_Models\Seasons_Page_View_Model;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Validator\Validator;

/**
 * RacketManager Season Admin functions
 * Class to implement RacketManager Admin Season
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Season
 */
class Admin_Season extends Admin_Display {

    private Admin_Action_Adapter $actions;
    private Admin_Screen_Presenter $screens;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Season_Action_Dispatcher $dispatcher = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                                    = $this->racketmanager->container;
        $this->actions                        = $actions ?? $c->get( 'admin_action_adapter' );
        $this->screens                        = $screens ?? $c->get( 'admin_screen_presenter' );
        $this->season_admin_action_dispatcher = $dispatcher ?? $c->get( 'season_action_dispatcher' );
        $this->admin_action_adapter           = $this->actions;
    }

    /**
     * Function to handle administration season displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $this->display_seasons_page();
    }

    /**
     * Display seasons page
     */
    public function display_seasons_page(): void {
        $flash       = $this->screens->apply_flash();
        $flash_data  = $flash['flash_data'] ?? array();
        $errors      = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();
        $season_name = isset( $flash_data['season_name'] ) ? strval( $flash_data['season_name'] ) : null;

        if ( isset( $_POST['addSeason'] ) || isset( $_POST['doSeasonDel'] ) ) {
            $result = $this->season_admin_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_season_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator();
        $validator = $validator->capability( 'edit_seasons' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        $seasons = $this->season_service->get_all_seasons();
        $vm      = new Seasons_Page_View_Model(
            seasons: $seasons,
            errors: $errors,
            season_name: $season_name,
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Seasons_Page_View_Model::class,
            'admin/show-seasons'
        );
    }
}
