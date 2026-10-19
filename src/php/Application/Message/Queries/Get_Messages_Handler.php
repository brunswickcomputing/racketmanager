<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\Queries;

use Racketmanager\Presentation\Presenters\Message_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use function __;

class Get_Messages_Handler {
    public function __construct(
        private readonly User_Repository_Interface $user_repository,
        private readonly ?Message_Presenter $message_presenter = null
    ) {}

    public function handle( Get_Messages_Query $query ): Shortcode_View_Model {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Shortcode_View_Model::for_error( __( 'You must be logged in to view a message', 'racketmanager' ) );
        }

        $user = $this->user_repository->find( $query->user_id );

        $messages = [];
        if ( $user ) {
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

        $presenter   = $this->message_presenter ?? new Message_Presenter();
        $messages_vm = $presenter->present_messages( $messages );

        $filename = ! empty( $query->template ) ? 'messages-' . $query->template : 'messages';

        return Shortcode_View_Model::for_template( $filename, [
            'messages' => $messages_vm,
        ], 'account' );
    }
}
