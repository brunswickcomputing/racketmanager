<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\Queries;

use Racketmanager\Application\Message\DTOs\Message_Detail_Result;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use function __;
use function is_object;

class Get_Message_Handler {
    public function __construct(
        private readonly Message_Repository_Interface $message_repository
    ) {}

    public function handle( Get_Message_Query $query ): Message_Detail_Result {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Message_Detail_Result::error( __( 'You must be logged in to view a message', 'racketmanager' ) );
        }

        if ( ! $query->message_id ) {
            return Message_Detail_Result::error( __( 'Message not found', 'racketmanager' ) );
        }

        $message = $this->message_repository->find( $query->message_id );

        if ( ! $message ) {
            return Message_Detail_Result::error( __( 'Message not found', 'racketmanager' ) );
        }

        if ( ! is_object( $message ) || (int) $message->userid !== (int) $query->user_id ) {
            return Message_Detail_Result::error( __( 'You are not authorised to view this message', 'racketmanager' ) );
        }

        if ( '1' === (string) ( $message->status ?? '' ) ) {
            $message->set_status( '0' );
            $this->message_repository->save( $message );
        }

        return new Message_Detail_Result(
            message: $message,
            template: $query->template
        );
    }
}
