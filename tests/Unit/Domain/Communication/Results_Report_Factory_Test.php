<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Communication;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Communication\Results_Report;
use Racketmanager\Domain\Communication\Results_Report_Factory;
use Racketmanager\Domain\DTO\Communication\Results_Report_Hydration_DTO;

final class Results_Report_Factory_Test extends TestCase {

    public function test_create_from_dto_copies_fields(): void {
        $report = Results_Report_Factory::create_from_dto( new Results_Report_Hydration_DTO( id: 1, match_id: 2, result_object: '{"a":1}', data: (object) [ 'a' => 1 ] ) );

        $this->assertInstanceOf( Results_Report::class, $report );
        $this->assertSame( 1, $report->id );
        $this->assertSame( 2, $report->match_id );
        $this->assertSame( '{"a":1}', $report->result_object );
        $this->assertSame( 1, $report->data->a );
    }

    public function test_from_object_decodes_result_object_and_encodes_data_like_the_legacy_constructor(): void {
        $from_string = Results_Report_Factory::from_object( (object) [ 'id' => '20', 'match_id' => '102', 'result_object' => '{"winner":"home"}' ] );
        $this->assertSame( 20, $from_string->id );
        $this->assertSame( 102, $from_string->match_id );
        $this->assertSame( 'home', $from_string->data->winner );

        $from_data = Results_Report_Factory::from_object( (object) [ 'match_id' => 12345, 'data' => (object) [ 'test' => 'report' ] ] );
        $this->assertSame( 0, $from_data->id );
        $this->assertSame( '{"test":"report"}', $from_data->result_object );
    }

    public function test_missing_payload_leaves_result_object_and_data_uninitialised(): void {
        $report = Results_Report_Factory::from_object( (object) [ 'match_id' => 1 ] );

        $this->assertFalse( isset( $report->result_object ) );
        $this->assertFalse( isset( $report->data ) );
    }

    public function test_invalid_or_empty_result_object_leaves_data_explicitly_null(): void {
        foreach ( [ 'not json', '' ] as $result_object ) {
            $report = Results_Report_Factory::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'result_object' => $result_object ] );

            $property = new \ReflectionProperty( Results_Report::class, 'data' );
            $this->assertTrue( $property->isInitialized( $report ), "data must be initialised for '$result_object'" );
            $this->assertNull( $property->getValue( $report ) );
            $this->assertSame( $result_object, $report->result_object );
        }

        $lenient = Results_Report::from_object( (object) [ 'id' => 1, 'match_id' => 2, 'result_object' => 'not json' ] );
        $this->assertNull( $lenient->data );
    }

    public function test_from_array_and_lenient_entity_helpers_match_from_object(): void {
        $data = [ 'id' => '1', 'match_id' => '2', 'result_object' => '{"x":1}' ];

        $this->assertEquals( Results_Report_Factory::from_object( (object) $data ), Results_Report_Factory::from_array( $data ) );
        $this->assertEquals( Results_Report_Factory::from_object( (object) $data ), Results_Report::from_object( (object) $data ) );
        $this->assertEquals( Results_Report::from_object( (object) $data ), Results_Report::from_array( $data ) );
    }

    public function test_empty_constructor_yields_empty_report_and_raw_object_is_rejected(): void {
        $empty = new Results_Report();
        $this->assertSame( 0, $empty->id );
        $this->assertFalse( isset( $empty->match_id ) );

        $this->expectException( \TypeError::class );
        new Results_Report( (object) [ 'id' => 1 ] );
    }
}
