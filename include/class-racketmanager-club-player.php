<?php
/**
 * Racketmanager_Club_Player API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club Player
 */

namespace Racketmanager;

/**
 * Class to implement the Club_Player object
 */
final class Racketmanager_Club_Player {
    /**
     * Id
     *
     * @var int
     */
    public int $id;
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
     * Club Player id
     *
     * @var int
     */
    public int $club_player_id;
    /**
     * Removed date
     *
     * @var string|null
     */
    public ?string $removed_date;
    /**
     * Removed user
     *
     * @var int|null
     */
    public ?int $removed_user;
    /**
     * Removed username
     *
     * @var string
     */
    public string $removed_user_name;
    /**
     * Removed user email
     *
     * @var string
     */
    public string $removed_user_email;
    /**
     * Created date
     *
     * @var string
     */
    public string $created_date;
    /**
     * Created user
     *
     * @var int|null
     */
    public ?int $created_user;
    /**
     * Requested date
     *
     * @var string|null
     */
    public ?string $requested_date;
    /**
     * Created username
     *
     * @var string
     */
    public string $created_user_name;
    /**
     * Created user email
     *
     * @var string
     */
    public string $created_user_email;
    /**
     * Requested user
     *
     * @var int|null
     */
    public ?int $requested_user;
    /**
     * Requested user name
     *
     * @var string
     */
    public string $requested_user_name;
    /**
     * Requested user email
     *
     * @var string
     */
    public string $requested_user_email;
    /**
     * Club
     *
     * @var object
     */
    public object $club;
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
    public bool|null $system_record;
    /**
     * Retrieve club_player instance
     *
     * @param int $club_player_id club player id or name.
     */
    public static function get_instance( int $club_player_id ) {
        global $wpdb;
        if ( ! $club_player_id ) {
            return false;
        }
        $club_player = wp_cache_get( $club_player_id, 'club_players' );
        if ( ! $club_player ) {
            $club_player = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `requested_date`, `requested_user` FROM $wpdb->racketmanager_club_players WHERE `id` = %d LIMIT 1",
                    $club_player_id
                )
            ); // db call ok.
            if ( ! $club_player ) {
                return false;
            }
            $club_player = new Racketmanager_Club_Player( $club_player );
            wp_cache_set( $club_player_id, $club_player, 'club_players' );
        }
        return $club_player;
    }
    /**
     * Constructor
     *
     * @param object|null $club_player Club_Player object.
     */
    public function __construct( object $club_player = null ) {
        if ( ! is_null( $club_player ) ) {
            foreach ( get_object_vars( $club_player ) as $key => $value ) {
                $this->$key = $value;
            }
            if ( ! isset( $this->id ) ) {
                $this->add();
            }
            $this->club_player_id = $this->id;
            if ( $this->club_id ) {
                $club = get_club( $this->club_id );
                if ( $club ) {
                    $this->club = $club;
                }
            }
            if ( $this->player_id ) {
                $player = get_player( $this->player_id );
                if ( $player ) {
                    $this->player = $player;
                }
            }
            if ( ! empty( $this->removed_user ) ) {
                $removed_user_details = get_userdata( $this->removed_user );
                if ( $removed_user_details ) {
                    $this->removed_user_name  = $removed_user_details->display_name;
                    $this->removed_user_email = $removed_user_details->user_email;
                }
            }
            if ( ! empty( $this->created_user ) ) {
                $created_user_details = get_userdata( $this->created_user );
                if ( $created_user_details ) {
                    $this->created_user_name  = $created_user_details->display_name;
                    $this->created_user_email = $created_user_details->user_email;
                }
            }
            if ( ! empty( $this->requested_user ) ) {
                $requested_user_details = get_userdata( $this->requested_user );
                if ( $requested_user_details ) {
                    $this->requested_user_name  = $requested_user_details->display_name;
                    $this->requested_user_email = $requested_user_details->user_email;
                }
            }
        }
    }
    /**
     * Create new club player
     */
    private function add(): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_club_players (`club_id`, `player_id`, `requested_date`, `requested_user` ) VALUES (%d, %d, now(), %d)",
                $this->club_id,
                $this->player_id,
                get_current_user_id()
            )
        );
        $this->id = $wpdb->insert_id;
    }
    /**
     * Approve Club Player
     */
    public function approve(): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_club_players SET `created_date` = NOW(), `created_user` = %d WHERE `id` = %d",
                get_current_user_id(),
                $this->id
            )
        );
        wp_cache_set( $this->id, $this, 'club_players' );
    }
    /**
     * Remove Club Player
     */
    public function remove(): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_club_players SET `removed_date` = NOW(), `removed_user` = %d WHERE `id` = %d",
                get_current_user_id(),
                $this->id
            )
        );
        wp_cache_set( $this->id, $this, 'club_players' );
    }
    /**
     * Delete Club Player
     */
    public function delete(): void {
        global $wpdb;
        $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_club_players WHERE `id` = %d",
                $this->id
            )
        );
        wp_cache_delete( $this->id, 'club_players' );
    }
}
