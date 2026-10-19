<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\Message_Presenter;

class Message_Presenter_Test extends TestCase {
    public function test_present_message_formats_dates_and_unread_class(): void {
        $vm = ( new Message_Presenter() )->present_message( (object) [
            'id' => '7', 'status' => '1', 'date' => '2026-03-05 14:07:09', 'subject' => 'Hi',
            'from_name' => 'Admin', 'from_email' => 'a@example.com', 'message_object' => '<p>x</p>', 'userid' => '3',
        ] );

        $this->assertSame( 7, $vm->id );
        $this->assertSame( '05-03-2026 14:07:09', $vm->date_formatted );
        $this->assertSame( 'Mar', $vm->date_month );
        $this->assertSame( '5', $vm->date_day );
        $this->assertSame( '2026', $vm->date_year );
        $this->assertSame( 'unread', $vm->status_class );
        $this->assertSame( 3, $vm->userid );
    }

    public function test_present_message_read_status_and_missing_date_guard(): void {
        $vm = ( new Message_Presenter() )->present_message( (object) [ 'id' => 1, 'status' => '0', 'subject' => 'S' ] );

        $this->assertSame( 'read', $vm->status_class );
        $this->assertSame( '', $vm->date_formatted );
        $this->assertSame( '', $vm->date_month );
        $this->assertNull( $vm->from_name );
    }

    public function test_present_messages_computes_read_count_and_details(): void {
        $list = ( new Message_Presenter() )->present_messages( [
            'total'  => 5,
            'unread' => 2,
            'detail' => [ (object) [ 'id' => 1, 'status' => '1' ], (object) [ 'id' => 2, 'status' => '0' ] ],
        ] );

        $this->assertSame( 5, $list->total );
        $this->assertSame( 2, $list->unread );
        $this->assertSame( 3, $list->read );
        $this->assertCount( 2, $list );
        $this->assertSame( 'unread', $list->detail[0]->status_class );
    }

    public function test_present_messages_empty(): void {
        $list = ( new Message_Presenter() )->present_messages( [] );

        $this->assertSame( 0, $list->total );
        $this->assertSame( 0, $list->read );
        $this->assertSame( [], $list->detail );
    }
}
