<?php
/**
 * Racketmanager_Player API: player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Player
 */

namespace Racketmanager;

/**
 * Class to implement the Player object
 */
final class Racketmanager_Player {
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
	 * Email address.
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
	 * @var string
	 */
	public $btm;
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
	 * Locked uiser name.
	 *
	 * @var string
	 */
	public $locked_user_name;
	/**
	 * Retrieve player instance
	 *
	 * @param int    $player_id player id.
	 * @param string $search_type type of id to seaerch for.
	 */
	public static function get_instance( $player_id, $search_type ) {
		if ( ! $player_id ) {
			return false;
		}
		$player = wp_cache_get( $player_id, 'players' );

		if ( ! $player ) {
			switch ( $search_type ) {
				case 'btm':
					$players = get_users(
						array(
							'meta_key'     => 'btm', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
							'meta_value'   => $player_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
							'meta_compare' => '=',
						)
					);
					if ( $players ) {
						$player = $players[0];
					}
					break;
				case 'email':
					$player = get_user_by( 'email', $player_id );
					break;
				case 'login':
					// format of login is first.surname( can contain spaces ).
					if ( false === strpos( $player_id, '.' ) ) {
						$pos = strpos( $player_id, ' ' );
						if ( false !== $pos ) {
							$player_id = substr_replace( $player_id, '.', $pos, strlen( ' ' ) );
						}
					}
					if ( false !== strpos( $player_id, '-' ) ) {
						$player_id = str_replace( '-', ' ', $player_id );
					}
					$player = get_user_by( 'login', strtolower( $player_id ) );
					break;
				case 'name':
					// format of nicename is first-surname( where surname spaces are converted to - ).
					if ( false !== strpos( $player_id, ' ' ) ) {
						$player_id = str_replace( ' ', '-', $player_id );
					}
					$player = get_user_by( 'slug', strtolower( $player_id ) );
					break;
				case 'id':
				default:
					$player_id = (int) $player_id;
					$player    = get_userdata( $player_id );
					break;
			}
			if ( ! $player ) {
				return false;
			}
			$player = new Racketmanager_Player( $player->data );
			wp_cache_set( $player_id, $player, 'players' );
		}

		return $player;
	}

	/**
	 * Constructor
	 *
	 * @param object $player Player object.
	 */
	public function __construct( $player = null ) {
		if ( ! is_null( $player ) ) {
			foreach ( $player as $key => $value ) {
				$this->$key = $value;
			}
			if ( ! isset( $this->ID ) ) {
				$this->ID = $this->add();
			}
			$this->id           = $this->ID;
			$this->email        = $this->user_email;
			$this->fullname     = $this->display_name;
			$this->created_date = $this->user_registered;
			$this->firstname    = get_user_meta( $this->ID, 'first_name', true );
			$this->surname      = get_user_meta( $this->ID, 'last_name', true );
			$this->gender       = get_user_meta( $this->ID, 'gender', true );
			$this->type         = get_user_meta( $this->ID, 'racketmanager_type', true );
			$this->btm          = get_user_meta( $this->ID, 'btm', true );
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
		}
	}

	/**
	 * Add player
	 *
	 * @return int $user_id id of inserted record.
	 */
	private function add() {
		$this->display_name          = $this->firstname . ' ' . $this->surname;
		$this->user_email            = $this->email;
		$this->user_registered       = gmdate( 'Y-m-d H:i:s' );
		$userdata                    = array();
		$userdata['first_name']      = $this->firstname;
		$userdata['last_name']       = $this->surname;
		$userdata['display_name']    = $this->display_name;
		$userdata['user_login']      = strtolower( $this->firstname ) . '.' . strtolower( $this->surname );
		$userdata['user_pass']       = $userdata['user_login'] . '1';
		$userdata['user_registered'] = $this->user_registered;
		if ( $this->email ) {
			$userdata['user_email'] = $this->email;
		}
		$user_id = wp_insert_user( $userdata );
		if ( ! is_wp_error( $user_id ) ) {
			update_user_meta( $user_id, 'show_admin_bar_front', false );
			update_user_meta( $user_id, 'gender', $this->gender );
			if ( isset( $this->btm ) && $this->btm > '' ) {
				update_user_meta( $user_id, 'btm', $this->btm );
			}
			if ( isset( $this->contactno ) && $this->contactno > '' ) {
				update_user_meta( $user_id, 'contactno', $this->contactno );
			}
		}
		return $user_id;
	}

	/**
	 * Update player
	 *
	 * @param string $firstname first name.
	 * @param string $surname surname.
	 * @param string $gender gender.
	 * @param string $btm lta tennis number.
	 * @param string $email email address.
	 * @param string $locked locked indicator.
	 * @param string $contact_no telephone number.
	 * @return null
	 */
	public function update( $firstname, $surname, $gender, $btm, $email, $locked, $contact_no ) {
		global $racketmanager;

		$update    = false;
		$user_data = array();
		if ( $this->firstname !== $firstname ) {
			$update                     = true;
			$user_data['first_name']    = $firstname;
			$user_data['display_name']  = $firstname . ' ' . $surname;
			$user_data['user_nicename'] = sanitize_title( $user_data['display_name'] );
		}
		if ( $this->surname !== $surname ) {
			$update                     = true;
			$user_data['last_name']     = $surname;
			$user_data['display_name']  = $firstname . ' ' . $surname;
			$user_data['user_nicename'] = sanitize_title( $user_data['display_name'] );
		}
		if ( $this->gender !== $gender ) {
			$update = true;
			update_user_meta( $this->ID, 'gender', $gender );
		}
		if ( $this->btm !== $btm ) {
			$update = true;
			update_user_meta( $this->ID, 'btm', $btm );
		}
		if ( $this->user_email !== $email ) {
			$update                  = true;
			$user_data['user_email'] = $email;
		}
		if ( $this->contactno !== $contact_no ) {
			$update = true;
			update_user_meta( $this->ID, 'contactno', $contact_no );
		}
		if ( $this->locked !== $locked ) {
			$update = true;
			if ( $locked ) {
				update_user_meta( $this->ID, 'locked', $locked );
				update_user_meta( $this->ID, 'locked_date', gmdate( 'Y-m-d' ) );
				update_user_meta( $this->ID, 'locked_user', get_current_user_id() );
			} else {
				delete_user_meta( $this->ID, 'locked' );
				delete_user_meta( $this->ID, 'locked_date' );
				delete_user_meta( $this->ID, 'locked_user' );
			}
		}

		if ( ! $update ) {
			$racketmanager->set_message( __( 'No updates', 'racketmanager' ) );
			return;
		}
		wp_cache_delete( $this->id, 'players' );
		if ( $user_data ) {
			$user_data['ID'] = $this->ID;
			$user_id         = wp_update_user( $user_data );
			if ( is_wp_error( $user_id ) ) {
				$racketmanager->set_message( $user_id->get_error_message(), true );
			} else {
				$racketmanager->set_message( __( 'Player details updated', 'racketmanager' ) );
			}
		} else {
			$racketmanager->set_message( __( 'Player details updated', 'racketmanager' ) );
		}
	}

	/**
	 * Update player contact details
	 *
	 * @param string $contact_no telephone number.
	 * @param string $contact_email email address.
	 * @return boolean
	 */
	public function update_contact( $contact_no, $contact_email ) {
		$current_contact_no    = get_user_meta( $this->ID, 'contactno', true );
		$current_contact_email = $this->user_email;
		if ( $current_contact_no !== $contact_no ) {
			update_user_meta( $this->ID, 'contactno', $contact_no );
		}
		if ( $current_contact_email !== $contact_email ) {
			$userdata               = array();
			$userdata['ID']         = $this->ID;
			$userdata['user_email'] = $contact_email;
			$user_id                = wp_update_user( $userdata );
			if ( is_wp_error( $user_id ) ) {
				$error_msg = $user_id->get_error_message();
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Unable to update user email ' . $this->ID . ' - ' . $contact_email . ' - ' . $error_msg );
				return false;
			}
		}
		return true;
	}
	/**
	 * Update player btm
	 *
	 * @param int $btm LTA tennis number.
	 * @return boolean
	 */
	public function update_btm( $btm ) {
		$current_btm = get_user_meta( $this->ID, 'btm', true );
		if ( $current_btm !== $btm ) {
			update_user_meta( $this->ID, 'btm', $btm );
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Delete player
	 */
	public function delete() {
		global $wpdb;

		$club_player = $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->racketmanager_club_players} WHERE `player_id` = %d",
				$this->id
			)
		);
		if ( ! $club_player ) {
			wp_delete_user( $this->id );
		} else {
			update_user_meta( $this->id, 'remove_date', gmdate( 'Y-m-d' ) );
		}
		wp_cache_flush_group( 'players' );
	}

	/**
	 * Get clubs for player
	 */
	public function get_clubs() {
		global $wpdb;

		$player_clubs = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `affiliatedclub`, `player_id`, `created_date` FROM {$wpdb->racketmanager_club_players} WHERE `player_id` = %d AND `removed_date` IS NULL ORDER BY `created_date` ASC, `affiliatedclub` ASC",
				$this->id
			)
		);
		foreach ( $player_clubs as $i => $player_club ) {
			$club                   = get_club( $player_club->affiliatedclub );
			$player_club->club_name = $club->shortcode;
			$player_clubs[ $i ]     = $player_club;
		}
		return $player_clubs;
	}
}
