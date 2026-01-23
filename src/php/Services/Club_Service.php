<?php
/**
 * Club_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Exception;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Club_Role;
use Racketmanager\Domain\DTO\Club_Details_DTO;
use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\Club_Has_Teams_Exception;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_team;

/**
 * Class to implement the Club Management Service
 */
class Club_Service {
    private Club_Role_Repository $club_role_repository;
    private Club_Repository $club_repository;
    private Registration_Repository $club_player_repository;
    private Player_Repository $player_repository;
    private Team_Repository $team_repository;
    private Player_Service $player_service;

    /**
     * Constructor
     *
     */
    public function __construct( Club_Repository $club_repository, Registration_Repository $club_player_repository, Club_Role_Repository $club_role_repository, Player_Repository $player_repository, Team_Repository $team_repository, Player_Service $player_service ) {
        $this->club_repository        = $club_repository;
        $this->club_role_repository   = $club_role_repository;
        $this->club_player_repository = $club_player_repository;
        $this->player_repository      = $player_repository;
        $this->team_repository        = $team_repository;
        $this->player_service         = $player_service;
    }

    /**
     * Add a new club
     *
     * @param object $club
     *
     * @return Club
     */
    public function add_club( object $club ): Club {
        $club = new Club( $club );
        $this->club_repository->save( $club );

        return $club;
    }

    /**
     * Update club details
     *
     * @param int $id
     * @param object $club_updated
     *
     * @return Club|bool
     */
    public function update_club( int $id, object $club_updated ): Club|bool {
        $club = $this->club_repository->find( $id );
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
     * Function to update club team name where the club shortcode has changed
     *
     * @param int $club_id
     * @param string $old_shortcode
     * @param string $shortcode
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
     * Get club by ID
     *
     * @param $club_id
     *
     * @return Club
     */
    public function get_club( $club_id ): Club {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util::club_not_found( $club_id ) );
        }

        return $club;
    }

    /**
     * Get club by shortcode
     *
     * @param $club_id
     *
     * @return Club
     */
    public function get_club_by_shortcode( $club_id ): Club {
        $club = $this->club_repository->find( $club_id, 'shortcode' );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( sprintf( __( 'Club with shortcode %s not found', 'racketmanager' ), $club_id ) );
        }
        return $club;
    }

    /**
     * Remove a club
     *
     * @param int $club_id
     *
     * @return void
     * @throws Exception
     */
    public function remove_club( int $club_id ): void {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util::club_not_found( $club_id ) );
        }
        if ( $this->team_repository->has_teams( $club_id ) ) {
            throw new Club_Has_Teams_Exception( sprintf( __( 'Unable to delete %s - still has teams', 'racketmanager' ), $club->get_name() ) );
        }
        $this->club_player_repository->delete_for_club( $club_id );
        $this->club_role_repository->delete_for_club( $club_id );
        $this->club_repository->delete( $club_id );
    }

    /**
     * Get clubs
     *
     * @param array $args
     *
     * @return array|int
     */
    public function get_clubs( array $args = array() ): array|int {
        return $this->club_repository->find_all( $args );
    }

    /**
     * Get clubs with details
     *
     * @param array $args
     *
     * @return array
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
     * Function to set a club role for a user
     *
     * @param int $club_id
     * @param int $role_id
     * @param int $user_id
     *
     * @return bool|Club_Role
     */
    public function set_club_role( int $club_id, int $role_id, int $user_id ): bool|Club_Role {
        $role_details = Util_Lookup::get_club_role( $role_id );
        if ( $role_details ) {
            if ( 1 === $role_details->limit ) {
                $club_role = $this->club_role_repository->search( array( 'club' => $club_id, 'role' => $role_id ) );
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
     * Function to reassign a club role to a user
     *
     * @param $club_role_id
     * @param $user_id
     *
     * @return Club_Role|null
     */
    public function reassign_role_user( $club_role_id, $user_id ): ?Club_Role {
        $club_role = $this->club_role_repository->find( $club_role_id );
        // Update the state and save it
        $club_role->set_user_id( $user_id );
        $this->club_role_repository->save( $club_role );

        return $club_role;
    }

    /**
     * Function to add a role for a club
     *
     * @param int $club_id
     * @param int $role_id
     * @param int $user_id
     *
     * @return Club_Role
     */
    public function add_club_role( int $club_id, int $role_id, int $user_id ): Club_Role {
        $club_role          = new stdClass();
        $club_role->club_id = $club_id;
        $club_role->role_id = $role_id;
        $club_role->user_id = $user_id;
        // Create the new entity locally
        $new_role = new Club_Role( $club_role );
        // Persist it via repository
        $this->club_role_repository->save( $new_role );

        return $new_role;
    }

    /**
     * Function to remove a club role
     *
     * @param int $club_role_id
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
     * Get roles for a club
     *
     * @param int|null $club_id
     *
     * @return array
     */
    public function get_roles_for_club( ?int $club_id ): array {
        return $this->club_role_repository->get_roles_for_club( $club_id );
    }

    /**
     * Get a club role by ID
     *
     * @param int|null $role_id
     *
     * @return Club_Role|null
     */
    public function get_role( ?int $role_id ): ?Club_Role {
        if ( empty( $role_id ) ) {
            throw new Role_Assignment_Not_Found_Exception( __('No role ID provided', 'racketmanager' ) );
        }
        $role =  $this->club_role_repository->find( $role_id );
        if ( empty( $role ) ) {
            throw new Role_Assignment_Not_Found_Exception( __('Role ID %s not found', 'racketmanager' ), $role_id );
        }
        return $role;
    }
    /**
     * Get club details
     *
     * @param int $club_id
     *
     * @return Club_Details_DTO
     */
    public function get_club_details( int $club_id ): Club_Details_DTO {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util::club_not_found( $club_id ) );
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
     * @return bool True if the user holds the specific role, false otherwise.
     */
    public function is_user_match_secretary( int $user_id, int $club_id ): bool {
        // Ensure user and club exist (optional but good practice)
        if ( ! $this->club_repository->find( $club_id ) ) {
            return false;
        }
        $role_assignment = $this->club_role_repository->search( array( 'club' => $club_id, 'role' => 1, 'user' => $user_id ) );

        // If a role object is returned, the assignment exists
        return $role_assignment !== null;
    }

    /**
     * Can user update as match secretary or admin user?
     *
     * @param int $club_id
     *
     * @return bool
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
     * Can the user update as captain (includes match secretary or admin user)?
     *
     * @param int $club_id
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
     * Can the user update players?
     *
     * @param int $club_id
     *
     * @return bool
     */
    public function can_user_update_players( int $club_id ): bool {
        global $racketmanager;
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
                    $options = $racketmanager->get_options( 'rosters' );
                    if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] ) {
                        $user_can_update = true;
                    }
                }
            }
        }
        return $user_can_update;
    }

    /**
     * Check if a player is a captain
     *
     * @param int $club_id
     * @param int $player player id.
     *
     * @return bool
     */
    public function is_player_captain( int $club_id, int $player ): bool {
        // Ensure user and club exist (optional but good practice)
        if ( ! $this->club_repository->find( $club_id ) ) {
            return false;
        }
        if ( ! $this->player_repository->find( $player ) ) {
            return false;
        }

        // If a player object is returned, the assignment exists
        return $this->team_repository->find_captain( $club_id, $player );
    }

    /**
     * Creates a new team with an automatically generated name based on club shortcode and sequence number.
     *
     * @param int|null $club_id The ID of the parent club.
     * @param string $type The type of team (e.g. 'Boys', 'Girls', 'Ladies', 'Mens', 'Mixed').
     *
     * @return Team The newly created Team object.
     */
    public function create_team( ?int $club_id, string $type ): Team {
        $club = $this->club_repository->find( $club_id );
        if ( ! $club ) {
            throw new Club_Not_Found_Exception( Util::club_not_found( $club_id ) );
        }
        $type_name = match( substr( $type, 0, 1 ) ) {
            'B' => __( 'Boys', 'racketmanager' ),
            'G' => __( 'Girls', 'racketmanager' ),
            'W' => __( 'Ladies', 'racketmanager' ),
            'M' => __( 'Mens', 'racketmanager' ),
            'X' => __( 'Mixed', 'racketmanager' ),
            default => null,
        };

        if ( empty( $type_name ) ) {
            throw new Invalid_Argument_Exception( __( 'Invalid team type', 'racketmanager' ) );
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
        $team          = new Team( $team );
        // We assume a save method exists in TeamRepository from a previous step
        $this->team_repository->save( $team );

        return $team;
    }

}
