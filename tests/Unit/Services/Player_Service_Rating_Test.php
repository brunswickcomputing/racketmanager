<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Ports\Rating_Gateway_Interface;
use Racketmanager\Exceptions\LTA_Tennis_Number_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
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

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Player_Service_Rating_Test extends TestCase {

    private RacketManager|MockObject $racketmanager;
    private Player_Repository|MockObject $player_repository;
    private Player_Error_Repository_Interface|MockObject $player_error_repository;
    private Club_Role_Repository_Interface|MockObject $club_role_repository;
    private Rating_Gateway_Interface|MockObject $rating_gateway;
    private League_Team_Repository|MockObject $league_team_repository;
    private Club_Repository|MockObject $club_repository;
    private Registration_Repository_Interface|MockObject $registration_repository;
    private Mailer_Interface|MockObject $mailer;
    private Player_Service $service;

    protected function setUp(): void {
        parent::setUp();

        $this->racketmanager           = $this->createMock( RacketManager::class );
        $this->player_repository       = $this->createMock( Player_Repository::class );
        $this->player_error_repository = $this->createMock( Player_Error_Repository_Interface::class );
        $this->club_role_repository    = $this->createMock( Club_Role_Repository_Interface::class );
        $this->rating_gateway          = $this->createMock( Rating_Gateway_Interface::class );
        $this->league_team_repository  = $this->createMock( League_Team_Repository::class );
        $this->club_repository         = $this->createMock( Club_Repository::class );
        $this->registration_repository = $this->createMock( Registration_Repository_Interface::class );
        $this->mailer                  = $this->createMock( Mailer_Interface::class );

        $this->service = new Player_Service(
            $this->racketmanager,
            $this->player_repository,
            $this->player_error_repository,
            $this->club_role_repository,
            $this->rating_gateway,
            $this->league_team_repository,
            $this->club_repository,
            $this->registration_repository,
            $this->mailer
        );
    }

    public function test_get_latest_wtn_updates_player_on_successful_rating_fetch(): void {
        $player = Player_Test_Factory::create_sparse( [
            'id'           => 42,
            'display_name' => 'Jane Doe',
            'btm'          => '10203040',
        ] );

        $this->player_repository->expects( self::once() )
            ->method( 'find' )
            ->with( 42 )
            ->willReturn( $player );

        $this->rating_gateway->expects( self::once() )
            ->method( 'prepare_env' );

        $wtn_data = array( 'singles' => '10.5', 'doubles' => '12.0' );
        $this->rating_gateway->expects( self::once() )
            ->method( 'fetch_player_wtn' )
            ->with( $player )
            ->willReturn( array(
                'status' => true,
                'value'  => $wtn_data,
            ) );

        $this->player_error_repository->expects( self::once() )
            ->method( 'delete_for_player' )
            ->with( 42 );

        $this->player_repository->expects( self::once() )
            ->method( 'update' )
            ->with( $player, array( 'wtn' => true ) );

        $this->service->get_latest_wtn( 42 );

        self::assertSame( $wtn_data, $player->get_wtn() );
    }

    public function test_get_latest_wtn_logs_error_on_failed_rating_fetch(): void {
        $player = Player_Test_Factory::create_sparse( [
            'id'           => 99,
            'display_name' => 'John Doe',
            'btm'          => '99999999',
        ] );

        $this->player_repository->expects( self::once() )
            ->method( 'find' )
            ->with( 99 )
            ->willReturn( $player );

        $this->rating_gateway->expects( self::once() )
            ->method( 'prepare_env' );

        $this->rating_gateway->expects( self::once() )
            ->method( 'fetch_player_wtn' )
            ->with( $player )
            ->willReturn( array(
                'status'  => false,
                'message' => 'LTA Membership Number not found',
            ) );

        $this->player_error_repository->expects( self::once() )
            ->method( 'delete_for_player' )
            ->with( 99 );

        $this->player_error_repository->expects( self::once() )
            ->method( 'save' )
            ->with( self::callback( function( $error ) {
                return 99 === $error->player_id && 'LTA Membership Number not found' === $error->message;
            } ) );

        $this->player_repository->expects( self::never() )
            ->method( 'update' );

        $this->expectException( \Racketmanager\Exceptions\WTN_Error_Exception::class );

        $this->service->get_latest_wtn( 99 );
    }

    public function test_get_latest_wtn_throws_exception_when_player_not_found(): void {
        $this->player_repository->expects( self::once() )
            ->method( 'find' )
            ->with( 999 )
            ->willReturn( null );

        $this->expectException( Player_Not_Found_Exception::class );

        $this->service->get_latest_wtn( 999 );
    }

    public function test_get_latest_wtn_throws_exception_when_btm_is_empty(): void {
        $player = Player_Test_Factory::create_sparse( [
            'id'           => 10,
            'display_name' => 'No BTM Player',
            'btm'          => null,
        ] );

        $this->player_repository->expects( self::once() )
            ->method( 'find' )
            ->with( 10 )
            ->willReturn( $player );

        $this->expectException( LTA_Tennis_Number_Not_Found_Exception::class );

        $this->service->get_latest_wtn( 10 );
    }
}
