<?php

namespace Racketmanager\Services;

use Exception;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Club_Role;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Util\Util_Lookup;
use stdClass;

class Club_Management_Service {
    private Club_Role_Repository $club_role_repository;
    private Club_Repository $club_repository;

    public function __construct( Club_Repository $club_repository, Club_Role_Repository $club_role_repository ) {
        $this->club_repository      = $club_repository;
        $this->club_role_repository = $club_role_repository;
    }
    public function add_club( object $club ): Club {
        $club = new Club( $club );
        $this->club_repository->save( $club );
        return $club;
    }

    public function update_club( int $id, Club $club_updated ): Club|bool {
        $club = $this->club_repository->find( $id );
        if ( ! $club ) {
            return false;
        }
        $updates = false;
        if ( $club_updated->name !== $club->name ) {
            $club->set_name( $club_updated->name );
            $updates    = true;
        }
        if ( $club_updated->shortcode !== $club->shortcode ) {
            $this->update_club_teams( $club->id, $club->shortcode, $club_updated->shortcode );
            $club->set_shortcode( $club_updated->shortcode );
            $updates = true;
        }
        if ( $club_updated->type !== $club->type ) {
            $club->set_type( $club_updated->type );
            $updates    = true;
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
            $team      = get_team( $team->id );
            $team_ref  = substr( $team->title, strlen( $old_shortcode ) + 1, strlen( $team->title ) );
            $new_title = $shortcode . ' ' . $team_ref;
            $team->update_title( $new_title );
        }

    }
    public function reassign_role_user( $club_role_id, $user_id ): ?Club_Role {
        $club_role = $this->club_role_repository->find( $club_role_id );
        // Update the state and save it
        $club_role->set_user_id( $user_id );
        $this->club_role_repository->save($club_role);

        return $club_role;
    }

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
        $newRole = new Club_Role( $club_role );
        // Persist it via repository
        $this->club_role_repository->save( $newRole );
        return $newRole;
    }
    public function remove_club_role( mixed $role_id ): void {
        $club_role = $this->club_role_repository->find( $role_id );
        if ( $club_role ) {
            $this->club_role_repository->delete( array( 'role_id' => $role_id ) );
        }
    }
}
