<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Tournament;

/**
 * Class to implement the Tournament Entry object (moved to PSR-4)
 */
class Tournament_Entry {
    /**
     * Id
     *
     * @var int|null
     */
    public int|null $id = null;
    /**
     * Tournament id
     *
     * @var int
     */
    public int $tournament_id;
    /**
     * Player id
     *
     * @var int
     */
    public int $player_id;
    /**
     * Status
     *
     * @var int
     */
    public int $status;
    /**
     * Fee
     *
     * @var int|null
     */
    public ?int $fee = null;
    /**
     * Club id
     *
     * @var int|null
     */
    public ?int $club_id = null;
    /**
     * Club
     *
     * @var object|null
     */
    public null|object $club = null;

    /**
     * Constructor
     *
     * @param object|null $tournament_entry Tournament Entry object.
     */
    public function __construct( ?object $tournament_entry = null ) {
        if ( is_null( $tournament_entry ) ) {
            return;
        }
        $this->id            = $tournament_entry->id ?? null;
        $this->tournament_id = $tournament_entry->tournament_id;
        $this->player_id     = $tournament_entry->player_id;
        $this->status        = $tournament_entry->status;
        $this->fee           = $tournament_entry->fee;
        $this->club_id       = $tournament_entry->club_id ?? null;
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }

    public function get_tournament_id(): int {
        return $this->tournament_id;
    }

    /**
     * @param int $tournament_id
     */
    public function set_tournament_id( int $tournament_id ): void {
        $this->tournament_id = $tournament_id;
    }

    public function get_player_id(): int {
        return $this->player_id;
    }

    public function set_player_id( int $player_id ): void {
        $this->player_id = $player_id;
    }

    public function get_status(): int {
        return $this->status;
    }

    /**
     * Set tournament entry status
     *
     * @param int $status status.
     */
    public function set_status( int $status ): void {
        $this->status = $status;
    }

    public function get_fee(): ?int {
        return $this->fee;
    }

    /**
     * Set tournament entry fee
     *
     * @param int|null $fee tournament fee.
     */
    public function set_fee( ?int $fee ): void {
        $this->fee = $fee;
    }

    public function get_club_id(): ?int {
        return $this->club_id;
    }

    public function set_club_id( ?int $club_id ): void {
        $this->club_id = $club_id;
    }

}
