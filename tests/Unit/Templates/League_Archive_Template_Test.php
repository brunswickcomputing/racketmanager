<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\Queries\Get_League_Archive_Handler;
use Racketmanager\Application\League\Queries\Get_League_Archive_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\League_Archive_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class League_Archive_Template_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Shortcode_Template_Renderer $template_renderer;
    private Shortcode_View_Presenter $view_presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $templates_dir           = ( defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH : dirname( __DIR__, 3 ) . '/' ) . 'templates/';
        $view_renderer           = new Php_View_Renderer( $templates_dir );
        $this->template_renderer = new Shortcode_Template_Renderer( $view_renderer );
        $this->view_presenter    = new Shortcode_View_Presenter( $this->template_renderer );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function render_and_capture_warnings( callable $render_callback, array &$warnings ): string {
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            return (string) $render_callback();
        } finally {
            restore_error_handler();
        }
    }

    private function create_option_gateway(): Option_Gateway_Interface {
        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            fn( string $option, mixed $default = false ) => 'date_format' === $option ? 'j M Y' : $default
        );

        return $option_gateway;
    }

    /**
     * Light League double: real setters, no persistence constructor.
     */
    private function create_league( Event $event, array $props ): League {
        return new class( $event, $props ) extends League {
            public function __construct( Event $event, array $props ) {
                $this->id             = $props['id'] ?? 100;
                $this->title          = $props['title'] ?? 'Division 1';
                $this->event_id       = $props['event_id'] ?? $event->id;
                $this->season         = $props['season'] ?? '2026';
                $this->current_season = $props['current_season'] ?? [
                    'name'           => '2026',
                    'home_away'      => 'true',
                    'num_match_days' => 14,
                    'match_dates'    => [],
                ];
                $this->event          = $event;
            }
        };
    }

    private function create_event( Competition $competition, array $props = [] ): Event {
        $seasons = $props['seasons'] ?? [ [ 'name' => '2025' ], [ 'name' => '2026' ] ];
        $leagues = $props['leagues'] ?? [];

        return new class( $competition, $seasons, $leagues, $props ) extends Event {
            public function __construct(
                Competition $competition,
                private readonly array $event_seasons,
                private readonly array $event_leagues,
                array $props
            ) {
                $this->id          = $props['id'] ?? 20;
                $this->name        = $props['name'] ?? 'Men\'s Doubles';
                $this->is_box      = $props['is_box'] ?? false;
                $this->competition = $competition;
            }

            public function get_seasons(): array {
                return $this->event_seasons;
            }

            public function get_leagues( array $args = [] ): array|int {
                return $this->event_leagues;
            }
        };
    }

    public function test_archive_template_renders_via_handler_with_zero_warnings_and_expected_markup(): void {
        $presenter = new League_Archive_Presenter( $this->create_option_gateway() );

        $competition = new Competition(
            name: 'Summer League 2026',
            type: Competition_Type::LEAGUE,
            age_group: 'Open',
            id: 10
        );
        $competition->is_team_entry   = true;
        $competition->is_championship = false;
        $competition->set_seasons( [
            '2026' => [
                'name'       => '2026',
                'date_start' => '2026-09-21',
                'date_end'   => '2027-03-21',
            ],
        ] );

        $event  = $this->create_event( $competition );
        $league = $this->create_league( $event, [
            'id'       => 100,
            'title'    => 'Division 1',
            'event_id' => 20,
        ] );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_League_Archive_Handler(
            league_service: $league_service,
            competition_service: $comp_service,
            template_renderer: $this->template_renderer,
            archive_presenter: $presenter
        );

        $query = new Get_League_Archive_Query(
            league_name: 'Division 1',
            tab: 'standings',
            post_id: 123
        );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'archive', $vm->template );
        $this->assertSame( 'last5', $league->templates['standings'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->view_presenter->render( $vm ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering archive template must produce zero PHP warnings' );
        $this->assertStringContainsString( 'archive-100', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Men\'s Doubles', $output );
        $this->assertStringContainsString( 'Summer League 2026', $output );
        $this->assertStringContainsString( '21 Sep 2026 to 21 Mar 2027', $output );
        $this->assertStringContainsString( 'calendar-range-fill', $output );
        $this->assertStringContainsString( 'value="2026" selected="selected"', $output );
        $this->assertStringContainsString( 'name="season"', $output );
        $this->assertStringContainsString( 'id="pageContentTab"', $output );
        $this->assertStringContainsString( "season='2026'", $output );
        $this->assertStringContainsString( "template='last5'", $output );
        $this->assertStringContainsString( "league_id='100'", $output );
        $this->assertStringContainsString( '[standings', $output );
    }

    public function test_archive_template_renders_matches_tab_when_specified_in_query_vars(): void {
        $presenter = new League_Archive_Presenter( $this->create_option_gateway() );

        $competition = new Competition(
            name: 'Summer League 2026',
            type: Competition_Type::LEAGUE,
            age_group: 'Open',
            id: 10
        );
        $competition->is_team_entry   = true;
        $competition->is_championship = false;
        $competition->set_seasons( [
            '2026' => [
                'name'       => '2026',
                'date_start' => '2026-09-21',
                'date_end'   => '2027-03-21',
            ],
        ] );

        $event  = $this->create_event( $competition, [ 'seasons' => [ [ 'name' => '2026' ] ] ] );
        $league = $this->create_league( $event, [
            'id'       => 100,
            'title'    => 'Division 1',
            'event_id' => 20,
        ] );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_League_Archive_Handler(
            league_service: $league_service,
            competition_service: $comp_service,
            template_renderer: $this->template_renderer,
            archive_presenter: $presenter
        );

        $query = new Get_League_Archive_Query(
            league_name: 'Division 1',
            matches: 'results',
            tab: 'matches',
            post_id: 74
        );

        $vm = $handler->handle( $query );

        $this->assertSame( 'results', $league->templates['matches'] );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->view_presenter->render( $vm ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering matches tab archive template must produce zero PHP warnings' );
        $this->assertStringContainsString( 'id="matches-tab"', $output );
        $this->assertStringContainsString( 'nav-link tabData active', $output );
        $this->assertStringContainsString( '[matches', $output );
        $this->assertStringContainsString( "season='2026'", $output );
        $this->assertStringContainsString( "template='results'", $output );
        $this->assertStringContainsString( "league_id='100'", $output );
        $this->assertStringContainsString( "post_id='74'", $output );
        $this->assertStringContainsString( 'action="https://example.test/page/74"', $output );
    }

    public function test_archive_template_renders_box_league_with_zero_warnings(): void {
        $presenter = new League_Archive_Presenter();

        $competition = new Competition(
            name: 'Singles Box League',
            type: Competition_Type::LEAGUE,
            age_group: 'Open',
            id: 11
        );
        $competition->is_team_entry   = false;
        $competition->is_championship = false;

        $event = $this->create_event( $competition, [
            'id'      => 21,
            'name'    => 'Men\'s Box',
            'is_box'  => true,
            'seasons' => [ [ 'name' => 'Round 1' ] ],
        ] );

        $league = $this->create_league( $event, [
            'id'             => 105,
            'title'          => 'Box 1',
            'event_id'       => 21,
            'current_season' => [
                'name'           => 'Round 1',
                'home_away'      => 'false',
                'num_match_days' => 5,
                'match_dates'    => [],
            ],
        ] );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_League_Archive_Handler(
            league_service: $league_service,
            competition_service: $comp_service,
            template_renderer: $this->template_renderer,
            archive_presenter: $presenter
        );

        $query = new Get_League_Archive_Query(
            league_name: 'Box 1',
            tab: 'matches'
        );

        $vm = $handler->handle( $query );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->view_presenter->render( $vm ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering box league archive template must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Round - Round 1', $output );
        $this->assertStringContainsString( 'Box 1', $output );
    }

    public function test_archive_template_renders_championship_with_zero_warnings(): void {
        $competition = new Competition(
            name: 'Cup Championship',
            type: Competition_Type::CUP,
            age_group: 'Open',
            id: 12
        );
        $competition->is_team_entry   = true;
        $competition->is_championship = true;

        $event = $this->create_event( $competition, [
            'id'      => 22,
            'name'    => 'Championship Finals',
            'seasons' => [ [ 'name' => '2026' ] ],
        ] );

        $league = $this->create_league( $event, [
            'id'       => 110,
            'title'    => 'Finals League',
            'event_id' => 22,
            'season'   => '2026',
        ] );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $presenter = new League_Archive_Presenter();
        $handler   = new Get_League_Archive_Handler(
            league_service: $league_service,
            competition_service: $comp_service,
            template_renderer: $this->template_renderer,
            archive_presenter: $presenter
        );

        $query = new Get_League_Archive_Query(
            league_name: 'Finals League'
        );

        $vm = $handler->handle( $query );

        $warnings = [];
        $output   = $this->render_and_capture_warnings(
            fn() => $this->view_presenter->render( $vm ),
            $warnings
        );

        $this->assertSame( [], $warnings, 'Rendering championship archive template must produce zero PHP warnings' );
        $this->assertStringContainsString( 'Finals League', $output );
        $this->assertStringNotContainsString( 'id="pageContentTab"', $output );
    }
}
