<?php
/**
 * RacketManager-Admin-Players API: RacketManager-admin-player class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManager-Admin-Player
 */

namespace Racketmanager\Admin;

use Exception;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\LTA_Tennis_Number_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Updated_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Exceptions\WTN_Error_Exception;
use Racketmanager\Services\Validator\Validator;

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
     * Display player page
     */
    public function display_player_page(): void {
        $validator = new Validator();
        $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $player_id     = null;
        $form_valid    = true;
        $page_referrer = null;
        if ( ! empty( $_POST ) ) {
            $validator = $validator->capability( 'edit_teams' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-player' );
            }
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $page_referrer = $_POST['page_referrer'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                if ( isset( $_POST['updatePlayer'] ) ) {
                    $player_id = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
                    try {
                        $player = $this->player_service->amend_player_details( $player_id );
                        if ( is_wp_error( $player ) ) {
                            $form_valid     = false;
                            $error_fields   = $player->get_error_codes();
                            $error_messages = $player->get_error_messages();
                            $player         = $player->get_error_data( 'player' );
                            $this->set_message( __( 'Error with player details', 'racketmanager' ), true );
                        } else {
                            $this->set_message( __( 'Player updated', 'racketmanager' ) );
                            $player = null;
                        }
                    } catch ( Player_Not_Updated_Exception $e ) {
                        $this->set_message( $e->getMessage(), 'warning' );
                    } catch ( Player_Not_Found_Exception|Duplicate_Email_Exception|Duplicate_BTM_Exception|Exception $e ) {
                        $this->set_message( $e->getMessage(), true );
                    }
                } elseif ( isset( $_POST['setWTN'] ) ) {
                    $player_id = isset( $_POST['playerId'] ) ? intval( $_POST['playerId'] ) : null;
                    try {
                        $this->player_service->get_latest_wtn( $player_id );
                        $this->set_message( __( 'WTN set', 'racketmanager' ) );
                    } catch ( Player_Not_Found_Exception|LTA_Tennis_Number_Not_Found_Exception|WTN_Error_Exception|Exception $e ) {
                        $this->set_message( $e->getMessage(), true );
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
                $club = $this->club_service->get_club( $club_id );
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
        try {
            $player = $this->player_service->get_player( $player_id );
            require_once RACKETMANAGER_PATH . 'templates/admin/players/show-player.php';
        } catch ( Player_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
        }
    }

    /**
     * Display player errors page
     */
    public function display_errors_page(): void {
        $validator = new Validator();
        $validator->capability( 'edit_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();

            return;
        }
        if ( isset( $_POST['doPlayerErrorBulk'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_player-error-bulk' );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } elseif ( 'delete' === $_POST['action'] ) {
                $msg = array();
                foreach ( $_POST['playerError'] as $player_error_id ) {
                    $this->player_service->remove_player_error( $player_error_id );
                    $msg[] = sprintf( __( '%s - Player error has been removed', 'racketmanager' ), $player_error_id );
                }
                $message = implode( '<br>', $msg );
                $this->set_message( $message );
            }
        }
        $this->show_message();
        $status            = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : null;
        $racketmanager_tab = 'errors';
        $player_errors     = $this->player_service->get_player_errors( $status );
        require_once RACKETMANAGER_PATH . 'templates/admin/players/show-errors.php';
    }

    /**
     * Display player requests page
     */
    public function display_requests_page(): void {
        $validator = new Validator();
        $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();

            return;
        }
        $club_id = isset( $_GET['club'] ) ? intval( $_GET['club'] ) : null;
        $status  = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'outstanding';
        if ( isset( $_POST['doPlayerRequest'] ) ) {
            $validator = $validator->capability( 'edit_leagues' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_club-player-request-bulk' );
            }
            if ( empty( $validator->error ) ) {
                if ( 'approve' === $_POST['action'] || 'delete' === $_POST['action'] ) {
                    if ( isset( $_POST['playerRequest'] ) ) {
                        $msg = array();
                        foreach ( $_POST['playerRequest'] as $player_request_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                            if ( 'approve' === $_POST['action'] ) {
                                try {
                                    $this->registration_service->approve_registration( $player_request_id, get_current_user_id() );
                                    $msg [] = sprintf( __( '%s - Player has been approved', 'racketmanager' ), $player_request_id );
                                } catch ( Registration_Not_Found_Exception $e ) {
                                    $msg[] = $e->getMessage();
                                }
                            } elseif ( 'delete' === $_POST['action'] ) {
                                $this->registration_service->remove_registration( $player_request_id, get_current_user_id() );
                                $msg[] = sprintf( __( '%s - Player has been removed from club', 'racketmanager' ), $player_request_id );
                            }
                        }
                        $message = implode( '<br>', $msg );
                        $this->set_message( $message );
                    } else {
                        $this->set_message( __( 'No selection made', 'racketmanager' ), true );
                    }
                } else {
                    $this->set_message( __( 'No action selected', 'racketmanager' ), true );
                }
            } else {
                $this->set_message( $validator->msg, true );
            }
        }
        $this->show_message();
        $racketmanager_tab = 'requests';
        $clubs             = $this->club_service->get_clubs();
        $player_requests   = $this->registration_service->get_registered_players_list( null, $status, $club_id );
        require_once RACKETMANAGER_PATH . 'templates/admin/players/show-requests.php';
    }

    /**
     * Display players page
     */
    public function display_players_page(): void {
        $validator = new Validator();
        $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();

            return;
        }
        $players           = null;
        $racketmanager_tab = 'players';
        if ( isset( $_POST['addPlayer'] ) ) {
            $validator = $validator->capability( 'edit_teams' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-player' );
            }
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                try {
                    $player = $this->player_service->add_new_player();
                    if ( is_wp_error( $player ) ) {
                        $form_valid     = false;
                        $error_fields   = $player->get_error_codes();
                        $error_messages = $player->get_error_messages();
                        $player         = $player->get_error_data( 'player' );
                        $this->set_message( __( 'Error with player details', 'racketmanager' ), true );
                    } else {
                        $this->set_message( __( 'Player added', 'racketmanager' ) );
                        $player = null;
                    }
                } catch ( Player_Exists_Exception|Duplicate_Email_Exception|Duplicate_BTM_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
            $tab = 'players';
        } elseif ( isset( $_POST['doPlayerDel'] ) ) {
            if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
                $validator = $validator->capability( 'edit_teams' );
                if ( empty( $validator->error ) ) {
                    $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_player-bulk' );
                }
                if ( empty( $validator->error ) ) {
                    $messages = array();
                    if ( isset( $_POST['player'] ) ) {
                        foreach ( $_POST['player'] as $player_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                            try {
                                $deleted = $this->player_service->delete_player( $player_id );
                                if ( $deleted ) {
                                    $messages[] = $player_id . ' ' . __( 'deleted', 'racketmanager' );
                                } else {
                                    $messages[] = sprintf( __( 'Unable to delete %d', 'racketmanager' ), $player_id );
                                }
                            } catch ( Player_Not_Found_Exception|Exception $e ) {
                                $messages[] = $e->getMessage();
                            }
                        }
                        $message = implode( '<br>', $messages );
                        $this->set_message( $message );
                    }
                } else {
                    $this->set_message( $validator->msg, true );
                }
            }
            $tab = 'players';
        } elseif ( isset( $_GET['doPlayerSearch'] ) ) {
            if ( ! empty( $_GET['name'] ) ) {
                $players = $this->player_service->get_all_players( array( 'name' => sanitize_text_field( wp_unslash( $_GET['name'] ) ) ) );
            } else {
                $this->set_message( __( 'No search term specified', 'racketmanager' ), true );
            }
            $tab = 'players';
        }
        $this->show_message();
        if ( ! $players ) {
            $players = $this->player_service->get_all_players();
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/players/show-players.php';
    }

    /**
     * Display players page
     */
    public function display_players_section(): void {
        $validator = new Validator();
        $validator->capability( 'view_leagues' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();

            return;
        }
        $this->display_errors_page();
    }
}
