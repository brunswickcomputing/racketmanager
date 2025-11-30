<?php
/**
 * Player_Error API: player error class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Player
 */

namespace Racketmanager\Domain;

/**
 * Class to implement the Player Error object
 */
final class Player_Error {
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
     * @param object|null $player_error Player Error object.
     */
    public function __construct( ?object $player_error = null ) {
        if ( ! is_null( $player_error ) ) {
            foreach ( $player_error as $key => $value ) {
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
