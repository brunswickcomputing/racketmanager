<?php
declare( strict_types=1 );

namespace {
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', 1 );
    }
}

namespace Racketmanager\Services\Fixture {
    function home_url( string $path = '' ): string {
        return 'https://example.com' . $path;
    }

    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

namespace Racketmanager\Tests\Unit\Services\Fixture {

    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;
    use Racketmanager\Domain\Competition\Competition;
    use Racketmanager\Domain\Competition\Event;
    use Racketmanager\Domain\Competition\League;
    use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
    use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
    use Racketmanager\Domain\Fixture\Fixture;
    use Racketmanager\Domain\Fixture\Rubber;
    use Racketmanager\Domain\Scoring\Set_Score;
    use Racketmanager\Domain\Team\Team;
    use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
    use Racketmanager\Repositories\Repository_Provider;
    use Racketmanager\Services\Competition_Service;
    use Racketmanager\Services\Fixture\Fixture_Detail_Service;
    use Racketmanager\Services\Fixture\Fixture_Link_Service;
    use Racketmanager\Services\Fixture\Fixture_Permission_Service;
    use Racketmanager\Services\Team_Service;

    #[AllowMockObjectsWithoutExpectations]
    class Fixture_Detail_Service_Test extends TestCase {

        private $fixture_repository;
        private $league_repository;
        private $team_repository;
        private Rubber_Repository_Interface|MockObject $rubber_repository;
        private $competition_service;
        private $team_service;
        private $tournament_service;
        private $permission_service;
        private $repository_provider;
        private $service_provider;
        private Fixture_Link_Service|MockObject $link_service;
        private Fixture_Detail_Service $service;

        public function test_get_fixture_with_details_populates_new_fields(): void {
            $this->configure_default_team_service();
            $fixture_id = 1;
            $fixture    = $this->createStub( Fixture::class );
            $fixture->method( 'get_league_id' )->willReturn( 10 );
            $fixture->method( 'get_home_points' )->willReturn( "5" );
            $fixture->method( 'get_away_points' )->willReturn( "3" );
            $fixture->method( 'get_season' )->willReturn( '2026' );
            $fixture->method( 'is_walkover' )->willReturn( false );
            $fixture->method( 'is_retired' )->willReturn( true );

            $event = $this->createStub( Event::class );
            $event->method( 'get_num_rubbers' )->willReturn( 9 );
            $event->method( 'get_id' )->willReturn( 20 );
            $event->method( 'get_competition_id' )->willReturn( 30 );

            $league = $this->createStub( League::class );
            $league->method( 'get_id' )->willReturn( 10 );
            $league->method( 'get_event_id' )->willReturn( 20 );
            $league->method( 'get_name' )->willReturn( 'Premier League' );
            $league->method( 'get_competition_type' )->willReturn( 'league' );

            $competition = $this->createStub( Competition::class );

            $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
            $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

            $this->link_service->method( 'get_fixture_link' )->willReturn( 'https://example.com/league/10/premier-league/2026/' );

            $default_team = $this->createStub( Team::class );
            $default_team->method( 'get_name' )->willReturn( 'Unknown' );
            $this->team_service->method( 'get_team_details' )->willReturn( new Team_Details_DTO( $default_team, null, null ) );
            $this->team_service->method( 'derive_team_details' )->willReturn( new Team_Details_DTO( $default_team, null, null ) );

            $dto = $this->service->get_fixture_with_details( $fixture_id );

            $this->assertInstanceOf( Fixture_Details_DTO::class, $dto );
            $this->assertEquals( '5 - 3', $dto->score_display );
            $this->assertContains( 'retired', $dto->status_flags );
            $this->assertStringContainsString( 'premier-league', $dto->link );
            $this->assertStringContainsString( '2026', $dto->link );
        }

        public function test_get_fixture_with_details_loads_unfiltered_rubbers(): void {
            $fixture = $this->configure_rubber_loading_context( 2 );
            $rubber  = new Rubber();
            $this->rubber_repository->expects( $this->once() )
                ->method( 'find_by_fixture_id' )
                ->with( 42, null )
                ->willReturn( [ $rubber ] );

            $dto = $this->service->get_fixture_with_details( 42 );

            $this->assertSame( $fixture, $dto->fixture );
            $this->assertSame( [ $rubber ], $dto->fixture->get_rubbers() );
        }

        public function test_get_fixture_with_details_loads_rubbers_filtered_by_player(): void {
            $fixture = $this->configure_rubber_loading_context( 2 );
            $rubber  = new Rubber();
            $this->rubber_repository->expects( $this->once() )
                ->method( 'find_by_fixture_id' )
                ->with( 42, 9 )
                ->willReturn( [ $rubber ] );

            $dto = $this->service->get_fixture_with_details( 42, false, 9 );

            $this->assertSame( $fixture, $dto->fixture );
            $this->assertSame( [ $rubber ], $dto->fixture->get_rubbers() );
        }

        public function test_get_fixture_with_no_rubbers_skips_rubber_repository(): void {
            $fixture = $this->configure_rubber_loading_context( 0 );
            $this->rubber_repository->expects( $this->never() )->method( 'find_by_fixture_id' );

            $dto = $this->service->get_fixture_with_details( 42 );

            $this->assertSame( $fixture, $dto->fixture );
            $this->assertSame( [], $dto->fixture->get_rubbers() );
        }

        private function configure_default_team_service(): void {
            $default_team = $this->createStub( Team::class );
            $default_team->method( 'get_name' )->willReturn( 'Unknown' );
            $default_dto = new Team_Details_DTO( $default_team, null, null );
            $this->team_service->method( 'get_team_details' )->willReturn( $default_dto );
            $this->team_service->method( 'derive_team_details' )->willReturn( $default_dto );
        }

        public function test_get_fixture_with_details_populates_withdrawn_status(): void {
            $fixture_id = 1;
            $fixture    = $this->createStub( Fixture::class );
            $fixture->method( 'get_league_id' )->willReturn( 10 );
            $fixture->method( 'get_season' )->willReturn( '2026' );
            $fixture->method( 'get_home_team' )->willReturn( '100' );
            $fixture->method( 'get_away_team' )->willReturn( '200' );

            $league = $this->createMock( League::class );
            $league->method( 'get_id' )->willReturn( 10 );
            $league->method( 'get_event_id' )->willReturn( 20 );
            $league->method( 'get_status' )->willReturnMap( [
                [ 100, '2026', 'W' ], // Home team withdrawn
                [ 200, '2026', 'A' ], // Away team active
            ] );

            $event = $this->createStub( Event::class );
            $event->method( 'get_competition_id' )->willReturn( 30 );
            $competition = $this->createStub( Competition::class );

            $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
            $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

            $home_team_obj = $this->createStub( Team::class );
            $home_team_dto = new Team_Details_DTO( $home_team_obj, null, null );
            $away_team_obj = $this->createStub( Team::class );
            $away_team_dto = new Team_Details_DTO( $away_team_obj, null, null );

            $this->team_service->method( 'get_team_details' )->willReturnMap( [
                [ 100, $home_team_dto ],
                [ 200, $away_team_dto ],
            ] );

            $dto = $this->service->get_fixture_with_details( $fixture_id );

            $this->assertTrue( $dto->home_team->is_withdrawn );
            $this->assertFalse( $dto->away_team->is_withdrawn );
        }

        public function test_generate_score_display_handles_walkover(): void {
            $this->configure_default_team_service();
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'is_walkover' )->willReturn( true );
            $fixture->method( 'get_home_points' )->willReturn( "10" );
            $fixture->method( 'get_away_points' )->willReturn( "0" );
            $fixture->method( 'get_season' )->willReturn( '2026' );

            $event = $this->createStub( Event::class );
            $event->method( 'get_num_rubbers' )->willReturn( 10 );
            $event->method( 'get_id' )->willReturn( 20 );
            $event->method( 'get_competition_id' )->willReturn( 30 );

            // Setup minimal context for get_fixture_with_details
            $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
            $league = $this->createStub( League::class );
            $league->method( 'get_competition_type' )->willReturn( 'league' );
            $league->method( 'get_id' )->willReturn( 10 );
            $league->method( 'get_event_id' )->willReturn( 20 );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
            $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );

            $dto = $this->service->get_fixture_with_details( 1 );
            $this->assertEquals( 'Walkover', $dto->score_display );
        }

        public function test_generate_score_display_handles_set_scores(): void {
            $this->configure_default_team_service();
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_home_points' )->willReturn( "2" );
            $fixture->method( 'get_away_points' )->willReturn( "1" );
            $fixture->method( 'get_season' )->willReturn( '2026' );
            $fixture->method( 'get_custom' )->willReturn( [
                'sets' => [
                    new Set_Score( 6, 4 ),
                    new Set_Score( 3, 6 ),
                    new Set_Score( 6, 2 )
                ]
            ] );

            $event = $this->createStub( Event::class );
            $event->method( 'get_num_rubbers' )->willReturn( 0 ); // Individual match
            $event->method( 'get_id' )->willReturn( 20 );
            $event->method( 'get_competition_id' )->willReturn( 30 );

            $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
            $league = $this->createStub( League::class );
            $league->method( 'get_competition_type' )->willReturn( 'league' );
            $league->method( 'get_id' )->willReturn( 10 );
            $league->method( 'get_event_id' )->willReturn( 20 );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
            $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );

            $dto = $this->service->get_fixture_with_details( 1 );
            $this->assertEquals( '6-4 3-6 6-2', $dto->score_display );
        }

        public function test_generate_score_display_handles_implicit_walkover(): void {
            $this->configure_default_team_service();
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'is_walkover' )->willReturn( false );
            $fixture->method( 'get_home_points' )->willReturn( null );
            $fixture->method( 'get_away_points' )->willReturn( null );
            $fixture->method( 'get_winner_id' )->willReturn( 100 );
            $fixture->method( 'get_home_team' )->willReturn( "-1" ); // BYE/Walkover indicator
            $fixture->method( 'get_season' )->willReturn( '2026' );

            $event = $this->createStub( Event::class );
            $event->method( 'get_num_rubbers' )->willReturn( 9 );
            $event->method( 'get_id' )->willReturn( 20 );

            $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
            $league = $this->createStub( League::class );
            $league->method( 'get_id' )->willReturn( 10 );
            $league->method( 'get_event_id' )->willReturn( 20 );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
            $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );

            $dto = $this->service->get_fixture_with_details( 1 );
            $this->assertEquals( 'Walkover', $dto->score_display );
        }

        public function test_collect_fixture_player_ids_from_team_entity_when_details_dto_players_are_empty(): void {
            $fixture = new Fixture();
            $fixture->set_id( 1 );
            $fixture->set_home_team( '10' );
            $fixture->set_away_team( '20' );

            $home = $this->create_team_details_with_players( [ (object) [ 'ID' => 10 ], (object) [ 'id' => 11 ] ] );
            $away = $this->create_team_details_with_players( [ (object) [ 'ID' => 20 ] ] );
            $this->assertSame( [], $home->players );

            $dto = new Fixture_Details_DTO(
                $fixture,
                $this->createStub( League::class ),
                $this->createStub( Event::class ),
                $this->createStub( Competition::class ),
                $home,
                $away
            );

            $this->assertSame( [ 10, 11, 20 ], $this->service->collect_fixture_player_ids( $dto ) );
        }

        public function test_collect_fixture_player_ids_walks_placeholder_ancestors(): void {
            $final = new Fixture();
            $final->set_id( 100 );
            $final->set_home_team( '1_semi_1' );
            $final->set_away_team( '10' );

            $semi = new Fixture();
            $semi->set_id( 50 );
            $semi->set_home_team( '11' );
            $semi->set_away_team( '12' );

            $home = $this->create_team_details_with_players( [ (object) [ 'ID' => 101 ] ] );
            $semi_dto = new Fixture_Details_DTO(
                $semi,
                $this->createStub( League::class ),
                $this->createStub( Event::class ),
                $this->createStub( Competition::class ),
                $home,
                $this->create_team_details_with_players( [] )
            );
            $final_dto = new Fixture_Details_DTO(
                $final,
                $this->createStub( League::class ),
                $this->createStub( Event::class ),
                $this->createStub( Competition::class )
            );

            $service = $this->getMockBuilder( Fixture_Detail_Service::class )
                ->setConstructorArgs( [
                    $this->repository_provider,
                    $this->competition_service,
                    $this->team_service,
                    $this->permission_service,
                    $this->link_service,
                ] )
                ->onlyMethods( [ 'get_fixture_with_details', 'get_previous_fixture' ] )
                ->getMock();

            $service->method( 'get_fixture_with_details' )->willReturnCallback( function ( $id ) use ( $final_dto, $semi_dto ) {
                $id = $id instanceof Fixture ? $id->get_id() : $id;
                return match ( (int) $id ) {
                    100 => $final_dto,
                    50 => $semi_dto,
                    default => null,
                };
            } );
            $service->method( 'get_previous_fixture' )->willReturnCallback( function ( $fixture, $ref ) use ( $semi ) {
                return '1_semi_1' === $ref ? $semi : null;
            } );

            $this->assertSame( [ 101 ], $service->collect_fixture_player_ids( 100 ) );
        }

        public function test_collect_fixture_player_ids_guards_against_cycles(): void {
            $fixture = new Fixture();
            $fixture->set_id( 7 );
            $fixture->set_home_team( '1_semi_1' );
            $fixture->set_away_team( '10' );

            $dto = new Fixture_Details_DTO(
                $fixture,
                $this->createStub( League::class ),
                $this->createStub( Event::class ),
                $this->createStub( Competition::class )
            );

            $service = $this->getMockBuilder( Fixture_Detail_Service::class )
                ->setConstructorArgs( [
                    $this->repository_provider,
                    $this->competition_service,
                    $this->team_service,
                    $this->permission_service,
                    $this->link_service,
                ] )
                ->onlyMethods( [ 'get_fixture_with_details', 'get_previous_fixture' ] )
                ->getMock();

            $service->method( 'get_fixture_with_details' )->willReturn( $dto );
            $service->method( 'get_previous_fixture' )->willReturn( $fixture );

            $this->assertSame( [], $service->collect_fixture_player_ids( 7 ) );
        }

        public function test_get_previous_fixture_swallows_repository_exceptions(): void {
            $repo = $this->createMock( Fixture_Repository_Interface::class );
            $repo->method( 'find_by_league_and_final' )->willThrowException( new \RuntimeException( 'db down' ) );

            $provider = $this->createStub( Repository_Provider::class );
            $provider->method( 'get_fixture_repository' )->willReturn( $repo );
            $provider->method( 'get_league_repository' )->willReturn( $this->league_repository );
            $provider->method( 'get_team_repository' )->willReturn( $this->team_repository );

            $service = new Fixture_Detail_Service(
                $provider,
                $this->competition_service,
                $this->team_service,
                $this->permission_service,
                $this->link_service
            );

            $fixture = new Fixture();
            $fixture->set_league_id( 5 );
            $fixture->set_season( '2026' );
            $this->assertNull( $service->get_previous_fixture( $fixture, '1_semi_1' ) );
        }

        public function test_get_previous_fixture_uses_home_away_leg(): void {
            $prev = new Fixture();
            $prev->set_id( 50 );

            $league = $this->createStub( League::class );
            $league->method( 'get_seasons' )->willReturn( [ '2026' => [ 'home_away' => true ] ] );

            $league_repo = $this->createMock( League_Repository_Interface::class );
            $league_repo->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $league );

            $fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
            $fixture_repo->expects( $this->once() )
                ->method( 'find_by_league_and_final' )
                ->with( 5, '2026', 'semi', 2 )
                ->willReturn( [ $prev ] );

            $provider = $this->createStub( Repository_Provider::class );
            $provider->method( 'get_fixture_repository' )->willReturn( $fixture_repo );
            $provider->method( 'get_league_repository' )->willReturn( $league_repo );
            $provider->method( 'get_team_repository' )->willReturn( $this->team_repository );

            $service = new Fixture_Detail_Service(
                $provider,
                $this->competition_service,
                $this->team_service,
                $this->permission_service,
                $this->link_service
            );

            $fixture = new Fixture();
            $fixture->set_league_id( 5 );
            $fixture->set_season( '2026' );
            $this->assertSame( $prev, $service->get_previous_fixture( $fixture, '1_semi_1' ) );
        }

        public function test_generate_score_display_handles_points_based_walkover(): void {
            $this->configure_default_team_service();
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'is_walkover' )->willReturn( false );
            $fixture->method( 'get_home_points' )->willReturn( "10" );
            $fixture->method( 'get_away_points' )->willReturn( "0" );
            $fixture->method( 'get_home_team' )->willReturn( "-1" );
            $fixture->method( 'get_season' )->willReturn( '2026' );

            $event = $this->createStub( Event::class );
            $event->method( 'get_num_rubbers' )->willReturn( 9 );
            $event->method( 'get_id' )->willReturn( 20 );

            $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );
            $league = $this->createStub( League::class );
            $league->method( 'get_id' )->willReturn( 10 );
            $league->method( 'get_event_id' )->willReturn( 20 );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
            $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );

            $dto = $this->service->get_fixture_with_details( 1 );
            $this->assertEquals( 'Walkover', $dto->score_display );
        }

        protected function setUp(): void {
            $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
            $this->league_repository  = $this->createStub( League_Repository_Interface::class );
            $this->team_repository    = $this->createStub( Team_Repository_Interface::class );
            $this->rubber_repository  = $this->createMock( Rubber_Repository_Interface::class );

            $this->competition_service = $this->createStub( Competition_Service::class );
            $this->team_service        = $this->createStub( Team_Service::class );
            $this->permission_service  = $this->createStub( Fixture_Permission_Service::class );
            $this->link_service        = $this->createStub( Fixture_Link_Service::class );

            $this->repository_provider = $this->createStub( Repository_Provider::class );
            $this->repository_provider->method( 'get_fixture_repository' )->willReturn( $this->fixture_repository );
            $this->repository_provider->method( 'get_league_repository' )->willReturn( $this->league_repository );
            $this->repository_provider->method( 'get_team_repository' )->willReturn( $this->team_repository );
            $this->repository_provider->method( 'get_rubber_repository' )->willReturn( $this->rubber_repository );

            $this->service = new Fixture_Detail_Service(
                $this->repository_provider,
                $this->competition_service,
                $this->team_service,
                $this->permission_service,
                $this->link_service
            );
        }

        private function configure_rubber_loading_context( int $num_rubbers ): Fixture {
            $fixture = new Fixture();
            $fixture->set_id( 42 );
            $fixture->set_league_id( 10 );
            $fixture->set_season( '2026' );
            $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

            $league = $this->createStub( League::class );
            $league->method( 'get_id' )->willReturn( 10 );
            $league->method( 'get_event_id' )->willReturn( 20 );
            $league->method( 'get_name' )->willReturn( 'Premier League' );

            $event = $this->createStub( Event::class );
            $event->method( 'get_id' )->willReturn( 20 );
            $event->method( 'get_competition_id' )->willReturn( 30 );
            $event->method( 'get_num_rubbers' )->willReturn( $num_rubbers );

            $this->league_repository->method( 'find_by_id' )->willReturn( $league );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );
            $this->competition_service->method( 'get_by_id' )->willReturn( $this->createStub( Competition::class ) );
            $this->link_service->method( 'get_fixture_link' )->willReturn( 'https://example.com/fixture/42/' );

            return $fixture;
        }

        private function create_team_details_with_players( array $players ): Team_Details_DTO {
            $team = new class( $players ) extends Team {
                public array $players;

                public function __construct( array $players ) {
                    $this->players = $players;
                }
            };

            return new Team_Details_DTO( $team );
        }
    }
}
