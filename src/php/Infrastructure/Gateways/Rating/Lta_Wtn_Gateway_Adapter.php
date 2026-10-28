<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Gateways\Rating;

use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Exceptions\LTA_System_Not_Available_Exception;
use Racketmanager\Exceptions\WTN_Error_Exception;
use Racketmanager\Util\Util_Lookup;

/**
 * Concrete secondary adapter for the LTA World Tennis Number (WTN) external rating gateway.
 */
class Lta_Wtn_Gateway_Adapter implements Rating_Gateway_Interface {
    protected array $args = array();

    /**
     * {@inheritDoc}
     */
    public function prepare_env(): void {
        if ( empty( $this->args ) ) {
            $this->args    = array();
            $url           = 'https://competitions.lta.org.uk/cookiewall/';
            $player_lookup = wp_remote_get( $url, $this->args );
            $new_cookies   = array();
            $code          = wp_remote_retrieve_response_code( $player_lookup );
            if ( 200 === $code ) {
                $cookies = wp_remote_retrieve_cookies( $player_lookup );
                foreach ( $cookies as $cookie ) {
                    if ( 'st' === $cookie->name ) {
                        $cookie->value .= '&c=1&cp=31';
                    }
                    $new_cookies[] = $cookie;
                }
                $this->args = array(
                    'cookies' => $new_cookies,
                    'timeout' => 5,
                );
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
            $btm            = isset( $player->btm ) ? (string) $player->btm : '';
            $wtn            = $this->get_player_wtn( $btm );
            $resp['value']  = $wtn;
            $resp['status'] = true;
            return $resp;
        } catch ( WTN_Error_Exception $e ) {
            return array( 'status' => false, 'message' => $e->getMessage() );
        }
    }

    /**
     * Get WTN for player from the LTA system.
     *
     * @param string $btm - LTA tennis number.
     * @return array<string, string>
     */
    protected function get_player_wtn( string $btm ): array {
        $search_body  = $this->fetch_search_page( $btm );
        $player_url   = $this->extract_player_url( $search_body );
        $profile_body = $this->fetch_player_page( $player_url );
        $wtn_block    = $this->extract_wtn_block( $profile_body );
        $wtn          = $this->parse_wtn_entries( $wtn_block );
        if ( empty( $wtn ) ) {
            throw new WTN_Error_Exception( __( 'WTN values not set', 'racketmanager' ) );
        }
        return $wtn;
    }

    /**
     * Perform a GET request and return the response body.
     *
     * @param string $url        URL to fetch.
     * @param string $error_msg  Message to throw when the request fails.
     * @param string $status_msg Message to throw when the response is not 200.
     * @return string
     * @throws WTN_Error_Exception When the request fails or returns a non-200 status.
     */
    private function fetch_page( string $url, string $error_msg, string $status_msg ): string {
        $response = wp_remote_get( $url, $this->args );
        if ( is_wp_error( $response ) ) {
            throw new WTN_Error_Exception( $error_msg );
        }
        $code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $code ) {
            throw new WTN_Error_Exception( $status_msg );
        }
        return wp_remote_retrieve_body( $response );
    }

    /**
     * Fetch the LTA player search page for a tennis number.
     *
     * @param string $btm LTA tennis number.
     * @return string
     * @throws WTN_Error_Exception When the search fails.
     */
    private function fetch_search_page( string $btm ): string {
        $url = 'https://competitions.lta.org.uk/find/player/DoSearch?Query=' . rawurlencode( $btm );
        return $this->fetch_page(
            $url,
            'ERROR: - ' . __( 'search error', 'racketmanager' ),
            __( 'LTA number not found', 'racketmanager' )
        );
    }

    /**
     * Fetch the LTA player profile page.
     *
     * @param string $player_url Absolute player profile URL.
     * @return string
     * @throws WTN_Error_Exception When the profile page cannot be retrieved.
     */
    private function fetch_player_page( string $player_url ): string {
        return $this->fetch_page(
            $player_url,
            __( 'View error', 'racketmanager' ),
            __( 'Unable to retrieve player page', 'racketmanager' )
        );
    }

    /**
     * Return the text strictly between two markers, searching from an offset.
     *
     * @param string $haystack     Text to search.
     * @param string $start_marker Marker preceding the wanted text.
     * @param string $end_marker   Marker following the wanted text.
     * @param int    $offset       Position to start searching from.
     * @return string|null Null when either marker is missing or the end precedes the start.
     */
    private function extract_between( string $haystack, string $start_marker, string $end_marker, int $offset = 0 ): ?string {
        $start = strpos( $haystack, $start_marker, $offset );
        if ( false === $start ) {
            return null;
        }
        $start += strlen( $start_marker );
        $end    = strpos( $haystack, $end_marker, $start );
        if ( false === $end || $end <= $start ) {
            return null;
        }
        return substr( $haystack, $start, $end - $start );
    }

    /**
     * Extract the absolute player profile URL from the search page body.
     *
     * @param string $body Search page HTML.
     * @return string
     * @throws WTN_Error_Exception When the player or the player link cannot be found.
     */
    private function extract_player_url( string $body ): string {
        $player_link_start = strpos( $body, '<h5 class="media__title">' );
        if ( false === $player_link_start ) {
            throw new WTN_Error_Exception( __( 'Player not found', 'racketmanager' ) );
        }
        $player_link = $this->extract_between( $body, '<h5 class="media__title">', '</a>', $player_link_start );
        $player_url  = null === $player_link ? null : $this->extract_between( $player_link, '<a href="', '"' );
        if ( null === $player_url ) {
            throw new WTN_Error_Exception( __( 'Player link not found', 'racketmanager' ) );
        }
        return 'https://competitions.lta.org.uk' . $player_url;
    }

    /**
     * Extract the WTN list block from the player profile page body.
     *
     * @param string $body Profile page HTML.
     * @return string The <ul>...</ul> block containing the WTN entries.
     * @throws WTN_Error_Exception When the WTN text, list or entries cannot be found.
     */
    private function extract_wtn_block( string $body ): string {
        if ( ! str_contains( $body, 'World Tennis Number' ) ) {
            throw new WTN_Error_Exception( __( 'WTN not found', 'racketmanager' ) );
        }
        $wtn_block_start = strpos( $body, '<ul class="list--inline list">' );
        $wtn_block_end   = false === $wtn_block_start ? false : strpos( $body, '</ul>', $wtn_block_start );
        if ( false === $wtn_block_end || $wtn_block_end <= $wtn_block_start ) {
            throw new WTN_Error_Exception( __( 'No WTN list found', 'racketmanager' ) );
        }
        $wtn_block = substr( $body, $wtn_block_start, $wtn_block_end - $wtn_block_start + 5 );
        if ( ! substr_count( $wtn_block, '<li class="list__item">' ) ) {
            throw new WTN_Error_Exception( __( 'No WTN entries found', 'racketmanager' ) );
        }
        return $wtn_block;
    }

    /**
     * Parse all WTN entries from the list block.
     *
     * @param string $wtn_block The <ul>...</ul> block containing the WTN entries.
     * @return array<string, string>
     */
    private function parse_wtn_entries( string $wtn_block ): array {
        $wtn    = array();
        $offset = 0;
        $entry  = $this->parse_wtn_entry( $wtn_block, $offset );
        while ( null !== $entry ) {
            if ( $entry['key'] ) {
                $wtn[ $entry['key'] ] = $entry['value'];
            }
            $offset = $entry['next'];
            $entry  = $this->parse_wtn_entry( $wtn_block, $offset );
        }
        return $wtn;
    }

    /**
     * Parse a single WTN entry starting from an offset.
     *
     * @param string $wtn_block The <ul>...</ul> block containing the WTN entries.
     * @param int    $offset    Position to start searching from.
     * @return array{key: mixed, value: string, next: int}|null Null when no further well-formed entry exists.
     */
    private function parse_wtn_entry( string $wtn_block, int $offset ): ?array {
        $type_start = strpos( $wtn_block, 'tag-duo__title', $offset );
        $type       = false === $type_start ? null : $this->extract_between( $wtn_block, '">', '</span>', $type_start );
        $wtn_value  = null === $type ? null : $this->extract_between( $wtn_block, '</svg>', '</span>', $type_start );
        if ( null === $wtn_value ) {
            return null;
        }
        return array(
            'key'   => Util_Lookup::get_match_type_key( $type ),
            'value' => trim( $wtn_value ),
            'next'  => (int) strpos( $wtn_block, '</span>', $type_start ),
        );
    }
}
