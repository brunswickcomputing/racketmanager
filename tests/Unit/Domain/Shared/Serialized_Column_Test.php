<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Shared;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Shared\Serialized_Column;

final class Serialized_Column_Test extends TestCase {

    public function test_decodes_serialized_array(): void {
        $value = serialize( array( 'age_limit' => 'open', 'age_offset' => 2, 'nested' => array( 'a' => 1 ) ) );

        $this->assertSame(
            array( 'age_limit' => 'open', 'age_offset' => 2, 'nested' => array( 'a' => 1 ) ),
            Serialized_Column::decode( $value )
        );
    }

    public function test_decodes_serialized_scalars(): void {
        $this->assertSame( 7, Serialized_Column::decode( 'i:7;' ) );
        $this->assertSame( 1.5, Serialized_Column::decode( 'd:1.5;' ) );
        $this->assertSame( 'hi', Serialized_Column::decode( 's:2:"hi";' ) );
        $this->assertTrue( Serialized_Column::decode( 'b:1;' ) );
        $this->assertFalse( Serialized_Column::decode( 'b:0;' ) );
        $this->assertNull( Serialized_Column::decode( 'N;' ) );
    }

    public function test_tolerates_surrounding_whitespace(): void {
        $this->assertSame( array( 1, 2 ), Serialized_Column::decode( "  a:2:{i:0;i:1;i:1;i:2;}\n" ) );
    }

    /**
     * @return iterable<string, array{0: mixed}>
     */
    public static function non_serialized_values(): iterable {
        yield 'null'          => [ null ];
        yield 'empty string'  => [ '' ];
        yield 'plain string'  => [ 'open' ];
        yield 'json string'   => [ '{"age_limit":"open"}' ];
        yield 'already array' => [ array( 'age_limit' => 'open' ) ];
        yield 'integer'       => [ 42 ];
        yield 'object'        => [ (object) array( 'x' => 1 ) ];
        yield 'looks like s:' => [ 's:hello' ];
        yield 'colon string'  => [ 'a:b:c' ];
    }

    #[DataProvider( 'non_serialized_values' )]
    public function test_returns_non_serialized_values_unchanged( mixed $value ): void {
        $this->assertSame( $value, Serialized_Column::decode( $value ) );
    }

    public function test_returns_malformed_serialized_string_unchanged(): void {
        $malformed = 'a:2:{i:0;s:3:"abc";}';

        $this->assertSame( $malformed, Serialized_Column::decode( $malformed ) );
    }

    public function test_hydrates_plain_stdclass_objects_like_maybe_unserialize(): void {
        $decoded = Serialized_Column::decode( 'O:8:"stdClass":1:{s:1:"x";i:1;}' );

        $this->assertInstanceOf( \stdClass::class, $decoded );
        $this->assertSame( 1, $decoded->x );
        $this->assertEquals( maybe_unserialize( 'O:8:"stdClass":1:{s:1:"x";i:1;}' ), $decoded );
    }

    public function test_hydrates_stdclass_nested_inside_arrays(): void {
        $decoded = Serialized_Column::decode( serialize( array( 'team' => (object) array( 'id' => 7 ) ) ) );

        $this->assertInstanceOf( \stdClass::class, $decoded['team'] );
        $this->assertSame( 7, $decoded['team']->id );
    }

    public function test_does_not_instantiate_other_classes(): void {
        $decoded = Serialized_Column::decode( 'O:12:"Some_Service":1:{s:1:"x";i:1;}' );

        $this->assertInstanceOf( \__PHP_Incomplete_Class::class, $decoded );
        $this->assertSame( 1, ( (array) $decoded )['x'] );
    }

    public function test_unslash_strips_slashes_recursively_like_stripslashes_deep(): void {
        $value = array(
            'name'   => "O\\'Brien",
            'nested' => array( 'quote' => 'say \\"hi\\"', 'count' => 3, 'flag' => true, 'none' => null ),
            'object' => (object) array( 'title' => "Ladies\\' Doubles" ),
        );

        $unslashed = Serialized_Column::unslash( $value );

        $this->assertSame( "O'Brien", $unslashed['name'] );
        $this->assertSame( 'say "hi"', $unslashed['nested']['quote'] );
        $this->assertSame( 3, $unslashed['nested']['count'] );
        $this->assertTrue( $unslashed['nested']['flag'] );
        $this->assertNull( $unslashed['nested']['none'] );
        $this->assertSame( "Ladies' Doubles", $unslashed['object']->title );
        $this->assertSame( 5, Serialized_Column::unslash( 5 ) );
        $this->assertNull( Serialized_Column::unslash( null ) );
    }

    public function test_decode_unslashed_matches_legacy_rubber_custom_hydration(): void {
        $custom = array(
            'sets'     => array( 1 => array( 'player1' => '6', 'player2' => '4' ) ),
            'walkover' => "home\\'s",
        );

        $decoded = Serialized_Column::decode_unslashed( serialize( $custom ) );

        $this->assertSame( array( 1 => array( 'player1' => '6', 'player2' => '4' ) ), $decoded['sets'] );
        $this->assertSame( "home's", $decoded['walkover'] );
        $this->assertSame( array( 'already' => 'array' ), Serialized_Column::decode_unslashed( array( 'already' => 'array' ) ) );
        $this->assertNull( Serialized_Column::decode_unslashed( null ) );
        $this->assertSame( '', Serialized_Column::decode_unslashed( '' ) );
    }

    public function test_decode_array_coerces_empty_values(): void {
        $this->assertSame( array(), Serialized_Column::decode_array( null ) );
        $this->assertSame( array(), Serialized_Column::decode_array( '' ) );
        $this->assertSame( array(), Serialized_Column::decode_array( 'b:0;' ) );
        $this->assertSame( array(), Serialized_Column::decode_array( 'a:2:{i:0;s:3:"abc";}' ) );
    }

    public function test_decode_array_keeps_arrays_and_wraps_scalars(): void {
        $this->assertSame( array( 'k' => 'v' ), Serialized_Column::decode_array( serialize( array( 'k' => 'v' ) ) ) );
        $this->assertSame( array( 'k' => 'v' ), Serialized_Column::decode_array( array( 'k' => 'v' ) ) );
        $this->assertSame( array( 'open' ), Serialized_Column::decode_array( 'open' ) );
        $this->assertSame( array( 'x' => 1 ), Serialized_Column::decode_array( (object) array( 'x' => 1 ) ) );
    }

    public function test_matches_wordpress_maybe_unserialize_for_typical_columns(): void {
        $samples = array(
            serialize( array( 'age_limit' => 'open', 'age_offset' => null ) ),
            'i:7;',
            'open',
            '',
            '{"json":true}',
            null,
            array( 'already' => 'array' ),
        );
        foreach ( $samples as $sample ) {
            $this->assertSame( maybe_unserialize( $sample ), Serialized_Column::decode( $sample ) );
        }
    }
}
