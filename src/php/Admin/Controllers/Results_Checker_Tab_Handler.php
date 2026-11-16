<?php
declare( strict_types=1 );
/**
 * Results Checker Tab Handler
 *
 * Handles results checker queries and bulk moderation mutations.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Results_Checker_Presenter;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;

/**
 * Class Results_Checker_Tab_Handler
 *
 * Coordinates data querying and mutations for the Results Checker tab.
 */
final readonly class Results_Checker_Tab_Handler {

    /**
     * @param Results_Checker_Repository_Interface        $results_checker_repository        Results checker repository.
     * @param Results_Checker_Presenter                   $results_checker_presenter         Results checker presenter.
     * @param Competition_Service                         $competition_service               Competition service.
     * @param Season_Service                              $season_service                    Season service.
     * @param Results_Checker_Action_Dispatcher_Interface $results_checker_action_dispatcher Dispatcher for results checker actions.
     */
    public function __construct(
        private Results_Checker_Repository_Interface $results_checker_repository,
        private Results_Checker_Presenter $results_checker_presenter,
        private Competition_Service $competition_service,
        private Season_Service $season_service,
        private Results_Checker_Action_Dispatcher_Interface $results_checker_action_dispatcher,
    ) {
    }

    /**
     * Delegate results checker POST mutations to the action dispatcher.
     *
     * @param array<string, mixed> $post Typically $_POST.
     *
     * @return Action_Result_DTO Mutation result.
     */
    public function handle_mutation( array $post ): Action_Result_DTO {
        return $this->results_checker_action_dispatcher->handle( $post );
    }

    /**
     * Query and assemble data for the Results Checker tab.
     *
     * @param array<string, mixed> $query Typically $_GET.
     *
     * @return array{
     *     results_checkers: array,
     *     competitions: array,
     *     events: array,
     *     seasons: array,
     *     season_select: string,
     *     competition_select: int|string,
     *     event_select: int|string,
     *     results_check_filter: string,
     * } Tab data array.
     */
    public function get_tab_data( array $query ): array {
        $season_select        = isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : '';
        $competition_select   = isset( $query['competition'] ) ? intval( $query['competition'] ) : '';
        $event_select         = isset( $query['event'] ) ? intval( $query['event'] ) : '';
        $results_check_filter = isset( $query['filterResultsChecker'] ) ? sanitize_text_field( wp_unslash( strval( $query['filterResultsChecker'] ) ) ) : 'outstanding';

        $raw_checkers = $this->results_checker_repository->find_all(
            array(
                'season'      => $season_select,
                'competition' => $competition_select,
                'event'       => $event_select,
                'status'      => $results_check_filter,
            )
        );
        if ( ! is_array( $raw_checkers ) ) {
            $raw_checkers = array();
        }

        $results_checkers = $this->results_checker_presenter->present_collection( $raw_checkers, $results_check_filter );

        $competitions = $this->competition_service->get_league_competitions();

        $seasons = $this->season_service->get_all_seasons();

        $events = array();
        foreach ( $competitions as $competition ) {
            $competition_id = is_object( $competition ) && isset( $competition->id ) ? (int) $competition->id : 0;
            if ( $competition_id <= 0 ) {
                continue;
            }
            try {
                $competition_events = $this->competition_service->get_events_for_competition( $competition_id );
            } catch ( Competition_Not_Found_Exception ) {
                continue;
            }
            foreach ( $competition_events as $event ) {
                $events[] = $event;
            }
        }

        return array(
            'results_checkers'     => $results_checkers,
            'competitions'         => $competitions,
            'events'               => $events,
            'seasons'              => $seasons,
            'season_select'        => $season_select,
            'competition_select'   => $competition_select,
            'event_select'         => $event_select,
            'results_check_filter' => $results_check_filter,
        );
    }
}
