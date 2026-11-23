<?php
declare( strict_types=1 );
/**
 * Registration_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Club\Club_Player_Factory;
use Racketmanager\Domain\Club\Events\Club_Player_Registered_Event;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Events\Domain_Event_Dispatcher_Interface;
use Racketmanager\Domain\Ports\Clock_Interface;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\Application\Player\Ports\Registration_Service_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Player_Repository;
use stdClass;
use WP_Error;
use function Racketmanager\club_players_notification;

/**
 * Class to implement the Club Player Management Service
 */
class Registration_Service implements Registration_Service_Interface {
    private Registration_Repository_Interface $club_player_repository;
    private Player_Repository $player_repository;
    private Club_Repository $club_repository;
    private Player_Service $player_service;
    private ?RacketManager $racketmanager;
    private Mailer_Interface $mailer;
    private ?Domain_Event_Dispatcher_Interface $event_dispatcher;
    private Clock_Interface $clock;

    /**
     * Initialise the service dependencies.
     *
     * @param mixed                                  $plugin_instance plugin instance.
     * @param Registration_Repository_Interface      $club_player_repository registration repository.
     * @param Player_Repository                      $player_repository player repository.
     * @param Club_Repository                        $club_repository club repository.
     * @param Player_Service                         $player_service player service.
     * @param Mailer_Interface                       $mailer mailer.
     * @param Clock_Interface                        $clock clock.
     * @param Domain_Event_Dispatcher_Interface|null $event_dispatcher event dispatcher.
     *
     * @return void
     */
    public function __construct(
        mixed $plugin_instance,
        Registration_Repository_Interface $club_player_repository,
        Player_Repository $player_repository,
        Club_Repository $club_repository,
        Player_Service $player_service,
        Mailer_Interface $mailer,
        Clock_Interface $clock,
        ?Domain_Event_Dispatcher_Interface $event_dispatcher = null
    ) {
        $this->racketmanager          = $plugin_instance;
        $this->club_player_repository = $club_player_repository;
        $this->player_repository      = $player_repository;
        $this->club_repository        = $club_repository;
        $this->player_service         = $player_service;
        $this->mailer                 = $mailer;
        $this->clock                  = $clock;
        $this->event_dispatcher       = $event_dispatcher;
    }

    /**
     * Get a club player registration.
     *
     * @param int $registration_id registration id.
     *
     * @return Club_Player_DTO
     * @throws Registration_Not_Found_Exception Registration not found exception.
     */
    public function get_registration( int $registration_id ): Club_Player_DTO {
        $registration = $this->club_player_repository->find( $registration_id );
        if ( ! $registration ) {
            throw new Registration_Not_Found_Exception( esc_html__( 'Registration not found', 'racketmanager' ) );
        }
        return $this->create_club_player_dto( $registration_id );
    }

    /**
     * Register a player to a club.
     *
     * @param int|null   $club_id club id.
     * @param int|null   $registered_by_user_id registered by user id.
     * @param array|null $request request.
     *
     * @throws Club_Not_Found_Exception Club not found exception.
     * @throws Player_Not_Found_Exception Player not found exception.
     * @throws Player_Already_Registered_Exception Player already registered exception.
     */
    public function register_player_to_club( ?int $club_id, ?int $registered_by_user_id = null, ?array $request = null ): string|WP_Error {
        $club = $this->club_repository->find_by_id( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( esc_html__( 'Club not found', 'racketmanager' ) );
        }
        $player = $this->player_service->validate_player( $request );
        if ( is_wp_error( $player ) ) {
            return $player;
        }
        if ( empty( $player->id ) ) {
            $player = $this->player_service->add_player( $player );
        }
        $player = $this->player_repository->find( $player->id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( esc_html__( 'Player not found or inactive', 'racketmanager' ) );
        }

        if ( $this->club_player_repository->find_by_club_and_player( $club_id, $player->id ) ) {
            throw new Player_Already_Registered_Exception( esc_html__( 'Player already registered to this club', 'racketmanager' ) );
        }

        $club_player                 = new stdClass();
        $club_player->club_id        = $club_id;
        $club_player->player_id      = $player->id;
        $club_player->requested_date = gmdate( 'Y-m-d' );
        $club_player->requested_user = $registered_by_user_id;
        $club_player->status         = 'pending';
        $registration                = Club_Player_Factory::from_object( $club_player );
        $this->club_player_repository->save( $registration );

        $options = $this->racketmanager->get_options( 'rosters' );
        if ( 'auto' === $options['rosterConfirmation'] || current_user_can( 'edit_teams' ) ) {
            $this->approve_registration( $registration->id ?? 0, $registered_by_user_id ?? 0 );
            $action = 'add';
            $msg    = __( 'Player added to club', 'racketmanager' );
            $status = 'approved';
        } else {
            $action = 'request';
            $msg    = __( 'Player registration pending', 'racketmanager' );
            $status = 'pending';
        }
        if ( ! empty( $options['rosterConfirmationEmail'] ) ) {
            $headers = array();
            $user    = $this->player_repository->find( $registered_by_user_id );
            if ( ! empty( $club->match_secretary->id ) && $club->match_secretary->id !== $user->ID ) {
                $headers[] = RACKETMANAGER_CC_EMAIL . $club->match_secretary->display_name . ' <' . $club->match_secretary->email . '>';
            }
            $email_to                  = $user->display_name . ' <' . $user->user_email . '>';
            $message_args              = array();
            $message_args['requestor'] = $user->display_name;
            $message_args['action']    = $action;
            $message_args['club']      = $club->shortcode;
            $message_args['player']    = $player->display_name;
            $message_args['btm']       = empty( $player->btm ) ? null : $player->btm;
            $headers[]                 = RACKETMANAGER_FROM_EMAIL . $this->racketmanager->site_name . ' <' . $options['rosterConfirmationEmail'] . '>';
            $headers[]                 = RACKETMANAGER_CC_EMAIL . $this->racketmanager->site_name . ' <' . $options['rosterConfirmationEmail'] . '>';
            $subject                   = $this->racketmanager->site_name . ' - ' . $msg . ' - ' . $club->shortcode;
            $message                   = club_players_notification( $message_args );
            $this->mailer->send( $email_to, $subject, $message, $headers );
        }

        $this->event_dispatcher?->dispatch(
            new Club_Player_Registered_Event(
                club_id: $club_id,
                player_id: $player->id,
                registered_by_user_id: $registered_by_user_id,
                status: $status
            )
        );

        return $msg;
    }

    /**
     * Approve registration for the club
     *
     * @param int $registration_id registration id.
     * @param int $approving_user approving user id.
     *
     * @return void
     * @throws Registration_Not_Found_Exception If the registration cannot be found.
     */
    public function approve_registration( int $registration_id, int $approving_user ): void {
        $registration = $this->club_player_repository->find( $registration_id );
        if ( ! $registration ) {
            throw new Registration_Not_Found_Exception( esc_html__( 'Registration not found to approve', 'racketmanager' ) );
        }
        $registration->approve( $approving_user, $this->clock->now() );
        $this->club_player_repository->save( $registration );
    }

    /**
     * Removes a player registration by locating it, marking it as approved by the removing user, and saving the updated registration.
     *
     * @param int $registration_id Registration identifier.
     * @param int $removing_user User identifier performing the removal.
     *
     * @return void
     *
     * @throws Registration_Not_Found_Exception If the registration cannot be found.
     */
    public function remove_registration( int $registration_id, int $removing_user ): void {
        $registration = $this->club_player_repository->find( $registration_id );
        if ( ! $registration ) {
            throw new Registration_Not_Found_Exception( esc_html__( 'Registration not found to remove', 'racketmanager' ) );
        }
        $registration->approve( $removing_user, $this->clock->now() );
        $this->club_player_repository->save( $registration );
    }

    /**
     * Retrieves registered club players matching the supplied filters and returns them as club player DTOs.
     *
     * @param string|null $active Active state filter.
     * @param string|null $status Registration status filter.
     * @param int|null    $club_id Club identifier filter.
     * @param string|null $gender Gender filter.
     * @param bool        $system Whether to include system registrations.
     * @param int|null    $max_age Maximum age filter.
     * @param int|null    $min_age Minimum age filter.
     *
     * @return array List of registered club player DTOs.
     */
    public function get_registered_players_list( ?string $active = null, ?string $status = null, ?int $club_id = null, ?string $gender = null, bool $system = false, ?int $max_age = null, ?int $min_age = null ): array {
        $players = $this->player_repository->find_club_players_with_details( $club_id, $status, $gender, $active, $system, $max_age, $min_age );

        return array_map(
            function ( $p ) {
                return $this->create_club_player_dto( $p->registration_id );
            },
            $players
        );
    }

    /**
     * Retrieves club player registrations for a player and converts them to DTOs.
     *
     * @param int         $player_id Player identifier.
     * @param string|null $status Registration status to filter by, or null to include all statuses.
     *
     * @return array List of club player DTOs for the player.
     */
    public function get_clubs_for_player( int $player_id, ?string $status = 'active' ): array {
        $registrations = $this->club_player_repository->find_by_player( $player_id, $status );
        return array_map( fn ( $registration ) => $this->create_club_player_dto( $registration->id ), $registrations );
    }

    /**
     * Creates a club player data transfer object for the specified registration, including related player, club, and user display names.
     *
     * @param int $registration_id Registration id.
     */
    private function create_club_player_dto( int $registration_id ): Club_Player_DTO {
        $registration       = $this->club_player_repository->find( $registration_id );
        $player             = $this->player_repository->find( $registration->player_id );
        $club               = $this->club_repository->find_by_id( $registration->club_id );
        $registered_by_name = null;
        $removed_by_name    = null;
        $approved_by_name   = null;
        if ( ! empty( $registration->requested_user ) ) {
            $registered_by      = $this->player_repository->find( $registration->requested_user );
            $registered_by_name = $registered_by?->display_name;
        }
        if ( ! empty( $registration->removed_user ) ) {
            $removed_by      = $this->player_repository->find( $registration->removed_user );
            $removed_by_name = $removed_by?->display_name;
        }
        if ( ! empty( $registration->requested_user ) ) {
            $approved_by      = $this->player_repository->find( $registration->requested_user );
            $approved_by_name = $approved_by?->display_name;
        }

        return new Club_Player_DTO( $player, $club, $registration, $registered_by_name, $removed_by_name, $approved_by_name );
    }

    /**
     * Retrieves a club player registration for the specified club and player.
     *
     * @param int $club_id Club identifier.
     * @param int $player_id Player identifier.
     *
     * @return Club_Player_DTO|null Club player data transfer object, or null if no registration exists.
     *
     * @throws Club_Not_Found_Exception If the club cannot be found.
     * @throws Player_Not_Found_Exception If the player cannot be found.
     */
    public function get_player_for_club( int $club_id, int $player_id ): ?Club_Player_DTO {
        $club = $this->club_repository->find_by_id( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( esc_html__( 'Club not found', 'racketmanager' ) );
        }
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( esc_html__( 'Player not found', 'racketmanager' ) );
        }
        $registration = $this->club_player_repository->find_by_club_and_player( $club_id, $player_id );
        if ( ! $registration ) {
            return null;
        }

        return $this->create_club_player_dto( $registration->id );
    }

    /**
     * Determines whether a player has an approved active registration in a club.
     *
     * @param int $club_id Club identifier.
     * @param int $player_id Player identifier.
     *
     * @return bool True if the player has an approved registration in the club, false otherwise.
     */
    public function is_player_active_in_club( int $club_id, int $player_id ): bool {
        $registration = $this->club_player_repository->find_by_club_and_player( $club_id, $player_id );
        if ( ! $registration ) {
            return false;
        }

        return $registration->get_status() === 'approved';
    }

    /**
     * Retrieves configured dummy players for a club, grouped by dummy player type and gender.
     *
     * @param int $club_id Club identifier.
     *
     * @return array Dummy players indexed by type and gender.
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
