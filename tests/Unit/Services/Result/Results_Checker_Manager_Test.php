<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Result;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Result\Results_Checker_Data;
use Racketmanager\Domain\Enums\Results_Checker_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Result\Results_Checker;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Services\Notification\Notification_Service;
use Racketmanager\Services\Result\Results_Checker_Manager;

final class Results_Checker_Manager_Test extends TestCase {

    private Results_Checker_Repository_Interface $repository;
    private Fixture_Result_Manager $result_manager;
    private League_Repository_Interface $league_repository;
    private Fixture_Repository_Interface $fixture_repository;
    private Results_Checker_Manager $manager;

    protected function setUp(): void {
        parent::setUp();

        $this->repository         = $this->createMock( Results_Checker_Repository_Interface::class );
        $this->result_manager     = $this->createStub( Fixture_Result_Manager::class );
        $this->league_repository  = $this->createStub( League_Repository_Interface::class );
        $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );

        $this->manager = new Results_Checker_Manager(
            $this->repository,
            $this->result_manager,
            $this->league_repository,
            $this->fixture_repository
        );
    }

    public function test_approve_sets_status_approved_and_saves(): void {
        $checker = new Results_Checker( new Results_Checker_Data( id: 1, match_id: 10 ) );

        $this->repository->expects( $this->once() )
            ->method( 'save' )
            ->with(
                $this->callback(
                    function ( Results_Checker $saved ) {
                        return $saved->is_approved() && Results_Checker_Status::APPROVED->value === $saved->get_status();
                    }
                )
            );

        $this->manager->approve( $checker );
        $this->assertTrue( $checker->is_approved() );
    }

    public function test_handle_match_error_with_no_league_marks_handled_and_saves(): void {
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $manager           = new Results_Checker_Manager(
            $this->repository,
            $this->result_manager,
            $league_repository,
            $this->fixture_repository
        );

        $checker = new Results_Checker( new Results_Checker_Data( id: 2, league_id: 5, match_id: 15 ) );

        $league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 5 )
            ->willReturn( null );

        $this->repository->expects( $this->never() )->method( 'save' );

        $manager->handle( $checker );
    }

    public function test_handle_match_error_applies_penalty_and_saves(): void {
        $result_manager     = $this->createMock( Fixture_Result_Manager::class );
        $league_repository  = $this->createMock( League_Repository_Interface::class );
        $fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $manager            = new Results_Checker_Manager(
            $this->repository,
            $result_manager,
            $league_repository,
            $fixture_repository
        );

        $checker = new Results_Checker( new Results_Checker_Data( id: 3, league_id: 5, match_id: 20 ) );

        $league = $this->createStub( League::class );
        $league->method( 'get_point_rule' )->willReturn( array( 'result_late' => 2 ) );

        $fixture = new Fixture();
        $fixture->set_id( 20 );
        $fixture->set_home_points( '5.0' );
        $fixture->set_away_points( '3.0' );
        $fixture->set_status( 1 );

        $league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 5 )
            ->willReturn( $league );

        $fixture_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 20 )
            ->willReturn( $fixture );

        $result_manager->expects( $this->once() )
            ->method( 'apply_penalty' )
            ->with( $fixture, 'home', 2 );

        $result_manager->expects( $this->once() )
            ->method( 'update_result' );

        $notification = $this->createMock( Notification_Service::class );
        $notification->expects( $this->once() )
            ->method( 'send_result_error_notification' )
            ->with( $checker, $fixture, 2.0 );

        $manager->set_notification_service( $notification );

        $this->repository->expects( $this->once() )
            ->method( 'save' )
            ->with(
                $this->callback(
                    function ( Results_Checker $saved ) {
                        return $saved->is_handled();
                    }
                )
            );

        $manager->handle( $checker );
        $this->assertTrue( $checker->is_handled() );
    }

    public function test_handle_player_error_applies_penalty_and_comments(): void {
        $result_manager     = $this->createMock( Fixture_Result_Manager::class );
        $league_repository  = $this->createMock( League_Repository_Interface::class );
        $fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $manager            = new Results_Checker_Manager(
            $this->repository,
            $result_manager,
            $league_repository,
            $fixture_repository
        );

        $checker = new Results_Checker( new Results_Checker_Data( id: 4, league_id: 6, match_id: 25, player_id: 99 ) );

        $league = $this->createStub( League::class );
        $league->method( 'get_point_rule' )->willReturn( array( 'player_error' => 1 ) );

        $fixture = new Fixture();
        $fixture->set_id( 25 );
        $fixture->set_home_points( '4.0' );
        $fixture->set_away_points( '2.0' );
        $fixture->set_status( 1 );
        $fixture->set_comments( array( 'result' => 'Existing comment' ) );

        $league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 6 )
            ->willReturn( $league );

        $fixture_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 25 )
            ->willReturn( $fixture );

        $result_manager->expects( $this->once() )
            ->method( 'apply_penalty' )
            ->with( $fixture, 'home', 1 );

        $result_manager->expects( $this->once() )
            ->method( 'update_result' );

        $this->repository->expects( $this->once() )
            ->method( 'save' )
            ->with(
                $this->callback(
                    function ( Results_Checker $saved ) {
                        return $saved->is_handled();
                    }
                )
            );

        $manager->handle( $checker );
        $this->assertTrue( $checker->is_handled() );
        $comments = $fixture->get_comments();
        $this->assertIsArray( $comments );
        $this->assertStringContainsString( 'Existing comment', $comments['result'] );
        $this->assertStringContainsString( 'Points deducted: 1', $comments['result'] );
    }
}
