<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Cup;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Cup\Cup_Plan_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Plan_Action_Dispatcher_Test extends TestCase {

    private Competition_Service $competition_service;
    private Action_Guard_Interface $action_guard;
    private Cup_Plan_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->action_guard        = $this->createMock( Action_Guard_Interface::class );

        $this->dispatcher = new Cup_Plan_Action_Dispatcher(
            $this->competition_service,
            $this->action_guard
        );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $result = $this->dispatcher->handle( array(), array() );
        $this->assertNull( $result->message );
    }

    public function test_save_cup_config_guard_failure(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array( 'saveCup' => 'Save' )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Forbidden', $result->message );
        $this->assertSame( 'config', $result->tab_override );
    }

    public function test_save_plan_guard_failure(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array( 'savePlan' => 'Save' )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Forbidden', $result->message );
        $this->assertSame( 'matches', $result->tab_override );
    }

    public function test_reset_plan_guard_failure(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array( 'resetPlan' => 'Reset' )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Forbidden', $result->message );
        $this->assertSame( 'matches', $result->tab_override );
    }

    public function test_save_cup_config_wp_error(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_cup-nonce', 'racketmanager_cup', 'edit_teams' );

        $wp_error = new WP_Error( 'startTime', 'Invalid start time' );

        $this->competition_service->expects( self::once() )
            ->method( 'set_plan_config' )
            ->with( 5, 2024, '10:00', 4, '01:00:00' )
            ->willReturn( $wp_error );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'saveCup'             => 'Save',
                'startTime'           => '10:00',
                'numCourtsAvailable'  => 4,
                'timeIncrement'       => '01:00:00',
            )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Error with plan details', $result->message );
        $this->assertSame( 'config', $result->tab_override );
        $this->assertContains( 'startTime', $result->flash_data['err_flds'] );
    }

    public function test_save_cup_config_success(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' );

        $this->competition_service->expects( self::once() )
            ->method( 'set_plan_config' )
            ->with( 5, 2024, '10:00', 4, '01:00:00' )
            ->willReturn( 1 );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'saveCup'             => 'Save',
                'startTime'           => '10:00',
                'numCourtsAvailable'  => 4,
                'timeIncrement'       => '01:00:00',
            )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Plan updated', $result->message );
        $this->assertSame( 'config', $result->tab_override );
    }

    public function test_save_plan_success(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_cup-planner-nonce', 'racketmanager_cup-planner', 'edit_teams' );

        $this->competition_service->expects( self::once() )
            ->method( 'save_plan' )
            ->with( 5, 2024, array( 1, 2 ), array( '10:00' ), array( '1' => 101 ), array( '1' => '10:00' ) )
            ->willReturn( 1 );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'savePlan'  => 'Save',
                'court'     => array( 1, 2 ),
                'startTime' => array( '10:00' ),
                'match'     => array( '1' => 101 ),
                'matchtime' => array( '1' => '10:00' ),
            )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Plan saved', $result->message );
        $this->assertSame( 'matches', $result->tab_override );
    }

    public function test_reset_plan_success(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_cup-planner-nonce', 'racketmanager_cup-planner', 'edit_teams' );

        $this->competition_service->expects( self::once() )
            ->method( 'reset_plan' )
            ->with( 5, 2024, array( 101, 102 ) )
            ->willReturn( 1 );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'resetPlan' => 'Reset',
                'match'     => array( 101, 102 ),
            )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Plan reset', $result->message );
        $this->assertSame( 'matches', $result->tab_override );
    }

    public function test_save_plan_competition_not_updated_warning(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' );

        $this->competition_service->expects( self::once() )
            ->method( 'save_plan' )
            ->willThrowException( new Competition_Not_Updated_Exception( 'No change' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'savePlan'  => 'Save',
                'court'     => array( 1 ),
                'startTime' => array( '10:00' ),
                'match'     => array( 101 ),
                'matchtime' => array( '10:00' ),
            )
        );

        $this->assertSame( Admin_Message_Type::WARNING, $result->message_type );
        $this->assertSame( 'No change', $result->message );
        $this->assertSame( 'matches', $result->tab_override );
    }

    public function test_reset_plan_competition_not_updated_warning(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' );

        $this->competition_service->expects( self::once() )
            ->method( 'reset_plan' )
            ->willThrowException( new Competition_Not_Updated_Exception( 'Reset failed' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'resetPlan' => 'Reset',
                'match'     => array( 101 ),
            )
        );

        $this->assertSame( Admin_Message_Type::WARNING, $result->message_type );
        $this->assertSame( 'Reset failed', $result->message );
        $this->assertSame( 'matches', $result->tab_override );
    }
}
