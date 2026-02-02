<?php
/**
 * AJAX response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager\Ajax;

use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Validator\Validator;
use stdClass;
use function Racketmanager\show_alert;

/**
 * Implement AJAX responses for calls from both frontend and admin.
 *
 * @author Paul Moffat
 */
class Ajax {
    public string $event_not_found;
    protected Competition_Entry_Service $competition_entry_service;
    protected Competition_Service $competition_service;
    protected Club_Service $club_service;
    protected Player_Service $player_service;
    protected Finance_Service $finance_service;
    protected Registration_Service $registration_service;
    protected Team_Service $team_service;
    protected RacketManager $racketmanager;

    /**
     * Register ajax actions.
     */
    public function __construct( $plugin_instance ) {
        add_action( 'wp_ajax_racketmanager_get_player_details', array( &$this, 'get_player_details' ) );
        $this->racketmanager             = $plugin_instance;
        $c                               = $this->racketmanager->container;
        $this->competition_entry_service = $c->get( 'competition_entry_service' );
        $this->competition_service       = $c->get( 'competition_service' );
        $this->club_service              = $c->get( 'club_service' );
        $this->finance_service           = $c->get( 'finance_service' );
        $this->player_service            = $c->get( 'player_service' );
        $this->registration_service      = $c->get( 'registration_service' );
        $this->team_service              = $c->get( 'team_service' );
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    public function logged_out(): void {
        $return    = array();
        $err_msg   = array();
        $err_field = array();
        $msg       = __( 'Must be logged in to access this feature', 'racketmanager' );
        array_push( $return, $msg, $err_msg, $err_field );
        wp_send_json_error( $return, '401' );
    }
    /**
     * Logged-out user for modal function
     *
     * @return void
     */
    public function logged_out_modal(): void {
        $return = array();
        $msg    = __( 'Must be logged in to access this feature', 'racketmanager' );
        $output = show_alert( $msg, 'danger', 'modal' );
        array_push( $return, $msg, $output );
        wp_send_json_error( $return, '401' );
    }
    /**
     * Ajax Response to get player information
     */
    public function get_player_details(): void {
        $players   = null;
        $validator = new Validator();
        $validator = $validator->check_security_token();
        if ( empty( $validator->error ) ) {
            $type    = isset( $_POST['type'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['type'] ) ) ) :null;
            $name    = isset( $_POST['name'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : null;
            $gender  = empty( $_POST['partnerGender'] ) ? null : sanitize_text_field( wp_unslash( $_POST['partnerGender'] ) );
            $club_id = empty( $_POST['club'] ) ? null : intval( $_POST['club'] );
            $players = $this->get_players_lookup( $type, $name, $gender, $club_id );
        }
        if ( empty( $validator->error ) ) {
            $response = wp_json_encode( $players );
            wp_send_json_success( $response );
        } else {
            wp_send_json_error( $validator->msg, $validator->status );
        }
    }

    /**
     * Get players from lookup
     *
     * @param string $type lookup type.
     * @param string $name lookup details.
     * @param string|null $gender gender.
     * @param int|null $club_id club id.
     *
     * @return array of players.
     */
    private function get_players_lookup( string $type, string $name, ?string $gender, ?int $club_id ): array {
        $results = array();
        if ( 'btm' === $type ) {
            $results = $this->get_player_by_reference( $name );
        } elseif ( 'name' === $type ) {
            $results = $this->get_players_by_name( $name, $gender, $club_id );
        }
        return $this->set_player_results( $results, $type );
    }

    /**
     * Get player by reference
     *
     * @param string $reference
     *
     * @return array
     */
    private function get_player_by_reference( string $reference ): array {
        $results = array();
        $player = $this->player_service->find_player_by_btm( $reference );
        if ( $player ) {
            $result             = new stdClass();
            $result->fullname   = $player->get_fullname();
            $result->user_email = $player->get_email();
            $result->club       = null;
            $result->club_id    = null;
            $result->roster_id  = null;
            $result->player_id  = $player->get_id();
            $results[]          = $result;
        }
        return $results;
    }

    /**
     * Get players by nam
     *
     * @param string $name
     * @param string|null $gender
     * @param int|null $club_id
     *
     * @return array
     */
    private function get_players_by_name( string $name, ?string $gender, ?int $club_id ): array {
        $results = array();
        $player_args         = array();
        $player_args['name'] = $name;
        $players             = $this->player_service->get_all_players( $player_args );
        foreach ( $players as $player ) {
            if ( ! empty( $gender ) && $gender !== $player->get_gender() ) {
                continue;
            }
            $player_clubs = $this->registration_service->get_clubs_for_player( $player->get_id() );
            foreach ( $player_clubs as $player_club ) {
                if ( empty( $club_id ) || $club_id === $player_club->club_id ) {
                    $result             = new stdClass();
                    $result->fullname   = $player->get_fullname();
                    $result->user_email = $player->get_email();
                    $result->player_id  = $player->get_id();
                    $result->btm        = $player->get_btm();
                    $result->contactno  = $player->get_contactno();
                    $result->gender     = $player->get_gender();
                    $result->roster_id  = $player_club->registration_id;
                    if ( empty( $club_id ) ) {
                        $result->club_id = $player_club->club_id;
                        $result->club    = $player_club->club_name;
                    }
                    $results[] = $result;
                }
            }
        }
        return $results;
    }

    /**
     * Return formatted results
     *
     * @param array|null $results result details.
     * @param string $type lookup type.
     *
     * @return array of players.
     */
    private function set_player_results( ?array $results, string $type ): array {
        $players = array();
        if ( empty( $results ) ) {
            return array(
                'label' => __( 'No results found', 'racketmanager' ),
            );
        }
        foreach ( $results as $r ) {
            $player['label'] = $r->fullname;
            if ( ! empty( $r->club ) ) {
                $player['label']  .= ' - ' . $r->club;
                $player['club_id'] = $r->club_id;
                $player['club']    = $r->club;
            }
            $player['name']       = $r->fullname;
            $player['id']         = $r->roster_id;
            $player['playerId']   = $r->player_id;
            $player['user_email'] = $r->user_email;
            $player['contactno']  = $r->contactno;
            $player['btm']        = $r->btm;
            if ( 'btm' === $type ) {
                $player['value'] = $player['btm'];
            } else {
                $player['value'] = $player['name'];
            }
            $players[] = $player;
        }
        return $players;
    }
    /**
     * Check security token
     *
     * @param string $nonce nonce name.
     * @param string $nonce_action nonce action.
     *
     * @return stdClass
     */
    protected function check_security_token( string $nonce = 'security', string $nonce_action = 'ajax-nonce' ): stdClass {
        $return = new stdClass();
        $return->err_msgs = array();
        $return->err_flds = array();
        if ( isset( $_REQUEST[ $nonce ] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST[ $nonce ] ) ), $nonce_action ) ) {
                $return->error  = true;
                $return->msg    = __( 'Sorry, the action could not be completed. The link or form you are using has expired or is invalid. Please try again, or go back to the previous page and try again', 'racketmanager' );
                $return->status = 403;
            }
        } else {
            $return->error  = true;
            $return->msg    = __( 'There was a problem with your request. Please try again or refresh the page', 'racketmanager' );
            $return->status = 403;
        }

        return $return;
    }
}
