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
			$this->opt_ins       = get_user_meta( $this->ID, 'racketmanager_opt_in' );
		}
	}
	/**
	 * Update function
	 *
	 * @param object $user updated details.
	 * @return object||false
	 */
	public function update( $user ) {
		global $racketmanager;
		$valid = true;
		if ( empty( $user->email ) ) {
			$valid = false;
			$err_fld[] = 'username';
			$err_msg[] = Racketmanager_Util::get_error_message( 'empty_username' );
		}
		if ( empty( $user->firstname ) ) {
			$valid = false;
			$err_fld[] = 'firstname';
			$err_msg[] = Racketmanager_Util::get_error_message( 'firstname_field_empty' );
		}
		if ( empty( $user->surname ) ) {
			$valid = false;
			$err_fld[] = 'lastname';
			$err_msg[] = Racketmanager_Util::get_error_message( 'lastname_field_empty' );
		}
		if ( empty( $user->gender ) ) {
			$valid = false;
			$err_fld[] = 'gender';
			$err_msg[] = Racketmanager_Util::get_error_message( 'gender_field_empty' );
		}
		if ( empty( $user->btm ) ) {
			$player_options = $racketmanager->get_options( 'rosters' );
			if ( isset( $player_options['btm'] ) && '1' === $player_options['btm'] ) {
				$valid = false;
				$err_fld[] = 'btm';
				$err_msg[] = Racketmanager_Util::get_error_message( 'btm_field_empty' );
			}
		}
		if ( $user->password !== $user->re_password ) {
			$valid = false;
			$err_fld[] = 'password';
			$err_msg[] = Racketmanager_Util::get_error_message( 'password_reset_mismatch' );
		}
		if ( $valid ) {
			$updates = $this->set_details( $user );
			if ( $updates ) {
				$this->message = __( 'User updated', 'racketmanager' );
				$this->update_result = 'success';
			} else {
				$this->message = __( 'No updates', 'racketmanager' );
				$this->update_result = 'warning';
			}
		} else {
			$this->err_flds = $err_fld;
			$this->err_msgs = $err_msg;
			$this->message = __( 'Errors found', 'racketmanager' );
			$this->update_result = 'danger';
		}
		return $this;
	}
	/**
	 * Set details function
	 *
	 * @param object $user updated details.
	 * @return boolean
	 */
	private function set_details( $user ) {
		$updates = false;
		$updated = array();
		$updated_user = array();
		if ( $this->user_email !== $user->email ) {
			$updates               = true;
			$this->user_email      = $user->email;
			$updated['user_email'] = $user->email;
		}
		if ( $this->firstname !== $user->firstname ) {
			$updates               = true;
			$this->firstname       = $user->firstname;
			$updated['first_name'] = $user->firstname;
		}
		if ( $this->surname !== $user->surname ) {
			$updates              = true;
			$this->surname        = $user->surname;
			$updated['last_name'] = $user->surname;
		}
		if ( $this->contactno !== $user->contactno ) {
			$updates              = true;
			$this->contactno      = $user->contactno;
			$updated['contactno'] = $user->contactno;
		}
		if ( $this->gender !== $user->gender ) {
			$updates           = true;
			$this->gender      = $user->gender;
			$updated['gender'] = $user->gender;
		}
		if ( empty( $this->btm ) ) {
			if ( ! empty( $user->btm ) ) {
				$updates        = true;
				$this->btm      = $user->btm;
				$updated['btm'] = $user->btm;
			}
		} elseif ( intval( $this->btm ) !== intval( $user->btm ) ) {
			$updates        = true;
			$this->btm      = $user->btm;
			$updated['btm'] = $user->btm;
		}
		if ( intval( $this->year_of_birth ) !== intval( $user->year_of_birth ) ) {
			$updates                  = true;
			$this->year_of_birth      = $user->year_of_birth;
			$updated['year_of_birth'] = $user->year_of_birth;
		}
		if ( ! empty( $user->password ) ) {
			$updates             = true;
			$this->password      = $user->password;
			$updated['password'] = $user->password;
		}
		$opt_in_choices = Racketmanager_Util::get_email_opt_ins();
		$opt_ins        = array();
		foreach ( $opt_in_choices as $opt_in_choice => $opt_in_desc ) {
			$user_opt_in[ $opt_in_choice ] = empty( $user->opt_ins[ $opt_in_choice ] ) ? false : true;
			if ( in_array( strval( $opt_in_choice ), $this->opt_ins, true ) ) {
				if ( empty( $user_opt_in[ $opt_in_choice ] ) ) {
					$updates = true;
					delete_user_meta( $this->id, 'racketmanager_opt_in', $opt_in_choice );
				} else {
					$opt_ins[] = strval( $opt_in_choice );
				}
			} elseif ( ! empty( $user_opt_in[ $opt_in_choice ] ) ) {
				$updates   = true;
				$opt_ins[] = strval( $opt_in_choice );
				add_user_meta( $this->id, 'racketmanager_opt_in', $opt_in_choice );
			}
		}
		$this->opt_ins = $opt_ins;
		if ( ! $updates ) {
			return false;
		}
		foreach ( $updated as $key => $value ) {
			// http://codex.wordpress.org/Function_Reference/wp_update_user.
			if ( 'contactno' === $key ) {
				update_user_meta( $this->ID, $key, $value );
			} elseif ( 'btm' === $key ) {
				update_user_meta( $this->ID, $key, $value );
			} elseif ( 'year_of_birth' === $key ) {
				update_user_meta( $this->ID, $key, $value );
			} elseif ( 'gender' === $key ) {
				update_user_meta( $this->ID, $key, $value );
			} elseif ( 'first_name' === $key ) {
				update_user_meta( $this->ID, $key, $value );
				$user_data[ $key ] = $value;
				$display_name      = $value . ' ' . sanitize_text_field( $this->surname );
				wp_update_user(
					array(
						'ID'           => $this->ID,
						'display_name' => $display_name,
					)
				);
				$this->display_name = $display_name;
				$this->fullname     = $display_name;
			} elseif ( 'last_name' === $key ) {
				update_user_meta( $this->ID, $key, $value );
				$user_data[ $key ] = $value;
				$display_name      = sanitize_text_field( $this->firstname ) . ' ' . $value;
				wp_update_user(
					array(
						'ID'           => $this->ID,
						'display_name' => $display_name,
					)
				);
				$this->display_name = $display_name;
				$this->fullname     = $display_name;
			} elseif ( 'password' === $key ) {
				wp_set_password( $value, $this->ID );
				wp_set_auth_cookie( $this->ID, 1, true );
			} else {
				wp_update_user(
					array(
						'ID' => $this->ID,
						$key => $value,
					)
				);
			}
		}
		return true;
	}
	/**
	 * Get messages function
	 *
	 * @param array $args search arguments.
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
	/**
	 * Get favourites function
	 *
	 * @param array $favourites_type optional favourites search criteria.
	 * @return array
	 */
	public function get_favourites( $favourites_type = null ) {
		$favourites_types = array( 'competition', 'league', 'club', 'team', 'player' );
		if ( $favourites_type ) {
			if ( false === array_search( $favourites_type, $favourites_types, true ) ) {
				return null;
			} else {
				$favourites = $this->get_favourites_for_type( $favourites_type );
			}
		} else {
			foreach ( $favourites_types as $f => $favourites_type ) {
				$favourites[ $f ]['name'] = $favourites_type;
				$favourites_type          = $this->get_favourites_for_type( $favourites_type );
				array_multisort( $favourites_type );
				$favourites[ $f ]['favourites'] = $favourites_type;
			}
		}
		return $favourites;
	}
	/**
	 * Get favourites for a type function
	 *
	 * @param string $favourites_type type of favourite.
	 * @return array
	 */
	private function get_favourites_for_type( $favourites_type ) {
		$userid          = $this->ID;
		$meta_key        = 'favourite-' . $favourites_type;
		$meta_favourites = get_user_meta( $userid, $meta_key );
		$favourites      = array();
		foreach ( $meta_favourites as $i => $favourite ) {
			$favourite_item = new \stdClass();
			if ( 'league' === $favourites_type ) {
				$league                 = get_league( $favourite );
				$favourite_item->name   = $league->title;
				$favourite_item->detail = $league;
			} elseif ( 'club' === $favourites_type ) {
				$club                   = get_club( $favourite );
				$favourite_item->name   = $club->name;
				$favourite_item->detail = $club;
			} elseif ( 'competition' === $favourites_type ) {
				$event                  = get_event( $favourite );
				$favourite_item->name   = $event->name;
				$favourite_item->detail = $event;
			} elseif ( 'player' === $favourites_type ) {
				$player                 = get_player( $favourite );
				$favourite_item->name   = $player->display_name;
				$favourite_item->detail = $player;
			} elseif ( 'team' === $favourites_type ) {
				$team                   = get_team( $favourite );
				$favourite_item->name   = $team->title;
				$favourite_item->detail = $team;
			}
			$favourite_item->id = $favourite;
			$favourites[ $i ]   = $favourite_item;
		}
		return $favourites;
	}
}
