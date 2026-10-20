<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Results_Report;

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

        $report = new Results_Report( $data );

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

        $report = new Results_Report( $data );

        $this->assertSame( 20, $report->id );
        $this->assertSame( 102, $report->match_id );
        $this->assertIsObject( $report->data );
        $this->assertSame( 'home', $report->data->winner );
        $this->assertSame( 'great match', $report->data->notes );
    }
}
