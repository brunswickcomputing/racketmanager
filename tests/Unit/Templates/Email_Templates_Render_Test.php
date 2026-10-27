<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Formatters\Email_Date_Formatter;
use Racketmanager\Presentation\Presenters\Notification_Presenter;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Creates_View_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Email_Templates_Render_Test extends TestCase {
    use Creates_View_Renderer;
    use Manages_Wordpress_Globals;

    private Shortcode_Template_Renderer $template_renderer;
    private Notification_Presenter $presenter;
    private Team_Repository_Interface $team_repository;
    private League_Repository_Interface $league_repository;
    private Competition_Service $competition_service;
    private Team_Service $team_service;
    private Option_Gateway_Interface $option_gateway;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $view_renderer           = $this->create_view_renderer();
        $this->template_renderer = new Shortcode_Template_Renderer( $view_renderer );

        $this->option_gateway = new class implements Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return match ( $option ) {
                    'date_format' => 'd/m/Y',
                    'time_format' => 'H:i',
                    default       => $default,
                };
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
                return true;
            }
            public function delete_option( string $option ): bool {
                return true;
            }
        };

        $this->team_repository = $this->createStub( Team_Repository_Interface::class );
        $this->team_repository->method( 'find_by_id' )->willReturnCallback(
            function ( int $id ): ?Team {
                return match ( $id ) {
                    940 => new Team( new Team_Hydration_DTO( id: 940, title: 'Leigh A' ) ),
                    941 => new Team( new Team_Hydration_DTO( id: 941, title: 'Westcliff B' ) ),
                    942 => new Team( new Team_Hydration_DTO( id: 942, title: 'Thorpe Bay C' ) ),
                    default => null,
                };
            }
        );

        $club_repository          = $this->createStub( Club_Repository_Interface::class );
        $tournament_repository    = $this->createStub( Tournament_Repository_Interface::class );
        $fixture_repository       = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_link_service     = $this->createStub( Fixture_Link_Service::class );
        $this->team_service       = $this->createStub( Team_Service::class );
        $this->competition_service = $this->createStub( Competition_Service::class );
        $this->league_repository  = $this->createStub( League_Repository_Interface::class );
        $permission_service       = $this->createStub( Fixture_Permission_Service::class );

        $this->competition_service->method( 'get_league_repository' )->willReturn( $this->league_repository );

        $this->presenter = new Notification_Presenter(
            $this->team_repository,
            $club_repository,
            $tournament_repository,
            $fixture_repository,
            $fixture_link_service,
            $this->team_service,
            $this->competition_service,
            $permission_service,
            'https://example.com',
            'Test Tennis League',
            $this->createStub( \Racketmanager\Services\Competition\League_Query_Service::class ),
            option_gateway: $this->option_gateway
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

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
        $league->title = $league_name;
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

        $competition = $this->createStub( Competition::class );
        $competition->method( 'get_name' )->willReturn( $competition_name );
        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );

        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Leigh A' );
        $home_team_details = new Team_Details_DTO( $home_team, null, null );

        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Westcliff B' );
        $away_team_details = new Team_Details_DTO( $away_team, null, null );

        $this->team_service->method( 'get_team_details' )->willReturnMap( [
            [ 940, $home_team_details ],
            [ 941, $away_team_details ],
        ] );
    }

    private function render_and_capture_warnings( callable $render_callback, array &$warnings ): string {
        $warnings = [];
        set_error_handler( static function ( int $errno, string $errstr, string $errfile, int $errline ) use ( &$warnings ): bool {
            if ( ! ( error_reporting() & $errno ) ) {
                return false;
            }
            $warnings[] = [
                'errno'   => $errno,
                'errstr'  => $errstr,
                'errfile' => $errfile,
                'errline' => $errline,
            ];
            return true;
        } );

        try {
            return (string) $render_callback();
        } finally {
            restore_error_handler();
        }
    }

    public function test_email_header_renders_site_url_and_organisation_with_zero_warnings(): void {
        $site_url     = 'https://racketmanager.test';
        $organisation = 'Leigh & Westcliff Tennis League';

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            function () use ( $site_url, $organisation ) {
                ob_start();
                require RACKETMANAGER_PATH . 'templates/email/email-header.php';
                require RACKETMANAGER_PATH . 'templates/email/email-footer.php';
                return ob_get_clean();
            },
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering email-header and footer must produce zero PHP warnings' );
        $this->assertStringContainsString( 'https://racketmanager.test', $output );
        $this->assertStringContainsString( 'Leigh & Westcliff Tennis League', $output );
    }

    public function test_send_fixtures_producer_to_template_rendering(): void {
        $f_home = Fixture_Factory::from_object( (object) [
            'id'         => 101,
            'league_id'  => 10,
            'home_team'  => '940',
            'away_team'  => '941',
            'date'       => '2026-10-02 00:00:00',
            'start_time' => '19:30',
            'season'     => '2026',
            'match_day'  => 1,
            'teams'      => null,
        ] );

        $f_away = Fixture_Factory::from_object( (object) [
            'id'         => 102,
            'league_id'  => 10,
            'home_team'  => '942',
            'away_team'  => '940',
            'date'       => '2026-10-09 00:00:00',
            'start_time' => '18:45',
            'season'     => '2026',
            'match_day'  => 2,
            'teams'      => [],
        ] );

        $f_midnight = Fixture_Factory::from_object( (object) [
            'id'         => 103,
            'league_id'  => 10,
            'home_team'  => '940',
            'away_team'  => '941',
            'date'       => '2026-10-16 00:00:00',
            'start_time' => '00:00',
            'season'     => '2026',
            'match_day'  => 3,
            'teams'      => null,
        ] );

        $f_bye = Fixture_Factory::from_object( (object) [
            'id'         => 104,
            'league_id'  => 10,
            'home_team'  => '940',
            'away_team'  => '-1',
            'date'       => '2026-10-23 00:00:00',
            'start_time' => null,
            'season'     => '2026',
            'match_day'  => 4,
            'teams'      => null,
        ] );

        $f_null_date = Fixture_Factory::from_object( (object) [
            'id'         => 105,
            'league_id'  => 10,
            'home_team'  => '940',
            'away_team'  => '941',
            'date'       => '0000-00-00 00:00:00',
            'start_time' => null,
            'season'     => '2026',
            'match_day'  => 5,
            'teams'      => null,
        ] );

        $current_team_id = 940;

        $row_home      = $this->presenter->present_send_fixtures_match( $f_home, $current_team_id );
        $row_away      = $this->presenter->present_send_fixtures_match( $f_away, $current_team_id );
        $row_midnight  = $this->presenter->present_send_fixtures_match( $f_midnight, $current_team_id );
        $row_bye       = $this->presenter->present_send_fixtures_match( $f_bye, $current_team_id );
        $row_null_date = $this->presenter->present_send_fixtures_match( $f_null_date, $current_team_id );

        $team = (object) [
            'id'           => 940,
            'title'        => 'Leigh A',
            'season'       => '2026',
            'captain'      => 55,
            'captain_name' => 'Jane Captain',
        ];

        $vars = [
            'competition'   => 'Division 1',
            'captain'       => 'Jane Captain',
            'season'        => '2026',
            'matches'       => [ $row_home, $row_away, $row_midnight, $row_bye, $row_null_date ],
            'team'          => $team,
            'action_url'    => 'https://example.com/league/division-1/2026/team/leigh-a',
            'organisation'  => 'Test Tennis League',
            'site_url'      => 'https://example.com',
            'contact_email' => 'secretary@example.com',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'send-fixtures', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering send-fixtures email must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Please find attached your fixture list for the Division 1 2026 season.', $output );
        $this->assertStringContainsString( '<td>02 Oct 26</td>', $output );
        $this->assertStringContainsString( '<td>Fri</td>', $output );
        $this->assertStringContainsString( '<td>19:30</td>', $output );
        $this->assertMatchesRegularExpression( '/align-right bold">\s*Leigh A\s*<\/td>/', $output, 'Home team Leigh A should be bold on home match' );
        $this->assertMatchesRegularExpression( '/align-left bold">\s*Leigh A\s*<\/td>/', $output, 'Away team Leigh A should be bold on away match' );
        $this->assertStringContainsString( '<td>18:45</td>', $output );
        $this->assertStringContainsString( 'Thorpe Bay C', $output );
        $this->assertStringContainsString( 'Westcliff B', $output );
        $this->assertStringNotContainsString( '<td>00:00</td>', $output, '00:00 start time should be hidden' );
        $this->assertMatchesRegularExpression( '/align-left">\s*Bye\s*<\/td>/', $output, 'Bye opponent should be placed in away cell' );
        $this->assertMatchesRegularExpression( '/<td>5<\/td>\s*<td><\/td>\s*<td><\/td>/', $output, 'Zero date row should have empty date and day cells' );
        $this->assertStringContainsString( 'Jane Captain', $output );
        $this->assertStringContainsString( 'Test Tennis League', $output );
    }

    public function test_match_notification_cup_producer_to_template_rendering(): void {
        $this->setup_notification_services( competition_name: 'Autumn Cup', event_id: null, competition_id: null, event_name: null, event_type: null );

        $fixture = Fixture_Factory::from_object( (object) [
            'id'         => 301,
            'league_id'  => 10,
            'home_team'  => '940',
            'away_team'  => '941',
            'date'       => '2026-10-15 18:30:00',
            'start_time' => '18:30',
            'season'     => '2026',
        ] );

        $vars = $this->presenter->present_match_notification( $fixture, [
            'competition_type' => 'cup',
            'competition'      => 'Autumn Cup',
        ] );

        $this->assertSame( 'match-notification-cup', $vars['template'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match-notification-cup', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match-notification-cup email must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Match to be played on 15/10/2026 at 18:30.', $output );
        $this->assertStringContainsString( 'Autumn Cup', $output );
        $this->assertStringContainsString( 'https://example.com', $output );
        $this->assertStringContainsString( 'Test Tennis League', $output );
    }

    public function test_match_date_change_notification_producer_to_template_rendering_with_delay(): void {
        $this->setup_notification_services( competition_name: 'Division 1' );

        $fixture = Fixture_Factory::from_object( (object) [
            'id'            => 501,
            'league_id'     => 10,
            'home_team'     => '940',
            'away_team'     => '941',
            'date'          => '2026-10-02 19:30:00',
            'date_original' => '2026-09-25 19:30:00',
            'start_time'    => '19:30',
            'season'        => '2026',
        ] );

        $vars = $this->presenter->present_date_change_notification( $fixture, [
            'competition_type' => 'league',
            'delay'            => true,
            'from_email'       => 'secretary@example.com',
        ] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match-date-change-notification', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match-date-change-notification must produce zero PHP warnings' );
        $this->assertStringContainsString( 'The date of this match has now changed.', $output );
        $this->assertStringContainsString( 'The new date is 2 October 2026 19:30.', $output );
        $this->assertStringContainsString( 'This is now after the round end date (25 September 2026 19:30).', $output );
        $this->assertStringContainsString( 'https://example.com', $output );
        $this->assertStringContainsString( 'Test Tennis League', $output );
    }

    public function test_match_date_change_notification_producer_to_template_rendering_without_start_time(): void {
        $this->setup_notification_services( competition_name: 'Division 1' );

        $fixture = Fixture_Factory::from_object( (object) [
            'id'            => 502,
            'league_id'     => 10,
            'home_team'     => '940',
            'away_team'     => '941',
            'date'          => '2026-10-02 00:00:00',
            'date_original' => '2026-09-25 00:00:00',
            'start_time'    => null,
            'season'        => '2026',
        ] );

        $vars = $this->presenter->present_date_change_notification( $fixture, [
            'competition_type' => 'league',
            'delay'            => false,
            'from_email'       => 'secretary@example.com',
        ] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match-date-change-notification', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match-date-change-notification must produce zero PHP warnings' );
        $this->assertStringContainsString( 'The date of this match has now changed.', $output );
        $this->assertStringContainsString( 'The new date is 02/10/2026.', $output );
        $this->assertStringNotContainsString( 'after the round end date', $output );
    }

    public function test_competition_entry_open_producer_to_template_rendering(): void {
        $formatter   = new Email_Date_Formatter( $this->option_gateway );
        $season_dtls = (object) [
            'date_start'   => '2026-05-01',
            'date_end'     => '2026-08-31',
            'date_closing' => '2026-04-15',
            'venue_name'   => 'Westcliff Hardcourts',
        ];

        $vars = [
            'email_subject'        => 'Competition Entry Open',
            'competition'          => 'Summer League',
            'season_dtls'          => $season_dtls,
            'date_closing_display' => $formatter->date( $season_dtls->date_closing ),
            'date_start_display'   => $formatter->date( $season_dtls->date_start ),
            'date_end_display'     => $formatter->date( $season_dtls->date_end ),
            'addressee'            => 'John McEnroe',
            'from_email'           => 'secretary@example.com',
            'action_url'           => 'https://example.com/entry-form/summer-league/2026/',
            'site_url'             => 'https://example.com',
            'organisation'         => 'Test Tennis League',
            'days_remaining'       => null,
            'is_championship'      => true,
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'competition-entry-open', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering competition-entry-open email must produce zero PHP warnings' );
        $this->assertStringContainsString( 'The closing date for entries is 15/04/2026.', $output );
        $this->assertStringContainsString( 'The competition will run from 01/05/2026 until 31/08/2026.', $output );
        $this->assertStringContainsString( 'Finals day will held at Westcliff Hardcourts.', $output );
        $this->assertStringContainsString( 'https://example.com/entry-form/summer-league/2026/', $output );
        $this->assertStringContainsString( 'Test Tennis League', $output );
    }

    public function test_competition_entry_open_renders_with_null_dates_and_days_remaining(): void {
        $formatter   = new Email_Date_Formatter( $this->option_gateway );
        $season_dtls = (object) [
            'date_start'   => null,
            'date_end'     => null,
            'date_closing' => null,
            'venue_name'   => null,
        ];

        $vars = [
            'email_subject'        => 'Entries Closing Soon',
            'competition'          => 'Winter Cup',
            'season_dtls'          => $season_dtls,
            'date_closing_display' => $formatter->date( $season_dtls->date_closing ),
            'date_start_display'   => $formatter->date( $season_dtls->date_start ),
            'date_end_display'     => $formatter->date( $season_dtls->date_end ),
            'addressee'            => 'Bjorn Borg',
            'from_email'           => 'cup@example.com',
            'action_url'           => 'https://example.com/entry-form/winter-cup/2026/',
            'site_url'             => 'https://example.com',
            'organisation'         => 'Test Tennis League',
            'days_remaining'       => 5,
            'is_championship'      => false,
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'competition-entry-open', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering competition-entry-open with null dates must produce zero PHP warnings' );
        $this->assertStringContainsString( 'There are now less than 5 days left before the closing date of the Winter Cup.', $output );
        $this->assertStringNotContainsString( 'The competition will run from', $output );
        $this->assertStringContainsString( 'Test Tennis League', $output );
    }
}
