<?php
declare( strict_types=1 );
/**
 * Player_Error API: player error class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Player
 */
namespace Racketmanager\Domain\Player;

use Racketmanager\Domain\DTO\Player\Player_Error_Hydration_DTO;

/**
 * Class to implement the Player Error object
 */
class Player_Error {
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
     * Message
     *
     * @var string
     */
    public string $message;
    /**
     * Created date
     *
     * @var string
     */
    public string $created_date;

    /**
     * Constructor
     *
     * @param Player_Error_Hydration_DTO|null $dto hydration DTO.
     */
    public function __construct( ?Player_Error_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }
        $this->id = $dto->id;
        if ( null !== $dto->player_id ) {
            $this->player_id = $dto->player_id;
        }
        if ( null !== $dto->message ) {
            $this->message = $dto->message;
        }
        if ( null !== $dto->created_date ) {
            $this->created_date = $dto->created_date;
        }
    }

    /**
     * Create from a generic object (e.g., database row) without factory validation.
     *
     * @param object $row player error row.
     *
     * @return static
     */
    public static function from_object( object $row ): static {
        return new static( Player_Error_Hydration_DTO::from_object( $row ) );
    }

    /**
     * Create from an array without factory validation.
     *
     * @param array $data player error data.
     *
     * @return static
     */
    public static function from_array( array $data ): static {
        return static::from_object( (object) $data );
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
     * Get player id
     *
     * @return int
     */
    public function get_player_id(): int {
        return $this->player_id;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function get_message(): string {
        return $this->message;
    }

    /**
     * Get created date
     *
     * @return string
     */
    public function get_created_date(): string {
        return $this->created_date;
    }
}
