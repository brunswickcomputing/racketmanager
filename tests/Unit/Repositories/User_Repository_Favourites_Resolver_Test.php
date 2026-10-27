<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Player\Player_Hydration_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\User_Repository;

/**
 * Covers the resolver-closure based favourite lookups in User_Repository::get_favourites().
 */
final class User_Repository_Favourites_Resolver_Test extends TestCase {
    private const USER_ID = 501;

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wp_stubs_user_meta'] = [];
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_user_meta'] );
        parent::tearDown();
    }

    private function user(): User {
        return new User( (object) [ 'ID' => self::USER_ID ] );
    }

    private function set_favourites( string $type, array $ids ): void {
        $GLOBALS['wp_stubs_user_meta'][ self::USER_ID ][ 'favourite-' . $type ] = $ids;
    }

    public function test_resolvers_are_not_invoked_at_construction(): void {
        $calls = 0;
        $spy   = function () use ( &$calls ) {
            ++$calls;
            return $this->createStub( League_Repository_Interface::class );
        };

        new User_Repository( $spy, $spy, $spy, $spy, $spy );

        $this->assertSame( 0, $calls );
    }

    public function test_league_resolver_is_invoked_lazily_and_only_once(): void {
        $this->set_favourites( 'league', [ 9, 11 ] );

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnCallback( fn( int $id ) => new League( (object) [ 'id' => $id, 'title' => 'Division ' . $id ] ) );

        $calls      = 0;
        $repository = new User_Repository( function () use ( &$calls, $league_repository ) {
            ++$calls;
            return $league_repository;
        } );

        $this->assertSame( 0, $calls );

        $favourites = $repository->get_favourites( $this->user(), 'league' );

        $this->assertSame( 1, $calls );
        $this->assertCount( 2, $favourites );
        $this->assertSame( 'Division 9', $favourites[0]->name );
        $this->assertSame( 9, $favourites[0]->id );
        $this->assertInstanceOf( League::class, $favourites[0]->detail );
        $this->assertSame( 'Division 11', $favourites[1]->name );
    }

    public function test_numeric_league_favourite_uses_find_by_id(): void {
        $this->set_favourites( 'league', [ '9' ] );

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 9 )
            ->willReturn( new League( (object) [ 'id' => 9, 'title' => 'Division 1' ] ) );
        $league_repository->expects( $this->never() )->method( 'find_by_name' );

        $repository = new User_Repository( fn() => $league_repository );
        $favourites = $repository->get_favourites( $this->user(), 'league' );

        $this->assertSame( 'Division 1', $favourites[0]->name );
        $this->assertSame( '9', $favourites[0]->id );
    }

    public function test_non_numeric_league_favourite_uses_find_by_name(): void {
        $this->set_favourites( 'league', [ 'Mens Division 1' ] );

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )
            ->method( 'find_by_name' )
            ->with( 'Mens Division 1' )
            ->willReturn( new League( (object) [ 'id' => 3, 'title' => 'Mens Division 1' ] ) );
        $league_repository->expects( $this->never() )->method( 'find_by_id' );

        $repository = new User_Repository( fn() => $league_repository );
        $favourites = $repository->get_favourites( $this->user(), 'league' );

        $this->assertSame( 'Mens Division 1', $favourites[0]->name );
        $this->assertSame( 'Mens Division 1', $favourites[0]->id );
    }

    public function test_league_not_found_yields_empty_name_and_null_detail(): void {
        $this->set_favourites( 'league', [ 77 ] );

        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'find_by_id' )->willReturn( null );

        $repository = new User_Repository( fn() => $league_repository );
        $favourites = $repository->get_favourites( $this->user(), 'league' );

        $this->assertSame( '', $favourites[0]->name );
        $this->assertNull( $favourites[0]->detail );
        $this->assertSame( 77, $favourites[0]->id );
    }

    public function test_club_favourite_uses_club_repository_find_by_id(): void {
        $this->set_favourites( 'club', [ 4 ] );

        $club       = new Club();
        $club->name = 'Leigh';

        $club_repository = $this->createMock( Club_Repository_Interface::class );
        $club_repository->expects( $this->once() )->method( 'find_by_id' )->with( 4 )->willReturn( $club );

        $repository = new User_Repository( null, fn() => $club_repository );
        $favourites = $repository->get_favourites( $this->user(), 'club' );

        $this->assertSame( 'Leigh', $favourites[0]->name );
        $this->assertSame( $club, $favourites[0]->detail );
    }

    public function test_competition_favourite_uses_event_repository_find_by_id(): void {
        $this->set_favourites( 'competition', [ 10 ] );

        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 10 )
            ->willReturn( new Event( (object) [ 'id' => 10, 'name' => 'Mens Singles' ] ) );

        $repository = new User_Repository( null, null, fn() => $event_repository );
        $favourites = $repository->get_favourites( $this->user(), 'competition' );

        $this->assertSame( 'Mens Singles', $favourites[0]->name );
        $this->assertInstanceOf( Event::class, $favourites[0]->detail );
    }

    public function test_player_favourite_uses_player_repository_find(): void {
        $this->set_favourites( 'player', [ 7 ] );

        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 7 )
            ->willReturn( new Player( new Player_Hydration_DTO( id: 7, display_name: 'Jane Doe' ) ) );

        $repository = new User_Repository( null, null, null, fn() => $player_repository );
        $favourites = $repository->get_favourites( $this->user(), 'player' );

        $this->assertSame( 'Jane Doe', $favourites[0]->name );
        $this->assertInstanceOf( Player::class, $favourites[0]->detail );
    }

    public function test_team_favourite_uses_team_repository_find_by_id(): void {
        $this->set_favourites( 'team', [ 12 ] );

        $team        = new Team();
        $team->title = 'Leigh A';

        $team_repository = $this->createMock( Team_Repository_Interface::class );
        $team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 12 )->willReturn( $team );

        $repository = new User_Repository( null, null, null, null, fn() => $team_repository );
        $favourites = $repository->get_favourites( $this->user(), 'team' );

        $this->assertSame( 'Leigh A', $favourites[0]->name );
        $this->assertSame( $team, $favourites[0]->detail );
    }

    public function test_empty_favourite_id_does_not_consult_repository(): void {
        $this->set_favourites( 'club', [ 0 ] );
        $this->set_favourites( 'team', [ '' ] );

        $club_repository = $this->createMock( Club_Repository_Interface::class );
        $club_repository->expects( $this->never() )->method( 'find_by_id' );
        $team_repository = $this->createMock( Team_Repository_Interface::class );
        $team_repository->expects( $this->never() )->method( 'find_by_id' );

        $repository = new User_Repository( null, fn() => $club_repository, null, null, fn() => $team_repository );

        $clubs = $repository->get_favourites( $this->user(), 'club' );
        $teams = $repository->get_favourites( $this->user(), 'team' );

        $this->assertSame( '', $clubs[0]->name );
        $this->assertNull( $clubs[0]->detail );
        $this->assertSame( '', $teams[0]->name );
        $this->assertNull( $teams[0]->detail );
    }

    public function test_get_favourites_without_type_uses_all_resolvers(): void {
        $this->set_favourites( 'competition', [ 10 ] );
        $this->set_favourites( 'league', [ 9 ] );
        $this->set_favourites( 'club', [ 4 ] );
        $this->set_favourites( 'team', [ 12 ] );
        $this->set_favourites( 'player', [ 7 ] );

        $club       = new Club();
        $club->name = 'Leigh';
        $team        = new Team();
        $team->title = 'Leigh A';

        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'find_by_id' )->willReturn( new League( (object) [ 'id' => 9, 'title' => 'Division 1' ] ) );
        $club_repository = $this->createStub( Club_Repository_Interface::class );
        $club_repository->method( 'find_by_id' )->willReturn( $club );
        $event_repository = $this->createStub( Event_Repository_Interface::class );
        $event_repository->method( 'find_by_id' )->willReturn( new Event( (object) [ 'id' => 10, 'name' => 'Mens Singles' ] ) );
        $player_repository = $this->createStub( Player_Repository_Interface::class );
        $player_repository->method( 'find' )->willReturn( new Player( new Player_Hydration_DTO( id: 7, display_name: 'Jane Doe' ) ) );
        $team_repository = $this->createStub( Team_Repository_Interface::class );
        $team_repository->method( 'find_by_id' )->willReturn( $team );

        $repository = new User_Repository(
            fn() => $league_repository,
            fn() => $club_repository,
            fn() => $event_repository,
            fn() => $player_repository,
            fn() => $team_repository
        );

        $favourites = $repository->get_favourites( $this->user() );
        $by_type    = array_column( $favourites, 'favourites', 'name' );

        $this->assertSame( [ 'competition', 'league', 'club', 'team', 'player' ], array_column( $favourites, 'name' ) );
        $this->assertSame( 'Mens Singles', $by_type['competition'][0]->name );
        $this->assertSame( 'Division 1', $by_type['league'][0]->name );
        $this->assertSame( 'Leigh', $by_type['club'][0]->name );
        $this->assertSame( 'Leigh A', $by_type['team'][0]->name );
        $this->assertSame( 'Jane Doe', $by_type['player'][0]->name );
    }
}
