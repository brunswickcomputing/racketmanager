<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Competition\Queries;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Competition\Ports\Competition_Service_Interface;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Query;
use Racketmanager\Application\Player\Ports\Player_Stats_Service_Interface;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use ReflectionException;
use ReflectionMethod;

final class Get_Competition_Clubs_Handler_Test extends TestCase {
    use Creates_Competition_Season_Service;

    private Competition_Service_Interface&MockObject $competition_service;
    private Club_Service_Interface&MockObject $club_service;
    private Player_Stats_Service_Interface&MockObject $player_stats_service;
    private Get_Competition_Clubs_Handler $handler;

    protected function setUp(): void {
        parent::setUp();

        $this->competition_service  = $this->createMock( Competition_Service_Interface::class );
        $this->club_service         = $this->createMock( Club_Service_Interface::class );
        $this->player_stats_service = $this->createMock( Player_Stats_Service_Interface::class );

        $this->handler = new Get_Competition_Clubs_Handler(
            $this->competition_service,
            $this->club_service,
            $this->player_stats_service,
            $this->competition_season_service()
        );
    }

    public function test_handle_returns_error_when_competition_identifier_is_empty(): void {
        $query = new Get_Competition_Clubs_Query( competition_id: null, competition_name: null );

        $this->competition_service->expects( $this->never() )->method( 'get_by_id' );
        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Competition not found', $result->error_message );
        $this->assertNull( $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( [], $result->clubs );
    }

    public function test_handle_returns_error_when_competition_not_found_by_id(): void {
        $query = new Get_Competition_Clubs_Query( competition_id: 42 );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 42 )
            ->willThrowException( new Competition_Not_Found_Exception( 'Competition with ID 42 not found' ) );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Competition with ID 42 not found', $result->error_message );
        $this->assertNull( $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( [], $result->clubs );
    }

    public function test_handle_returns_error_when_competition_not_found_by_slug(): void {
        $query = new Get_Competition_Clubs_Query( competition_name: 'summer-league' );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 'summer league' )
            ->willThrowException( new Competition_Not_Found_Exception( 'Competition not found' ) );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Competition not found', $result->error_message );
        $this->assertNull( $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( [], $result->clubs );
    }

    public function test_handle_returns_error_when_club_not_found_by_shortcode(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );

        $query = new Get_Competition_Clubs_Query(
            competition_id: 10,
            club_identifier: 'NONEXISTENT'
        );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->club_service->expects( $this->once() )
            ->method( 'get_club_by_shortcode' )
            ->with( 'NONEXISTENT' )
            ->willThrowException( new Club_Not_Found_Exception( 'Original club error' ) );

        $this->club_service->expects( $this->never() )->method( 'get_club' );
        $this->competition_service->expects( $this->never() )->method( 'get_teams_by_query' );
        $this->competition_service->expects( $this->never() )->method( 'get_matches_for_competition' );
        $this->competition_service->expects( $this->never() )->method( 'get_players_for_competition_query' );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Club not found', $result->error_message );
        $this->assertNull( $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( [], $result->clubs );
    }

    public function test_handle_returns_error_when_club_not_found_by_numeric_id(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );

        $query = new Get_Competition_Clubs_Query(
            competition_id: 10,
            club_identifier: '99'
        );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->club_service->expects( $this->once() )
            ->method( 'get_club' )
            ->with( 99 )
            ->willThrowException( new Club_Not_Found_Exception( 'Original club error' ) );

        $this->club_service->expects( $this->never() )->method( 'get_club_by_shortcode' );
        $this->competition_service->expects( $this->never() )->method( 'get_teams_by_query' );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Club not found', $result->error_message );
        $this->assertNull( $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( [], $result->clubs );
    }

    public function test_handle_returns_error_when_specified_season_not_found_for_competition_clubs(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );

        $query = new Get_Competition_Clubs_Query(
            competition_id: 10,
            season: 2024
        );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_club_details_for_competition' )
            ->with( 10, 2024 )
            ->willThrowException( new Season_Not_Found_Exception( 'Season 2024 not found for competition' ) );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Season 2024 not found for competition', $result->error_message );
        $this->assertNull( $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( [], $result->clubs );
    }

    public function test_handle_returns_error_when_default_season_not_found_for_competition_clubs(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );

        $query = new Get_Competition_Clubs_Query( competition_id: 10 );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_club_details_for_competition' )
            ->with( 10, 2026 )
            ->willThrowException( new Season_Not_Found_Exception( 'Season not found' ) );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Season not found', $result->error_message );
        $this->assertNull( $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( [], $result->clubs );
    }

    /**
     * Tests that handle resolves and hydrates a Club domain instance when a numeric club identifier is provided.
     */
    public function test_handle_resolves_and_hydrates_club_domain_instance_by_numeric_id(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $club        = Club_Test_Factory::create(
            id: 12,
            name: 'Westcliff Lawn Tennis Club',
            shortcode: 'WLTC'
        );
        $query       = new Get_Competition_Clubs_Query(
            competition_id: 10,
            club_identifier: '12'
        );

        $teams             = [
            (object) [ 'id' => 1, 'name' => 'Men 1st' ],
            (object) [ 'id' => 2, 'name' => 'Mixed 1st' ],
        ];
        $next_matches      = [
            (object) [ 'id' => 201, 'date' => '2026-06-15 18:30:00' ],
        ];
        $latest_matches    = [
            (object) [ 'id' => 101, 'date' => '2026-05-20 19:00:00' ],
        ];
        $raw_players       = [
            Player_Test_Factory::create( id: 301, display_name: 'Player One' ),
        ];
        $populated_players = [
            (object) [ 'id' => 301, 'display_name' => 'Player One', 'stats' => [ 'played' => 4, 'won' => 3 ] ],
        ];

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->club_service->expects( $this->once() )
            ->method( 'get_club' )
            ->with( 12 )
            ->willReturn( $club );

        $this->club_service->expects( $this->never() )
            ->method( 'get_club_by_shortcode' );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_teams_by_query' )
            ->with(
                $competition,
                [
                    'club'   => 12,
                    'season' => '2026',
                    'status' => 1,
                ]
            )
            ->willReturn( $teams );

        $this->competition_service->expects( $this->exactly( 2 ) )
            ->method( 'get_matches_for_competition' )
            ->willReturnCallback(
                static function ( Competition $comp, array $args ) use ( $competition, $next_matches, $latest_matches ): array {
                    self::assertSame( $competition, $comp );

                    if ( 'next' === $args['time'] ) {
                        self::assertSame(
                            [
                                'season'  => '2026',
                                'club'    => 12,
                                'time'    => 'next',
                                'orderby' => [
                                    'date'      => 'ASC',
                                    'league_id' => 'DESC',
                                ],
                            ],
                            $args
                        );
                        return $next_matches;
                    }

                    if ( 'latest' === $args['time'] ) {
                        self::assertSame(
                            [
                                'season'  => '2026',
                                'club'    => 12,
                                'time'    => 'latest',
                                'orderby' => [
                                    'date'      => 'ASC',
                                    'league_id' => 'DESC',
                                ],
                            ],
                            $args
                        );
                        return $latest_matches;
                    }

                    self::fail( 'Unexpected time argument: ' . ( $args['time'] ?? 'null' ) );
                }
            );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_players_for_competition_query' )
            ->with(
                $competition,
                [
                    'club'   => 12,
                    'season' => '2026',
                ]
            )
            ->willReturn( $raw_players );

        $this->player_stats_service->expects( $this->once() )
            ->method( 'populate_players_stats' )
            ->with( $raw_players, $competition, '2026', 'competition' )
            ->willReturn( $populated_players );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertNull( $result->error_message );
        $this->assertSame( $competition, $result->competition );
        $this->assertSame( $club, $result->competition_club );
        $this->assertSame( [], $result->clubs );
        $this->assertSame( 'clubs', $result->template );

        $this->assertInstanceOf( Club::class, $result->competition_club );
        $this->assertSame( $teams, $club->teams );
        $this->assertSame( [ '2026-06-15' => [ $next_matches[0] ] ], $club->matches );
        $this->assertSame( [ '2026-05-20' => [ $latest_matches[0] ] ], $club->results );
        $this->assertSame( $populated_players, $club->players );
    }

    /**
     * Tests that handle resolves and hydrates a Club domain instance when a shortcode identifier is provided.
     */
    public function test_handle_resolves_and_hydrates_club_by_shortcode_identifier(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $club        = Club_Test_Factory::create(
            id: 15,
            name: 'Westcliff Hardcourt Tennis Club',
            shortcode: 'WTC'
        );
        $query       = new Get_Competition_Clubs_Query(
            competition_id: 10,
            club_identifier: 'WTC'
        );

        $teams             = [ (object) [ 'id' => 10, 'name' => 'Ladies 1st' ] ];
        $next_matches      = [ (object) [ 'id' => 301, 'date' => '2026-07-01 18:00:00' ] ];
        $latest_matches    = [ (object) [ 'id' => 302, 'date' => '2026-06-20 18:00:00' ] ];
        $raw_players       = [ Player_Test_Factory::create( id: 401, display_name: 'Player Two' ) ];
        $populated_players = [ (object) [ 'id' => 401, 'display_name' => 'Player Two', 'stats' => [] ] ];

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->club_service->expects( $this->once() )
            ->method( 'get_club_by_shortcode' )
            ->with( 'WTC' )
            ->willReturn( $club );

        $this->club_service->expects( $this->never() )
            ->method( 'get_club' );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_teams_by_query' )
            ->with(
                $competition,
                [
                    'club'   => 15,
                    'season' => '2026',
                    'status' => 1,
                ]
            )
            ->willReturn( $teams );

        $this->competition_service->expects( $this->exactly( 2 ) )
            ->method( 'get_matches_for_competition' )
            ->willReturnCallback(
                static function ( Competition $comp, array $args ) use ( $competition, $next_matches, $latest_matches ): array {
                    self::assertSame( $competition, $comp );

                    if ( 'next' === $args['time'] ) {
                        self::assertSame(
                            [
                                'season'  => '2026',
                                'club'    => 15,
                                'time'    => 'next',
                                'orderby' => [
                                    'date'      => 'ASC',
                                    'league_id' => 'DESC',
                                ],
                            ],
                            $args
                        );
                        return $next_matches;
                    }

                    if ( 'latest' === $args['time'] ) {
                        self::assertSame(
                            [
                                'season'  => '2026',
                                'club'    => 15,
                                'time'    => 'latest',
                                'orderby' => [
                                    'date'      => 'ASC',
                                    'league_id' => 'DESC',
                                ],
                            ],
                            $args
                        );
                        return $latest_matches;
                    }

                    self::fail( 'Unexpected time argument: ' . ( $args['time'] ?? 'null' ) );
                }
            );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_players_for_competition_query' )
            ->with(
                $competition,
                [
                    'club'   => 15,
                    'season' => '2026',
                ]
            )
            ->willReturn( $raw_players );

        $this->player_stats_service->expects( $this->once() )
            ->method( 'populate_players_stats' )
            ->with( $raw_players, $competition, '2026', 'competition' )
            ->willReturn( $populated_players );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( $club, $result->competition_club );
        $this->assertSame( 15, $result->competition_club->get_id() );
        $this->assertSame( $teams, $club->teams );
        $this->assertSame( [ '2026-07-01' => [ $next_matches[0] ] ], $club->matches );
        $this->assertSame( [ '2026-06-20' => [ $latest_matches[0] ] ], $club->results );
        $this->assertSame( $populated_players, $club->players );
    }

    /**
     * Tests date grouping logic for both fixtures and results across multiple same and different dates.
     */
    public function test_handle_groups_fixtures_and_results_by_date_with_multiple_same_and_different_dates(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $club        = Club_Test_Factory::create( id: 12 );
        $query       = new Get_Competition_Clubs_Query(
            competition_id: 10,
            club_identifier: '12'
        );

        $fixture_1 = (object) [ 'id' => 1, 'date' => '2026-06-01 10:00:00' ];
        $fixture_2 = (object) [ 'id' => 2, 'date' => '2026-06-01 14:30:00' ];
        $fixture_3 = (object) [ 'id' => 3, 'date' => '2026-06-08 19:00:00' ];

        $result_1 = (object) [ 'id' => 4, 'date' => '2026-05-10 09:30:00' ];
        $result_2 = (object) [ 'id' => 5, 'date' => '2026-05-17 11:00:00' ];
        $result_3 = (object) [ 'id' => 6, 'date' => '2026-05-17 15:45:00' ];

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->club_service->expects( $this->once() )
            ->method( 'get_club' )
            ->with( 12 )
            ->willReturn( $club );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_teams_by_query' )
            ->willReturn( [] );

        $this->competition_service->expects( $this->exactly( 2 ) )
            ->method( 'get_matches_for_competition' )
            ->willReturnCallback(
                static function ( Competition $comp, array $args ) use ( $fixture_1, $fixture_2, $fixture_3, $result_1, $result_2, $result_3 ): array {
                    if ( 'next' === $args['time'] ) {
                        return [ $fixture_1, $fixture_2, $fixture_3 ];
                    }

                    if ( 'latest' === $args['time'] ) {
                        return [ $result_1, $result_2, $result_3 ];
                    }

                    return [];
                }
            );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_players_for_competition_query' )
            ->willReturn( [] );

        $this->player_stats_service->expects( $this->once() )
            ->method( 'populate_players_stats' )
            ->willReturn( [] );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );

        // Verify upcoming fixtures date grouping.
        $this->assertSame( [ '2026-06-01', '2026-06-08' ], array_keys( $club->matches ) );
        $this->assertCount( 2, $club->matches['2026-06-01'] );
        $this->assertSame( [ $fixture_1, $fixture_2 ], $club->matches['2026-06-01'] );
        $this->assertCount( 1, $club->matches['2026-06-08'] );
        $this->assertSame( [ $fixture_3 ], $club->matches['2026-06-08'] );

        // Verify past results date grouping.
        $this->assertSame( [ '2026-05-10', '2026-05-17' ], array_keys( $club->results ) );
        $this->assertCount( 1, $club->results['2026-05-10'] );
        $this->assertSame( [ $result_1 ], $club->results['2026-05-10'] );
        $this->assertCount( 2, $club->results['2026-05-17'] );
        $this->assertSame( [ $result_2, $result_3 ], $club->results['2026-05-17'] );
    }

    /**
     * Tests that player statistics are enriched and populated on the club.
     */
    public function test_handle_populates_player_statistics_via_player_stats_service(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $club        = Club_Test_Factory::create( id: 12 );
        $query       = new Get_Competition_Clubs_Query(
            competition_id: 10,
            club_identifier: '12'
        );

        $raw_players      = [
            Player_Test_Factory::create( id: 101, display_name: 'John Doe' ),
            Player_Test_Factory::create( id: 102, display_name: 'Jane Smith' ),
        ];
        $enriched_players = [
            (object) [
                'id'           => 101,
                'display_name' => 'John Doe',
                'stats'        => (object) [
                    'played' => 6,
                    'won'    => 5,
                    'lost'   => 1,
                ],
            ],
            (object) [
                'id'           => 102,
                'display_name' => 'Jane Smith',
                'stats'        => (object) [
                    'played' => 4,
                    'won'    => 4,
                    'lost'   => 0,
                ],
            ],
        ];

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->club_service->expects( $this->once() )
            ->method( 'get_club' )
            ->with( 12 )
            ->willReturn( $club );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_teams_by_query' )
            ->willReturn( [] );

        $this->competition_service->expects( $this->exactly( 2 ) )
            ->method( 'get_matches_for_competition' )
            ->willReturn( [] );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_players_for_competition_query' )
            ->with(
                $competition,
                [
                    'club'   => 12,
                    'season' => '2026',
                ]
            )
            ->willReturn( $raw_players );

        $this->player_stats_service->expects( $this->once() )
            ->method( 'populate_players_stats' )
            ->with( $raw_players, $competition, '2026', 'competition' )
            ->willReturn( $enriched_players );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( $enriched_players, $club->players );
        $this->assertCount( 2, $club->players );
        $this->assertSame( 101, $club->players[0]->id );
        $this->assertSame( 6, $club->players[0]->stats->played );
        $this->assertSame( 5, $club->players[0]->stats->won );
        $this->assertSame( 102, $club->players[1]->id );
        $this->assertSame( 4, $club->players[1]->stats->played );
        $this->assertSame( 4, $club->players[1]->stats->won );
    }

    /**
     * Tests fallback dynamic property assignment when club is a generic object without get_id() and not a Club instance.
     *
     * @throws ReflectionException When ReflectionMethod instantiation or invocation fails.
     */
    public function test_hydrate_club_assigns_dynamic_properties_when_generic_object(): void {
        $competition  = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $generic_club = (object) [
            'id'   => 88,
            'name' => 'Generic Tennis Club',
        ];

        $teams             = [ (object) [ 'id' => 5, 'name' => 'Generic Team' ] ];
        $next_matches      = [ (object) [ 'id' => 10, 'date' => '2026-06-25 18:00:00' ] ];
        $latest_matches    = [ (object) [ 'id' => 11, 'date' => '2026-05-30 18:00:00' ] ];
        $raw_players       = [ (object) [ 'id' => 20 ] ];
        $populated_players = [ (object) [ 'id' => 20, 'stats' => [ 'played' => 1 ] ] ];

        $this->assertFalse( $generic_club instanceof Club );
        $this->assertFalse( method_exists( $generic_club, 'get_id' ) );

        $this->club_service->expects( $this->never() )->method( $this->anything() );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_teams_by_query' )
            ->with(
                $competition,
                [
                    'club'   => 88,
                    'season' => '2026',
                    'status' => 1,
                ]
            )
            ->willReturn( $teams );

        $this->competition_service->expects( $this->exactly( 2 ) )
            ->method( 'get_matches_for_competition' )
            ->willReturnCallback(
                static function ( Competition $comp, array $args ) use ( $competition, $next_matches, $latest_matches ): array {
                    self::assertSame( $competition, $comp );

                    if ( 'next' === $args['time'] ) {
                        self::assertSame( 88, $args['club'] );
                        return $next_matches;
                    }

                    if ( 'latest' === $args['time'] ) {
                        self::assertSame( 88, $args['club'] );
                        return $latest_matches;
                    }

                    return [];
                }
            );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_players_for_competition_query' )
            ->with(
                $competition,
                [
                    'club'   => 88,
                    'season' => '2026',
                ]
            )
            ->willReturn( $raw_players );

        $this->player_stats_service->expects( $this->once() )
            ->method( 'populate_players_stats' )
            ->with( $raw_players, $competition, '2026', 'competition' )
            ->willReturn( $populated_players );

        $ref_method = new ReflectionMethod( Get_Competition_Clubs_Handler::class, 'hydrate_club' );
        $ref_method->invoke( $this->handler, $generic_club, $competition, '2026' );

        // Verify fallback dynamic properties on generic object.
        $this->assertSame( $teams, $generic_club->teams );
        $this->assertSame( [ '2026-06-25' => [ $next_matches[0] ] ], $generic_club->matches );
        $this->assertSame( [ '2026-05-30' => [ $latest_matches[0] ] ], $generic_club->results );
        $this->assertSame( $populated_players, $generic_club->players );
    }

    /**
     * Tests fallback dynamic property assignment when generic object has a get_id() method.
     *
     * @throws ReflectionException When ReflectionMethod instantiation or invocation fails.
     */
    public function test_hydrate_club_assigns_dynamic_properties_when_generic_object_has_get_id_method(): void {
        $competition  = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $generic_club = new class() {
            public int $id        = 77;
            public string $name   = 'Custom Object Club';
            public mixed $teams   = null;
            public mixed $matches = null;
            public mixed $results = null;
            public mixed $players = null;

            public function get_id(): int {
                return $this->id;
            }
        };

        $teams             = [ (object) [ 'id' => 7 ] ];
        $next_matches      = [ (object) [ 'id' => 17, 'date' => '2026-07-10 18:00:00' ] ];
        $latest_matches    = [ (object) [ 'id' => 18, 'date' => '2026-06-10 18:00:00' ] ];
        $raw_players       = [ (object) [ 'id' => 27 ] ];
        $populated_players = [ (object) [ 'id' => 27, 'stats' => [] ] ];

        $this->assertFalse( $generic_club instanceof Club );
        $this->assertTrue( method_exists( $generic_club, 'get_id' ) );

        $this->club_service->expects( $this->never() )->method( $this->anything() );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_teams_by_query' )
            ->with(
                $competition,
                [
                    'club'   => 77,
                    'season' => '2026',
                    'status' => 1,
                ]
            )
            ->willReturn( $teams );

        $this->competition_service->expects( $this->exactly( 2 ) )
            ->method( 'get_matches_for_competition' )
            ->willReturnCallback(
                static function ( Competition $comp, array $args ) use ( $competition, $next_matches, $latest_matches ): array {
                    self::assertSame( $competition, $comp );

                    if ( 'next' === $args['time'] ) {
                        self::assertSame( 77, $args['club'] );
                        return $next_matches;
                    }

                    if ( 'latest' === $args['time'] ) {
                        self::assertSame( 77, $args['club'] );
                        return $latest_matches;
                    }

                    return [];
                }
            );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_players_for_competition_query' )
            ->with(
                $competition,
                [
                    'club'   => 77,
                    'season' => '2026',
                ]
            )
            ->willReturn( $raw_players );

        $this->player_stats_service->expects( $this->once() )
            ->method( 'populate_players_stats' )
            ->with( $raw_players, $competition, '2026', 'competition' )
            ->willReturn( $populated_players );

        $ref_method = new ReflectionMethod( Get_Competition_Clubs_Handler::class, 'hydrate_club' );
        $ref_method->invoke( $this->handler, $generic_club, $competition, '2026' );

        $this->assertSame( $teams, $generic_club->teams );
        $this->assertSame( [ '2026-07-10' => [ $next_matches[0] ] ], $generic_club->matches );
        $this->assertSame( [ '2026-06-10' => [ $latest_matches[0] ] ], $generic_club->results );
        $this->assertSame( $populated_players, $generic_club->players );
    }

    /**
     * Tests that handle returns competition clubs overview when no club identifier is provided and explicit season is set.
     */
    public function test_handle_returns_competition_clubs_overview_with_explicit_season_integer(): void {
        $competition    = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $expected_clubs = [
            (object) [ 'id' => 1, 'name' => 'Westcliff Lawn' ],
            (object) [ 'id' => 2, 'name' => 'Leigh Road Baptist' ],
        ];

        $query = new Get_Competition_Clubs_Query(
            competition_id: 10,
            season: 2024
        );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_club_details_for_competition' )
            ->with( 10, 2024 )
            ->willReturn( $expected_clubs );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );
        $this->competition_service->expects( $this->never() )->method( 'get_teams_by_query' );
        $this->competition_service->expects( $this->never() )->method( 'get_matches_for_competition' );
        $this->competition_service->expects( $this->never() )->method( 'get_players_for_competition_query' );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertNull( $result->error_message );
        $this->assertSame( $competition, $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( $expected_clubs, $result->clubs );
        $this->assertSame( 'clubs', $result->template );
    }

    /**
     * Tests that handle returns competition clubs overview resolving default season integer when query season is null.
     */
    public function test_handle_returns_competition_clubs_overview_with_default_numeric_season_when_query_season_is_null(): void {
        $competition    = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $expected_clubs = [
            (object) [ 'id' => 3, 'name' => 'Thorpe Bay' ],
            (object) [ 'id' => 4, 'name' => 'Rayleigh' ],
        ];

        $query = new Get_Competition_Clubs_Query(
            competition_id: 10,
            season: null
        );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_club_details_for_competition' )
            ->with( 10, 2026 )
            ->willReturn( $expected_clubs );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertNull( $result->error_message );
        $this->assertSame( $competition, $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( $expected_clubs, $result->clubs );
        $this->assertSame( 'clubs', $result->template );
    }

    /**
     * Tests that handle passes null season integer when competition current season name is non-numeric.
     */
    public function test_handle_returns_competition_clubs_overview_with_null_season_when_season_name_is_non_numeric(): void {
        $competition    = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( 'summer' )
        );
        $expected_clubs = [
            (object) [ 'id' => 5, 'name' => 'Wickford' ],
        ];

        $query = new Get_Competition_Clubs_Query(
            competition_id: 10,
            season: null
        );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_club_details_for_competition' )
            ->with( 10, null )
            ->willReturn( $expected_clubs );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertNull( $result->error_message );
        $this->assertSame( $competition, $result->competition );
        $this->assertNull( $result->competition_club );
        $this->assertSame( $expected_clubs, $result->clubs );
        $this->assertSame( 'clubs', $result->template );
    }

    /**
     * Tests that template suffix is formatted as clubs-{template} when custom template string is provided.
     */
    public function test_handle_formats_custom_template_suffix(): void {
        $competition    = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );
        $expected_clubs = [
            (object) [ 'id' => 1, 'name' => 'Westcliff Lawn' ],
        ];

        $query = new Get_Competition_Clubs_Query(
            competition_id: 10,
            template: 'cards'
        );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_club_details_for_competition' )
            ->with( 10, 2026 )
            ->willReturn( $expected_clubs );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'clubs-cards', $result->template );
    }

    /**
     * Tests that template defaults to 'clubs' when template string is empty.
     */
    public function test_handle_defaults_to_clubs_template_when_template_is_empty_string(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            seasons: Competition_Test_Factory::season_list( '2026' )
        );

        $query = new Get_Competition_Clubs_Query(
            competition_id: 10,
            template: ''
        );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_by_id' )
            ->with( 10 )
            ->willReturn( $competition );

        $this->competition_service->expects( $this->once() )
            ->method( 'get_club_details_for_competition' )
            ->with( 10, 2026 )
            ->willReturn( [] );

        $this->club_service->expects( $this->never() )->method( $this->anything() );
        $this->player_stats_service->expects( $this->never() )->method( $this->anything() );

        $result = $this->handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'clubs', $result->template );
        $this->assertSame( [], $result->clubs );
    }
}
