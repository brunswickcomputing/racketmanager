<?php
/**
 * Club_Role API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club Role
 */

namespace Racketmanager\Domain;

use Racketmanager\Util\Util_Lookup;
use function Racketmanager\get_user;

/**
 * Class to implement the Club_Role object
 */
final class Club_Role {
    /**
     * Id
     *
     * @var ?int
     */
    public ?int $id = null;
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
     * @var object
     */
    public object $role;
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
     * Constructor
     *
     * @param object|null $club_role Club_Role object.
     */
    public function __construct( ?object $club_role = null ) {
        if ( ! is_null( $club_role ) ) {
            foreach ( get_object_vars( $club_role ) as $key => $value ) {
                $this->$key = $value;
            }
            if ( ! empty( $this->user_id ) ) {
                $user = get_user( $this->user_id );
                if ( $user ) {
                    $this->user = $user;
                }
            }
            if ( !empty( $this->role_id ) ) {
                $this->role = Util_Lookup::get_club_role( $this->role_id );
            } elseif( $this->role ) {
                $this->role_id = Util_Lookup::get_club_role_ref( $this->role );
            }
        }
    }

    /**
     * Get id
     *
     * @return ?int
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Get club id
     *
     * @return int
     */
    public function get_club_id(): int {
        return $this->club_id;
    }

    /**
     * Get role id
     *
     * @return int
     */
    public function get_role_id(): int {
        return $this->role_id;
    }

    /**
     * Get user id
     *
     * @return int
     */
    public function get_user_id(): int {
        return $this->user_id;
    }

    /**
     * Set id
     *
     * @param int $insert_id
     *
     * @return void
     */
    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }
    /**
     * Set user id
     *
     * @param int $user_id
     *
     * @return void
     */
    public function set_user_id( int $user_id ): void {
        $this->user_id = $user_id;
    }
}
