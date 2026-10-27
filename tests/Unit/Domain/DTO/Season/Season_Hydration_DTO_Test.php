<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Season;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Season\Season_Hydration_DTO;

final class Season_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_wpdb_string_row_to_declared_types(): void {
        $dto = Season_Hydration_DTO::from_object( (object) [ 'id' => '7', 'name' => 2026 ] );

        $this->assertSame( 7, $dto->id );
        $this->assertSame( '2026', $dto->name );
    }

    public function test_from_object_defaults_missing_columns(): void {
        $dto = Season_Hydration_DTO::from_object( new \stdClass() );

        $this->assertNull( $dto->id );
        $this->assertSame( '', $dto->name );
    }

    public function test_from_object_drops_unknown_keys(): void {
        $dto = Season_Hydration_DTO::from_object( (object) [ 'id' => 1, 'name' => '2025', 'extra' => 'x' ] );

        $this->assertSame( [ 'id', 'name' ], array_keys( get_object_vars( $dto ) ) );
    }

    public function test_from_array_equals_from_object(): void {
        $data = [ 'id' => '3', 'name' => '2024' ];

        $this->assertEquals( Season_Hydration_DTO::from_object( (object) $data ), Season_Hydration_DTO::from_array( $data ) );
    }
}
