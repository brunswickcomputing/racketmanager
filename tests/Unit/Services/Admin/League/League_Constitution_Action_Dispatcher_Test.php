<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Admin\League;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use stdClass;

require_once __DIR__ . '/../../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class League_Constitution_Action_Dispatcher_Test extends TestCase {

    private League_Team_Repository_Interface $league_team_repository;

    protected function setUp(): void {
        parent::setUp();
        $this->league_team_repository = $this->createStub( League_Team_Repository_Interface::class );
    }

    public function test_unhandled_action_returns_empty_dto(): void {
        $guard      = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::never() )->method( 'assert_allowed' );
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );

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

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );
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

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );
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

        $GLOBALS['wp_stubs_league_teams'][7929] = $league_team;

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $repo );
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

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );
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

        $event = new class() {
            public bool $called = false;
            public function promote_and_relegate( array $teams, ?string $season, League_Team_Repository_Interface $repo ): bool {
                $this->called = true;
                return true;
            }
        };

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $repo );
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
        self::assertFalse( $event->called );
    }

    public function test_promote_relegate_success_passes_repository_to_event(): void {
        $guard = $this->createStub( Action_Guard_Interface::class );
        $repo  = $this->createStub( League_Team_Repository_Interface::class );

        $event = new class() {
            public ?League_Team_Repository_Interface $passed_repo = null;
            public ?string $passed_season = null;
            public array $passed_teams = array();
            public function promote_and_relegate( array $teams, ?string $season, League_Team_Repository_Interface $repo ): bool {
                $this->passed_repo   = $repo;
                $this->passed_season = $season;
                $this->passed_teams  = $teams;
                return true;
            }
        };

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $repo );
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
        self::assertSame( $repo, $event->passed_repo );
        self::assertSame( '2026', $event->passed_season );
        self::assertCount( 2, $event->passed_teams );
        self::assertSame( 1, $event->passed_teams[0]->table_id );
        self::assertSame( 1, $event->passed_teams[0]->rank );
        self::assertSame( 2, $event->passed_teams[1]->rank );
    }

    public function test_add_teams_guard_failure(): void {
        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_add-teams-bulk', 'edit_leagues' )
            ->willThrowException( new Invalid_Status_Exception( 'Token failed' ) );

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );
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

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );
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

        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );
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
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );

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
        $dispatcher = new League_Constitution_Action_Dispatcher( $guard, $this->league_team_repository );

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
