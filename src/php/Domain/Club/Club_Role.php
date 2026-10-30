<?php
declare( strict_types=1 );
/**
 * Club_Role API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club Role
 */
namespace Racketmanager\Domain\Club;

use Racketmanager\Domain\DTO\Club\Club_Role_Hydration_DTO;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use Racketmanager\Util\Util_Lookup;

/**
 * Class to implement the Club_Role object
 */
class Club_Role {
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
     * @var ?object
     */
    public ?object $role = null;
    /**
     * Club
     *
     * @var ?object
     */
    public ?object $club = null;
    /**
     * User
     *
     * @var ?object
     */
    public ?object $user = null;
    /**
     * Constructor
     *
     * @param Club_Role_Hydration_DTO|null $dto hydration DTO.
     */
    public function __construct( ?Club_Role_Hydration_DTO $dto = null ) {
        if ( ! is_null( $dto ) ) {
            foreach ( get_object_vars( $dto ) as $key => $value ) {
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }
            if ( !empty( $this->role_id ) ) {
                $this->role = Util_Lookup::get_club_role( (string) $this->role_id );
            } elseif( $this->role ) {
                $role_desc = is_object( $this->role ) && isset( $this->role->desc )
                    ? (string) $this->role->desc
                    : (string) $this->role;
                $this->role_id = Util_Lookup::get_club_role_ref( $role_desc );
            }
        }
    }

    /**
     * Create a Club_Role from a raw row via Club_Role_Factory.
     *
     * @param object $row club role row.
     * @return self
     */
    public static function from_object( object $row ): self {
        return Club_Role_Factory::from_object( $row );
    }

    /**
     * Create a Club_Role from an associative array via Club_Role_Factory.
     *
     * @param array $data club role data.
     * @return self
     */
    public static function from_array( array $data ): self {
        return Club_Role_Factory::from_array( $data );
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
