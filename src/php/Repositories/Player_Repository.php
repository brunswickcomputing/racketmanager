<?php
/**
 * Player_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repository
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Player;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Update_Exception;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use WP_User;
use wpdb;

/**
 * Class to implement the Player repository
 */
class Player_Repository {
    const META_KEY_GENDER = 'gender';
    const META_KEY_BTM = 'btm';
    const META_KEY_YOB = 'year_of_birth';
    private wpdb $wpdb;

    /**
     * Create a new Player_Repository instance.
     *
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Add a new player to the database.
     *
     * @param Player $player
     *
     * @return void
     */
    public function add( Player $player ): void {
        $userdata                    = array();
        $userdata['first_name']      = $player->get_firstname();
        $userdata['last_name']       = $player->get_surname();
        $userdata['display_name']    = $player->get_display_name();
        $userdata['user_login']      = $player->get_login();
        $userdata['user_pass']       = $player->get_password();
        $userdata['user_registered'] = $player->get_date_registered();
        if ( $player->email ) {
            $userdata['user_email'] = $player->email;
        }
        $user_id = wp_insert_user( $userdata );
        if ( ! is_wp_error( $user_id ) ) {
            $player->set_id( $user_id );
            update_user_meta( $user_id, 'show_admin_bar_front', false );
            update_user_meta( $user_id, self::META_KEY_GENDER, $player->get_gender() );
            if ( isset( $player->btm ) && $player->btm > '' ) {
                $this->save_btm( $user_id, $player->get_btm() );
            }
            if ( isset( $player->contactno ) && $player->contactno > '' ) {
                $this->save_contact_no( $user_id, $player->get_contactno() );
            }
            if ( isset( $player->year_of_birth ) && $player->year_of_birth > '' ) {
                update_user_meta( $user_id, 'year_of_birth', $player->get_year_of_birth() );
            }
        }

    }

    /**
     * Save btm for player
     *
     * @param int $player_id
     * @param int $btm
     *
     * @return void
     */
    public function save_btm( int $player_id, int $btm ): void {
        update_user_meta( $player_id, self::META_KEY_BTM, $btm );
    }

    /**
     * Save contact number for player
     *
     * @param int $player_id
     * @param string $contact_no
     *
     * @return void
     */
    public function save_contact_no( int $player_id, string $contact_no ): void {
        update_user_meta( $player_id, 'contactno', $contact_no );
    }

    /**
     * Update player details in the database.
     *
     * @param Player $player
     * @param array $updates
     *
     * @return void
     * @throws Player_Update_Exception
     */
    public function update( Player $player, array $updates ): void {
        if ( isset( $updates['core'] ) ) {
            $user_data                  = array();
            $user_data['first_name']    = $player->get_firstname();
            $user_data['last_name']     = $player->get_surname();
            $user_data['display_name']  = $player->get_display_name();
            $user_data['user_nicename'] = sanitize_title( $user_data['display_name'] );
            $user_data['user_email']    = $player->get_email();
            $user_data['ID']            = $player->get_id();
            $user_id                    = wp_update_user( $user_data );
            if ( is_wp_error( $user_id ) ) {
                throw new Player_Update_Exception( $user_id->get_error_message() );
            }
        }
        $user_id = $player->get_id();
        if ( isset( $updates[ self::META_KEY_GENDER ] ) ) {
            update_user_meta( $user_id, self::META_KEY_GENDER, $player->get_gender() );
        }
        if ( isset( $updates[ self::META_KEY_BTM ] ) ) {
            $this->save_btm( $user_id, $player->get_btm() );
        }
        if ( isset( $updates['contactno'] ) ) {
            $this->save_contact_no( $user_id, $player->get_contactno() );
        }
        if ( isset( $updates['dob'] ) ) {
            update_user_meta( $user_id, self::META_KEY_YOB, $player->get_year_of_birth() );
        }
        if ( isset( $updates['locked'] ) ) {
            if ( $player->get_locked() ) {
                update_user_meta( $user_id, 'locked', $player->get_locked() );
                update_user_meta( $user_id, 'locked_date', $player->get_locked_date() );
                update_user_meta( $user_id, 'locked_user', $player->get_locked_user() );
            } else {
                delete_user_meta( $user_id, 'locked' );
                delete_user_meta( $user_id, 'locked_date' );
                delete_user_meta( $user_id, 'locked_user' );
            }
        }
        if ( isset( $updates['removed'] ) ) {
            update_user_meta( $user_id, 'remove', $player->get_removed_date() );
            update_user_meta( $user_id, 'remove_user', $player->get_removed_user() );
        }
        if ( isset( $updates['wtn'] ) ) {
            foreach ( $player->get_wtn() as $match_type => $wtn ) {
                $wtn_type = 'wtn_' . $match_type;
                update_user_meta( $user_id, $wtn_type, $wtn );
            }
        }
        wp_cache_delete( $player->get_id(), 'players' );
    }

    /**
     * Find all players matching the specified criteria.
     *
     * @param array $args
     *
     * @return array
     */
    public function find_all( array $args ): array {
        $defaults       = array(
            'active' => false,
            'name'   => false,
        );
        $args           = array_merge( $defaults, $args );
        $active         = $args['active'];
        $name           = $args['name'];
        $orderby_string = 'display_name';
        $order          = 'ASC';
        if ( $active ) {
            return $this->get_active_players();
        }
        $user_args                 = array();
        $user_args['meta_key']     = self::META_KEY_GENDER; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $user_args['meta_value']   = 'M,F'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        $user_args['meta_compare'] = 'IN';
        $user_args['orderby']      = $orderby_string;
        $user_args['order']        = $order;
        if ( $name ) {
            if ( is_numeric( $name ) ) {
                $user_args['meta_key']   = self::META_KEY_BTM; //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                $user_args['meta_value'] = $name; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
            } else {
                $user_args['search']         = '*' . $name . '*';
                $user_args['search_columns'] = array( 'display_name' );
            }
        }
        $user_search = wp_json_encode( $user_args );
        $players     = wp_cache_get( md5( $user_search ), 'players' );
        if ( ! $players ) {
            $user_args['fields'] = array( 'ID', 'display_name' );
            $players             = get_users( $user_args );
            if ( $players ) {
                $i = 0;
                foreach ( $players as $player ) {
                    $player        = $this->find( $player->ID );
                    $players[ $i ] = $player;
                    ++ $i;
                }
            }
            wp_cache_set( md5( $user_search ), $players, 'players' );
        }

        return $players;
    }

    /**
     * Get active players
     *
     * @return array
     */
    private function get_active_players(): array {
        $sql     = "SELECT DISTINCT `player_id` FROM {$this->wpdb->prefix}racketmanager_rubber_players UNION SELECT DISTINCT `player_id` FROM {$this->wpdb->prefix}racketmanager_tournament_entries ORDER BY `player_id`";
        $players = wp_cache_get( md5( $sql ), 'players' );
        if ( ! $players ) {
            $players = $this->wpdb->get_results( $sql // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );
            if ( $players ) {
                $i = 0;
                foreach ( $players as $player ) {
                    $player        = $this->find( $player->player_id );
                    $players[ $i ] = $player;
                    ++ $i;
                }
            }
            wp_cache_set( md5( $sql ), $players, 'players' );
        }

        return $players;
    }

    /**
     * Find a player by ID, login, name or email.
     *
     * @param int|string $player_id
     * @param string $search_type
     *
     * @return Player|null
     */
    public function find( int|string $player_id, string $search_type = 'id' ): ?Player {
        if ( empty( $player_id ) ) {
            throw new Player_Not_Found_Exception( __( 'Player ID cannot be empty.', 'racketmanager' ) );
        }
        $player = wp_cache_get( $player_id, 'players' );
        if ( $player ) {
            return $player;
        }

        $player = match ( $search_type ) {
            'btm' => $this->find_by_btm( $player_id ),
            'email' => $this->find_by_email( $player_id ),
            'login' => $this->find_by_login( $player_id ),
            'name' => $this->find_by_name( $player_id ),
            default => get_userdata( $player_id ),
        };
        if ( ! $player ) {
            return null;
        }
        $player->data->firstname     = get_user_meta( $player->data->ID, 'first_name', true );
        $player->data->surname       = get_user_meta( $player->data->ID, 'last_name', true );
        $player->data->gender        = get_user_meta( $player->data->ID, self::META_KEY_GENDER, true );
        $player->data->type          = get_user_meta( $player->data->ID, 'racketmanager_type', true );
        $player->data->btm           = get_user_meta( $player->data->ID, self::META_KEY_BTM, true );
        $year_of_birth               = get_user_meta( $player->data->ID, self::META_KEY_YOB, true );
        $player->data->year_of_birth = empty( $year_of_birth ) ? null : $year_of_birth;
        $contact_no                  = get_user_meta( $player->data->ID, 'contactno', true );
        $player->data->contactno     = empty( $contact_no ) ? null : $contact_no;
        $removed_date                = get_user_meta( $player->data->ID, 'remove_date', true );
        $player->data->removed_date  = null;
        $player->data->removed_user  = null;
        if ( ! empty( $removed_date ) ) {
            $player->data->removed_date = $removed_date;
            $removed_user               = get_user_meta( $player->data->ID, 'remove_user', true );
            if ( ! empty( $removed_user ) ) {
                $player->data->removed_user = $removed_user;
            }
        }
        $locked               = get_user_meta( $player->data->ID, 'locked', true );
        $player->data->locked = ! empty( $locked );
        if ( ! empty( $locked ) ) {
            $player->data->locked_date = get_user_meta( $player->data->ID, 'locked_date', true );
            $player->data->locked_user = get_user_meta( $player->data->ID, 'locked_user', true );
        }
        $player->data->system_record = get_user_meta( $player->data->ID, 'racketmanager_type', true );
        $match_types                 = Util_Lookup::get_match_types();
        foreach ( $match_types as $match_type => $description ) {
            $wtn_type                         = 'wtn_' . $match_type;
            $player->data->wtn[ $match_type ] = get_user_meta( $player->data->ID, $wtn_type, true );
        }
        $player->data->opt_ins = get_user_meta( $player->data->ID, 'racketmanager_opt_in' );

        // Create a new Player object from the retrieved user data.
        $player = new Player( $player->data );
        wp_cache_set( $player_id, $player, 'players' );

        return $player;
    }

    /**
     * Get player by LTA tennis number
     *
     * @param $player_id
     *
     * @return false|mixed
     */
    public function find_by_btm( $player_id ): mixed {
        $players = get_users( array(
                'meta_key'     => self::META_KEY_BTM, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value'   => $player_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                'meta_compare' => '=',
            ) );
        if ( $players ) {
            return $players[0];
        } else {
            return false;
        }
    }

    public function find_by_email( $player_id ): false|WP_User {
        return get_user_by( 'email', $player_id );
    }

    /**
     * Get player by login
     *
     * @param $player_id
     *
     * @return false|WP_User
     */
    public function find_by_login( $player_id ): false|WP_User {
        // the format of login is first.surname( can contain spaces ).
        if ( ! str_contains( $player_id, '.' ) ) {
            $pos = strpos( $player_id, ' ' );
            if ( false !== $pos ) {
                $player_id = substr_replace( $player_id, '.', $pos, strlen( ' ' ) );
            }
        }

        return get_user_by( 'login', strtolower( $player_id ) );
    }

    /**
     * Get a player by name
     *
     * @param $player_id
     *
     * @return false|WP_User
     */
    public function find_by_name( $player_id ): false|WP_User {
        // the format of nicename is first-surname (where surname spaces are converted to '-').
        if ( str_contains( $player_id, ' ' ) ) {
            $player_id = str_replace( ' ', '-', $player_id );
        }

        return get_user_by( 'slug', strtolower( $player_id ) );
    }

    /**
     * Check if a player has club associations function
     *
     * @param int $player_id
     *
     * @return bool
     */
    public function has_club_associations( int $player_id ): bool {
        $roles_table        = $this->wpdb->prefix . 'racketmanager_club_roles';
        $club_players_table = $this->wpdb->prefix . 'racketmanager_club_players';
        $query              = $this->wpdb->prepare( "SELECT ((SELECT COUNT(id) FROM $roles_table WHERE user_id = %d) + (SELECT COUNT(id) FROM $club_players_table WHERE player_id = %d)) AS association_count", $player_id, $player_id );
        $count              = $this->wpdb->get_var( $query );

        return $count > 0;
    }

    /**
     * Delete a player from the database.
     *
     * @param int $player_id
     *
     * @return void
     */
    public function delete( int $player_id ): void {
        wp_delete_user( $player_id );
    }

    /**
     * Finds all Player Ids that are registered for a specific club.
     *
     * @param int $club_id
     *
     * @return int[] Array of user IDs.
     */
    public function find_player_ids_by_club( int $club_id ): array {
        $registration_table = $this->wpdb->prefix . 'racketmanager_club_players';
        $query              = $this->wpdb->prepare( "SELECT player_id FROM $registration_table WHERE club_id = %d AND `removed_date` IS NULL", $club_id );

        return $this->wpdb->get_col( $query );
    }

    /**
     * Finds players and their registration details based on various filters.
     *
     * @param int|null $club_id Optional Club ID filter.
     * @param string|null $status Optional status filter ('pending', 'approved').
     * @param string|null $gender Optional gender filter.
     * @param string|null $active Optional active filter.
     * @param bool $system Optional system filter.
     * @param int|null $max_age Optional maximum age filter.
     * @param int|null $min_age Optional minimum age filter.
     *
     * @return array
     */
    public function find_club_players_with_details( ?int $club_id = null, ?string $status = null, ?string $gender = null, ?string $active = null, bool $system = false, ?int $max_age = null, ?int $min_age = null ): array {
        $registration_table = $this->wpdb->prefix . 'racketmanager_club_players';
        $club_table         = $this->wpdb->prefix . 'racketmanager_clubs';
        $user_table         = $this->wpdb->users;
        $user_meta_table    = $this->wpdb->usermeta;

        // Base query: Join users, registrations, and clubs tables
        $query = "SELECT r.id as registration_id, u.ID as user_id, u.display_name as display_name, u.user_email as email, c.id as club_id, c.shortcode as club_name, r.requested_date as registration_date, r.created_date as approval_date, r.removed_date as removal_date, r.requested_user as registered_by_user_id, r.created_user as approved_by_user_id, r.removed_user as removed_by_user_id, MAX(IF(um_gender.meta_key = %s, um_gender.meta_value, NULL)) as gender, MAX(IF(um_yob.meta_key = %s, um_yob.meta_value, NULL)) as year_of_birth FROM $user_table u INNER JOIN $registration_table r ON u.ID = r.player_id INNER JOIN $club_table c ON r.club_id = c.id LEFT JOIN $user_meta_table um_gender ON u.ID = um_gender.user_id AND um_gender.meta_key = %s LEFT JOIN $user_meta_table um_yob ON u.ID = um_yob.user_id AND um_yob.meta_key = %s";

        $params       = [ self::META_KEY_GENDER, self::META_KEY_YOB, self::META_KEY_GENDER, self::META_KEY_YOB ];
        $search_terms = [];
        if ( $club_id ) {
            $search_terms[] = $this->wpdb->prepare( "r.club_id = %d", $club_id );
        }
        if ( ! $system ) {
            $search_terms[] = "r.system_record IS NULL";
        }
        if ( $active ) {
            $search_terms[] = "r.removed_date IS NULL";
        }
        switch ( $status ) {
            case 'outstanding':
                $search_terms[] = "r.created_date IS NULL";
                break;
            case 'all':
            default:
                break;
        }
        // Age filtering
        $current_year = (int) date( 'Y' );
        if ( is_numeric( $min_age ) ) {
            // A player who is at least $minAge must have a YOB <= ($currentYear - $minAge)
            $max_year_of_birth = $current_year - (int) $min_age;
            // We need to add an explicit JOIN condition for the YOB meta key for the WHERE clause to work efficiently
            $search_terms[] = $this->wpdb->prepare( "CAST(um_yob.meta_value AS UNSIGNED) <= %d", $max_year_of_birth );
        }
        if ( is_numeric( $max_age ) ) {
            // A player who is at most $maxAge must have a YOB >= ($currentYear - $maxAge)
            $min_year_of_birth = $current_year - (int) $max_age;
            $search_terms[] = $this->wpdb->prepare( "CAST(um_yob.meta_value AS UNSIGNED) >= %d", $min_year_of_birth );
        }
        $search = Util::search_string( $search_terms, true );
        $query  .= " $search GROUP BY u.ID, c.id, r.id ";

        if ( ! empty( $gender ) ) {
            // Filter happens in the HAVING clause because gender is aggregated
            // OR we use an INNER JOIN for meta-query optimisation if performance is key.
            // Using HAVING for simplicity here:
            $query    .= " HAVING gender = %s";
            $params[] = $gender;
        }

        $query   .= " ORDER BY u.display_name ASC";
        $results = $this->wpdb->get_results( $this->wpdb->prepare( $query, $params ) );

        return array_map( function ( $row ) {
            return $row->registration_id;
        }, $results );
    }

}
