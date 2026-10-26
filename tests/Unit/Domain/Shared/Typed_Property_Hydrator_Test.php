<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Shared;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Shared\Typed_Property_Hydrator;

#[\AllowDynamicProperties]
class Typed_Property_Hydrator_Parent_Fixture {
    public int $count = 7;
    public ?int $captain = null;
    public float $ratio = 0.0;
    public bool $home = false;
    public ?bool $system_record = null;
    public string $season = '';
    public ?string $age_limit = null;
    public array $custom = array();
    public $untyped = 'default';
    public int|string $union = 0;
    private ?string $status = null;

    public function status(): ?string {
        return $this->status;
    }
}

final class Typed_Property_Hydrator_Child_Fixture extends Typed_Property_Hydrator_Parent_Fixture {
}

final class Typed_Property_Hydrator_Test extends TestCase {

    /**
     * @return iterable<string, array{0: string, 1: mixed, 2: mixed}>
     */
    public static function row_values(): iterable {
        yield 'numeric string to int'          => [ 'count', '12', 12 ];
        yield 'float to int'                   => [ 'count', 3.0, 3 ];
        yield 'empty string to int'            => [ 'count', '', 0 ];
        yield 'numeric string to nullable int' => [ 'captain', '42', 42 ];
        yield 'empty string to nullable int'   => [ 'captain', '', 0 ];
        yield 'null to nullable int'           => [ 'captain', null, null ];
        yield 'numeric string to float'        => [ 'ratio', '0.5', 0.5 ];
        yield 'empty string to float'          => [ 'ratio', '', 0.0 ];
        yield 'string flag to bool'            => [ 'home', '1', true ];
        yield 'zero string to bool'            => [ 'home', '0', false ];
        yield 'empty string to bool'           => [ 'home', '', false ];
        yield 'int to nullable bool'           => [ 'system_record', 1, true ];
        yield 'int to string'                  => [ 'season', 2026, '2026' ];
        yield 'int to nullable string'         => [ 'age_limit', 45, '45' ];
        yield 'empty string stays string'      => [ 'season', '', '' ];
        yield 'empty string to array'          => [ 'custom', '', array() ];
        yield 'array unchanged'                => [ 'custom', array( 'a' => 1 ), array( 'a' => 1 ) ];
        yield 'untyped unchanged'              => [ 'untyped', '12', '12' ];
        yield 'union unchanged'                => [ 'union', '12', '12' ];
        yield 'non numeric string to int kept' => [ 'untyped', 'abc', 'abc' ];
    }

    #[DataProvider( 'row_values' )]
    public function test_assign_coerces_scalars_to_declared_property_type( string $key, mixed $raw, mixed $expected ): void {
        $target = new Typed_Property_Hydrator_Parent_Fixture();

        Typed_Property_Hydrator::assign( $target, $key, $raw );

        self::assertSame( $expected, $target->$key );
    }

    public function test_null_is_skipped_for_non_nullable_property_and_default_is_kept(): void {
        $target = new Typed_Property_Hydrator_Parent_Fixture();

        Typed_Property_Hydrator::assign( $target, 'count', null );
        Typed_Property_Hydrator::assign( $target, 'season', null );

        self::assertSame( 7, $target->count );
        self::assertSame( '', $target->season );
    }

    public function test_assign_reaches_private_properties_including_inherited_ones(): void {
        $parent = new Typed_Property_Hydrator_Parent_Fixture();
        $child  = new Typed_Property_Hydrator_Child_Fixture();

        Typed_Property_Hydrator::assign( $parent, 'status', 1 );
        Typed_Property_Hydrator::assign( $child, 'status', 'active' );

        self::assertSame( '1', $parent->status() );
        self::assertSame( 'active', $child->status() );
        self::assertArrayNotHasKey( 'status', get_object_vars( $child ), 'No dynamic shadow property may be created.' );
    }

    public function test_undeclared_keys_become_dynamic_properties_unchanged(): void {
        $target = new Typed_Property_Hydrator_Parent_Fixture();

        Typed_Property_Hydrator::assign( $target, 'club_name', '12' );

        self::assertSame( '12', $target->club_name );
    }
}
