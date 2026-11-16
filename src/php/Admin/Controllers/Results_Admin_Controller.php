<?php
declare( strict_types=1 );
/**
 * Results Admin Controller
 *
 * Coordinates results administrative screen display and mutations.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Results_Page_View_Model;
use Racketmanager\Application\Admin\Ports\Action_Guard_Interface;

/**
 * Class Results_Admin_Controller
 *
 * Coordinates tab handlers for Results Checker and match fixture tabs.
 */
final readonly class Results_Admin_Controller {

    /**
     * @param Results_Checker_Tab_Handler  $checker_tab_handler  Handler for results checker tab.
     * @param Results_Fixtures_Tab_Handler $fixtures_tab_handler Handler for fixture match tabs.
     * @param Action_Guard_Interface       $action_guard         Security guard service.
     */
    public function __construct(
        private Results_Checker_Tab_Handler $checker_tab_handler,
        private Results_Fixtures_Tab_Handler $fixtures_tab_handler,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle results screen request (GET view model or POST mutation PRG redirect).
     *
     * @param array<string, mixed> $query      Typically $_GET.
     * @param array<string, mixed> $post       Typically $_POST.
     * @param array<string, mixed> $flash_data Rehydrated flash data.
     *
     * @return array{view_model?: Results_Page_View_Model, redirect?: string, message?: string|null, message_type?: bool|string|null, flash_data?: array<string, mixed>}
     */
    public function results_page( array $query, array $post, array $flash_data = array() ): array {
        unset( $flash_data );

        $this->action_guard->assert_capability( 'view_leagues' );

        if ( isset( $post['doResultsChecker'] ) ) {
            $result = $this->checker_tab_handler->handle_mutation( $post );

            $extra_args = array( 'tab' => 'resultschecker' );
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );

            return array(
                'redirect'     => Admin_Redirect_Url_Builder::results_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $tab = isset( $query['tab'] ) ? sanitize_text_field( wp_unslash( strval( $query['tab'] ) ) ) : 'resultschecker';

        $checker_data  = $this->checker_tab_handler->get_tab_data( $query );
        $fixtures_data = $this->fixtures_tab_handler->get_tab_data();

        return array(
            'view_model' => new Results_Page_View_Model(
                tab: $tab,
                pending_results_matches: $fixtures_data['pending_results_matches'],
                awaiting_confirmation_matches: $fixtures_data['awaiting_confirmation_matches'],
                challenged_results_matches: $fixtures_data['challenged_results_matches'],
                approved_results_matches: $fixtures_data['approved_results_matches'],
                postponed_matches: $fixtures_data['postponed_matches'],
                tournament_results_matches: $fixtures_data['tournament_results_matches'],
                season_select: $checker_data['season_select'],
                competition_select: $checker_data['competition_select'],
                event_select: $checker_data['event_select'],
                results_check_filter: $checker_data['results_check_filter'],
                results_checkers: $checker_data['results_checkers'],
                competitions: $checker_data['competitions'],
                events: $checker_data['events'],
                seasons: $checker_data['seasons'],
            ),
        );
    }
}
