<?php
/**
 * Club_Player API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain;

use Racketmanager\Exceptions\Invalid_Status_Exception;

/**
 * Class to implement the Club_Player object
 */
final class Club_Player {
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
     * @param object|null $club_player Club_Player object.
     */
    public function __construct( ?object $club_player = null ) {
        if ( ! is_null( $club_player ) ) {
            foreach ( get_object_vars( $club_player ) as $key => $value ) {
                $this->$key = $value;
            }
        }
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
     * @param int $user_id
     *
     * @return void
     */
    public function approve( int $user_id ): void {
        if ($this->status !== 'pending') {
            throw new Invalid_Status_Exception( __('Registration is not pending approval', 'racketmanager' ) );
        }
        $this->status       = 'approved';
        $this->created_date = current_time('mysql');
        $this->created_user = $user_id;
    }

    /**
     * Remove registration
     *
     * @param int $user_id
     *
     * @return void
     */
    public function remove( int $user_id): void {
        if ($this->status === 'removed') {
            throw new Invalid_Status_Exception(__('Registration is already removed', 'racketmanager' ) );
        }
        $this->status       = 'removed';
        $this->removed_date = current_time('mysql');
        $this->removed_user = $user_id;
    }
}
