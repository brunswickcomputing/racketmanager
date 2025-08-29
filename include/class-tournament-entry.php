<?php
/**
 * Tournament_Entry API: tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Tournament
 */

namespace Racketmanager;

/**
 * Class to implement the Tournament Entry object
 */
final class Tournament_Entry {
    /**
     * Id
     *
     * @var int|false
     */
    public int|false $id;
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
     * @var string|null
     */
    public ?string $fee;
    /**
     * Club id
     *
     * @var int|null
     */
    public ?int $club_id;
    /**
     * Club
     *
     * @var object|null
     */
    public null|object $club = null;

    /**
     * Retrieve tournament entry instance
     *
     * @param int|string $tournament_entry_id tournament entry id.
     * @param string $search_term search term - defaults to id.
     *
     * @return object|false
     */
    public static function get_instance( int|string $tournament_entry_id, string $search_term = 'id' ): object|false {
        global $wpdb;
        if ( ! $tournament_entry_id ) {
            return false;
        }
        switch ( $search_term ) {
            case 'key':
                $search_terms  = explode( '_', $tournament_entry_id );
                $tournament_id = $search_terms[0];
                $player_id     = $search_terms[1];
                $search        = $wpdb->prepare(
                    '`tournament_id` = %d AND `player_id` = %d',
                    intval( $tournament_id ),
                    $player_id,
                );
                break;
            case 'id':
            default:
                $search              = $wpdb->prepare(
                    '`id` = %d',
                    $tournament_entry_id
                );
                break;
        }
        $tournament_entry = wp_cache_get( $tournament_entry_id, 'tournament_entries' );
        if ( ! $tournament_entry ) {
            $tournament_entry = $wpdb->get_row(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT `id`, `tournament_id`, `player_id`, `status`, `fee`, `club_id` FROM $wpdb->racketmanager_tournament_entries WHERE $search"
            ); // db call ok.
            if ( ! $tournament_entry ) {
                return false;
            }
            $tournament_entry = new Tournament_Entry( $tournament_entry );
            wp_cache_set( $tournament_entry_id, $tournament_entry, 'tournament_entries' );
        }
        return $tournament_entry;
    }
    /**
     * Constructor
     *
     * @param object|null $tournament_entry Tournament Entry object.
     */
    public function __construct( ?object $tournament_entry = null ) {
        if ( ! is_null( $tournament_entry ) ) {
            foreach ( $tournament_entry as $key => $value ) {
                $this->$key = $value;
            }
            if ( ! isset( $this->id ) ) {
                $this->id = $this->add();
            }
            if ( ! empty( $this->club_id ) ) {
                $club = get_club( $this->club_id );
                if ( $club ) {
                    $this->club = $club;
                }
            }
        }
    }

    /**
     * Add tournament entry
     *
     * @return false|int|mixed
     */
    private function add(): mixed {
        global $wpdb, $racketmanager;
        $valid   = true;
        $err_msg = array();
        if ( empty( $this->tournament_id ) ) {
            $valid     = false;
            $err_msg[] = __( 'Tournament is required', 'racketmanager' );
        }
        if ( empty( $this->player_id ) ) {
            $valid     = false;
            $err_msg[] = __( 'Player is required', 'racketmanager' );
        }
        if ( $valid ) {
            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "INSERT INTO $wpdb->racketmanager_tournament_entries (`tournament_id`, `player_id`, `status`) VALUES (%d, %d, %d)",
                    $this->tournament_id,
                    $this->player_id,
                    $this->status,
                )
            );
            $racketmanager->set_message( __( 'Tournament entry added', 'racketmanager' ) );
            $this->id = $wpdb->insert_id;
            if ( ! empty( $this->fee ) ) {
                $this->set_fee( $this->fee );
            }
            if ( ! empty( $this->club_id ) ) {
                $this->set_club( $this->club_id );
            }
            return $this->id;
        } else {
            $racketmanager->set_message( implode( '<br>', $err_msg ), true );
            return false;
        }
    }
    /**
     * Set tournament entry status
     *
     * @param int $status status.
     * @param false|string $fee tournament fee.
     */
    public function set_status( int $status, false|string $fee = false ): void {
        global $wpdb;
        $this->status = $status;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_tournament_entries SET `status` = %d WHERE `id` = %d",
                $this->status,
                $this->id
            )
        );
        if ( ! empty( $fee ) ) {
            $this->set_fee( $fee );
        }
    }
    /**
     * Set tournament entry fee
     *
     * @param string $fee tournament fee.
     */
    public function set_fee( string $fee ): void {
        global $wpdb;
        if ( ! empty( $fee ) ) {
            $this->fee = $fee;
            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_tournament_entries SET `fee` = %d WHERE `id` = %d",
                    $this->fee,
                    $this->id
                )
            );
        }
    }
    /**
     * Set club
     *
     * @param int $club club id.
     */
    public function set_club( int $club ): void {
        global $wpdb;
        if ( ! empty( $club ) ) {
            $this->club_id = $club;
            $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                    "UPDATE $wpdb->racketmanager_tournament_entries SET `club_id` = %d WHERE `id` = %d",
                    $this->club_id,
                    $this->id
                )
            );
        }
    }
    /**
     * Delete tournament entry
     */
    public function delete(): void {
        global $wpdb, $racketmanager;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_tournament_entries WHERE `id` = %d",
                $this->id
            )
        );
        $racketmanager->set_message( __( 'Tournament Entry Deleted', 'racketmanager' ) );
        wp_cache_delete( $this->id, 'tournament_entries' );
    }
}
