<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Tournament;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Tournament\Tournament_Partner_Request_DTO;

class Tournament_Partner_Request_DTO_Test extends TestCase {

    public function test_construct_with_camel_case_payload(): void {
        $data = [
            'tournamentId' => '8',
            'playerId'     => '3',
            'partnerId'    => '15',
            'eventId'      => '4',
            'partner'      => 'Jane Doe',
            'modal'        => 'partner-modal',
        ];

        $dto = new Tournament_Partner_Request_DTO( $data );

        $this->assertSame( 8, $dto->tournament_id );
        $this->assertSame( 3, $dto->player_id );
        $this->assertSame( 15, $dto->partner_id );
        $this->assertSame( 4, $dto->event_id );
        $this->assertSame( 'Jane Doe', $dto->partner_name );
        $this->assertSame( 'partner-modal', $dto->modal );
    }

    public function test_construct_with_snake_case_payload(): void {
        $data = [
            'tournament_id' => '12',
            'player_id'     => '6',
            'partner_id'    => '18',
            'event_id'      => '9',
        ];

        $dto = new Tournament_Partner_Request_DTO( $data );

        $this->assertSame( 12, $dto->tournament_id );
        $this->assertSame( 6, $dto->player_id );
        $this->assertSame( 18, $dto->partner_id );
        $this->assertSame( 9, $dto->event_id );
    }

    public function test_construct_with_legacy_tournament_key(): void {
        $data = [
            'tournament' => '25',
        ];

        $dto = new Tournament_Partner_Request_DTO( $data );

        $this->assertSame( 25, $dto->tournament_id );
    }

    public function test_camel_first_precedence_when_conflicting_ids_provided(): void {
        $data = [
            'tournamentId'  => '8',
            'tournament_id' => '999',
            'tournament'    => '555',
            'playerId'      => '3',
            'player_id'     => '30',
            'partnerId'     => '15',
            'partner_id'    => '150',
            'eventId'       => '4',
            'event_id'      => '40',
        ];

        $dto = new Tournament_Partner_Request_DTO( $data );

        $this->assertSame( 8, $dto->tournament_id );
        $this->assertSame( 3, $dto->player_id );
        $this->assertSame( 15, $dto->partner_id );
        $this->assertSame( 4, $dto->event_id );
    }

    public function test_blank_alias_does_not_shadow_valid_fallback(): void {
        $data = [
            'tournamentId' => '',
            'tournament_id' => '17',
            'playerId'     => '',
            'player_id'    => '8',
            'partnerId'    => '',
            'partner_id'   => '19',
            'eventId'      => '',
            'event_id'     => '5',
        ];

        $dto = new Tournament_Partner_Request_DTO( $data );

        $this->assertSame( 17, $dto->tournament_id );
        $this->assertSame( 8, $dto->player_id );
        $this->assertSame( 19, $dto->partner_id );
        $this->assertSame( 5, $dto->event_id );

        // Fallback to legacy tournament key when both camelCase and snake_case are blank
        $legacy_data = [
            'tournamentId'  => '',
            'tournament_id' => '',
            'tournament'    => '33',
        ];
        $legacy_dto = new Tournament_Partner_Request_DTO( $legacy_data );
        $this->assertSame( 33, $legacy_dto->tournament_id );
    }

    public function test_empty_payload_defaults_to_null(): void {
        $dto = new Tournament_Partner_Request_DTO( [] );

        $this->assertNull( $dto->tournament_id );
        $this->assertNull( $dto->player_id );
        $this->assertNull( $dto->partner_id );
        $this->assertNull( $dto->event_id );
        $this->assertNull( $dto->partner_name );
        $this->assertNull( $dto->modal );
    }
}
