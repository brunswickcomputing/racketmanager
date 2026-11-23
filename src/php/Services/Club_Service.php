<?php
declare( strict_types=1 );
/**
 * Club_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Factory;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Club\Club_Role_Factory;
use Racketmanager\Domain\DTO\Club\Club_Details_DTO;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Exceptions\Club_Has_Teams_Exception;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Util\Util_Lookup;
use Racketmanager\Util\Util_Messages;
use stdClass;

/**
 * Class to implement the Club Management Service
 */
class Club_Service implements Club_Service_Interface {
    private Club_Role_Repository_Interface $club_role_repository;
    private Club_Repository $club_repository;
    private Registration_Repository_Interface $club_player_repository;
    private Player_Repository $player_repository;
    private Team_Repository $team_repository;
    private Player_Service $player_service;
    private Option_Service $option_service;

    /**
     * Constructor
     *
     * @param Club_Repository                   $club_repository         Club repository.
     * @param Registration_Repository_Interface $club_player_repository  Club player repository.
     * @param Club_Role_Repository_Interface    $club_role_repository    Club role repository.
     * @param Player_Repository                 $player_repository       Player repository.
     * @param Team_Repository                   $team_repository         Team repository.
     * @param Player_Service                    $player_service          Player service.
     * @param Option_Service                    $option_service          Option service.
     */
    public function __construct(
        Club_Repository $club_repository,
        Registration_Repository_Interface $club_player_repository,
        Club_Role_Repository_Interface $club_role_repository,
        Player_Repository $player_repository,
        Team_Repository $team_repository,
        Player_Service $player_service,
        Option_Service $option_service
    ) {
        $this->club_repository        = $club_repository;
        $this->club_role_repository   = $club_role_repository;
        $this->club_player_repository = $club_player_repository;
        $this->player_repository      = $player_repository;
        $this->team_repository        = $team_repository;
        $this->player_service         = $player_service;
        $this->option_service         = $option_service;
    }

    /**
     * Adds a club by converting the supplied object to a Club instance and saving it.
     *
     * @param object $club Club data object to convert and save.
     *
     * @return Club Saved club instance.
     */
    public function add_club( object $club ): Club {
        $club = Club_Factory::from_object( $club );
        $this->club_repository->save( $club );

        return $club;
    }

    /**
     * Updates an existing club with the provided details when changes are detected.
     *
     * @param int    $id Club ID to update.
     * @param object $club_updated Updated club data to compare against the existing club.
     *
     * @return Club|bool Updated club object when changes are saved, false if the club is not found or no updates are required.
     */
    public function update_club( int $id, object $club_updated ): Club|bool {
        $club = $this->club_repository->find_by_id( $id );
        if ( ! $club ) {
            return false;
        }
        $updates = false;
        if ( $club_updated->name !== $club->name ) {
            $club->set_name( $club_updated->name );
            $updates = true;
        }
        if ( $club_updated->shortcode !== $club->shortcode ) {
            $this->update_club_teams( $club->id, $club->shortcode, $club_updated->shortcode );
            $club->set_shortcode( $club_updated->shortcode );
            $updates = true;
        }
        if ( $club_updated->type !== $club->type ) {
            $club->set_type( $club_updated->type );
            $updates = true;
        }
        if ( $club_updated->contactno !== $club->contactno ) {
            $club->set_contact_no( $club_updated->contactno );
            $updates = true;
        }
        if ( $club_updated->website !== $club->website ) {
            $club->set_website( $club_updated->website );
            $updates = true;
        }
        if ( $club_updated->founded !== $club->founded ) {
            $club->set_founded( $club_updated->founded );
            $updates = true;
        }
        if ( $club_updated->facilities !== $club->facilities ) {
            $club->set_facilities( $club_updated->facilities );
            $updates = true;
        }
        if ( $club_updated->address !== $club->address ) {
            $club->set_address( $club_updated->address );
            $updates = true;
        }
        if ( $updates ) {
            $this->club_repository->save( $club );

            return $club;
        }

        return false;
    }

    /**
     * Updates the titles of all teams associated with a club to use the new club shortcode.
     *
     * @param int    $club_id Club ID whose team titles should be updated.
     * @param string $old_shortcode Existing club shortcode currently used in team titles.
     * @param string $shortcode New club shortcode to apply to team titles.
     *
     * @return void
     */
    private function update_club_teams( int $club_id, string $old_shortcode, string $shortcode ): void {
        $teams = $this->club_repository->get_teams( array( 'club' => $club_id ) );
        foreach ( $teams as $team ) {
            $team_ref  = substr( $team->title, strlen( $old_shortcode ) + 1, strlen( $team->title ) );
            $new_title = $shortcode . ' ' . $team_ref;
            $team->set_title( $new_title );
            $this->team_repository->save( $team );
        }
    }

    /**
     * Retrieves a club by its ID.
     *
     * @param mixed $club_id Club ID to retrieve.
     *
     * @return Club Club matching the specified ID.
     *
     * @throws Club_Not_Found_Exception If no club is found for the specified ID.
     */
    public function get_club( mixed $club_id ): Club {
        $club = $this->club_repository->find_by_id( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( esc_html( Util_Messages::club_not_found( $club_id ) ) );
        }

        return $club;
    }

    /**
     * Retrieves a club by its shortcode.
     *
     * @param mixed $club_id Club shortcode used to locate the club.
     *
     * @return Club Club matching the specified shortcode.
     *
     * @throws Club_Not_Found_Exception If no club is found for the specified shortcode.
     */
    public function get_club_by_shortcode( mixed $club_id ): Club {
        $club = $this->club_repository->find_by_id( $club_id, 'shortcode' );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( esc_html( Util_Messages::club_shortcode_not_found( $club_id ) ) );
        }
        return $club;
    }

    /**
     * Removes the specified club and its related player and role records.
     *
     * @param int $club_id Club ID to remove.
     *
     * @return void
     *
     * @throws Club_Not_Found_Exception If the club does not exist.
     * @throws Club_Has_Teams_Exception If the club still has teams assigned.
     */
    public function remove_club( int $club_id ): void {
        $club = $this->club_repository->find_by_id( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( esc_html( Util_Messages::club_not_found( $club_id ) ) );
        }
        if ( $this->team_repository->has_teams( $club_id ) ) {
            // Translators: %s is the name of the club.
            throw new Club_Has_Teams_Exception( esc_html( sprintf( __( 'Unable to delete %s - still has teams', 'racketmanager' ), $club->get_shortcode() ) ) );
        }
        $this->club_player_repository->delete_for_club( $club_id );
        $this->club_role_repository->delete_for_club( $club_id );
        $this->club_repository->delete( $club_id );
    }

    /**
     * Retrieves clubs matching the specified query arguments.
     *
     * @param array $args Optional arguments used to filter, sort, or limit the returned clubs.
     *
     * @return array|int Array of matching clubs, or an integer result depending on the repository query arguments.
     */
    public function get_clubs( array $args = array() ): array|int {
        return $this->club_repository->find_all( $args );
    }

    /**
     * Retrieves clubs with their associated roles and match secretary details.
     *
     * @param array $args Optional arguments used to filter or query clubs.
     *
     * @return array List of club details data transfer objects.
     */
    public function get_clubs_with_details( array $args = array() ): array {
        $clubs              = $this->club_repository->find_all( $args );
        $clubs_with_details = array();
        foreach ( $clubs as $club ) {
            $club_id = $club->get_id();
            $roles   = $this->club_role_repository->get_roles_for_club( $club_id );
            try {
                $match_secretary = $this->player_service->get_match_secretary_details( $club_id );
            } catch ( Role_Assignment_Not_Found_Exception ) {
                $match_secretary = null;
            }
            $clubs_with_details[] = new Club_Details_DTO( $club, $roles, $match_secretary );
        }
        return $clubs_with_details;
    }

    /**
     * Assigns a club role to a user, reassigning limited roles when an existing assignment is found.
     *
     * @param int      $club_id Club ID to assign the role for.
     * @param int|null $role_id Role ID to assign, or null if no role is specified.
     * @param int|null $user_id User ID to assign to the role, or null to leave the role unassigned.
     *
     * @return bool|Club_Role Club role assignment on success, or false if the role details cannot be found.
     */
    public function set_club_role( int $club_id, ?int $role_id, ?int $user_id ): bool|Club_Role {
        $role_details = Util_Lookup::get_club_role( (string) $role_id );
        if ( $role_details ) {
            if ( 1 === $role_details->limit ) {
                $club_role = $this->club_role_repository->search(
                    array(
                        'club' => $club_id,
                        'role' => $role_id,
                    )
                );
                if ( $club_role ) {
                    $club_role = $this->reassign_role_user( $club_role[0]->id, $user_id );
                } else {
                    $club_role = $this->add_club_role( $club_id, $role_id, $user_id );
                }
            } else {
                $club_role = $this->add_club_role( $club_id, $role_id, $user_id );
            }

            return $club_role;
        } else {
            return false;
        }
    }

    /**
     * Reassigns a club role to the specified user.
     *
     * @param mixed $club_role_id Club role ID to reassign.
     * @param mixed $user_id User ID to assign to the club role.
     *
     * @return Club_Role|null Updated club role, or null if the club role was not found.
     */
    public function reassign_role_user( mixed $club_role_id, mixed $user_id ): ?Club_Role {
        $club_role = $this->club_role_repository->find( (int) $club_role_id );
        if ( ! $club_role ) {
            return null;
        }
        // Update the state and save it.
        $club_role->reassign_user( (int) $user_id );
        $this->club_role_repository->save( $club_role );

        return $club_role;
    }

    /**
     * Adds a role assignment for a user within a club and persists it.
     *
     * @param int $club_id Club ID to assign the role to.
     * @param int $role_id Role ID to assign.
     * @param int $user_id User ID receiving the role assignment.
     *
     * @return Club_Role Newly created and persisted club role assignment.
     */
    public function add_club_role( int $club_id, int $role_id, int $user_id ): Club_Role {
        $club_role          = new stdClass();
        $club_role->club_id = $club_id;
        $club_role->role_id = $role_id;
        $club_role->user_id = $user_id;
        // Create the new entity locally.
        $new_role = Club_Role_Factory::from_object( $club_role );
        // Persist it via repository.
        $this->club_role_repository->save( $new_role );

        return $new_role;
    }

    /**
     * Removes a club role if it exists.
     *
     * @param int $club_role_id Club role ID to remove.
     *
     * @return void
     */
    public function remove_club_role( int $club_role_id ): void {
        $club_role = $this->club_role_repository->find( $club_role_id );
        if ( $club_role ) {
            $this->club_role_repository->delete_for_role( $club_role_id );
        }
    }

    /**
     * Retrieves the roles associated with the specified club.
     *
     * @param int|null $club_id Club ID to retrieve roles for, or null to retrieve roles not limited to a specific club.
     *
     * @return array Roles associated with the club.
     */
    public function get_roles_for_club( ?int $club_id ): array {
        return $this->club_role_repository->get_roles_for_club( $club_id );
    }

    /**
     * Retrieves a club role by its ID.
     *
     * @param int|null $role_id Role ID to retrieve.
     *
     * @return Club_Role|null Club role matching the supplied ID.
     * @throws Role_Assignment_Not_Found_Exception If the role cannot be found.
     */
    public function get_role( ?int $role_id ): ?Club_Role {
        if ( empty( $role_id ) ) {
            throw new Role_Assignment_Not_Found_Exception( esc_html__( 'No role ID provided', 'racketmanager' ) );
        }
        $role = $this->club_role_repository->find( $role_id );
        if ( empty( $role ) ) {
            // Translators: %s is the role ID.
            throw new Role_Assignment_Not_Found_Exception( esc_html( sprintf( __( 'Role ID %s not found', 'racketmanager' ), $role_id ) ) );
        }
        return $role;
    }

    /**
     * Retrieves detailed information for a club, including its roles and match secretary details when available.
     *
     * @param int $club_id Club ID to retrieve details for.
     *
     * @return Club_Details_DTO Club details data transfer object.
     *
     * @throws Club_Not_Found_Exception If the club cannot be found.
     */
    public function get_club_details( int $club_id ): Club_Details_DTO {
        $club = $this->club_repository->find_by_id( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( esc_html( Util_Messages::club_not_found( $club_id ) ) );
        }
        $roles = $this->club_role_repository->get_roles_for_club( $club_id );
        try {
            $match_secretary = $this->player_service->get_match_secretary_details( $club_id );
        } catch ( Role_Assignment_Not_Found_Exception ) {
            $match_secretary = null;
        }
        return new Club_Details_DTO( $club, $roles, $match_secretary );
    }
    /**
     * Checks if a specific WordPress user is assigned the 'Match Secretary' role for a club.
     *
     * @param int $user_id The WordPress User id.
     * @param int $club_id The Club id.
     *
     * @return bool True if the user holds the specific role, false otherwise.
     */
    public function is_user_match_secretary( int $user_id, int $club_id ): bool {
        // Ensure user and club exist (optional but good practice).
        if ( ! $this->club_repository->find_by_id( $club_id ) ) {
            return false;
        }
        $role_assignment = $this->club_role_repository->search(
            array(
                'club' => $club_id,
                'role' => \Racketmanager\Domain\Enums\Club_Role::MATCH_SECRETARY->value,
                'user' => $user_id,
            )
        );

        // If a role object is returned, the assignment exists.
        return null !== $role_assignment;
    }

    /**
     * Determines whether the current logged-in user can update the specified club.
     *
     * @param int $club_id Club ID to check update permissions for.
     *
     * @return bool True if the current user can update the club, false otherwise.
     */
    public function can_user_update( int $club_id ): bool {
        $user_can_update = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_can_update = true;
            } else {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( $this->is_user_match_secretary( $club_id, $userid ) ) {
                    $user_can_update = true;
                }
            }
        }
        return $user_can_update;
    }

    /**
     * Can user update as captain, match secretary, or admin user?
     *
     * @param int $club_id Club ID.
     *
     * @return bool
     */
    public function can_user_update_as_captain( int $club_id ): bool {
        $user_can_update = $this->can_user_update( $club_id );
        if ( ! $user_can_update ) {
            $user            = wp_get_current_user();
            $userid          = $user->ID;
            $user_can_update = $this->is_player_captain( $club_id, $userid );
        }
        return $user_can_update;
    }

    /**
     * Can user update player rosters as an administrator, match secretary, or permitted captain?
     *
     * @param int $club_id Club ID.
     *
     * @return bool True if the current user can update players, false otherwise.
     */
    public function can_user_update_players( int $club_id ): bool {
        $user_can_update = false;
        if ( is_user_logged_in() ) {
            if ( current_user_can( 'manage_racketmanager' ) ) {
                $user_can_update = true;
            } else {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( $this->is_user_match_secretary( $club_id, $userid ) ) {
                    $user_can_update = true;
                } elseif ( $this->is_player_captain( $club_id, $userid ) ) {
                    $options = $this->option_service->get_options( 'rosters' );
                    if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] ) {
                        $user_can_update = true;
                    }
                }
            }
        }
        return $user_can_update;
    }

    /**
     * Check if a player is a captain.
     *
     * @param int $club_id The ID of the club.
     * @param int $player The ID of the player.
     *
     * @return bool
     */
    public function is_player_captain( int $club_id, int $player ): bool {
        // Ensure user and club exist (optional but good practice).
        if ( ! $this->club_repository->find_by_id( $club_id ) ) {
            return false;
        }
        if ( ! $this->player_repository->find( $player ) ) {
            return false;
        }

        // If a player object is returned, the assignment exists.
        return $this->team_repository->find_captain( $club_id, $player );
    }

    /**
     * Creates a new team with an automatically generated name based on club shortcode and sequence number.
     *
     * @param int|null $club_id The ID of the parent club.
     * @param string   $type The type of team (e.g. 'Boys', 'Girls', 'Ladies', 'Mens', 'Mixed').
     *
     * @return Team The newly created Team object.
     * @throws Club_Not_Found_Exception When the club is not found.
     * @throws Invalid_Argument_Exception When the team type is invalid.
     */
    public function create_team( ?int $club_id, string $type ): Team {
        $club = $this->club_repository->find_by_id( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( esc_html( Util_Messages::club_not_found( $club_id ) ) );
        }
        $type_name = match ( substr( $type, 0, 1 ) ) {
            'B' => __( 'Boys', 'racketmanager' ),
            'G' => __( 'Girls', 'racketmanager' ),
            'W' => __( 'Ladies', 'racketmanager' ),
            'M' => __( 'Mens', 'racketmanager' ),
            'X' => __( 'Mixed', 'racketmanager' ),
            default => null,
        };

        if ( empty( $type_name ) ) {
            throw new Invalid_Argument_Exception( esc_html( Util_Messages::invalid_team_type() ) );
        }

        // 1. Get the next available sequence number
        $next_sequence_number = $this->team_repository->find_next_sequence_number( $club->get_shortcode(), $type_name );

        // 2. Generate the team name
        $team_name = $club->get_shortcode() . ' ' . $type_name . ' ' . $next_sequence_number;

        // 3. Create the Domain Entity and Save it
        $team          = new stdClass();
        $team->title   = $team_name;
        $team->stadium = $club->get_shortcode();
        $team->club_id = $club->get_id();
        $team->type    = $type;
        $team          = Team_Factory::from_object( $team );
        // We assume a save method exists in TeamRepository from a previous step.
        $this->team_repository->save( $team );

        return $team;
    }
}
