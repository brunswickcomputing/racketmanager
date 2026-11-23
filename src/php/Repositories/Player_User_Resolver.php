<?php
/**
 * Player user resolver collaborator.
 *
 * @package Racketmanager\Repositories
 */

declare( strict_types=1 );

namespace Racketmanager\Repositories;

use WP_User;

/**
 * Resolves WordPress user records across lookup strategies (ID, BTM, email, login, and name/slug).
 */
class Player_User_Resolver {
    private const string META_KEY_BTM = 'btm';

    /**
     * Resolve the WordPress user record by search type.
     *
     * @param int|string $player_id   Player identifier or lookup value.
     * @param string     $search_type Search type ('id', 'btm', 'email', 'login', or 'name').
     *
     * @return mixed User object or false when not found.
     */
    public function resolve( int|string $player_id, string $search_type = 'id' ): mixed {
        return match ( $search_type ) {
            'btm' => $this->find_by_btm( $player_id ),
            'email' => $this->find_by_email( (string) $player_id ),
            'login' => $this->find_by_login( (string) $player_id ),
            'name' => $this->find_by_name( (string) $player_id ),
            default => $this->find_by_id( $player_id ),
        };
    }

    /**
     * Find a user by WordPress user ID.
     *
     * @param int|string $player_id Player user ID.
     *
     * @return mixed User object when found, or false when not found.
     */
    public function find_by_id( int|string $player_id ): mixed {
        return get_userdata( $player_id );
    }

    /**
     * Find a user by BTM player ID.
     *
     * @param mixed $player_id BTM player ID to search for.
     *
     * @return mixed User object when found, or false when no matching user exists.
     */
    public function find_by_btm( mixed $player_id ): mixed {
        $players = get_users(
            array(
                'meta_key'     => self::META_KEY_BTM, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value'   => $player_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                'meta_compare' => '=',
            )
        );
        if ( $players ) {
            return $players[0];
        }

        return false;
    }

    /**
     * Find a user by email address.
     *
     * @param string $player_id Email address to search for.
     *
     * @return false|WP_User User object if found, false otherwise.
     */
    public function find_by_email( string $player_id ): false|WP_User {
        return get_user_by( 'email', $player_id );
    }

    /**
     * Find a WordPress user by player login.
     *
     * Normalises a player identifier by replacing the first space with a period when
     * no period is already present, then searches for a matching lowercase login.
     *
     * @param string $player_id Player login or name-based identifier.
     *
     * @return false|WP_User User object when found, or false when no matching user exists.
     */
    public function find_by_login( string $player_id ): false|WP_User {
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
     * Find a WordPress user by player name slug.
     *
     * Converts spaces in the supplied player identifier to hyphens before searching
     * for a matching user nicename slug.
     *
     * @param string $player_id Player name or slug to search for.
     *
     * @return false|WP_User Matching user object, or false if no user is found.
     */
    public function find_by_name( string $player_id ): false|WP_User {
        // the format of nicename is first-surname (where surname spaces are converted to '-').
        if ( str_contains( $player_id, ' ' ) ) {
            $player_id = str_replace( ' ', '-', $player_id );
        }

        return get_user_by( 'slug', strtolower( $player_id ) );
    }
}
