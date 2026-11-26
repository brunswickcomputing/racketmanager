<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Club;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Club\Club_Hydration_DTO;

final class Club_Hydration_DTO_Test extends TestCase {

    public function test_from_object_casts_string_db_row_to_declared_types(): void {
        $player = (object) [ 'id' => '1', 'display_name' => 'John Doe' ];
        $dto = Club_Hydration_DTO::from_object(
            (object) [
                'id'             => '5',
                'name'      => 'Rayleigh LTC',
                'shortcode'  => 'Rayleigh',
                'type'        => 'affiliated',
                'website'   => 'https://www.rayleightlc.com',
                'contactno'   => '01702582345',
                'founded'   => '1926',
                'address'   => '127 Rayleigh High Street, Rayleigh Essex SS9 2PE',
                'match_secretary' => $player,
            ]
        );

        $this->assertSame( 5, $dto->id );
        $this->assertSame( 'Rayleigh LTC', $dto->name );
        $this->assertSame( 'Rayleigh', $dto->shortcode );
        $this->assertSame( 'affiliated', $dto->type );
        $this->assertSame( 'https://www.rayleightlc.com', $dto->website );
        $this->assertSame( '01702582345', $dto->contactno );
        $this->assertSame( 1926, $dto->founded );
        $this->assertSame( '127 Rayleigh High Street, Rayleigh Essex SS9 2PE', $dto->address );
        $this->assertSame( $player, $dto->match_secretary );
    }

    public function test_empty_columns_follow_legacy_coercion(): void {
        $dto = Club_Hydration_DTO::from_object(
            (object) [
                'id'            => '',
                'name'     => '',
                'shortcode'  => '',
                'type' => '',
                'founded'  => '',
            ]
        );

        $this->assertNull( $dto->id );
        $this->assertSame( '', $dto->name );
        $this->assertSame( '', $dto->shortcode );
        $this->assertSame( '', $dto->type );
        $this->assertNull( $dto->founded );
    }

    public function test_attached_player_object_is_kept_and_unknown_keys_dropped(): void {
        $player = (object) [ 'id' => 10 ];
        $dto    = Club_Hydration_DTO::from_object( (object) [ 'id' => 1, 'match_secretary' => $player, 'team' => 'x' ] );

        $this->assertSame( $player, $dto->match_secretary );
        $this->assertFalse( property_exists( $dto, 'team' ) );
    }
}
