<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Schedule_Service as Competition_League_Schedule_Service;
use Racketmanager\Services\Competition\Promotion_Relegation_Service;
use Racketmanager\Services\League_Service;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Constitution_Action_Dispatcher_Test extends TestCase {

    private League_Team_Repository_Interface $league_team_repository;
    private Competition_League_Schedule_Service $league_schedule_service;
    private League_Repository_Interface $league_repository;
    private Event_Repository_Interface $event_repository;
    private Team_Repository_Interface $team_repository;
    private Promotion_Relegation_Service $promotion_relegation_service;

    protected function setUp(): void {
        parent::setUp();
        $this->league_team_repository  = $this->createStub( League_Team_Repository_Interface::class );
        $this->league_schedule_service = $this->createStub( Competition_League_Schedule_Service::class );
        $this->league_repository       = $this->createStub( League_Repository_Interface::class );
        $this->event_repository        = $this->createStub( Event_Repository_Interface::class );
        $this->team_repository         = $this->createStub( Team_Repository_Interface::class );
        $this->promotion_relegation_service = $this->createStub( Promotion_Relegation_Service::class );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $guard      = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );

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

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
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

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'saveConstitution' => '1',
            )
        );

        self::assertSame( 'Invalid token', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_save_constitution_update_persists_rank_and_returns_success(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );
        $repo  = $this->createMock( League_Team_Repository_Interface::class );

        $repo->expects( self::once() )
            ->method( 'set_constitution_rank' )
            ->with( 7929, 2347, 2, 'W1', '1' )
            ->willReturn( true );

        $league_team = new League_Team( (object) [
            'id'        => 1532,
            'table_id'  => 7929,
            'league_id' => 2000,
            'rank'      => 1,
            'status'    => '',
            'profile'   => '0',
        ] );

        $repo->expects( self::atLeastOnce() )->method( 'find_by_id' )->with( 7929 )->willReturn( $league_team );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $repo, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'saveConstitution'    => '1',
                'constitutionAction'  => 'update',
                'table_id'            => array( 7929 ),
                'league_id'           => array( 7929 => 2347 ),
                'rank'                => array( 7929 => 2 ),
                'status'              => array( 7929 => 'W1' ),
                'profile'             => array( 7929 => '1' ),
            )
        );

        self::assertSame( 'Updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( 2347, $league_team->league_id );
        self::assertSame( 2, $league_team->rank );
        self::assertSame( 'W1', $league_team->status );
        self::assertSame( 1, $league_team->get_profile() );
    }

    public function test_promote_relegate_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'constitution-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Invalid token' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
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
        $repo  = $this->createMock( League_Team_Repository_Interface::class );
        $repo->expects( self::never() )->method( 'set_constitution_rank' );

        $event = $this->createMock( Event::class );
        $promotion_relegation_service = $this->createMock( Promotion_Relegation_Service::class );
        $promotion_relegation_service->expects( self::never() )->method( 'promote_and_relegate' );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $repo, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'promoteRelegate' => '1',
                'js-active'       => 1,
                'latest_season'   => '2026',
                'event_id'        => 5,
                'table_id'        => array( 1 ),
                'team_id'         => array( 1 => 11 ),
                'league_id'       => array( 1 => 10 ),
                'status'          => array( 1 => 'P' ),
            ),
            $event
        );

        self::assertSame( 'Promotion and relegation has already occurred', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_promote_relegate_success_passes_int_season_and_event_to_service(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );
        $repo  = $this->createStub( League_Team_Repository_Interface::class );

        $passed_teams = array();
        $event        = $this->createMock( Event::class );
        $promotion_relegation_service = $this->createMock( Promotion_Relegation_Service::class );
        $promotion_relegation_service->expects( self::once() )
            ->method( 'promote_and_relegate' )
            ->with(
                self::identicalTo( $event ),
                self::callback( static function ( array $teams ) use ( &$passed_teams ): bool {
                    $passed_teams = $teams;
                    return true;
                } ),
                self::identicalTo( 2026 )
            )
            ->willReturn( true );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $repo, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'promoteRelegate' => '1',
                'js-active'       => 1,
                'latest_season'   => '2026',
                'event_id'        => 5,
                'table_id'        => array( 1, 2 ),
                'team_id'         => array( 1 => 11, 2 => 12 ),
                'league_id'       => array( 1 => 10, 2 => 10 ),
                'old_rank'        => array( 1 => 1, 2 => 2 ),
                'status'          => array( 1 => '', 2 => '' ),
            ),
            $event
        );

        self::assertSame( 'Promotion and relegation actioned', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertCount( 2, $passed_teams );
        self::assertSame( 1, $passed_teams[0]->table_id );
        self::assertSame( 1, $passed_teams[0]->rank );
        self::assertSame( 2, $passed_teams[1]->rank );
    }

    public function test_promote_relegate_reports_event_failure_as_error(): void {
        $event = $this->createMock( Event::class );
        $promotion_relegation_service = $this->createMock( Promotion_Relegation_Service::class );
        $promotion_relegation_service->expects( self::once() )->method( 'promote_and_relegate' )->willReturn( false );

        $dispatcher = new League_Constitution_Action_Dispatcher( $this->createStub( Action_Guard_Interface::class ), $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'promoteRelegate' => '1',
                'js-active'       => 1,
                'latest_season'   => '2026',
                'table_id'        => array( 1 ),
                'team_id'         => array( 1 => 11 ),
                'league_id'       => array( 1 => 10 ),
                'old_rank'        => array( 1 => 1 ),
                'status'          => array( 1 => '' ),
            ),
            $event
        );

        self::assertSame( 'Error with promotion and relegation', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function invalid_seasons(): array {
        return array(
            'missing' => array( array() ),
            'empty'   => array( array( 'latest_season' => '' ) ),
            'text'    => array( array( 'latest_season' => 'summer' ) ),
        );
    }

    #[DataProvider( 'invalid_seasons' )]
    public function test_promote_relegate_without_valid_season_returns_error_before_mutation( array $season_post ): void {
        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( self::never() )->method( 'update_constitution' );
        $event = $this->createMock( Event::class );
        $promotion_relegation_service = $this->createMock( Promotion_Relegation_Service::class );
        $promotion_relegation_service->expects( self::never() )->method( 'promote_and_relegate' );

        $dispatcher = new League_Constitution_Action_Dispatcher( $this->createStub( Action_Guard_Interface::class ), $league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array_merge(
                array(
                    'promoteRelegate' => '1',
                    'js-active'       => 1,
                    'table_id'        => array( 1 ),
                    'team_id'         => array( 1 => 11 ),
                    'league_id'       => array( 1 => 10 ),
                    'old_rank'        => array( 1 => 1 ),
                    'status'          => array( 1 => '' ),
                ),
                $season_post
            ),
            $event
        );

        self::assertSame( 'No season set', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_promote_relegate_runs_real_event_with_fresh_leagues_and_forwarded_repositories(): void {
        $competition = $this->createMock( Competition::class );
        $competition->expects( self::once() )
            ->method( 'get_season_by_name' )
            ->with( '2026' )
            ->willReturn( array(
                'teams_prom_relg'  => 2,
                'teams_per_club'   => 2,
                'max_teams'        => 4,
                'lowest_promotion' => 1,
            ) );
        $event = new Event( (object) array( 'id' => 93, 'name' => 'Mixed U10 Summer', 'type' => 'XD' ), $competition );

        $league                  = $this->createStub( League::class );
        $league->id              = 50;
        $league->num_teams_total = 4;

        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( self::once() )
            ->method( 'query_leagues' )
            ->with( 93, self::callback( static fn( array $args ): bool => '2026' === $args['season'] ) )
            ->willReturn( array( (object) array( 'id' => 50 ) ) );

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( self::once() )->method( 'find_by_id' )->with( 50 )->willReturn( $league );
        $league_repository->expects( self::once() )
            ->method( 'query_league_teams' )
            ->with(
                50,
                array(
                    'club'   => 7,
                    'season' => '2026',
                    'count'  => true,
                    'cache'  => false,
                ),
                true
            )
            ->willReturn( 1 );

        $team_entity          = new Team();
        $team_entity->club_id = 7;
        $team_repository      = $this->createMock( Team_Repository_Interface::class );
        $team_repository->expects( self::once() )->method( 'find_by_id' )->with( '34' )->willReturn( $team_entity );

        $writes                 = array();
        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( self::once() )
            ->method( 'update_constitution' )
            ->willReturnCallback( static function ( int $table_id, int $league_id, string $status ) use ( &$writes ): bool {
                $writes[] = array( $table_id, $league_id, $status );
                return true;
            } );

        $promotion_relegation_service = new Promotion_Relegation_Service(
            $league_team_repository,
            $league_repository,
            $event_repository,
            $team_repository
        );

        $dispatcher = new League_Constitution_Action_Dispatcher( $this->createStub( Action_Guard_Interface::class ), $league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $league_repository, $event_repository, $team_repository, $promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'promoteRelegate' => '1',
                'js-active'       => '1',
                'latest_season'   => '2026',
                'table_id'        => array( '78' ),
                'team_id'         => array( '78' => '34' ),
                'league_id'       => array( '78' => '50' ),
                'old_rank'        => array( '78' => '4' ),
                'status'          => array( '78' => '' ),
            ),
            $event
        );

        self::assertSame( 'Promotion and relegation actioned', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( array( 78, 50, 'BT' ) ), $writes );
    }

    public function test_promote_relegate_with_unknown_team_writes_nothing(): void {
        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_season_by_name' )->willReturn( array(
            'teams_prom_relg'  => 2,
            'teams_per_club'   => 2,
            'max_teams'        => 4,
            'lowest_promotion' => 1,
        ) );
        $event = new Event( (object) array( 'id' => 93, 'name' => 'Mixed U10 Summer', 'type' => 'XD' ), $competition );

        $league     = $this->createStub( League::class );
        $league->id = 50;
        $event_repository = $this->createStub( Event_Repository_Interface::class );
        $event_repository->method( 'query_leagues' )->willReturn( array( (object) array( 'id' => 50 ) ) );
        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'find_by_id' )->willReturn( $league );

        $known_team          = new Team();
        $known_team->club_id = 7;
        $team_repository     = $this->createStub( Team_Repository_Interface::class );
        $team_repository->method( 'find_by_id' )->willReturnCallback( static fn( $id ) => '34' === $id ? $known_team : null );

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( self::never() )->method( 'update_constitution' );

        $promotion_relegation_service = new Promotion_Relegation_Service(
            $league_team_repository,
            $league_repository,
            $event_repository,
            $team_repository
        );

        $dispatcher = new League_Constitution_Action_Dispatcher( $this->createStub( Action_Guard_Interface::class ), $league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $league_repository, $event_repository, $team_repository, $promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'promoteRelegate' => '1',
                'js-active'       => '1',
                'latest_season'   => '2026',
                'table_id'        => array( '78', '79' ),
                'team_id'         => array( '78' => '34', '79' => '999' ),
                'league_id'       => array( '78' => '50', '79' => '50' ),
                'old_rank'        => array( '78' => '4', '79' => '1' ),
                'status'          => array( '78' => '', '79' => '' ),
            ),
            $event
        );

        self::assertSame( 'Error with promotion and relegation', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_teams_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Token failed' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'action' => 'addTeamsToLeague',
            )
        );

        self::assertSame( 'Token failed', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_teams_empty_warning(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'action'    => 'addTeamsToLeague',
                'league_id' => 5,
                'team'      => array(),
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

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
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
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );

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
        $guard                   = $this->createStub( Action_Guard_Interface::class );
        $league_schedule_service = $this->createMock( Competition_League_Schedule_Service::class );
        $dispatcher              = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $this->createStub( League_Service::class ), $league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );

        $event = new stdClass();

        $league_schedule_service->expects( self::once() )
            ->method( 'generate_box_league_matches' )
            ->with( $event )
            ->willReturn( array( 'scheduled' => 1, 'skipped' => 0 ) );

        $result = $dispatcher->handle(
            array(),
            array(
                'generate_matches' => '1',
                'event_id'         => 12,
                'latest_season'    => 2026,
            ),
            $event
        );

        self::assertSame( 'Matches generated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
        self::assertSame( array( 'scheduled' => 1, 'skipped' => 0 ), $result->flash_data );
    }

    public function test_generate_matches_reports_empty_event(): void {
        $dispatcher = new League_Constitution_Action_Dispatcher( $this->createStub( Action_Guard_Interface::class ), $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
        $this->league_schedule_service->method( 'generate_box_league_matches' )->willReturn( array( 'scheduled' => 0, 'skipped' => 0 ) );

        $result = $dispatcher->handle( array(), array( 'generate_matches' => '1', 'event_id' => 12, 'latest_season' => 2026 ), new stdClass() );

        self::assertSame( 'No leagues scheduled', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_generate_matches_warns_when_some_leagues_are_skipped(): void {
        $dispatcher = new League_Constitution_Action_Dispatcher( $this->createStub( Action_Guard_Interface::class ), $this->league_team_repository, $this->createStub( League_Service::class ), $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
        $this->league_schedule_service->method( 'generate_box_league_matches' )->willReturn( array( 'scheduled' => 1, 'skipped' => 2 ) );

        $result = $dispatcher->handle( array(), array( 'generate_matches' => '1', 'event_id' => 12, 'latest_season' => 2026 ), new stdClass() );

        self::assertSame( 'Matches generated (2 leagues skipped)', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
        self::assertSame( array( 'scheduled' => 1, 'skipped' => 2 ), $result->flash_data );
    }

    public function test_delete_teams_delegates_to_league_service_success(): void {
        $guard          = $this->createStub( Action_Guard_Interface::class );
        $league_service = $this->createMock( League_Service::class );

        $league     = $this->createMock( \Racketmanager\Domain\Competition\League::class );
        $league->id = 7;
        $this->league_repository->method( 'find_by_id' )->willReturnCallback( static fn( int $id ) => 7 === $id ? $league : null );

        $league_service->expects( self::once() )
            ->method( 'delete_team' )
            ->with( $league, 15, '2026' );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $league_service, $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doActionConstitution' => '1',
                'action'               => 'delete',
                'table'                => array( 'tbl1' ),
                'team_id'              => array( 'tbl1' => '15' ),
                'league_id'            => array( 'tbl1' => '7' ),
                'latest_season'        => '2026',
            )
        );

        self::assertSame( '15 deleted', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_delete_teams_returns_error_when_league_service_throws(): void {
        $guard          = $this->createStub( Action_Guard_Interface::class );
        $league_service = $this->createMock( League_Service::class );

        $league     = $this->createMock( \Racketmanager\Domain\Competition\League::class );
        $league->id = 7;
        $this->league_repository->method( 'find_by_id' )->willReturnCallback( static fn( int $id ) => 7 === $id ? $league : null );

        $league_service->expects( self::once() )
            ->method( 'delete_team' )
            ->willThrowException( new \RuntimeException( 'Failed to cascade fixture deletion' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository, $league_service, $this->league_schedule_service, $this->league_repository, $this->event_repository, $this->team_repository, $this->promotion_relegation_service );
        $result     = $dispatcher->handle(
            array(),
            array(
                'doActionConstitution' => '1',
                'action'               => 'delete',
                'table'                => array( 'tbl1' ),
                'team_id'              => array( 'tbl1' => '15' ),
                'league_id'            => array( 'tbl1' => '7' ),
                'latest_season'        => '2026',
            )
        );

        self::assertSame( 'Failed to cascade fixture deletion', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_save_constitution_insert_invokes_league_repository_and_returns_success(): void {
        $guard             = $this->createStub( Action_Guard_Interface::class );
        $league_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );

        $league_repository->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( 200, 10, '2026', '1', 'A', 0 )
            ->willReturn( 1 );

        $league     = new \Racketmanager\Domain\Competition\League( (object) array( 'id' => 200, 'title' => 'Div 1' ) );
        $league_repository->expects( self::atLeastOnce() )->method( 'find_by_id' )->with( 200 )->willReturn( $league );

        $dispatcher = new League_Constitution_Action_Dispatcher(
            $guard,
            $this->league_team_repository,
            $this->createStub( League_Service::class ),
            $this->league_schedule_service,
            $league_repository,
            $this->event_repository,
            $this->team_repository,
            $this->promotion_relegation_service
        );

        $result = $dispatcher->handle(
            array(),
            array(
                'saveConstitution'   => '1',
                'constitutionAction' => 'insert',
                'table_id'           => array( 'new_1' ),
                'team_id'            => array( 'new_1' => 10 ),
                'league_id'          => array( 'new_1' => 200 ),
                'rank'               => array( 'new_1' => 1 ),
                'status'             => array( 'new_1' => 'A' ),
                'latest_season'      => '2026',
            )
        );

        self::assertSame( 'Updated', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_save_constitution_insert_failure_returns_no_updates(): void {
        $guard             = $this->createStub( Action_Guard_Interface::class );
        $league_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );

        $league_repository->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( 200, 10, '2026', '1', 'A', 0 )
            ->willReturn( false );

        $league     = new \Racketmanager\Domain\Competition\League( (object) array( 'id' => 200, 'title' => 'Div 1' ) );
        $league_repository->expects( self::atLeastOnce() )->method( 'find_by_id' )->with( 200 )->willReturn( $league );

        $dispatcher = new League_Constitution_Action_Dispatcher(
            $guard,
            $this->league_team_repository,
            $this->createStub( League_Service::class ),
            $this->league_schedule_service,
            $league_repository,
            $this->event_repository,
            $this->team_repository,
            $this->promotion_relegation_service
        );

        $result = $dispatcher->handle(
            array(),
            array(
                'saveConstitution'   => '1',
                'constitutionAction' => 'insert',
                'table_id'           => array( 'new_1' ),
                'team_id'            => array( 'new_1' => 10 ),
                'league_id'          => array( 'new_1' => 200 ),
                'rank'               => array( 'new_1' => 1 ),
                'status'             => array( 'new_1' => 'A' ),
                'latest_season'      => '2026',
            )
        );

        self::assertSame( 'No updates', $result->message );
        self::assertSame( Admin_Message_Type::WARNING, $result->message_type );
    }

    public function test_add_teams_invokes_league_repository_and_returns_success(): void {
        $guard             = $this->createStub( Action_Guard_Interface::class );
        $league_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );

        $league_repository->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( 200, 10, '2026', '99', 'NT', 1 )
            ->willReturn( 1 );

        $league     = new \Racketmanager\Domain\Competition\League( (object) array( 'id' => 200, 'title' => 'Div 1' ) );
        $league_repository->expects( self::atLeastOnce() )->method( 'find_by_id' )->with( 200 )->willReturn( $league );
        $this->team_repository->method( 'find_by_id' )->willReturnCallback( static fn( int $id ) => 10 === $id ? new Team( (object) array( 'id' => 10, 'title' => 'Team Ten' ) ) : null );

        $dispatcher = new League_Constitution_Action_Dispatcher(
            $guard,
            $this->league_team_repository,
            $this->createStub( League_Service::class ),
            $this->league_schedule_service,
            $league_repository,
            $this->event_repository,
            $this->team_repository,
            $this->promotion_relegation_service
        );

        $result = $dispatcher->handle(
            array(),
            array(
                'action'    => 'addTeamsToLeague',
                'league_id' => 200,
                'season'    => '2026',
                'team'      => array( 10 ),
            )
        );

        self::assertSame( 'Team Ten added', $result->message );
        self::assertSame( Admin_Message_Type::SUCCESS, $result->message_type );
    }

    public function test_add_teams_returns_error_when_league_not_found(): void {
        $league_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $league_repository->expects( self::once() )->method( 'find_by_id' )->with( 404 )->willReturn( null );
        $league_repository->expects( self::never() )->method( 'add_team_to_league' );

        $dispatcher = new League_Constitution_Action_Dispatcher(
            $this->createStub( Action_Guard_Interface::class ),
            $this->league_team_repository,
            $this->createStub( League_Service::class ),
            $this->league_schedule_service,
            $league_repository,
            $this->event_repository,
            $this->team_repository,
            $this->promotion_relegation_service
        );

        $result = $dispatcher->handle(
            array(),
            array(
                'action'    => 'addTeamsToLeague',
                'league_id' => 404,
                'season'    => '2026',
                'team'      => array( 10 ),
            )
        );

        self::assertStringContainsString( 'could not be added', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }

    public function test_add_teams_failure_returns_error(): void {
        $guard             = $this->createStub( Action_Guard_Interface::class );
        $league_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );

        $league_repository->expects( self::once() )
            ->method( 'add_team_to_league' )
            ->with( 200, 10, '2026', '99', 'NT', 1 )
            ->willReturn( false );

        $league     = new \Racketmanager\Domain\Competition\League( (object) array( 'id' => 200, 'title' => 'Div 1' ) );
        $league_repository->expects( self::atLeastOnce() )->method( 'find_by_id' )->with( 200 )->willReturn( $league );

        $dispatcher = new League_Constitution_Action_Dispatcher(
            $guard,
            $this->league_team_repository,
            $this->createStub( League_Service::class ),
            $this->league_schedule_service,
            $league_repository,
            $this->event_repository,
            $this->team_repository,
            $this->promotion_relegation_service
        );

        $result = $dispatcher->handle(
            array(),
            array(
                'action'    => 'addTeamsToLeague',
                'league_id' => 200,
                'season'    => '2026',
                'team'      => array( 10 ),
            )
        );

        self::assertStringContainsString( 'could not be added', $result->message );
        self::assertSame( Admin_Message_Type::ERROR, $result->message_type );
    }
}
