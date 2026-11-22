<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Communication;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Communication\Message;

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

        $message = Message::from_object( $data );

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
        $message->set_status( '1' );
        $this->assertSame( '1', $message->status );

        $message->set_status( '0' );
        $this->assertSame( '0', $message->status );
    }

    public function test_getters_and_setters(): void {
        $message = new Message();
        $message->set_id( 42 );
        $message->set_subject( 'Match Report' );
        $message->set_user_id( 123 );
        $message->set_date( '2026-11-22 20:00:00' );
        $message->set_message_object( 'Payload content' );

        $this->assertSame( 42, $message->get_id() );
        $this->assertSame( 'Match Report', $message->get_subject() );
        $this->assertSame( 123, $message->get_user_id() );
        $this->assertSame( 123, $message->get_userid() );
        $this->assertSame( '2026-11-22 20:00:00', $message->get_date() );
        $this->assertSame( 'Payload content', $message->get_message_object() );

        $message->set_userid( 456 );
        $this->assertSame( 456, $message->get_user_id() );

        $message->set_from_name( 'Custom Name' );
        $message->set_from_email( 'custom@example.com' );
        $this->assertSame( 'Custom Name', $message->get_from_name() );
        $this->assertSame( 'custom@example.com', $message->get_from_email() );
    }

    public function test_status_helpers(): void {
        $message = new Message();
        $message->mark_as_unread();

        $this->assertSame( '1', $message->get_status() );
        $this->assertTrue( $message->is_unread() );
        $this->assertFalse( $message->is_read() );

        $message->mark_as_read();
        $this->assertSame( '0', $message->get_status() );
        $this->assertTrue( $message->is_read() );
        $this->assertFalse( $message->is_unread() );
    }

    public function test_set_sender_parses_sender_details(): void {
        $message = new Message();
        $message->set_sender( 'Tennis Club <club@example.com>' );

        $this->assertSame( 'Tennis Club <club@example.com>', $message->get_sender() );
        $this->assertSame( 'Tennis Club', $message->get_from_name() );
        $this->assertSame( 'club@example.com', $message->get_from_email() );

        $message->set_sender( 'plain_address@example.com' );
        $this->assertSame( 'plain_address@example.com', $message->get_sender() );
        $this->assertSame( '', $message->get_from_name() );
        $this->assertSame( '', $message->get_from_email() );
    }

    public function test_is_owned_by(): void {
        $message = Message::from_array(
            array(
                'id'     => 1,
                'userid' => 99,
            )
        );

        $this->assertTrue( $message->is_owned_by( 99 ) );
        $this->assertFalse( $message->is_owned_by( 100 ) );
    }
}
