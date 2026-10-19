<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Player;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Player\Players_List_DTO;

final class Players_List_DTO_Test extends TestCase {
    public function test_construct_with_object_maps_all_fields_and_aliases(): void {
        $row = (object) [
            'player_id'     => '42',
            'display_name'  => 'Jane Smith',
            'firstName'     => 'Jane',
            'surname'       => 'Smith',
            'email'         => 'jane@example.com',
            'gender'        => 'F',
            'btm'           => '12345',
            'user_nicename' => 'janesmith',
        ];

        $dto = new Players_List_DTO( $row );

        $this->assertSame( 42, $dto->id );
        $this->assertSame( 42, $dto->ID );
        $this->assertSame( 42, $dto->get_id() );
        $this->assertSame( 'Jane Smith', $dto->display_name );
        $this->assertSame( 'Jane Smith', $dto->get_display_name() );
        $this->assertSame( 'Jane', $dto->firstname );
        $this->assertSame( 'Jane', $dto->first_name );
        $this->assertSame( 'Smith', $dto->surname );
        $this->assertSame( 'Smith', $dto->last_name );
        $this->assertSame( 'Smith, Jane', $dto->index );
        $this->assertSame( 'jane@example.com', $dto->email );
        $this->assertSame( 'F', $dto->gender );
        $this->assertSame( '12345', $dto->btm );
        $this->assertSame( 'janesmith', $dto->user_nicename );
        $this->assertSame( '/player/jane-smith/12345/', $dto->link );
    }

    public function test_construct_with_array_and_alternate_aliases(): void {
        $data = [
            'ID'            => 10,
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'display_name'  => 'John Doe',
            'user_email'    => 'john@example.com',
            'btm_number'    => '99999',
            'gender'        => 'M',
            'user_nicename' => 'johndoe',
        ];

        $dto = new Players_List_DTO( $data );

        $this->assertSame( 10, $dto->id );
        $this->assertSame( 10, $dto->ID );
        $this->assertSame( 'John', $dto->firstname );
        $this->assertSame( 'Doe', $dto->surname );
        $this->assertSame( 'john@example.com', $dto->email );
        $this->assertSame( '99999', $dto->btm );
        $this->assertSame( 'Smith, Jane', ( new Players_List_DTO( [ 'first_name' => 'Jane', 'last_name' => 'Smith' ] ) )->index );
        $this->assertSame( '/player/john-doe/99999/', $dto->link );
    }

    public function test_link_generation_without_btm(): void {
        $dto = new Players_List_DTO( [
            'id'           => 1,
            'display_name' => 'Alice Wonder',
        ] );

        $this->assertSame( '/player/alice-wonder/', $dto->link );
    }

    public function test_explicit_link_and_index_override(): void {
        $dto = new Players_List_DTO( [
            'id'           => 1,
            'display_name' => 'Bob Builder',
            'link'         => '/custom/bob-link/',
            'index'        => 'Custom Index',
        ] );

        $this->assertSame( '/custom/bob-link/', $dto->link );
        $this->assertSame( 'Custom Index', $dto->index );
    }

    public function test_empty_display_name_results_in_null_link(): void {
        $dto = new Players_List_DTO( [
            'id' => 1,
        ] );

        $this->assertNull( $dto->link );
        $this->assertSame( '', $dto->index );
        $this->assertSame( '', $dto->get_display_name() );
    }
}
