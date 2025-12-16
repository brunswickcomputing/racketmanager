<?php
/**
 * Player__Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Exception;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Player_Error;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\LTA_System_Not_Available_Exception;
use Racketmanager\Exceptions\LTA_Tennis_Number_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Updated_Exception;
use Racketmanager\Exceptions\Role_Assignment_Not_Found_Exception;
use Racketmanager\Exceptions\WTN_Error_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Player_Error_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Services\Contracts\Wtn_Api_Client_Interface;
use Racketmanager\Services\Validator\Validator;
use stdClass;
use WP_Error;
use function Racketmanager\get_club;

/**
 * Class to implement the Player Management Service
 */
class Player_Service {
    private Player_Repository $player_repository;
    private ?RacketManager $racketmanager;
    private ?Player_Error_Repository $player_error_repository;
    /**
     * @var mixed|null
     */
    private null|Wtn_Api_Client_Interface $wtn_api_client;
    private ?Club_Role_Repository $club_role_repository;
    private League_Team_Repository $league_team_repository;
    private Club_Repository $club_repository;
    private Registration_Repository $registration_repository;

    /**
     * Constructor
     *
     * @param $plugin_instance
     * @param Player_Repository $player_repository
     * @param Player_Error_Repository $player_error_repository
     * @param Club_Role_Repository $club_role_repository
     * @param Wtn_Api_Client_Interface $wtn_api_client
     * @param League_Team_Repository $league_team_repository
     * @param Club_Repository $club_repository
     * @param Registration_Repository $registration_repository
     */
    public function __construct( $plugin_instance, Player_Repository $player_repository, Player_Error_Repository $player_error_repository, Club_Role_Repository $club_role_repository, Wtn_Api_Client_Interface $wtn_api_client, League_Team_Repository $league_team_repository, Club_Repository $club_repository, Registration_Repository $registration_repository ) {
        $this->racketmanager           = $plugin_instance;
        $this->player_repository       = $player_repository;
        $this->player_error_repository = $player_error_repository;
        $this->club_role_repository    = $club_role_repository;
        $this->wtn_api_client          = $wtn_api_client;
        $this->league_team_repository  = $league_team_repository;
        $this->club_repository         = $club_repository;
        $this->registration_repository = $registration_repository;
    }

    public function add_new_player(): Player|WP_Error {
        $player = $this->validate_player();
        if ( is_wp_error( $player ) ) {
            return $player;
        }
        if ( empty( $player->id ) ) {
            return $this->add_player( $player );
        }
        throw new Player_Exists_Exception( __( 'Player already exists', 'racketmanager' ) );
    }

    /**
     * Validate player details
     *
     * @return stdClass|WP_Error
     */
    public function validate_player(): stdClass|WP_Error {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $player_id     = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
        $firstname     = isset( $_POST['firstname'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) ) : null;
        $surname       = isset( $_POST['surname'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['surname'] ) ) ) : null;
        $gender        = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : null;
        $btm           = isset( $_POST['btm'] ) ? sanitize_text_field( wp_unslash( $_POST['btm'] ) ) : null;
        $contactno     = empty( $_POST['contactno'] ) ? null : sanitize_text_field( wp_unslash( $_POST['contactno'] ) );
        $email         = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : null;
        $locked        = isset( $_POST['locked'] );
        $year_of_birth = empty( $_POST['year_of_birth'] ) ? null : intval( $_POST['year_of_birth'] );
        $validator     = new Validator();
        $validator     = $validator->first_name( $firstname );
        $validator     = $validator->surname( $surname );
        $validator     = $validator->gender( $gender );
        if ( empty( $validator->error ) && empty( $player_id ) ) {
            $name            = $firstname . ' ' . $surname;
            $existing_player = $this->player_repository->find( $name, 'name' );
            if ( $existing_player ) {
                $player_id = $existing_player->get_id();
            }
        }
        $validator = $validator->btm( intval( $btm ), $player_id );
        $validator = $validator->email( $email, $player_id, false );
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        $player                = new stdClass();
        $player->id            = $player_id;
        $player->firstname     = $firstname;
        $player->surname       = $surname;
        $player->fullname      = $firstname . ' ' . $surname;
        $player->user_login    = strtolower( $firstname ) . '.' . strtolower( $surname );
        $player->email         = $email;
        $player->btm           = $btm;
        $player->contactno     = $contactno;
        $player->gender        = $gender;
        $player->locked        = $locked;
        $player->year_of_birth = $year_of_birth;
        if ( ! empty( $validator->error ) ) {
            $validator->err->add_data( $player, 'player' );

            return $validator->err;
        } else {
            return $player;
        }
    }

    /**
     * Add a new player.
     *
     * @param object $player
     *
     * @return Player
     */
    public function add_player( object $player ): Player {
        if ( $this->player_repository->find_by_email( $player->email ) ) {
            throw new Duplicate_Email_Exception( __( 'A player with this email address already exists', 'racketmanager' ) );
        }
        if ( $this->player_repository->find_by_btm( $player->btm ) ) {
            throw new Duplicate_Btm_Exception( __( 'A player with the LTA Tennis number already exists', 'racketmanager' ) );
        }
        $player->display_name    = $player->firstname . ' ' . $player->surname;
        $player->user_registered = gmdate( 'Y-m-d H:i:s' );
        $player->user_login      = strtolower( $player->firstname ) . '.' . strtolower( $player->surname );
        $player->user_pass       = $player->user_login . '1';
        $player                  = new Player( $player );
        $this->player_repository->add( $player );

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

    /**
     * Get player by ID
     *
     * @param int|null $id
     *
     * @return Player
     */
    public function get_player( ?int $id ): Player {
        $player = $this->player_repository->find( $id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( sprintf( __( 'Player not found', 'racketmanager' ), $id ) );
        }
        return $player;
    }

    /**
     * Get player by LTA tennis number
     *
     * @param string $btm
     *
     * @return Player|null
     */
    public function find_player_by_btm( string $btm ): Player|null {
        return $this->player_repository->find( $btm, 'btm' );
    }

    /**
     * Get a player by name
     *
     * @param string $name
     *
     * @return Player|null
     */
    public function get_player_by_name( string $name ): Player|null {
        return $this->player_repository->find( $name, 'name' );
    }

    /**
     * Delete player
     *
     * @param $player_id
     *
     * @return bool
     * @throws Player_Not_Found_Exception|Exception
     */
    public function delete_player( $player_id ): bool {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( sprintf( "Player ID %s not found.", 'racketmanager' ), $player_id );
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

    /**
     * Amend player details
     *
     * @param int|null $player_id
     *
     * @return Player|WP_Error
     * @throws Exception
     */
    public function amend_player_details( ?int $player_id ): Player|WP_Error {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( sprintf( __( 'Player Id %d not found', 'racketmanager' ), $player_id ) );
        }
        $player = $this->validate_player();
        if ( is_wp_error( $player ) ) {
            return $player;
        }
        try {
            return $this->update_player( $player->id, $player );
        } catch ( Player_Not_Found_Exception $e ) {
            throw new Player_Not_Found_Exception( $e->getMessage() );
        } catch ( Player_Not_Updated_Exception $e ) {
            throw new Player_Not_Updated_Exception( $e->getMessage() );
        } catch ( Exception $e ) {
            throw new Exception( $e->getMessage() );
        }
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
            throw new Player_Not_Found_Exception( sprintf( __( 'Player Id %d not found', 'racketmanager' ), $player_id ) );
        }
        $updates                      = array();
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
            throw new Player_Not_Updated_Exception( __( 'No changes to update', 'racketmanager' ) );
        }
        try {
            $this->player_repository->update( $player, $updates );
        } catch ( Exception $e ) {
            throw new Exception( $e->getMessage() );
        }

        return $player;
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
     * Schedule player ratings for a club
     *
     * @param int|null $club_id
     *
     * @return void
     */
    public function schedule_player_ratings( ?int $club_id = null ): void {
        if ( ! $club_id ) {
            throw new Club_Not_Found_Exception( __( 'Club Id not provided', 'racketmanager' ) );
        }
        $schedule_name   = 'rm_calculate_player_ratings';
        $schedule_args[] = $club_id;
        wp_schedule_single_event( time(), $schedule_name, $schedule_args );
    }

    /**
     * Calculate player ratings
     *
     * Moved from RacketManager to the Player__Service to better align
     * responsibility with the player domain. This method gathers the list of
     * registered, active players (optionally scoped to a club) and triggers the
     * WTN update workflow in the plugin core.
     *
     * @param int|null $club_id Club id to scope the player list. Null for all clubs.
     *
     * @return void
     */
    public function calculate_player_ratings( int $club_id = null ): void {
        $wtn_list = array();
        if ( $club_id ) {
            // Active players only; restrict to club if provided.
            $player_ids = $this->player_repository->find_player_ids_by_club( $club_id );
        } else {
            // Sync all players who have a BTM number (our proxy for a "player" user)
            $player_ids = get_users( array(
                'meta_key' => Player_Repository::META_KEY_BTM,
                'fields'   => 'ID',
            ) );
        }
        if ( $player_ids ) {
            foreach ( $player_ids as $player ) {
                if ( empty( $player->btm ) ) {
                    $wtn_list[] = $player;
                }
            }
        }
        if ( $wtn_list ) {
            try {
                $this->wtn_api_client->prepare_env();
                $this->get_wtn_for_players( $wtn_list, $club_id );
            } catch ( LTA_System_Not_Available_Exception $e ) {
                error_log( $e->getMessage() );
            }
        }
    }

    /**
     * Get the latest WTN for a list of players
     *
     * @param $players
     * @param $club_id
     *
     * @return void
     */
    private function get_wtn_for_players( $players, $club_id = null ): void {
        $error_count = 0;
        $btm_missing = 0;
        foreach ( $players as $player ) {
            try {
                $this->get_latest_wtn( $player );
            } catch ( WTN_Error_Exception ) {
                ++ $error_count;
            } catch ( LTA_Tennis_Number_Not_Found_Exception ) {
                ++ $btm_missing;
            }
        }
        if ( $club_id ) {
            $club = get_club( $club_id );
        } else {
            $club = null;
        }
        $headers           = array();
        $headers[]         = 'From: ' . $this->racketmanager->admin_email;
        $organisation_name = $this->racketmanager->site_name;
        $email_subject     = $this->racketmanager->site_name . ' - ' . __( 'WTN Update', 'racketmanager' );
        if ( $club ) {
            $email_subject .= ' - ' . $club->shortcode;
        }
        $email_to      = $this->racketmanager->admin_email;
        $email_message = $this->racketmanager->shortcodes->load_template(
            'wtn-report', array(
            'error_count'   => $error_count,
            'club'          => $club,
            'player_count'  => count( $players ),
            'email_subject' => $email_subject,
            'organisation'  => $organisation_name,
        ),
            'email',
        );
        wp_mail( $email_to, $email_subject, $email_message, $headers );
    }

    /**
     * Get the latest WTN for a player
     *
     * @param int|null $player_id
     *
     * @return void
     */
    public function get_latest_wtn( ?int $player_id ): void {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( sprintf( __( 'Player Id %d not found', 'racketmanager' ), $player_id ) );
        }
        if ( empty( $player->get_btm() ) ) {
            throw new LTA_Tennis_Number_Not_Found_Exception( sprintf( __( 'LTA Tennis number not found for %s', 'racketmanager' ), $player->display_name ) );
        }
        try {
            $this->wtn_api_client->prepare_env();
            $response = $this->wtn_api_client->fetch_player_wtn( $player );
            $this->player_error_repository->delete_for_player( $player->id );
            if ( $response['status'] ) {
                $wtn = $response['value'];
                $player->set_wtn( $wtn );
                $this->player_repository->update( $player, array( 'wtn' => true ) );
            } else {
                $message                    = $response['message'];
                $player_error               = new stdClass();
                $player_error->player_id    = $player->id;
                $player_error->message      = $message;
                $player_error->created_date = current_time( 'mysql' );
                $player_error               = new Player_Error( $player_error );
                $this->player_error_repository->save( $player_error );
                throw new WTN_Error_Exception( $message );
            }
        } catch ( LTA_System_Not_Available_Exception $e ) {
            throw new LTA_System_Not_Available_Exception( $e->getMessage() );
        }
    }

    /**
     * Get player errors
     *
     * @param $status
     *
     * @return array
     */
    public function get_player_errors( $status = null ): array {
        return $this->player_error_repository->find_all_with_details( $status );
    }

    /**
     * Remove player error
     *
     * @param $id
     *
     * @return void
     */
    public function remove_player_error( $id ): void {
        $this->player_error_repository->delete( $id );
    }

    /**
     * Get titles for player
     *
     * @param int|null $player_id
     *
     * @return array
     */
    public function get_titles_for_player( ?int $player_id ): array {
        $player = $this->player_repository->find( $player_id );
        if ( ! $player ) {
            throw new Player_Not_Found_Exception( sprintf( __( 'Player Id %d not found', 'racketmanager' ), $player_id ) );
        }
        return $this->player_repository->get_titles( $player_id );
    }

    /**
     * Get match secretary details for a club
     *
     * @param int $club_id
     *
     * @return Player
     */
    public function get_match_secretary_details( int $club_id ): Player {
        $roles             = $this->club_role_repository->search( array( 'club' => $club_id, 'role' => 1 ) );
        $secretary_user_id = null;
        foreach ( $roles as $role ) {
            if ( $role->get_user_id() ) {
                $secretary_user_id = $role->get_user_id();
                break;
            }
        }
        if ( ! $secretary_user_id ) {
            // No user assigned to the role, or the role doesn't exist
            throw new Role_Assignment_Not_Found_Exception( __( 'No active match secretary assigned for Club %s', 'racketmanager' ), $club_id );
        }
        $secretary_player = $this->player_repository->find( $secretary_user_id );

        if ( ! $secretary_player ) {
            throw new Player_Not_Found_Exception( __( 'Match Secretary user ID %s found in roles table, but not in users table', 'racketmanager' ), $club_id );
        }
        return $secretary_player;
    }

}
