<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Account\Commands;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Account\Commands\Add_Favourite_Command;
use Racketmanager\Application\Account\Commands\Delete_Messages_Command;
use Racketmanager\Application\Account\Commands\Login_Command;
use Racketmanager\Application\Account\Commands\Reset_Password_Command;
use Racketmanager\Application\Account\Commands\Update_Account_Command;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

final class Account_Commands_Test extends TestCase {
    public function test_add_favourite_command(): void {
        $post = [
            'type' => 'club',
            'id'   => '15',
        ];

        $cmd = Add_Favourite_Command::from_post( $post, 42 );

        $this->assertSame( 'club', $cmd->type );
        $this->assertSame( 15, $cmd->id );
        $this->assertSame( 42, $cmd->user_id );
    }

    public function test_delete_messages_command(): void {
        $post = [
            'message_type' => 'result',
        ];

        $cmd = Delete_Messages_Command::from_post( $post, 42 );

        $this->assertSame( 'result', $cmd->message_type );
        $this->assertSame( 42, $cmd->user_id );
    }

    public function test_login_command(): void {
        $post = [
            'log'         => 'testuser',
            'pwd'         => 'secret',
            'redirect_to' => 'https://example.com/account',
        ];

        $cmd = Login_Command::from_post( $post );

        $this->assertSame( 'testuser', $cmd->user_login );
        $this->assertSame( 'secret', $cmd->user_password );
        $this->assertSame( 'https://example.com/account', $cmd->redirect_to );
    }

    public function test_reset_password_command(): void {
        $post = [
            'user_login' => 'testuser@example.com',
        ];

        $cmd = Reset_Password_Command::from_post( $post );

        $this->assertSame( 'testuser@example.com', $cmd->user_login );
    }

    public function test_update_account_command(): void {
        $post = [
            'username'      => 'john@example.com',
            'firstname'     => 'John',
            'lastname'      => 'Doe',
            'contactno'     => '0123456789',
            'gender'        => 'M',
            'btm'           => '123456',
            'year_of_birth' => '1990',
            'password'      => 'newpass',
            'rePassword'    => 'newpass',
            'opt_in'        => [ 'email' => 1 ],
        ];

        $cmd = Update_Account_Command::from_post( $post );

        $this->assertSame( 'john@example.com', $cmd->username );
        $this->assertSame( 'John', $cmd->firstname );
        $this->assertSame( 'Doe', $cmd->lastname );
        $this->assertSame( '0123456789', $cmd->contactno );
        $this->assertSame( 'M', $cmd->gender );
        $this->assertSame( 123456, $cmd->btm );
        $this->assertSame( 1990, $cmd->year_of_birth );
        $this->assertSame( 'newpass', $cmd->password );
        $this->assertSame( 'newpass', $cmd->re_password );
        $this->assertSame( [ 'email' => 1 ], $cmd->opt_ins );
    }
}
