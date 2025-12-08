<?php
/**
 * AJAX Front end match response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_Ajax_Frontend_Match
 */

namespace Racketmanager\Ajax;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Services\Validator\Validator_Match;
use stdClass;
use function Racketmanager\get_match;
use function Racketmanager\match_header;
use function Racketmanager\match_option_modal;
use function Racketmanager\match_status_modal;
use function Racketmanager\rubber_status_modal;
use function Racketmanager\show_alert;
use function Racketmanager\show_match_card;

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
     *
     * @param $plugin_instance
     */
    public function __construct( $plugin_instance ) {
        parent::__construct( $plugin_instance );
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
        add_action( 'wp_ajax_racketmanager_update_rubbers', array( &$this, 'update_team_match' ) );
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
    #[NoReturn]
    public function match_status_options(): void {
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
            $validator    = $validator->match_status( $match_status, $error_field, true );
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
     * Build screen to show the selected match option
     */
    #[NoReturn]
    public function show_match_option(): void {
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
     * Set the match date function
     *
     * @return void
     */
    public function set_match_date(): void {
        $match_id      = null;
        $modal         = null;
        $schedule_date = null;
        $match         = null;
        $error_field   = 'schedule-date';
        $validator     = new Validator_Match();
        $validator     = $validator->check_security_token( 'racketmanager_nonce', 'match-option' );
        if ( empty( $validator->error ) ) {
            $modal         = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $match_id      = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $schedule_date = isset( $_POST['schedule-date'] ) ? sanitize_text_field( wp_unslash( $_POST['schedule-date'] ) ) : null;
            $validator     = $validator->modal( $modal, $error_field );
            $validator     = $validator->match( $match_id, $error_field );
        }
        if ( empty( $validator->error ) ) {
            $match     = get_match( $match_id );
            $validator = $validator->scheduled_date( $schedule_date, $match->date );
        }
        if ( empty( $validator->error ) ) {
            if ( strlen( $schedule_date ) === 10 ) {
                $schedule_date_fmt = mysql2date( 'D j M', $schedule_date );
            } else {
                $schedule_date_fmt = mysql2date( 'j F Y H:i', $schedule_date );
            }
            $match         = $match->update_match_date( $schedule_date, $match->date );
            $match->status = 5;
            $match->set_status( $match->status );
            $return                         = new stdClass();
            $return->msg                    = __( 'Match schedule updated', 'racketmanager' );
            $return->modal                  = $modal;
            $return->match_id               = $match_id;
            $return->schedule_date          = $schedule_date;
            $return->schedule_date_formated = $schedule_date_fmt;
            wp_send_json_success( $return );
        }
        $return      = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to update match schedule', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
    }
    /**
     * Switch home and away teams function
     *
     * @return void
     */
    public function switch_home_away(): void {
        $modal     = null;
        $match_id  = null;
        $error_field   = 'schedule-date';
        $validator     = new Validator_Match();
        $validator     = $validator->check_security_token( 'racketmanager_nonce', 'match-option' );
        if ( empty( $validator->error ) ) {
            $modal     = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $match_id  = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $validator = $validator->modal( $modal, $error_field );
            $validator = $validator->match( $match_id, $error_field );
        }
        if ( empty( $validator->error ) ) {
            $match = get_match( $match_id );
            $old_home   = $match->home_team;
            $old_away   = $match->away_team;
            $match_date = $match->league->event->seasons[ $match->season ]['match_dates'][ $match->match_day - 1 ];
            if ( $match_date ) {
                $match->update_match_date( $match_date );
                $match->set_teams( $old_away, $old_home );
                $return           = new stdClass();
                $return->msg      = __( 'Home and away teams switched', 'racketmanager' );
                $return->modal    = $modal;
                $return->match_id = $match_id;
                $return->link     = $match->link;
                wp_send_json_success( $return );
            } else {
                $validator->error      = true;
                $validator->err_flds[] = 'schedule-date';
                $validator->err_msgs[] = __( 'Match day not found', 'racketmanager' );
            }
        }
        $return = $validator->get_details();
        if ( empty( $return->msg ) ) {
            $return->msg = __( 'Unable to update match schedule', 'racketmanager' );
        }
        wp_send_json_error( $return, $return->status );
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
     * Show rubber status options
     */
    #[NoReturn]
    public function match_rubber_status_options(): void {
        $output = null;
        $validator = new Validator_Match();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $rubber_id = isset( $_POST['rubber_id'] ) ? intval( $_POST['rubber_id'] ) : null;
            $modal     = isset( $_POST['modal'] ) ? sanitize_text_field( wp_unslash( $_POST['modal'] ) ) : null;
            $status    = isset( $_POST['score_status'] ) ? sanitize_text_field( wp_unslash( $_POST['score_status'] ) ) : null;
            $validator = $validator->modal( $modal );
            $validator = $validator->rubber( $rubber_id );
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
            $validator     = $validator->score_status( $score_status );
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
     * Update match header
     */
    public function update_match_header(): void {
        $validator = new Validator_Match();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $match_id = isset( $_POST['match_id'] ) ? intval( $_POST['match_id'] ) : null;
            $validator = $validator->match( $match_id );
        }
        if ( empty( $validator->error ) ) {
            $edit_mode = isset( $_POST['edit_mode'] ) ? sanitize_text_field( wp_unslash( $_POST['edit_mode'] ) ) : false;
            $output    = match_header( $match_id, array( 'edit' => $edit_mode ) );
            wp_send_json_success( $output );
        }
        $return = $validator->get_details();
        wp_send_json_error( $return, $return->status );
    }
    /**
     * Update match details
     */
    public function update_match(): void {
        $validator = new Validator_Match();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'scores-match' );
        if ( empty( $validator->error ) ) {
            $match_id     = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
            $match_status = isset( $_POST['match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['match_status'] ) ) : null;
            $sets         = $_POST['sets'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
            $validator    = $validator->match( $match_id );
            if ( empty( $validator->error ) ) {
                $match     = get_match( $match_id );
                $validator = $match->handle_result_update( $sets, $match_status );
                if ( empty( $validator->error ) ) {
                    $match = get_match( $match_id );
                    $return = array();
                    array_push( $return, $validator->msg, $match->home_points, $match->away_points, $match->winner_id, $match->sets );
                    wp_send_json_success( $return );
                }
            }
        }
        $msg = __( 'Unable to update match result', 'racketmanager' );
        $return = array();
        array_push( $return, $msg, $validator->err_msgs, $validator->err_flds );
        wp_send_json_error( $return, $validator->status );
    }
    /**
     * Update match details for team matches only
     */
    public function update_team_match(): void {
        $validator = new Validator_Match();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'rubbers-match' );
        if ( empty( $validator->error ) ) {
            $match_id  = isset( $_POST['current_match_id'] ) ? intval( $_POST['current_match_id'] ) : 0;
            $validator = $validator->match( $match_id );
            $action    = isset( $_POST['updateRubber'] ) ? sanitize_text_field( wp_unslash( $_POST['updateRubber'] ) ) : null;
            $validator = $validator->result_action( $action );
            if ( empty( $validator->error ) ) {
                $match = get_match( $match_id );
                switch ( $action ) {
                    case 'results':
                        $match_status    = isset( $_POST['new_match_status'] ) ? sanitize_text_field( wp_unslash( $_POST['new_match_status'] ) ) : null;
                        $match_comments  = isset( $_POST['matchComments'] ) ? wp_unslash( $_POST['matchComments'] ) : '';
                        $rubber_ids      = $_POST['id'] ?? null;
                        $rubber_types    = $_POST['type'] ?? null;
                        $players         = $_POST['players'] ?? array();
                        $sets            = $_POST['sets'] ?? array();
                        $rubber_statuses = $_POST['match_status'] ?? null;
                        $validator       = $match->handle_team_result_update( $match_status, $rubber_statuses, $match_comments, $rubber_ids, $rubber_types, $players, $sets );
                        break;
                    case 'confirm':
                        $result_home      = isset( $_POST['result_home'] ) ? true : null;
                        $result_away      = isset( $_POST['result_away'] ) ? true : null;
                        $result_confirm   = isset( $_POST['resultConfirm'] ) ? sanitize_text_field( wp_unslash( $_POST['resultConfirm'] ) ) : null;
                        $confirm_comments = isset( $_POST['resultConfirmComments'] ) ? sanitize_text_field( wp_unslash( $_POST['resultConfirmComments'] ) ) : '';
                        $validator        = $match->handle_team_result_confirmation( $result_confirm, $confirm_comments, $result_home, $result_away );
                        break;
                    default:
                        break;
                }
            }
        }
        if ( ! empty( $validator->error ) ) {
            $return = $validator;
            if ( empty( $return->msg ) ) {
                $return->msg = __( 'Unable to save result', 'racketmanager' );
            }
            $return->rubbers = $validator->rubbers;
            wp_send_json_error( $return, $return->status );
        }
        $return           = $validator;
        $return->rubbers  = $validator->rubbers;
        $return->status   = $validator->status;
        $return->warnings = $validator->warnings;
        wp_send_json_success( $return );
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
        $winner         = null;
        $loser          = null;
        $score_message  = null;
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
}
