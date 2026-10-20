<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Club\Commands;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Club\Commands\Club_Player_Request_Command;
use Racketmanager\Application\Club\Commands\Club_Players_Remove_Command;
use Racketmanager\Application\Club\Commands\Set_Club_Role_Command;
use Racketmanager\Application\Club\Commands\Update_Club_Command;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

final class Club_Commands_Test extends TestCase {
    public function test_club_player_request_command(): void {
        $post = [
            'club' => '10',
        ];

        $cmd = Club_Player_Request_Command::from_post( $post, 5 );

        $this->assertSame( 10, $cmd->club_id );
        $this->assertSame( 5, $cmd->user_id );
    }

    public function test_club_players_remove_command(): void {
        $post = [
            'clubPlayer' => [ '1', '2', '3' ],
        ];

        $cmd = Club_Players_Remove_Command::from_post( $post, 5 );

        $this->assertSame( [ 1, 2, 3 ], $cmd->club_player_ids );
        $this->assertSame( 5, $cmd->user_id );
    }

    public function test_set_club_role_command(): void {
        $post = [
            'clubId'       => '30',
            'userId'       => '40',
            'clubRoleId'   => '2',
            'contactno'    => '0123456789',
            'contactemail' => 'sec@example.com',
        ];

        $cmd = Set_Club_Role_Command::from_post( $post );

        $this->assertSame( 30, $cmd->club_id );
        $this->assertSame( 40, $cmd->user_id );
        $this->assertSame( 2, $cmd->club_role_id );
        $this->assertSame( '0123456789', $cmd->contact_no );
        $this->assertSame( 'sec@example.com', $cmd->email );
    }

    public function test_update_club_command(): void {
        $post = [
            'club_id'                  => '50',
            'clubContactNo'            => '0123456789',
            'facilities'               => 'Courts and clubhouse',
            'founded'                  => '1920',
            'website'                  => 'https://example.com',
            'address'                  => '123 High Street',
            'matchSecretaryId'         => '10',
            'matchSecretaryContactNo'  => '0987654321',
            'matchSecretaryEmail'      => 'matchsec@example.com',
        ];

        $cmd = Update_Club_Command::from_post( $post );

        $this->assertSame( 50, $cmd->club_id );
        $this->assertSame( '0123456789', $cmd->contact_no );
        $this->assertSame( 'Courts and clubhouse', $cmd->facilities );
        $this->assertSame( 1920, $cmd->founded );
        $this->assertSame( 'https://example.com', $cmd->website );
        $this->assertSame( '123 High Street', $cmd->address );
        $this->assertSame( 10, $cmd->match_secretary_id );
        $this->assertSame( '0987654321', $cmd->match_secretary_contact_no );
        $this->assertSame( 'matchsec@example.com', $cmd->match_secretary_email );

        $sec = $cmd->match_secretary();
        $this->assertSame( 10, $sec->id );
        $this->assertSame( '0987654321', $sec->contactno );
        $this->assertSame( 'matchsec@example.com', $sec->email );
    }
}
