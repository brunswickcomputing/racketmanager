<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Public;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Tournament;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Domain\Team;
use Racketmanager\Domain\Club;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Email_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Public\Shortcodes_Email;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
class Shortcodes_Email_Integration_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $plugin;
    private $container;
    private $shortcode;
    private $presenter;
    private array $rendered = [];
    private $fixture_repo;
    private $comp_service;
    private $team_service;
    private $tournament_service;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->plugin            = $this->createMock( RacketManager::class );
        $this->plugin->site_name = 'Test Tennis League';
        $this->plugin->site_url  = 'https://example.com';
        $this->plugin->date_format = 'd/m/Y';
        $this->container         = new Simple_Container();
        $this->plugin->container = $this->container;
        $GLOBALS['racketmanager'] = $this->plugin;

        $this->fixture_repo = $this->createMock( Fixture_Repository_Interface::class );
        $league_repo        = $this->createMock( League_Repository_Interface::class );

        $this->comp_service = $this->createMock( Competition_Service::class );
        $this->comp_service->method( 'get_league_repository' )->willReturn( $league_repo );

        $this->team_service       = $this->createMock( Team_Service::class );
        $this->tournament_service = $this->createMock( Tournament_Service::class );

        $this->container->set( 'fixture_repository', $this->fixture_repo );
        $this->container->set( 'competition_repository', $this->createMock( Competition_Repository_Interface::class ) );
        $this->container->set( 'competition_service', $this->comp_service );
        $this->container->set( 'team_service', $this->team_service );
        $this->container->set( 'tournament_service', $this->tournament_service );

        $fixture = Fixture_Factory::from_object( (object) [
            'id'        => 555,
            'league_id' => 10,
            'home_team' => '1',
            'away_team' => '2',
            'date'      => '2026-06-01 19:00:00',
            'season'    => '2026',
            'host'      => 'home',
            'match_day' => 1,
        ] );
        $this->fixture_repo->method( 'find_by_id' )->willReturnCallback( function ( int $id ) use ( $fixture ) {
            return 555 === $id ? $fixture : null;
        } );

        $league = $this->createMock( League::class );
        $league->method( 'get_id' )->willReturn( 10 );
        $league->method( 'get_event_id' )->willReturn( 20 );
        $league->method( 'get_name' )->willReturn( 'Division 1' );
        $league->current_season = [ 'name' => '2026' ];
        $league_repo->method( 'find_by_id' )->willReturn( $league );

        $event = $this->createMock( Event::class );
        $event->method( 'get_id' )->willReturn( 20 );
        $event->method( 'get_competition_id' )->willReturn( 30 );
        $event->method( 'get_name' )->willReturn( 'Summer League' );
        $event->method( 'get_type' )->willReturn( 'league' );
        $this->comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $competition = $this->createMock( Competition::class );
        $competition->method( 'get_id' )->willReturn( 30 );
        $competition->method( 'get_name' )->willReturn( 'Summer Competition' );
        $competition->type = Competition_Type::LEAGUE;
        $this->comp_service->method( 'get_by_id' )->willReturn( $competition );

        $home_team_dto = $this->create_team_details_dto( 'Home Team', 'HTC', 'Captain Home', 'home@example.com', '111111' );
        $away_team_dto = $this->create_team_details_dto( 'Away Team', 'ATC', 'Captain Away', 'away@example.com', '222222' );

        $this->team_service->method( 'get_team_details' )->willReturnMap( [
            [ 1, $home_team_dto ],
            [ 2, $away_team_dto ],
        ] );

        $link_service = $this->createStub( Fixture_Link_Service::class );
        $perm_service = $this->createStub( Fixture_Permission_Service::class );

        $this->presenter = new Email_Shortcode_Presenter(
            $this->tournament_service,
            $this->comp_service,
            $this->team_service,
            $link_service,
            $perm_service,
            'https://example.com',
            'Test Tennis League'
        );

        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback( function ( string $path, $vm, array $vars ): string {
            $this->rendered = [ 'path' => $path, 'vars' => $vars ];

            return 'rendered_html_' . $path;
        } );

        $template_renderer = new Shortcode_Template_Renderer( $renderer );

        $this->container->set( 'email_shortcode_adapter', fn() => new Email_Shortcode_Adapter(
            $this->fixture_repo,
            $this->presenter,
            $template_renderer
        ) );

        $this->shortcode = new Shortcodes_Email( $this->plugin );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_team_details_dto( string $name, string $shortcode, string $captain, string $email, string $contact_no ): Team_Details_DTO {
        $team = $this->createMock( Team::class );
        $team->method( 'get_name' )->willReturn( $name );
        $club = $this->createStub( Club::class );
        $club->shortcode = $shortcode;
        $dto = new Team_Details_DTO( $team, $club, null );
        $dto->captain      = $captain;
        $dto->contactemail = $email;
        $dto->contactno    = $contact_no;

        return $dto;
    }

    public function test_show_match_notification_renders_template_with_adapted_data(): void {
        $output = $this->shortcode->show_match_notification( [
            'match'            => 555,
            'competition_type' => 'league',
        ] );

        $this->assertSame( 'rendered_html_email/match-notification', $output );
        $this->assertSame( 'email/match-notification', $this->rendered['path'] );
        $this->assertSame( 'Division 1', $this->rendered['vars']['match']->league->title );
        $this->assertSame( 'Home Team', $this->rendered['vars']['match']->teams['home']->title );
        $this->assertSame( 'Away Team', $this->rendered['vars']['match']->teams['away']->title );
    }

    public function test_show_match_notification_cup_renders_cup_template(): void {
        $output = $this->shortcode->show_match_notification( [
            'match'            => 555,
            'competition_type' => 'cup',
        ] );

        $this->assertSame( 'rendered_html_email/match-notification-cup', $output );
        $this->assertSame( 'email/match-notification-cup', $this->rendered['path'] );
    }

    public function test_show_result_notification_renders_template_with_admin_url(): void {
        $output = $this->shortcode->show_result_notification( [
            'match'            => 555,
            'league'           => '10',
            'match_day'        => '3',
            'round'            => 'Quarter-Final',
            'competition_type' => 'league',
        ] );

        $this->assertSame( 'rendered_html_email/result-notification', $output );
        $this->assertSame( 'email/result-notification', $this->rendered['path'] );
        $this->assertStringContainsString( 'view=results', $this->rendered['vars']['action_url'] );
        $this->assertStringContainsString( '&subpage=show-league&league_id=10', $this->rendered['vars']['action_url'] );
        $this->assertStringContainsString( '&match_day=3', $this->rendered['vars']['action_url'] );
        $this->assertStringContainsString( '&final=Quarter-Final&tab=matches', $this->rendered['vars']['action_url'] );
    }

    public function test_show_captain_result_notification_and_alias(): void {
        $output1 = $this->shortcode->show_captain_result_notification( [ 'match' => 555 ] );
        $this->assertSame( 'rendered_html_email/result-notification', $output1 );

        $output2 = $this->shortcode->show_result_notification_captain( [ 'match' => 555 ] );
        $this->assertSame( 'rendered_html_email/result-notification', $output2 );
    }

    public function test_show_result_outstanding_notification_league(): void {
        $output = $this->shortcode->show_result_outstanding_notification( [ 'match' => 555 ] );
        $this->assertSame( 'rendered_html_email/match-result-pending', $output );
        $this->assertStringContainsString( '/match/division-1/2026/day1/home-team-vs-away-team', $this->rendered['vars']['action_url'] );
    }

    public function test_show_match_date_change_notification(): void {
        $GLOBALS['wp_stubs_options']['date_format'] = 'd/m/Y';

        $output = $this->shortcode->show_match_date_change_notification( [
            'match'         => 555,
            'original_date' => '2026-06-01',
            'new_date'      => '2026-06-08',
            'delay'         => true,
        ] );

        $this->assertSame( 'rendered_html_email/match-date-change-notification', $output );
        $this->assertTrue( $this->rendered['vars']['delay'] );
        // Raw shortcode dates are formatted by the presenter (fixture has no start time => site date format).
        $this->assertSame( '08/06/2026', $this->rendered['vars']['new_date'] );
        $this->assertSame( '08/06/2026', $this->rendered['vars']['match_date'] );
        $this->assertSame( '01/06/2026', $this->rendered['vars']['original_date'] );
    }

    public function test_match_date_change_shortcode_with_raw_mysql_dates_renders_formatted_dates_in_real_template(): void {
        $GLOBALS['wp_stubs_options']['date_format'] = 'd/m/Y';

        $fixture = Fixture_Factory::from_object( (object) [
            'id'            => 556,
            'league_id'     => 10,
            'home_team'     => '1',
            'away_team'     => '2',
            'date'          => '2026-10-02 19:30:00',
            'date_original' => '2026-09-25 19:30:00',
            'start_time'    => '19:30',
            'season'        => '2026',
            'host'          => 'home',
            'match_day'     => 1,
        ] );
        $fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );

        $adapter = new Email_Shortcode_Adapter(
            $fixture_repo,
            $this->presenter,
            new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) )
        );

        $warnings = [];
        set_error_handler( static function ( int $errno, string $errstr ) use ( &$warnings ): bool {
            $warnings[] = $errstr;

            return true;
        } );
        try {
            // Mirrors the legacy match_date_change_notification() template tag, which passes raw MySQL datetimes.
            $html = $adapter->show_match_date_change_notification( [
                'match'         => 556,
                'new_date'      => '2026-10-02 19:30:00',
                'original_date' => '2026-09-25 19:30:00',
                'delay'         => true,
            ] );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'The new date is 2 October 2026 19:30.', $html );
        $this->assertStringContainsString( 'This is now after the round end date (25 September 2026 19:30).', $html );
        $this->assertStringNotContainsString( '2026-10-02 19:30:00', $html );
        $this->assertStringContainsString( 'href="https://example.com"', $html );
        $this->assertStringContainsString( 'Test Tennis League', $html );
    }

    public function test_match_date_change_shortcode_without_start_time_uses_site_date_format_in_real_template(): void {
        $GLOBALS['wp_stubs_options']['date_format'] = 'j M Y';

        $adapter = new Email_Shortcode_Adapter(
            $this->fixture_repo,
            $this->presenter,
            new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) )
        );

        $html = $adapter->show_match_date_change_notification( [
            'match'    => 555,
            'new_date' => '2026-10-02 00:00:00',
        ] );

        $this->assertStringContainsString( 'The new date is 2 Oct 2026.', $html );
        $this->assertStringNotContainsString( 'after the round end date', $html );
    }

    public function test_real_php_template_renderer_renders_email_templates_without_errors(): void {
        $php_renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );

        $fixture = Fixture_Factory::from_object( (object) [
            'id'            => 555,
            'league_id'     => 10,
            'home_team'     => '1',
            'away_team'     => '2',
            'date'          => '2026-06-08 19:00:00',
            'date_original' => '2026-06-01 19:00:00',
            'season'        => '2026',
            'host'          => 'home',
            'match_day'     => 1,
        ] );

        // 1. Result notification
        $vars_result = $this->presenter->present_result_notification( $fixture, [
            'league'    => '10',
            'match_day' => '1',
        ] );
        $html_result = $php_renderer->render_to_string( 'email/result-notification', (object) $vars_result, $vars_result );
        $this->assertStringContainsString( 'Test Tennis League', $html_result );
        $this->assertStringContainsString( 'Division 1', $html_result );

        // 2. Pending match result
        $vars_pending = $this->presenter->present_result_outstanding_notification( $fixture, [] );
        $html_pending = $php_renderer->render_to_string( 'email/match-result-pending', (object) $vars_pending, $vars_pending );
        $this->assertStringContainsString( 'Test Tennis League', $html_pending );
        $this->assertStringContainsString( 'Home Team', $html_pending );
        $this->assertStringContainsString( 'Away Team', $html_pending );

        // 3. Match date change notification
        $vars_date = $this->presenter->present_match_date_change_notification( $fixture, [
            'original_date' => '2026-06-01',
            'new_date'      => '2026-06-08',
        ] );
        $html_date = $php_renderer->render_to_string( 'email/match-date-change-notification', (object) $vars_date, $vars_date );
        $this->assertStringContainsString( 'Test Tennis League', $html_date );

        // 4. Team withdrawn
        $team = (object) [ 'title' => 'Withdrawn Team' ];
        $league = (object) [ 'title' => 'Division 1' ];
        $vars_withdr = $this->presenter->present_team_withdrawn_email( $team, $league, '2026', [
            'subject' => 'Team Withdrawal Notice',
        ] );
        $html_withdr = $php_renderer->render_to_string( 'email/team-withdrawn', (object) $vars_withdr, $vars_withdr );
        $this->assertStringContainsString( 'Withdrawn Team', $html_withdr );

        // 5. Match team withdrawn
        $vars_match_withdr = $this->presenter->present_withdrawn_team_match( [
            'event'   => 'Men Singles',
            'round'   => 'Quarter-Final',
            'subject' => 'Opponent Withdrawn',
        ] );
        $html_match_withdr = $php_renderer->render_to_string( 'email/match-team-withdrawn', (object) $vars_match_withdr, $vars_match_withdr );
        $this->assertStringContainsString( 'Men Singles', $html_match_withdr );

        // 6. Event constitution
        $event = (object) [ 'name' => 'Annual Tournament' ];
        $vars_const = $this->presenter->present_event_constitution( $event );
        $html_const = $php_renderer->render_to_string( 'event/constitution', (object) $vars_const, $vars_const );
        $this->assertStringContainsString( 'Annual Tournament', $html_const );
    }
}
