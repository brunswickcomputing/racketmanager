<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Communication;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Communication\Message;
use Racketmanager\Domain\Communication\Message_Factory;
use Racketmanager\Domain\DTO\Communication\Message_Hydration_DTO;
use TypeError;

final class Message_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_fields_and_derives_from_name_and_email(): void {
        $message = Message_Factory::create_from_dto( new Message_Hydration_DTO( id: 10, subject: 'S', userid: 25, status: '1', date: '2026-10-20 12:00:00', sender: '"John Doe" <john.doe@example.com>', message_object: 'Body' ) );

        $this->assertInstanceOf( Message::class, $message );
        $this->assertSame( 10, $message->id );
        $this->assertSame( 25, $message->userid );
        $this->assertSame( '1', $message->status );
        $this->assertSame( 'John Doe', $message->from_name );
        $this->assertSame( 'john.doe@example.com', $message->from_email );
        $this->assertSame( 'Body', $message->message_object );
    }

    public function test_from_object_casts_string_db_row_and_parses_bracket_only_sender(): void {
        $message = Message_Factory::from_object( (object) [ 'id' => '3', 'userid' => '', 'status' => 1, 'sender' => '<a@b.c>' ] );

        $this->assertSame( 3, $message->id );
        $this->assertSame( 0, $message->userid );
        $this->assertSame( '1', $message->status );
        $this->assertSame( '', $message->from_name );
        $this->assertSame( 'a@b.c', $message->from_email );
    }

    public function test_plain_sender_leaves_derived_fields_empty_and_missing_sender_skips_parsing(): void {
        $plain = Message_Factory::from_object( (object) [ 'id' => 1, 'sender' => 'plain' ] );
        $this->assertSame( 'plain', $plain->sender );
        $this->assertSame( '', $plain->from_name );
        $this->assertSame( '', $plain->from_email );

        $none = Message_Factory::from_object( (object) [ 'id' => 1 ] );
        $this->assertFalse( isset( $none->sender ) );
        $this->assertSame( '', $none->from_email );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'id' => '3', 'subject' => 'S', 'sender' => 'A <a@b.c>' ];

        $this->assertEquals( Message_Factory::from_object( (object) $data ), Message_Factory::from_array( $data ) );
        $this->assertEquals( Message_Factory::from_object( (object) $data ), Message::from_object( (object) $data ) );
        $this->assertEquals( Message::from_object( (object) $data ), Message::from_array( $data ) );
    }

    public function test_empty_constructor_yields_empty_message_and_raw_object_is_rejected(): void {
        $empty = new Message();
        $this->assertFalse( isset( $empty->id ) );
        $this->assertSame( '', $empty->from_name );

        $this->expectException( TypeError::class );
        $this->assertInstanceOf(
            Message::class,
            new Message( (object) [ 'id' => 1 ] )
        );
    }
}
