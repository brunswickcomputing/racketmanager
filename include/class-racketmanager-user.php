<?php
/**
 * Racketmanager_User API: user class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage User
 */

namespace Racketmanager;

/**
 * Class to implement the User object
 */
final class Racketmanager_User {
	/**
	 * Id.
	 *
	 * @var int
	 */
	public $ID;
	/**
	 * ID.
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Email address.
	 *
	 * @var string
	 */
	public $email;
	/**
	 * User Email address.
	 *
	 * @var string
	 */
	public $user_email;
	/**
	 * Fullname - join of first name and surname.
	 *
	 * @var string
	 */
	public $fullname;
	/**
	 * Display name.
	 *
	 * @var string
	 */
	public $display_name;
	/**
	 * Date player created.
	 *
	 * @var string
	 */
	public $created_date;
	/**
	 * Date player created.
	 *
	 * @var string
	 */
	public $user_registered;
	/**
	 * First name.
	 *
	 * @var string
	 */
	public $firstname;
	/**
	 * Surname.
	 *
	 * @var string
	 */
	public $surname;
	/**
	 * Gender.
	 *
	 * @var string
	 */
	public $gender;
	/**
	 * Type.
	 *
	 * @var string
	 */
	public $type;
	/**
	 * LTA Membership Number.
	 *
	 * @var int
	 */
	public $btm;
	/**
	 * Year of birth.
	 *
	 * @var int
	 */
	public $year_of_birth;
	/**
	 * Age.
	 *
	 * @var int
	 */
	public $age;
	/**
	 * Contact Number.
	 *
	 * @var string
	 */
	public $contactno;
	/**
	 * Removed date.
	 *
	 * @var string
	 */
	public $removed_date;
	/**
	 * Removed user.
	 *
	 * @var int
	 */
	public $removed_user;
	/**
	 * Locked indicator.
	 *
	 * @var boolean
	 */
	public $locked;
	/**
	 * Locked date.
	 *
	 * @var string
	 */
	public $locked_date;
	/**
	 * Locked user.
	 *
	 * @var int
	 */
	public $locked_user;
	/**
	 * Locked user name.
	 *
	 * @var string
	 */
	public $locked_user_name;
	/**
	 * System record.
	 *
	 * @var string
	 */
	public $system_record;
	/**
	 * Matches.
	 *
	 * @var array
	 */
	public $matches = array();
	/**
	 * Statistics.
	 *
	 * @var array
	 */
	public $statistics = array();
	/**
	 * Retrieve user instance
	 *
	 * @param int $user_id user id.
	 */
	public static function get_instance( $user_id ) {
		if ( ! $user_id ) {
			return false;
		}
		$user = wp_cache_get( $user_id, 'users' );
		$user = new Racketmanager_User( $user );
		return $user;
	}

	/**
	 * Constructor
	 *
	 * @param object $user User object.
	 */
	public function __construct( $user = null ) {
		if ( ! is_null( $user ) ) {
			foreach ( $user as $key => $value ) {
				$this->$key = $value;
			}
			$this->id            = $this->ID;
			$this->email         = $this->user_email;
			$this->fullname      = $this->display_name;
			$this->created_date  = $this->user_registered;
			$this->firstname     = get_user_meta( $this->ID, 'first_name', true );
			$this->surname       = get_user_meta( $this->ID, 'last_name', true );
			$this->gender        = get_user_meta( $this->ID, 'gender', true );
			$this->type          = get_user_meta( $this->ID, 'racketmanager_type', true );
			$this->btm           = get_user_meta( $this->ID, 'btm', true );
			$this->year_of_birth = get_user_meta( $this->ID, 'year_of_birth', true );
			if ( $this->year_of_birth ) {
				$this->age = gmdate( 'Y' ) - intval( $this->year_of_birth );
			} else {
				$this->age = 0;
			}
			$this->contactno    = get_user_meta( $this->ID, 'contactno', true );
			$this->removed_date = get_user_meta( $this->ID, 'remove_date', true );
			$this->removed_user = get_user_meta( $this->ID, 'remove_user', true );
			$this->locked       = get_user_meta( $this->ID, 'locked', true );
			$this->locked_date  = get_user_meta( $this->ID, 'locked_date', true );
			$this->locked_user  = get_user_meta( $this->ID, 'locked_user', true );
			if ( $this->locked_user ) {
				$this->locked_user_name = get_userdata( $this->locked_user )->display_name;
			} else {
				$this->locked_user_name = '';
			}
			$this->system_record = get_user_meta( $this->ID, 'leaguemanager_type', true );
		}
	}

	/**
	 * Get messages function
	 *
	 * @param array $args seartcy arguments.
	 * @return array||false
	 */
	public function get_messages( $args ) {
		global $wpdb;

		$defaults = array(
			'count'   => false,
			'status'  => false,
			'orderby' => array( 'date' => 'DESC' ),
		);
		$args     = array_merge( $defaults, (array) $args );
		$count    = $args['count'];
		$status   = $args['status'];
		$orderby  = $args['orderby'];

		$search_terms = array();
		if ( $status ) {
			switch ( $status ) {
				case 'unread':
					$status = '1';
					break;
				case 'read':
					$status = '0';
					break;
				default:
					break;
			}
			$search_terms[] = $wpdb->prepare( '`status` = %s', $status );
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search = implode( ' AND ', $search_terms );
		}

		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$order = $orderby_string;

		if ( $count ) {
			$sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_messages} WHERE `userid` = $this->ID";
			if ( '' !== $search ) {
				$sql .= " AND $search";
			}
			return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
		}

		$sql = "SELECT `id` FROM {$wpdb->racketmanager_messages} WHERE `userid` = $this->ID";
		if ( '' !== $search ) {
			$sql .= " AND $search";
		}
		if ( '' !== $order ) {
			$sql .= " ORDER BY $order";
		}

		$messages = wp_cache_get( md5( $sql ), 'messages' );
		if ( ! $messages ) {
			$messages = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			);
			wp_cache_set( md5( $sql ), $messages, 'messages' );
		}
		$i = 0;
		foreach ( $messages as $message ) {
			$message_dtl    = get_message( $message->id );
			$messages[ $i ] = $message_dtl;
			++$i;
		}
		return $messages;
	}
	/**
	 * Delete messages function
	 *
	 * @param string $type type of messages to delete.
	 * @return int||error object
	 */
	public function delete_messages( $type ) {
		global $wpdb;
		return $wpdb->delete( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->racketmanager_messages,
			array( 'status' => $type ),
			array( '%s' ),
		);
	}
}
