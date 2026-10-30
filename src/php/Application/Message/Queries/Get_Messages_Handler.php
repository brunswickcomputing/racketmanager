<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\Queries;

use Racketmanager\Application\Message\DTOs\Messages_List_Result;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use function __;

class Get_Messages_Handler {
    public function __construct(
        private readonly User_Repository_Interface $user_repository,
        private readonly Message_Repository_Interface $message_repository
    ) {}

    public function handle( Get_Messages_Query $query ): Messages_List_Result {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Messages_List_Result::error( __( 'You must be logged in to view a message', 'racketmanager' ) );
        }

        $user = $this->user_repository->find( $query->user_id );

        $messages = [];
        if ( $user ) {
            $user_id = (int) $user->id;
            $messages_total = $this->message_repository->find_by_user( $user_id, [ 'count' => true ] );
            if ( $messages_total ) {
                $messages['total']  = $messages_total;
                $messages['detail'] = $this->message_repository->find_by_user( $user_id, [] );
                $messages['unread'] = $this->message_repository->find_by_user( $user_id, [
                    'count'  => true,
                    'status' => 'unread',
                ] );
            }
        }

        return new Messages_List_Result(
            messages: $messages,
            template: $query->template
        );
    }
}
