<?php
/**
 * Club_Role API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club Role
 */

namespace Racketmanager;

/**
 * Class to implement the Club_Role object
 */
final class Club_Role {
    /**
     * Id
     *
     * @var int
     */
    public int $id;
    /**
     * User id
     *
     * @var int
     */
    public int $user_id;
    /**
     * Club id
     *
     * @var int
     */
    public int $club_id;
    /**
     * Role id
     *
     * @var int
     */
    public int $role_id;
    /**
     * Role
     *
     * @var string
     */
    public string $role;
    /**
     * Club
     *
     * @var object
     */
    public object $club;
    /**
     * User
     *
     * @var object
     */
    public object $user;
    /**
     * Retrieve club_role instance
     *
     * @param int $club_role_id club role id.
     */
    public static function get_instance( int $club_role_id ) {
        global $wpdb;
        if ( ! $club_role_id ) {
            return false;
        }
        $club_role = wp_cache_get( $club_role_id, 'club_roles' );
        if ( ! $club_role ) {
            $club_role = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `id`, `user_id`, `club_id`, `role_id` FROM $wpdb->racketmanager_club_roles WHERE `id` = %d LIMIT 1",
                    $club_role_id
                )
            ); // db call ok.
            if ( ! $club_role ) {
                return false;
            }
            $club_role = new Club_Role( $club_role );
            wp_cache_set( $club_role_id, $club_role, 'club_roles' );
        }
        return $club_role;
    }
    /**
     * Constructor
     *
     * @param object|null $club_role Club_Role object.
     */
    public function __construct( ?object $club_role = null ) {
        if ( ! is_null( $club_role ) ) {
            foreach ( get_object_vars( $club_role ) as $key => $value ) {
                $this->$key = $value;
            }
            if ( ! isset( $this->id ) ) {
                $this->add();
            }
            if ( $this->user_id ) {
                $user = get_user( $this->user_id );
                if ( $user ) {
                    $this->user = $user;
                }
            }
            if ( $this->role_id ) {
                $this->role = Util::get_club_role( $this->role_id );
            }
        }
    }
    /**
     * Create new club player
     */
    private function add(): void {
        global $wpdb;
        if ( empty( $this->role_id ) ) {
            $this->role_id = Util::get_club_role_ref( $this->role );
        }
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_club_roles (`club_id`, `role_id`, `user_id` ) VALUES (%d, %d, %d)",
                $this->club_id,
                $this->role_id,
                $this->user_id,
            )
        );
        $this->id = $wpdb->insert_id;
    }
    /**
     * Update Club Role
     */
    public function update( int $user_id ): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_club_roles SET `user_id` = %d WHERE `id` = %d",
                $user_id,
                $this->id
            )
        );
        $this->user_id = $user_id;
        wp_cache_set( $this->id, 'club_roles' );
    }
    /**
     * Delete Club Role
     */
    public function delete(): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_club_roles WHERE `id` = %d",
                $this->id
            )
        );
        wp_cache_delete( $this->id, 'club_roles' );
    }
}
