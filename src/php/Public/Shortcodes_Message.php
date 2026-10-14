<?php
declare( strict_types=1 );

/**
 * Shortcodes_Message API: Shortcodes_Message class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Message
 */

namespace Racketmanager\Public;

use Racketmanager\Infrastructure\Wordpress\Shortcodes\Message_Shortcode_Adapter;

/**
 * Backward-compatible facade for Message shortcodes.
 *
 * All shortcode logic is implemented in Message_Shortcode_Adapter and its Query Handlers.
 * This class preserves legacy public signatures and delegates to the container adapter.
 *
 * @deprecated Use Message_Shortcode_Adapter.
 */
class Shortcodes_Message extends Shortcodes {
    /**
     * Show messages
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_messages( array $atts = [] ): string {
        return $this->adapter()->show_messages( $atts );
    }

    /**
     * Show message
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_message( array $atts = [] ): string {
        return $this->adapter()->show_message( $atts );
    }

    private function adapter(): Message_Shortcode_Adapter {
        return $this->racketmanager->container->get( 'message_shortcode_adapter' );
    }
}
