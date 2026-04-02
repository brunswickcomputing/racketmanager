<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util_Messages;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Tournament_Service_Test extends TestCase {

    public function test_bulk_remove_tournaments_success(): void {
        $repo    = $this->createMock( Tournament_Repository::class );
        $service = $this->get_mocked_service( $repo );

        $service->expects( self::exactly( 2 ) )
                ->method( 'remove_tournament' )
                ->willReturnMap( [
                    [ 123, 1 ],
                    [ 456, 1 ],
                ] );

        $result = $service->bulk_remove_tournaments( [ 123, 456 ] );

        $expected_message = Util_Messages::tournament_deleted( 123 ) . '<br>' . Util_Messages::tournament_deleted( 456 );
        self::assertEquals( $expected_message, $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    private function get_mocked_service( $tournament_repository ): MockObject|Tournament_Service {
        return $this->getMockBuilder( Tournament_Service::class )
                    ->setConstructorArgs( [
                        $this->createMock( RacketManager::class ),
                        $tournament_repository,
                        $this->createMock( Charge_Repository::class ),
                        $this->createMock( Event_Repository::class ),
                        $this->createMock( Fixture_Service::class ),
                        $this->createMock( League_Team_Repository::class ),
                        $this->createMock( Tournament_Entry_Repository::class ),
                        $this->createMock( Competition_Service::class ),
                        $this->createMock( Player_Service::class ),
                        $this->createMock( Club_Service::class ),
                        $this->createMock( Finance_Service::class ),
                        $this->createMock( League_Service::class )
                    ] )
                    ->onlyMethods( [ 'remove_tournament' ] )
                    ->getMock();
    }

    public function test_bulk_remove_tournaments_with_not_found(): void {
        $repo    = $this->createMock( Tournament_Repository::class );
        $service = $this->get_mocked_service( $repo );

        $service->expects( self::exactly( 2 ) )
                ->method( 'remove_tournament' )
                ->willReturnCallback( function ( $id ) {
                    if ( $id === 123 ) {
                        return 1;
                    }
                    throw new Tournament_Not_Found_Exception( "Not found $id" );
                } );

        $result = $service->bulk_remove_tournaments( [ 123, 456 ] );

        $expected_message = Util_Messages::tournament_deleted( 123 ) . '<br>Not found 456';
        self::assertEquals( $expected_message, $result['message'] );
        self::assertTrue( $result['message_type'] );
    }
}
