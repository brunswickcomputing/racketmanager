<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Enums\Club_Role as Club_Role_Enum;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Exceptions\Charge_Not_Found_Exception;
use Racketmanager\Application\Mail\Mailer_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Interfaces\Charge_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Invoice_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Error_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

require_once __DIR__ . '/../../wp-stubs.php';

/**
 * Regression coverage for int/string boundaries that only surfaced once the Services layer declared
 * strict_types=1: paths the static hazard scan flagged but no existing test exercised.
 */
#[AllowMockObjectsWithoutExpectations]
final class Strict_Types_Boundary_Regression_Test extends TestCase {

    public function test_set_club_role_resolves_int_role_id_against_string_keyed_lookup(): void {
        $club_role_repository = $this->createMock( Club_Role_Repository_Interface::class );
        $club_role_repository->expects( self::once() )->method( 'save' )->with( self::isInstanceOf( Club_Role::class ) );
        $service = $this->make_club_service( $club_role_repository );

        $role = $service->set_club_role( 3, Club_Role_Enum::COACH->value, 7 );

        self::assertInstanceOf( Club_Role::class, $role );
        self::assertSame( 3, $role->club_id );
        self::assertSame( Club_Role_Enum::COACH->value, $role->role_id );
        self::assertSame( 7, $role->user_id );
    }

    public function test_set_club_role_returns_false_for_unknown_role_id(): void {
        $club_role_repository = $this->createMock( Club_Role_Repository_Interface::class );
        $club_role_repository->expects( self::never() )->method( 'save' );
        $service = $this->make_club_service( $club_role_repository );

        self::assertFalse( $service->set_club_role( 3, 999, 7 ) );
    }

    public function test_set_charge_used_builds_not_found_message_from_numeric_and_non_numeric_keys(): void {
        $charge_repository = $this->createMock( Charge_Repository_Interface::class );
        $charge_repository->method( 'find_by_id' )->willReturn( null );
        $service = $this->make_finance_service( $charge_repository );

        $messages = [];
        foreach ( [ '12', 'abc' ] as $key ) {
            try {
                $service->set_charge_used( $key );
                self::fail( 'Expected Charge_Not_Found_Exception for key ' . $key );
            } catch ( Charge_Not_Found_Exception $e ) {
                $messages[] = $e->getMessage();
            }
        }

        self::assertSame( [ 'Charge 12 not found', 'Charge not found' ], $messages );
    }

    public function test_update_btm_stores_int_btm_on_string_typed_setter(): void {
        $player            = Player_Test_Factory::create_sparse( [ 'id' => 42, 'btm' => '100' ] );
        $player_repository = $this->createMock( Player_Repository::class );
        $player_repository->method( 'find' )->willReturn( $player );
        $player_repository->expects( self::once() )->method( 'update' )->with( $player, [ 'btm' => true ] )->willReturn( true );
        $service = $this->make_player_service( $player_repository );

        self::assertTrue( $service->update_btm( 42, 200 ) );
        self::assertSame( '200', $player->btm );
        self::assertFalse( $service->update_btm( 42, 200 ) );
    }

    private function make_club_service( Club_Role_Repository_Interface $club_role_repository ): Club_Service {
        return new Club_Service(
            $this->createMock( Club_Repository::class ),
            $this->createMock( Registration_Repository_Interface::class ),
            $club_role_repository,
            $this->createMock( Player_Repository::class ),
            $this->createMock( Team_Repository::class ),
            $this->createMock( Player_Service::class )
        );
    }

    private function make_finance_service( Charge_Repository_Interface $charge_repository ): Finance_Service {
        $app                = $this->createStub( RacketManager::class );
        $app->currency_code = 'GBP';
        $app->method( 'get_options' )->willReturn( [ 'stripe_is_live' => false, 'api_secret_key_test' => 'sk_test' ] );

        return new Finance_Service(
            $app,
            $charge_repository,
            $this->createStub( Invoice_Repository_Interface::class ),
            $this->createStub( Club_Repository_Interface::class ),
            $this->createStub( Tournament_Repository_Interface::class ),
            $this->createStub( Competition_Service::class ),
            $this->createStub( Player_Service::class )
        );
    }

    private function make_player_service( Player_Repository $player_repository ): Player_Service {
        return new Player_Service(
            $this->createMock( RacketManager::class ),
            $player_repository,
            $this->createMock( Player_Error_Repository_Interface::class ),
            $this->createMock( Club_Role_Repository_Interface::class ),
            $this->createMock( Rating_Gateway_Interface::class ),
            $this->createMock( League_Team_Repository::class ),
            $this->createMock( Club_Repository::class ),
            $this->createMock( Registration_Repository_Interface::class ),
            $this->createMock( Mailer_Interface::class )
        );
    }
}
