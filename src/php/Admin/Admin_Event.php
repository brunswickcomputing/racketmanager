<?php
/**
 * RacketManager-Admin API: RacketManager-admin-event class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Event
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Screen_Presenter;
use Racketmanager\Services\Admin\Event\Event_Action_Dispatcher;
use Racketmanager\Services\Validator\Validator_Config;
use stdClass;

/**
 * RacketManager administration functions
 * Class to implement RacketManager Administration Event panel
 */
final class Admin_Event extends Admin_Display {

    private Admin_Action_Adapter $actions;
    private Admin_Screen_Presenter $screens;
    private Event_Action_Dispatcher $event_action_dispatcher;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $actions = null,
        ?Admin_Screen_Presenter $screens = null,
        ?Event_Action_Dispatcher $event_action_dispatcher = null,
    ) {
        parent::__construct( $plugin_instance );
        $c                             = $this->racketmanager->container;
        $this->actions                 = $actions ?? $c->get( 'admin_action_adapter' );
        $this->screens                 = $screens ?? $c->get( 'admin_screen_presenter' );
        $this->event_action_dispatcher = $event_action_dispatcher ?? $c->get( 'event_action_dispatcher' );
        $this->admin_action_adapter    = $this->actions;
    }

    /**
     * Handle config page function
     *
     * @return void
     */
    public function display_config_page(): void {
        $flash      = $this->screens->apply_flash();
        $flash_data = $flash['flash_data'] ?? array();
        $errors     = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();

        if ( isset( $_POST['addEventConfig'] ) || isset( $_POST['updateEventConfig'] ) ) {
            $result = $this->event_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_event_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator_Config();
        $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->err_msgs[0], true );
            $this->show_message();
            return;
        }

        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }

        $tournament    = null;
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        if ( $tournament_id ) {
            try {
                $tournament = $this->tournament_service->get_tournament( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
        }

        $event_id = isset( $_GET['event_id'] ) ? intval( $_GET['event_id'] ) : null;
        try {
            $event     = $this->competition_service->get_event_by_id( $event_id );
            $new_event = false;
        } catch ( Event_Not_Found_Exception ) {
            $event     = new stdClass();
            $new_event = true;
        }

        if ( ! $new_event ) {
            $event->config              = (object) $event->settings;
            $event->config->num_sets    = $event->num_sets;
            $event->config->num_rubbers = $event->num_rubbers;
        }

        $form_data = $flash_data['form_data'] ?? array();
        if ( ! empty( $form_data ) ) {
            if ( $new_event ) {
                $event->name   = isset( $form_data['event_name'] ) ? sanitize_text_field( wp_unslash( strval( $form_data['event_name'] ) ) ) : null;
                $event->type   = isset( $form_data['type'] ) ? sanitize_text_field( wp_unslash( strval( $form_data['type'] ) ) ) : null;
                $event->config = (object) array(
                    'age_limit'          => isset( $form_data['age_limit'] ) ? sanitize_text_field( wp_unslash( strval( $form_data['age_limit'] ) ) ) : null,
                    'age_offset'         => isset( $form_data['age_offset'] ) ? sanitize_text_field( wp_unslash( strval( $form_data['age_offset'] ) ) ) : null,
                    'scoring'            => isset( $form_data['scoring'] ) ? sanitize_text_field( wp_unslash( strval( $form_data['scoring'] ) ) ) : null,
                    'num_sets'           => isset( $form_data['num_sets'] ) ? intval( $form_data['num_sets'] ) : null,
                    'num_rubbers'        => isset( $form_data['num_rubbers'] ) ? intval( $form_data['num_rubbers'] ) : null,
                    'reverse_rubbers'    => isset( $form_data['reverse_rubbers'] ) && 'true' === $form_data['reverse_rubbers'],
                    'match_days_allowed' => isset( $form_data['match_days_allowed'] ) ? wp_unslash( $form_data['match_days_allowed'] ) : null,
                    'offset'             => isset( $form_data['offset'] ) ? intval( $form_data['offset'] ) : null,
                    'primary_league'     => isset( $form_data['primary_league'] ) ? intval( $form_data['primary_league'] ) : null,
                );
            } else {
                if ( isset( $form_data['event_name'] ) ) {
                    $event->name = sanitize_text_field( wp_unslash( strval( $form_data['event_name'] ) ) );
                }
                if ( isset( $form_data['type'] ) ) {
                    $event->type = sanitize_text_field( wp_unslash( strval( $form_data['type'] ) ) );
                }
                if ( isset( $event->config ) ) {
                    if ( isset( $form_data['age_limit'] ) ) {
                        $event->config->age_limit = sanitize_text_field( wp_unslash( strval( $form_data['age_limit'] ) ) );
                    }
                    if ( isset( $form_data['age_offset'] ) ) {
                        $event->config->age_offset = sanitize_text_field( wp_unslash( strval( $form_data['age_offset'] ) ) );
                    }
                    if ( isset( $form_data['scoring'] ) ) {
                        $event->config->scoring = sanitize_text_field( wp_unslash( strval( $form_data['scoring'] ) ) );
                    }
                    if ( isset( $form_data['num_sets'] ) ) {
                        $event->config->num_sets = intval( $form_data['num_sets'] );
                    }
                    if ( isset( $form_data['num_rubbers'] ) ) {
                        $event->config->num_rubbers = intval( $form_data['num_rubbers'] );
                    }
                    if ( isset( $form_data['reverse_rubbers'] ) ) {
                        $event->config->reverse_rubbers = intval( $form_data['reverse_rubbers'] );
                    }
                    if ( isset( $form_data['match_days_allowed'] ) ) {
                        $event->config->match_days_allowed = $form_data['match_days_allowed'];
                    }
                    if ( isset( $form_data['offset'] ) ) {
                        $event->config->offset = intval( $form_data['offset'] );
                    }
                    if ( isset( $form_data['primary_league'] ) ) {
                        $event->config->primary_league = intval( $form_data['primary_league'] );
                    }
                }
            }
        }

        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['tab'] ) ) ) : 'general';

        $raw_leagues = ( ! $new_event && ( $competition->is_championship ?? false ) && isset( $event ) && method_exists( $event, 'get_leagues' ) ) ? $event->get_leagues() : array();
        $leagues     = is_array( $raw_leagues ) ? $raw_leagues : array();

        $vm = new Tournament_Event_Config_Page_View_Model(
            competition: $competition,
            event: $event,
            tournament: $tournament,
            new_event: $new_event,
            errors: $errors,
            tab: $tab,
            leagues: $leagues,
        );

        $this->screens->present(
            array( 'view_model' => $vm ),
            Tournament_Event_Config_Page_View_Model::class,
            'admin/includes/event-config'
        );
    }
}
