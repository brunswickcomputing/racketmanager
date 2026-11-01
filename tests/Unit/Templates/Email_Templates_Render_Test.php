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
use Racketmanager\Application\Ports\Option_Gateway_Interface;
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

    public function test_tournament_withdrawal_template_renders_with_zero_warnings(): void {
        $player = new class {
            public function get_fullname(): string {
                return 'Roger Federer';
            }
        };

        $vars = [
            'tournament_name' => 'Championship 2026',
            'player'          => $player,
            'tournament_link' => '<a href="https://example.com/tournament/championship-2026">Championship 2026</a>',
            'tournament'      => (object) [ 'name' => 'Championship 2026', 'is_open' => true ],
            'tournament_dates'=> (object) [ 'date_closing_display' => '07/06/2026' ],
            'action_url'      => 'https://example.com/withdraw',
            'contact_email'   => 'admin@example.com',
            'site_url'        => 'https://example.com',
            'organisation'    => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'tournament-withdrawal', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament-withdrawal must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Roger Federer', $output );
        $this->assertStringContainsString( 'Championship 2026', $output );
        $this->assertStringContainsString( '07/06/2026', $output );
        $this->assertStringContainsString( 'admin@example.com', $output );
    }

    public function test_cup_entry_template_renders_with_zero_warnings(): void {
        $vars = [
            'competition_name' => 'Men\'s Senior Cup',
            'season'           => '2026',
            'club'             => 'Leigh LTC',
            'cup_entries'      => [
                [
                    'event'        => 'Open Cup',
                    'teamName'     => 'Leigh 1',
                    'matchday'     => 'Saturday',
                    'matchtime'    => '14:00',
                    'captain'      => 'John Doe',
                    'contactno'    => '0123456789',
                    'contactemail' => 'john@example.com',
                ],
            ],
            'comments'         => 'Looking forward to it',
            'contact_email'    => 'cup@example.com',
            'site_url'         => 'https://example.com',
            'organisation'     => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'cup-entry', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering cup-entry must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Leigh LTC', $output );
        $this->assertStringContainsString( 'Open Cup', $output );
        $this->assertStringContainsString( 'Leigh 1', $output );
        $this->assertStringContainsString( 'John Doe', $output );
        $this->assertStringContainsString( 'Looking forward to it', $output );
    }

    public function test_league_entry_template_renders_with_zero_warnings(): void {
        $vars = [
            'competition_name' => 'Main League',
            'season'           => '2026',
            'club'             => 'Westcliff LTC',
            'event_entries'    => [
                'num_courts_available' => 4,
                'events'               => [
                    [
                        'event' => 'Division 1 Men',
                        'teams' => [
                            [
                                'teamName'     => 'Westcliff 1',
                                'matchday'     => 'Saturday',
                                'matchtime'    => '14:00',
                                'captain'      => 'Bob Smith',
                                'contactno'    => '0123456789',
                                'contactemail' => 'bob@example.com',
                            ],
                        ],
                    ],
                ],
            ],
            'comments'         => 'Ready for new season',
            'contact_email'    => 'league@example.com',
            'site_url'         => 'https://example.com',
            'organisation'     => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'league-entry', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering league-entry must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Westcliff LTC', $output );
        $this->assertStringContainsString( 'Division 1 Men', $output );
        $this->assertStringContainsString( 'Westcliff 1', $output );
        $this->assertStringContainsString( 'Bob Smith', $output );
        $this->assertStringContainsString( 'Ready for new season', $output );
    }

    public function test_tournament_entry_template_renders_with_zero_warnings(): void {
        $player = new class {
            public string $firstname = 'Novak';
            public string $surname   = 'Djokovic';
            public string $contactno = '0123456789';
            public string $btm       = 'BTM999';

            public function get_fullname(): string {
                return 'Novak Djokovic';
            }
        };

        $vars = [
            'tournament_name'    => 'Annual Open',
            'player'             => $player,
            'tournament_link'    => '<a href="https://example.com/tournament/annual-open">Annual Open</a>',
            'tournament_entries' => [
                [
                    'event_name' => 'Men\'s Singles',
                    'partner'    => null,
                ],
            ],
            'comments'           => 'Special seeding request',
            'contact_email'      => 'tournament@example.com',
            'action_url'         => 'https://example.com/entry/confirm',
            'site_url'           => 'https://example.com',
            'organisation'       => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'tournament-entry', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament-entry must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Novak', $output );
        $this->assertStringContainsString( 'Djokovic', $output );
        $this->assertStringContainsString( '0123456789', $output );
        $this->assertStringContainsString( 'BTM999', $output );
        $this->assertStringContainsString( 'Men\'s Singles', $output );
        $this->assertStringContainsString( 'Special seeding request', $output );
        $this->assertStringContainsString( 'https://example.com/entry/confirm', $output );
    }

    public function test_tournament_entry_open_template_renders_with_zero_warnings(): void {
        $vars = [
            'addressee'        => 'Club Secretary',
            'type'             => 'club',
            'tournament'       => (object) [ 'name' => 'Summer Championship' ],
            'tournament_dates' => null,
            'account_link'     => 'https://example.com/account',
            'days_remaining'   => 7,
            'from_email'       => 'entries@example.com',
            'action_url'       => 'https://example.com/tournament/enter',
            'site_url'         => 'https://example.com',
            'organisation'     => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'tournament-entry-open', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament-entry-open must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Club Secretary', $output );
        $this->assertStringContainsString( 'Please pass this information on to your members.', $output );
        $this->assertStringContainsString( 'https://example.com/tournament/enter', $output );
    }

    public function test_tournament_finalists_template_renders_with_zero_warnings(): void {
        $vars = [
            'addressee'    => 'Finalist Player',
            'organisation' => 'Test Tennis League',
            'tournament'   => (object) [
                'name'        => 'Summer Championship',
                'information' => (object) [
                    'referee'      => 'Jane Referee',
                    'match_format' => null,
                    'photography'  => null,
                    'parking'      => null,
                    'catering'     => null,
                    'spectatora'   => null,
                ],
            ],
            'rounds'       => [ '10:00', '15:00' ],
            'from_email'   => 'finals@example.com',
            'action_url'   => 'https://example.com/finals/order-of-play',
            'site_url'     => 'https://example.com',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'tournament-finalists', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering tournament-finalists must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Finalist Player', $output );
        $this->assertStringContainsString( 'Summer Championship', $output );
        $this->assertStringContainsString( 'Jane Referee', $output );
        $this->assertStringContainsString( '10:00', $output );
        $this->assertStringContainsString( '15:00', $output );
    }

    public function test_favourite_notification_template_renders_with_zero_warnings(): void {
        $home_team = new class {
            public function get_name(): string {
                return 'Leigh LTC A';
            }
        };
        $away_team = new class {
            public function get_name(): string {
                return 'Westcliff LTC B';
            }
        };
        $fixture = new class {
            public function get_home_points(): string {
                return '4';
            }
            public function get_away_points(): string {
                return '1';
            }
        };

        $vars = [
            'user'          => (object) [ 'first_name' => 'Fan User' ],
            'match_url'     => 'https://example.com/match/501',
            'fixture'       => $fixture,
            'home_team'     => $home_team,
            'away_team'     => $away_team,
            'from_email'    => 'notify@example.com',
            'favourite_url' => 'https://example.com/favourites',
            'site_url'      => 'https://example.com',
            'organisation'  => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'favourite-notification', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering favourite-notification must produce zero PHP warnings' );
        $this->assertStringContainsString( '<title>Match Result - Favourite Notification</title>', $output );
        $this->assertStringContainsString( 'Fan User', $output );
        $this->assertStringContainsString( 'Leigh LTC A', $output );
        $this->assertStringContainsString( 'Westcliff LTC B', $output );
        $this->assertStringContainsString( '4-1', $output );
        $this->assertStringContainsString( 'https://example.com/favourites', $output );
    }

    public function test_wtn_report_template_renders_with_zero_warnings(): void {
        $club = new class {
            public function get_shortcode(): string {
                return 'LTC';
            }
        };

        $vars = [
            'club'         => $club,
            'player_count' => 125,
            'error_count'  => 0,
            'site_url'     => 'https://example.com',
            'organisation' => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'wtn-report', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering wtn-report must produce zero PHP warnings' );
        $this->assertStringContainsString( 'A WTN update process has completed.', $output );
        $this->assertStringContainsString( 'The process was run for LTC.', $output );
        $this->assertStringContainsString( '125 players were processed.', $output );
    }

    public function test_match_notification_template_renders_with_zero_warnings(): void {
        $vars = [
            'tournament'      => (object) [ 'name' => 'Summer Championship' ],
            'match'           => (object) [
                'match_date' => '2026-06-15',
                'league'     => (object) [ 'title' => 'Championship League' ],
            ],
            'round'           => 'Quarter Final',
            'draw_link'       => 'https://example.com/draw',
            'teams'           => [
                'home' => (object) [
                    'title'  => 'Home Team',
                    'club'   => 'Leigh LTC',
                    'player' => [ (object) [ 'display_name' => 'Alice Player', 'contactno' => '0123456789' ] ],
                ],
                'away' => (object) [
                    'title'  => 'Away Team',
                    'club'   => 'Westcliff LTC',
                    'player' => [ (object) [ 'display_name' => 'Bob Player', 'contactno' => '0987654321' ] ],
                ],
            ],
            'rules_link'      => 'https://example.com/rules',
            'tournament_link' => 'https://example.com/tournament',
            'action_url'      => 'https://example.com/match/100',
            'email_from'      => 'tournament@example.com',
            'site_url'        => 'https://example.com',
            'organisation'    => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match-notification', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match-notification must produce zero PHP warnings' );
        $this->assertStringContainsString( '<title>Next match confirmation - Summer Championship</title>', $output );
        $this->assertStringContainsString( 'Championship League Quarter Final Match Confirmation', $output );
        $this->assertStringContainsString( 'Alice Player', $output );
        $this->assertStringContainsString( 'Bob Player', $output );
        $this->assertStringContainsString( 'https://example.com/match/100', $output );
    }

    public function test_match_result_pending_tournament_template_renders_with_zero_warnings(): void {
        $match = new class {
            public object $league;
            public string $match_date = '2026-06-15';

            public function __construct() {
                $this->league = (object) [ 'title' => 'Division 1' ];
            }

            public function get_title( string $bye, string $unknown ): string {
                return 'Leigh LTC vs Westcliff LTC';
            }
        };

        $vars = [
            'organisation' => 'Test Tennis League',
            'time_period'  => 48,
            'match'        => $match,
            'action_url'   => 'https://example.com/match/enter-result',
            'from_email'   => 'results@example.com',
            'site_url'     => 'https://example.com',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match-result-pending-tournament', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match-result-pending-tournament must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Leigh LTC vs Westcliff LTC', $output );
        $this->assertStringContainsString( 'more than 2 days after the match was due to be played', $output );
        $this->assertStringContainsString( 'https://example.com/match/enter-result', $output );
    }

    public function test_result_check_match_template_renders_with_zero_warnings(): void {
        $vars = [
            'captain'       => 'Captain Bob',
            'reason'        => 'Scorecard submitted past deadline.',
            'penalty'       => '1',
            'contact_email' => 'results@example.com',
            'site_url'      => 'https://example.com',
            'organisation'  => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'result-check-match', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering result-check-match must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Captain Bob', $output );
        $this->assertStringContainsString( 'Scorecard submitted past deadline.', $output );
        $this->assertStringContainsString( 'You have been deducted 1 point.', $output );
    }

    public function test_result_check_player_template_renders_with_zero_warnings(): void {
        $vars = [
            'captain'       => 'Captain Alice',
            'player'        => 'Player Charlie',
            'reason'        => 'Player was not registered before match',
            'opponent'      => 'Opponent Dan',
            'penalty'       => '1',
            'contact_email' => 'results@example.com',
            'site_url'      => 'https://example.com',
            'organisation'  => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'result-check-player', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering result-check-player must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Captain Alice', $output );
        $this->assertStringContainsString( 'Player Charlie -  Player was not registered before match.', $output );
        $this->assertStringContainsString( 'As such, the rubber will be awarded to Opponent Dan.', $output );
        $this->assertStringContainsString( 'You will also be deducted 1 point', $output );
    }

    public function test_contact_teams_template_renders_with_zero_warnings(): void {
        $vars = [
            'intro'        => 'Welcome to the new season.',
            'body'         => [ 'Matches commence next week.', 'Please ensure balls are provided.' ],
            'closing_text' => 'Best regards, Committee',
            'object_type'  => 'league',
            'title_text'   => 'Important League Update',
            'site_url'     => 'https://example.com',
            'organisation' => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'contact-teams', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering contact-teams must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Important League Update', $output );
        $this->assertStringContainsString( 'Welcome to the new season.', $output );
        $this->assertStringContainsString( 'Matches commence next week.', $output );
        $this->assertStringContainsString( 'Best regards, Committee', $output );
    }

    public function test_club_player_notification_template_renders_with_zero_warnings(): void {
        $vars = [
            'requestor'     => 'Secretary Dave',
            'action'        => 'approved',
            'club'          => 'Leigh LTC',
            'player'        => 'New Player',
            'btm'           => 'BTM12345',
            'action_url'    => 'https://example.com/players/manage',
            'contact_email' => 'members@example.com',
            'site_url'      => 'https://example.com',
            'organisation'  => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'club-player-notification', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering club-player-notification must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Secretary Dave', $output );
        $this->assertStringContainsString( 'New Player', $output );
        $this->assertStringContainsString( 'BTM12345', $output );
        $this->assertStringContainsString( 'https://example.com/players/manage', $output );
    }

    public function test_email_templates_render_with_explicit_vm_object_with_zero_warnings(): void {
        $vm = (object) [
            'captain'       => 'Captain VM',
            'reason'        => 'VM check passed.',
            'penalty'       => '0',
            'contact_email' => 'admin@example.com',
            'site_url'      => 'https://example.com',
            'organisation'  => 'Test Tennis League',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'result-check-match', [ 'vm' => $vm ], 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering result-check-match with object $vm must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Captain VM', $output );
        $this->assertStringContainsString( 'VM check passed.', $output );
    }

    public function test_title_component_preserves_parent_local_titles_when_vars_contain_outer_title_text(): void {
        $vars = [
            'competition_name' => 'Open Cup',
            'season'           => '2026',
            'club'             => 'Westcliff LTC',
            'cup_entries'      => [
                [
                    'event'    => 'Men\'s Open Cup',
                    'teamName' => 'Westcliff 1',
                    'matchday' => 'Sunday',
                    'matchtime'=> '10:00',
                    'captain'  => 'Captain Dan',
                ],
            ],
            'comments'         => null,
            'contact_email'    => 'cup@example.com',
            'site_url'         => 'https://example.com',
            'organisation'     => 'Test Tennis League',
            'title_text'       => 'GLOBAL OUTER TITLE THAT MUST NOT OVERRIDE PER-SECTION TITLES',
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'cup-entry', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering cup-entry with outer title_text must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Entry confirmation', $output );
        $this->assertStringContainsString( 'Entry Details', $output );
        $this->assertStringContainsString( 'Events', $output );
        $this->assertStringContainsString( 'Men\'s Open Cup', $output );
        $this->assertStringNotContainsString( 'GLOBAL OUTER TITLE THAT MUST NOT OVERRIDE PER-SECTION TITLES', $output );
    }

    public function test_favourite_notification_preserves_fixed_html_title_with_producer_supplied_email_subject(): void {
        $home_team = new class {
            public function get_name(): string {
                return 'Leigh LTC A';
            }
        };
        $away_team = new class {
            public function get_name(): string {
                return 'Westcliff LTC B';
            }
        };
        $fixture = new class {
            public function get_home_points(): string {
                return '4';
            }
            public function get_away_points(): string {
                return '1';
            }
        };

        $producer_subject = 'Test Tennis League - Division 1 Result Notification';

        $vars = [
            'user'          => (object) [ 'first_name' => 'Fan User' ],
            'match_url'     => 'https://example.com/match/501',
            'fixture'       => $fixture,
            'home_team'     => $home_team,
            'away_team'     => $away_team,
            'from_email'    => 'notify@example.com',
            'favourite_url' => 'https://example.com/favourites',
            'site_url'      => 'https://example.com',
            'organisation'  => 'Test Tennis League',
            'email_subject' => $producer_subject,
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'favourite-notification', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering favourite-notification with producer subject must produce zero PHP warnings' );
        $this->assertStringNotContainsString( '<title>' . $producer_subject . '</title>', $output );
        $this->assertStringContainsString( '<title>Match Result - Favourite Notification</title>', $output );
    }

    public function test_match_notification_respects_producer_supplied_email_subject(): void {
        $producer_subject = 'Summer Championship - Round of 16 Match Confirmation';

        $vars = [
            'tournament'      => (object) [ 'name' => 'Summer Championship' ],
            'match'           => (object) [
                'match_date' => '2026-06-15',
                'league'     => (object) [ 'title' => 'Championship League' ],
            ],
            'round'           => 'Quarter Final',
            'draw_link'       => 'https://example.com/draw',
            'teams'           => [
                'home' => (object) [
                    'title'  => 'Home Team',
                    'club'   => 'Leigh LTC',
                    'player' => [ (object) [ 'display_name' => 'Alice Player', 'contactno' => '0123456789' ] ],
                ],
                'away' => (object) [
                    'title'  => 'Away Team',
                    'club'   => 'Westcliff LTC',
                    'player' => [ (object) [ 'display_name' => 'Bob Player', 'contactno' => '0987654321' ] ],
                ],
            ],
            'rules_link'      => 'https://example.com/rules',
            'tournament_link' => 'https://example.com/tournament',
            'action_url'      => 'https://example.com/match/100',
            'email_from'      => 'tournament@example.com',
            'site_url'        => 'https://example.com',
            'organisation'    => 'Test Tennis League',
            'email_subject'   => $producer_subject,
        ];

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->template_renderer->render( 'match-notification', $vars, 'email' ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering match-notification with producer subject must produce zero PHP warnings' );
        $this->assertStringContainsString( '<title>' . $producer_subject . '</title>', $output );
        $this->assertStringNotContainsString( '<title>Next match confirmation - Summer Championship</title>', $output );
    }
}
