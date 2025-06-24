<?php
/**
 * AJAX Front end match response methods

 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Frontend_Match
 */

namespace Racketmanager;

use JetBrains\PhpStorm\NoReturn;
use stdClass;

/**
 * Implement AJAX front end match responses.
 *
 * @author Paul Moffat
 */
class Ajax_Match extends Ajax {
    public string $no_match_id ;
    public string $no_modal;
    public string $not_played;
    public string $match_not_found;
    /**
     * Register ajax actions.
     */
    public function __construct() {
        parent::__construct();
        add_action( 'wp_ajax_racketmanager_match_card', array( &$this, 'print_match_card' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_card', array( &$this, 'print_match_card' ) );
        add_action( 'wp_ajax_racketmanager_match_rubber_status', array( &$this, 'match_rubber_status_options' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_rubber_status', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_rubber_status', array( &$this, 'set_match_rubber_status' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_rubber_status', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_match_status', array( &$this, 'match_status_options' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_status', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_status', array( &$this, 'set_match_status' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_status', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_match_option', array( &$this, 'show_match_option' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_match_option', array( &$this, 'logged_out_modal' ) );
        add_action( 'wp_ajax_racketmanager_set_match_date', array( &$this, 'set_match_date' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_set_match_date', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_switch_home_away', array( &$this, 'switch_home_away' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_switch_home_away', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_reset_match_result', array( &$this, 'reset_match_result' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_reset_match_result', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_update_match_header', array( &$this, 'update_match_header' ) );
        add_action( 'wp_ajax_racketmanager_update_match', array( &$this, 'update_match' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_match', array( &$this, 'logged_out' ) );
        add_action( 'wp_ajax_racketmanager_update_rubbers', array( &$this, 'update_rubbers' ) );
        add_action( 'wp_ajax_nopriv_racketmanager_update_rubbers', array( &$this, 'logged_out' ) );
        $this->no_match_id     = __( 'Match id not supplied', 'racketmanager' );
        $this->no_modal        = __( 'Modal name not supplied', 'racketmanager' );
        $this->not_played      = __( 'Not played', 'racketmanager' );
        $this->match_not_found = __( 'Match not found', 'racketmanager' );
    }
    /**
     * Build screen to allow printing of match cards
     */
    public function print_match_card(): void {
        $validator = new Validator_Match();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $match_id = isset( $_POST['matchId'] ) ? intval( $_POST['matchId'] ) : null;
            $output   = show_match_card( $match_id );
            wp_send_json_success( $output );
        }
        $return = $validator->get_details();
        wp_send_json_error( $return->msg, $return->status );
    }
    /**
     * Build screen to allow match status to be captured
     */
    #[NoReturn] public function match_status_options(): void {
        $output = null;
        $validator = new Validator_Match();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : 0;
            $modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $status   = isset( $_POST['match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'] ) ) : null;
            $validator = $validator->modal( $modal );
            $validator = $validator->match( $match_id );
            if ( empty( $validator->error ) ) {
                $output = match_status_modal( array( 'status' => $status, 'modal' => $modal, 'match_id' => $match_id ) );
            }
        }
        if ( ! empty( $validator->error ) ) {
            $return = $validator->get_details();
            $output = show_alert( $return->err_msgs[0], 'danger', 'modal' );
            status_header( $return->status );
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }
    /**
     * Set match status
     */
    public function set_match_status(): void {
        $return         = new stdClass();
        $error_field    = 'score_status';
        $validator      = new Validator_Match();
        $validator      = $validator->check_security_token( 'racketmanager_nonce', 'match-status' );
        if ( empty( $validator->error ) ) {
            $modal        = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $match_id     = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $match_status = isset( $_POST['score_status'] ) ? sanitize_text_field( wp_unslash( $_POST['score_status'] ) ) : null;
            $validator    = $validator->modal( $modal, $error_field );
            $validator    = $validator->match( $match_id, $error_field );
            $validator    = $validator->match_status( $match_status, $error_field );
            if ( empty( $validator->error ) ) {
                $match                  = get_match( $match_id );
                $status_dtls            = $this->set_status_details( $match_status, $match->home_team, $match->away_team );
                $return->match_id       = $match_id;
                $return->match_status   = $status_dtls->status;
                $return->status_message = $status_dtls->message;
                $return->status_class   = $status_dtls->class;
                $return->modal          = $modal;
                $return->num_rubbers    = $match->num_rubbers;
                wp_send_json_success( $return );
            }
        }
        $return      = $validator->get_details();
        $return->msg = __( 'Unable to set match status', 'racketmanager' );
        wp_send_json_error( $return, $return->status );
    }
    /**
     * Build screen to match rubber status to be captured
     */
    #[NoReturn] public function match_rubber_status_options(): void {
        $output      = null;
        $error_field = 'score_status';
        $validator   = new Validator_Match();
        $validator   = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $rubber_id = isset( $_POST['rubber_id'] ) ? intval( $_POST['rubber_id'] ) : null;
            $modal     = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $status    = isset( $_POST['score_status'] ) ? sanitize_text_field( wp_unslash( $_POST['score_status'] ) ) : null;
            $validator = $validator->modal( $modal, $error_field );
            $validator = $validator->rubber( $rubber_id, $error_field );
            if ( empty( $validator->error ) ) {
                $output = rubber_status_modal( $rubber_id, array( 'status' => $status, 'modal' => $modal ) );
            }
        }
        if ( ! empty( $validator->error ) ) {
            $return = $validator->get_details();
            if ( empty( $return->msg ) ) {
                $return->msg = implode( '<br> ', $return->err_msgs );
            }
            $output = show_alert( $return->msg, 'danger', 'modal' );
            status_header( $return->status );
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }
    /**
     * Set match rubber status
     */
    public function set_match_rubber_status(): void {
        $return      = new stdClass();
        $error_field = 'score_status';
        $validator   = new Validator_Match();
        $validator   = $validator->check_security_token( 'racketmanager_nonce', 'match-rubber-status' );
        if ( empty( $validator->error ) ) {
            $modal         = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $rubber_number = isset( $_POST['rubber_number'] ) ? intval( $_POST['rubber_number'] ) : null;
            $score_status  = isset( $_POST['score_status'] ) ? sanitize_text_field( wp_unslash( $_POST['score_status'] ) ) : null;
            $home_team     = isset( $_POST['home_team'] ) ? intval( $_POST['home_team'] ) : null;
            $away_team     = isset( $_POST['away_team'] ) ? intval( $_POST['away_team'] ) : null;
            $validator     = $validator->modal( $modal, $error_field );
            $validator     = $validator->rubber_number( $rubber_number, $error_field );
            $validator     = $validator->score_status( $score_status, $error_field );
            if ( empty( $validator->error ) ) {
                $status_dtls            = $this->set_status_details( $score_status, $home_team, $away_team );
                $return->score_status   = $status_dtls->status;
                $return->status_message = $status_dtls->message;
                $return->status_class   = $status_dtls->class;
                $return->modal          = $modal;
                $return->rubber_number  = $rubber_number;
                wp_send_json_success( $return );
            }
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to set score status', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }
    /**
     * Function to set match or rubber status details
     *
     * @param string $status status value.
     * @param int $home_team home team id.
     * @param int $away_team away_team id.
     */
    public function set_status_details( string $status, int $home_team, int $away_team ): object {
        $status_message = array();
        $status_class   = array();
        $status_values  = explode( '_', $status );
        $status_value   = $status_values[0];
        $player_ref     = $status_values[1] ?? null;
        $winner        = null;
        $loser         = null;
        $score_message = null;
        switch ( $status_value ) {
            case 'walkover':
                $score_message = __( 'Walkover', 'racketmanager' );
                if ( 'player2' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player1' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'retired':
                $score_message = __( 'Retired', 'racketmanager' );
                if ( 'player1' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player2' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'invalid':
                $score_message = __( 'Invalid player', 'racketmanager' );
                if ( 'player1' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player2' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'share':
                $score_message = $this->not_played;
                break;
            case 'abandoned':
                $score_message = __( 'Abandoned', 'racketmanager' );
                break;
            case 'cancelled':
                $score_message = __( 'Cancelled', 'racketmanager' );
                break;
            case 'none':
                $status = '';
                break;
            default:
                break;
        }
        if ( $winner ) {
            $status_message[ $winner ] = '';
            $status_message[ $loser ]  = $score_message;
            $status_class[ $winner ]   = 'winner';
            $status_class[ $loser ]    = 'loser';
        } elseif ( 'share' === $status_value || 'cancelled' === $status_value || 'invalid' === $status_value ) {
            $status_message[ $home_team ] = $score_message;
            $status_message[ $away_team ] = $score_message;
            $status_class[ $home_team ]   = 'tie';
            $status_class[ $away_team ]   = 'tie';
        } elseif ( 'abandoned' === $status_value ) {
            $status_message[ $home_team ] = $score_message;
            $status_message[ $away_team ] = $score_message;
            $status_class[ $home_team ]   = '';
            $status_class[ $away_team ]   = '';
        } else {
            $status_message[ $home_team ] = '';
            $status_message[ $away_team ] = '';
            $status_class[ $home_team ]   = '';
            $status_class[ $away_team ]   = '';
        }
        $status_dtls          = new stdClass();
        $status_dtls->message = $status_message;
        $status_dtls->class   = $status_class;
        $status_dtls->status  = $status;
        return $status_dtls;
    }
    /**
     * Build screen to show selected match option
     */
    #[NoReturn] public function show_match_option(): void {
        $output  = null;
        $return  = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : 0;
            $modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $option   = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : null;
            $output   = match_option_modal( array( 'option' => $option, 'modal' => $modal, 'match_id' => $match_id ) );
        }
        if ( ! empty( $return->error ) ) {
            $output = show_alert( $return->msg, 'danger', 'modal' );
            status_header( $return->status );
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        wp_die();
    }
    /**
     * Set match date function
     *
     * @return void
     */
    public function set_match_date(): void {
        $match_id               = null;
        $modal                  = null;
        $schedule_date          = null;
        $schedule_date_formated = null;
        $error_field            = 'schedule-date';
        $validator              = new Validator_Match();
        $validator              = $validator->check_security_token( 'racketmanager_nonce', 'match-option' );
        if ( empty( $validator->error ) ) {
            $modal         = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $match_id      = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $schedule_date = isset( $_POST['schedule-date'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule-date'] ) ) : null;
            $validator     = $validator->modal( $modal, $error_field );
            $validator     = $validator->match( $match_id, $error_field );
        }
        if ( empty( $validator->error ) ) {
            $match = get_match( $match_id );
            if ( $schedule_date ) {
                if ( strlen( $schedule_date ) === 10 ) {
                    $schedule_date          = substr( $schedule_date, 0, 10 );
                    $match_date             = substr( $match->date, 0, 10 );
                    $schedule_date_formated = mysql2date( 'D j M', $schedule_date );
                } else {
                    $schedule_date          = substr( $schedule_date, 0, 10 ) . ' ' . substr( $schedule_date, 11, 5 );
                    $match_date             = $match->date;
                    $schedule_date_formated = mysql2date( 'j F Y H:i', $schedule_date );
                }
            } else {
                $match_date = null;
            }
            $validator = $validator->scheduled_date( $schedule_date, $match_date );
            if ( empty( $validator->error ) ) {
                $match         = $match->update_match_date( $schedule_date, $match->date );
                $match->status = 5;
                $match->set_status( $match->status );
                $msg = __( 'Match schedule updated', 'racketmanager' );
                $return = new stdClass();
                $return->msg = $msg;
                $return->modal = $modal;
                $return->match_id = $match_id;
                $return->schedule_date = $schedule_date;
                $return->schedule_date_formated = $schedule_date_formated;
                wp_send_json_success( $return );
            }
        }
        $return      = $validator->get_details();
        $return->msg = __( 'Unable to update match schedule', 'racketmanager' );
        wp_send_json_error( $return, $return->status );
    }
    /**
     * Switch home and away teams function
     *
     * @return void
     */
    public function switch_home_away(): void {
        $return    = array();
        $err_msg   = array();
        $err_field = array();
        $valid     = true;
        $msg       = null;
        $match_id  = null;
        $match     = null;
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'match-option' ) ) {
            $valid       = false;
            $err_field[] = '';
            $err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
        }
        if ( $valid ) {
            $modal = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            if ( $modal ) {
                $match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
                if ( $match_id ) {
                    $match = get_match( $match_id );
                    if ( $match ) {
                        $old_home   = $match->home_team;
                        $old_away   = $match->away_team;
                        $match_date = $match->league->event->seasons[ $match->season ]['match_dates'][ $match->match_day - 1 ];
                        if ( $match_date ) {
                            $match->update_match_date( $match_date );
                            $match->set_teams( $old_away, $old_home );
                            $msg = __( 'Home and away teams switched', 'racketmanager' );
                        } else {
                            $valid       = false;
                            $err_field[] = 'schedule-date';
                            $err_msg[]   = __( 'Match day not found', 'racketmanager' );
                        }
                    } else {
                        $valid       = false;
                        $err_field[] = 'schedule-date';
                        $err_msg[]   = $this->match_not_found;
                    }
                } else {
                    $valid       = false;
                    $err_field[] = 'schedule-date';
                    $err_msg[]   = $this->no_match_id;
                }
            } else {
                $valid       = false;
                $err_field[] = 'schedule-date';
                $err_msg[]   = $this->no_modal;
            }
        }
        if ( $valid ) {
            array_push( $return, $msg, $modal, $match_id, $match->link );
            wp_send_json_success( $return );
        } else {
            $msg = __( 'Unable to update match schedule', 'racketmanager' );
            array_push( $return, $msg, $err_msg, $err_field );
            wp_send_json_error( $return, '500' );
        }
    }
    /**
     * Reset match function
     *
     * @return void
     */
    public function reset_match_result(): void {
        $msg       = null;
        $match_id  = null;
        $modal     = null;
        $return    = $this->check_security_token( 'racketmanager_nonce', 'match-option');
        if ( empty( $return->error ) ) {
            $modal    = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            if ( ! $modal ) {
                $return->error  = true;
                $return->msg    = $this->no_modal;
                $return->status = 404;
            }
            if ( ! $match_id ) {
                $return->error  = true;
                $return->msg    = $this->no_match_id;
                $return->status = 404;
            }
        }
        if ( empty( $return->error ) ) {
            $match = get_match( $match_id );
            if ( $match ) {
                $match->reset_result();
                $msg   = __( 'Match result reset', 'racketmanager' );
            } else {
                $return->error  = true;
                $return->msg    = $this->match_not_found;
                $return->status = 404;
            }
        }
        if ( empty( $return->error ) ) {
            $return->msg      = $msg;
            $return->modal    = $modal;
            $return->match_id = $match_id;
            wp_send_json_success( $return );
        } else {
            wp_send_json_error( $return, $return->status );
        }
    }
    /**
     * Update match header
     */
    public function update_match_header(): void {
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $match_id     = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            if ( ! empty( $match_id ) ) {
                $match = get_match( $match_id );
                if ( $match ) {
                    $edit_mode = isset( $_POST['edit_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_mode'] ) ) : false;
                    $output    = match_header( $match->id, array( 'edit' => $edit_mode ) );
                    wp_send_json_success( $output );
                } else {
                    $return->error  = true;
                    $return->msg    = __( 'Match not found', 'racketmanager' );
                    $return->status = 404;
                }
            } else {
                $return->error  = true;
                $return->msg    = __( 'Match id not found', 'racketmanager' );
                $return->status = 404;
            }
        }
        if ( ! empty( $return->error ) ) {
            wp_send_json_error( $return->msg, $return->status );
        }
    }

    /**
     * Update match scores
     */
    public function update_match(): void {
        global $league, $match, $racketmanager;

        $return    = array();
        $err_msg   = array();
        $err_field = array();
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'scores-match' ) ) {
            $error       = true;
            $err_field[] = '';
            $err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
        } else {
            $match_id            = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
            $match               = get_match( $match_id );
            $league              = get_league( $match->league_id );
            $match_confirmed     = 'P';
            $custom['sets']      = $_POST['sets'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $match_status        = isset( $_POST['match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'] ) ) : null;
            $set_prefix          = 'set_';
            $errors['err_msg']   = $err_msg;
            $errors['err_field'] = $err_field;
            $sets                = $custom['sets'] ?? null;
            $match_validate      = $this->validate_match_score( $match, $sets, $set_prefix, $errors, false, $match_status );
            $error               = $match_validate[0];
            $err_msg             = $match_validate[1];
            $home_points         = $match_validate[3];
            $away_points         = $match_validate[4];
            $err_field           = $match_validate[2];
            $sets                = $match_validate[5];
            $custom['sets']      = $sets;
            if ( $match_status ) {
                switch ( $match_status ) {
                    case 'walkover_player1':
                        $custom['walkover'] = 'home';
                        break;
                    case 'walkover_player2':
                        $custom['walkover'] = 'away';
                        break;
                    case 'retired_player1':
                        $custom['retired'] = 'home';
                        break;
                    case 'retired_player2':
                        $custom['retired'] = 'away';
                        break;
                    case 'share':
                        $custom['share'] = 'true';
                        break;
                    case 'abandoned':
                        $custom['abandoned'] = 'true';
                        break;
                    case 'cancelled':
                        $custom['cancelled'] = 'true';
                        break;
                    default:
                        break;
                }
            }
            if ( ! $error ) {
                $match->update_sets( $sets );
                $match_updated = $match->update_result( $home_points, $away_points, $custom, $match_confirmed );
                if ( $match_updated ) {
                    $match_message       = __( 'Result saved', 'racketmanager' );
                    $match               = get_match( $match_id );
                    $msg                 = $match_message;
                    $rm_options          = $racketmanager->get_options();
                    $result_confirmation = $rm_options[ $match->league->event->competition->type ]['resultConfirmation'];
                    if ( 'auto' === $result_confirmation || ( current_user_can( 'manage_racketmanager' ) ) ) {
                        $match->confirmed = 'Y';
                        $match->set_confirmed();
                        $update = $match->update_league_with_result();
                        $msg    = $update->msg;
                        if ( ! current_user_can( 'manage_racketmanager' ) ) {
                            $match_confirmed = 'Y';
                            $match->result_notification( $match_confirmed, $match_message );
                        }
                    } else {
                        $match->result_notification( $match_confirmed, $match_message );
                    }
                } else {
                    $msg = __( 'No result to save', 'racketmanager' );
                }
                array_push( $return, $msg, $match->home_points, $match->away_points, $match->winner_id, $sets );
                wp_send_json_success( $return );
            }
        }
        if ( $error ) {
            $msg = __( 'Unable to update match result', 'racketmanager' );
            array_push( $return, $msg, $err_msg, $err_field );
            wp_send_json_error( $return, 500 );
        }
    }

    /**
     * Update match rubber scores
     */
    public function update_rubbers(): void {
        global $racketmanager, $match;
        $return          = array();
        $msg             = null;
        $err_field       = array();
        $err_msg         = array();
        $error           = false;
        $updated_rubbers = array();
        $club_id         = null;
        $match_confirmed = null;
        $match_comments  = null;
        $user_team       = null;
        $user_type       = null;
        $confirm_comments = null;
        $result_confirmation = null;
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'rubbers-match' ) ) {
            $error       = true;
            $err_field[] = '';
            $err_msg[]   = __( 'Form has expired. Please refresh the page and resubmit', 'racketmanager' );
        } elseif ( isset( $_POST['updateRubber'] ) ) {
            $updated_rubbers     = '';
            $match_id            = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
            $match               = get_match( $match_id );
            $rm_options          = $racketmanager->get_options();
            $match_confirmed     = '';
            $is_update_allowed   = $match->is_update_allowed();
            $user_type           = $is_update_allowed->user_type;
            $user_team           = $is_update_allowed->user_team;
            $result_confirmation = $rm_options[ $match->league->event->competition->type ]['resultConfirmation'];
            $match_comments      = isset( $_POST['matchComments'] ) ? wp_unslash( $_POST['matchComments'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $confirm_comments    = isset( $_POST['resultConfirmComments'] ) ? sanitize_text_field( wp_unslash( $_POST['resultConfirmComments'] ) ) : '';
            if ( 'results' === $_POST['updateRubber'] ) {
                $user_can_update = true;
                $player_found = false;
                if ( 'player' === $user_type ) {
                    if ( 'home' === $user_team || 'both' === $user_team ) {
                        if ( get_current_user_id() === intval( $match->teams['home']->captain_id ) || get_current_user_id() === intval( $match->teams['home']->club->matchsecretary ) ) {
                            $player_found = true;
                        }
                        $club_id = $match->teams['home']->club_id;
                    } elseif ( 'away' === $user_team ) {
                        if ( get_current_user_id() === intval( $match->teams['away']->captain_id ) || get_current_user_id() === intval( $match->teams['away']->club->match_secretary ) ) {
                            $player_found = true;
                        }
                        $club_id = $match->teams['away']->club_id;
                    }
                    if ( ! $player_found ) {
                        $club           = get_club( $club_id );
                        $club_player    = $club->get_players(
                            array(
                                'player' => get_current_user_id(),
                                'active' => true,
                            )
                        );
                        $club_player_id = $club_player[0]->roster_id;
                        for ( $ix = 1; $ix <= $match->num_rubbers; $ix++ ) {
                            $players = isset( $_POST['players'][ $ix ] ) ? ( wp_unslash( $_POST['players'][ $ix ] ) ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                            if ( 'home' === $user_team || 'both' === $user_team ) {
                                $home_players = (array) $players['home'];
                                $player_found = array_search( $club_player_id, $home_players, true );
                                if ( $player_found ) {
                                    break;
                                }
                            }
                            if ( ! $player_found && ( 'away' === $user_team || 'both' === $user_team ) ) {
                                $away_players = (array) $players['away'];
                                $player_found = array_search( $club_player_id, $away_players, true );
                                if ( $player_found ) {
                                    break;
                                }
                            }
                        }
                    }
                    if ( ! $player_found ) {
                        $user_can_update = false;
                        $err_msg[]       = __( 'Player cannot submit results', 'racketmanager' );
                        $error           = true;
                    }
                }
                if ( $user_can_update ) {
                    $match_status    = isset( $_POST['new_match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['new_match_status'] ) ) : null;
                    $rubber_result   = $this->update_rubber_results( $match, $match_status );
                    $error           = $rubber_result[0];
                    $match_confirmed = $rubber_result[1];
                    $err_msg         = $rubber_result[2];
                    $err_field       = $rubber_result[3];
                    $updated_rubbers = $rubber_result[4];
                }
            } elseif ( 'confirm' === $_POST['updateRubber'] ) {
                $result_confirm  = isset( $_POST['resultConfirm'] ) ? sanitize_text_field( wp_unslash( $_POST['resultConfirm'] ) ) : null;
                $match_confirmed = $this->confirm_rubber_results( $result_confirm );
                if ( empty( $match_confirmed ) ) {
                    $error       = true;
                    $err_field[] = 'resultConfirm';
                    $err_field[] = 'resultChallenge';
                    $err_msg[]   = __( 'Either confirm or challenge result', 'racketmanager' );
                } elseif ( 'C' === $match_confirmed ) {
                    if ( empty( $confirm_comments ) ) {
                        $error       = true;
                        $err_field[] = 'resultConfirmComments';
                        $err_msg[]   = __( 'You must enter a reason for challenging the result', 'racketmanager' );
                    }
                }
                if ( ! $error ) {
                    $match->delete_result_check();
                    $rubbers = $match->get_rubbers();
                    foreach ( $rubbers as $rubber ) {
                        $rubber->check_players();
                    }
                }
            }
        }
        if ( ! $error ) {
            if ( $match_confirmed ) {
                $match_message = null;
                if ( 'D' === $match_confirmed ) {
                    $match_updated_by = $match->update_match_result_status( $match_confirmed, null, null, null, null );
                    $match_message    = __( 'Match Postponed', 'racketmanager' );
                    $msg              = $match_message;
                } else {
                    if ( isset( $_POST['result_home'] ) ) {
                        $actioned_by = 'home';
                    } elseif ( isset( $_POST['result_away'] ) ) {
                        $actioned_by = 'away';
                    } else {
                        $actioned_by = '';
                    }
                    $match_updated_by = $match->update_match_result_status( $match_confirmed, $match_comments, $confirm_comments, $user_team, $actioned_by );
                    if ( 'A' === $match_confirmed ) {
                        if ( 'auto' === $result_confirmation || 'admin' === $user_type  ) {
                            $match->confirmed = 'Y';
                            $match->set_confirmed();
                            $update = $match->update_league_with_result();
                            $msg    = $update->msg;
                            if ( 'admin' !== $user_type ) {
                                $match_message = __( 'Result Approved', 'racketmanager' );
                                if ( $update->updated || 'Y' === $match->updated ) {
                                    $match_confirmed = 'Y';
                                }
                            }
                        }
                    } elseif ( 'C' === $match_confirmed ) {
                        $match_message = __( 'Result Challenged', 'racketmanager' );
                        $msg           = $match_message;
                    } elseif ( 'P' === $match_confirmed ) {
                        if ( 'admin' === $user_type ) {
                            $match->confirmed = 'Y';
                            $update           = $match->update_league_with_result();
                            $msg              = $update->msg;
                        } else {
                            $msg = __( 'Result Saved', 'racketmanager' );
                            $match_message = $msg;
                        }
                    }
                }
                if ( $match_message ) {
                    $match->result_notification( $match_confirmed, $match_message, $match_updated_by );
                }
            } elseif ( ! $msg ) {
                $msg = __( 'No results to save', 'racketmanager' );
            }
            $player_warnings = null;
            if ( $match->has_result_check() ) {
                $warning_player  = false;
                $warning_match   = array();
                $result_status   = 'warning';
                $result_warnings = $racketmanager->get_result_warnings( array( 'match' => $match->id ) );
                foreach ( $result_warnings as $player_warning ) {
                    if ( $player_warning->rubber_id ) {
                        $warning_player = true;
                        $rubber = get_rubber( $player_warning->rubber_id );
                        if ( $rubber ) {
                            if ( $player_warning->team_id === intval( $match->home_team ) ) {
                                $team = 'home';
                            } else {
                                $team = 'away';
                            }
                            if ( intval( $player_warning->player_id ) === intval( $rubber->players[ $team ]['1']->id ) ) {
                                $player_number = 1;
                            } else {
                                $player_number = 2;
                            }
                            $player_ref                     = 'players_' . $rubber->rubber_number . '_' . $team . '_' . $player_number;
                            $player_warnings[ $player_ref ] = $player_warning->description;
                        }
                    } else {
                        $warning_match[] = $player_warning->description;
                    }
                }
                if ( $warning_player ) {
                    $msg .= '<br>' . __( 'Match has player warnings', 'racketmanager' );
                }
                foreach ( $warning_match as $warning ) {
                    $msg .= '<br>' . $warning;
                }
            } else {
                $result_status = 'success';
            }
            $home_points = $updated_rubbers['homepoints'] ?? null;
            $away_points = $updated_rubbers['awaypoints'] ?? null;
            array_push( $return, $msg, $home_points, $away_points, $updated_rubbers, $result_status, $player_warnings );
            wp_send_json_success( $return );
        } else {
            $msg = __( 'Unable to save result', 'racketmanager' );
            array_push( $return, $msg, $err_msg, $err_field, $updated_rubbers );
            wp_send_json_error( $return, 500 );
        }
    }
    /**
     * Update results for each rubber
     *
     * @param object $match match details.
     * @param string $new_match_status match status.
     */
    public function update_rubber_results( object $match, string $new_match_status ): array {
        global $racketmanager, $match;
        $return              = array();
        $error               = false;
        $err_msg             = array();
        $err_field           = array();
        $match_confirmed     = '';
        $home_team_score     = 0;
        $away_team_score     = 0;
        $home_team_score_tie = 0;
        $away_team_score_tie = 0;
        if ( ! empty( $match->leg ) && '2' === $match->leg && ! empty( $match->linked_match ) ) {
            $linked_match = get_match( $match->linked_match );
            if ( ! empty( $linked_match->winner_id ) ) {
                $home_team_score_tie = $linked_match->home_points;
                $away_team_score_tie = $linked_match->away_points;
            }
        }
        $match_players                        = array();
        $player_options                       = $racketmanager->get_options( 'player' );
        $club                                 = get_club( $match->teams['home']->club_id );
        $player['walkover']['male']['home']   = $club->get_player( $player_options['walkover']['male'] );
        $player['walkover']['female']['home'] = $club->get_player( $player_options['walkover']['female'] );
        $player['noplayer']['male']['home']   = $club->get_player( $player_options['noplayer']['male'] );
        $player['noplayer']['female']['home'] = $club->get_player( $player_options['noplayer']['female'] );
        $player['share']['male']['home']      = $club->get_player( $player_options['share']['male'] );
        $player['share']['female']['home']    = $club->get_player( $player_options['share']['female'] );
        $club                                 = get_club( $match->teams['away']->club_id );
        $player['walkover']['male']['away']   = $club->get_player( $player_options['walkover']['male'] );
        $player['walkover']['female']['away'] = $club->get_player( $player_options['walkover']['female'] );
        $player['noplayer']['male']['away']   = $club->get_player( $player_options['noplayer']['male'] );
        $player['noplayer']['female']['away'] = $club->get_player( $player_options['noplayer']['female'] );
        $player['share']['male']['away']      = $club->get_player( $player_options['share']['male'] );
        $player['share']['female']['away']    = $club->get_player( $player_options['share']['female'] );
        $updated_rubbers                      = array();

        $match        = get_match( $match );
        if ( empty( $match->date_result_entered ) ) {
            $match->set_result_entered();
        }
        $is_cancelled = false;
        if ( 'cancelled' === $new_match_status ) {
            $is_cancelled = true;
        }
        $is_withdrawn = false;
        if ( $match->teams['home']->is_withdrawn || $match->teams['away']->is_withdrawn ) {
            $is_withdrawn     = true;
            $new_match_status = 'withdrawn';
        }
        $match->home_points = 0;
        $match->away_points = 0;
        $match->delete_result_check();
        $stats                    = array();
        $stats['rubbers']['home'] = 0;
        $stats['rubbers']['away'] = 0;
        $stats['sets']['home']    = 0;
        $stats['sets']['away']    = 0;
        $stats['games']['home']   = 0;
        $stats['games']['away']   = 0;

        for ( $ix = 1; $ix <= $match->num_rubbers; $ix++ ) {
            // phpcs:disable WordPress.Security.NonceVerification.Missing
            $rubber_id    = isset( $_POST['id'][ $ix ] ) ? intval( $_POST['id'][ $ix ] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $rubber_type  = isset( $_POST['type'][ $ix ] ) ? sanitize_text_field( wp_unslash( $_POST['type'][ $ix ] ) ) : null;
            $players      = isset( $_POST['players'][ $ix ] ) ? ( wp_unslash( $_POST['players'][ $ix ] ) ) : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $sets         = isset( $_POST['sets'][ $ix ] ) ? ( wp_unslash( $_POST['sets'][ $ix ] ) ) : array(); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $match_status = isset( $_POST['match_status'][ $ix ] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'][ $ix ] ) ) : null;
            // phpcs:enable WordPress.Security.NonceVerification.Missing
            $rubber    = get_rubber( $rubber_id );
            $opponents = array( 'home', 'away' );
            if ( 'D' === substr( $rubber_type, 1, 1 ) ) {
                $player_numbers = array( '1', '2' );
            } else {
                $player_numbers = array( '1' );
            }
            $set_prefix     = 'set_' . $ix . '_';
            $validate_match = true;
            $playoff        = false;
            $share          = null;
            $walkover       = null;
            $retired        = null;
            $invalid        = null;
            $abandoned      = null;
            $is_cancelled   = null;
            if ( $is_withdrawn ) {
                $match_status = 'withdrawn';
            }
            switch ( $match_status ) {
                case 'share':
                    $share = true;
                    if ( 'MD' === $match->league->type || 'BD' === $match->league->type ) {
                        $players['home']['1'] = $player['share']['male']['home']->roster_id;
                        $players['home']['2'] = $players['home']['1'];
                        $players['away']['1'] = $player['share']['male']['away']->roster_id;
                        $players['away']['2'] = $players['away']['1'];
                    } elseif ( 'WD' === $match->league->type || 'GD' === $match->league->type ) {
                        $players['home']['1'] = $player['share']['female']['home']->roster_id;
                        $players['home']['2'] = $players['home']['1'];
                        $players['away']['1'] = $player['share']['female']['away']->roster_id;
                        $players['away']['2'] = $players['away']['1'];
                    } elseif ( 'XD' === $match->league->type ) {
                        $players['home']['1'] = $player['share']['male']['home']->roster_id;
                        $players['home']['2'] = $player['share']['female']['home']->roster_id;
                        $players['away']['1'] = $player['share']['male']['away']->roster_id;
                        $players['away']['2'] = $player['share']['female']['away']->roster_id;
                    }
                    break;
                case 'walkover_player1':
                    $walkover = 'home';
                    if ( 'MD' === $match->league->type || 'BD' === $match->league->type ) {
                        if ( empty( $players['home']['1'] ) ) {
                            $players['home']['1'] = $player['walkover']['male']['home']->roster_id;
                        }
                        if ( empty( $players['home']['2'] ) ) {
                            $players['home']['2'] = $player['walkover']['male']['home']->roster_id;
                        }
                        $players['away']['1'] = $player['noplayer']['male']['away']->roster_id;
                        $players['away']['2'] = $players['away']['1'];
                    } elseif ( 'WD' === $match->league->type || 'GD' === $match->league->type ) {
                        if ( empty( $players['home']['1'] ) ) {
                            $players['home']['1'] = $player['walkover']['female']['home']->roster_id;
                        }
                        if ( empty( $players['home']['2'] ) ) {
                            $players['home']['2'] = $player['walkover']['female']['home']->roster_id;
                        }
                        $players['away']['1'] = $player['noplayer']['female']['away']->roster_id;
                        $players['away']['2'] = $players['away']['1'];
                    } elseif ( 'XD' === $match->league->type ) {
                        if ( empty( $players['home']['1'] ) ) {
                            $players['home']['1'] = $player['walkover']['male']['home']->roster_id;
                        }
                        if ( empty( $players['home']['2'] ) ) {
                            $players['home']['2'] = $player['walkover']['female']['home']->roster_id;
                        }
                        $players['away']['1'] = $player['noplayer']['male']['away']->roster_id;
                        $players['away']['2'] = $player['noplayer']['female']['away']->roster_id;
                    }
                    break;
                case 'walkover_player2':
                    $walkover = 'away';
                    if ( 'MD' === $match->league->type || 'BD' === $match->league->type ) {
                        $players['home']['1'] = $player['noplayer']['male']['home']->roster_id;
                        $players['home']['2'] = $players['home']['1'];
                        if ( empty( $players['away']['1'] ) ) {
                            $players['away']['1'] = $player['walkover']['male']['away']->roster_id;
                        }
                        if ( empty( $players['away']['2'] ) ) {
                            $players['away']['2'] = $player['walkover']['male']['away']->roster_id;
                        }
                    } elseif ( 'WD' === $match->league->type || 'GD' === $match->league->type ) {
                        $players['home']['1'] = $player['noplayer']['female']['home']->roster_id;
                        $players['home']['2'] = $players['home']['1'];
                        if ( empty( $players['away']['1'] ) ) {
                            $players['away']['1'] = $player['walkover']['female']['away']->roster_id;
                        }
                        if ( empty( $players['away']['2'] ) ) {
                            $players['away']['2'] = $player['walkover']['female']['away']->roster_id;
                        }
                    } elseif ( 'XD' === $match->league->type ) {
                        $players['home']['1'] = $player['noplayer']['male']['home']->roster_id;
                        $players['home']['2'] = $player['noplayer']['female']['home']->roster_id;
                        if ( empty( $players['away']['1'] ) ) {
                            $players['away']['1'] = $player['walkover']['male']['away']->roster_id;
                        }
                        if ( empty( $players['away']['2'] ) ) {
                            $players['away']['2'] = $player['walkover']['female']['away']->roster_id;
                        }
                    }
                    break;
                case 'retired_player1':
                    $retired = 'home';
                    break;
                case 'retired_player2':
                    $retired = 'away';
                    break;
                case 'invalid_player1':
                    $invalid = 'home';
                    break;
                case 'invalid_player2':
                    $invalid = 'away';
                    break;
                case 'invalid_players':
                    $invalid = 'both';
                    break;
                case 'abandoned':
                    $abandoned = true;
                    break;
                case 'cancelled':
                    $is_cancelled = true;
                    break;
                default:
                    break;
            }
            if ( isset( $match->league->scoring ) && ( 'TP' === $match->league->scoring || 'MP' === $match->league->scoring || 'MPL' === $match->league->scoring ) && intval( $match->num_rubbers ) === $ix && intval( $match->num_rubbers ) > $match->league->num_rubbers ) {
                if ( empty( $match->leg ) || '2' !== $match->leg ) {
                    if ( $home_team_score !== $away_team_score ) {
                        $validate_match = false;
                    } else {
                        $playoff = true;
                    }
                } elseif ( $home_team_score_tie !== $away_team_score_tie ) {
                    $validate_match = false;
                } else {
                    $playoff = true;
                }
            }
            if ( $validate_match ) {
                if ( empty( $share ) && empty( $is_withdrawn ) && empty( $is_cancelled ) ) {
                    foreach ( $opponents as $opponent ) {
                        $team_players = $players[$opponent] ?? array();
                        foreach ( $player_numbers as $player_number ) {
                            if ( empty( $team_players[ $player_number ] ) ) {
                                $err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
                                $err_msg[]   = __( 'Player not selected', 'racketmanager' );
                            } else {
                                $player_ref  = $team_players[ $player_number ];
                                $club_player = get_club_player( $player_ref );
                                if ( ! $club_player->system_record ) {
                                    $player_found = in_array( $player_ref, $match_players, true );
                                    if ( ! $player_found ) {
                                        if ( $playoff ) {
                                            $err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
                                            $err_msg[]   = __( 'Player for playoff must have played', 'racketmanager' );
                                        } elseif ( $rubber->reverse_rubber ) {
                                            $err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
                                            $err_msg[]   = __( 'Player for reverse rubber must have played', 'racketmanager' );
                                        } else {
                                            $match_players[] = $player_ref;
                                        }
                                    } elseif ( ! $playoff && ! $rubber->reverse_rubber ) {
                                        $err_field[] = 'players_' . $ix . '_' . $opponent . '_' . $player_number;
                                        $err_msg[]   = __( 'Player already selected', 'racketmanager' );
                                    }
                                }
                            }
                        }
                    }
                }
                $status              = null;
                $rubber_number       = $ix;
                $errors['err_msg']   = $err_msg;
                $errors['err_field'] = $err_field;
                $match_validate      = $this->validate_match_score( $match, $sets, $set_prefix, $errors, $rubber_number, $match_status );
                $error               = $match_validate[0];
                $err_msg             = $match_validate[1];
                $err_field           = $match_validate[2];
                $sets                = $match_validate[5];
                $match_stats         = $match_validate[6];
                $points              = $match_validate[7];
                if ( ! $error ) {
                    $custom         = array();
                    $custom['sets'] = $sets;
                    if ( $walkover ) {
                        $status             = 1;
                        $custom['walkover'] = $walkover;
                    } elseif ( $share ) {
                        $status          = 3;
                        $custom['share'] = true;
                    } elseif ( $retired ) {
                        $status            = 2;
                        $custom['retired'] = $retired;
                    } elseif ( $abandoned ) {
                        $status              = 6;
                        $custom['abandoned'] = true;
                    } elseif ( $is_cancelled ) {
                        $status              = 8;
                        $custom['cancelled'] = true;
                    } elseif ( $invalid ) {
                        $status            = 9;
                        $custom['invalid'] = $invalid;
                    } elseif ( empty( $status ) ) {
                        $status = 0;
                    }
                    $custom['stats']         = $match_stats;
                    $stats['sets']['home']  += $match_stats['sets']['home'];
                    $stats['sets']['away']  += $match_stats['sets']['away'];
                    $stats['games']['home'] += $match_stats['games']['home'];
                    $stats['games']['away'] += $match_stats['games']['away'];
                    $points['home']['team']  = $match->home_team;
                    $points['away']['team']  = $match->away_team;
                    $result                  = $rubber->calculate_result( $points );
                    $home_score              = $result->home;
                    $away_score              = $result->away;
                    $winner                  = $result->winner;
                    $loser                   = $result->loser;
                    if ( is_numeric( $home_score ) ) {
                        $home_team_score     += $home_score;
                        $home_team_score_tie += $home_score;
                    }
                    if ( is_numeric( $away_score ) ) {
                        $away_team_score     += $away_score;
                        $away_team_score_tie += $away_score;
                    }
                    if ( $winner === $match->home_team ) {
                        ++$stats['rubbers']['home'];
                    } elseif ( $winner === $match->away_team ) {
                        ++$stats['rubbers']['away'];
                    } else {
                        $stats['rubbers']['home'] += 0.5;
                        $stats['rubbers']['away'] += 0.5;
                    }
                    if ( ! empty( $home_score ) || ! empty( $away_score ) || $is_withdrawn || $is_cancelled || $invalid ) {
                        $home_score                                   = ! empty( $home_score ) ? $home_score : 0;
                        $away_score                                   = ! empty( $away_score ) ? $away_score : 0;
                        $updated_rubbers['homepoints'][ $rubber_id ] = $home_score;
                        $updated_rubbers['awaypoints'][ $rubber_id ] = $away_score;
                        $match->home_points                         += $home_score;
                        $match->away_points                         += $away_score;

                        $rubber->set_players( $players );
                        $rubber->home_points = $home_score;
                        $rubber->away_points = $away_score;
                        $rubber->winner_id   = $winner;
                        $rubber->loser_id    = $loser;
                        $rubber->custom      = $custom;
                        $rubber->status      = $status;
                        $rubber->update_result();
                        $match_confirmed = 'P';
                        foreach ( $opponents as $opponent ) {
                            foreach ( $player_numbers as $player_number ) {
                                $updated_rubbers[ $rubber_id ]['players'][ $opponent ][] = $players[$opponent][$player_number] ?? null;
                            }
                        }
                        $updated_rubbers[ $rubber_id ]['sets']   = $sets;
                        $updated_rubbers[ $rubber_id ]['winner'] = $winner;
                    }
                }
            }
        }
        if ( ! $error ) {
            if ( $is_withdrawn || $is_cancelled ) {
                $match_confirmed = 'P';
                $home_team_score = 0;
                $away_team_score = 0;
            } else {
                $check_options = $racketmanager->get_options( 'checks' );
                $match->delete_result_check();
                $rubbers      = $match->get_rubbers();
                $prev_ratings = array();
                foreach ( $rubbers as $rubber ) {
                    if ( ! empty( $rubber->players ) ) {
                        $check_results = $rubber->check_players();
                        if ( ! empty( $match->league->event->competition->rules['leadTimecheck'] ) && ! empty( $check_options['wtn_check'] ) ) {
                            $wtns = $check_results['wtns'];
                            if ( ! empty( $prev_wtns ) ) {
                                foreach ( $wtns as $opponent => $wtn ) {
                                    if ( $wtn < $prev_wtns[ $opponent ] ) {
                                        $team_err = $opponent . '_team';
                                        $team     = $match->$team_err;
                                        /* translators: %1$d: rubber number, %2$d: rubber team rating, %3$d: previous rubber rating*/
                                        $message = sprintf( __( 'Players out of order. Rubber %1$d has wtn %2$.1f - previous rubber has wtn %3$.1f', 'racketmanager' ), $rubber->rubber_number, $wtn, $prev_wtns[ $opponent ] );
                                        $players = $rubber->players[ $opponent ];
                                        foreach ( $players as $player ) {
                                            $match->add_player_result_check( $team, $player->id, $message, $rubber->id );
                                        }
                                    }
                                }
                            }
                            $prev_wtns = $wtns;
                        } elseif ( ! empty( $match->league->event->competition->rules['ratingCheck'] ) && ! empty( $check_options['ratingCheck'] ) ) {
                            $ratings = $check_results['ratings'];
                            if ( ! empty( $prev_ratings ) ) {
                                foreach ( $ratings as $opponent => $rating ) {
                                    if ( $rating > $prev_ratings[ $opponent ] ) {
                                        $team_err = $opponent . '_team';
                                        $team     = $match->$team_err;
                                        /* translators: %1$d: rubber number, %2$d: rubber team rating, %3$d: previous rubber rating*/
                                        $message = sprintf( __( 'Players out of order. Rubber %1$d has rating %2$d - previous rubber has rating %3$d', 'racketmanager' ), $rubber->rubber_number, $rating, $prev_ratings[ $opponent ] );
                                        $players = $rubber->players[ $opponent ];
                                        foreach ( $players as $player ) {
                                            $match->add_player_result_check( $team, $player->id, $message, $rubber->id );
                                        }
                                    }
                                }
                            }
                            $prev_ratings = $ratings;
                        }
                    }
                }
            }
            $match_custom['stats'] = $stats;
            $status                = Racketmanager_Util::get_match_status_code( $new_match_status );
            $match->update_result( $home_team_score, $away_team_score, $match_custom, $match_confirmed, $status );
            $competition_options = $racketmanager->get_options( $match->league->event->competition->type );
            if ( ! empty( $match->league->event->competition->rules['resultTimeout'] ) ) {
                $result_timeout      = $competition_options['resultTimeout'] ?? null;
                if ( $result_timeout && ! empty( $match->date_result_entered ) ) {
                    $date_result_entered = date_create( $match->date_result_entered );
                    $match_date          = date_create( $match->date );
                    $diff                = date_diff( $date_result_entered, $match_date );
                    if ( $diff->invert ) {
                        $time_diff  = $diff->days * 24 * 60;
                        $time_diff += $diff->h * 60;
                        $time_diff += $diff->i;
                        $timeout    = $result_timeout * 60;
                        if ( $time_diff > $timeout ) {
                            $time_diff_hours = $time_diff / 60;
                            /* translators: %d: number of hours */
                            $reason = sprintf( __( 'Result entered %d hours after match', 'racketmanager' ), $time_diff_hours );
                            $match->add_match_result_check( $match->home_team, $reason );
                        }
                    }
                }
            }
        }
        array_push( $return, $error, $match_confirmed, $err_msg, $err_field, $updated_rubbers );
        return $return;
    }
    /**
     * Validate Match Score
     *
     * @param object $match match details.
     * @param array $sets set details.
     * @param string $set_prefix_start set prefix.
     * @param array $errors array of error messages and error fields.
     * @param false|int $rubber_number optional rubber number.
     * @param false|string $match_status match_status setting.
     */
    public function validate_match_score( object $match, array $sets, string $set_prefix_start, array $errors, false|int $rubber_number = false, false|string $match_status = false ): array {
        $num_sets_to_win  = intval( $match->league->num_sets_to_win );
        $num_games_to_win = 1;
        $point_rule       = $match->league->get_point_rule();
        $points_format    = null;
        if ( 1 === $num_sets_to_win && ! empty( $point_rule['match_result'] ) && 'games' === $point_rule['match_result'] ) {
            $points_format = 'games';
        }
        $return                 = array();
        $home_score              = 0;
        $away_score              = 0;
        $error                  = false;
        $scoring                = $match->league->scoring ?? 'TB';
        $sets_updated           = array();
        $s                      = 1;
        $stats                  = array();
        $stats['sets']['home']  = 0;
        $stats['sets']['away']  = 0;
        $stats['games']['home'] = 0;
        $stats['games']['away'] = 0;

        $points['home']['sets']   = 0;
        $points['away']['sets']   = 0;
        $points['shared']['sets'] = 0;
        $points['split']['sets']  = 0;
        if ( ! empty( $sets ) ) {
            $num_sets    = count( $sets );
            $set_retired = null;
            if ( 'retired_player1' === $match_status || 'retired_player2' === $match_status || 'abandoned' === $match_status ) {
                for ( $s1 = $num_sets; $s1 >= 1; $s1-- ) {
                    if ( '' !== $sets[ $s1 ]['player1'] || '' !== $sets[ $s1 ]['player2'] ) {
                        $set_retired = $s1;
                        break;
                    }
                }
            }
            foreach ( $sets as $set ) {
                $set_prefix = $set_prefix_start . $s . '_';
                $set_type   = Racketmanager_Util::get_set_type( $scoring, $match->final_round, $match->league->num_sets, $s, $rubber_number, $match->num_rubbers, $match->leg );
                $set_info   = Racketmanager_Util::get_set_info( $set_type );
                if ( 1 === $s ) {
                    $num_games_to_win = $set_info->min_win;
                }
                if ( ( $s > $num_sets_to_win ) && ( $home_score === $num_sets_to_win || $away_score === $num_sets_to_win ) ) {
                    $set_info->set_type = 'null';
                }
                $set_status = null;
                switch ( $match_status ) {
                    case 'retired_player1':
                    case 'retired_player2':
                    case 'abandoned':
                        if ( $set_retired === $s ) {
                            $set_status = $match_status;
                        } elseif ( $s > $set_retired ) {
                            $set_info->set_type = 'null';
                        }
                        break;
                    case 'cancelled':
                    default:
                        $set_status = $match_status;
                        break;
                }
                $set_validate        = $this->validate_set( $set, $set_prefix, $errors['err_msg'], $errors['err_field'], $set_info, $set_status );
                $set                 = $set_validate[2];
                $errors['err_msg']   = $set_validate[0];
                $errors['err_field'] = $set_validate[1];
                if ( $errors['err_msg'] ) {
                    $error = true;
                }
                $set_player_1  = is_null( $set['player1'] ) ? null : strtoupper( $set['player1'] );
                $set_player_2  = is_null( $set['player2'] ) ? null : strtoupper( $set['player2'] );
                $set_completed = $set['completed'];
                if ( null !== $set_player_1 && null !== $set_player_2 ) {
                    if ( ( $set_player_1 > $set_player_2 && ( empty( $set_status ) || ( 'abandoned' === $set_status && $set_completed ) ) ) || ( 'retired_player2' ) === $set_status || ( 'invalid_player2' ) === $set_status || ( 'invalid_players' ) === $set_status ) {
                        if ( empty( $points_format ) ) {
                            ++$points['home']['sets'];
                            ++$stats['sets']['home'];
                            ++$home_score;
                            if ( 'MTB' === $set['settype'] ) {
                                ++$stats['games']['home'];
                            }
                        } else {
                            $home_score = $set_player_1;
                            $away_score = $set_player_2;
                        }
                    } elseif ( ( $set_player_1 < $set_player_2 && ( empty( $set_status ) || ( 'abandoned' === $set_status && $set_completed ) ) ) || ( 'retired_player1' ) === $set_status || ( 'invalid_player1' ) === $set_status ) {
                        if ( empty( $points_format ) ) {
                            ++$points['away']['sets'];
                            ++$stats['sets']['away'];
                            ++$away_score;
                            if ( 'MTB' === $set['settype'] ) {
                                ++$stats['games']['away'];
                            }
                        } else {
                            $home_score = $set_player_1;
                            $away_score = $set_player_2;
                        }
                    } elseif ( 'S' === $set_player_1 ) {
                        ++$points['shared']['sets'];
                        $stats['sets']['home'] += 0.5;
                        $stats['sets']['away'] += 0.5;
                        $home_score             += 0.5;
                        $away_score             += 0.5;
                    }
                }
                if ( is_numeric( $set_player_1 ) && 'MTB' !== $set['settype'] ) {
                    $stats['games']['home'] += $set_player_1;
                }
                if ( is_numeric( $set_player_2 ) && 'MTB' !== $set['settype'] ) {
                    $stats['games']['away'] += $set_player_2;
                }
                $sets_updated[ $s ] = $set;
                ++$s;
            }
            if ( ! empty( $home_score ) && ! empty( $away_score ) ) {
                ++$points['split']['sets'];
            }
        }
        if ( 'league' === $match->league->event->competition->type ) {
            $point_rule              = $match->league->get_point_rule();
            $walkover_rubber_penalty = empty( $point_rule['forwalkover_rubber'] ) ? 0 : $point_rule['forwalkover_rubber'];
        } else {
            $walkover_rubber_penalty = 0;
        }
        if ( 'walkover_player1' === $match_status ) {
            $stats['sets']['home']     += $num_sets_to_win;
            $points['home']['sets']    += $num_sets_to_win;
            $points['away']['walkover'] = true;
            $home_score                 += $num_sets_to_win;
            $away_score                 -= $walkover_rubber_penalty;
            $stats['games']['home']    += $num_games_to_win * $num_sets_to_win;
        } elseif ( 'walkover_player2' === $match_status ) {
            $stats['sets']['away']     += $num_sets_to_win;
            $points['away']['sets']    += $num_sets_to_win;
            $points['home']['walkover'] = true;
            $away_score                 += $num_sets_to_win;
            $home_score                 -= $walkover_rubber_penalty;
            $stats['games']['away']    += $num_games_to_win * $num_sets_to_win;
        } elseif ( 'retired_player1' === $match_status ) {
            $points['home']['retired'] = true;
            $points['away']['sets']    = $num_sets_to_win;
            $stats['sets']['away']     = $num_sets_to_win;
            $away_score                 = $num_sets_to_win;
        } elseif ( 'retired_player2' === $match_status ) {
            $points['away']['retired'] = true;
            $points['home']['sets']    = $num_sets_to_win;
            $stats['sets']['home']     = $num_sets_to_win;
            $home_score                 = $num_sets_to_win;
        } elseif ( 'invalid_player2' === $match_status ) {
            $stats['sets']['home']     = $num_sets_to_win;
            $points['home']['sets']    = $num_sets_to_win;
            $points['away']['invalid'] = true;
            $home_score                 = $num_sets_to_win;
            $away_score                -= $walkover_rubber_penalty;
            $stats['games']['home']    = $num_games_to_win * $num_sets_to_win;
            $stats['games']['away']    = 0;
        } elseif ( 'invalid_player1' === $match_status ) {
            $stats['sets']['away']     = $num_sets_to_win;
            $points['away']['sets']    = $num_sets_to_win;
            $points['home']['invalid'] = true;
            $away_score                 = $num_sets_to_win;
            $home_score                -= $walkover_rubber_penalty;
            $stats['games']['away']    = $num_games_to_win * $num_sets_to_win;
            $stats['games']['home']    = 0;
        } elseif ( 'invalid_players' === $match_status ) {
            $stats['sets']['home']     = 0;
            $points['home']['sets']    = 0;
            $stats['sets']['away']     = 0;
            $points['away']['sets']    = 0;
            $points['both']['invalid'] = true;
            $away_score                 = $walkover_rubber_penalty;
            $home_score                 = $walkover_rubber_penalty;
            $stats['games']['away']    = 0;
            $stats['games']['home']    = 0;
        } elseif ( 'share' === $match_status ) {
            $shared_sets              = $match->league->num_sets / 2;
            $points['shared']['sets'] = $match->league->num_sets;
            $home_score               += $shared_sets;
            $away_score               += $shared_sets;
        } elseif ( 'withdrawn' === $match_status ) {
            $points['withdrawn'] = 1;
        } elseif ( 'cancelled' === $match_status ) {
            $points['cancelled'] = 1;
        } elseif ( 'abandoned' === $match_status ) {
            if ( $home_score !== $num_sets_to_win && $away_score !== $num_sets_to_win ) {
                $shared_sets              = $match->league->num_sets - $home_score - $away_score;
                $points['shared']['sets'] = $shared_sets;
                $home_score              += $shared_sets;
                $away_score              += $shared_sets;
            }
        }
        array_push( $return, $error, $errors['err_msg'], $errors['err_field'], $home_score, $away_score, $sets_updated, $stats, $points );
        return $return;
    }

    /**
     * Validate set
     *
     * @param array $set set information.
     * @param string $set_prefix set prefix.
     * @param array $err_msg error messages.
     * @param array $err_field error fields.
     * @param object $set_info type of set.
     * @param string|null $match_status match_status setting.
     */
    public function validate_set( array $set, string $set_prefix, array $err_msg, array $err_field, object $set_info, ?string $match_status ): array {
        $return         = array();
        $completed_set  = false;
        $set_type       = $set_info->set_type;
        if ( 'walkover_player1' === $match_status || 'walkover_player2' === $match_status ) {
            if ( 'null' === $set_type ) {
                $set['player1'] = '';
                $set['player2'] = '';
            } else {
                $set['player1'] = null;
                $set['player2'] = null;
            }
            $set['tiebreak'] = '';
        } elseif ( 'retired_player1' === $match_status || 'retired_player2' === $match_status || 'abandoned' === $match_status ) {
            if ( 'null' === $set_type ) {
                $set['player1']  = '';
                $set['player2']  = '';
                $set['tiebreak'] = '';
            }
        }
        if ( ! is_null( $set['player1'] ) || ! is_null( $set['player2'] ) ) {
            if ( 'null' === $set_type ) {
                if ( '' !== $set['player1'] ) {
                    $err_msg[]   = __( 'Set score should be empty', 'racketmanager' );
                    $err_field[] = $set_prefix . 'player1';
                }
                if ( '' !== $set['player2'] ) {
                    $err_msg[]   = __( 'Set score should be empty', 'racketmanager' );
                    $err_field[] = $set_prefix . 'player2';
                }
                if ( '' !== $set['tiebreak'] ) {
                    $err_msg[]   = __( 'Tie break should be empty', 'racketmanager' );
                    $err_field[] = $set_prefix . 'tiebreak';
                }
            } elseif ( 'share' === $match_status || 'withdrawn' === $match_status ) {
                $set['player1']  = '';
                $set['player2']  = '';
                $set['tiebreak'] = '';
            } elseif ( 'S' === $set['player1'] || 'S' === $set['player2'] ) {
                if ( 'S' !== $set['player1'] ) {
                    $err_msg[]   = __( 'Both scores must be shared', 'racketmanager' );
                    $err_field[] = $set_prefix . 'player1';
                }
                if ( 'S' !== $set['player2'] ) {
                    $err_msg[]   = __( 'Both scores must be shared', 'racketmanager' );
                    $err_field[] = $set_prefix . 'player2';
                }
            } elseif ( empty( $set['player1'] ) && empty( $set['player2'] ) ) {
                if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status && 'abandoned' !== $match_status ) {
                    $err_msg[]   = __( 'Set scores must be entered', 'racketmanager' );
                    $err_field[] = $set_prefix . 'player1';
                    $err_field[] = $set_prefix . 'player2';
                }
            } elseif ( $set['player1'] === $set['player2'] ) {
                if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status && 'abandoned' !== $match_status ) {
                    $err_msg[]   = __( 'Set scores must be different', 'racketmanager' );
                    $err_field[] = $set_prefix . 'player1';
                    $err_field[] = $set_prefix . 'player2';
                }
            } elseif ( $set['player1'] > $set['player2'] ) {
                $set_data        = new stdClass();
                $set_data->msg   = $err_msg;
                $set_data->field = $err_field;
                $set_data        = $this->validate_set_score( $set, $set_prefix, 'player1', 'player2', $set_data, $set_info, $match_status );
                $err_msg         = $set_data->msg;
                $err_field       = $set_data->field;
                $completed_set   = $set_data->completed_set;
            } elseif ( $set['player1'] < $set['player2'] ) {
                $set_data        = new stdClass();
                $set_data->msg   = $err_msg;
                $set_data->field = $err_field;
                $set_data        = $this->validate_set_score( $set, $set_prefix, 'player2', 'player1', $set_data, $set_info, $match_status );
                $err_msg         = $set_data->msg;
                $err_field       = $set_data->field;
                $completed_set   = $set_data->completed_set;
            } elseif ( '' === $set['player1'] || '' === $set['player2'] ) {
                if ( 'retired_player1' !== $match_status && 'retired_player2' !== $match_status ) {
                    $err_msg[] = __( 'Set score not entered', 'racketmanager' );
                    if ( '' === $set['player1'] ) {
                        $err_field[] = $set_prefix . 'player1';
                    }
                    if ( '' === $set['player2'] ) {
                        $err_field[] = $set_prefix . 'player2';
                    }
                }
            }
        }
        $set['completed'] = $completed_set;
        $set['settype']   = $set_type;
        array_push( $return, $err_msg, $err_field, $set );
        return $return;
    }
    /**
     * Validate set score function
     *
     * @param array $set set details.
     * @param string $set_prefix ste prefix.
     * @param string $team_1 team 1.
     * @param string $team_2 team 2.
     * @param object $return_data return data.
     * @param object $set_info set info.
     * @param string|null $match_status match status.
     *
     * @return object
     */
    private function validate_set_score( array $set, string $set_prefix, string $team_1, string $team_2, object $return_data, object $set_info, string $match_status = null ): object {
        $game_difference_incorrect = __( 'Games difference incorrect', 'racketmanager' );
        $tie_break_score_required  = __( 'Tie break score required', 'racketmanager' );
        $tiebreak_allowed  = $set_info->tiebreak_allowed;
        $tiebreak_required = $set_info->tiebreak_required;
        $max_win           = $set_info->max_win;
        $min_win           = $set_info->min_win;
        $max_loss          = $set_info->max_loss;
        $min_loss          = $set_info->min_loss;
        $err_msg           = $return_data->msg;
        $err_field         = $return_data->field;
        $retired_player    = 'retired_' . $team_2;
        $completed_set     = true;
        if ( $set[ $team_1 ] < $min_win && $match_status !== $retired_player ) {
            if ( 'abandoned' === $match_status ) {
                $completed_set = false;
            } else {
                $err_msg[]   = __( 'Winning set score too low', 'racketmanager' );
                $err_field[] = $set_prefix . $team_1;
            }
        } elseif ( $set[ $team_1 ] > $max_win ) {
            $err_msg[]   = __( 'Winning set score too high', 'racketmanager' );
            $err_field[] = $set_prefix . $team_1;
        } elseif ( intval( $set[ $team_1 ] ) === intval( $min_win ) && $max_win !== $min_win && $set[ $team_2 ] > $min_loss && $match_status !== $retired_player ) {
            $err_msg[]   = __( 'Games difference must be at least 2', 'racketmanager' );
            $err_field[] = $set_prefix . $team_1;
            $err_field[] = $set_prefix . $team_2;
        } elseif ( intval( $set[ $team_1 ] ) === $max_win ) {
            if ( $set[ $team_2 ] < $max_loss && $max_win !== $min_win ) {
                $err_msg[]   = $game_difference_incorrect;
                $err_field[] = $set_prefix . $team_1;
                $err_field[] = $set_prefix . $team_2;
            } elseif ( $tiebreak_allowed && $set[ $team_2 ] > $max_loss ) {
                if ( ! strlen( $set['tiebreak'] ) > 0 ) {
                    $err_msg[]   = $tie_break_score_required;
                    $err_field[] = $set_prefix . 'tiebreak';
                } elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
                    $err_msg[]   = __( 'Tie break score must be whole number', 'racketmanager' );
                    $err_field[] = $set_prefix . 'tiebreak';
                }
            } elseif ( $tiebreak_required && '' === $set['tiebreak'] ) {
                $err_msg[]   = $tie_break_score_required;
                $err_field[] = $set_prefix . 'tiebreak';
            }
        } elseif ( $set[ $team_1 ] > $min_win && $set[ $team_2 ] < $min_loss ) {
            $err_msg[]   = $game_difference_incorrect;
            $err_field[] = $set_prefix . $team_1;
            $err_field[] = $set_prefix . $team_2;
        } elseif ( $set[ $team_1 ] > $min_win && $set[ $team_2 ] > $min_loss && ( $set[ $team_1 ] - 2 ) !== intval( $set[ $team_2 ] ) ) {
            if ( ! str_starts_with( $match_status, 'retired_player' ) ) {
                $err_msg[]   = $game_difference_incorrect;
                $err_field[] = $set_prefix . $team_2;
            }
        } elseif ( $set['tiebreak'] > '' ) {
            if ( ! $tiebreak_required ) {
                $err_msg[]   = __( 'Tie break score should be empty', 'racketmanager' );
                $err_field[] = $set_prefix . 'tiebreak';
            }
        } elseif ( $tiebreak_required ) {
            if ( '' === $set['tiebreak'] ) {
                $err_msg[]   = $tie_break_score_required;
                $err_field[] = $set_prefix . 'tiebreak';
            } elseif ( ! is_numeric( $set['tiebreak'] ) || strval( round( $set['tiebreak'] ) ) !== $set['tiebreak'] ) {
                $err_msg[]   = __( 'Tie break score must be whole number', 'racketmanager' );
                $err_field[] = $set_prefix . 'tiebreak';
            }
        }
        $return_data->msg           = $err_msg;
        $return_data->field         = $err_field;
        $return_data->completed_set = $completed_set;
        return $return_data;
    }
    /**
     * Confirm results of rubbers
     *
     * @param string|null $result_confirm result confirmation.
     */
    public function confirm_rubber_results( ?string $result_confirm ): string {
        return match ( $result_confirm ) {
            'confirm'   => 'A',
            'challenge' => 'C',
            default     => '',
        };
    }

}
