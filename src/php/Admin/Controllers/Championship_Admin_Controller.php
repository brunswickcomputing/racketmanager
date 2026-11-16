<?php
declare( strict_types=1 );
/**
 * Championship Admin Controller
 *
 * Handles championship admin mutations (startFinals, updateFinalResults).
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\League_Service;

/**
 * Coordinates championship administrative actions and result updates.
 */
final readonly class Championship_Admin_Controller {

    /**
     * @param Championship_Manager|null $championship_manager Championship domain manager.
     * @param League_Service            $league_service       League query service.
     * @param Action_Guard_Interface    $action_guard         Security guard service.
     */
    public function __construct(
        private ?Championship_Manager $championship_manager,
        private League_Service $league_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Handle championship actions.
     *
     * @param object|null          $league Target league entity or descriptor.
     * @param array<string, mixed> $query  Typically $_GET.
     * @param array<string, mixed> $post   Typically $_POST.
     *
     * @return array{tab: string, redirect?: string, message?: string|null, message_type?: bool|string|null}
     */
    public function handle_championship_page( ?object $league, array $query, array $post ): array {
        $tab    = 'finalResults';
        $league = $this->resolve_league( $league );

        if ( ! $league ) {
            return $this->league_not_found_result( $query, $post, $tab );
        }

        if ( isset( $post['action'] ) ) {
            return $this->handle_action( $league, $query, $post, $tab );
        }

        return array(
            'tab' => $tab,
        );
    }

    /**
     * Resolve league instance from input.
     *
     * @param object|null $league Target league entity or descriptor.
     *
     * @return League|null
     */
    private function resolve_league( ?object $league ): ?League {
        if ( $league instanceof League ) {
            return $league;
        }

        $league_id = is_object( $league ) && isset( $league->id ) ? (int) $league->id : null;

        return null !== $league_id ? $this->league_service->get_league( $league_id ) : null;
    }

    /**
     * Construct error result when league is not found.
     *
     * @param array<string, mixed> $query Typically $_GET.
     * @param array<string, mixed> $post  Typically $_POST.
     * @param string               $tab   Active tab.
     *
     * @return array{tab: string, redirect?: string, message: string, message_type: true}
     */
    private function league_not_found_result( array $query, array $post, string $tab ): array {
        $result = array(
            'tab'          => $tab,
            'message'      => __( 'League not found', 'racketmanager' ),
            'message_type' => true,
        );
        if ( isset( $post['action'] ) ) {
            $result['redirect'] = Admin_Redirect_Url_Builder::championship_screen( $query, array( 'tab' => $tab ) );
        }

        return $result;
    }

    /**
     * Route and handle submitted action.
     *
     * @param League               $league Current league.
     * @param array<string, mixed> $query  Query parameters.
     * @param array<string, mixed> $post   Post parameters.
     * @param string               $tab    Current tab.
     *
     * @return array{tab: string, redirect?: string, message?: string|null, message_type?: bool|string|null}
     */
    private function handle_action( League $league, array $query, array $post, string $tab ): array {
        $action = sanitize_text_field( wp_unslash( strval( $post['action'] ) ) );

        return match ( $action ) {
            'startFinals'        => $this->handle_start_finals( $league, $query ),
            'updateFinalResults' => $this->handle_update_final_results( $league, $query, $post, $tab ),
            default              => array( 'tab' => $tab ),
        };
    }

    /**
     * Build screen arguments for admin redirect URL.
     *
     * @param string $tab    Active tab.
     * @param League $league Target league.
     *
     * @return array{tab: string, league?: int|string}
     */
    private function build_screen_args( string $tab, League $league ): array {
        $extra_args = array(
            'tab' => $tab,
        );
        if ( isset( $league->id ) ) {
            $extra_args['league'] = $league->id;
        }

        return $extra_args;
    }

    /**
     * Handle startFinals action.
     *
     * @param League               $league Target league entity.
     * @param array<string, mixed> $query  Typically $_GET.
     *
     * @return array{tab: string, redirect: string, message: string, message_type: true}
     */
    private function handle_start_finals( League $league, array $query ): array {
        $extra_args = $this->build_screen_args( 'preliminary', $league );

        try {
            $this->action_guard->assert_allowed(
                'racketmanager_proceed_nonce',
                'racketmanager_championship_proceed',
                'update_results'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return array(
                'redirect'     => Admin_Redirect_Url_Builder::championship_screen( $query, $extra_args ),
                'tab'          => 'preliminary',
                'message'      => $e->getMessage() ?: __( 'Errors found', 'racketmanager' ),
                'message_type' => true,
            );
        }

        return array(
            'redirect'     => Admin_Redirect_Url_Builder::championship_screen( $query, $extra_args ),
            'tab'          => 'preliminary',
            'message'      => __( 'First round not started', 'racketmanager' ),
            'message_type' => true,
        );
    }

    /**
     * Handle updateFinalResults action.
     *
     * @param League               $league Target league entity.
     * @param array<string, mixed> $query  Typically $_GET.
     * @param array<string, mixed> $post   Typically $_POST.
     * @param string               $tab    Current tab.
     *
     * @return array{tab: string, redirect: string, message: string, message_type: bool}
     */
    private function handle_update_final_results( League $league, array $query, array $post, string $tab ): array {
        $extra_args = $this->build_screen_args( $tab, $league );

        try {
            $this->action_guard->assert_allowed(
                'racketmanager_nonce',
                'racketmanager_update-finals',
                'update_results'
            );
        } catch ( Invalid_Status_Exception $e ) {
            return array(
                'redirect'     => Admin_Redirect_Url_Builder::championship_screen( $query, $extra_args ),
                'tab'          => $tab,
                'message'      => $e->getMessage() ?: __( 'Errors found', 'racketmanager' ),
                'message_type' => true,
            );
        }

        if ( ! $this->championship_manager ) {
            return array(
                'redirect'     => Admin_Redirect_Url_Builder::championship_screen( $query, $extra_args ),
                'tab'          => $tab,
                'message'      => __( 'Championship manager not found', 'racketmanager' ),
                'message_type' => true,
            );
        }

        $num_updated = $this->execute_update_final_results( $this->championship_manager, $league, $post );

        return array(
            'redirect'     => Admin_Redirect_Url_Builder::championship_screen( $query, $extra_args ),
            'tab'          => $tab,
            /* translators: %d: number of updated fixtures. */
            'message'      => sprintf( __( 'Updated Results of %d fixtures', 'racketmanager' ), $num_updated ),
            'message_type' => false,
        );
    }

    /**
     * Execute final results update via championship manager.
     *
     * @param Championship_Manager $manager Championship domain manager.
     * @param League               $league  Target league.
     * @param array<string, mixed> $post    Submitted post data.
     *
     * @return int Number of updated fixtures.
     */
    private function execute_update_final_results( Championship_Manager $manager, League $league, array $post ): int {
        $custom      = $this->extract_array_param( $post, 'custom' );
        $matches     = $this->extract_array_param( $post, 'matches' );
        $home_points = $this->extract_array_param( $post, 'home_points' );
        $away_points = $this->extract_array_param( $post, 'away_points' );
        $round       = isset( $post['round'] ) ? intval( $post['round'] ) : null;

        return $manager->update_final_results(
            $league->championship,
            $matches,
            $home_points,
            $away_points,
            $custom,
            $round
        );
    }

    /**
     * Extract array parameter from post data or return empty array.
     *
     * @param array<string, mixed> $post Submitted post data.
     * @param string               $key  Parameter key.
     *
     * @return array<array-key, mixed>
     */
    private function extract_array_param( array $post, string $key ): array {
        return isset( $post[ $key ] ) && is_array( $post[ $key ] ) ? $post[ $key ] : array();
    }
}
