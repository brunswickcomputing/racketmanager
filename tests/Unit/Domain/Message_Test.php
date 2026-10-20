<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Message;

class Message_Test extends TestCase {
    public function test_instantiation_without_data(): void {
        $message = new Message();
        $this->assertInstanceOf( Message::class, $message );
        $this->assertSame( '', $message->from_name );
        $this->assertSame( '', $message->from_email );
    }

    public function test_instantiation_with_data_and_email_parsing(): void {
        $data = (object) [
            'id'             => 10,
            'userid'         => 25,
            'subject'        => 'Test Message Subject',
            'status'         => '1',
            'date'           => '2026-10-20 12:00:00',
            'sender'         => '"John Doe" <john.doe@example.com>',
            'message_object' => 'Serialized message content',
        ];

        $message = new Message( $data );

        $this->assertSame( 10, $message->id );
        $this->assertSame( 25, $message->userid );
        $this->assertSame( 'Test Message Subject', $message->subject );
        $this->assertSame( '1', $message->status );
        $this->assertSame( '2026-10-20 12:00:00', $message->date );
        $this->assertSame( '"John Doe" <john.doe@example.com>', $message->sender );
        $this->assertSame( 'John Doe', $message->from_name );
        $this->assertSame( 'john.doe@example.com', $message->from_email );
        $this->assertSame( 'Serialized message content', $message->message_object );
    }

    public function test_set_status_updates_property_in_memory(): void {
        $message = new Message();
        $message->status = '1';
        $message->set_status( '0' );

        $this->assertSame( '0', $message->status );
    }
}
