<?php
/**
 * RacketManager-Admin-Players API: RacketManager-admin-players class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Players
 */

namespace Racketmanager;

/**
 * RacketManager players administration functions
 * Class to implement RacketManager Administration Players panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class RacketManager_Admin_Players extends RacketManager_Admin {

	/**
	 * Constructor
	 */
	public function __construct() {
		global $racketmanager_ajax_admin;
		parent::__construct();
	}
	/**
	 * Display players page
	 */
	public function display_players_section() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$this->display_errors_page();
		}
	}
	/**
	 * Display player errors page
	 */
	public function display_errors_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$status            = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : null;
			$racketmanager_tab = 'errors';
			$player_errors     = $this->get_player_errors( $status );
			include_once RACKETMANAGER_PATH . 'admin/players/show-errors.php';
		}
	}
	/**
	 * Display player requests page
	 */
	public function display_requests_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$club_id = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
			$status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'outstanding';
			if ( isset( $_POST['doplayerrequest'] ) ) {
				if ( current_user_can( 'edit_teams' ) ) {
					check_admin_referer( 'club-player-request-bulk' );
					if ( isset( $_POST['playerRequest'] ) ) {
						foreach ( $_POST['playerRequest'] as $i => $player_request_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
							if ( 'approve' === $_POST['action'] ) {
								if ( ! current_user_can( 'edit_teams' ) ) {
									$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
								} elseif ( isset( $_POST['club_id'][ $i ] ) ) {
										$club = get_club( intval( $_POST['club_id'][ $i ] ) );
										$club->approve_player_request( intval( $player_request_id ) );
								}
							} elseif ( 'delete' === $_POST['action'] ) {
								if ( ! current_user_can( 'edit_teams' ) ) {
									$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
								} else {
									$this->delete_player_request( intval( $player_request_id ) );
								}
							}
						}
					}
				} else {
					$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
				}
			}
			$racketmanager_tab = 'requests';
			$player_requests = $racketmanager->get_club_players(
				array(
					'club'   => $club_id,
					'status' => $status,
					'type'   => 'player',
					'orderby' => array(
									   'requested_date' => 'DESC',
									   'created_date'   => 'DESC',
									   'club_id'        => 'ASC',
									   'player_id'      => 'ASC',
									   )
				)
			);
			include_once RACKETMANAGER_PATH . 'admin/players/show-requests.php';
		}
	}
	/**
	 * Display players page
	 */
	public function display_players_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_leagues' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$players = null;
			$racketmanager_tab = 'players';
			$player_errors     = $this->get_player_errors();
			if ( isset( $_POST['addPlayer'] ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-player' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
				} else {
					$player_valid = $this->validatePlayer();
					if ( $player_valid[0] ) {
						$new_player = $player_valid[1];
						$player     = get_player( $new_player->user_login, 'login' );  // get player by login.
						if ( ! $player ) {
							$player = new Racketmanager_Player( $new_player );
							$this->set_message( __( 'Player added', 'racketmanager' ) );
							$player = null;
						} else {
							$this->set_message( __( 'Player already exists', 'racketmanager' ), true );
						}
					} else {
						$form_valid     = false;
						$error_fields   = $player_valid[1];
						$error_messages = $player_valid[2];
						$this->set_message( __( 'Error with player details', 'racketmanager' ), true );
					}
				}
				$tab = 'players';
			} elseif ( isset( $_POST['doPlayerDel'] ) ) {
				if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
					if ( current_user_can( 'edit_teams' ) ) {
						check_admin_referer( 'player-bulk' );
						$messages      = array();
						$message_error = false;
						if ( isset( $_POST['player'] ) ) {
							foreach ( $_POST['player'] as $player_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
								$player = get_player( $player_id );
								$player->delete();
								$messages[] = $player->fullname . ' ' . __( 'deleted', 'racketmanager' );
							}
							$message = implode( '<br>', $messages );
							$this->set_message( $message, $message_error );
						}
					} else {
						$this->set_message( __( 'You do not have permission to perform this task', 'racketmanager' ), true );
					}
				}
				$tab = 'players';
			} elseif ( isset( $_GET['doPlayerSearch'] ) ) {
				if ( ! empty( $_GET['name'] ) ) {
					$players = $racketmanager->get_all_players( array( 'name' => sanitize_text_field( wp_unslash( $_GET['name'] ) ) ) );
				} else {
					$this->set_message( __( 'No search term specified', 'racketmanager' ), true );
				}
				$tab = 'players';
			}
			$this->printMessage();
			if ( ! $players ) {
				$players = $racketmanager->get_all_players( array() );
			}
			include_once RACKETMANAGER_PATH . 'admin/players/show-players.php';
		}
	}
	/**
	 * Display player page
	 */
	public function display_player_page() {
		global $racketmanager;
		if ( ! current_user_can( 'edit_teams' ) ) {
			$this->set_message( __( 'You do not have sufficient permissions to access this page', 'racketmanager' ), true );
			$this->printMessage();
		} else {
			$form_valid    = true;
			$page_referrer = null;
			if ( ! empty( $_POST ) ) {
				if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-player' ) ) {
					$this->set_message( __( 'Security token invalid', 'racketmanager' ), true );
					$this->printMessage();
				} else {
					$page_referrer = isset( $_POST['page_referrer'] ) ? $_POST['page_referrer'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					if ( isset( $_POST['updatePlayer'] ) ) {
						$player_valid  = $this->validatePlayer();
						if ( $player_valid[0] ) {
							if ( isset( $_POST['playerId'] ) ) {
								$player     = get_player( intval( $_POST['playerId'] ) );
								$new_player = $player_valid[1];
								$player->update( $new_player );
							}
						} else {
							$form_valid     = false;
							$error_fields   = $player_valid[1];
							$error_messages = $player_valid[2];
							$this->set_message( __( 'Error with player details', 'racketmanager' ), true );
						}
					} elseif ( isset( $_POST['setWTN'] ) ) {
						$player_id = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
						$btm       = isset( $_POST['btm'] ) ? intval( $_POST['btm'] ) : null;
						if ( $player_id && $btm ) {
							$player = get_player( $player_id );
							if ( $player ) {
								$player->btm = $btm;
								$wtn         = $this->get_wtn( $player );
								if ( $wtn ) {
									$player->set_wtn( $wtn );
									$this->set_message( __( 'WTN set', 'racketmanager' ) );
								} else {
									$this->set_message( __( 'Error setting WTN', 'racketmanager' ), true );
								}
							} else {
								$this->set_message( __( 'Player not found', 'racketmanager' ), true );
							}
						} else {
							$this->set_message( __( 'No LTA Tennis number set', 'racketmanager' ), true );
						}
					}
				}
			} else {
				$page_referrer = wp_get_referer();
			}
			$this->printMessage();
			if ( isset( $_GET['club_id'] ) ) {
				$club_id = intval( $_GET['club_id'] );
				if ( $club_id ) {
					$club = get_club( $club_id );
				}
			}
			if ( isset( $_GET['player_id'] ) ) {
				$player_id = intval( $_GET['player_id'] );
			}
			if ( ! $page_referrer ) {
				if ( empty( $club_id ) ) {
					$page_referrer = 'admin.php?page=racketmanager-players&amp;tab=players';
				} else {
					$page_referrer = 'admin.php?page=racketmanager-clubs&amp;view=players&amp;club_id=' . $club_id;
				}
			}
			$player = get_player( $player_id );
			include_once RACKETMANAGER_PATH . '/admin/players/show-player.php';
		}
	}
	/**
	 * Get player errors
	 *
	 * @param string $$message message (optional).
	 * @return array
	 */
	private function get_player_errors( $message ) {
		global $wpdb;
		$search = null;
		switch( $message ) {
			case 'noplayer':
				$code = 'Player not found';
				break;
			case 'nowtn':
				$code = 'WTN not found';
				break;
			default:
				$code = null;
		}
		if ( $code ) {
			$search = $wpdb->prepare( 'AND `message` = %s', $code );
		}
		$player_errors = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT `id` FROM {$wpdb->racketmanager_player_errors} WHERE 1 = 1 $search order by `player_id`"
		);

		$i = 0;
		foreach ( $player_errors as $i => $player_error ) {
			$player_error        = get_player_error( $player_error->id );
			$player_errors[ $i ] = $player_error;
		}
		return $player_errors;
	}
	/**
	 * Get wtn from lta database
	 *
	 * @param object $player player object.
	 * @return array
	 */
	private function get_wtn( $player ) {
		$player_list = array( $player->ID );
		$args = $this->set_wtn_env( $player_list );
		$wtn  = array();
		if ( $args ) {
			$wtn_response = $this->get_player_wtn( $args, $player );
			if ( $wtn_response->status ) {
				$wtn = $wtn_response->value;
			} else {
				$feedback          = new \stdClass();
				$feedback->player  = $player;
				$feedback->message = $wtn_response->message;
				$errors[]          = $feedback;
				$this->handle_player_errors( $errors );
			}
		}
		return $wtn;
	}
}
