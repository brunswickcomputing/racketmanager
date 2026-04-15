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
    // We'll redefine get_match here to return a mock-like object
    if ( ! function_exists( 'Racketmanager\get_match' ) ) {
        function get_match( $id ) {
            return $GLOBALS['mock_match'] ?? (object) [
                'id'     => $id,
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
                    'home' => (object) [ 'title' => 'Home Team', 'team_type' => 'P' ],
                    'away' => (object) [ 'title' => 'Away Team', 'team_type' => 'P' ],
                ],
                'link'   => 'match/1/',
                'season' => '2024',
                'host'   => 'home'
            ];
        }
    }

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
    use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;

    class Notification_Presenter_Test extends TestCase {
        private Tournament_Repository_Interface|MockObject $tournament_repository;
        private Notification_Presenter $presenter;

        public function test_present_match_notification(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 1 );
            $fixture->method( 'get_season' )->willReturn( '2024' );
            $fixture->method( 'get_host' )->willReturn( 'home' );
            $fixture->method( 'get_home_team' )->willReturn( "101" );
            $fixture->method( 'get_away_team' )->willReturn( "102" );

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
            $GLOBALS['wp_stubs_matches'][2] = $match;

            $args = [
                'competition_type' => 'league',
                'competition'      => 'League Name'
            ];

            $vars = $this->presenter->present_match_notification( $fixture, $args );

            $this->assertNotNull( $vars['tournament'], 'Tournament should not be null even for leagues' );
            $this->assertEquals( 'League Name', $vars['tournament']->name );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_match_notification_cup_template_name(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 3 );

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
            $GLOBALS['wp_stubs_matches'][3] = $match;

            $args = [
                'competition_type' => 'cup',
                'competition'      => 'Cup Name'
            ];

            $vars = $this->presenter->present_match_notification( $fixture, $args );

            $this->assertEquals( 'match-notification-cup', $vars['template'] );
        }

        protected function setUp(): void {
            parent::setUp();
            $team_repository             = $this->createStub( Team_Repository_Interface::class );
            $club_repository             = $this->createStub( Club_Repository_Interface::class );
            $this->tournament_repository = $this->createMock( Tournament_Repository_Interface::class );

            $this->presenter = new Notification_Presenter( $team_repository, $club_repository, $this->tournament_repository, 'https://example.com', 'Test Site' );
        }
    }
}
