<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Season;

use Error;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Season\Season_Hydration_DTO;
use Racketmanager\Domain\Season\Season;

/**
 * Unit tests for Season domain entity encapsulation, hydration, and mutation rules.
 */
final class Season_Test extends TestCase {

    public function test_constructor_hydrates_from_dto(): void {
        $dto    = new Season_Hydration_DTO( id: 10, name: '2026/2027' );
        $season = new Season( $dto );

        $this->assertSame( 10, $season->id );
        $this->assertSame( '2026/2027', $season->name );
        $this->assertSame( 10, $season->get_id() );
        $this->assertSame( '2026/2027', $season->get_name() );
    }

    public function test_constructor_without_dto_yields_default_values(): void {
        $season = new Season();

        $this->assertNull( $season->id );
        $this->assertSame( '', $season->name );
        $this->assertNull( $season->get_id() );
        $this->assertSame( '', $season->get_name() );
    }

    public function test_from_object_hydrates_via_factory(): void {
        $row    = (object) [ 'id' => '15', 'name' => '2025' ];
        $season = Season::from_object( $row );

        $this->assertSame( 15, $season->id );
        $this->assertSame( '2025', $season->name );
        $this->assertSame( 15, $season->get_id() );
        $this->assertSame( '2025', $season->get_name() );
    }

    public function test_from_array_hydrates_via_factory(): void {
        $data   = [ 'id' => 20, 'name' => '2024' ];
        $season = Season::from_array( $data );

        $this->assertSame( 20, $season->id );
        $this->assertSame( '2024', $season->name );
        $this->assertSame( 20, $season->get_id() );
        $this->assertSame( '2024', $season->get_name() );
    }

    public function test_asymmetric_visibility_rejects_external_writes_to_name(): void {
        $season = new Season( new Season_Hydration_DTO( id: 1, name: '2026' ) );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        /** @phpstan-ignore-next-line */
        $season->name = '2027';
    }

    public function test_asymmetric_visibility_rejects_external_writes_to_id(): void {
        $season = new Season( new Season_Hydration_DTO( id: 1, name: '2026' ) );

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        /** @phpstan-ignore-next-line */
        $season->id = 5;
    }

    public function test_subclass_cannot_modify_private_set_property(): void {
        $subclass = new class() extends Season {
            public function mutate_from_subclass(): void {
                $this->name = 'Subclass Mutated';
            }
        };

        $this->expectException( Error::class );
        $this->expectExceptionMessageMatches( '/Cannot modify private\(set\) property/' );

        $subclass->mutate_from_subclass();
    }

    public function test_set_name_mutates_state_correctly(): void {
        $season = new Season();
        $season->set_name( 'Summer 2026' );

        $this->assertSame( 'Summer 2026', $season->name );
        $this->assertSame( 'Summer 2026', $season->get_name() );
    }

    public function test_set_id_mutates_state_correctly(): void {
        $season = new Season();
        $season->set_id( 42 );

        $this->assertSame( 42, $season->id );
        $this->assertSame( 42, $season->get_id() );
    }

    public function test_getter_and_property_read_parity(): void {
        $season = new Season( new Season_Hydration_DTO( id: 99, name: '2028/2029' ) );

        $this->assertSame( $season->name, $season->get_name() );
        $this->assertSame( $season->id, $season->get_id() );

        $season->set_name( '2029/2030' );
        $season->set_id( 100 );

        $this->assertSame( $season->name, $season->get_name() );
        $this->assertSame( $season->id, $season->get_id() );
    }
}
