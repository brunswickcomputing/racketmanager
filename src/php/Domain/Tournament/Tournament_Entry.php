<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Tournament;

use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Hydration_DTO;

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
     * @param Tournament_Entry_Hydration_DTO|null $dto hydration DTO.
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
     * @param object $row tournament entry row.
     *
     * @return self
     */
    public static function from_object( object $row ): self {
        return Tournament_Entry_Factory::from_object( $row );
    }

    /**
     * Create from an array via Tournament_Entry_Factory.
     *
     * @param array $data tournament entry data.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return Tournament_Entry_Factory::from_array( $data );
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
