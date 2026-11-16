<?php
declare( strict_types=1 );
/**
 * Results Fixtures Tab Handler
 *
 * Coordinates match data queries and presentation across the 6 fixture tabs.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter;
use Racketmanager\Admin\View_Models\Admin_Results_Match_View_Model;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;

/**
 * Class Results_Fixtures_Tab_Handler
 *
 * Assembles match view models for the results fixture tabs.
 */
final readonly class Results_Fixtures_Tab_Handler {

    /**
     * @param Fixture_Repository_Interface  $fixture_repository      Fixture repository.
     * @param Admin_Results_Match_Presenter $results_match_presenter Presenter for match rows.
     * @param Option_Service                $option_service          Option service.
     */
    public function __construct(
        private Fixture_Repository_Interface $fixture_repository,
        private Admin_Results_Match_Presenter $results_match_presenter,
        private Option_Service $option_service,
    ) {
    }

    /**
     * Assemble match view models for all 6 fixture tabs.
     *
     * @return array{
     *     pending_results_matches: Admin_Results_Match_View_Model[],
     *     awaiting_confirmation_matches: Admin_Results_Match_View_Model[],
     *     challenged_results_matches: Admin_Results_Match_View_Model[],
     *     approved_results_matches: Admin_Results_Match_View_Model[],
     *     postponed_matches: Admin_Results_Match_View_Model[],
     *     tournament_results_matches: Admin_Results_Match_View_Model[],
     * }
     */
    public function get_tab_data(): array {
        $league_result_pending     = $this->option_value( 'league', 'resultPending' );
        $tournament_result_pending = $this->option_value( 'tournament', 'resultPending' );

        return array(
            'pending_results_matches'       => $this->present_results_matches(
                array(
                    'time'             => 'outstanding',
                    'competition_type' => 'league',
                    'orderby'          => array(
                        'updated' => 'ASC',
                        'id'      => 'ASC',
                    ),
                    'resultPending'    => $league_result_pending,
                ),
                'pending-results',
                true,
                __( 'Result pending', 'racketmanager' ),
                $league_result_pending
            ),
            'awaiting_confirmation_matches' => $this->present_results_matches(
                array(
                    'status'  => 'P',
                    'orderby' => array(
                        'updated' => 'ASC',
                        'id'      => 'ASC',
                    ),
                ),
                'awaiting-confirmation'
            ),
            'challenged_results_matches'    => $this->present_results_matches(
                array(
                    'status'  => 'C',
                    'orderby' => array(
                        'updated' => 'ASC',
                        'id'      => 'ASC',
                    ),
                ),
                'challenge-results'
            ),
            'approved_results_matches'      => $this->present_results_matches(
                array(
                    'status'  => 'A',
                    'orderby' => array(
                        'updated' => 'ASC',
                        'id'      => 'ASC',
                    ),
                ),
                'results'
            ),
            'postponed_matches'             => $this->present_results_matches(
                array(
                    'status'        => 'D',
                    'orderby'       => array(
                        'updated' => 'ASC',
                        'id'      => 'ASC',
                    ),
                    'resultPending' => $league_result_pending,
                ),
                'postponed',
                true,
                '',
                $league_result_pending
            ),
            'tournament_results_matches'    => $this->present_results_matches(
                array(
                    'time'             => 'outstanding',
                    'competition_type' => 'tournament',
                    'orderby'          => array(
                        'updated' => 'ASC',
                        'id'      => 'ASC',
                    ),
                    'resultPending'    => $tournament_result_pending,
                ),
                'tournament',
                false,
                '',
                $tournament_result_pending
            ),
        );
    }

    /**
     * Query matches from repository, hydrate, and format with presenter.
     *
     * @param array<string, mixed> $match_args          Query filters for find_all.
     * @param string               $referrer            Origin tab identifier.
     * @param bool                 $include_result_path Whether to build a direct result submission link.
     * @param string               $default_tooltip     Default tooltip text.
     * @param mixed                $result_pending      Threshold value for overdue calculation.
     *
     * @return Admin_Results_Match_View_Model[]
     */
    private function present_results_matches(
        array $match_args,
        string $referrer,
        bool $include_result_path = true,
        string $default_tooltip = '',
        mixed $result_pending = null
    ): array {
        if ( empty( $match_args['resultPending'] ) ) {
            unset( $match_args['resultPending'] );
        }

        $matches = $this->fixture_repository->find_all( $match_args );
        if ( ! is_array( $matches ) ) {
            $matches = array();
        }

        $hydrated = array();
        foreach ( $matches as $match ) {
            $full = $this->hydrate_match( $match );
            if ( is_object( $full ) ) {
                $hydrated[] = $full;
            }
        }

        return $this->results_match_presenter->present_collection(
            $hydrated,
            $referrer,
            $include_result_path,
            $default_tooltip,
            $result_pending
        );
    }

    /**
     * Hydrate lightweight match objects to full fixture entities when necessary.
     *
     * @param mixed $match Match row or entity.
     *
     * @return object|null
     */
    private function hydrate_match( mixed $match ): ?object {
        if ( is_object( $match ) && ( isset( $match->link ) || isset( $match->match_title ) || isset( $match->fixture_title ) ) ) {
            return $match;
        }

        $id = 0;
        if ( is_object( $match ) && isset( $match->id ) ) {
            $id = (int) $match->id;
        } elseif ( is_numeric( $match ) ) {
            $id = (int) $match;
        }

        if ( $id > 0 ) {
            $hydrated = $this->fixture_repository->find_by_id( $id );
            if ( is_object( $hydrated ) ) {
                return $hydrated;
            }
        }

        return is_object( $match ) ? $match : null;
    }

    /**
     * Fetch an option value from the option service.
     *
     * @param string $group Option section/group name.
     * @param string $key   Option key within section.
     *
     * @return mixed
     */
    private function option_value( string $group, string $key ): mixed {
        $options = $this->option_service->get_options( $group );
        return is_array( $options ) ? ( $options[ $key ] ?? '' ) : '';
    }
}
