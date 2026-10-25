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

use Racketmanager\Admin\Presenters\Admin_Competition_Page_Presenter;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
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
    private Admin_Competition_Page_Presenter $pages;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Competition_Action_Dispatcher $competition_action_dispatcher = null,
        ?Competition_Season_Action_Dispatcher $competition_season_action_dispatcher = null,
        ?Admin_Competition_Page_Presenter $pages = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                                          = $this->racketmanager->container;
        $this->actions                              = $actions ?? $c->get( 'admin_action_adapter' );
        $this->screens                              = $screens ?? $c->get( 'admin_screen_presenter' );
        $this->competition_action_dispatcher        = $competition_action_dispatcher ?? $c->get( 'competition_action_dispatcher' );
        $this->competition_season_action_dispatcher = $competition_season_action_dispatcher ?? $c->get( 'competition_season_action_dispatcher' );
        $this->pages                                = $pages
            ?? ( $c->has( 'admin_competition_page_presenter' )
                ? $c->get( 'admin_competition_page_presenter' )
                : new Admin_Competition_Page_Presenter( $this->competition_service, $this->club_service, $this->season_service, $this->finance_service ) );
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

        if ( isset( $_POST['updateCompetitionConfig'] ) || isset( $_POST['doActionEvent'] ) ) {
            $result = $this->competition_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_competition_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator_Config( $this->competition_service );
        $validator->capability( 'edit_leagues' );
        if ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $validator->competition( $competition_id );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->err_msgs[0] ?? $validator->msg, true );
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
        if ( ! $competition ) {
            $this->set_message( __( 'Competition not found', 'racketmanager' ), true );
            $this->show_message();
            return;
        }

        $tournament    = null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        if ( $tournament_id ) {
            try {
                $tournament = $this->tournament_service->get_tournament( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
                $this->show_message();
                return;
            }
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['tab'] ) ) ) : 'general';
        $vm = $this->pages->config( $competition, $tournament, $tab, $flash_data );

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

        if ( isset( $_POST['addSeason'] ) || isset( $_POST['editSeason'] ) ) {
            $result = $this->competition_season_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_competition_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $edit_mode = true;
        $validator = new Validator_Config( $this->competition_service );
        $validator = $validator->capability( 'edit_seasons' );
        if ( empty( $validator->error ) ) {
            $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
            $season         = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['season'] ) ) : null;
            $validator      = $validator->competition( $competition_id );
            if ( empty( $validator->error ) ) {
                try {
                    $competition = $this->competition_service->get_competition( $competition_id );
                    if ( null !== $season && '' !== $season ) {
                        $validator = $validator->season_set( $season, $competition->get_seasons() );
                        if ( ! empty( $validator->error ) ) {
                            $this->set_message( $validator->err_msgs[0] ?? $validator->msg, true );
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
                $this->set_message( $validator->err_msgs[0] ?? $validator->msg, true );
            }
        } else {
            $this->set_message( $validator->err_msgs[0] ?? $validator->msg, true );
        }

        if ( ! empty( $validator->error ) ) {
            $this->show_message();
            return;
        }

        if ( $edit_mode && ! $competition->get_season_by_name( $season ) ) {
            $this->set_message( __( 'Season not found for competition', 'racketmanager' ), true );
            $this->show_message();
            return;
        }

        if ( ! $edit_mode ) {
            $this->set_message( __( 'New season', 'racketmanager' ), 'info' );
        }

        $this->show_message();

        $vm = $this->pages->season( $competition, $season, $edit_mode, $flash_data );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Season_Modify_Page_View_Model::class,
            'admin/includes/season-edit'
        );
    }
}
