<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\Cup;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Request_DTO;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Cup\Cup_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Cup_Setup_Action_Dispatcher_Test extends TestCase {

    private Competition_Service $competition_service;
    private Draw_Action_Handler_Interface $championship_admin_service;
    private Action_Guard_Interface $action_guard;
    private Cup_Setup_Action_Dispatcher $dispatcher;

    protected function setUp(): void {
        parent::setUp();
        $this->competition_service        = $this->createMock( Competition_Service::class );
        $this->championship_admin_service = $this->createMock( Draw_Action_Handler_Interface::class );
        $this->action_guard               = $this->createMock( Action_Guard_Interface::class );

        $this->dispatcher = new Cup_Setup_Action_Dispatcher(
            $this->competition_service,
            $this->championship_admin_service,
            $this->action_guard
        );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $result = $this->dispatcher->handle( array(), array() );
        $this->assertNull( $result->message );
    }

    public function test_calculate_ratings_guard_failure(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array( 'rank' => 'calculate_rank' )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Forbidden', $result->message );
    }

    public function test_competition_setup_guard_failure(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array( 'action' => 'save' )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Forbidden', $result->message );
    }

    public function test_league_setup_guard_failure(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Forbidden' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'league_id' => 12, 'season' => 2024 ),
            array( 'action' => 'add', 'league_id' => 12 )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Forbidden', $result->message );
    }

    public function test_league_setup_empty_rounds_returns_error(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'league_id' => 12, 'season' => 2024 ),
            array( 'action' => 'add', 'league_id' => 12, 'rounds' => array() )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'No rounds specified', $result->message );
    }

    public function test_calculate_ratings_success(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_calculate_ratings', 'edit_matches' );

        $this->competition_service->expects( self::once() )
            ->method( 'calculate_team_ratings' )
            ->with( 5, 2024 );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array( 'rank' => 'calculate_rank' )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Cup ratings set', $result->message );
    }

    public function test_calculate_ratings_competition_not_found(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' );

        $this->competition_service->expects( self::once() )
            ->method( 'calculate_team_ratings' )
            ->willThrowException( new Competition_Not_Found_Exception( 'Not found' ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 999, 'season' => 2024 ),
            array( 'rank' => 'calculate_rank' )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertSame( 'Not found', $result->message );
    }

    public function test_competition_setup_invalid_missing_date(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' );

        $competition = $this->createMock( Competition::class );
        $competition->expects( self::once() )
            ->method( 'get_season_by_name' )
            ->willReturn( array( 'match_dates' => array() ) );

        $this->competition_service->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 5 )
            ->willReturn( $competition );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'action' => '',
                'rounds' => array(
                    array( 'round' => '1', 'match_date' => '' ),
                ),
            )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertStringContainsString( 'Match date missing for round 1', $result->message );
        $this->assertNotEmpty( $result->flash_data['form_data'] );
    }

    public function test_competition_setup_invalid_dates_out_of_order(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' );

        $competition = $this->createMock( Competition::class );
        $competition->expects( self::once() )
            ->method( 'get_season_by_name' )
            ->willReturn( array( 'match_dates' => array() ) );

        $this->competition_service->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 5 )
            ->willReturn( $competition );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'action' => '',
                'rounds' => array(
                    array( 'round' => 'Final', 'match_date' => '2024-05-10' ),
                    array( 'round' => 'Semi-final', 'match_date' => '2024-05-15' ),
                ),
            )
        );

        $this->assertSame( Admin_Message_Type::ERROR, $result->message_type );
        $this->assertStringContainsString( 'after next round date', $result->message );
    }

    public function test_competition_setup_success(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' );

        $competition = $this->createMock( Competition::class );
        $competition->expects( self::once() )
            ->method( 'get_season_by_name' )
            ->willReturn( array( 'match_dates' => array() ) );

        $competition->expects( self::once() )
            ->method( 'get_seasons' )
            ->willReturn( array( 2024 => array() ) );

        $competition->expects( self::never() )->method( 'update_seasons' );

        $this->competition_service->expects( self::once() )
            ->method( 'update_seasons' )
            ->with(
                $competition,
                self::callback( function ( array $seasons ) {
                    return isset( $seasons[2024]['match_dates'] )
                        && count( $seasons[2024]['match_dates'] ) === 2
                        && $seasons[2024]['match_dates'][0] === '2024-05-01'
                        && $seasons[2024]['match_dates'][1] === '2024-05-10';
                } )
            )
            ->willReturn( true );

        $this->competition_service->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 5 )
            ->willReturn( $competition );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'season' => 2024 ),
            array(
                'action' => '',
                'rounds' => array(
                    array( 'round' => 'Final', 'match_date' => '2024-05-10' ),
                    array( 'round' => 'Semi-final', 'match_date' => '2024-05-01' ),
                ),
            )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Cup match dates updated', $result->message );
    }

    public function test_league_setup_delegates_to_championship_service(): void {
        $this->action_guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' );

        $this->championship_admin_service->expects( self::once() )
            ->method( 'set_championship_fixtures' )
            ->with( self::callback( function ( Draw_Action_Request_DTO $dto ) {
                return $dto->league_id === 12
                    && $dto->season === '2024'
                    && isset( $dto->post['action'] )
                    && $dto->post['action'] === 'add';
            } ) )
            ->willReturn( new Action_Result_DTO( 'Fixtures added', Admin_Message_Type::SUCCESS ) );

        $result = $this->dispatcher->handle(
            array( 'competition_id' => 5, 'league_id' => 12, 'season' => 2024 ),
            array(
                'action'    => 'add',
                'league_id' => 12,
                'season'    => '2024',
                'rounds'    => array(
                    array( 'round' => 'Final', 'match_date' => '2024-05-10' ),
                ),
            )
        );

        $this->assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        $this->assertSame( 'Fixtures added', $result->message );
    }
}
