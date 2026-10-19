<?php
/**
 * RacketManager-Admin API: Admin_Result class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Result
 */

declare( strict_types=1 );

namespace Racketmanager\Admin;

use LogicException;
use Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\Services\Admin\Result\Results_Checker_Action_Dispatcher_Interface;
use Racketmanager\Services\Container\Simple_Container;

/**
 * RacketManager Season Admin functions
 * Class to implement RacketManager Admin Result
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Result
 */
class Admin_Result extends Admin_Display {

    private ?Results_Checker_Action_Dispatcher_Interface $results_checker_dispatcher = null;
    private Admin_Results_Match_Presenter $results_match_presenter;

    public function __construct(
        $plugin_instance,
        ?Admin_Action_Adapter $admin_action_adapter = null,
        ?Results_Checker_Action_Dispatcher_Interface $results_checker_action_dispatcher = null,
        ?Admin_Results_Match_Presenter $results_match_presenter = null,
    ) {
        parent::__construct( $plugin_instance );
        if ( null !== $admin_action_adapter ) {
            $this->admin_action_adapter = $admin_action_adapter;
        }
        $this->results_checker_dispatcher = $results_checker_action_dispatcher;
        $this->results_match_presenter    = $results_match_presenter ?? $this->resolve_results_match_presenter();
    }

    /**
     * Function to handle administration club displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $this->display_results_page();
    }

    /**
     * Show RacketManager results page
     */
    public function display_results_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'view_leagues' ) ) {
            $this->set_message( $this->no_permission, true );
            $this->show_message();
            return;
        }

        if ( isset( $_POST['doResultsChecker'] ) ) {
            $dispatcher = $this->results_checker_dispatcher ?? $racketmanager->container->get( 'results_checker_action_dispatcher' );
            $result     = $dispatcher->handle( $_POST );
            if ( $this->deliver_results_action_result( $result, $_GET, array( 'tab' => 'resultschecker' ) ) ) {
                return;
            }
        }

        $this->apply_flash_message();
        $this->show_message();

        $season_select        = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['season'] ) ) ) : '';
        $competition_select   = isset( $_GET['competition'] ) ? intval( $_GET['competition'] ) : '';
        $event_select         = isset( $_GET['event'] ) ? intval( $_GET['event'] ) : '';
        $results_check_filter = isset( $_GET['filterResultsChecker'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['filterResultsChecker'] ) ) ) : 'outstanding';
        $tab                  = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( strval( $_GET['tab'] ) ) ) : 'resultschecker';

        $results_checkers = $racketmanager->get_result_warnings(
            array(
                'season'      => $season_select,
                'competition' => $competition_select,
                'event'       => $event_select,
                'status'      => $results_check_filter,
            )
        );
        $presenter        = $racketmanager->container->get( 'results_checker_presenter' );
        $results_checkers = $presenter->present_collection( $results_checkers, $results_check_filter );

        $league_result_pending      = $this->option_value( 'league', 'resultPending' );
        $tournament_result_pending  = $this->option_value( 'tournament', 'resultPending' );
        $pending_results_matches    = $this->present_results_matches(
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
        );
        $awaiting_confirmation_matches = $this->present_results_matches(
            array(
                'status'  => 'P',
                'orderby' => array(
                    'updated' => 'ASC',
                    'id'      => 'ASC',
                ),
            ),
            'awaiting-confirmation'
        );
        $challenged_results_matches = $this->present_results_matches(
            array(
                'status'  => 'C',
                'orderby' => array(
                    'updated' => 'ASC',
                    'id'      => 'ASC',
                ),
            ),
            'challenge-results'
        );
        $approved_results_matches = $this->present_results_matches(
            array(
                'status'  => 'A',
                'orderby' => array(
                    'updated' => 'ASC',
                    'id'      => 'ASC',
                ),
            ),
            'results'
        );
        $postponed_matches = $this->present_results_matches(
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
        );
        $tournament_results_matches = $this->present_results_matches(
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
        );

        $competitions = $this->competition_service->get_league_competitions();
        $seasons      = $this->season_service->get_all_seasons();
        $events       = array();
        foreach ( $competitions as $competition ) {
            try {
                $competition_events = $this->competition_service->get_events_for_competition( (int) $competition->id );
            } catch ( Competition_Not_Found_Exception ) {
                continue;
            }
            foreach ( $competition_events as $event ) {
                $events[] = $event;
            }
        }
        include RACKETMANAGER_PATH . 'templates/admin/show-results.php';
    }

    /**
     * @param array<string, mixed> $match_args
     *
     * @return \Racketmanager\Admin\View_Models\Admin_Results_Match_View_Model[]
     */
    private function present_results_matches( array $match_args, string $referrer, bool $include_result_path = true, string $default_tooltip = '', mixed $result_pending = null ): array {
        global $racketmanager;

        if ( empty( $match_args['resultPending'] ) ) {
            unset( $match_args['resultPending'] );
        }

        $matches = $racketmanager->get_matches( $match_args );
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

        return $this->results_match_presenter->present_collection( $hydrated, $referrer, $include_result_path, $default_tooltip, $result_pending );
    }

    private function hydrate_match( mixed $match ): ?object {
        if ( is_object( $match ) && ( isset( $match->link ) || isset( $match->match_title ) || isset( $match->fixture_title ) ) ) {
            return $match;
        }
        $container = $this->racketmanager->container ?? null;
        if ( $container instanceof Simple_Container && $container->has( 'fixture_repository' ) ) {
            $id = is_object( $match ) && isset( $match->id ) ? (int) $match->id : ( is_numeric( $match ) ? (int) $match : 0 );
            if ( $id > 0 ) {
                $hydrated = $container->get( 'fixture_repository' )->find_by_id( $id );
                if ( is_object( $hydrated ) ) {
                    return $hydrated;
                }
            }
        }

        return is_object( $match ) ? $match : null;
    }

    private function option_value( string $group, string $key ): mixed {
        global $racketmanager;
        $options = $racketmanager->get_options( $group );

        return is_array( $options ) ? ( $options[ $key ] ?? '' ) : '';
    }

    private function resolve_results_match_presenter(): Admin_Results_Match_Presenter {
        $container = $this->racketmanager->container ?? null;
        if ( ! $container instanceof Simple_Container || ! $container->has( 'admin_results_match_presenter' ) ) {
            throw new LogicException( 'The admin results match presenter is not registered.' );
        }

        return $container->get( 'admin_results_match_presenter' );
    }
}
