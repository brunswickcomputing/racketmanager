<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use function Racketmanager\get_user;
use function __;

class Get_Messages_Handler {
    public function handle( Get_Messages_Query $query ): Shortcode_View_Model {
        if ( ! $query->is_logged_in || null === $query->user_id ) {
            return Shortcode_View_Model::for_error(
                __( 'You must be logged in to view a message', 'racketmanager' )
            );
        }

        $user = function_exists( '\Racketmanager\get_user' )
            ? get_user( $query->user_id )
            : null;

        $messages = [];
        if ( $user && method_exists( $user, 'get_messages' ) ) {
            $messages_total = $user->get_messages( [ 'count' => true ] );
            if ( $messages_total ) {
                $messages['total']  = $messages_total;
                $messages['detail'] = $user->get_messages( [] );
                $messages['unread'] = $user->get_messages( [
                    'count'  => true,
                    'status' => 'unread',
                ] );
            }
        }

        $filename = '' !== $query->template ? 'messages-' . $query->template : 'messages';

        return Shortcode_View_Model::for_template(
            $filename,
            [ 'messages' => $messages ],
            'account'
        );
    }
}
