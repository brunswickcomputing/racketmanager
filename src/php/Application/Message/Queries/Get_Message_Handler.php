<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Message\Queries;

use Racketmanager\Presentation\Presenters\Message_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use function __;
use function is_object;

class Get_Message_Handler {
    public function __construct(
        private readonly Message_Repository_Interface $message_repository,
        private readonly ?Message_Presenter $message_presenter = null
    ) {}

    public function handle( Get_Message_Query $query ): Shortcode_View_Model {
        if ( ! $query->is_logged_in || ! $query->user_id ) {
            return Shortcode_View_Model::for_error( __( 'You must be logged in to view a message', 'racketmanager' ) );
        }

        if ( ! $query->message_id ) {
            return Shortcode_View_Model::for_error( __( 'Message not found', 'racketmanager' ) );
        }

        $message = $this->message_repository->find( $query->message_id );

        if ( ! $message ) {
            return Shortcode_View_Model::for_error( __( 'Message not found', 'racketmanager' ) );
        }

        if ( ! is_object( $message ) || (int) $message->userid !== (int) $query->user_id ) {
            return Shortcode_View_Model::for_error( __( 'You are not authorised to view this message', 'racketmanager' ) );
        }

        if ( '1' === (string) ( $message->status ?? '' ) ) {
            $message->set_status( '0' );
        }

        $presenter = $this->message_presenter ?? new Message_Presenter();
        $vm_message = $presenter->present_message( $message );

        $filename = ! empty( $query->template ) ? 'message-' . $query->template : 'message';

        return Shortcode_View_Model::for_template( $filename, [
            'message_dtl' => $vm_message,
        ], 'account' );
    }
}
