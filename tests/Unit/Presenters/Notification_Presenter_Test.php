<?php
declare( strict_types=1 );

namespace Racketmanager\Presenters {
    if ( ! function_exists( 'Racketmanager\Presenters\__' ) ) {
        function __( $text, $domain ) {
            return $text;
        }
    }
}

namespace Racketmanager {

    if ( ! function_exists( 'Racketmanager\seo_url' ) ) {
        function seo_url( $url ): string {
            return strtolower( str_replace( ' ', '-', $url ) );
        }
    }
}

namespace Racketmanager\Tests\Unit\Presenters {

    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;
    use Racketmanager\Domain\Fixture\Fixture;
    use Racketmanager\Domain\Tournament;
    use Racketmanager\Presenters\Notification_Presenter;
    use Racketmanager\Services\Fixture\Fixture_Link_Service;
    use Racketmanager\Services\Fixture\Fixture_Permission_Service;
    use Racketmanager\Services\Competition_Service;
    use Racketmanager\Services\Team_Service;
    use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
    use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
    use Racketmanager\Domain\Competition\League;
    use Racketmanager\Domain\Competition\Event;
    use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
    use Racketmanager\Domain\Team;
    use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;

    class Notification_Presenter_Test extends TestCase {
        private Tournament_Repository_Interface|MockObject $tournament_repository;
        private Fixture_Link_Service|MockObject $fixture_link_service;
        private Team_Service|MockObject $team_service;
        private Competition_Service|MockObject $competition_service;
        private Fixture_Permission_Service|MockObject $permission_service;
        private League_Repository_Interface|MockObject $league_repository;
        private Notification_Presenter $presenter;

        private function create_mock_fixture_details( $fixture_id = 1 ): Fixture_Details_DTO {
            $league = $this->createStub( League::class );
            $league->method( 'get_name' )->willReturn( 'Test League' );

            $event = $this->createStub( Event::class );
            $event->method( 'get_name' )->willReturn( 'Test Event' );
            $event->method( 'get_type' )->willReturn( 'LD' );

            $home_team = $this->createStub( Team::class );
            $home_team->method( 'get_name' )->willReturn( 'Home Team' );
            $home_team_details = new Team_Details_DTO( $home_team, null, null );

            $away_team = $this->createStub( Team::class );
            $away_team->method( 'get_name' )->willReturn( 'Away Team' );
            $away_team_details = new Team_Details_DTO( $away_team, null, null );

            return new Fixture_Details_DTO(
                $this->createStub( Fixture::class ),
                $league,
                $event,
                $this->createStub( \Racketmanager\Domain\Competition\Competition::class ),
                $home_team_details,
                $away_team_details,
                null,
                null,
                null,
                'match/' . $fixture_id . '/',
                null,
                [],
                ''
            );
        }

        public function test_present_match_notification(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 1 );
            $fixture->method( 'get_season' )->willReturn( '2024' );
            $fixture->method( 'get_host' )->willReturn( 'home' );
            $fixture->method( 'get_home_team' )->willReturn( "101" );
            $fixture->method( 'get_away_team' )->willReturn( "102" );
            $fixture->method( 'get_league_id' )->willReturn( 10 );

            $match                          = (object) [
                'id'     => 1,
                'league' => (object) [
                    'title' => 'Test League',
                    'type'  => 'LD',
                    'event' => (object) [
                        'name'        => 'Test Event',
                        'competition' => (object) [
                            'type' => 'tournament',
                            'id'   => 123
                        ]
                    ]
                ],
                'teams'  => [
                    'home' => (object) [ 'title' => 'Home Team', 'team_type' => 'P', 'players' => [] ],
                    'away' => (object) [ 'title' => 'Away Team', 'team_type' => 'P', 'players' => [] ],
                ],
                'link'   => 'match/1/',
                'season' => '2024',
                'host'   => 'home'
            ];
            $GLOBALS['mock_match'] = $match;
            $GLOBALS['wp_stubs_matches'][1] = $match;

            $args = [
                'competition_type'  => 'tournament',
                'tournament'        => '123,2024',
                'tournament_search' => 'shortcode'
            ];

            $tournament       = $this->createStub( Tournament::class );
            $tournament->link = '/tournament/123/';
            $tournament->name = 'Mock Tournament';
            $this->tournament_repository->method( 'find_by_id' )->with( '123,2024', 'shortcode' )->willReturn( $tournament );

            $league = $this->createStub( League::class );
            $league->method( 'get_name' )->willReturn( 'Test League' );
            $league->method( 'get_event_id' )->willReturn( 100 );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );

            $event = $this->createStub( Event::class );
            $event->method( 'get_name' )->willReturn( 'Test Event' );
            $event->method( 'get_type' )->willReturn( 'LD' );
            $event->method( 'get_competition_id' )->willReturn( 50 );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

            $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );
            $competition->method( 'get_name' )->willReturn( 'Test Competition' );
            $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

            $home_team_details = new Team_Details_DTO( $this->createStub( Team::class ), null, null );
            $home_team_details->team->method( 'get_name' )->willReturn( 'Home Team' );
            $away_team_details = new Team_Details_DTO( $this->createStub( Team::class ), null, null );
            $away_team_details->team->method( 'get_name' )->willReturn( 'Away Team' );
            
            $this->team_service->method( 'get_team_details' )->willReturnMap([
                [101, $home_team_details],
                [102, $away_team_details]
            ]);

            $vars = $this->presenter->present_match_notification( $fixture, $args );

            $this->assertArrayHasKey( 'action_url', $vars );
            $this->assertStringContainsString( 'Mock Tournament', $vars['tournament_link'] );
            $this->assertStringContainsString( 'Test Event', $vars['draw_link'] );
            $this->assertEquals( 'Home Team', $vars['teams']['home']->name );
            $this->assertEquals( 'Away Team', $vars['teams']['away']->name );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_match_notification_league_is_not_null(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 2 );
            $fixture->method( 'get_league_id' )->willReturn( 10 );
            $fixture->method( 'get_season' )->willReturn( '2024' );
            $fixture->method( 'get_home_team' )->willReturn( "101" );
            $fixture->method( 'get_away_team' )->willReturn( "102" );

            $match                          = (object) [
                'id'     => 2,
                'league' => (object) [
                    'title' => 'Test League',
                    'type'  => 'LD',
                    'event' => (object) [
                        'name'        => 'Test Event',
                        'competition' => (object) [
                            'type' => 'league',
                            'name' => 'League Name',
                            'id'   => 124
                        ]
                    ]
                ],
                'teams'  => [
                    'home' => (object) [ 'title' => 'Home Team', 'team_type' => 'P', 'players' => [] ],
                    'away' => (object) [ 'title' => 'Away Team', 'team_type' => 'P', 'players' => [] ],
                ],
                'link'   => 'match/2/',
                'season' => '2024',
                'host'   => 'home'
            ];
            $match->teams['home']->title = 'Home Team';
            $match->teams['away']->title = 'Away Team';
            $GLOBALS['mock_match'] = $match;
            $GLOBALS['wp_stubs_matches'][2] = $match;

            $args = [
                'competition_type' => 'league',
                'competition'      => 'League Name'
            ];

            $league = $this->createStub( League::class );
            $league->method( 'get_name' )->willReturn( 'Test League' );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );

            $event = $this->createStub( Event::class );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

            $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );
            $competition->method( 'get_name' )->willReturn( 'League Name' );
            $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

            $home_team_details = new Team_Details_DTO( $this->createStub( Team::class ), null, null );
            $home_team_details->team->method( 'get_name' )->willReturn( 'Home Team' );
            $away_team_details = new Team_Details_DTO( $this->createStub( Team::class ), null, null );
            $away_team_details->team->method( 'get_name' )->willReturn( 'Away Team' );
            
            $this->team_service->method( 'get_team_details' )->willReturnMap([
                [101, $home_team_details],
                [102, $away_team_details]
            ]);

            $vars = $this->presenter->present_match_notification( $fixture, $args );

            $this->assertNotNull( $vars['tournament'], 'Tournament should not be null even for leagues' );
            $this->assertEquals( 'League Name', $vars['tournament']->name );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_match_notification_cup_template_name(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 3 );
            $fixture->method( 'get_league_id' )->willReturn( 10 );
            $fixture->method( 'get_season' )->willReturn( '2024' );
            $fixture->method( 'get_home_team' )->willReturn( "101" );
            $fixture->method( 'get_away_team' )->willReturn( "102" );

            $match                          = (object) [
                'id'     => 3,
                'league' => (object) [
                    'title' => 'Test League',
                    'type'  => 'LD',
                    'event' => (object) [
                        'name'        => 'Test Event',
                        'competition' => (object) [
                            'type' => 'cup',
                            'id'   => 125
                        ]
                    ]
                ],
                'teams'  => [
                    'home' => (object) [ 'title' => 'Home Team', 'team_type' => 'T' ],
                    'away' => (object) [ 'title' => 'Away Team', 'team_type' => 'T' ],
                ],
                'link'   => 'match/3/',
                'season' => '2024',
                'host'   => 'home'
            ];
            $match->league->title = 'Test League';
            $match->teams['home']->title = 'Home Team';
            $match->teams['away']->title = 'Away Team';
            $GLOBALS['mock_match'] = $match;
            $GLOBALS['wp_stubs_matches'][3] = $match;

            $args = [
                'competition_type' => 'cup',
                'competition'      => 'Cup Name'
            ];

            $league = $this->createStub( League::class );
            $league->method( 'get_name' )->willReturn( 'Test League' );
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );

            $event = $this->createStub( Event::class );
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

            $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );
            $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

            $home_team_details = new Team_Details_DTO( $this->createStub( Team::class ), null, null );
            $home_team_details->team->method( 'get_name' )->willReturn( 'Home Team' );
            $away_team_details = new Team_Details_DTO( $this->createStub( Team::class ), null, null );
            $away_team_details->team->method( 'get_name' )->willReturn( 'Away Team' );
            
            $this->team_service->method( 'get_team_details' )->willReturnMap([
                [101, $home_team_details],
                [102, $away_team_details]
            ]);

            $vars = $this->presenter->present_match_notification( $fixture, $args );

            $this->assertEquals( 'match-notification-cup', $vars['template'] );
        }

        protected function setUp(): void {
            parent::setUp();
            $this->tournament_repository = $this->createMock( Tournament_Repository_Interface::class );
            $this->fixture_link_service = $this->createStub( Fixture_Link_Service::class );
            $this->team_service = $this->createStub( Team_Service::class );
            $this->competition_service = $this->createStub( Competition_Service::class );
            $this->permission_service = $this->createStub( Fixture_Permission_Service::class );
            $this->league_repository = $this->createStub( League_Repository_Interface::class );

            $this->competition_service->method( 'get_league_repository' )->willReturn( $this->league_repository );

            $team_repository = $this->createStub( Team_Repository_Interface::class );
            $club_repository = $this->createStub( Club_Repository_Interface::class );

            $this->presenter = new Notification_Presenter(
                $team_repository,
                $club_repository,
                $this->tournament_repository,
                $this->fixture_link_service,
                $this->team_service,
                $this->competition_service,
                $this->permission_service,
                'https://example.com',
                'Test Site'
            );
        }
    }
}
