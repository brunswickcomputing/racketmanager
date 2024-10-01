<?php
/**
 * Racketmanager_Tournament_Entry API: tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Tournament
 */

namespace Racketmanager;

/**
 * Class to implement the Tournament Entry object
 */
final class Racketmanager_Tournament_Entry {
	/**
	 * Id
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Tournament id
	 *
	 * @var int
	 */
	public $tournament_id;
	/**
	 * Player id
	 *
	 * @var int
	 */
	public $player_id;
	/**
	 * Status
	 *
	 * @var int
	 */
	public $status;
	/**
	 * Retrieve tournament entry instance
	 *
	 * @param int    $tournament_entry_id tournament entry id.
	 * @param string $search_term search term - defaults to id.
	 * @return object
	 */
	public static function get_instance( $tournament_entry_id, $search_term = 'id' ) {
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
				$tournament_entry_id = (int) $tournament_entry_id;
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
				"SELECT `id`, `tournament_id`, `player_id`, `status` FROM {$wpdb->racketmanager_tournament_entries} WHERE $search"
			); // db call ok.
			if ( ! $tournament_entry ) {
				return false;
			}
			$tournament_entry = new Racketmanager_Tournament_Entry( $tournament_entry );
			wp_cache_set( $tournament_entry_id, $tournament_entry, 'tournament_entries' );
		}
		return $tournament_entry;
	}
	/**
	 * Constructor
	 *
	 * @param object $tournament_entry Tournament Entry object.
	 */
	public function __construct( $tournament_entry = null ) {
		if ( ! is_null( $tournament_entry ) ) {
			foreach ( $tournament_entry as $key => $value ) {
				$this->$key = $value;
			}

			if ( ! isset( $this->id ) ) {
				$this->id = $this->add();
			}
		}
	}
	/**
	 * Add tournament entry
	 */
	private function add() {
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
					"INSERT INTO {$wpdb->racketmanager_tournament_entries} (`tournament_id`, `player_id`, `status`) VALUES (%d, %d, %d)",
					$this->tournament_id,
					$this->player_id,
					$this->status,
				)
			);
			$racketmanager->set_message( __( 'Tournament entry added', 'racketmanager' ) );
			$this->id = $wpdb->insert_id;
			return $this->id;
		} else {
			$racketmanager->set_message( implode( '<br>', $err_msg ), true );
			return false;
		}
	}
	/**
	 * Confirm tournament entry
	 */
	public function confirm() {
		global $wpdb;
		$this->status = 1;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_tournament_entries} SET `status` = %d WHERE `id` = %d",
				$this->status,
				$this->id
			)
		);
	}
	/**
	 * Delete tournament entry
	 */
	public function delete() {
		global $wpdb, $racketmanager;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_tournament_entries} WHERE `id` = %d",
				$this->id
			)
		);
		$racketmanager->set_message( __( 'Tournament Entry Deleted', 'racketmanager' ) );
		wp_cache_flush_group( 'tournaments' );
	}
}
