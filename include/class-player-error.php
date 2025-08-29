<?php
/**
 * Player_Error API: player error class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Player
 */

namespace Racketmanager;

/**
 * Class to implement the Player Error object
 */
final class Player_Error {
    /**
     * Id
     *
     * @var int|false
     */
    public int|false $id;
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
     * Updated date
     *
     * @var string|null
     */
    public ?string $updated_date;
    /**
     * Updated user
     *
     * @var string|null
     */
    public ?string $updated_user;
    /**
     * Player
     *
     * @var object|null
     */
    public null|object $player = null;

    /**
     * Retrieve player error instance
     *
     * @param int $player_error_id player error id.
     * @return object|false
     */
    public static function get_instance( int $player_error_id ): object|false {
        global $wpdb;
        if ( ! $player_error_id ) {
            return false;
        }
        $player_error = wp_cache_get( $player_error_id, 'player_errors' );
        if ( ! $player_error ) {
            $player_error = $wpdb->get_row(
               $wpdb->prepare(
                    "SELECT `id`, `player_id`, `status`, `created_date`, `updated_date`, `updated_user`, `message` FROM $wpdb->racketmanager_player_errors WHERE id = %d",
                    $player_error_id,
                )
            );
            if ( ! $player_error ) {
                return false;
            }
            $player_error = new Player_Error( $player_error );
            wp_cache_set( $player_error_id, $player_error, 'player_errors' );
        }
        return $player_error;
    }
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
            if ( ! isset( $this->id ) ) {
                $this->id = $this->add();
            }
            if ( ! empty( $this->player_id ) ) {
                $player = get_player( $this->player_id );
                if ( $player ) {
                    $this->player = $player;
                }
            }
        }
    }
    /**
     * Add player error
     */
    private function add(): false|int {
        global $wpdb, $racketmanager;
        $valid   = true;
        $err_msg = array();
        if ( empty( $this->player_id ) ) {
            $valid     = false;
            $err_msg[] = __( 'Player is required', 'racketmanager' );
        }
        if ( empty( $this->message ) ) {
            $valid     = false;
            $err_msg[] = __( 'Message is required', 'racketmanager' );
        }
        if ( $valid ) {
            if ( empty( $this->status ) ) {
                $this->status = 0;
            }
            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "INSERT INTO $wpdb->racketmanager_player_errors (`player_id`, `message`, `status`, `created_date`) VALUES (%d, %s, %d, NOW())",
                    $this->player_id,
                    $this->message,
                    $this->status,
                )
            );
            $racketmanager->set_message( __( 'Player error added', 'racketmanager' ) );
            $this->id = $wpdb->insert_id;
            return $this->id;
        } else {
            $racketmanager->set_message( implode( '<br>', $err_msg ), true );
            return false;
        }
    }
    /**
     * Set player error status
     *
     * @param int $status status.
     */
    public function set_status( int $status ): void {
        global $wpdb;
        $this->status = $status;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_player_errors SET `status` = %d WHERE `id` = %d",
                $this->status,
                $this->id
            )
        );
    }
    /**
     * Delete player error
     */
    public function delete(): void {
        global $wpdb, $racketmanager;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_player_errors WHERE `id` = %d",
                $this->id
            )
        );
        $racketmanager->set_message( __( 'Player Error Deleted', 'racketmanager' ) );
        wp_cache_flush_group( 'player_errors' );
    }
}
