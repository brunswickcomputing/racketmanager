<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Results_Checker_Tab_Handler;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Results_Checker_Presenter;
use Racketmanager\Presentation\View_Models\Results_Checker_View_Model;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Application\Admin\Ports\Results_Checker_Action_Dispatcher_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Results_Checker_Tab_Handler_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Results_Checker_Repository_Interface $repository;
    private Results_Checker_Presenter $presenter;
    private Competition_Service $competition_service;
    private Season_Service $season_service;
    private Results_Checker_Action_Dispatcher_Interface $dispatcher;
    private Results_Checker_Tab_Handler $handler;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->repository          = $this->createMock( Results_Checker_Repository_Interface::class );
        $this->presenter           = $this->createMock( Results_Checker_Presenter::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->season_service      = $this->createMock( Season_Service::class );
        $this->dispatcher          = $this->createMock( Results_Checker_Action_Dispatcher_Interface::class );

        $this->handler = new Results_Checker_Tab_Handler(
            $this->repository,
            $this->presenter,
            $this->competition_service,
            $this->season_service,
            $this->dispatcher
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_handle_mutation_delegates_to_dispatcher(): void {
        $post   = array( 'doResultsChecker' => '1', 'check' => array( 1, 2 ) );
        $dto    = new Action_Result_DTO( 'Approved', Admin_Message_Type::SUCCESS, 'resultschecker' );

        $this->dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->with( $post )
            ->willReturn( $dto );

        $result = $this->handler->handle_mutation( $post );

        self::assertSame( $dto, $result );
    }

    public function test_get_tab_data_with_defaults(): void {
        $this->repository->expects( self::once() )
            ->method( 'find_all' )
            ->with(
                array(
                    'season'      => '',
                    'competition' => '',
                    'event'       => '',
                    'status'      => 'outstanding',
                )
            )
            ->willReturn( array( (object) array( 'id' => 1 ) ) );

        $vm = new Results_Checker_View_Model(
            id: 1,
            formatted_date: '2026-04-10',
            match_link: 'https://example.test',
            match_title: 'Title',
            team_title: 'Team',
            player_link: 'https://example.test',
            player_name: 'Player',
            description: 'Desc',
            status_desc: 'Status',
            tooltip: 'Tip',
            show_status: true,
        );

        $this->presenter->expects( self::once() )
            ->method( 'present_collection' )
            ->with( array( (object) array( 'id' => 1 ) ), 'outstanding' )
            ->willReturn( array( $vm ) );

        $comp = (object) array( 'id' => 5 );
        $this->competition_service->expects( self::once() )
            ->method( 'get_league_competitions' )
            ->willReturn( array( $comp ) );

        $event = (object) array( 'id' => 10 );
        $this->competition_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 5 )
            ->willReturn( array( $event ) );

        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array( '2025' ) );

        $data = $this->handler->get_tab_data( array() );

        self::assertSame( array( $vm ), $data['results_checkers'] );
        self::assertSame( array( $comp ), $data['competitions'] );
        self::assertSame( array( $event ), $data['events'] );
        self::assertSame( array( '2025' ), $data['seasons'] );
        self::assertSame( '', $data['season_select'] );
        self::assertSame( '', $data['competition_select'] );
        self::assertSame( '', $data['event_select'] );
        self::assertSame( 'outstanding', $data['results_check_filter'] );
    }

    public function test_get_tab_data_with_filters_and_handles_competition_not_found(): void {
        $this->repository->expects( self::once() )
            ->method( 'find_all' )
            ->with(
                array(
                    'season'      => '2025',
                    'competition' => 10,
                    'event'       => 20,
                    'status'      => 'approved',
                )
            )
            ->willReturn( array() );

        $this->presenter->expects( self::once() )
            ->method( 'present_collection' )
            ->with( array(), 'approved' )
            ->willReturn( array() );

        $comp = (object) array( 'id' => 10 );
        $this->competition_service->expects( self::once() )
            ->method( 'get_league_competitions' )
            ->willReturn( array( $comp ) );

        $this->competition_service->expects( self::once() )
            ->method( 'get_events_for_competition' )
            ->with( 10 )
            ->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $data = $this->handler->get_tab_data(
            array(
                'season'               => '2025',
                'competition'          => '10',
                'event'                => '20',
                'filterResultsChecker' => 'approved',
            )
        );

        self::assertSame( array(), $data['events'] );
        self::assertSame( '2025', $data['season_select'] );
        self::assertSame( 10, $data['competition_select'] );
        self::assertSame( 20, $data['event_select'] );
        self::assertSame( 'approved', $data['results_check_filter'] );
    }
}
