<?php

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Player;
use Exception;
use Racketmanager\Util\Util_Lookup;
use WP_User;
use wpdb;

class Player_Repository {
    private wpdb $wpdb;
    private string $table_name;

    /**
     * Create a new Player_Repository instance.
     *
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'users';
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
        $userdata['user_login']      = $player->get_user_login();
        $userdata['user_pass']       = $player->get_user_password();
        $userdata['user_registered'] = $player->get_user_registered();
        if ( $player->email ) {
            $userdata['user_email'] = $player->email;
        }
        $user_id = wp_insert_user( $userdata );
        if ( ! is_wp_error( $user_id ) ) {
            $player->set_id( $user_id );
            update_user_meta( $user_id, 'show_admin_bar_front', false );
            update_user_meta( $user_id, 'gender', $player->get_gender() );
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
     * Update player details in the database.
     *
     * @param Player $player
     * @param array $updates
     *
     * @return void
     * @throws Exception
     */
    public function update( Player $player, array $updates ): void {
        if ( isset( $updates['core'] ) ) {
            $user_data                  = array();
            $user_data['first_name']    = $player->get_firstname();
            $user_data['last_name']     = $player->get_surname();
            $user_data['display_name']  = $player->get_display_name();
            $user_data['user_nicename'] = sanitize_title( $user_data['display_name'] );
            $user_data['user_email']    = $player->get_user_email();
            $user_data['ID']            = $player->get_id();
            $user_id                    = wp_update_user( $user_data );
            if ( is_wp_error( $user_id ) ) {
                throw new Exception( $user_id->get_error_message() );
            }
        }
        $user_id = $player->get_id();
        if ( isset( $updates['gender'] ) ) {
            update_user_meta( $user_id, 'gender', $player->get_gender() );
        }
        if ( isset( $updates['btm'] ) ) {
            $this->save_btm( $user_id, $player->get_btm() );
        }
        if ( isset( $updates['contactno'] ) ) {
            $this->save_contact_no( $user_id, $player->get_contactno() );
        }
        if ( isset( $updates['dob'] ) ) {
            update_user_meta( $user_id, 'year_of_birth', $player->get_year_of_birth() );
        }
        if ( isset( $updates['locked'] ) ) {
            if ( $player->locked ) {
                update_user_meta( $user_id, 'locked', $player->locked );
                update_user_meta( $user_id, 'locked_date', gmdate( 'Y-m-d' ) );
                update_user_meta( $user_id, 'locked_user', get_current_user_id() );
            } else {
                delete_user_meta( $user_id, 'locked' );
                delete_user_meta( $user_id, 'locked_date' );
                delete_user_meta( $user_id, 'locked_user' );
            }
        }
    }

    /**
     * Find a player by ID, login, name or email.
     *
     * @param int $player_id
     * @param string $search_type
     *
     * @return Player|null
     */
    public function find( int $player_id, string $search_type = 'id' ): ?Player {
        $player = wp_cache_get( $player_id, 'players' );

        if ( ! $player ) {
            $player = match ( $search_type ) {
                'btm'   => self::get_player_by_btm( $player_id ),
                'email' => get_user_by( 'email', $player_id ),
                'login' => self::get_player_by_login( $player_id ),
                'name'  => self::get_player_by_name( $player_id ),
                default => get_userdata( $player_id ),
            };
            if ( ! $player ) {
                return null;
            }
            $player->data->firstname     = get_user_meta( $player->data->ID, 'first_name', true );
            $player->data->surname       = get_user_meta( $player->data->ID, 'last_name', true );
            $player->data->gender        = get_user_meta( $player->data->ID, 'gender', true );
            $player->data->type          = get_user_meta( $player->data->ID, 'racketmanager_type', true );
            $player->data->btm           = get_user_meta( $player->data->ID, 'btm', true );
            $year_of_birth               = get_user_meta( $player->data->ID, 'year_of_birth', true );
            $player->data->year_of_birth = empty( $year_of_birth ) ? null : $year_of_birth;
            $player->data->contactno     = get_user_meta( $player->data->ID, 'contactno', true );
            $player->data->removed_date  = get_user_meta( $player->data->ID, 'remove_date', true );
            $player->data->removed_user  = get_user_meta( $player->data->ID, 'remove_user', true );
            $locked                      = get_user_meta( $player->data->ID, 'locked', true );
            $player->data->locked        = ! empty( $locked );
            if ( ! empty( $locked ) ) {
                $player->data->locked_date = get_user_meta( $player->data->ID, 'locked_date', true );
                $player->data->locked_user = get_user_meta( $player->data->ID, 'locked_user', true );
            }
            $player->data->system_record = get_user_meta( $player->data->ID, 'racketmanager_type', true );
            $match_types = Util_Lookup::get_match_types();
            foreach ( $match_types as $match_type => $description ) {
                $wtn_type                         = 'wtn_' . $match_type;
                $player->data->wtn[ $match_type ] = get_user_meta( $player->data->ID, $wtn_type, true );
            }
            $player->data->opt_ins = get_user_meta( $player->data->ID, 'racketmanager_opt_in' );

            // Create a new Player object from the retrieved user data.
            $player = new Player( $player->data );
            wp_cache_set( $player_id, $player, 'players' );
        }

        return $player;
    }
    /**
     * Get player by LTA tennis number
     *
     * @param $player_id
     *
     * @return false|mixed
     */
    private static function get_player_by_btm( $player_id ): mixed {
        $players = get_users(
            array(
                'meta_key'     => 'btm', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value'   => $player_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                'meta_compare' => '=',
            )
        );
        if ( $players ) {
            return $players[0];
        } else {
            return false;
        }
    }
    /**
     * Get player by login
     *
     * @param $player_id
     *
     * @return false|WP_User
     */
    private static function get_player_by_login( $player_id ): false|WP_User {
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
    private static function get_player_by_name( $player_id ): false|WP_User {
        // the format of nicename is first-surname (where surname spaces are converted to '-').
        if ( str_contains( $player_id, ' ' ) ) {
            $player_id = str_replace( ' ', '-', $player_id );
        }
        return get_user_by( 'slug', strtolower( $player_id ) );
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
        $user_args['meta_key']     = 'gender'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
        $user_args['meta_value']   = 'M,F'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
        $user_args['meta_compare'] = 'IN';
        $user_args['orderby']      = $orderby_string;
        $user_args['order']        = $order;
        if ( $name ) {
            if ( is_numeric( $name ) ) {
                $user_args['meta_key']   = 'btm'; //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
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
                    ++$i;
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
            $players = $this->wpdb->get_results(
                $sql // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            );
            if ( $players ) {
                $i = 0;
                foreach ( $players as $player ) {
                    $player        = $this->find( $player->player_id );
                    $players[ $i ] = $player;
                    ++$i;
                }
            }
            wp_cache_set( md5( $sql ), $players, 'players' );
        }
        return $players;
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
     * Save email for player
     *
     * @param int $player_id
     * @param string $email
     *
     * @return void
     */
    public function save_email( int $player_id, string $email ): void {
        $userdata               = array();
        $userdata['ID']         = $player_id;
        $userdata['user_email'] = $email;
        $user_id                = wp_update_user( $userdata );
        if ( is_wp_error( $user_id ) ) {
            $error_msg = $user_id->get_error_message();
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( 'Unable to update user email ' . $player_id . ' - ' . $email . ' - ' . $error_msg );
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
        update_user_meta( $player_id, 'btm', $btm );
    }
}
