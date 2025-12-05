<?php
/**
 * Club_Player_Management_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Club_Player;
use Racketmanager\Domain\Club_Player_DTO;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Player_Repository;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Player_Repository;
use stdClass;
use WP_Error;
use function Racketmanager\club_players_notification;

/**
 * Class to implement the Club Player Management Service
 */
class Club_Player_Management_Service {
    private Club_Player_Repository $club_player_repository;
    private Player_Repository $player_repository;
    private Club_Repository $club_repository;
    private Player_Management_Service $player_service;
    private ?RacketManager $racketmanager;

    /**
     * Constructor
     *
     * @param $plugin_instance
     * @param Club_Player_Repository $club_player_repository
     * @param Player_Repository $player_repository
     * @param Club_Repository $club_repository
     * @param Player_Management_Service $player_service
     */
    public function __construct( $plugin_instance, Club_Player_Repository $club_player_repository, Player_Repository $player_repository, Club_Repository $club_repository, Player_Management_Service $player_service ) {
        $this->racketmanager          = $plugin_instance;
        $this->club_player_repository = $club_player_repository;
        $this->player_repository      = $player_repository;
        $this->club_repository        = $club_repository;
        $this->player_service         = $player_service;
    }

    /**
     * Get registration details
     *
     * @param $registration_id
     *
     * @return Club_Player_DTO
     */
    public function get_registration( $registration_id ): Club_Player_DTO {
        $registration = $this->club_player_repository->find( $registration_id );
        if ( ! $registration ) {
            throw new Registration_Not_Found_Exception( __( 'Registration not found', 'racketmanager' ) );
        }
        return $this->create_club_player_dto( $registration_id );
    }

    /**
     * Register player to club
     *
     * @param int|null $club_id
     * @param int|null $registered_by_userId
     *
     * @return Club_Player|WP_Error
     */
    public function register_player_to_club( ?int $club_id, ?int $registered_by_userId = null ): string|WP_Error {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( __( 'Club not found', 'racketmanager' ) );
        }
        $player = $this->player_service->validate_player();
        if ( is_wp_error( $player ) ) {
            return $player;
        }
        if ( empty( $player->id ) ) {
            $player = $this->player_service->add_player( $player );
        }
        $player = $this->player_repository->find( $player->id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( __( 'Player not found or inactive', 'racketmanager' ) );
        }

        if ( $this->club_player_repository->find_by_club_and_player( $club_id, $player->id ) ) {
            throw new Player_Already_Registered_Exception( __( 'Player already registered to this club', 'racketmanager' ) );
        }

        $club_player                 = new stdClass();
        $club_player->club_id        = $club_id;
        $club_player->player_id      = $player->id;
        $club_player->requested_date = gmdate( 'Y-m-d' );
        $club_player->requested_user = $registered_by_userId;
        $club_player->status         = 'pending';
        $registration                = new Club_Player( $club_player );
        $this->club_player_repository->save( $registration );

        $options = $this->racketmanager->get_options( 'rosters' );
        if ( 'auto' === $options['rosterConfirmation'] || current_user_can( 'edit_teams' ) ) {
            $this->approve_registration( $registration->id, $registered_by_userId );
            $action = 'add';
            $msg    = __( 'Player added to club', 'racketmanager' );
        } else {
            $action = 'request';
            $msg    = __( 'Player registration pending', 'racketmanager' );
        }
        if ( ! empty( $options['rosterConfirmationEmail'] ) ) {
            $headers = array();
            $user    = $this->player_repository->find( $registered_by_userId );
            if ( ! empty( $club->match_secretary->id ) && $club->match_secretary->id !== $user->ID ) {
                $headers[] = RACKETMANAGER_CC_EMAIL . $club->match_secretary->display_name . ' <' . $club->match_secretary->email . '>';
            }
            $email_to                  = $user->display_name . ' <' . $user->user_email . '>';
            $message_args              = array();
            $message_args['requestor'] = $user->display_name;
            $message_args['action']    = $action;
            $message_args['club']      = $club->shortcode;
            $message_args['player']    = $player->fullname;
            $message_args['btm']       = empty( $player->btm ) ? null : $player->btm;
            $headers[]                 = RACKETMANAGER_FROM_EMAIL . $this->racketmanager->site_name . ' <' . $options['rosterConfirmationEmail'] . '>';
            $headers[]                 = RACKETMANAGER_CC_EMAIL . $this->racketmanager->site_name . ' <' . $options['rosterConfirmationEmail'] . '>';
            $subject                   = $this->racketmanager->site_name . ' - ' . $msg . ' - ' . $club->shortcode;
            $message                   = club_players_notification( $message_args );
            wp_mail( $email_to, $subject, $message, $headers );
        }

        return $msg;
    }

    /**
     * Approve registration
     *
     * @param int $registration_id
     * @param int $approving_user
     *
     * @return void
     */
    public function approve_registration( int $registration_id, int $approving_user ): void {
        $registration = $this->club_player_repository->find( $registration_id );
        if ( ! $registration ) {
            throw new Registration_Not_Found_Exception( __( 'Registration not found to approve', 'racketmanager' ) );
        }
        $registration->approve( $approving_user );
        $this->club_player_repository->save( $registration );
    }

    /**
     * Remove registration from the club
     *
     * @param int $registration_id
     * @param int $removing_user
     *
     * @return void
     */
    public function remove_registration( int $registration_id, int $removing_user ): void {
        $registration = $this->club_player_repository->find( $registration_id );
        if ( ! $registration ) {
            throw new Registration_Not_Found_Exception( __( 'Registration not found to remove', 'racketmanager' ) );
        }
        $registration->approve( $removing_user );
        $this->club_player_repository->save( $registration );
    }

    /**
     * Retrieves all active players registered for any club in the system, with full details and optional filtering.
     *
     * @param string|null $active Optional active filter.
     * @param string|null $status Optional status filter.
     * @param int|null $club_id Optional Club ID filter.
     * @param string|null $gender Optional gender filter.
     * @param bool $system Optional system filter.
     *
     * @return Club_Player_DTO[]
     */
    public function get_registered_players_list( string $active = null, string $status = null, int $club_id = null, string $gender = null, bool $system = false, ?int $max_age = null, ?int $min_age = null ): array {
        $players = $this->player_repository->find_club_players_with_details( $club_id, $status, $gender, $active, $system, $max_age, $min_age );

        return array_map( function ( $registration_id ) {
            return $this->create_club_player_dto( $registration_id );
        }, $players );
    }

    /**
     * Create Club Player DTO from registration ID
     *
     * @param int $registration_id
     *
     * @return Club_Player_DTO
     */
    private function create_club_player_dto( int $registration_id ): Club_Player_DTO {
        $registration       = $this->club_player_repository->find( $registration_id );
        $player             = $this->player_repository->find( $registration->player_id );
        $club               = $this->club_repository->find( $registration->club_id );
        $registered_by_name = null;
        $removed_by_name    = null;
        $approved_by_name   = null;
        if ( ! empty( $registration->get_requested_user() ) ) {
            $registered_by      = $this->player_repository->find( $registration->get_requested_user() );
            $registered_by_name = $registered_by?->display_name;
        }
        if ( ! empty( $registration->get_removed_user() ) ) {
            $removed_by      = $this->player_repository->find( $registration->get_removed_user() );
            $removed_by_name = $removed_by?->display_name;
        }
        if ( ! empty( $registration->get_requested_user() ) ) {
            $approved_by      = $this->player_repository->find( $registration->get_requested_user() );
            $approved_by_name = $approved_by?->display_name;
        }

        return new Club_Player_DTO( $player, $club, $registration, $registered_by_name, $removed_by_name, $approved_by_name );
    }

    /**
     * Get player registration for a club
     *
     * @param int $club_id
     * @param int $player_id
     *
     * @return Club_Player_DTO|null
     */
    public function get_player_for_club( int $club_id, int $player_id ): ?Club_Player_DTO {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( __( 'Club not found', 'racketmanager' ) );
        }
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( __( 'Player not found', 'racketmanager' ) );
        }
        $registration = $this->club_player_repository->find_by_club_and_player( $club_id, $player_id );

        return $this->create_club_player_dto( $registration->id );
    }

    /**
     * Check if a player is active in a club
     *
     * @param int $club_id
     * @param int $player_id
     *
     * @return bool
     */
    public function is_player_active_in_club( int $club_id, int $player_id ): bool {
        $registration = $this->club_player_repository->find_by_club_and_player( $club_id, $player_id );
        if ( ! $registration ) {
            return false;
        }

        return $registration->get_status() === 'approved';
    }

    /**
     * Get fake players for a club
     *
     * @param int $club_id
     *
     * @return array
     */
    public function get_dummy_players( int $club_id ): array {
        $player_options                = $this->racketmanager->get_options( 'player' );
        $players['walkover']['male']   = $this->club_player_repository->find_by_club_and_player( $club_id, $player_options['walkover']['male'] );
        $players['walkover']['female'] = $this->club_player_repository->find_by_club_and_player( $club_id, $player_options['walkover']['female'] );
        $players['noplayer']['male']   = $this->club_player_repository->find_by_club_and_player( $club_id, $player_options['noplayer']['male'] );
        $players['noplayer']['female'] = $this->club_player_repository->find_by_club_and_player( $club_id, $player_options['noplayer']['female'] );
        $players['share']['male']      = $this->club_player_repository->find_by_club_and_player( $club_id, $player_options['share']['male'] );
        $players['share']['female']    = $this->club_player_repository->find_by_club_and_player( $club_id, $player_options['share']['female'] );

        return $players;
    }
}
