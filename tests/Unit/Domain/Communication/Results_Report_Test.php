<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Communication;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Communication\Results_Report;

class Results_Report_Test extends TestCase {
    public function test_instantiation_without_data(): void {
        $report = new Results_Report();
        $this->assertInstanceOf( Results_Report::class, $report );
        $this->assertSame( 0, $report->id );
    }

    public function test_instantiation_with_raw_data_encodes_json(): void {
        $data = (object) [
            'id'       => 15,
            'match_id' => 101,
            'data'     => (object) [ 'home_score' => 4, 'away_score' => 2 ],
        ];

        $report = Results_Report::from_object( $data );

        $this->assertSame( 15, $report->id );
        $this->assertSame( 101, $report->match_id );
        $this->assertIsObject( $report->data );
        $this->assertSame( 4, $report->data->home_score );
        $this->assertSame( '{"home_score":4,"away_score":2}', $report->result_object );
    }

    public function test_instantiation_with_result_object_string_decodes_data(): void {
        $data = (object) [
            'id'            => 20,
            'match_id'      => 102,
            'result_object' => '{"winner":"home","notes":"great match"}',
        ];

        $report = Results_Report::from_object( $data );

        $this->assertSame( 20, $report->id );
        $this->assertSame( 102, $report->match_id );
        $this->assertIsObject( $report->data );
        $this->assertSame( 'home', $report->data->winner );
        $this->assertSame( 'great match', $report->data->notes );
    }

    public function test_encoding_is_byte_identical_to_wp_json_encode_default_flags(): void {
        $payload = (object) [
            'winner'  => 'home',
            'notes'   => 'Café / "quoted" & <tag>',
            'sets'    => [ [ 6, 4 ], [ 7, 5 ] ],
            'nothing' => null,
            'ratio'   => 0.5,
        ];

        $report = Results_Report::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'data' => $payload ] );

        $this->assertSame( wp_json_encode( $payload ), $report->result_object );
        $this->assertSame( json_encode( $payload ), $report->result_object );
        $this->assertEquals( $payload, json_decode( $report->result_object ) );
    }

    public function test_encoding_recovers_from_invalid_utf8_instead_of_returning_false(): void {
        $report = Results_Report::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'data' => (object) [ 'notes' => "bad \xB1 byte" ] ] );

        $this->assertIsString( $report->result_object );
        $this->assertNotFalse( json_decode( $report->result_object ) );
    }
}
