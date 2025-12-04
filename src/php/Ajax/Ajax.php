<?php
/**
 * AJAX response methods (PSR-4 relocated)
 *
 * @package    RacketManager
 * @subpackage RacketManager_AJAX
 */

namespace Racketmanager\Ajax;

use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Player_Repository;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Services\Club_Management_Service;
use Racketmanager\Services\Club_Player_Management_Service;
use Racketmanager\Services\Player_Management_Service;
use stdClass;
use function Racketmanager\show_alert;

/**
 * Implement AJAX responses for calls from both frontend and admin.
 *
 * @author Paul Moffat
 */
class Ajax {
    public string $event_not_found;
    protected Club_Management_Service $club_service;
    private Player_Management_Service $player_service;
    protected Club_Player_Management_Service $club_player_service;
    private RacketManager $racketmanager;

    /**
     * Register ajax actions.
     */
    public function __construct( $plugin_instance ) {
        add_action( 'wp_ajax_racketmanager_get_player_details', array( &$this, 'get_player_details' ) );
        $this->racketmanager       = $plugin_instance;
        $club_repository           = new Club_Repository();
        $club_player_repository    = new Club_Player_Repository();
        $club_role_repository      = new Club_Role_Repository();
        $player_repository         = new Player_Repository();
        $this->club_service        = new Club_Management_Service( $club_repository, $club_player_repository, $club_role_repository, $player_repository );
        $this->player_service      = new Player_Management_Service( $this->racketmanager, $player_repository );
        $this->club_player_service = new Club_Player_Management_Service( $this->racketmanager, $club_player_repository, $player_repository, $club_repository, $this->player_service );
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
        $players     = null;
        $return = $this->check_security_token();
        if ( empty( $return->error ) ) {
            $type    = isset( $_POST['type'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['type'] ) ) ) :null;
            $name    = isset( $_POST['name'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['name'] ) ) ) : null;
            $gender  = empty( $_POST['partnerGender'] ) ? null : sanitize_text_field( wp_unslash( $_POST['partnerGender'] ) );
            $club_id = empty( $_POST['club'] ) ? null : intval( $_POST['club'] );
            $players = $this->get_players_lookup( $type, $name, $gender, $club_id );
        }
        if ( empty( $return->error ) ) {
            $response = wp_json_encode( $players );
            wp_send_json_success( $response );
        } else {
            wp_send_json_error( $return->msg, $return->status );
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
            $player = $this->player_service->find_player_by_btm( $name );
            if ( $player ) {
                $result             = new stdClass();
                $result->fullname   = $player->display_name;
                $result->user_email = $player->user_email;
                $result->club       = null;
                $result->club_id    = null;
                $result->roster_id  = null;
                $result->player_id  = $player->ID;
                $results[]          = $result;
            }
        } elseif ( 'name' === $type ) {
            $player_args         = array();
            $player_args['name'] = $name;
            $players             = $this->player_service->get_all_players( $player_args );
            foreach ( $players as $player ) {
                $player_clubs = $player->get_clubs();
                foreach ( $player_clubs as $player_club ) {
                    if ( empty( $club_id ) || $club_id === $player_club->id ) {
                        $result             = new stdClass();
                        $result->fullname   = $player->display_name;
                        $result->user_email = $player->user_email;
                        $result->club_id    = $player_club->id;
                        $result->roster_id  = $player_club->club_player_id;
                        $result->club       = $player_club->shortcode;
                        $result->player_id  = $player->ID;
                        $results[]          = $result;
                    }
                }
            }
        }

        return $this->set_player_results( $results, $type, $gender );
    }

    /**
     * Return formatted results
     *
     * @param array|null $results result details.
     * @param string $type lookup type.
     * @param string|null $gender gender.
     *
     * @return array of players.
     */
    private function set_player_results( ?array $results, string $type, ?string $gender ): array {
        $players = array();
        if ( empty( $results ) ) {
            return array(
                'label' => __( 'No results found', 'racketmanager' ),
            );
        }
        foreach ( $results as $r ) {
            $player['label'] = $r->fullname;
            if ( $r->club ) {
                $player['label'] .= ' - ' . $r->club;
            }
            $player['name']       = $r->fullname;
            $player['id']         = $r->roster_id;
            $player['club_id']    = $r->club_id;
            $player['club']       = $r->club;
            $player['playerId']   = $r->player_id;
            $player['user_email'] = $r->user_email;
            $player['contactno']  = get_user_meta( $r->player_id, 'contactno', true );
            $player['btm']        = get_user_meta( $r->player_id, 'btm', true );
            if ( 'btm' === $type ) {
                $player['value'] = $player['btm'];
            } else {
                $player['value'] = $player['name'];
            }
            if ( $gender ) {
                $player['gender'] = get_user_meta( $r->player_id, 'gender', true );
                if ( $gender !== $player['gender'] ) {
                    continue;
                }
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
