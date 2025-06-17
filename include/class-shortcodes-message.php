<?php
/**
 * Shortcodes_Message API: Shortcodes_Message class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes
 */

namespace Racketmanager;

/**
 * Class to implement the Shortcodes_Message object
 */
class Shortcodes_Message extends Shortcodes {
    /**
     * Initialize shortcodes
     */
    public function __construct() {
        parent::__construct();
    }
    /**
     * Function to show messages
     *
     *    [messages template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_messages( array $atts ): string {
        $args = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        if ( ! is_user_logged_in() ) {
            return $this->return_error( __( 'You must be logged in to view a message', 'racketmanager' ) );
        }
        $template       = $args['template'];
        $user           = get_user( get_current_user_id() );
        $messages_total = $user->get_messages( array( 'count' => true ) );
        if ( $messages_total ) {
            $messages['total']  = $messages_total;
            $messages['detail'] = $user->get_messages( array() );
            $messages['unread'] = $user->get_messages(
                array(
                    'count'  => true,
                    'status' => 'unread',
                )
            );
        }
        $filename = ( ! empty( $template ) ) ? 'messages-' . $template : 'messages';

        return $this->load_template( $filename, array( 'messages' => $messages ), 'account' );
    }
    /**
     * Function to show message
     *
     *    [show-message template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_message( array $atts ): string {
        $args       = shortcode_atts(
            array(
                'id'       => null,
                'template' => '',
            ),
            $atts
        );
        $message_id = $args['id'];
        $template   = $args['template'];
        $msg        = null;
        if ( ! is_user_logged_in() ) {
            $msg = __( 'You must be logged in to view a message', 'racketmanager' );
        } else {
            if ( ! $message_id ) {
                $msg = __( 'No message id found in request', 'racketmanager' );
            } else {
                $message = get_message( $message_id );
                if ( $message ) {
                    if ( '1' === $message->status ) {
                        $status = '0';
                        $message->set_status( $status );
                    }
                    $filename = ( ! empty( $template ) ) ? 'message-' . $template : 'message';
                    return $this->load_template( $filename, array( 'message_dtl' => $message ), 'account' );
                } else {
                    $msg = __( 'Message not found', 'racketmanager' );
                }
            }
        }
        return $this->return_error( $msg );
    }
}
