<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: Admin_Championship class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Championship
 */

namespace Racketmanager\Admin;

use Racketmanager\Domain\Competition\League;
use Racketmanager\Services\Championship_Manager;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Validator\Validator;

/**
 * RacketManager Championship Admin functions
 * Class to implement RacketManager Admin Championship
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Championship
 */
class Admin_Championship extends Admin_Display {

    private ?Championship_Manager $championship_manager = null;

    public function __construct(
        $plugin_instance = null,
        ?Simple_Container $container = null,
        ?Championship_Manager $championship_manager = null,
    ) {
        parent::__construct( $plugin_instance, $container );
        $c                          = $container ?? ( $this->racketmanager->container ?? null );
        $manager_key                = ( $c instanceof Simple_Container )
            ? ( $c->has_definition( Championship_Manager::class ) ? Championship_Manager::class : ( $c->has( 'championship_manager' ) ? 'championship_manager' : null ) )
            : null;
        $this->championship_manager = $championship_manager ?? ( ( $c instanceof Simple_Container && $manager_key ) ? $c->get( $manager_key ) : null );
    }

    /**
     * Handle administration panel
     *
     * @param object|null $league league object.
     */
    public function handle_championship_admin_page( ?object $league = null ): string {
        $validator = new Validator();
        $tab       = 'finalResults'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        if ( ! ( $league instanceof League ) ) {
            $league = $this->racketmanager->container->get( 'league_service' )->get_league( $league->id ?? null );
        }
        if ( ! $league ) {
            $this->set_message( __( 'League not found', 'racketmanager' ), true );
            $this->show_message();
            return $tab;
        }
        if ( isset( $_POST['action'] ) ) {
            $action = sanitize_text_field( wp_unslash( $_POST['action'] ) );
            if ( 'startFinals' === $action ) {
                $validator = $validator->check_security_token( 'racketmanager_proceed_nonce', 'racketmanager_championship_proceed' );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->capability( 'update_results');
                }
                if ( empty( $validator->error ) ) {
                    //TODO: start final rounds now moved to championship admin service
                    //$updates = $this->start_final_rounds( $league );
                    $updates = false;
                    if ( $updates ) {
                        $this->set_message( __( 'First round started', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'First round not started', 'racketmanager' ), true );
                        $tab = 'preliminary'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                    }
                }
            } elseif ( 'updateFinalResults' === $action ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_update-finals' );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->capability( 'update_results' );
                }
                if ( empty( $validator->error ) ) {
                    $custom      = $_POST['custom'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $matches     = $_POST['matches'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $home_points = $_POST['home_points'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $away_points = $_POST['away_points'] ?? array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $round       = isset( $_POST['round'] ) ? intval( $_POST['round'] ) : null;

                    $championship_manager = $this->championship_manager;
                    if ( $championship_manager ) {
                        $num_updated = $championship_manager->update_final_results( $league->championship, $matches, $home_points, $away_points, $custom, $round );
                        $this->set_message( sprintf( __( 'Updated Results of %d fixtures', 'racketmanager' ), $num_updated ) );
                    } else {
                        $this->set_message( __( 'Championship manager not found', 'racketmanager' ), true );
                    }
                }
                if ( ! empty( $validator->error ) ) {
                    if ( empty( $validator->msg ) ) {
                        $validator->msg = __( 'Errors found', 'racketmanager' );
                    }
                    $this->set_message( $validator->msg, true );
                }
            }
            $this->show_message();
        }
        return $tab; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    }
}
