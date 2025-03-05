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
	public $id;
	/**
	 * Retrieve club_player instance
	 *
	 * @param int    $club_player_id club player id or name.
	 * @param string $search_term search.
	 */
	public static function get_instance( $club_player_id ) {
		global $wpdb;

		if ( ! $club_player_id ) {
			return false;
		}

		$club_player = wp_cache_get( $club_player_id, 'club_players' );

		if ( ! $club_player ) {
			$club_player = $wpdb->get_row(
										  $wpdb->prepare(
														 "SELECT `id`, `player_id`, `system_record`, `club_id`, `removed_date`, `removed_user`, `created_date`, `created_user`, `updated` FROM {$wpdb->racketmanager_club_players} WHERE `id` = %d LIMIT 1",
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
	 * @param object $club_player Club_Player object.
	 */
	public function __construct( $club_player = null ) {
		if ( ! is_null( $club_player ) ) {
			foreach ( get_object_vars( $club_player ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( ! isset( $this->id ) ) {
				$this->add();
			}
			$this->club_player_id = $this->id;
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
		}
	}

	/**
	 * Create new club player
	 */
	private function add() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_club_players} (`club_id`, `player_id`, `created_date`, `created_user` ) VALUES (%d, %d, now(), %d)",
				$this->club_id,
				$this->player_id,
				get_current_user_id()
			)
		);
		$this->id = $wpdb->insert_id;
	}
	/**
	 * Remove Club Player
	 */
	public function remove() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_club_players} SET `removed_date` = NOW(), `removed_user` = %d WHERE `id` = %d",
				get_current_user_id(),
				$this->id
			)
		);
		wp_cache_set( $this->id, $this, 'club_players' );
	}
	/**
	 * Delete Club Player
	 */
	public function delete() {
		global $wpdb;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_club_players} WHERE `id` = %d",
				$this->id
			)
		);
		wp_cache_delete( $this->id, 'club_players' );
	}
}
