<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Season;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Season\Season_Hydration_DTO;
use Racketmanager\Domain\Season\Season;
use Racketmanager\Domain\Season\Season_Factory;

final class Season_Factory_Test extends TestCase {

    public function test_create_from_dto_instantiates_season_with_matching_properties(): void {
        $season = Season_Factory::create_from_dto( new Season_Hydration_DTO( id: 4, name: '2026' ) );

        $this->assertInstanceOf( Season::class, $season );
        $this->assertSame( 4, $season->get_id() );
        $this->assertSame( '2026', $season->get_name() );
    }

    public function test_from_object_casts_numeric_string_id(): void {
        $season = Season_Factory::from_object( (object) [ 'id' => '12', 'name' => '2025' ] );

        $this->assertSame( 12, $season->get_id() );
        $this->assertSame( '2025', $season->get_name() );
    }

    public function test_from_object_without_id_yields_null_id_and_empty_name(): void {
        $season = Season_Factory::from_object( new \stdClass() );

        $this->assertNull( $season->get_id() );
        $this->assertSame( '', $season->get_name() );
    }

    public function test_from_array_matches_from_object(): void {
        $data = [ 'id' => 9, 'name' => '2023' ];

        $this->assertEquals( Season_Factory::from_object( (object) $data ), Season_Factory::from_array( $data ) );
    }

    public function test_lenient_entity_from_object_matches_factory_and_constructor_rejects_raw_object(): void {
        $row = (object) [ 'id' => '2', 'name' => '2022' ];

        $this->assertEquals( Season_Factory::from_object( $row ), Season::from_object( $row ) );
        $this->assertEquals( Season::from_object( $row ), Season::from_array( (array) $row ) );

        $empty = new Season();
        $this->assertNull( $empty->get_id() );

        $this->expectException( \TypeError::class );
        new Season( $row );
    }
}
