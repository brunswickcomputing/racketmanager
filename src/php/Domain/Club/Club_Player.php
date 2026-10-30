<?php
declare( strict_types=1 );
/**
 * Club_Player API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */
namespace Racketmanager\Domain\Club;

use DateTimeImmutable;
use Racketmanager\Domain\DTO\Club\Club_Player_Hydration_DTO;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;
use Racketmanager\Exceptions\Invalid_Status_Exception;

/**
 * Class to implement the Club_Player object
 */
class Club_Player {
    /** MySQL datetime format used for the approval / removal timestamps (matches `current_time( 'mysql' )`). */
    private const string DATE_FORMAT = 'Y-m-d H:i:s';
    /**
     * Id
     *
     * @var ?int
     */
    public ?int $id = null;
    /**
     * Player id
     *
     * @var int
     */
    public int $player_id;
    /**
     * Club id
     *
     * @var int
     */
    public int $club_id;
    /**
     * Removed date
     *
     * @var string|null
     */
    public ?string $removed_date = null;
    /**
     * Removed user
     *
     * @var int|null
     */
    public ?int $removed_user = null;
    /**
     * Created date
     *
     * @var ?string
     */
    public ?string $created_date = null;
    /**
     * Created user
     *
     * @var int|null
     */
    public ?int $created_user = null;
    /**
     * Requested date
     *
     * @var string|null
     */
    public ?string $requested_date;
    /**
     * Requested user
     *
     * @var int|null
     */
    public ?int $requested_user;
    /**
     * Player
     *
     * @var object
     */
    public object $player;
    /**
     * System record
     *
     * @var boolean|null
     */
    public bool|null $system_record = null;
    /**
     * Status
     *
     * @var string|null
     */
    private ?string $status = null;

    /**
     * Constructor
     *
     * @param Club_Player_Hydration_DTO|null $dto hydration DTO.
     */
    public function __construct( ?Club_Player_Hydration_DTO $dto = null ) {
        if ( ! is_null( $dto ) ) {
            // Nullable properties receive null, non-nullable ones keep their default when the column is absent.
            foreach ( get_object_vars( $dto ) as $key => $value ) {
                Typed_Property_Hydrator::assign( $this, $key, $value );
            }
        }
    }

    /**
     * Create a Club_Player from a raw row via Club_Player_Factory.
     *
     * @param object $row club player row.
     * @return self
     */
    public static function from_object( object $row ): self {
        return Club_Player_Factory::from_object( $row );
    }

    /**
     * Create a Club_Player from an associative array via Club_Player_Factory.
     *
     * @param array $data club player data.
     * @return self
     */
    public static function from_array( array $data ): self {
        return Club_Player_Factory::from_array( $data );
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
     * Set the id
     *
     * @param int $insert_id
     *
     * @return void
     */
    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }

    /**
     * Get player id
     *
     * @return int|null
     */
    public function get_player_id(): ?int {
        return $this->player_id;
    }

    /**
     * Get club id
     *
     * @return int|null
     */
    public function get_club_id(): ?int {
        return $this->club_id;
    }

    /**
     * Get the requested date
     *
     * @return string|null
     */
    public function get_requested_date(): ?string {
        return $this->requested_date;
    }

    /**
     * Get requested user id
     *
     * @return int|null
     */
    public function get_requested_user(): ?int {
        return $this->requested_user;
    }

    /**
     * Get the approval date
     *
     * @return string|null
     */
    public function get_created_date(): ?string {
        return $this->created_date;
    }

    /**
     * Get approval user id
     *
     * @return int|null
     */
    public function get_created_user(): ?int {
        return $this->created_user;
    }

    /**
     * Get the removed date
     *
     * @return string|null
     */
    public function get_removed_date(): ?string {
        return $this->removed_date;
    }

    /**
     * Get removed user id
     *
     * @return int|null
     */
    public function get_removed_user(): ?int {
        return $this->removed_user;
    }

    /**
     * Get system record indicator
     *
     * @return bool|null
     */
    public function get_system_record(): ?bool {
        return $this->system_record;
    }

    /**
     * Get status
     *
     * @return string|null
     */
    public function get_status(): ?string {
        return $this->status;
    }

     /**
     * Set the removal date
     *
     * @param string $date
     *
     * @return void
     */
    public function set_removal_date( string $date ): void {
        $this->removed_date = $date;
    }

    /**
     * Set removal user id
     *
     * @param int $userid
     *
     * @return void
     */
    public function set_removal_user( int $userid ): void {
        $this->removed_user = $userid;
    }

    /**
     * Approve registration
     *
     * @param int               $user_id approving user.
     * @param DateTimeImmutable $now     approval time (site-local, see Clock_Interface).
     *
     * @return void
     */
    public function approve( int $user_id, DateTimeImmutable $now ): void {
        if ( 'pending' !== $this->status ) {
            throw new Invalid_Status_Exception( 'Registration is not pending approval' );
        }
        $this->status       = 'approved';
        $this->created_date = $now->format( self::DATE_FORMAT );
        $this->created_user = $user_id;
    }

    /**
     * Remove registration
     *
     * @param int               $user_id removing user.
     * @param DateTimeImmutable $now     removal time (site-local, see Clock_Interface).
     *
     * @return void
     */
    public function remove( int $user_id, DateTimeImmutable $now ): void {
        if ( 'removed' === $this->status ) {
            throw new Invalid_Status_Exception( 'Registration is already removed' );
        }
        $this->status       = 'removed';
        $this->removed_date = $now->format( self::DATE_FORMAT );
        $this->removed_user = $user_id;
    }
}
