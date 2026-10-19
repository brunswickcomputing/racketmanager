<?php
/**
 * Message Presenter
 *
 * @package RacketManager
 * @subpackage Presentation/Presenters
 */

declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\Message;
use Racketmanager\Presentation\View_Models\Message_View_Model;
use Racketmanager\Presentation\View_Models\Messages_List_View_Model;
use function mysql2date;

class Message_Presenter {
    public function present_message( object $message ): Message_View_Model {
        $raw_date       = $message->date ?? null;
        $date_formatted = ! empty( $raw_date ) ? (string) mysql2date( 'd-m-Y G:i:s', $raw_date ) : '';
        $date_month     = ! empty( $raw_date ) ? (string) mysql2date( 'M', $raw_date ) : '';
        $date_day       = ! empty( $raw_date ) ? (string) mysql2date( 'j', $raw_date ) : '';
        $date_year      = ! empty( $raw_date ) ? (string) mysql2date( 'Y', $raw_date ) : '';
        $status         = isset( $message->status ) ? (string) $message->status : '0';
        $status_class   = '1' === $status ? 'unread' : 'read';

        return new Message_View_Model(
            id: (int) ( $message->id ?? 0 ),
            from_name: ! empty( $message->from_name ) ? (string) $message->from_name : null,
            from_email: ! empty( $message->from_email ) ? (string) $message->from_email : null,
            subject: (string) ( $message->subject ?? '' ),
            date_formatted: $date_formatted,
            message_object: (string) ( $message->message_object ?? '' ),
            status: $status,
            status_class: $status_class,
            date_month: $date_month,
            date_day: $date_day,
            date_year: $date_year,
            date: $raw_date,
            userid: isset( $message->userid ) ? (int) $message->userid : null,
        );
    }

    /**
     * @param array $messages_data
     * @return Messages_List_View_Model
     */
    public function present_messages( array $messages_data ): Messages_List_View_Model {
        $total  = (int) ( $messages_data['total'] ?? 0 );
        $unread = (int) ( $messages_data['unread'] ?? 0 );
        $read   = $total - $unread;
        $detail = [];
        if ( ! empty( $messages_data['detail'] ) && is_array( $messages_data['detail'] ) ) {
            foreach ( $messages_data['detail'] as $msg ) {
                $detail[] = $this->present_message( $msg );
            }
        }

        return new Messages_List_View_Model(
            total: $total,
            unread: $unread,
            read: $read,
            detail: $detail,
        );
    }
}
