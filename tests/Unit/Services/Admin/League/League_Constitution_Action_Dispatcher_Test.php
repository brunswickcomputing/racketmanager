<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Constitution_Action_Dispatcher_Test extends TestCase {

    public function test_unhandled_action_returns_empty_dto(): void {
        $guard      = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );

        $result = $dispatcher->handle( array(), array() );

        self::assertNull( $result->message );
        self::assertNull( $result->message_type );
    }

    public function test_delete_teams_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'constitution-bulk', 'del_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Permission denied' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doActionConstitution' => '1',
                'action'               => 'delete',
            )
        );

        self::assertSame( 'Permission denied', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_save_constitution_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'saveConstitution' => '1',
            )
        );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_promote_relegate_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'promoteRelegate' => '1',
            )
        );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_promote_relegate_already_occurred(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );

        $result = $dispatcher->handle(
            array( 'event_id' => '10' ),
            array(
                'promoteRelegate' => '1',
                'table_id'        => array( 1 ),
                'status'          => array( 1 => 'P' ),
                'team_id'         => array( 1 => 5 ),
                'league_id'       => array( 1 => 2 ),
                'old_rank'        => array( 1 => 1 ),
                'rank'            => array( 1 => 0 ),
                'event_id'        => 10,
            )
        );

        self::assertSame( 'Promotion and relegation has already occurred', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_teams_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Token invalid' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'action' => 'addTeamsToLeague',
            )
        );

        self::assertSame( 'Token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_teams_empty_warning(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );

        $result = $dispatcher->handle(
            array(),
            array(
                'action' => 'addTeamsToLeague',
                'team'   => array(),
            )
        );

        self::assertSame( 'No teams to add', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_generate_matches_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Token invalid' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );
        $result     = $dispatcher->handle(
            array(),
            array(
                'generate_matches' => '1',
            )
        );

        self::assertSame( 'Token invalid', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_generate_matches_missing_event_or_season(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );

        $result1 = $dispatcher->handle(
            array(),
            array(
                'generate_matches' => '1',
            )
        );
        self::assertSame( 'No event set', $result1->message );
        self::assertSame( Admin_Message_Type::ERROR, $result1->message_type );

        $result2 = $dispatcher->handle(
            array( 'event_id' => '5' ),
            array(
                'generate_matches' => '1',
            )
        );
        self::assertSame( 'No season set', $result2->message );
        self::assertSame( Admin_Message_Type::ERROR, $result2->message_type );
    }

    public function test_generate_matches_success(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard );

        $event = new class() {
            public int $called = 0;
            public function generate_box_league_matches(): void {
                $this->called++;
            }
        };

        $result = $dispatcher->handle(
            array(),
            array(
                'generate_matches' => '1',
                'event_id'         => 12,
                'latest_season'    => 2026,
            ),
            $event
        );

        self::assertSame( 1, $event->called );
        self::assertSame( 'Matches generated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }
}
