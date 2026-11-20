<?php
declare( strict_types=1 );
/**
 * Tournament event config admin controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Exception;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Event\Event_Action_Dispatcher;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Tournament_Service;
use stdClass;

/**
 * Controller coordinating tournament and event configuration screen requests.
 */
final readonly class Tournament_Event_Config_Admin_Controller {

    /**
     * Constructor.
     *
     * @param Tournament_Service         $tournament_service      Tournament service.
     * @param Competition_Service        $competition_service     Competition service.
     * @param Action_Guard_Interface     $action_guard            Security guard.
     * @param Event_Repository_Interface $event_repository        Event repository.
     * @param Event_Action_Dispatcher    $event_action_dispatcher Event action dispatcher.
     */
    public function __construct(
        private Tournament_Service $tournament_service,
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
        private Event_Repository_Interface $event_repository,
        private Event_Action_Dispatcher $event_action_dispatcher,
    ) {
    }

    /**
     * Handle tournament event configuration screen requests.
     *
     * @param array<string, mixed> $query      Typically $_GET.
     * @param array<string, mixed> $post       Typically $_POST.
     * @param array<string, mixed> $flash_data Restored validation errors and form values.
     *
     * @return array{view_model?: Tournament_Event_Config_Page_View_Model, message?: string|null, message_type?: bool|string|null, redirect?: string, flash_data?: array<string, mixed>}
     */
    public function handle( array $query = array(), array $post = array(), array $flash_data = array() ): array {
        // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
        $query = ! empty( $query ) ? $query : $_GET;
        $post  = ! empty( $post ) ? $post : $_POST;
        // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

        if ( isset( $post['addEventConfig'] ) || isset( $post['updateEventConfig'] ) ) {
            return $this->handle_action( $query, $post );
        }

        return $this->render_screen( $query, $flash_data );
    }

    /**
     * Handle an event configuration action and build the redirect response data.
     *
     * @param array $query Query parameters for the current request.
     * @param array $post Posted form data for the current request.
     *
     * @return array Redirect URL, message details, and flash data for the handled action.
     */
    private function handle_action( array $query, array $post ): array {
        $result     = $this->event_action_dispatcher->handle( $query, $post );
        $extra_args = array_merge( array( 'view' => 'event-config' ), $result->query_params );

        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        } elseif ( ! isset( $query['tab'] ) && isset( $post['active-tab'] ) && is_string( $post['active-tab'] ) ) {
            $extra_args['tab'] = sanitize_key( wp_unslash( $post['active-tab'] ) );
        }

        return array(
            'redirect'     => Admin_Redirect_Url_Builder::event_screen( $query, $extra_args ),
            'message'      => $result->message,
            'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
            'flash_data'   => $result->flash_data,
        );
    }

    /**
     * Render tournament event configuration screen.
     *
     * @param array<string, mixed> $query      Query parameters.
     * @param array<string, mixed> $flash_data Restored session validation errors and form data.
     *
     * @return array{view_model?: Tournament_Event_Config_Page_View_Model, message?: string, message_type?: bool}
     */
    private function render_screen( array $query, array $flash_data ): array {
        try {
            $this->action_guard->assert_capability( 'edit_leagues' );

            $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
            $competition    = $this->competition_service->get_by_id( $competition_id );

            $tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
            $tournament    = $tournament_id ? $this->tournament_service->get_tournament( $tournament_id ) : null;

            $event_id              = isset( $query['event_id'] ) ? intval( $query['event_id'] ) : null;
            [ $event, $new_event ] = $this->resolve_event( $event_id );

            $errors = new Error_Bag( is_array( $flash_data['errors'] ?? null ) ? $flash_data['errors'] : array() );

            if ( is_array( $flash_data['form_data'] ?? null ) ) {
                $this->apply_flash_form_data( $event, $flash_data['form_data'], $new_event );
            }

            if ( ! $new_event ) {
                $this->sync_event_config_rubbers_and_sets( $event );
            }

            $tab = isset( $query['tab'] ) && is_string( $query['tab'] )
                ? sanitize_key( wp_unslash( $query['tab'] ) )
                : 'general';

            return array(
                'view_model' => new Tournament_Event_Config_Page_View_Model(
                    $competition,
                    $event,
                    $tournament,
                    $new_event,
                    errors: $errors,
                    tab: $tab,
                    leagues: $this->get_event_leagues( $competition, $event, $new_event )
                ),
            );
        } catch ( Exception $e ) {
            return $this->error_response( $e->getMessage() );
        }
    }

    /**
     * Build standard error response array.
     *
     * @param string $message Error message.
     *
     * @return array{message: string, message_type: true}
     */
    private function error_response( string $message ): array {
        return array(
            'message'      => $message,
            'message_type' => true,
        );
    }

    /**
     * Resolve an event by ID or return an empty event placeholder when no valid event is found.
     *
     * @param int|null $event_id Event ID to resolve, or null to create a new placeholder.
     *
     * @return array Tuple containing the resolved event object and a boolean indicating whether it is new.
     */
    private function resolve_event( ?int $event_id ): array {
        if ( $event_id ) {
            try {
                return array( $this->competition_service->get_event_by_id( $event_id ), false );
            } catch ( Event_Not_Found_Exception ) {
                return array( new stdClass(), true );
            }
        }

        return array( new stdClass(), true );
    }

    /**
     * Apply submitted flash form data to the event configuration and scalar event properties.
     *
     * @param object $event Event object to update.
     * @param array  $form_data Submitted form data keyed by field name.
     * @param bool   $new_event Whether the event is being created.
     *
     * @return void
     */
    private function apply_flash_form_data( object $event, array $form_data, bool $new_event ): void {
        $config = $new_event ? new stdClass() : ( $event->config ?? (object) $event->settings );
        foreach ( $form_data as $field => $value ) {
            $config->{ 'event_name' === $field ? 'name' : $field } = $value;
        }
        $this->set_event_config( $event, $config );

        $scalar_fields = array(
            'event_name'  => 'name',
            'type'        => 'type',
            'num_sets'    => 'num_sets',
            'num_rubbers' => 'num_rubbers',
        );

        foreach ( $scalar_fields as $form_key => $target_property ) {
            if ( isset( $form_data[ $form_key ] ) ) {
                $this->set_event_property( $event, $target_property, $form_data[ $form_key ] );
            }
        }
    }

    /**
     * Set a supported event property on an Event instance or generic event object.
     *
     * @param object $event Event object to update.
     * @param string $property Property name to set.
     * @param mixed  $value Value to assign to the property.
     *
     * @return void
     */
    private function set_event_property( object $event, string $property, mixed $value ): void {
        if ( $event instanceof Event ) {
            match ( $property ) {
                'name'        => $event->set_name( strval( $value ) ),
                'type'        => $event->set_type( strval( $value ) ),
                'num_sets'    => $event->set_num_sets( intval( $value ) ),
                'num_rubbers' => $event->set_num_rubbers( intval( $value ) ),
                default       => null,
            };
            return;
        }

        match ( $property ) {
            'name', 'type'            => $event->{$property} = strval( $value ),
            'num_sets', 'num_rubbers' => $event->{$property} = intval( $value ),
            default                   => null,
        };
    }

    /**
     * Set the configuration object on an event, using the Event setter when available.
     *
     * @param object $event Event instance or event-like object to receive the configuration.
     * @param object $config Configuration object to assign to the event.
     *
     * @return void
     */
    private function set_event_config( object $event, object $config ): void {
        if ( $event instanceof Event ) {
            $event->set_config_object( $config );
        } else {
            $event->config = $config;
        }
    }

    /**
     * Synchronises the event configuration with the event's rubber and set counts.
     *
     * @param object $event Event object containing configuration, settings, num_sets, and num_rubbers values.
     *
     * @return void
     */
    private function sync_event_config_rubbers_and_sets( object $event ): void {
        $cfg              = $event->config ?? (object) $event->settings;
        $cfg->num_sets    = $event->num_sets;
        $cfg->num_rubbers = $event->num_rubbers;
        $this->set_event_config( $event, $cfg );
    }

    /**
     * Resolve leagues for event config view model.
     *
     * @param object $competition Current competition.
     * @param object $event       Current event.
     * @param bool   $new_event   Whether creating a new event.
     *
     * @return array<int, object>
     */
    private function get_event_leagues( object $competition, object $event, bool $new_event ): array {
        if ( $new_event || empty( $event->id ) || empty( $competition->is_championship ) ) {
            return array();
        }

        $raw_leagues = $this->event_repository->query_leagues( (int) $event->id );
        return is_array( $raw_leagues ) ? $raw_leagues : array();
    }
}
