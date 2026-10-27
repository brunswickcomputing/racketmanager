<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Communication;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Communication\Message_Hydration_DTO;

final class Message_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_to_declared_types(): void {
        $dto = Message_Hydration_DTO::from_object( (object) [
            'id'             => '10',
            'userid'         => '25',
            'subject'        => 'Subject',
            'status'         => 1,
            'date'           => '2026-10-20 12:00:00',
            'sender'         => '"John Doe" <john.doe@example.com>',
            'message_object' => 'Body',
        ] );

        $this->assertSame( 10, $dto->id );
        $this->assertSame( 25, $dto->userid );
        $this->assertSame( 'Subject', $dto->subject );
        $this->assertSame( '1', $dto->status );
        $this->assertSame( '2026-10-20 12:00:00', $dto->date );
        $this->assertSame( '"John Doe" <john.doe@example.com>', $dto->sender );
        $this->assertSame( 'Body', $dto->message_object );
    }

    public function test_empty_integer_columns_become_zero_and_missing_or_null_columns_stay_null(): void {
        $dto = Message_Hydration_DTO::from_object( (object) [ 'id' => '', 'userid' => null, 'status' => '' ] );

        $this->assertSame( 0, $dto->id );
        $this->assertNull( $dto->userid );
        $this->assertSame( '', $dto->status );
        $this->assertNull( $dto->subject );
        $this->assertNull( $dto->sender );
    }

    public function test_unknown_keys_and_derived_fields_are_dropped(): void {
        $dto = Message_Hydration_DTO::from_object( (object) [ 'id' => 1, 'from_name' => 'x', 'from_email' => 'y', 'foo' => 'bar' ] );

        $this->assertSame( [ 'id', 'subject', 'userid', 'status', 'date', 'sender', 'message_object' ], array_keys( get_object_vars( $dto ) ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => '3', 'subject' => 'S', 'sender' => 'a <b@c.d>' ];

        $this->assertEquals( Message_Hydration_DTO::from_object( (object) $data ), Message_Hydration_DTO::from_array( $data ) );
    }
}
