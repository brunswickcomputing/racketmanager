<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Team\Team;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Club;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

/**
 * Covers the repository-backed lookups that replaced the legacy get_* helpers in Validator.
 */
final class Validator_Repository_Lookup_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        unset( $GLOBALS['racketmanager'] );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function make_validator(
        ?Team_Repository_Interface $team_repository = null,
        ?Player_Repository_Interface $player_repository = null,
        ?Club_Repository_Interface $club_repository = null,
        ?Event_Repository_Interface $event_repository = null,
        ?League_Repository_Interface $league_repository = null,
        ?User_Repository_Interface $user_repository = null
    ): Validator {
        return new Validator(
            null,
            null,
            null,
            null,
            $team_repository,
            $player_repository,
            $club_repository,
            $event_repository,
            $league_repository,
            $user_repository
        );
    }

    private function make_player( int $id, string $display_name ): Player {
        return new Player( new Player_Hydration_DTO( id: $id, display_name: $display_name ) );
    }

    // ---------------------------------------------------------------- team()

    public function test_team_sets_not_found_error_with_404_when_repository_returns_null(): void {
        $team_repository = $this->createMock( Team_Repository_Interface::class );
        $team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( null );

        $validator = $this->make_validator( team_repository: $team_repository );
        $result    = $validator->team( 5 );

        $this->assertSame( $validator, $result );
        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'contactno' ], $validator->err_flds );
        $this->assertSame( [ 'Team not found' ], $validator->err_msgs );
        $this->assertSame( [ 0 => 404 ], $validator->err_statuses );
        $this->assertSame( 404, $validator->status );
    }

    public function test_team_passes_when_repository_returns_team(): void {
        $team_repository = $this->createMock( Team_Repository_Interface::class );
        $team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( new Team() );

        $validator = $this->make_validator( team_repository: $team_repository );
        $validator->team( 5 );

        $this->assertFalse( $validator->error );
        $this->assertSame( [], $validator->err_flds );
        $this->assertSame( [], $validator->err_msgs );
        $this->assertNull( $validator->status );
    }

    public function test_team_with_empty_id_does_not_consult_repository(): void {
        $team_repository = $this->createMock( Team_Repository_Interface::class );
        $team_repository->expects( $this->never() )->method( 'find_by_id' );

        $validator = $this->make_validator( team_repository: $team_repository );
        $validator->team( null );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'Team id not found' ], $validator->err_msgs );
        $this->assertSame( 404, $validator->status );
    }

    // -------------------------------------------------------------- player()

    public function test_player_uses_player_repository_find(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )->method( 'find' )->with( 7 )->willReturn( null );

        $validator = $this->make_validator( player_repository: $player_repository );
        $validator->player( 7 );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'contactno' ], $validator->err_flds );
        $this->assertSame( [ 'Player not found' ], $validator->err_msgs );
    }

    public function test_player_passes_when_found(): void {
        $player_repository = $this->createStub( Player_Repository_Interface::class );
        $player_repository->method( 'find' )->willReturn( $this->make_player( 7, 'Jane Doe' ) );

        $validator = $this->make_validator( player_repository: $player_repository );
        $validator->player( 7 );

        $this->assertFalse( $validator->error );
    }

    // --------------------------------------------------------------- email()

    public function test_email_flags_address_used_by_other_player(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 'a@b.c', 'email' )
            ->willReturn( $this->make_player( 99, 'Jane Doe' ) );

        $validator = $this->make_validator( player_repository: $player_repository );
        $validator->email( 'a@b.c', 7 );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'contactemail' ], $validator->err_flds );
        $this->assertSame( [ 'Email address already used by Jane Doe' ], $validator->err_msgs );
        $this->assertSame( 'Email address already used by Jane Doe', $validator->err->get_error_message( 'contactemail' ) );
    }

    public function test_email_allows_address_owned_by_same_player(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 'a@b.c', 'email' )
            ->willReturn( $this->make_player( 7, 'Jane Doe' ) );

        $validator = $this->make_validator( player_repository: $player_repository );
        $validator->email( 'a@b.c', 7 );

        $this->assertFalse( $validator->error );
        $this->assertSame( [], $validator->err_msgs );
    }

    public function test_email_allows_unused_address(): void {
        $player_repository = $this->createStub( Player_Repository_Interface::class );
        $player_repository->method( 'find' )->willReturn( null );

        $validator = $this->make_validator( player_repository: $player_repository );
        $validator->email( 'new@b.c', null );

        $this->assertFalse( $validator->error );
    }

    // ----------------------------------------------------------------- btm()

    public function test_btm_looks_up_by_btm_and_flags_other_player(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 123, 'btm' )
            ->willReturn( $this->make_player( 42, 'John Smith' ) );

        $validator = $this->make_validator( player_repository: $player_repository );
        $validator->btm( 123, 7 );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'btm' ], $validator->err_flds );
        $this->assertSame( [ 'LTA Tennis Number already used by John Smith' ], $validator->err_msgs );
    }

    public function test_btm_allows_number_owned_by_same_player(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 123, 'btm' )
            ->willReturn( $this->make_player( 7, 'John Smith' ) );

        $validator = $this->make_validator( player_repository: $player_repository );
        $validator->btm( 123, 7 );

        $this->assertFalse( $validator->error );
    }

    // --------------------------------------------------------------- event()

    public function test_event_with_string_uses_find_by_name(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( $this->once() )
            ->method( 'find_by_name' )
            ->with( 'Mens Singles' )
            ->willReturn( new Event( (object) [ 'id' => 10, 'name' => 'Mens Singles' ] ) );
        $event_repository->expects( $this->never() )->method( 'find_by_id' );

        $validator = $this->make_validator( event_repository: $event_repository );
        $validator->event( 'Mens Singles' );

        $this->assertFalse( $validator->error );
    }

    public function test_event_with_int_uses_find_by_id(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( null );
        $event_repository->expects( $this->never() )->method( 'find_by_name' );

        $validator = $this->make_validator( event_repository: $event_repository );
        $validator->event( 10 );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'event' ], $validator->err_flds );
        $this->assertSame( [ 'Event not found' ], $validator->err_msgs );
        $this->assertSame( 404, $validator->status );
    }

    public function test_event_with_object_does_not_consult_repository(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( $this->never() )->method( 'find_by_id' );
        $event_repository->expects( $this->never() )->method( 'find_by_name' );

        $validator = $this->make_validator( event_repository: $event_repository );
        $validator->event( (object) [ 'id' => 1 ] );

        $this->assertFalse( $validator->error );
    }

    // ---------------------------------------------------------------- club()

    public function test_club_uses_club_repository_find_by_id(): void {
        $club_repository = $this->createMock( Club_Repository_Interface::class );
        $club_repository->expects( $this->once() )->method( 'find_by_id' )->with( '3' )->willReturn( new Club() );

        $validator = $this->make_validator( club_repository: $club_repository );
        $validator->club( '3' );

        $this->assertFalse( $validator->error );
    }

    public function test_club_sets_not_found_when_repository_returns_null(): void {
        $club_repository = $this->createMock( Club_Repository_Interface::class );
        $club_repository->expects( $this->once() )->method( 'find_by_id' )->with( '3' )->willReturn( null );

        $validator = $this->make_validator( club_repository: $club_repository );
        $validator->club( '3', 'clubId' );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'clubId' ], $validator->err_flds );
        $this->assertSame( [ 'Club not found' ], $validator->err_msgs );
    }

    // -------------------------------------------------------------- league()

    public function test_league_uses_league_repository_find_by_id(): void {
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 9 )
            ->willReturn( new League( (object) [ 'id' => 9, 'title' => 'Division 1' ] ) );

        $validator = $this->make_validator( league_repository: $league_repository );
        $validator->league( 9 );

        $this->assertFalse( $validator->error );
    }

    public function test_league_sets_not_found_when_repository_returns_null(): void {
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 9 )->willReturn( null );

        $validator = $this->make_validator( league_repository: $league_repository );
        $validator->league( 9 );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'league_id' ], $validator->err_flds );
        $this->assertSame( [ 'League not found' ], $validator->err_msgs );
    }

    // ------------------------------------------------- container resolution

    public function test_repositories_are_resolved_from_plugin_container_when_not_injected(): void {
        $team_repository = $this->createMock( Team_Repository_Interface::class );
        $team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( null );

        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )->method( 'find' )->with( 'a@b.c', 'email' )->willReturn( null );

        $club_repository = $this->createMock( Club_Repository_Interface::class );
        $club_repository->expects( $this->once() )->method( 'find_by_id' )->with( '3' )->willReturn( new Club() );

        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( $this->once() )->method( 'find_by_id' )->with( 10 )->willReturn( null );

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 9 )->willReturn( null );

        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )->method( 'find_by_id' )->with( 4 )->willReturn( null );

        $container = new Simple_Container();
        $container->set( 'team_repository', $team_repository );
        $container->set( 'player_repository', $player_repository );
        $container->set( 'club_repository', $club_repository );
        $container->set( 'event_repository', $event_repository );
        $container->set( 'league_repository', $league_repository );
        $container->set( 'user_repository', $user_repository );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $validator = new Validator();
        $validator->team( 5 );
        $validator->email( 'a@b.c', 7 );
        $validator->club( '3' );
        $validator->event( 10 );
        $validator->league( 9 );

        $this->assertSame( [ 'contactno', 'event', 'league_id' ], $validator->err_flds );
        $this->assertSame( [ 'Team not found', 'Event not found', 'League not found' ], $validator->err_msgs );

        $club_validator = new Validator_Club( $this->createStub( Club_Role_Repository_Interface::class ) );
        $club_validator->user( 4 );

        $this->assertSame( [ 'User not found' ], $club_validator->err_msgs );
    }

    public function test_explicit_constructor_arguments_take_precedence_over_container(): void {
        $container_team_repository = $this->createMock( Team_Repository_Interface::class );
        $container_team_repository->expects( $this->never() )->method( 'find_by_id' );

        $container = new Simple_Container();
        $container->set( 'team_repository', $container_team_repository );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $injected_team_repository = $this->createMock( Team_Repository_Interface::class );
        $injected_team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( new Team() );

        $validator = $this->make_validator( team_repository: $injected_team_repository );
        $validator->team( 5 );

        $this->assertFalse( $validator->error );
    }

    public function test_container_entries_of_wrong_type_are_ignored(): void {
        $container = new Simple_Container();
        $container->set( 'team_repository', new \stdClass() );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $validator = new Validator();

        $property = new \ReflectionProperty( Validator::class, 'team_repository' );
        $this->assertNull( $property->getValue( $validator ) );
    }

    public function test_constructs_without_warnings_when_no_global_racketmanager(): void {
        $this->assertArrayNotHasKey( 'racketmanager', $GLOBALS );

        $validator = new Validator();

        $this->assertFalse( $validator->error );
        $this->assertSame( [], $validator->err_flds );
        $this->assertNull( $validator->status );

        $property = new \ReflectionProperty( Validator::class, 'team_repository' );
        $this->assertNull( $property->getValue( $validator ) );
    }

    // ------------------------------------------------------- Validator_Club

    public function test_validator_club_user_uses_user_repository_find_by_id(): void {
        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )->method( 'find_by_id' )->with( 4 )->willReturn( new User( (object) [ 'ID' => 4 ] ) );

        $validator = new Validator_Club( $this->createStub( Club_Role_Repository_Interface::class ), null, $user_repository );
        $validator->user( 4 );

        $this->assertFalse( $validator->error );
    }

    public function test_validator_club_user_sets_not_found_when_repository_returns_null(): void {
        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )->method( 'find_by_id' )->with( 4 )->willReturn( null );

        $validator = new Validator_Club( $this->createStub( Club_Role_Repository_Interface::class ), null, $user_repository );
        $validator->user( 4, 'owner' );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'owner' ], $validator->err_flds );
        $this->assertSame( [ 'User not found' ], $validator->err_msgs );
    }

    public function test_validator_club_match_secretary_uses_player_repository_find(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )->method( 'find' )->with( 8 )->willReturn( null );

        $validator = new Validator_Club( $this->createStub( Club_Role_Repository_Interface::class ), $player_repository );
        $validator->match_secretary( 8 );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'match_secretary' ], $validator->err_flds );
        $this->assertSame( [ 'Match secretary not found' ], $validator->err_msgs );
    }

    public function test_validator_club_match_secretary_passes_when_player_found(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )->method( 'find' )->with( 8 )->willReturn( $this->make_player( 8, 'Sec Retary' ) );

        $validator = new Validator_Club( $this->createStub( Club_Role_Repository_Interface::class ), $player_repository );
        $validator->match_secretary( 8 );

        $this->assertFalse( $validator->error );
    }
}
