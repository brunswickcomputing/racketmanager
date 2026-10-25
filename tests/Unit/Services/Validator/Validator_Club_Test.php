<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Services\Validator\Validator_Club;

final class Validator_Club_Test extends TestCase {
    public function test_club_role_validates_successfully_with_injected_repository(): void {
        $role     = new Club_Role();
        $role->id = 15;

        $repo = $this->createStub( Club_Role_Repository_Interface::class );
        $repo->method( 'find' )->willReturn( $role );

        $validator = new Validator_Club( $repo );
        $result    = $validator->club_role( 15 );

        $this->assertSame( $validator, $result );
        $this->assertFalse( $validator->error );
        $this->assertEmpty( $validator->err_flds );
        $this->assertEmpty( $validator->err_msgs );
    }

    public function test_club_role_fails_when_role_not_found_in_repository(): void {
        $repo = $this->createStub( Club_Role_Repository_Interface::class );
        $repo->method( 'find' )->willReturn( null );

        $validator = new Validator_Club( $repo );
        $result    = $validator->club_role( 999 );

        $this->assertSame( $validator, $result );
        $this->assertTrue( $validator->error );
        $this->assertContains( 'userName', $validator->err_flds );
    }

    public function test_club_role_fails_when_id_is_null_or_zero(): void {
        $repo      = $this->createStub( Club_Role_Repository_Interface::class );
        $validator = new Validator_Club( $repo );

        $validator->club_role( null );
        $this->assertTrue( $validator->error );
        $this->assertContains( 'userName', $validator->err_flds );

        $validator_zero = new Validator_Club( $repo );
        $validator_zero->club_role( 0 );
        $this->assertTrue( $validator_zero->error );
    }

    public function test_name_validation(): void {
        $validator = new Validator_Club();
        $validator->name( '' );
        $this->assertTrue( $validator->error );
        $this->assertContains( 'club', $validator->err_flds );

        $validator_valid = new Validator_Club();
        $validator_valid->name( 'Westcliff Lawn Tennis Club' );
        $this->assertFalse( $validator_valid->error );
    }

    public function test_short_code_validation(): void {
        $validator = new Validator_Club();
        $validator->short_code( null );
        $this->assertTrue( $validator->error );
        $this->assertContains( 'shortcode', $validator->err_flds );

        $validator_valid = new Validator_Club();
        $validator_valid->short_code( 'WLTC' );
        $this->assertFalse( $validator_valid->error );
    }

    public function test_address_validation(): void {
        $validator = new Validator_Club();
        $validator->address( '' );
        $this->assertTrue( $validator->error );
        $this->assertContains( 'address', $validator->err_flds );

        $validator_valid = new Validator_Club();
        $validator_valid->address( '10 Court Lane' );
        $this->assertFalse( $validator_valid->error );
    }

    public function test_user_validation(): void {
        $GLOBALS['wp_stubs_users'][5] = (object) [ 'ID' => 5 ];

        $validator = new Validator_Club();
        $validator->user( null );
        $this->assertTrue( $validator->error );
        $this->assertContains( 'userName', $validator->err_flds );

        $validator_valid = new Validator_Club();
        $validator_valid->user( 5 );
        $this->assertFalse( $validator_valid->error );

        unset( $GLOBALS['wp_stubs_users'] );
    }
}
