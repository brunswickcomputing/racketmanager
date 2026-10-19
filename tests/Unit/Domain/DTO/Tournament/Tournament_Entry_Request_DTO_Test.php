<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO\Tournament;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Request_DTO;

class Tournament_Entry_Request_DTO_Test extends TestCase {

    public function test_construct_with_camel_case_form_payload(): void {
        $data = [
            'tournamentId'     => '8',
            'playerId'         => '3',
            'clubId'           => '12',
            'season'           => '2026',
            'contactemail'     => 'player@example.com',
            'contactno'        => '0123456789',
            'btm'              => 'BTM123456',
            'competitionFee'   => '5.00',
            'priceCostTotal'   => '25.00',
            'pricePaidTotal'   => '0.00',
            'commentDetails'   => 'Looking forward to playing',
            'acceptance'       => '1',
            'tournamentEvents' => '101,102,103',
            'event'            => [ 101, 102 ],
            'eventFee'         => [ 101 => '10.00', 102 => '10.00' ],
            'partner'          => [ 102 => 'Partner Name' ],
            'partnerId'        => [ 102 => '45' ],
        ];

        $dto = new Tournament_Entry_Request_DTO( $data );

        $this->assertSame( 8, $dto->tournament_id );
        $this->assertSame( 3, $dto->player_id );
        $this->assertSame( 12, $dto->club_id );
        $this->assertSame( '2026', $dto->season );
        $this->assertSame( 'player@example.com', $dto->email );
        $this->assertSame( '0123456789', $dto->phone );
        $this->assertSame( 'BTM123456', $dto->lta_number );
        $this->assertSame( 5.0, $dto->competition_fee );
        $this->assertSame( 25.0, $dto->total_cost );
        $this->assertSame( 0.0, $dto->paid_amt );
        $this->assertSame( 'Looking forward to playing', $dto->comments );
        $this->assertTrue( $dto->acceptance );
        $this->assertSame( [ 103 ], $dto->missed_event_ids );
        $this->assertCount( 2, $dto->entries );
        $this->assertSame( 10.0, $dto->entries[101]->fee );
        $this->assertSame( '', $dto->entries[101]->partner_name );
        $this->assertSame( 0, $dto->entries[101]->partner_id );
        $this->assertSame( 10.0, $dto->entries[102]->fee );
        $this->assertSame( 'Partner Name', $dto->entries[102]->partner_name );
        $this->assertSame( 45, $dto->entries[102]->partner_id );
    }

    public function test_construct_with_snake_case_payload(): void {
        $data = [
            'tournament_id' => '15',
            'player_id'     => '7',
            'club_id'       => '4',
        ];

        $dto = new Tournament_Entry_Request_DTO( $data );

        $this->assertSame( 15, $dto->tournament_id );
        $this->assertSame( 7, $dto->player_id );
        $this->assertSame( 4, $dto->club_id );
    }

    public function test_construct_with_legacy_tournament_key(): void {
        $data = [
            'tournament' => '22',
        ];

        $dto = new Tournament_Entry_Request_DTO( $data );

        $this->assertSame( 22, $dto->tournament_id );
    }

    public function test_snake_first_precedence_when_conflicting_ids_provided(): void {
        // Deliberate snake-first precedence requirement: tournament_id has precedence over tournamentId
        $data = [
            'tournament_id' => '8',
            'tournamentId'  => '999',
            'tournament'    => '555',
        ];

        $dto = new Tournament_Entry_Request_DTO( $data );

        $this->assertSame( 8, $dto->tournament_id );
    }

    public function test_blank_alias_does_not_shadow_valid_fallback(): void {
        // Empty string in tournament_id should fallback to tournamentId
        $data1 = [
            'tournament_id' => '',
            'tournamentId'  => '8',
        ];
        $dto1 = new Tournament_Entry_Request_DTO( $data1 );
        $this->assertSame( 8, $dto1->tournament_id );

        // Empty string in tournament_id and tournamentId should fallback to tournament
        $data2 = [
            'tournament_id' => '',
            'tournamentId'  => '',
            'tournament'    => '14',
        ];
        $dto2 = new Tournament_Entry_Request_DTO( $data2 );
        $this->assertSame( 14, $dto2->tournament_id );

        // Empty string in playerId should fallback to player_id
        $data3 = [
            'playerId'  => '',
            'player_id' => '9',
        ];
        $dto3 = new Tournament_Entry_Request_DTO( $data3 );
        $this->assertSame( 9, $dto3->player_id );

        // Empty string in clubId should fallback to club_id
        $data4 = [
            'clubId'  => '',
            'club_id' => '11',
        ];
        $dto4 = new Tournament_Entry_Request_DTO( $data4 );
        $this->assertSame( 11, $dto4->club_id );
    }

    public function test_empty_payload_defaults_to_zero(): void {
        $dto = new Tournament_Entry_Request_DTO( [] );

        $this->assertSame( 0, $dto->tournament_id );
        $this->assertSame( 0, $dto->player_id );
        $this->assertSame( 0, $dto->club_id );
        $this->assertSame( '', $dto->season );
        $this->assertSame( '', $dto->email );
        $this->assertSame( '', $dto->phone );
        $this->assertSame( '', $dto->lta_number );
        $this->assertSame( 0.0, $dto->competition_fee );
        $this->assertSame( 0.0, $dto->total_cost );
        $this->assertSame( 0.0, $dto->paid_amt );
        $this->assertSame( '', $dto->comments );
        $this->assertFalse( $dto->acceptance );
        $this->assertSame( [], $dto->entries );
        $this->assertSame( [], $dto->missed_event_ids );
    }
}
