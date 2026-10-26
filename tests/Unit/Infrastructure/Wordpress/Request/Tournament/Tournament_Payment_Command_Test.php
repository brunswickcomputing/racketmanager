<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Request\Tournament;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Payment_Command;

class Tournament_Payment_Command_Test extends TestCase {

    public function test_from_post_with_camel_case_keys(): void {
        $post = [
            'tournamentId' => '8',
            'playerId'     => '3',
            'invoiceId'    => '11',
        ];

        $command = Tournament_Payment_Command::from_post( $post );

        $this->assertSame( 8, $command->tournament_id );
        $this->assertSame( 3, $command->player_id );
        $this->assertSame( 11, $command->invoice_id );
    }

    public function test_from_post_with_snake_case_keys(): void {
        $post = [
            'tournament_id' => '14',
            'player_id'     => '9',
            'invoice_id'    => '25',
        ];

        $command = Tournament_Payment_Command::from_post( $post );

        $this->assertSame( 14, $command->tournament_id );
        $this->assertSame( 9, $command->player_id );
        $this->assertSame( 25, $command->invoice_id );
    }

    public function test_from_post_camel_first_precedence_when_conflicting(): void {
        $post = [
            'tournamentId'  => '8',
            'tournament_id' => '999',
            'playerId'      => '3',
            'player_id'     => '333',
            'invoiceId'     => '11',
            'invoice_id'    => '111',
        ];

        $command = Tournament_Payment_Command::from_post( $post );

        $this->assertSame( 8, $command->tournament_id );
        $this->assertSame( 3, $command->player_id );
        $this->assertSame( 11, $command->invoice_id );
    }

    public function test_from_post_blank_alias_does_not_shadow_valid_fallback(): void {
        $post = [
            'tournamentId'  => '',
            'tournament_id' => '8',
            'playerId'      => '',
            'player_id'     => '3',
            'invoiceId'     => '',
            'invoice_id'    => '11',
        ];

        $command = Tournament_Payment_Command::from_post( $post );

        $this->assertSame( 8, $command->tournament_id );
        $this->assertSame( 3, $command->player_id );
        $this->assertSame( 11, $command->invoice_id );
    }

    public function test_from_post_with_empty_array_yields_null(): void {
        $command = Tournament_Payment_Command::from_post( [] );

        $this->assertNull( $command->tournament_id );
        $this->assertNull( $command->player_id );
        $this->assertNull( $command->invoice_id );
    }
}
