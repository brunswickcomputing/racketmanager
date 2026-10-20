<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters {
    if ( ! function_exists( 'Racketmanager\Presentation\Presenters\__' ) ) {
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

namespace Racketmanager\Tests\Unit\Presentation\Presenters {

    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;
    use Racketmanager\Domain\Fixture\Fixture;
    use Racketmanager\Domain\Tournament;
    use Racketmanager\Presentation\Presenters\Notification_Presenter;
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
    use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
    use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
    use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

    class Notification_Presenter_Test extends TestCase {
        use Manages_Wordpress_Globals;

        private Tournament_Repository_Interface|MockObject $tournament_repository;
        private Fixture_Link_Service|MockObject $fixture_link_service;
        private Team_Service|MockObject $team_service;
        private Competition_Service|MockObject $competition_service;
        private Fixture_Permission_Service|MockObject $permission_service;
        private League_Repository_Interface|MockObject $league_repository;
        private Fixture_Repository_Interface|MockObject $fixture_repository;
        private Notification_Presenter $presenter;

        private function setup_notification_services(
            string $league_name = 'Test League',
            string $competition_name = 'Test Competition',
            ?int $event_id = 100,
            ?int $competition_id = 50,
            ?string $event_name = 'Test Event',
            ?string $event_type = 'LD'
        ): void {
            $league = $this->createStub( League::class );
            $league->method( 'get_name' )->willReturn( $league_name );
            if ( null !== $event_id ) {
                $league->method( 'get_event_id' )->willReturn( $event_id );
            }
            $this->league_repository->method( 'find_by_id' )->willReturn( $league );

            $event = $this->createStub( Event::class );
            if ( null !== $event_name ) {
                $event->method( 'get_name' )->willReturn( $event_name );
            }
            if ( null !== $event_type ) {
                $event->method( 'get_type' )->willReturn( $event_type );
            }
            if ( null !== $competition_id ) {
                $event->method( 'get_competition_id' )->willReturn( $competition_id );
            }
            $this->competition_service->method( 'get_event_by_id' )->willReturn( $event );

            $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );
            $competition->method( 'get_name' )->willReturn( $competition_name );
            $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

            $home_team_details = new Team_Details_DTO( $this->createStub( Team::class ), null, null );
            $home_team_details->team->method( 'get_name' )->willReturn( 'Home Team' );
            $away_team_details = new Team_Details_DTO( $this->createStub( Team::class ), null, null );
            $away_team_details->team->method( 'get_name' )->willReturn( 'Away Team' );

            $this->team_service->method( 'get_team_details' )->willReturnMap( [
                [ 101, $home_team_details ],
                [ 102, $away_team_details ],
            ] );
        }

        public function test_present_match_notification(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 1 );
            $fixture->method( 'get_season' )->willReturn( '2024' );
            $fixture->method( 'get_host' )->willReturn( 'home' );
            $fixture->method( 'get_home_team' )->willReturn( "101" );
            $fixture->method( 'get_away_team' )->willReturn( "102" );
            $fixture->method( 'get_league_id' )->willReturn( 10 );

            $args = [
                'competition_type'  => 'tournament',
                'tournament'        => '123,2024',
                'tournament_search' => 'shortcode'
            ];

            $tournament       = $this->createStub( Tournament::class );
            $tournament->name = 'Mock Tournament';
            $this->tournament_repository->expects( $this->once() )->method( 'find_by_id' )->with( '123,2024', 'shortcode' )->willReturn( $tournament );

            $this->setup_notification_services();

            $vars = $this->presenter->present_match_notification( $fixture, $args );

            $this->assertArrayHasKey( 'action_url', $vars );
            $this->assertStringContainsString( 'Mock Tournament', $vars['tournament_link'] );
            $this->assertStringContainsString( 'Test Event', $vars['draw_link'] );
            $this->assertEquals( 'Home Team', $vars['teams']['home']->name );
            $this->assertEquals( 'Away Team', $vars['teams']['away']->name );
            $this->assertEquals( 'https://example.com', $vars['site_url'] );
            $this->assertEquals( 'Test Site', $vars['organisation'] );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_match_notification_league_is_not_null(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 2 );
            $fixture->method( 'get_league_id' )->willReturn( 10 );
            $fixture->method( 'get_season' )->willReturn( '2024' );
            $fixture->method( 'get_home_team' )->willReturn( "101" );
            $fixture->method( 'get_away_team' )->willReturn( "102" );

            $args = [
                'competition_type' => 'league',
                'competition'      => 'League Name'
            ];

            $this->setup_notification_services( competition_name: 'League Name', event_id: null, competition_id: null, event_name: null, event_type: null );

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
            $fixture->method( 'get_date' )->willReturn( '2026-10-15 18:30:00' );
            $fixture->method( 'get_start_time' )->willReturn( '18:30' );

            $args = [
                'competition_type' => 'cup',
                'competition'      => 'Cup Name'
            ];

            $this->setup_notification_services( competition_name: 'Cup Name', event_id: null, competition_id: null, event_name: null, event_type: null );

            $vars = $this->presenter->present_match_notification( $fixture, $args );

            $this->assertSame( 'match-notification-cup', $vars['template'] );
            $this->assertSame( 'Cup Name', $vars['competition_name'] );
            $this->assertSame( '15/10/2026', $vars['match_date'] );
            $this->assertSame( '18:30', $vars['match_time'] );
            $this->assertSame( 'https://example.com', $vars['site_url'] );
            $this->assertSame( 'Test Site', $vars['organisation'] );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_result_notification_includes_action_url(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 1 );

            $vars = $this->presenter->present_result_notification( $fixture, [] );

            $this->assertArrayHasKey( 'action_url', $vars );
            $this->assertStringContainsString( 'https://example.com', $vars['action_url'] );
            $this->assertStringContainsString( 'result/', $vars['action_url'] );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_date_change_notification(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 5 );
            $fixture->method( 'get_league_id' )->willReturn( 10 );
            $fixture->method( 'get_season' )->willReturn( '2026' );
            $fixture->method( 'get_home_team' )->willReturn( '101' );
            $fixture->method( 'get_away_team' )->willReturn( '102' );
            $fixture->method( 'get_date' )->willReturn( '2026-06-25 19:00:00' );
            $fixture->method( 'get_date_original' )->willReturn( '2026-06-18 19:00:00' );
            $fixture->method( 'get_start_time' )->willReturn( '19:00' );

            $this->setup_notification_services( competition_name: 'Division 1' );

            $vars = $this->presenter->present_date_change_notification( $fixture, [
                'competition_type' => 'league',
            ] );

            $this->assertSame( 'Test League', $vars['competition_name'] );
            $this->assertSame( 'https://example.com', $vars['site_url'] );
            $this->assertSame( 'Test Site', $vars['organisation'] );
            $this->assertSame( '25 June 2026 19:00', $vars['match_date'] );
            $this->assertSame( '18 June 2026 19:00', $vars['original_date'] );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_date_change_notification_without_start_time_uses_site_date_format(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 5 );
            $fixture->method( 'get_league_id' )->willReturn( 10 );
            $fixture->method( 'get_season' )->willReturn( '2026' );
            $fixture->method( 'get_home_team' )->willReturn( '101' );
            $fixture->method( 'get_away_team' )->willReturn( '102' );
            $fixture->method( 'get_date' )->willReturn( '2026-06-25 00:00:00' );
            $fixture->method( 'get_date_original' )->willReturn( '2026-06-18 00:00:00' );
            $fixture->method( 'get_start_time' )->willReturn( null );

            $this->setup_notification_services( competition_name: 'Division 1' );

            $vars = $this->presenter->present_date_change_notification( $fixture, [
                'competition_type' => 'league',
            ] );

            $this->assertSame( '25/06/2026', $vars['match_date'] );
            $this->assertSame( '18/06/2026', $vars['original_date'] );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_team_withdrawn_notification_includes_site_info(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 6 );
            $fixture->method( 'get_league_id' )->willReturn( 10 );
            $fixture->method( 'get_season' )->willReturn( '2026' );
            $fixture->method( 'get_home_team' )->willReturn( '101' );
            $fixture->method( 'get_away_team' )->willReturn( '102' );

            $this->setup_notification_services();

            $vars = $this->presenter->present_team_withdrawn_notification( $fixture, [] );

            $this->assertSame( 'https://example.com', $vars['site_url'] );
            $this->assertSame( 'Test Site', $vars['organisation'] );
        }

        #[AllowMockObjectsWithoutExpectations]
        public function test_present_result_outstanding_notification_includes_action_url(): void {
            $fixture = $this->createStub( Fixture::class );
            $fixture->method( 'get_id' )->willReturn( 1 );

            $vars = $this->presenter->present_result_outstanding_notification( $fixture, [] );

            $this->assertArrayHasKey( 'action_url', $vars );
            $this->assertStringContainsString( 'https://example.com', $vars['action_url'] );
            $this->assertStringContainsString( 'result/', $vars['action_url'] );
        }

        protected function setUp(): void {
            parent::setUp();
            $this->backup_globals();
            $this->tournament_repository = $this->createMock( Tournament_Repository_Interface::class );
            $this->fixture_link_service = $this->createStub( Fixture_Link_Service::class );
            $this->team_service = $this->createStub( Team_Service::class );
            $this->competition_service = $this->createStub( Competition_Service::class );
            $this->permission_service = $this->createStub( Fixture_Permission_Service::class );
            $this->league_repository = $this->createStub( League_Repository_Interface::class );
            $this->fixture_repository = $this->createStub( Fixture_Repository_Interface::class );

            $this->competition_service->method( 'get_league_repository' )->willReturn( $this->league_repository );

            $team_repository = $this->createStub( Team_Repository_Interface::class );
            $club_repository = $this->createStub( Club_Repository_Interface::class );

            $this->presenter = new Notification_Presenter(
                $team_repository,
                $club_repository,
                $this->tournament_repository,
                $this->fixture_repository,
                $this->fixture_link_service,
                $this->team_service,
                $this->competition_service,
                $this->permission_service,
                'https://example.com',
                'Test Site'
            );
        }

        protected function tearDown(): void {
            $this->restore_globals();
            parent::tearDown();
        }
    }
}
