<?php
declare( strict_types=1 );
/**
 * Tournament event config admin controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Tournament_Event_Config_Page_View_Model;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Services\Admin\Event\Event_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
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
			$result = $this->event_action_dispatcher->handle( $query, $post );

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

		try {
			$this->action_guard->assert_capability( 'edit_leagues' );
		} catch ( \Exception $e ) {
			return array(
				'message'      => $e->getMessage(),
				'message_type' => true,
			);
		}

		$competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
		try {
			$competition = $this->competition_service->get_by_id( $competition_id );
		} catch ( Competition_Not_Found_Exception $e ) {
			return array(
				'message'      => $e->getMessage(),
				'message_type' => true,
			);
		}

		$tournament_id = isset( $query['tournament'] ) ? intval( $query['tournament'] ) : null;
		$tournament    = null;
		if ( $tournament_id ) {
			try {
				$tournament = $this->tournament_service->get_tournament( $tournament_id );
			} catch ( Tournament_Not_Found_Exception $e ) {
				return array(
					'message'      => $e->getMessage(),
					'message_type' => true,
				);
			}
		}

		$event_id = isset( $query['event_id'] ) ? intval( $query['event_id'] ) : null;
		if ( $event_id ) {
			try {
				$event     = $this->competition_service->get_event_by_id( $event_id );
				$new_event = false;
			} catch ( Event_Not_Found_Exception ) {
				$event     = new stdClass();
				$new_event = true;
			}
		} else {
			$event     = new stdClass();
			$new_event = true;
		}

		$errors = is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();
		if ( is_array( $flash_data['form_data'] ?? null ) ) {
			$form_data = $flash_data['form_data'];
			$config    = $new_event ? new stdClass() : $event->config ?? (object) $event->settings;
			foreach ( $form_data as $field => $value ) {
				$config->{ 'event_name' === $field ? 'name' : $field } = $value;
			}
			$event->config = $config;
			if ( isset( $form_data['event_name'] ) ) {
				$event->name = strval( $form_data['event_name'] );
			}
			if ( isset( $form_data['type'] ) ) {
				$event->type = strval( $form_data['type'] );
			}
			if ( isset( $form_data['num_sets'] ) ) {
				$event->num_sets = intval( $form_data['num_sets'] );
			}
			if ( isset( $form_data['num_rubbers'] ) ) {
				$event->num_rubbers = intval( $form_data['num_rubbers'] );
			}
		}

		if ( ! $new_event ) {
			$event->config              = $event->config ?? (object) $event->settings;
			$event->config->num_sets    = $event->num_sets;
			$event->config->num_rubbers = $event->num_rubbers;
		}

		return array(
			'view_model' => new Tournament_Event_Config_Page_View_Model(
				$competition,
				$event,
				$tournament,
				$new_event,
				errors: $errors,
				tab: isset( $query['tab'] ) && is_string( $query['tab'] ) ? sanitize_key( wp_unslash( $query['tab'] ) ) : 'general',
				leagues: $this->get_event_leagues( $competition, $event, $new_event )
			),
		);
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
