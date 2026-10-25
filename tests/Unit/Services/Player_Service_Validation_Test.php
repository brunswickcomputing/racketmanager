<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Exceptions\Player_Not_Updated_Exception;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Error_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Services\Player_Service;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use stdClass;

require_once __DIR__ . '/../../wp-stubs.php';

final class Player_Service_Validation_Test extends TestCase {
    private Player_Repository $player_repository;
    private Player_Service $service;

    protected function setUp(): void {
        parent::setUp();

        $this->player_repository       = $this->createStub( Player_Repository::class );
        $player_error_repository       = $this->createStub( Player_Error_Repository_Interface::class );
        $club_role_repository          = $this->createStub( Club_Role_Repository_Interface::class );
        $rating_gateway                = $this->createStub( Rating_Gateway_Interface::class );
        $league_team_repository        = $this->createStub( League_Team_Repository::class );
        $club_repository               = $this->createStub( Club_Repository::class );
        $registration_repository       = $this->createStub( Registration_Repository_Interface::class );
        $mailer                        = $this->createStub( Mailer_Interface::class );
        $racketmanager                 = $this->createStub( RacketManager::class );

        $this->service = new Player_Service(
            $racketmanager,
            $this->player_repository,
            $player_error_repository,
            $club_role_repository,
            $rating_gateway,
            $league_team_repository,
            $club_repository,
            $registration_repository,
            $mailer
        );

        $_POST    = [];
        $_GET     = [];
        $_REQUEST = [];
    }

    public function test_validate_player_uses_request_array_when_post_has_conflicting_values(): void {
        $_POST = [
            'playerId'      => '999',
            'firstname'     => 'WrongFirst',
            'surname'       => 'WrongLast',
            'gender'        => 'F',
            'btm'           => '99999999',
            'contactno'     => '09999999999',
            'email'         => 'wrong@example.com',
            'locked'        => '1',
            'year_of_birth' => '1980',
        ];

        $request = [
            'playerId'      => 42,
            'firstname'     => 'Alice',
            'surname'       => 'Smith',
            'gender'        => 'F',
            'btm'           => '12345678',
            'contactno'     => '01234567890',
            'email'         => 'alice.smith@example.com',
            'year_of_birth' => '1995',
        ];

        $result = $this->service->validate_player( $request );

        $this->assertInstanceOf( stdClass::class, $result );
        $this->assertSame( 42, $result->id );
        $this->assertSame( 'Alice', $result->firstname );
        $this->assertSame( 'Smith', $result->surname );
        $this->assertSame( 'Alice Smith', $result->fullname );
        $this->assertSame( 'alice.smith', $result->user_login );
        $this->assertSame( 'alice.smith@example.com', $result->email );
        $this->assertSame( '12345678', $result->btm );
        $this->assertSame( '01234567890', $result->contactno );
        $this->assertSame( 'F', $result->gender );
        $this->assertFalse( $result->locked );
        $this->assertSame( 1995, $result->year_of_birth );
    }

    public function test_validate_player_with_null_argument_falls_back_to_post(): void {
        $_POST = [
            'playerId'      => 7,
            'firstname'     => 'Bob',
            'surname'       => 'Jones',
            'gender'        => 'M',
            'btm'           => '87654321',
            'contactno'     => '07700900123',
            'email'         => 'bob.jones@example.com',
            'locked'        => '1',
            'year_of_birth' => '2000',
        ];

        $result = $this->service->validate_player();

        $this->assertInstanceOf( stdClass::class, $result );
        $this->assertSame( 7, $result->id );
        $this->assertSame( 'Bob', $result->firstname );
        $this->assertSame( 'Jones', $result->surname );
        $this->assertSame( 'Bob Jones', $result->fullname );
        $this->assertSame( 'bob.jones', $result->user_login );
        $this->assertSame( 'bob.jones@example.com', $result->email );
        $this->assertSame( '87654321', $result->btm );
        $this->assertSame( '07700900123', $result->contactno );
        $this->assertSame( 'M', $result->gender );
        $this->assertTrue( $result->locked );
        $this->assertSame( 2000, $result->year_of_birth );
    }

    public function test_validate_player_with_empty_array_does_not_leak_into_post(): void {
        $_POST = [
            'playerId'      => 99,
            'firstname'     => 'LeakedFirst',
            'surname'       => 'LeakedLast',
            'gender'        => 'M',
            'btm'           => '11111111',
            'contactno'     => '01111111111',
            'email'         => 'leaked@example.com',
        ];

        // Passing empty array explicitly: should NOT read $_POST
        $result = $this->service->validate_player( [] );

        // Should return WP_Error because firstname, surname, gender are missing
        $this->assertTrue( is_wp_error( $result ) );
        $this->assertContains( 'firstname', $result->get_error_codes() );
    }

    public function test_amend_player_details_with_request_updates_player(): void {
        $_POST = [
            'firstname' => 'ShouldBeIgnored',
        ];

        $player = Player_Test_Factory::create_sparse( [
            'id'           => 15,
            'display_name' => 'Charlie Brown',
            'btm'          => '55555555',
            'firstname'    => 'Charlie',
            'surname'      => 'Brown',
            'email'        => 'charlie@example.com',
            'gender'       => 'M',
        ] );

        $player_repo = $this->createMock( Player_Repository::class );
        $player_repo->expects( $this->exactly( 2 ) )->method( 'find' )->with( 15 )->willReturn( $player );
        $player_repo->expects( $this->once() )
            ->method( 'update' )
            ->with(
                $this->callback( function ( Player $p ) {
                    return $p->firstname === 'Charles';
                } ),
                $this->callback( function ( array $updates ) {
                    return isset( $updates['core'] ) && $updates['core'] === true;
                } )
            );

        $service = new Player_Service(
            $this->createStub( RacketManager::class ),
            $player_repo,
            $this->createStub( Player_Error_Repository_Interface::class ),
            $this->createStub( Club_Role_Repository_Interface::class ),
            $this->createStub( Rating_Gateway_Interface::class ),
            $this->createStub( League_Team_Repository::class ),
            $this->createStub( Club_Repository::class ),
            $this->createStub( Registration_Repository_Interface::class ),
            $this->createStub( Mailer_Interface::class )
        );

        $request = [
            'playerId'  => 15,
            'firstname' => 'Charles',
            'surname'   => 'Brown',
            'gender'    => 'M',
            'btm'       => '55555555',
            'email'     => 'charlie@example.com',
        ];

        $result = $service->amend_player_details( 15, $request );
        $this->assertInstanceOf( Player::class, $result );
        $this->assertSame( 'Charles', $result->firstname );
    }

    public function test_validate_player_defaults_missing_or_empty_contactno_to_null(): void {
        $base = [
            'firstname' => 'Alice',
            'surname'   => 'Smith',
            'gender'    => 'F',
            'btm'       => '12345678',
            'email'     => 'alice.smith@example.com',
        ];

        $missing = $this->service->validate_player( $base );
        $this->assertInstanceOf( stdClass::class, $missing );
        $this->assertNull( $missing->contactno );

        $empty = $this->service->validate_player( $base + [ 'contactno' => '' ] );
        $this->assertInstanceOf( stdClass::class, $empty );
        $this->assertNull( $empty->contactno );
    }

    public function test_amend_player_details_without_changes_and_null_contactno_raises_no_changes(): void {
        $player = Player_Test_Factory::create_sparse( [
            'id'            => 15,
            'display_name'  => 'Charlie Brown',
            'btm'           => '55555555',
            'firstname'     => 'Charlie',
            'surname'       => 'Brown',
            'email'         => 'charlie@example.com',
            'gender'        => 'M',
            'contactno'     => null,
            'year_of_birth' => null,
            'locked'        => false,
        ] );

        $player_repo = $this->createMock( Player_Repository::class );
        $player_repo->method( 'find' )->willReturn( $player );
        $player_repo->expects( $this->never() )->method( 'update' );

        $service = new Player_Service(
            $this->createStub( RacketManager::class ),
            $player_repo,
            $this->createStub( Player_Error_Repository_Interface::class ),
            $this->createStub( Club_Role_Repository_Interface::class ),
            $this->createStub( Rating_Gateway_Interface::class ),
            $this->createStub( League_Team_Repository::class ),
            $this->createStub( Club_Repository::class ),
            $this->createStub( Registration_Repository_Interface::class ),
            $this->createStub( Mailer_Interface::class )
        );

        $this->expectException( Player_Not_Updated_Exception::class );
        $this->expectExceptionMessage( 'No changes to update' );

        $service->amend_player_details(
            15,
            [
                'playerId'  => 15,
                'firstname' => 'Charlie',
                'surname'   => 'Brown',
                'gender'    => 'M',
                'btm'       => '55555555',
                'email'     => 'charlie@example.com',
                'contactno' => '',
            ]
        );
    }
}
