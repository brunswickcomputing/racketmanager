<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Player;
use Racketmanager\Repositories\Player_Repository;
use Exception;

class Player_Management_Service {
    private Player_Repository $player_repository;

    /**
     * Constructor
     *
     * @param Player_Repository $player_repository
     */
    public function __construct( Player_Repository $player_repository ) {
        $this->player_repository = $player_repository;
    }

    /**
     * Add a new player.
     *
     * @param object $player
     *
     * @return Player
     */
    public function add_player( object $player ): Player {
        $player->display_name    = $player->firstname . ' ' . $player->surname;
        $player->user_registered = gmdate( 'Y-m-d H:i:s' );
        $player->user_login      = strtolower( $player->firstname ) . '.' . strtolower( $player->surname );
        $player->user_pass       = $player->user_login . '1';
        $player                  = new Player( $player );
        $this->player_repository->add( $player );
        return $player;
    }

    /**
     * Update player details
     *
     * @param int $player_id
     * @param object $updated_player
     *
     * @return Player|bool
     * @throws Exception
     */
    public function update_player( int $player_id, object $updated_player ): Player|bool {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            throw new Exception( sprintf( __( 'Player Id %d not found', 'racketmanager' ), $player_id ) );
        }
        $updates = array();
        $updated_player->display_name = $updated_player->firstname . ' ' . $updated_player->surname;
        if ( $updated_player->firstname !== $player->firstname ) {
            $player->set_firstname( $updated_player->firstname );
            $updates['core'] = true;
        }
        if ( $updated_player->surname !== $player->surname ) {
            $player->set_surname( $updated_player->surname );
            $updates['core'] = true;
        }
        if ( $updated_player->display_name !== $player->display_name ) {
            $player->set_display_name( $updated_player->display_name );
            $updates['core'] = true;
        }
        if ( $updated_player->email !== $player->email ) {
            $player->set_email( $updated_player->email );
            $updates['core'] = true;
        }
        if ( $updated_player->gender !== $player->gender ) {
            $player->set_gender( $updated_player->gender );
            $updates['gender'] = true;
        }
        if ( $updated_player->btm !== $player->btm ) {
            $player->set_btm( $updated_player->btm );
            $updates['btm'] = true;
        }
        if ( $updated_player->contactno !== $player->contactno ) {
            $player->set_contactno( $updated_player->contactno );
            $updates['contactno'] = true;
        }
        if ( $updated_player->year_of_birth !== $player->year_of_birth ) {
            $player->set_year_of_birth( $updated_player->year_of_birth );
            $updates['dob'] = true;
        }
        if ( $updated_player->locked !== $player->locked ) {
            if ( $updated_player->locked ) {
                $player->set_locked_date( gmdate( 'Y-m-d' ) );
                $player->set_locked_user( get_current_user_id() );
            }
            $player->set_locked( $updated_player->locked );
            $updates['locked'] = true;
        }
        if ( empty( $updates ) ) {
            return false;
        }
        try {
            $this->player_repository->update( $player, $updates );
        } catch ( Exception $e ) {
            throw new Exception( $e->getMessage() );
        }

        return $player;
    }

    /**
     * Get all players
     *
     * @param array $args
     *
     * @return array
     */
    public function get_all_players( array $args = array() ): array {
        return $this->player_repository->find_all( $args );
    }

    public function find_player_by_btm( string $btm ): Player|null {
        return $this->player_repository->find( $btm, 'btm' );
    }
    /**
     * Update player contact details
     *
     * @param int $player_id
     * @param string $contact_no
     * @param string $contact_email
     *
     * @return Player|bool
     * @throws Exception
     */
    public function update_contact_details( int $player_id, string $contact_no, string $contact_email ): Player|bool {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            return false;
        }
        $updates               = array();
        $current_contact_no    = $player->contactno;
        $current_contact_email = $player->email;
        if ( $current_contact_no !== $contact_no ) {
            $player->set_contactno( $contact_no );
            $updates['contactno'] = true;
        }
        if ( $current_contact_email !== $contact_email ) {
            $player->set_email( $contact_email );
            $updates['core'] = true;
        }
        $this->player_repository->update( $player, $updates );
        return true;
    }

    /**
     * Handle tournament entry personal information
     *
     * @param int $player_id
     * @param int|null $btm
     * @param string|null $contactno
     * @param string|null $contactemail
     *
     * @return bool
     * @throws Exception
     */
    public function handle_tournament_entry_personal_information( int $player_id, ?int $btm, ?string $contactno, ?string $contactemail ): bool {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            return false;
        }
        $updates         = $this->update_btm( $player_id, $btm );
        $contact_updates = $this->update_contact_details( $player_id, $contactno, $contactemail );

        return $updates || $contact_updates;
    }

    /**
     * Update player BTM
     *
     * @param int $player_id
     * @param int $btm
     *
     * @return bool
     * @throws Exception
     */
    public function update_btm( int $player_id, int $btm ): bool {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            return false;
        }
        if ( intval( $player->btm ) !== $btm ) {
            // implement this later
            // if ( empty( $player->btm ) ) {
            //     $this->check_results_warning( 'btm' );
            // }
            $player->set_btm( $btm );
            $updates['btm'] = true;
            $this->player_repository->update( $player, $updates );
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete player
     *
     * @param $player_id
     *
     * @return bool
     * @throws Exception
     */
    public function delete_player( $player_id ): bool {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            throw new Exception( sprintf("Player ID %s not found.", 'racketmanager' ), $player_id );
        }
        wp_cache_flush_group( 'players' );
        if ( $this->player_repository->has_club_associations( $player_id ) ) {
            $updates['removed'] = true;
            $player->set_removed_date( gmdate( 'Y-m-d' ) );
            $player->set_removed_user( get_current_user_id() );
            $this->player_repository->update( $player, $updates );
            return false;
        } else {
            $this->player_repository->delete( $player_id );
            return true;
        }

    }
}
