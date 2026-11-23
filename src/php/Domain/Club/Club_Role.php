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
use Racketmanager\Domain\Enums\Club_Role as Club_Role_Enum;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use Racketmanager\Util\Util_Lookup;

/**
 * Class to implement the Club_Role object
 */
class Club_Role {
    /**
     * Id
     *
     * @var int|null
     */
    public private(set) ?int $id = null;

    /**
     * User id
     *
     * @var int
     */
    public private(set) int $user_id = 0;

    /**
     * Club id
     *
     * @var int
     */
    public private(set) int $club_id = 0;

    /**
     * Role id
     *
     * @var int
     */
    public private(set) int $role_id = 0;

    /**
     * Role
     *
     * @var object|null
     */
    public private(set) ?object $role = null;

    /**
     * Club
     *
     * @var object|null
     */
    public private(set) ?object $club = null;

    /**
     * User
     *
     * @var object|null
     */
    public private(set) ?object $user = null;

    /**
     * Constructor
     *
     * @param Club_Role_Hydration_DTO|null $dto Hydration DTO.
     */
    public function __construct( ?Club_Role_Hydration_DTO $dto = null ) {
        if ( ! is_null( $dto ) ) {
            foreach ( get_object_vars( $dto ) as $key => $value ) {
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }
            if ( ! empty( $this->role_id ) ) {
                $this->role = Util_Lookup::get_club_role( (string) $this->role_id ) ?: null;
            } elseif ( $this->role ) {
                $role_desc     = is_object( $this->role ) && isset( $this->role->desc )
                    ? (string) $this->role->desc
                    : (string) $this->role;
                $this->role_id = Util_Lookup::get_club_role_ref( $role_desc );
            }
        }
    }

    /**
     * Create a Club_Role from a raw row via Club_Role_Factory.
     *
     * @param object $row Club role row.
     * @return self
     */
    public static function from_object( object $row ): self {
        return Club_Role_Factory::from_object( $row );
    }

    /**
     * Create a Club_Role from an associative array via Club_Role_Factory.
     *
     * @param array $data Club role data.
     * @return self
     */
    public static function from_array( array $data ): self {
        return Club_Role_Factory::from_array( $data );
    }

    /**
     * Get id
     *
     * @return int|null
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
     * Get role
     *
     * @return object|null
     */
    public function get_role(): ?object {
        return $this->role;
    }

    /**
     * Get club
     *
     * @return object|null
     */
    public function get_club(): ?object {
        return $this->club;
    }

    /**
     * Get user
     *
     * @return object|null
     */
    public function get_user(): ?object {
        return $this->user;
    }

    /**
     * Set id
     *
     * @param int|null $id Role record ID.
     *
     * @return void
     */
    public function set_id( ?int $id ): void {
        $this->id = $id;
    }

    /**
     * Set user id
     *
     * @param int $user_id User ID.
     *
     * @return void
     */
    public function set_user_id( int $user_id ): void {
        $this->user_id = $user_id;
    }

    /**
     * Set club id
     *
     * @param int $club_id Club ID.
     *
     * @return void
     */
    public function set_club_id( int $club_id ): void {
        $this->club_id = $club_id;
    }

    /**
     * Set role id
     *
     * @param int $role_id Role ID.
     *
     * @return void
     */
    public function set_role_id( int $role_id ): void {
        $this->role_id = $role_id;
    }

    /**
     * Set role
     *
     * @param object|null $role Role object.
     *
     * @return void
     */
    public function set_role( ?object $role ): void {
        $this->role = $role;
    }

    /**
     * Set club
     *
     * @param object|null $club Club object.
     *
     * @return void
     */
    public function set_club( ?object $club ): void {
        $this->club = $club;
    }

    /**
     * Set user
     *
     * @param object|null $user User object.
     *
     * @return void
     */
    public function set_user( ?object $user ): void {
        $this->user = $user;
    }

    /**
     * Reassign the role to a user.
     *
     * @param int         $user_id User ID.
     * @param object|null $user    Optional user object.
     *
     * @return void
     */
    public function reassign_user( int $user_id, ?object $user = null ): void {
        $this->user_id = $user_id;
        $this->user    = $user;
    }

    /**
     * Get the backed enum instance for this role.
     *
     * @return Club_Role_Enum|null
     */
    public function get_role_enum(): ?Club_Role_Enum {
        return Club_Role_Enum::try_from_value( $this->role_id );
    }

    /**
     * Check if the role is Match Secretary.
     *
     * @return bool
     */
    public function is_match_secretary(): bool {
        return $this->get_role_enum()?->is_match_secretary() ?? false;
    }

    /**
     * Check if the role is Coach.
     *
     * @return bool
     */
    public function is_coach(): bool {
        return $this->get_role_enum()?->is_coach() ?? false;
    }

    /**
     * Check if the role is Treasurer.
     *
     * @return bool
     */
    public function is_treasurer(): bool {
        return $this->get_role_enum()?->is_treasurer() ?? false;
    }
}
