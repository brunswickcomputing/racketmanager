<?php
/**
 * RacketManager-Admin-Players API: RacketManager-admin-player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Player
 */

namespace Racketmanager\Admin;

use Racketmanager\Domain\Player;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_club_player;
use function Racketmanager\get_player;
use function Racketmanager\get_player_error;

/**
 * RacketManager player administration functions
 * Class to implement RacketManager Administration Players panel
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
final class Admin_Player extends Admin_Display {
    /**
     * Function to handle administration players displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        switch ( $view ) {
            case 'player':
                $this->display_player_page();
                break;
            case 'errors':
                $this->display_errors_page();
                break;
            case 'requests':
                $this->display_requests_page();
                break;
            case 'players':
                $this->display_players_page();
                break;
            default:
                $this->display_players_section();
                break;
        }
    }
    /**
     * Display players page
     */
    public function display_players_section(): void {
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $this->display_errors_page();
        }
    }
    /**
     * Display player errors page
     */
    public function display_errors_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $status            = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : null;
            $racketmanager_tab = 'errors';
            $player_errors     = $this->get_player_errors( $status );
            require_once RACKETMANAGER_PATH . 'templates/admin/players/show-errors.php';
        }
    }
    /**
     * Display player requests page
     */
    public function display_requests_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $club_id = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
            $status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'outstanding';
            if ( isset( $_POST['doPlayerRequest'] ) ) {
                if ( current_user_can( 'edit_teams' ) ) {
                    check_admin_referer( 'club-player-request-bulk' );
                    if ( isset( $_POST['playerRequest'] ) ) {
                        $msg = array();
                        foreach ( $_POST['playerRequest'] as $i => $player_request_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                            if ( 'approve' === $_POST['action'] ) {
                                if ( ! current_user_can( 'edit_teams' ) ) {
                                    $this->set_message( $this->no_permission, true );
                                } else {
                                    $club_player = get_club_player( $player_request_id );
                                    $club_player?->approve();
                                    $msg[] = sprintf( __( 'Player %s has been approved for %s.', 'racketmanager' ), $club_player->player->display_name, $club_player->club->shortcode );
                                }
                            } elseif ( 'delete' === $_POST['action'] ) {
                                if ( ! current_user_can( 'edit_teams' ) ) {
                                    $this->set_message( $this->no_permission, true );
                                } else {
                                    $club_player = get_club_player( $player_request_id );
                                    $club_player?->remove();
                                    $msg[] = sprintf( __( 'Player %s has been removed from %s.', 'racketmanager' ), $club_player->player->display_name, $club_player->club->shortcode );
                                }
                            }
                        }
                        $message = implode( '<br>', $msg );
                        $this->set_message( $message );
                        $this->show_message();
                    }
                } else {
                    $this->set_message( $this->no_permission, true );
                }
            }
            $racketmanager_tab = 'requests';
            $clubs             = $this->club_service->get_clubs();
            $player_requests   = $racketmanager->get_club_players(
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
            require_once RACKETMANAGER_PATH . 'templates/admin/players/show-requests.php';
        }
    }
    /**
     * Display players page
     */
    public function display_players_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $players = null;
            $racketmanager_tab = 'players';
            $player_errors     = $this->get_player_errors();
            if ( isset( $_POST['addPlayer'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-player' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                    $this->show_message();
                } else {
                    $player_valid = $racketmanager->validate_player();
                    if ( $player_valid[0] ) {
                        $new_player = $player_valid[1];
                        $player     = get_player( $new_player->user_login, 'login' );  // get player by login.
                        if ( ! $player ) {
                            $player = new Player( $new_player );
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
                        $messages = array();
                        if ( isset( $_POST['player'] ) ) {
                            foreach ( $_POST['player'] as $player_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                                $player = get_player( $player_id );
                                $player->delete();
                                $messages[] = $player->fullname . ' ' . __( 'deleted', 'racketmanager' );
                            }
                            $message = implode( '<br>', $messages );
                            $this->set_message( $message );
                        }
                    } else {
                        $this->set_message( $this->no_permission, true );
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
            $this->show_message();
            if ( ! $players ) {
                $players = $racketmanager->get_all_players( array() );
            }
            require_once RACKETMANAGER_PATH . 'templates/admin/players/show-players.php';
        }
    }
    /**
     * Display player page
     */
    public function display_player_page(): void {
        global $racketmanager;
        if ( ! current_user_can( 'edit_teams' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $player_id     = null;
            $form_valid    = true;
            $page_referrer = null;
            if ( ! empty( $_POST ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-player' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                    $this->show_message();
                } else {
                    $page_referrer = $_POST['page_referrer'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    if ( isset( $_POST['updatePlayer'] ) ) {
                        if ( isset( $_POST['playerId'] ) ) {
                            $player_valid = $racketmanager->validate_player();
                            if ( $player_valid[0] ) {
                                $player     = get_player( intval( $_POST['playerId'] ) );
                                $new_player = $player_valid[1];
                                $return     = $player->update( $new_player );
                                $this->set_message( $return->msg, $return->state );
                            } else {
                                $form_valid     = false;
                                $error_fields   = $player_valid[1];
                                $error_messages = $player_valid[2];
                                $this->set_message( __( 'Error with player details', 'racketmanager' ), true );
                            }
                        } else {
                            $this->set_message( __( 'Player id not found', 'racketmanager' ), true );
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
                                    $this->set_message( __( 'WTN not found', 'racketmanager' ), true );
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
            $this->show_message();
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
            require_once RACKETMANAGER_PATH . 'templates/admin/players/show-player.php';
        }
    }
    /**
     * Get player errors
     *
     * @param string|null $message message (optional).
     * @return array
     */
    private function get_player_errors( string $message = null ): array {
        global $wpdb;
        $search = null;
        $code = match ($message) {
            'no_player' => 'Player not found',
            'no_wtn'    => 'WTN not found',
            default     => null,
        };
        if ( $code ) {
            $search = $wpdb->prepare( 'WHERE `message` = %s', $code );
        }
        $player_errors = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `id` FROM $wpdb->racketmanager_player_errors $search order by `player_id`"
        );
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
    private function get_wtn( object $player ): array {
        global $racketmanager;
        $player_list = array( $player->ID );
        $args        = $racketmanager->set_wtn_env( $player_list );
        $wtn         = array();
        if ( $args ) {
            $wtn_response = $racketmanager->get_player_wtn( $args, $player );
            if ( $wtn_response->status ) {
                $wtn = $wtn_response->value;
            } else {
                $feedback          = new stdClass();
                $feedback->player  = $player;
                $feedback->message = $wtn_response->message;
                $errors[]          = $feedback;
                $racketmanager->handle_player_errors( $errors );
            }
        }
        return $wtn;
    }
}
