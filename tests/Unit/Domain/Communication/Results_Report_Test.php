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

    public function test_getters_and_setters(): void {
        $report = new Results_Report();
        $report->set_id( 55 );
        $report->set_match_id( 200 );

        $this->assertSame( 55, $report->get_id() );
        $this->assertSame( 200, $report->get_match_id() );
        $this->assertTrue( $report->is_for_match( 200 ) );
        $this->assertFalse( $report->is_for_match( 201 ) );
    }

    public function test_set_data_syncs_result_object(): void {
        $report = new Results_Report();
        $this->assertFalse( $report->has_data() );
        $this->assertFalse( $report->has_report() );

        $payload = (object) [ 'home' => 3, 'away' => 1 ];
        $report->set_data( $payload );

        $this->assertTrue( $report->has_data() );
        $this->assertTrue( $report->has_report() );
        $this->assertSame( '{"home":3,"away":1}', $report->get_result_object() );
        $this->assertEquals( $payload, $report->get_data() );
    }

    public function test_set_result_object_syncs_data(): void {
        $report = new Results_Report();
        $json   = '{"referee":"Smith","confirmed":true}';
        $report->set_result_object( $json );

        $this->assertSame( $json, $report->get_result_object() );
        $this->assertIsObject( $report->get_data() );
        $this->assertSame( 'Smith', $report->get_data()->referee );
        $this->assertTrue( $report->get_data()->confirmed );
    }
}
