<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Standings;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Settings;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Standings\Standings_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

/**
 * Covers Standings_Service::resolve_league_team() (League_Team passthrough, raw row hydration,
 * id lookup and empty skipping) through the public get_standings() / save_standings() entry points.
 */
final class Standings_Service_Resolve_League_Team_Test extends TestCase {
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

    private function event(): Event {
        $competition                  = $this->createStub( Competition::class );
        $competition->settings        = new Competition_Settings( [ 'team_ranking' => 'auto' ] );
        $competition->standings       = [];
        $competition->is_player_entry = false;
        return Event::from_object( (object) [ 'name' => 'League', 'seasons' => [] ], $competition );
    }

    private function league(): League {
        $league             = $this->createStub( League::class );
        $league->id         = 12;
        $league->event      = $this->event();
        $league->point_rule = 'three';
        $league->num_rubbers = 0;
        $league->standings  = [];
        $league->method( 'get_season' )->willReturn( '2026' );
        $league->method( 'get_point_rule' )->willReturn( [] );
        return $league;
    }

    private function league_team( int $id ): League_Team {
        $team            = new League_Team();
        $team->id        = $id;
        $team->league_id = 12;
        $team->season    = '2026';
        $team->custom    = [];
        return $team;
    }

    private function league_query_service(): League_Query_Service {
        $league_query_service = $this->createStub( League_Query_Service::class );
        $league_query_service->method( 'get_matches' )->willReturnCallback(
            static fn( object $league, array $args ): array|int => ( $args['count'] ?? false ) ? 0 : []
        );
        return $league_query_service;
    }

    public function test_get_standings_passes_league_team_entities_through_without_repository(): void {
        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->never() )->method( 'hydrate' );
        $league_team_repository->expects( $this->never() )->method( 'find_by_id' );

        $team    = $this->league_team( 10 );
        $service = new Standings_Service(
            $this->createStub( League_Repository_Interface::class ),
            $league_team_repository,
            league_query_service: $this->league_query_service()
        );

        $standings = $service->get_standings( $this->league(), [ $team ], 0 );

        $this->assertSame( [ $team ], $standings );
        $this->assertSame( 0, $team->done_matches );
    }

    public function test_get_standings_hydrates_raw_rows_through_league_team_repository(): void {
        $row      = (object) [ 'id' => 21, 'league_id' => 12, 'season' => '2026' ];
        $hydrated = $this->league_team( 21 );

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->once() )->method( 'hydrate' )->with( $row )->willReturn( $hydrated );
        $league_team_repository->expects( $this->never() )->method( 'find_by_id' );

        $service = new Standings_Service(
            $this->createStub( League_Repository_Interface::class ),
            $league_team_repository,
            league_query_service: $this->league_query_service()
        );

        $standings = $service->get_standings( $this->league(), [ $row ], 0 );

        $this->assertSame( [ $hydrated ], $standings );
    }

    public function test_get_standings_looks_up_ids_through_league_team_repository(): void {
        $team_5  = $this->league_team( 5 );
        $team_6  = $this->league_team( 6 );

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnCallback( static fn( int|string|null $id ): ?League_Team => match ( $id ) {
                5     => $team_5,
                '6'   => $team_6,
                default => null,
            } );
        $league_team_repository->expects( $this->never() )->method( 'hydrate' );

        $service = new Standings_Service(
            $this->createStub( League_Repository_Interface::class ),
            $league_team_repository,
            league_query_service: $this->league_query_service()
        );

        $standings = $service->get_standings( $this->league(), [ 5, '6' ], 0 );

        $this->assertSame( [ $team_5, $team_6 ], $standings );
    }

    public function test_get_standings_skips_empty_references_and_unresolved_ids(): void {
        $team = $this->league_team( 10 );

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 99 )->willReturn( null );
        $league_team_repository->expects( $this->never() )->method( 'hydrate' );

        $service = new Standings_Service(
            $this->createStub( League_Repository_Interface::class ),
            $league_team_repository,
            league_query_service: $this->league_query_service()
        );

        $standings = $service->get_standings( $this->league(), [ null, 0, '', $team, 99 ], 0 );

        // Unresolvable entries are left untouched; only the resolved entity is processed.
        $this->assertSame( [ null, 0, '', $team, 99 ], $standings );
        $this->assertSame( 0, $team->done_matches );
    }

    public function test_get_standings_mixed_references_are_resolved_and_ranked(): void {
        $entity   = $this->league_team( 10 );
        $hydrated = $this->league_team( 21 );
        $found    = $this->league_team( 5 );
        $row      = (object) [ 'id' => 21 ];

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->once() )->method( 'hydrate' )->with( $row )->willReturn( $hydrated );
        $league_team_repository->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $found );

        $league  = $this->league();
        $ranking = $this->createMock( League_Service::class );
        $ranking->expects( $this->once() )->method( 'rank_teams_by_points' )->with( [ $entity, $hydrated, $found ] )->willReturnArgument( 0 );
        $ranking->expects( $this->once() )->method( 'get_ranking' )->with( $league, [ $entity, $hydrated, $found ] )->willReturnArgument( 1 );

        $service = new Standings_Service(
            $this->createStub( League_Repository_Interface::class ),
            $league_team_repository,
            $ranking,
            league_query_service: $this->league_query_service()
        );

        $standings = $service->get_standings( $league, [ $entity, $row, 5 ], 0 );

        $this->assertSame( [ $entity, $hydrated, $found ], $standings );
    }

    public function test_save_standings_hydrates_raw_row_before_persisting(): void {
        $row      = (object) [ 'id' => 21, 'league_id' => 12, 'season' => '2026' ];
        $hydrated = $this->league_team( 21 );

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->once() )->method( 'hydrate' )->with( $row )->willReturn( $hydrated );

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )
            ->method( 'update_team_standings' )
            ->with( 12, '2026', 21, $this->isArray() );

        $service = new Standings_Service(
            $league_repository,
            $league_team_repository,
            league_query_service: $this->league_query_service()
        );

        $service->save_standings( $this->league(), $row );
    }

    public function test_save_standings_uses_league_team_entity_without_hydration(): void {
        $team = $this->league_team( 10 );

        $league_team_repository = $this->createMock( League_Team_Repository_Interface::class );
        $league_team_repository->expects( $this->never() )->method( 'hydrate' );
        $league_team_repository->expects( $this->never() )->method( 'find_by_id' );

        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )->method( 'update_team_standings' )->with( 12, '2026', 10, $this->isArray() );

        $service = new Standings_Service(
            $league_repository,
            $league_team_repository,
            league_query_service: $this->league_query_service()
        );

        $service->save_standings( $this->league(), $team );
    }
}
