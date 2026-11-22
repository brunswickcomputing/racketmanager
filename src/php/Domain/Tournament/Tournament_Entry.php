<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Tournament;

use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Hydration_DTO;

/**
 * Class to implement the Tournament Entry object (moved to PSR-4)
 */
class Tournament_Entry {
    /**
     * Entry ID.
     *
     * @var int|null
     */
    public private(set) ?int $id = null;
    /**
     * Tournament ID.
     *
     * @var int
     */
    public private(set) int $tournament_id;
    /**
     * Player ID.
     *
     * @var int
     */
    public private(set) int $player_id;
    /**
     * Status.
     *
     * @var int
     */
    public private(set) int $status;
    /**
     * Fee.
     *
     * @var int|null
     */
    public private(set) ?int $fee = null;
    /**
     * Club ID.
     *
     * @var int|null
     */
    public private(set) ?int $club_id = null;
    /**
     * Club object.
     *
     * @var object|null
     */
    public private(set) ?object $club = null;

    /**
     * Constructor
     *
     * @param Tournament_Entry_Hydration_DTO|null $dto Hydration DTO.
     */
    public function __construct( ?Tournament_Entry_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }
        $this->id = $dto->id;
        if ( null !== $dto->tournament_id ) {
            $this->tournament_id = $dto->tournament_id;
        }
        if ( null !== $dto->player_id ) {
            $this->player_id = $dto->player_id;
        }
        if ( null !== $dto->status ) {
            $this->status = $dto->status;
        }
        $this->fee     = $dto->fee;
        $this->club_id = $dto->club_id;
    }

    /**
     * Create from a generic object (e.g. database row) via Tournament_Entry_Factory.
     *
     * @param object $row Tournament entry row.
     *
     * @return self
     */
    public static function from_object( object $row ): self {
        return Tournament_Entry_Factory::from_object( $row );
    }

    /**
     * Create from an array via Tournament_Entry_Factory.
     *
     * @param array $data Tournament entry data.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return Tournament_Entry_Factory::from_array( $data );
    }

    /**
     * Get entry ID.
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Set entry ID.
     *
     * @param int|null $insert_id Entry ID.
     */
    public function set_id( ?int $insert_id ): void {
        $this->id = $insert_id;
    }

    /**
     * Get tournament ID.
     *
     * @return int
     */
    public function get_tournament_id(): int {
        return $this->tournament_id;
    }

    /**
     * Set tournament ID.
     *
     * @param int $tournament_id Tournament ID.
     */
    public function set_tournament_id( int $tournament_id ): void {
        $this->tournament_id = $tournament_id;
    }

    /**
     * Get player ID.
     *
     * @return int
     */
    public function get_player_id(): int {
        return $this->player_id;
    }

    /**
     * Set player ID.
     *
     * @param int $player_id Player ID.
     */
    public function set_player_id( int $player_id ): void {
        $this->player_id = $player_id;
    }

    /**
     * Get entry status.
     *
     * @return int
     */
    public function get_status(): int {
        return $this->status;
    }

    /**
     * Set tournament entry status.
     *
     * @param int $status Status.
     */
    public function set_status( int $status ): void {
        $this->status = $status;
    }

    /**
     * Get fee.
     *
     * @return int|null
     */
    public function get_fee(): ?int {
        return $this->fee;
    }

    /**
     * Set tournament entry fee.
     *
     * @param int|null $fee Tournament fee.
     */
    public function set_fee( ?int $fee ): void {
        $this->fee = $fee;
    }

    /**
     * Get club ID.
     *
     * @return int|null
     */
    public function get_club_id(): ?int {
        return $this->club_id;
    }

    /**
     * Set club ID.
     *
     * @param int|null $club_id Club ID.
     */
    public function set_club_id( ?int $club_id ): void {
        $this->club_id = $club_id;
    }

    /**
     * Get club object.
     *
     * @return object|null
     */
    public function get_club(): ?object {
        return $this->club;
    }

    /**
     * Set club object.
     *
     * @param object|null $club Club object.
     */
    public function set_club( ?object $club ): void {
        $this->club = $club;
    }

    /**
     * Check if the entry is pending (status 0).
     *
     * @return bool
     */
    public function is_pending(): bool {
        return isset( $this->status ) && 0 === $this->status;
    }

    /**
     * Check if the entry is confirmed (status 2).
     *
     * @return bool
     */
    public function is_confirmed(): bool {
        return isset( $this->status ) && 2 === $this->status;
    }

    /**
     * Check if the entry is withdrawn (status 3).
     *
     * @return bool
     */
    public function is_withdrawn(): bool {
        return isset( $this->status ) && 3 === $this->status;
    }

    /**
     * Mark the entry as withdrawn.
     */
    public function withdraw(): void {
        $this->status = 3;
    }

    /**
     * Check whether entry has a fee assigned.
     *
     * @return bool
     */
    public function has_fee(): bool {
        return null !== $this->fee && $this->fee > 0;
    }

    /**
     * Check whether entry is associated with a club.
     *
     * @return bool
     */
    public function has_club(): bool {
        return null !== $this->club_id && $this->club_id > 0;
    }

    /**
     * Check whether entry belongs to a given tournament.
     *
     * @param int $tournament_id Tournament ID.
     *
     * @return bool
     */
    public function is_for_tournament( int $tournament_id ): bool {
        return isset( $this->tournament_id ) && $this->tournament_id === $tournament_id;
    }

    /**
     * Check whether entry belongs to a given player.
     *
     * @param int $player_id Player ID.
     *
     * @return bool
     */
    public function is_for_player( int $player_id ): bool {
        return isset( $this->player_id ) && $this->player_id === $player_id;
    }
}
