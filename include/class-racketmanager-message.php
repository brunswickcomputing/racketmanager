<?php
/**
 * Racketmanager_Message API: message class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Message
 */

namespace Racketmanager;

/**
 * Class to implement the message object
 */
final class Racketmanager_Message {
	/**
	 * Id
	 *
	 * @var int
	 */
	public int $id;
	/**
	 * Subject
	 *
	 * @var string
	 */
	public string $subject;
	/**
	 * Userid
	 *
	 * @var int
	 */
	public int $userid;
	/**
	 * Status
	 *
	 * @var string
	 */
	public string $status;
	/**
	 * Date
	 *
	 * @var string
	 */
	public string $date;
	/**
	 * Sender
	 *
	 * @var string
	 */
	public string $sender;
	/**
	 * From name
	 *
	 * @var string
	 */
	public string $from_name;
	/**
	 * From email
	 *
	 * @var string
	 */
	public string $from_email;
	/**
	 * Message detail stored in database
	 *
	 * @var string
	 */
	public string $message_object;

	/**
	 * Get class instance
	 *
	 * @param int $message_id id.
	 */
	public static function get_instance( int $message_id ) {
		global $wpdb;
		if ( ! $message_id ) {
			return false;
		}
		$message = wp_cache_get( $message_id, 'message' );

		if ( ! $message ) {
			$message = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `id`, `userid`, `date`, `status`, `subject`, `sender`, `message_object` FROM {$wpdb->racketmanager_messages} WHERE `id` = %d LIMIT 1",
					$message_id
				)
			);  // db call ok.

			if ( ! $message ) {
				return false;
			}

			$message = new Racketmanager_Message( $message );

			wp_cache_set( $message->id, $message, 'message' );
		}

		return $message;
	}

	/**
	 * Construct class instance
	 *
	 * @param object|null $message message object.
	 */
	public function __construct( object $message = null ) {
		if ( ! is_null( $message ) ) {
			foreach ( get_object_vars( $message ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( ! isset( $this->id ) ) {
				$this->add();
			}
			$sender           = $this->sender;
			$bracket_pos      = strpos( $sender, '<' );
			$this->from_name  = '';
			$this->from_email = '';
			if ( false !== $bracket_pos ) {
				// Text before the bracketed email is the "From" name.
				if ( $bracket_pos > 0 ) {
					$from_name       = substr( $sender, 0, $bracket_pos );
					$from_name       = str_replace( '"', '', $from_name );
					$from_name       = trim( $from_name );
					$this->from_name = $from_name;
				}
				$from_email       = substr( $sender, $bracket_pos + 1 );
				$from_email       = str_replace( '>', '', $from_email );
				$from_email       = trim( $from_email );
				$this->from_email = $from_email;
			}
		}
	}

	/**
	 * Add new message
	 */
	private function add(): void {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_messages} (`userid`, `date`, `status`, `subject`, `sender`, `message_object`) VALUES (%d, %s, %s, %s, %s, %s)",
				$this->userid,
				$this->date,
				$this->status,
				$this->subject,
				$this->sender,
				$this->message_object
			)
		);
		$this->id = $wpdb->insert_id;
	}

	/**
	 * Delete message
	 */
	public function delete(): \mysqli_result|bool|int|null {
		global $wpdb;
		return $wpdb->delete( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->racketmanager_messages,
			array( 'id' => $this->id ),
			array( '%d' ),
		);
	}
	/**
	 * Set message status
	 *
	 * @param string $status status value.
	 */
	public function set_status( string $status ): true {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_messages} set `status` = %s WHERE `id` = %d",
				$status,
				$this->id
			)
		);  // db call ok.
		wp_cache_delete( $this->id, 'message' );
		return true;
	}
}
