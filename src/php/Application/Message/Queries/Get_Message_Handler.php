<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use function Racketmanager\get_message;
use function __;

class Get_Message_Handler {
    public function handle( Get_Message_Query $query ): Shortcode_View_Model {
        if ( ! $query->is_logged_in || null === $query->user_id ) {
            return Shortcode_View_Model::for_error(
                __( 'You must be logged in to view a message', 'racketmanager' )
            );
        }

        if ( null === $query->message_id || $query->message_id <= 0 ) {
            return Shortcode_View_Model::for_error(
                __( 'Message not found', 'racketmanager' )
            );
        }

        $message = function_exists( '\Racketmanager\get_message' )
            ? get_message( $query->message_id )
            : null;

        if ( ! $message ) {
            return Shortcode_View_Model::for_error(
                __( 'Message not found', 'racketmanager' )
            );
        }

        $message_userid = isset( $message->userid ) ? (int) $message->userid : null;
        if ( null === $message_userid || $message_userid !== $query->user_id ) {
            return Shortcode_View_Model::for_error(
                __( 'You are not authorised to view this message', 'racketmanager' )
            );
        }

        if ( '1' === (string) ( $message->status ?? '' ) && method_exists( $message, 'set_status' ) ) {
            $message->set_status( '0' );
        }

        $filename = '' !== $query->template ? 'message-' . $query->template : 'message';

        return Shortcode_View_Model::for_template(
            $filename,
            [ 'message_dtl' => $message ],
            'account'
        );
    }
}
