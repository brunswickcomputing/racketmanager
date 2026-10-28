<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Setup_Action_Dispatcher_Test extends TestCase {

    public function test_nonce_failure_returns_error_without_loading_competition(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_competition' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add_championship-fixtures', 'edit_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'Security token invalid' ) );

        $result = ( new League_Setup_Action_Dispatcher( $competition_service, $guard, $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array(
                'action' => 'update',
                'season' => '2026',
                'rounds' => array( array( 'round' => '1', 'match_date' => '2026-04-01' ) ),
            )
        );

        self::assertSame( 'Security token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_capability_failure_on_ratings_returns_error(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'calculate_team_ratings' );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_calculate_ratings', 'edit_matches' )
            ->willThrowException( new Invalid_Status_Exception( 'You do not have permission to perform this task' ) );

        $result = ( new League_Setup_Action_Dispatcher( $competition_service, $guard, $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) )->handle(
            array(),
            array( 'rank' => '1', 'competition_id' => '3', 'season' => '2026' )
        );

        self::assertSame( 'You do not have permission to perform this task', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_validation_failure_does_not_persist_seasons(): void {
        $competition = $this->createMock( Competition::class );
        $competition->expects( self::never() )->method( 'update_seasons' );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::never() )->method( 'get_competition' );

        $result = ( new League_Setup_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ), $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array(
                'action' => 'update',
                'season' => '2026',
                'rounds' => array( array( 'round' => '1', 'match_date' => '' ) ),
            )
        );

        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
        self::assertStringContainsString( 'Match date missing for round 1', strval( $result->message ) );
    }

    public function test_successful_match_date_update_cascades_to_events(): void {
        $event = new class() {
            public array $seasons = array( 2026 => array( 'name' => '2026' ) );
            public int $offset = 0;
            public function get_seasons(): array {
                return $this->seasons;
            }
            public function update_seasons( array $seasons ): bool {
                $this->seasons = $seasons;
                return true;
            }
        };

        $competition = $this->createMock( Competition::class );
        $competition->expects( self::once() )->method( 'get_season_by_name' )->with( '2026' )->willReturn( array( 'name' => '2026' ) );
        $competition->expects( self::once() )->method( 'get_seasons' )->willReturn( array( 2026 => array( 'name' => '2026' ) ) );
        $competition->expects( self::never() )->method( 'update_seasons' );

        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'update_seasons' )
            ->with( $competition, array( 2026 => array( 'name' => '2026', 'match_dates' => array( '2026-04-01', '2026-04-08' ) ) ) )
            ->willReturn( true );
        $competition_service->expects( self::once() )->method( 'get_competition' )->with( 3 )->willReturn( $competition );
        $competition_service->expects( self::once() )->method( 'get_events_for_competition' )->with( 3, 2026 )->willReturn( array( $event ) );

        $result = ( new League_Setup_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ), $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) )->handle(
            array( 'competition_id' => '3' ),
            array(
                'action' => 'update',
                'season' => '2026',
                'rounds' => array(
                    array( 'round' => '1', 'match_date' => '2026-04-01' ),
                    array( 'round' => '2', 'match_date' => '2026-04-08' ),
                ),
            )
        );

        self::assertSame( 'Match dates updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( '2026-04-01', '2026-04-08' ), $event->seasons[2026]['match_dates'] );
    }

    public function test_ratings_success_and_season_not_found(): void {
        $competition_service = $this->createMock( Competition_Service::class );
        $competition_service->expects( self::once() )->method( 'calculate_team_ratings' )->with( 3, 2026 );

        $success = ( new League_Setup_Action_Dispatcher( $competition_service, $this->createStub( Action_Guard_Interface::class ), $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) )->handle(
            array(),
            array( 'rank' => '1', 'competition_id' => '3', 'season' => '2026' )
        );

        self::assertSame( 'League ratings set', $success->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $success->message_type );

        $failing = $this->createMock( Competition_Service::class );
        $failing->expects( self::once() )->method( 'calculate_team_ratings' )->willThrowException( new Season_Not_Found_Exception( 'Season missing' ) );

        $error = ( new League_Setup_Action_Dispatcher( $failing, $this->createStub( Action_Guard_Interface::class ), $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class ) ) )->handle(
            array(),
            array( 'rank' => '1', 'competition_id' => '3', 'season' => '2026' )
        );

        self::assertSame( 'Season missing', $error->message );
        self::assertSame( Admin_Message_Type::ERROR, $error->message_type );
    }
}
