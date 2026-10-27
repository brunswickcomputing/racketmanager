<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Communication;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Communication\Results_Report_Hydration_DTO;

final class Results_Report_Hydration_DTO_Test extends TestCase {

    public function test_result_object_string_is_decoded_into_data(): void {
        $dto = Results_Report_Hydration_DTO::from_object( (object) [
            'id'            => '20',
            'match_id'      => '102',
            'result_object' => '{"winner":"home","notes":"great match"}',
        ] );

        $this->assertSame( 20, $dto->id );
        $this->assertSame( 102, $dto->match_id );
        $this->assertSame( '{"winner":"home","notes":"great match"}', $dto->result_object );
        $this->assertIsObject( $dto->data );
        $this->assertSame( 'home', $dto->data->winner );
    }

    public function test_data_payload_is_encoded_into_result_object(): void {
        $dto = Results_Report_Hydration_DTO::from_object( (object) [
            'match_id' => 101,
            'data'     => (object) [ 'home_score' => 4, 'away_score' => 2 ],
        ] );

        $this->assertNull( $dto->id );
        $this->assertSame( '{"home_score":4,"away_score":2}', $dto->result_object );
        $this->assertSame( 4, $dto->data->home_score );
    }

    public function test_both_sides_present_are_kept_verbatim(): void {
        $dto = Results_Report_Hydration_DTO::from_object( (object) [
            'result_object' => '{"a":1}',
            'data'          => (object) [ 'b' => 2 ],
        ] );

        $this->assertSame( '{"a":1}', $dto->result_object );
        $this->assertEquals( (object) [ 'b' => 2 ], $dto->data );
    }

    public function test_invalid_utf8_payload_is_still_encoded(): void {
        $dto = Results_Report_Hydration_DTO::from_object( (object) [ 'data' => (object) [ 'notes' => "bad \xB1 byte" ] ] );

        $this->assertIsString( $dto->result_object );
        $this->assertStringContainsString( '"notes"', $dto->result_object );
    }

    public function test_missing_everything_is_all_null_and_unknown_keys_are_dropped(): void {
        $dto = Results_Report_Hydration_DTO::from_object( (object) [ 'foo' => 'bar' ] );

        $this->assertNull( $dto->id );
        $this->assertNull( $dto->match_id );
        $this->assertNull( $dto->result_object );
        $this->assertNull( $dto->data );
        $this->assertSame( [ 'id', 'match_id', 'result_object', 'data' ], array_keys( get_object_vars( $dto ) ) );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => '1', 'match_id' => '2', 'result_object' => '{"x":1}' ];

        $this->assertEquals( Results_Report_Hydration_DTO::from_object( (object) $data ), Results_Report_Hydration_DTO::from_array( $data ) );
    }
}
