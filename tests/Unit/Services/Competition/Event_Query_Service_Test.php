<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
class Event_Query_Service_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }
    private function event(): Event {
        $event = new Event( (object) [
            'id' => 93,
            'name' => 'Mens Doubles',
            'type' => 'MD',
            'seasons' => '{"2026":{"name":"2026"}}',
        ] );
        $event->current_season = [ 'name' => '2026' ];
        return $event;
    }

    /**
     * Build a fully configured service; any repository not overridden is a stub.
     *
     * @param array<string, object> $repositories
     */
    private function service( Event_Repository_Interface $event_repository, array $repositories = [] ): Event_Query_Service {
        return new Event_Query_Service(
            $event_repository,
            $repositories['fixture_repository'] ?? $this->createStub( Fixture_Repository_Interface::class ),
            $repositories['rubber_repository'] ?? $this->createStub( Rubber_Repository_Interface::class ),
            $repositories['league_repository'] ?? $this->createStub( League_Repository_Interface::class ),
            $repositories['team_repository'] ?? $this->createStub( Team_Repository_Interface::class ),
            $repositories['club_repository'] ?? $this->createStub( Club_Repository_Interface::class ),
            $repositories['player_repository'] ?? $this->createStub( Player_Repository_Interface::class )
        );
    }

    public function test_leagues_are_hydrated_through_the_lazily_injected_repository(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league = League_Factory::from_array( [ 'id' => 2395, 'title' => 'Division One', 'event_id' => 93 ], $this->event() );
        $event_repository->expects( $this->exactly( 2 ) )->method( 'query_leagues' )
            ->with( 93, $this->callback( static fn( array $args ): bool => '2026' === $args['season'] ) )
            ->willReturn( [ (object) [ 'id' => 2395 ] ] );
        $league_repository->expects( $this->exactly( 2 ) )->method( 'find_by_id' )->with( 2395 )->willReturn( $league );
        $resolved = 0;
        $service = new Event_Query_Service( $event_repository, league_repository: static function () use ( &$resolved, $league_repository ) {
            ++$resolved;
            return $league_repository;
        } );

        $this->assertSame( 0, $resolved );
        $event = $this->event();
        $this->assertSame( [ $league ], $service->get_leagues( $event, [ 'season' => '2026' ] ) );
        $this->assertSame( [ $league ], $service->get_leagues( $event, [ 'season' => '2026' ] ) );
        $this->assertSame( 1, $resolved );
    }

    public function test_matches_forward_event_fixture_and_rubber_repositories(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
        $fixture = Fixture_Factory::from_array( [ 'id' => 21 ] );
        $rubber = $this->createStub( Rubber::class );
        $event_repository->expects( $this->once() )->method( 'query_matches' )
            ->with( 93, $this->callback( static fn( array $args ): bool => 7 === $args['player'] ) )
            ->willReturn( [ (object) [ 'id' => 21 ] ] );
        $fixture_repository->expects( $this->once() )->method( 'find_by_id_with_relations' )->with( 21 )->willReturn( $fixture );
        $rubber_repository->expects( $this->once() )->method( 'find_by_fixture_id' )->with( 21, 7 )->willReturn( [ $rubber ] );
        $resolved = [];
        $service = new Event_Query_Service(
            $event_repository,
            static function () use ( &$resolved, $fixture_repository ) {
                $resolved[] = 'fixture';
                return $fixture_repository;
            },
            static function () use ( &$resolved, $rubber_repository ) {
                $resolved[] = 'rubber';
                return $rubber_repository;
            }
        );

        $this->assertSame( [], $resolved );
        $this->assertSame( [ $fixture ], $service->get_matches( $this->event(), [ 'player' => 7 ] ) );
        $this->assertSame( [ $rubber ], $fixture->rubbers );
        $this->assertSame( $fixture_repository, $service->get_fixture_repository() );
        $this->assertSame( $rubber_repository, $service->get_rubber_repository() );
        $this->assertSame( [ 'fixture', 'rubber' ], $resolved );
    }

    #[DataProvider( 'count_queries' )]
    public function test_count_queries_forward_arguments_to_the_event_repository( string $method, string $repository_method ): void {
        $repository = $this->createMock( Event_Repository_Interface::class );
        $args = $this->callback( static fn( array $args ): bool => true === $args['count'] && '2026' === $args['season'] );
        $expectation = $repository->expects( $this->once() )->method( $repository_method );
        if ( 'get_players' === $method ) {
            $expectation->with( 93, false, $args );
        } else {
            $expectation->with( 93, $args );
        }
        $expectation->willReturn( 4 );
        $service = $this->service( $repository );

        $this->assertSame( 4, $service->$method( $this->event(), [ 'season' => '2026', 'count' => true ] ) );
    }

    public static function count_queries(): array {
        return [
            [ 'get_teams', 'query_teams' ],
            [ 'get_players', 'query_players' ],
            [ 'get_clubs', 'query_clubs' ],
        ];
    }

    public function test_get_players_defaults_to_current_season(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( $this->once() )
            ->method( 'query_players' )
            ->with(
                93,
                false,
                $this->callback( static fn( array $args ): bool => '2026' === ( $args['season'] ?? null ) )
            )
            ->willReturn( [] );
        $service = $this->service( $event_repository );

        $this->assertSame( [], $service->get_players( $this->event() ) );
    }

    #[DataProvider( 'missing_repositories' )]
    public function test_lazy_repository_resolving_to_null_fails_loudly( string $repository_name ): void {
        $service = new Event_Query_Service(
            $this->createStub( Event_Repository_Interface::class ),
            ...[ $repository_name => static fn() => null ]
        );
        $getter = 'get_' . $repository_name;
        $this->expectException( \LogicException::class );
        $service->$getter();
    }

    #[DataProvider( 'missing_repositories' )]
    public function test_unconfigured_repository_fails_loudly( string $repository_name ): void {
        $service = new Event_Query_Service( $this->createStub( Event_Repository_Interface::class ) );
        $getter = 'get_' . $repository_name;
        $this->expectException( \LogicException::class );
        $service->$getter();
    }

    public function test_enrichment_without_required_repository_fails_before_querying(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $event_repository->expects( $this->never() )->method( 'query_teams' );
        $service = new Event_Query_Service( $event_repository );

        $this->expectException( \LogicException::class );
        $service->get_teams( $this->event(), [ 'season' => '2026' ] );
    }

    public static function missing_repositories(): array {
        return [ [ 'league_repository' ], [ 'fixture_repository' ], [ 'rubber_repository' ], [ 'team_repository' ], [ 'club_repository' ], [ 'player_repository' ] ];
    }

    public function test_invalid_event_is_not_silently_treated_as_an_empty_result(): void {
        $service = new Event_Query_Service( $this->createStub( Event_Repository_Interface::class ) );
        $this->expectException( \TypeError::class );
        $service->get_leagues( new \stdClass() );
    }

    public function test_get_players_enriches_players_using_player_repository_find(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $player_repository = $this->createMock( Player_Repository_Interface::class );

        $event_repository->expects( $this->once() )
            ->method( 'query_players' )
            ->with( 93, false, $this->anything() )
            ->willReturn( [ (object) [ 'player_id' => 123 ] ] );

        $player = $this->createMock( Player::class );
        $player->id = 123;
        $player->system_record = false;

        $player_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 123 )
            ->willReturn( $player );

        $service = new Event_Query_Service(
            $event_repository,
            player_repository: $player_repository
        );

        $result = $service->get_players( $this->event(), [ 'season' => '2026' ] );
        $this->assertSame( [ $player ], $result );
    }

    public function test_get_teams_info_enriches_teams_with_club_from_repository(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $club_repository = $this->createMock( Club_Repository_Interface::class );

        $team_row = (object) [ 'id' => 1, 'club_id' => 133, 'captain' => 0, 'roster' => '', 'title' => 'Club A 1' ];
        $event_repository->expects( $this->once() )
            ->method( 'get_teams_info' )
            ->with( 93, '2026', $this->anything() )
            ->willReturn( [ $team_row ] );

        $club = $this->createMock( Club::class );
        $club_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 133 )
            ->willReturn( $club );

        $service = new Event_Query_Service(
            $event_repository,
            club_repository: $club_repository
        );

        $result = $service->get_teams_info( $this->event(), [ 'season' => '2026' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( $club, $result[0]->club );
        $this->assertSame( 'Club A 1', $result[0]->title );
    }

    public function test_get_clubs_enriches_clubs_with_player_counts(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $club_repository = $this->createMock( Club_Repository_Interface::class );
        $player_repository = $this->createMock( Player_Repository_Interface::class );

        $club_row = (object) [ 'club_id' => 133, 'team_count' => 1 ];
        $event_repository->expects( $this->once() )
            ->method( 'query_clubs' )
            ->with( 93, $this->anything() )
            ->willReturn( [ $club_row ] );

        $club = $this->createMock( Club::class );
        $club->id = 133;

        $club_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 133 )
            ->willReturn( $club );

        $event_repository->expects( $this->once() )
            ->method( 'query_players' )
            ->with( 93, false, $this->anything() )
            ->willReturn( 2 );

        $service = new Event_Query_Service(
            $event_repository,
            club_repository: $club_repository,
            player_repository: $player_repository
        );

        $result = $service->get_clubs( $this->event(), [ 'season' => '2026' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 2, $result[0]->player_count );
    }

    public function test_get_constitution_enriches_old_league_title_from_repository(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $league_repository = $this->createMock( League_Repository_Interface::class );

        $row = (object) [ 'old_league_id' => 50 ];
        $event_repository->expects( $this->once() )
            ->method( 'query_constitution' )
            ->with( 93, $this->anything() )
            ->willReturn( [ $row ] );

        $league = $this->createMock( League::class );
        $league->title = 'Division One';

        $league_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 50 )
            ->willReturn( $league );

        $service = new Event_Query_Service(
            $event_repository,
            league_repository: $league_repository
        );

        $result = $service->get_constitution( $this->event(), [ 'season' => '2026' ] );
        $this->assertCount( 1, $result );
        $this->assertSame( 'Division One', $result[0]->old_league_title );
    }

    public function test_get_club_enriches_club_with_teams_and_players(): void {
        $event_repository = $this->createMock( Event_Repository_Interface::class );
        $fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $rubber_repository = $this->createMock( Rubber_Repository_Interface::class );
        $club_repository = $this->createMock( Club_Repository_Interface::class );
        $team_repository = $this->createMock( Team_Repository_Interface::class );
        $player_repository = $this->createMock( Player_Repository_Interface::class );

        $event = $this->event();
        $club_input = (object) [ 'id' => 133 ];

        $team_row = (object) [
            'id'        => 10,
            'club_id'   => 133,
            'team_id'   => 20,
            'name'      => 'Club A 1',
            'team_type' => 'P',
            'roster'    => serialize( [ 123 ] ),
        ];
        $event_repository->expects( $this->once() )
            ->method( 'query_teams' )
            ->willReturn( [ $team_row ] );

        $club_entity = $this->createMock( Club::class );
        $club_repository->expects( $this->atLeastOnce() )
            ->method( 'find_by_id' )
            ->with( 133 )
            ->willReturn( $club_entity );

        $team_player = $this->createMock( Player::class );
        $team_player->method( 'get_fullname' )->willReturn( 'Jane Doe' );
        $team_player->method( 'get_id' )->willReturn( 123 );
        $team_entity = $this->createMock( Team::class );
        $team_entity->players = [ $team_player ];
        $team_repository->expects( $this->once() )
            ->method( 'find_by_id' )
            ->with( 20 )
            ->willReturn( $team_entity );

        $event_repository->expects( $this->exactly( 2 ) )
            ->method( 'query_matches' )
            ->willReturn( [] );

        $event_repository->expects( $this->once() )
            ->method( 'query_players' )
            ->with( 93, false, $this->callback( static fn( array $args ): bool => 133 === $args['club'] ) )
            ->willReturn( [ (object) [ 'player_id' => 123 ] ] );

        $player_entity = $this->createMock( Player::class );
        $player_entity->system_record = false;
        $player_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 123 )
            ->willReturn( $player_entity );

        $service = new Event_Query_Service(
            $event_repository,
            fixture_repository: $fixture_repository,
            rubber_repository: $rubber_repository,
            team_repository: $team_repository,
            club_repository: $club_repository,
            player_repository: $player_repository
        );

        $enriched = $service->get_club( $event, $club_input );
        $this->assertCount( 1, $enriched->teams );
        $this->assertSame( $club_entity, $enriched->teams[0]->club );
        $this->assertSame( 'Club A 1', $enriched->teams[0]->title );
        $this->assertSame( [ $team_player ], $enriched->teams[0]->players );
        $this->assertSame( [ 1 => 'Jane Doe' ], $enriched->teams[0]->player );
        $this->assertSame( [ 1 => 123 ], $enriched->teams[0]->player_id );
        $this->assertSame( [ $player_entity ], $enriched->players );
    }

    public function test_set_season_request_season_takes_precedence_and_is_strict(): void {
        $event_repository = $this->createStub( Event_Repository_Interface::class );
        $service          = $this->service( $event_repository );

        $event = new Event( (object) [
            'id'      => 93,
            'name'    => 'Mens Doubles',
            'type'    => 'MD',
            'seasons' => json_encode( [
                '2025' => [ 'name' => '2025' ],
                '2026' => [ 'name' => '2026' ],
            ] ),
        ] );

        // 1. Request season (2025) takes precedence over explicit fallback (2026)
        $_GET['season'] = '2025';
        $service->set_season( $event, '2026' );
        $this->assertIsArray( $event->current_season );
        $this->assertSame( '2025', $event->current_season['name'] );

        // 2. Request season with unconfigured season is strict (evaluates to false)
        $_GET['season'] = '1999';
        $service->set_season( $event, '2026' );
        $this->assertFalse( $event->current_season );
    }

    public function test_set_season_force_overwrite_bypasses_request(): void {
        $event_repository = $this->createStub( Event_Repository_Interface::class );
        $service          = $this->service( $event_repository );

        $event = new Event( (object) [
            'id'      => 93,
            'name'    => 'Mens Doubles',
            'type'    => 'MD',
            'seasons' => json_encode( [
                '2025' => [ 'name' => '2025' ],
                '2026' => [ 'name' => '2026' ],
            ] ),
        ] );

        $_GET['season'] = '2025';
        // force_overwrite: true sets explicit season bypassing request
        $service->set_season( $event, '2026', force_overwrite: true );
        $this->assertIsArray( $event->current_season );
        $this->assertSame( '2026', $event->current_season['name'] );
    }
}
