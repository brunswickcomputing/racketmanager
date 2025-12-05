<?php
/**
 * External Services: Default WTN API client implementation
 *
 * Architectural placement:
 * - External API communication classes live under Services/External
 * - They implement contracts from Services/Contracts and are injected into
 *   domain services (e.g. Player__Service) as needed.
 */

namespace Racketmanager\Services\External;

use Racketmanager\Exceptions\LTA_System_Not_Available_Exception;
use Racketmanager\Exceptions\WTN_Error_Exception;
use Racketmanager\Services\Contracts\Wtn_Api_Client_Interface;
use Racketmanager\Util\Util_Lookup;

class Wtn_Api_Client implements Wtn_Api_Client_Interface {
    private array $args;

    /**
     * {@inheritDoc}
     */
    public function prepare_env(): void {
        if ( empty( $this->args ) ) {
            $this->args          = array();
            $url           = 'https://competitions.lta.org.uk/cookiewall/';
            $player_lookup = wp_remote_get( $url, $this->args );
            $new_cookies   = array();
            $code          = wp_remote_retrieve_response_code( $player_lookup );
            if ( 200 === $code ) {
                $cookies = wp_remote_retrieve_cookies( $player_lookup );
                foreach( $cookies as $cookie ) {
                    if ( 'st' === $cookie->name ) {
                        $cookie->value .= "&c=1&cp=31";
                    }
                    $new_cookies[] = $cookie;
                }
                $this->args = [
                    'cookies'   => $new_cookies,
                    'timeout'   => 5,
                ];
            }
        }
       if ( empty( $this->args ) ) {
            throw new LTA_System_Not_Available_Exception( __( 'Unable to access LTA system', 'racketmanager' ) );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function fetch_player_wtn( object $player ): array {
        try {
            $wtn            = $this->get_player_wtn( $player->btm );
            $resp['value']  = $wtn;
            $resp['status'] = true;
            return $resp;
        } catch ( WTN_Error_Exception $e ) {
            return array( 'status' => false, 'message' => $e->getMessage() );
        }
    }

    /**
     * Get WTN for player
     *
     * @param $btm - lta tennis number.
     *
     * @return array {wtn-singles:wtn,wtn-singles:wtn}
     */
    private function get_player_wtn( $btm ): array {
        $wtn           = array();
        $url           = 'https://competitions.lta.org.uk/find/player/DoSearch?Query=' . $btm;
        $player_lookup = wp_remote_get( $url, $this->args );
        if ( is_wp_error( $player_lookup ) ) {
            throw new WTN_Error_Exception( 'ERROR: - ' . __( 'search error', 'racketmanager' ) );
        }
        $code = wp_remote_retrieve_response_code( $player_lookup );
        if ( 200 !== $code ) {
            throw new WTN_Error_Exception( __( 'LTA number not found', 'racketmanager' ) );
        }
        $body              = wp_remote_retrieve_body( $player_lookup );
        $player_link_start = strpos( $body, '<h5 class="media__title">' );
        if ( ! $player_link_start ) {
            throw new WTN_Error_Exception( __( 'Player not found', 'racketmanager' ) );
        }
        $player_link_end  = strpos( $body, '</a>', $player_link_start );
        $player_link      = substr( $body, $player_link_start, $player_link_end - $player_link_start );
        $player_url_start = strpos( $player_link, '<a href="' );
        if ( ! $player_url_start ) {
            throw new WTN_Error_Exception( __( 'Player link not found', 'racketmanager' ) );
        }
        $player_url_start += 9;
        $player_url_end    = strpos( $player_link, '"', $player_url_start );
        $player_url_len    = $player_url_end - $player_url_start;
        $player_url        = substr( $player_link, $player_url_start, $player_url_len );
        $player_url        = 'https://competitions.lta.org.uk' . $player_url;
        $player_detail     = wp_remote_get( $player_url, $this->args );
        if ( is_wp_error( $player_detail ) ) {
            throw new WTN_Error_Exception( __( 'View error', 'racketmanager' ) );
        }
        $code = wp_remote_retrieve_response_code( $player_detail );
        if ( 200 !== $code ) {
            throw new WTN_Error_Exception( __( 'Unable to retrieve player page', 'racketmanager' ) );
        }
        $body           = wp_remote_retrieve_body( $player_detail );
        $wtn_text_found = strpos( $body, 'World Tennis Number' );
        if ( ! $wtn_text_found ) {
            throw new WTN_Error_Exception( __( 'WTN not found', 'racketmanager' ) );
        }
        $wtn_block_start = strpos( $body, '<ul class="list--inline list">' );
        if ( ! $wtn_block_start ) {
            throw new WTN_Error_Exception( __( 'No WTN list found', 'racketmanager' ) );
        }
        $wtn_block_end   = strpos( $body, '</ul>', $wtn_block_start  );
        $wtn_block_len   = $wtn_block_end - $wtn_block_start + 5;
        $wtn_block       = substr( $body, $wtn_block_start, $wtn_block_len );
        $num_wtns        = substr_count( $wtn_block, '<li class="list__item">' );
        if ( ! $num_wtns ) {
            throw new WTN_Error_Exception( __( 'No WTN entries found', 'racketmanager' ) );
        }
        $start = 0;
        for ( $i = 1; $i <= $num_wtns; $i++ ) {
            $type_start  = strpos( $wtn_block, 'tag-duo__title', $start );
            $type_end    = strpos( $wtn_block, '</span>', $type_start );
            $type_start += 16;
            $type_len    = $type_end - $type_start;
            $type        = substr( $wtn_block, $type_start, $type_len );
            $wtn_key     = Util_Lookup::get_match_type_key( $type );
            $wtn_start   = strpos( $wtn_block, '</svg>', $type_start );
            $wtn_end     = strpos( $wtn_block, '</span>', $wtn_start );
            $wtn_start  += 6;
            $wtn_len     = $wtn_end - $wtn_start;
            $wtn_value   = substr( $wtn_block, $wtn_start, $wtn_len );
            $start       = $type_end;
            if ( $wtn_key ) {
                $wtn[ $wtn_key ] = trim( $wtn_value );
            }
        }
        if ( empty( $wtn ) ) {
            throw new WTN_Error_Exception( __( 'WTN values not set', 'racketmanager' ) );
        }
        return $wtn;
    }
}
