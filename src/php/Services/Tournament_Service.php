<?php
/**
 * Tournament_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Charge;
use Racketmanager\Domain\DTO\Tournament_Request_DTO;
use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Database_Operation_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Updated_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Validator\Validator_Tournament;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Messages;
use stdClass;
use WP_Error;

/**
 * Class to implement the Tournament Management Service
 */
class Tournament_Service {
    private RacketManager $racketmanager;
    private Tournament_Repository $tournament_repository;
    private Charge_Repository $charge_repository;
    private Competition_Service $competition_service;

    /**
     * Constructor
     *
     */
    public function __construct( RacketManager $plugin_instance, Tournament_Repository $tournament_repository, Charge_Repository $charge_repository, Competition_Service $competition_service ) {
        $this->racketmanager = $plugin_instance;
        $this->tournament_repository  = $tournament_repository;
        $this->charge_repository  = $charge_repository;
        $this->competition_service = $competition_service;
    }

    public function get_tournament( null|string|int $tournament_id, $search_term = 'id' ): ?Tournament {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id, $search_term );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::charge_not_found( $tournament_id ) );
        }
        return $tournament;
    }

    public function get_tournament_and_fees( ?int $tournament_id ): array {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }
        $fees = $this->get_fees( $tournament_id );
        return array( 'tournament' => $tournament, 'fees' => $fees );
    }

    public function get_fees( ?int $tournament_id ): stdClass {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }
        $args                = array();
        $args['competition'] = $tournament->competition_id;
        $args['season']      = $tournament->season;
        $charges             = $this->racketmanager->get_charges( $args );
        $competition_fee     = null;
        $event_fee           = null;
        $fee_id              = null;
        $fee_status          = null;
        if ( $charges ) {
            $competition_fee = 0;
            $event_fee       = 0;
            foreach ( $charges as $charge ) {
                $competition_fee += $charge->fee_competition;
                $event_fee       += $charge->fee_event;
                $fee_id           = $charge->id;
                $fee_status       = $charge->status;
            }
        }
        $fees              = new stdClass();
        $fees->competition = $competition_fee;
        $fees->event       = $event_fee;
        $fees->id          = $fee_id;
        $fees->status      = $fee_status;
        return $fees;
    }

    /**
     * Add tournament
     * - Call save tournament method which handles save logic
     *
     * @param Tournament_Request_DTO $tournament_request
     *
     * @return Tournament|int|WP_Error
     */
    public function add_tournament( Tournament_Request_DTO $tournament_request ): Tournament|int|WP_Error {
        return $this->save_tournament( new Tournament(), $tournament_request );
    }

    /**
     * Update tournament
     * - Find tournament by id
     * - Call save tournament method which handles save logic
     *
     * @param Tournament_Request_DTO $tournament_request
     *
     * @return Tournament|int|WP_Error
     */
    public function update_tournament( Tournament_Request_DTO $tournament_request ): Tournament|int|WP_Error {
        $tournament = $this->tournament_repository->find_by_id( $tournament_request->id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_request->id ) );
        }
        return $this->save_tournament( $tournament, $tournament_request );
    }

    /**
     * Save tournament
     * - Validate tournament request
     * - Set tournament attributes
     * - Set competition dates
     * - Save tournament record to the database
     * - Save charge record to the database
     *
     * @param Tournament $tournament
     * @param Tournament_Request_DTO $tournament_request
     *
     * @return Tournament|WP_Error
     */
    private function save_tournament( Tournament $tournament, Tournament_Request_DTO $tournament_request ): Tournament|WP_Error {
        $validator = $this->validate_tournament( $tournament_request );
        if ( is_wp_error( $validator ) ) {
            return $validator;
        }
        $tournament = $this->set_tournament_attributes( $tournament, $tournament_request );
        $charge     = $this->set_tournament_fees( $tournament_request );
        try {
            $updates = $this->save_tournament_and_fees( $tournament, $charge );
        } catch ( Database_Operation_Exception $e ) {
            return new WP_Error( 'database_error', $e->getMessage() );
        }
        if ( $updates ) {
            $tournament->set_tournament_info();
            $this->set_competition_dates( $tournament_request );
            if ( ! $tournament->is_closed && ! $tournament->is_active ) {
                $this->schedule_tournament_emails( $tournament );
                $this->schedule_tournament_ratings( $tournament );
            }
            return $tournament;
        }
        throw new Tournament_Not_Updated_Exception( Util_Messages::no_updates() );
    }

    /**
     * Save tournament and fees
     * - Save tournament record to the database
     * - Save charge record to the database
     *
     * @param Tournament $tournament
     * @param Charge $charge
     *
     * @return bool
     */
    private function save_tournament_and_fees( Tournament $tournament, Charge $charge ): bool {
        global $wpdb;
        $wpdb->query( 'START TRANSACTION' );
        try {
            $response = $this->tournament_repository->save( $tournament );
            if ( false === $response ) {
                throw new Database_Operation_Exception( Util_Messages::tournament_failed_to_save() );
            } elseif ( 0 === $response ) {
                $updates = false;
            } else {
                $updates = true;
            }
            $response = $this->charge_repository->save( $charge );
            if ( false === $response ) {
                throw new Database_Operation_Exception( Util_Messages::charge_failed_to_save() );
            } elseif ( 0 === $response ) {
                $fee_updates = false;
            } else {
                $fee_updates = true;
            }
            $wpdb->query( 'COMMIT' );
        } catch ( Database_Operation_Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            throw new Database_Operation_Exception( $e->getMessage() );
        }

        return $updates || $fee_updates;
    }
    private function validate_tournament( Tournament_Request_DTO $tournament ): bool|WP_Error {
        $validator = new Validator_Tournament();
        $validator = $validator->name( $tournament->name );
        $validator = $validator->competition( $tournament->competition_id );
        $validator = $validator->season( $tournament->season );
        $validator = $validator->venue( $tournament->venue );
        $validator = $validator->grade( $tournament->grade );
        $validator = $validator->num_entries( $tournament->num_entries );
        $validator = $validator->date( $tournament->date_open, 'open' );
        $validator = $validator->date( $tournament->date_closing, 'closing', $tournament->date_open, 'open' );
        $validator = $validator->date( $tournament->date_withdrawal, 'withdrawal', $tournament->date_closing, 'closing' );
        $validator = $validator->date( $tournament->date_start, 'start', $tournament->date_withdrawal, 'withdrawal' );
        $validator = $validator->date( $tournament->date, 'end', $tournament->date_start, 'start' );
        if ( $validator->error ) {
            return $validator->err;
        }
        return true;
    }

    private function set_tournament_attributes( Tournament $tournament, Tournament_Request_DTO $tournament_request ): Tournament {
        $tournament->set_name( $tournament_request->name );
        $tournament->set_competition_id( $tournament_request->competition_id );
        $tournament->set_season( $tournament_request->season );
        $tournament->set_venue( $tournament_request->venue );
        $tournament->set_end_date( $tournament_request->date );
        $tournament->set_closing_date( $tournament_request->date_closing );
        $tournament->set_withdrawal_date( $tournament_request->date_withdrawal );
        $tournament->set_opening_date( $tournament_request->date_open );
        $tournament->set_start_date( $tournament_request->date_start );
        $tournament->set_competition_code( $tournament_request->competition_code );
        $tournament->set_grade( $tournament_request->grade );
        $tournament->set_num_entries( $tournament_request->num_entries );
        return $tournament;
    }

    private function set_competition_dates( Tournament_Request_DTO $tournament_request ): void {
        $season                   = new stdClass();
        $season->name             = $tournament_request->season;
        $season->date_open        = $tournament_request->date_open;
        $season->date_closing     = $tournament_request->date_closing;
        $season->date_withdrawal  = $tournament_request->date_withdrawal;
        $season->date_start       = $tournament_request->date_start;
        $season->date_end         = $tournament_request->date;
        $season->competition_code = $tournament_request->competition_code;
        $season->grade            = $tournament_request->grade;
        $season->venue            = $tournament_request->venue;
        $this->competition_service->set_season_for_tournament_competition( $tournament_request->competition_id, $season );
    }

    /**
     * Delete tournament
     *
     * Remove season for competition
     * Remove tournament emails scheduled events
     * Remove tournament charge
     * Remove tournament record from the database
     *
     * @param int|null $tournament_id
     *
     * @return int
     */
    public function remove_tournament( ?int $tournament_id ): int {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }
        $this->competition_service->remove_season_for_competition( $tournament->competition_id, $tournament->season );
        $this->remove_tournament_fees( $tournament->competition_id, $tournament->season );
        $schedule_args = array( $tournament_id );
        $schedule_name = 'rm_calculate_tournament_ratings';
        Util::clear_scheduled_event( $schedule_name, $schedule_args );
        $schedule_name = 'rm_notify_tournament_entry_open';
        Util::clear_scheduled_event( $schedule_name, $schedule_args );
        $schedule_name = 'rm_notify_tournament_entry_reminder';
        Util::clear_scheduled_event( $schedule_name, $schedule_args );
        $schedule_name = 'rm_notify_tournament_finalists';
        Util::clear_scheduled_event( $schedule_name, $schedule_args );
        if ( isset( $this->charge ) ) {
            $this->charge->delete();
        }

        return $this->tournament_repository->delete( $tournament->get_id() );
    }

    /**
     * Schedule tournament emails
     * On tournament open date - message to notify entry open
     * 7 days before the tournament closing date - message to remind tournament entry closing soon
     * 5 days before the tournament final date - message tournament finalists
     *
     * @param Tournament $tournament
     *
     * @return void
     */
    private function schedule_tournament_emails( Tournament $tournament ): void {
        $schedule_args   = array();
        $schedule_args[] = $tournament->get_id();
        if ( ! empty( $tournament->get_open_date() ) ) {
            $schedule_name = 'rm_notify_tournament_entry_open';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            $schedule_start = Util::get_schedule_date( $tournament->get_open_date() );
            $success        = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
            if ( ! $success ) {
                error_log( __( 'Error scheduling tournament open emails', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        if ( ! empty( $tournament->get_closing_date() ) ) {
            $schedule_name = 'rm_notify_tournament_entry_reminder';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            $chase_date     = Util::amend_date( $tournament->get_closing_date(), 7, '-' );
            $schedule_start = Util::get_schedule_date( $chase_date );
            $success        = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
            if ( ! $success ) {
                error_log( __( 'Error scheduling tournament reminder emails', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        if ( ! empty( $tournament->get_end_date() ) ) {
            $schedule_name = 'rm_notify_tournament_finalists';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            $finalists_date = Util::amend_date( $tournament->get_end_date(), 5, '-' );
            $schedule_start = Util::get_schedule_date( $finalists_date );
            $success        = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
            if ( ! $success ) {
                error_log( __( 'Error scheduling tournament finalists emails', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
    }

    /**
     * Schedule tournament ratings calculation
     * Scheduled to run 1 day after the tournament closing date
     *
     * @param Tournament $tournament
     *
     * @return void
     */
    private function schedule_tournament_ratings( Tournament $tournament ): void {
        $schedule_args   = array();
        $schedule_args[] = $tournament->get_id();
        $schedule_name   = 'rm_calculate_tournament_ratings';
        Util::clear_scheduled_event( $schedule_name, $schedule_args );
        $date_schedule  = Util::amend_date( $tournament->get_closing_date(), 1 );
        $schedule_start = Util::get_schedule_date( $date_schedule );
        $success        = wp_schedule_single_event( $schedule_start, $schedule_name, $schedule_args );
        if ( ! $success ) {
            error_log( __( 'Error scheduling tournament ratings calculation', 'racketmanager' ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }

    /**
     * Set tournament fees
     *
     * @param Tournament_Request_DTO $tournament_request
     *
     * @return Charge
     */
    private function set_tournament_fees( Tournament_Request_DTO $tournament_request ): Charge {
        $competition_id = $tournament_request->competition_id;
        $season         = $tournament_request->season;
        $key            = $competition_id . '_' . $season;
        $charge         = $this->charge_repository->find_by_id( $key );
        if ( ! $charge ) {
            $charge = new Charge();
            $charge->set_competition_id( $competition_id );
            $charge->set_season( $season );
            $charge->set_status( 'draft' );
        }
        $charge->set_fee_competition( $tournament_request->fees->competition );
        $charge->set_fee_event( $tournament_request->fees->event );
        $charge->set_date( $tournament_request->date_start );
        return $charge;
    }

    /**
     * Remove tournament fees
     *
     * @param int|null $competition_id
     * @param string|null $season
     *
     * @return void
     */
    private function remove_tournament_fees( ?int $competition_id, ?string $season ): void {
        $key            = $competition_id . '_' . $season;
        $charge         = $this->charge_repository->find_by_id( $key );
        if ( $charge ) {
            $this->charge_repository->delete( $charge->get_id() );
        }
    }

}
