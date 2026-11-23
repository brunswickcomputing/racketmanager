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

    /** Status constants */
    public const string STATUS_ACTIVE   = 'active';
    public const string STATUS_APPROVED = 'approved';
    public const string STATUS_PENDING  = 'pending';
    public const string STATUS_REMOVED  = 'removed';

    /**
     * Id
     *
     * @var int|null
     */
    public private(set) ?int $id = null;

    /**
     * Player id
     *
     * @var int|null
     */
    public private(set) ?int $player_id = null;

    /**
     * Club id
     *
     * @var int|null
     */
    public private(set) ?int $club_id = null;

    /**
     * Removed date
     *
     * @var string|null
     */
    public private(set) ?string $removed_date = null;

    /**
     * Removed user
     *
     * @var int|null
     */
    public private(set) ?int $removed_user = null;

    /**
     * Created date
     *
     * @var string|null
     */
    public private(set) ?string $created_date = null;

    /**
     * Created user
     *
     * @var int|null
     */
    public private(set) ?int $created_user = null;

    /**
     * Requested date
     *
     * @var string|null
     */
    public private(set) ?string $requested_date = null;

    /**
     * Requested user
     *
     * @var int|null
     */
    public private(set) ?int $requested_user = null;

    /**
     * Player
     *
     * @var object|null
     */
    public private(set) ?object $player = null;

    /**
     * System record
     *
     * @var bool|null
     */
    public private(set) ?bool $system_record = null;

    /**
     * Status
     *
     * @var string|null
     */
    public private(set) ?string $status = null;

    /**
     * Constructor
     *
     * @param Club_Player_Hydration_DTO|null $dto hydration DTO.
     */
    public function __construct( ?Club_Player_Hydration_DTO $dto = null ) {
        if ( null !== $dto ) {
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
     * Approve registration
     *
     * @param int               $user_id approving user.
     * @param DateTimeImmutable $now     approval time (site-local, see Clock_Interface).
     *
     * @return void
     * @throws Invalid_Status_Exception Registration is not pending approval.
     */
    public function approve( int $user_id, DateTimeImmutable $now ): void {
        if ( self::STATUS_PENDING !== $this->status ) {
            throw new Invalid_Status_Exception( 'Registration is not pending approval' );
        }
        $this->status       = self::STATUS_APPROVED;
        $this->created_date = $this->format_date( $now );
        $this->created_user = $user_id;
    }

    /**
     * Remove registration
     *
     * @param int               $user_id removing user.
     * @param DateTimeImmutable $now     removal time (site-local, see Clock_Interface).
     *
     * @return void
     * @throws Invalid_Status_Exception Registration is already removed.
     */
    public function remove( int $user_id, DateTimeImmutable $now ): void {
        if ( self::STATUS_REMOVED === $this->status ) {
            throw new Invalid_Status_Exception( 'Registration is already removed' );
        }
        $this->status       = self::STATUS_REMOVED;
        $this->removed_date = $this->format_date( $now );
        $this->removed_user = $user_id;
    }

    /**
     * Format timestamp according to domain date format.
     *
     * @param DateTimeImmutable $date Date to format.
     * @return string
     */
    private function format_date( DateTimeImmutable $date ): string {
        return $date->format( self::DATE_FORMAT );
    }

    /**
     * Check if registration is active.
     *
     * @return bool
     */
    public function is_active(): bool {
        return self::STATUS_ACTIVE === $this->status || self::STATUS_APPROVED === $this->status;
    }

    /**
     * Check if registration is pending.
     *
     * @return bool
     */
    public function is_pending(): bool {
        return self::STATUS_PENDING === $this->status;
    }

    /**
     * Check if registration is removed.
     *
     * @return bool
     */
    public function is_removed(): bool {
        return self::STATUS_REMOVED === $this->status;
    }

    /**
     * Check if registration is a system record.
     *
     * @return bool
     */
    public function is_system_record(): bool {
        return true === $this->system_record;
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
     * @param int|null $id Id for player.
     *
     * @return void
     */
    public function set_id( ?int $id ): void {
        $this->id = $id;
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
     * Get player
     *
     * @return object|null
     */
    public function get_player(): ?object {
        return $this->player;
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
     * Get system record
     *
     * @return bool|null
     */
    public function get_system_record(): ?bool {
        return $this->system_record;
    }

    /**
     * Get created date
     *
     * @return string|null
     */
    public function get_created_date(): ?string {
        return $this->created_date;
    }

    /**
     * Get created user
     *
     * @return int|null
     */
    public function get_created_user(): ?int {
        return $this->created_user;
    }

    /**
     * Get requested date
     *
     * @return string|null
     */
    public function get_requested_date(): ?string {
        return $this->requested_date;
    }

    /**
     * Get requested user
     *
     * @return int|null
     */
    public function get_requested_user(): ?int {
        return $this->requested_user;
    }

    /**
     * Get removed date
     *
     * @return string|null
     */
    public function get_removed_date(): ?string {
        return $this->removed_date;
    }

    /**
     * Get removed user
     *
     * @return int|null
     */
    public function get_removed_user(): ?int {
        return $this->removed_user;
    }
}
