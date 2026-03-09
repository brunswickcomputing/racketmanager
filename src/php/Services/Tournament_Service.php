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
use Racketmanager\Domain\Competition;
use Racketmanager\Domain\DTO\Tournament\Championship_Rounds_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_Entry_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Config_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Finals_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Information_Request_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Player_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Request_DTO;
use Racketmanager\Domain\Event;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Database_Operation_Exception;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Entry_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Updated_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Validator\Validator_Plan;
use Racketmanager\Services\Validator\Validator_Tournament;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use Racketmanager\Util\Util_Messages;
use stdClass;
use WP_Error;
use function Racketmanager\debug_to_console;
use function Racketmanager\get_league;

/**
 * Class to implement the Tournament Management Service
 */
class Tournament_Service {
    private RacketManager $racketmanager;
    private Tournament_Repository $tournament_repository;
    private Charge_Repository $charge_repository;
    private Event_Repository $event_repository;
    private Fixture_Service $fixture_service;
    private League_Team_Repository $league_team_repository;
    private Tournament_Entry_Repository $tournament_entry_repository;
    private Competition_Service $competition_service;
    private Player_Service $player_service;
    private Club_Service $club_service;
    private Finance_Service $finance_service;

    /**
     * Constructor
     *
     */
    public function __construct( RacketManager $plugin_instance, Tournament_Repository $tournament_repository, Charge_Repository $charge_repository, Event_Repository $event_repository, Fixture_Service $fixture_service, League_Team_Repository $league_team_repository, Tournament_Entry_Repository $tournament_entry_repository, Competition_Service $competition_service, Player_Service $player_service, Club_Service $club_service, Finance_Service $finance_service ) {
        $this->racketmanager               = $plugin_instance;
        $this->tournament_repository       = $tournament_repository;
        $this->charge_repository           = $charge_repository;
        $this->event_repository            = $event_repository;
        $this->fixture_service             = $fixture_service;
        $this->league_team_repository      = $league_team_repository;
        $this->tournament_entry_repository = $tournament_entry_repository;
        $this->competition_service         = $competition_service;
        $this->player_service              = $player_service;
        $this->club_service                = $club_service;
        $this->finance_service             = $finance_service;
    }

    public function get_tournaments( array $criteria = array() ): array {
        return $this->tournament_repository->find_by( $criteria );
    }

    public function get_tournaments_for_player( ?int $player_id ): array {
        return $this->tournament_repository->find_by_player( $player_id );
    }

    public function get_tournaments_with_details( array $criteria = array() ): array {
        $tournaments = $this->tournament_repository->find_by( $criteria );
        foreach ( $tournaments as $i => $tournament ) {
            try {
                $competition = $this->competition_service->get_by_id( $tournament->get_competition_id() );
                $venue       = $tournament->get_venue();
                $club        = $this->club_service->get_club( $venue );
            } catch ( Competition_Not_Found_Exception|Club_Not_Found_Exception $e ) {
                throw new Competition_Not_Found_Exception( $e->getMessage() );
            }
            $tournaments[ $i ] = new Tournament_Details_DTO( $tournament, $competition, $club );
        }

        return $tournaments;
    }

    public function get_tournament_with_details_by_name( ?string $name ): ?Tournament_Details_DTO {
        try {
            $tournament = $this->get_tournament_by_name( $name );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        return $this->get_tournament_details( $tournament );
    }

    public function get_tournament_by_name( ?string $name ): ?Tournament {
        return $this->get_tournament( $name, 'name' );
    }

    public function get_tournament( null|string|int $tournament_id, $search_term = 'id' ): ?Tournament {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id, $search_term );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }

        return $tournament;
    }

    private function get_tournament_details( Tournament $tournament ): Tournament_Details_DTO {
        try {
            $competition = $this->competition_service->get_by_id( $tournament->get_competition_id() );
            $venue       = $tournament->get_venue();
            $club        = $this->club_service->get_club( $venue );
        } catch ( Competition_Not_Found_Exception|Club_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e->getMessage() );
        }

        return new Tournament_Details_DTO( $tournament, $competition, $club );
    }

    public function get_active_tournament( ?string $age_group = null ): ?Tournament {
        return $this->tournament_repository->find_active( $age_group );
    }

    public function get_tournament_with_details( int|string|null $tournament_id ): ?Tournament_Details_DTO {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }

        return $this->get_tournament_details( $tournament );
    }

    public function get_tournament_overview( int $tournament_id ): ?stdClass {
        $tournament = $this->tournament_repository->find_tournament_overview( $tournament_id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }

        return $tournament;
    }

    public function get_events_with_details_for_tournament( ?int $tournament_id ): array {
        $events = $this->tournament_repository->find_events_by_tournament_with_details( $tournament_id );
        foreach ( $events as $i => $event ) {
            $event->draw_size = pow( 2, ceil( log( $event->num_teams, 2 ) ) );
            $events[ $i ]     = $event;
        }

        return $events;
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
        $charges             = $this->finance_service->get_charges_by_criteria( $args );
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
                $fee_id          = $charge->id;
                $fee_status      = $charge->status;
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
        $tournament->update_from_request( $tournament_request );
        $charge = $this->set_tournament_fees( $tournament_request );
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
     * Delete tournament
     *
     * Remove season for competition
     * Remove tournament emails scheduled events
     * Remove tournament charge
     * Delete tournament
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
     * Bulk remove tournaments.
     *
     * @param array $tournament_ids
     * @return array{message:string, message_type:bool}
     */
    public function bulk_remove_tournaments( array $tournament_ids ): array {
        $messages      = array();
        $message_error = false;

        foreach ( $tournament_ids as $tournament_id ) {
            try {
                $deleted    = $this->remove_tournament( $tournament_id );
                $messages[] = $deleted
                    ? Util_Messages::tournament_deleted( $tournament_id )
                    : Util_Messages::tournament_not_deleted( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                $messages[]    = $e->getMessage();
                $message_error = true;
            }
        }

        return array(
            'message'      => implode( '<br>', $messages ),
            'message_type' => $message_error,
        );
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
        $key    = $competition_id . '_' . $season;
        $charge = $this->charge_repository->find_by_id( $key );
        if ( $charge ) {
            $this->charge_repository->delete( $charge->get_id() );
        }
    }

    public function get_player_entry_for_event( int $event_id, int $player_id, int $season ): ?stdClass {
        return $this->tournament_repository->find_event_details_for_player( $player_id, $event_id, $season );
    }

    public function get_tournament_event_entry_details_for_player( ?int $player_id, ?int $tournament_id ): array {
        if ( empty( $player_id ) ) {
            throw new Player_Not_Found_Exception( Util_Messages::player_not_found( $player_id ) );
        }
        if ( empty( $tournament_id ) ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }

        return $this->tournament_repository->find_event_entry_details_for_player_in_tournament( $player_id, $tournament_id );
    }

    public function set_tournament_information( ?int $tournament_id, Tournament_Information_Request_DTO $tournament_information_request_DTO ): bool|WP_Error {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return new WP_Error( 'tournament_not_found', $e->getMessage() );
        }
        $tournament->set_information( $tournament_information_request_DTO );

        return $this->tournament_repository->save( $tournament );
    }

    /**
     * Notify finalists of final day details
     *
     * @param int|null $tournament_id
     *
     * @return bool notification status
     */
    public function notify_finalists_for_tournament( ?int $tournament_id ): bool {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $players               = $this->tournament_repository->find_finalists_for_tournament( $tournament->get_id() );
        $times                 = array();
        $tournament_start_time = strtotime( $tournament->get_start_time() );
        $fixture_length        = strtotime( $tournament->get_time_increment() );
        foreach ( $tournament->get_order_of_play() as $final_courts ) {
            $start_time = $tournament_start_time;
            foreach ( $final_courts['matches'] as $fixture_id ) {
                $fixture_time = gmdate( 'H:i', $start_time );
                if ( ! empty( $fixture_id ) && ! in_array( $fixture_time, $times, true ) ) {
                    $times[] = $fixture_time;
                }
                $start_time = $start_time + $fixture_length;
            }
        }
        sort( $times );
        $headers    = array();
        $action_url = $this->racketmanager->site_url . $tournament->link . 'order_of_play/';
        $from_email = $this->racketmanager->get_confirmation_email( 'tournament' );
        if ( ! $from_email ) {
            throw new Invalid_Argument_Exception( Util_Messages::secretary_email_not_found( 'tournament' ) );
        }
        $message_sent      = false;
        $headers[]         = RACKETMANAGER_FROM_EMAIL . 'Tournament Secretary <' . $from_email . '>';
        $headers[]         = RACKETMANAGER_CC_EMAIL . 'Tournament Secretary <' . $from_email . '>';
        $organisation_name = $this->racketmanager->site_name;
        $email_subject     = $this->racketmanager->site_name . ' ' . ucwords( $tournament->name ) . ' ' . __( 'tournament finals day', 'racketmanager' );
        foreach ( $players as $player ) {
            $email_to      = $player->display_name . ' <' . $player->email . '>';
            $email_message = $this->racketmanager->shortcodes->load_template(
                'tournament-finalists',
                array(
                    'email_subject' => $email_subject,
                    'from_email'    => $from_email,
                    'action_url'    => $action_url,
                    'organisation'  => $organisation_name,
                    'tournament'    => $tournament,
                    'rounds'        => $times,
                    'addressee'     => $player->display_name,
                ),
                'email'
            );
            wp_mail( $email_to, $email_subject, $email_message, $headers );
            $message_sent = true;
        }

        return $message_sent;
    }

    public function get_event_details_for_tournament( Tournament $tournament, null|int|string $event_id ): ?Tournament_Event_DTO {
        $event = $this->tournament_repository->find_event_for_tournament( $tournament->get_id(), $event_id );
        if ( ! $event ) {
            throw new Event_Not_Found_Exception( Util_Messages::event_not_found( $event_id ) );
        }

        $entries   = $this->get_event_entries_for_tournament( $tournament->get_id(), $event->get_id() );
        $num_seeds = Util_Lookup::get_number_of_seeds( count( $entries ) );

        return new Tournament_Event_DTO( $event->get_id(), $event->get_name(), $entries, $num_seeds );
    }

    private function get_event_entries_for_tournament( int $tournament_id, int $event_id ): array {
        $event_entries = $this->tournament_repository->find_teams_for_tournament_event( $tournament_id, $event_id );

        return array_map(
            function ( $entry ) {
                $players = array();
                if ( ! empty( $entry->player_data ) ) {
                    foreach ( explode( ',', $entry->player_data ) as $team_player ) {
                        list( $id, $name ) = explode( ':', $team_player, 2 );
                        $players[] = new Tournament_Player_DTO( $id, $name );
                    }
                }

                return new Tournament_Event_Entry_DTO( (int) $entry->team_id, $entry->team_name, (int) $entry->rank, (float) $entry->rating, $players );
            }, $event_entries
        );
    }

    public function get_draw_details_for_tournament( Tournament $tournament, null|int|string $event_id ): ?Event {
        $event = $this->tournament_repository->find_event_for_tournament( $tournament->get_id(), $event_id );
        if ( ! $event ) {
            throw new Event_Not_Found_Exception( Util_Messages::event_not_found( $event_id ) );
        }

        // 1. Fetch all leagues and all matches for the event at once to avoid N+1 issues
        $leagues        = $this->competition_service->get_leagues_for_event( $event->get_id() );
        $event_fixtures = $this->tournament_repository->find_matches_by_event_for_tournament(
            $tournament->get_id(),
            $event->get_id()
        );

        foreach ( $leagues as $index => $league ) {
            $league = get_league( $league->id );
            // Assuming $league is already a populated object/entity
            $championship_finals = array_reverse( $league->championship->get_finals() );
            $active_finals       = [];

            foreach ( $championship_finals as $final ) {
                // 2. Filter matches from the pre-fetched list instead of querying inside the loop
                $fixtures = array_filter( $event_fixtures,
                    fn( $fixture ) => $fixture->league_id === $league->id && $fixture->final === $final['key']
                );

                if ( ! empty( $fixtures ) ) {
                    $final['matches'] = array_map(
                        fn( $fixture ) => $this->fixture_service->get_tournament_fixture_with_details( $fixture ),
                        $fixtures
                    );
                    $active_finals[]  = (object) $final;
                }
            }

            $league->finals    = $active_finals;
            $leagues[ $index ] = $league;
        }

        $event->leagues = $leagues;

        // 3. Map the full match list with details for the meta-field
        $fixtures = array_map(
            fn( $fixture ) => $this->fixture_service->get_tournament_fixture_with_details( $fixture ),
            $event_fixtures
        );
        $event->set_meta( 'fixtures', $fixtures );

        return $event;
    }

    public function get_players_for_tournament( ?int $tournament_id, ?string $status = null ): ?array {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $players = $this->tournament_entry_repository->find_by_tournament( $tournament->get_id(), $status );

        return Util::get_players_list( $players );
    }

    /**
     * @param int|null $tournament_id
     * @return array{confirmed: array, unpaid: array, pending: array, withdrawn: array}
     * @throws Tournament_Not_Found_Exception
     */
    public function get_categorized_players_for_tournament( ?int $tournament_id ): array {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $all_entries = $this->tournament_entry_repository->find_by_tournament( $tournament->get_id() );

        $confirmed = array();
        $unpaid    = array();
        $pending   = array();
        $withdrawn = array();

        foreach ( $all_entries as $entry ) {
            $status = (int) $entry->status;
            if ( 0 === $status ) {
                $pending[] = $entry;
            } elseif ( 3 === $status ) {
                $withdrawn[] = $entry;
            } elseif ( 2 === $status ) {
                if ( 'paid' === $entry->invoice_status ) {
                    $confirmed[] = $entry;
                } else {
                    $unpaid[] = $entry;
                }
            }
        }

        return array(
            'confirmed' => Util::get_players_list( $confirmed ),
            'unpaid'    => Util::get_players_list( $unpaid ),
            'pending'   => Util::get_players_list( $pending ),
            'withdrawn' => Util::get_players_list( $withdrawn ),
        );
    }

    public function get_player_details_for_tournament( Tournament $tournament, null|int|string $player_input ): ?Player {
        try {
            if ( is_numeric( $player_input ) ) {
                $player = $this->player_service->get_player( $player_input );
            } else {
                $player = $this->player_service->get_player_by_name( $player_input );
            }
        } catch ( Player_Not_Found_Exception $e ) {
            throw new Player_Not_Found_Exception( $e->getMessage() );
        }
        $player_id        = $player->get_id();
        $tournament_id    = $tournament->get_id();
        $tournament_entry = $this->tournament_entry_repository->find_by_tournament_and_player( $tournament_id, $player_id );
        if ( ! $tournament_entry ) {
            throw new Tournament_Entry_Not_Found_Exception( Util_Messages::tournament_entry_not_found() );
        }
        if ( ! empty( $tournament_entry->get_club_id() ) ) {
            $player->club = $this->club_service->get_club( $tournament_entry->get_club_id() );
        }
        $player->teams      = $this->tournament_repository->find_event_entry_details_for_player_in_tournament( $player_id, $tournament_id );
        $fixtures           = $this->fixture_service->get_fixtures_for_player_for_tournament( $player->get_id(), $tournament->get_id() );
        $player->matches    = $fixtures;
        $player->statistics = $this->get_statistics_for_a_player_by_tournament( $player, $fixtures );

        return $player;

    }

    public function get_statistics_for_a_player_by_tournament( Player $player, array $matches ): array {
        $statistics   = array();
        $opponents    = array( 'home', 'away' );
        $opponents_pt = array( 'player1', 'player2' );
        foreach ( $matches as $dto ) {
            $fixture   = $dto->fixture;
            $league    = $dto->league;
            $home_team = $dto->home_team;
            $away_team = $dto->away_team;
            $teams_dto = array( 'home' => $home_team, 'away' => $away_team );

            if ( ! empty( $fixture->winner_id ) ) {
                $match_type         = strtolower( substr( $league->type, 1, 1 ) );
                $winner             = null;
                $loser              = null;
                $player_ref         = null;
                $player_team        = null;
                $player_team_status = null;
                foreach ( $opponents as $opponent ) {
                    if ( ! $teams_dto[ $opponent ] ) {
                        continue;
                    }
                    if ( (int) $fixture->winner_id === (int) $teams_dto[ $opponent ]->team->get_id() ) {
                        $winner = $opponent;
                    }
                    if ( (int) $fixture->loser_id === (int) $teams_dto[ $opponent ]->team->get_id() ) {
                        $loser = $opponent;
                    }
                    if ( $teams_dto[ $opponent ]->team->has_player( (int) $player->get_id() ) ) {
                        $player_team = $opponent;
                        if ( 'home' === $player_team ) {
                            $player_ref = 'player1';
                        } else {
                            $player_ref = 'player2';
                        }
                    }
                }
                if ( $winner === $player_team ) {
                    $player_team_status = 'winner';
                } elseif ( $loser === $player_team ) {
                    $player_team_status = 'loser';
                }
                if ( ! isset( $statistics[ $match_type ]['played'][ $player_team_status ] ) ) {
                    $statistics[ $match_type ]['played'][ $player_team_status ] = 0;
                }
                ++ $statistics[ $match_type ]['played'][ $player_team_status ];
                if ( $fixture->is_walkover && 'winner' === $player_team_status ) {
                    if ( ! isset( $statistics[ $match_type ]['walkover'] ) ) {
                        $statistics[ $match_type ]['walkover'] = 0;
                    }
                    ++ $statistics[ $match_type ]['walkover'];
                }
                $sets = ! empty( $fixture->custom['sets'] ) ? $fixture->custom['sets'] : array();
                foreach ( $sets as $set ) {
                    if ( isset( $set['player1'] ) && '' !== $set['player1'] && isset( $set['player2'] ) && '' !== $set['player2'] ) {
                        if ( $set['player1'] > $set['player2'] ) {
                            if ( 'player1' === $player_ref ) {
                                $stat_ref = 'winner';
                            } else {
                                $stat_ref = 'loser';
                            }
                        } elseif ( 'player1' === $player_ref ) {
                            $stat_ref = 'loser';
                        } else {
                            $stat_ref = 'winner';
                        }
                        if ( ! isset( $statistics[ $match_type ]['sets'][ $stat_ref ] ) ) {
                            $statistics[ $match_type ]['sets'][ $stat_ref ] = 0;
                        }
                        ++ $statistics[ $match_type ]['sets'][ $stat_ref ];
                        foreach ( $opponents_pt as $opponent_ref ) {
                            if ( $player_ref === $opponent_ref ) {
                                if ( ! isset( $statistics[ $match_type ]['games']['winner'] ) ) {
                                    $statistics[ $match_type ]['games']['winner'] = 0;
                                }
                                $statistics[ $match_type ]['games']['winner'] += $set[ $opponent_ref ];
                            } else {
                                if ( ! isset( $statistics[ $match_type ]['games']['loser'] ) ) {
                                    $statistics[ $match_type ]['games']['loser'] = 0;
                                }
                                $statistics[ $match_type ]['games']['loser'] += $set[ $opponent_ref ];
                            }
                        }
                    }
                }
            }
        }

        return $player->get_stats( $statistics );
    }

    public function get_winners_for_tournament( Tournament $tournament ): array {
        $matches = $this->tournament_repository->find_winners_for_tournament( $tournament->get_id() );
        $results = array();
        foreach ( $matches as $match ) {
            $match->winner      = $match->winner_names;
            $match->loser       = $match->loser_names;
            $match->winner_club = $match->winner_clubs;
            $match->loser_club  = $match->loser_clubs;
            $key                = strtoupper( $match->match_type );
            if ( false === array_key_exists( $key, $results ) ) {
                $results[ $key ] = array();
            }
            $results[ $key ][] = $match;
        }

        return $results;
    }

    public function get_fixture_dates_for_tournament( Tournament $tournament ): array {
        $matches     = $this->tournament_repository->find_match_dates_for_tournament( $tournament->get_id() );
        $match_dates = array();
        foreach ( $matches as $match ) {
            $key                 = substr( $match->match_date, 0, 10 );
            $match_dates[ $key ] = substr( $match->match_date, 0, 10 );

        }

        return $match_dates;

    }

    public function get_fixtures_by_date_for_tournament( Tournament $tournament, ?string $match_date ): array {
        if ( empty( $match_date ) ) {
            throw new Invalid_Argument_Exception( Util_Messages::date_missing() );
        }
        $fixtures           = $this->tournament_repository->find_matches_by_date_for_tournament( $tournament->get_id(), $match_date );
        $tournament_matches = array();
        foreach ( $fixtures as $fixture ) {
            $dto = $this->fixture_service->get_tournament_fixture_with_details( $fixture );
            $key = substr( $fixture->date, 11, 5 );
            if ( '00:00' === $key ) {
                $key = '99:99';
            }
            if ( false === array_key_exists( $key, $tournament_matches ) ) {
                $tournament_matches[ $key ] = array();
            }
            $tournament_matches[ $key ][] = $dto;
        }
        ksort( $tournament_matches );

        return $tournament_matches;

    }

    public function contact_teams( ?int $tournament_id, ?string $message, bool $active = false ): bool {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        if ( $active ) {
            $players = $this->tournament_repository->find_active_players_for_tournament( $tournament->get_id() );
        } else {
            $players = $this->tournament_entry_repository->find_by_tournament( $tournament->get_id() );
        }
        $send          = false;
        $email_message = str_replace( '\"', '"', $message );
        $headers       = array();
        $email_from    = $this->racketmanager->get_confirmation_email( 'tournament' );
        $headers[]     = RACKETMANAGER_FROM_EMAIL . ucfirst( 'tournament' ) . ' Secretary <' . $email_from . '>';
        $headers[]     = RACKETMANAGER_CC_EMAIL . ucfirst( 'tournament' ) . ' Secretary <' . $email_from . '>';
        $email_subject = $this->racketmanager->site_name . ' - ' . $tournament->get_name() . ' - ' . __( 'Important Message', 'racketmanager' );
        $email_to      = array();
        foreach ( $players as $player ) {
            if ( ! empty( $player->email ) ) {
                $headers[] = RACKETMANAGER_BCC_EMAIL . $player->display_name . ' <' . $player->email . '>';
                $send      = true;
            }
        }
        if ( $send ) {
            wp_mail( $email_to, $email_subject, $email_message, $headers );
        }

        return $send;
    }

    /**
     * @throws Tournament_Not_Found_Exception
     */
    public function get_contact_preview( ?int $tournament_id, string $season, string $email_title, string $email_intro, array $email_body, string $email_close ): string {
        $tournament = $this->get_tournament( $tournament_id );

        $email_subject = $this->racketmanager->site_name . ' - ' . $tournament->get_name() . ' ' . $season . ' - ' . __( 'Important Message', 'racketmanager' );

        return strval(
            $this->racketmanager->shortcodes->load_template(
                'contact-teams',
                array(
                    'tournament'    => $tournament,
                    'organisation'  => $this->racketmanager->site_name,
                    'season'        => $season,
                    'title_text'    => $email_title,
                    'intro'         => $email_intro,
                    'body'          => $email_body,
                    'closing_text'  => $email_close,
                    'email_subject' => $email_subject,
                ),
                'email'
            )
        );
    }

    public function calculate_player_team_rating_for_tournament( ?int $tournament_id ): bool {
        debug_to_console( 'in calculate_player_team_rating_for_tournament');
        try {
            $events = $this->get_events_for_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $updates = false;
        foreach ( $events as $event ) {
            $type         = substr( $event->get_type(), 1, 1 );
            $league_teams = $this->league_team_repository->find_by_event_id( $event->get_id() );
            foreach ( $league_teams as $league_team ) {
                $team_rating = 0;
                $players     = $this->player_service->get_players_for_team( $league_team->get_team_id() );
                foreach ( $players as $player ) {
                    $rating      = empty( $player->wtn[ $type ] ) ? 40.9 : floatval( $player->wtn[ $type ] );
                    $team_rating += $rating;
                }
                $league_team->set_rating( $team_rating );
                $count = $this->league_team_repository->save( $league_team );
                debug_to_console( $count);
                if ( $count ) {
                    $updates = true;
                }
            }
        }

        return $updates;
    }

    /**
     * Get all events for a tournament
     *
     * @param int|null $tournament_id
     *
     * @return Event[]
     */
    public function get_events_for_tournament( ?int $tournament_id ): array {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        return $this->event_repository->find_by_competition_id( $tournament->get_competition_id() );
    }

    public function get_leagues_by_event_for_tournament( ?int $tournament_id ): array {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $leagues = $this->tournament_repository->find_leagues_by_event_for_tournament( $tournament->get_id() );
        $events  = array();
        foreach ( $leagues as $league ) {
            $league->draw_size               = pow( 2, ceil( log( $league->total_entries, 2 ) ) );
            $events[ $league->event_name ][] = $league;
        }

        return $events;
    }

    public function set_finals_config_for_tournament( ?int $tournament_id, Tournament_Finals_Config_Request_DTO $config ): bool|WP_Error {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $validator = new Validator_Plan();
        $validator = $validator->start_time( $config->start_time );
        $validator = $validator->num_courts_available( $config->num_courts );
        $validator = $validator->time_increment( $config->time_increment );
        if ( $validator->error ) {
            return $validator->err;
        }
        $tournament->set_num_courts( $config->num_courts );
        $tournament->set_start_time( $config->start_time );
        $tournament->set_time_increment( $config->time_increment );

        return $this->tournament_repository->save( $tournament );
    }

    public function reset_plan_for_tournament( ?int $tournament_id ): bool {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $updates = $this->fixture_service->reset_finals_fixtures_for_tournament( $tournament->get_id(), $tournament->get_end_date() );
        $tournament->set_order_of_play();
        $tournament_updates = $this->tournament_repository->save( $tournament );
        if ( $tournament_updates || $updates ) {
            return true;
        }

        return false;
    }

    public function get_finals_matches_for_tournament( ?int $tournament_id ): array {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }

        $fixtures = $this->fixture_service->get_finals_fixtures_for_tournament( $tournament->get_id() );

        return array_map(
            fn( $fixture ) => $this->fixture_service->get_tournament_fixture_with_details( $fixture ),
            $fixtures
        );
    }

    public function save_finals_plan_for_tournament( ?int $tournament_id, Tournament_Finals_Request_DTO $request ): bool|WP_Error {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        $updates       = false;
        $order_of_play = array();
        foreach ( $request->court_schedules as $idx => $court_schedule ) {
            $court_name                          = $court_schedule->name;
            $start_time                          = $court_schedule->start_time;
            $order_of_play[ $idx ]['court']      = $court_name;
            $order_of_play[ $idx ]['start_time'] = $start_time;
            foreach ( $court_schedule->slots as $slot_num => $slot ) {
                $order_of_play[ $idx ]['matches'][ $slot_num ] = $slot->fixture_id;
                $time                                          = strtotime( $start_time ) + $slot->start_time;
                $new_date                                      = gmdate( 'Y-m-d H:i:s', $time );
                $fixture_update                                = $this->fixture_service->update_fixture_finals_data( $slot->fixture_id, $new_date, $court_name );
                if ( $fixture_update ) {
                    $updates = true;
                }
            }
        }
        $tournament->set_order_of_play( $order_of_play );
        $tournament_updates = $this->tournament_repository->save( $tournament );
        if ( $tournament_updates || $updates ) {
            return true;
        }

        return false;
    }

    public function set_round_dates_for_tournament( ?int $tournament_id, Championship_Rounds_Request_DTO $request ): bool|WP_Error {
        try {
            $tournament = $this->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception|Competition_Not_Found_Exception $e ) {
            throw new Tournament_Not_Found_Exception( $e->getMessage() );
        }
        try {
            return $this->competition_service->set_championship_round_dates_for_competition_season( $tournament->get_competition_id(), $tournament->get_season(), $request );
        } catch ( Competition_Not_Found_Exception $e ) {
            throw new Competition_Not_Found_Exception( $e->getMessage() );
        }
    }

    /**
     * Calculate default match dates for the tournament rounds.
     *
     * @param Tournament  $tournament
     * @param Competition $competition
     *
     * @return array<int, string>
     */
    public function calculate_default_match_dates( Tournament $tournament, Competition $competition ): array {
        $round_length = $competition->settings['round_length'] ?? 7;
        return $tournament->calculate_default_match_dates( $round_length );
    }
}
