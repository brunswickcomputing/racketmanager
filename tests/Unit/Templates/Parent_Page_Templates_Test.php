<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Query;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Parent_Page_Templates_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
    use Creates_Competition_Season_Service;
    use Manages_Wordpress_Globals;

    private string $templates_dir;
    private Php_View_Renderer $renderer;
    private Shortcode_View_Presenter $view_presenter;
    private Competition_Shortcode_Presenter $competition_presenter;
    private Tournament_Presenter $tournament_presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_GET                        = [];
        $_POST                       = [];
        $_REQUEST                    = [];
        $GLOBALS['query_vars']       = [];
        unset( $GLOBALS['wp_query'] );
        global $wp;
        $wp                          = (object) [ 'query_vars' => [] ];
        $this->templates_dir         = dirname( __DIR__, 3 ) . '/templates/';
        $this->renderer              = new Php_View_Renderer( $this->templates_dir );
        $this->competition_presenter = new Competition_Shortcode_Presenter( $this->renderer );
        $this->tournament_presenter  = new Tournament_Presenter();
        $this->view_presenter        = new Shortcode_View_Presenter(
            new Shortcode_Template_Renderer( $this->renderer )
        );
    }

    protected function tearDown(): void {
        $_GET                  = [];
        $_POST                 = [];
        $_REQUEST              = [];
        $GLOBALS['query_vars'] = [];
        unset( $GLOBALS['wp_query'] );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_competition_handler_to_page_render_league_with_no_post(): void {
        $competition = new Competition(
            name: 'Summer League',
            type: Competition_Type::LEAGUE,
            age_group: 'seniors',
            seasons: [
                '2026' => [
                    'name'        => '2026',
                    'venue'       => 1,
                    'date_start'  => '2026-05-01',
                    'date_end'    => '2026-08-31',
                ],
            ],
            id: 15
        );
        $competition->is_cup = false;

        $club = Club::from_object( (object) [
            'id'        => 1,
            'name'      => 'Central Club',
            'shortcode' => 'Central Club',
        ] );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club' )->willReturn( $club );

        $player_service   = $this->createStub( Player_Service::class );
        $security_service = $this->createStub( Security_Service_Interface::class );
        $settings_service = $this->createStub( Settings_Service::class );

        $handler = new Get_Competition_Handler(
            $comp_service,
            $club_service,
            $player_service,
            $security_service,
            $settings_service,
            $this->competition_season_service( [ 1 => 'Central Club' ] )
        );

        $query = new Get_Competition_Query(
            competition_id: 15,
            season: '2026',
            tab: 'teams'
        );

        $vm = $handler->handle( $query );

        $this->assertSame( 'teams', $vm->tab );
        $this->assertFalse( $vm->is_singular );
        $this->assertSame( 'summer-league', $vm->competition_seo_name );
        $this->assertSame( 'summer-league', $vm->pagename );
        $this->assertArrayHasKey( 'overview', $vm->menu_options );
        $this->assertArrayHasKey( 'events', $vm->menu_options );
        $this->assertArrayHasKey( 'clubs', $vm->menu_options );
        $this->assertArrayHasKey( 'teams', $vm->menu_options );
        $this->assertArrayHasKey( 'players', $vm->menu_options );
        $this->assertArrayHasKey( 'winners', $vm->menu_options );
        $this->assertArrayNotHasKey( 'order_of_play', $vm->menu_options );
        $this->assertTrue( $vm->menu_options['teams']['selected'] );
        $this->assertFalse( $vm->menu_options['overview']['selected'] );

        $output = $this->competition_presenter->render( $vm );

        $this->assertStringContainsString( 'Summer League', $output );
        $this->assertStringContainsString( 'Central Club', $output );
        $this->assertStringContainsString( 'id="teams-tab"', $output );
        $this->assertStringContainsString( 'aria-selected="1"', $output );
        $this->assertStringContainsString( 'data-name="summer-league"', $output );
        $this->assertStringContainsString( 'id="competitionTabContent"', $output );
        $this->assertStringNotContainsString( 'id="order_of_play-tab"', $output );
    }

    public function test_competition_handler_to_page_render_cup_with_order_of_play(): void {
        $competition = new Competition(
            name: 'Knockout Cup',
            type: Competition_Type::CUP,
            age_group: 'seniors',
            seasons: [
                '2026' => [
                    'name'        => '2026',
                    'venue'       => 2,
                    'orderofplay' => true,
                    'date_start'  => '2026-06-01',
                    'date_end'    => '2026-07-01',
                ],
            ],
            id: 22
        );
        $competition->is_cup = true;

        $club = Club::from_object( (object) [
            'id'        => 2,
            'name'      => 'Memorial Ground',
            'shortcode' => 'Memorial Ground',
        ] );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club' )->willReturn( $club );

        $player_service   = $this->createStub( Player_Service::class );
        $security_service = $this->createStub( Security_Service_Interface::class );
        $settings_service = $this->createStub( Settings_Service::class );

        $handler = new Get_Competition_Handler(
            $comp_service,
            $club_service,
            $player_service,
            $security_service,
            $settings_service,
            $this->competition_season_service( [ 2 => 'Memorial Ground' ] )
        );

        $query = new Get_Competition_Query(
            competition_id: 22,
            season: '2026',
            tab: 'overview'
        );

        $vm = $handler->handle( $query );

        $this->assertArrayHasKey( 'order_of_play', $vm->menu_options );
        $this->assertTrue( $vm->menu_options['order_of_play']['available'] );
        $this->assertSame( 'knockout-cup', $vm->competition_seo_name );

        $output = $this->competition_presenter->render( $vm );

        $this->assertStringContainsString( 'Knockout Cup', $output );
        $this->assertStringContainsString( 'Memorial Ground', $output );
        $this->assertStringContainsString( 'id="order_of_play-tab"', $output );
        $this->assertStringContainsString( 'data-name="knockout-cup"', $output );
    }

    public function test_competition_handler_regression_season_selector_pagename(): void {
        $competition = new Competition(
            name: 'Summer League',
            type: Competition_Type::LEAGUE,
            age_group: 'seniors',
            seasons: [
                '2026' => [
                    'name'       => '2026',
                    'date_start' => '2026-05-01',
                    'date_end'   => '2026-08-31',
                ],
            ],
            id: 15
        );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $handler = new Get_Competition_Handler(
            $comp_service,
            $this->createStub( Club_Service::class ),
            $this->createStub( Player_Service::class ),
            $this->createStub( Security_Service_Interface::class ),
            $this->createStub( Settings_Service::class ),
            $this->competition_season_service()
        );

        // Rewrite rules set query_vars['pagename'] = 'competition'
        $query = Get_Competition_Query::from_atts(
            atts: [],
            query_vars: [ 'competition' => 'summer-league', 'pagename' => 'competition' ],
            get: []
        );

        $vm = $handler->handle( $query );

        $this->assertSame( 'summer-league', $vm->competition_seo_name );
        $this->assertSame( 'summer-league', $vm->pagename );

        $output = $this->competition_presenter->render( $vm );
        $this->assertStringContainsString( 'value="summer-league"', $output );
        $this->assertStringNotContainsString( 'id="pagename" value="competition"', $output );
    }

    private function create_event(
        int $id,
        string $name,
        Competition $competition,
        bool $is_box = false
    ): Event {
        $event                 = ( new \ReflectionClass( Event::class ) )->newInstanceWithoutConstructor();
        $event->id             = $id;
        $event->name           = $name;
        $event->is_box         = $is_box;
        $event->competition_id = $competition->id;
        $event->competition    = $competition;
        $event->seasons        = wp_json_encode( [ '2026' => [ 'name' => '2026' ] ] );
        $event->current_season = [ 'name' => '2026' ];
        return $event;
    }

    public function test_event_handler_to_page_render_league_event(): void {
        $competition = new Competition(
            name: 'Summer League',
            type: Competition_Type::LEAGUE,
            age_group: 'seniors',
            seasons: [ '2026' => [ 'name' => '2026', 'events' => [ 50 ] ] ],
            id: 10
        );
        $competition->is_championship = false;
        $competition->current_season = [ 'name' => '2026', 'events' => [ 50 ] ];

        $event = $this->create_event( 50, "Men's Division 1", $competition, false );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_Event_Handler( $comp_service, $this->event_query_service() );
        $query   = new Get_Event_Query(
            event_id: 50,
            season: '2026',
            tab: 'standings',
            post_id: 88,
            pagename: 'mens-division-1'
        );

        $adapter = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            competition_shortcode_presenter: $this->competition_presenter
        );
        $result = $handler->handle( $query );
        $vm     = $adapter->present_event( $result );

        $this->assertSame( 'standings', $vm->vars['tab'] );
        $this->assertSame( 'league', $vm->vars['competition_type'] );
        $this->assertSame( 'mens-division-1', $vm->vars['event_seo_name'] );
        $this->assertArrayHasKey( 'standings', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'clubs', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'teams', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'players', $vm->vars['menu_options'] );
        $this->assertArrayNotHasKey( 'draw', $vm->vars['menu_options'] );

        $output = $this->view_presenter->render( $vm );

        $this->assertStringContainsString( "Men's Division 1", $output );
        $this->assertStringContainsString( 'id="standings-tab"', $output );
        $this->assertStringContainsString( 'id="clubs-tab"', $output );
        $this->assertStringContainsString( 'id="teams-tab"', $output );
        $this->assertStringContainsString( 'data-name="mens-division-1"', $output );
        $this->assertStringContainsString( 'data-competition-type="league"', $output );
        $this->assertStringNotContainsString( 'id="draw-tab"', $output );
    }

    public function test_event_handler_to_page_render_championship_event(): void {
        $competition = new Competition(
            name: 'County Championship',
            type: Competition_Type::TOURNAMENT,
            age_group: 'seniors',
            seasons: [ '2026' => [ 'name' => '2026', 'events' => [ 60 ] ] ],
            id: 12
        );
        $competition->is_championship = true;
        $competition->current_season = [ 'name' => '2026', 'events' => [ 60 ] ];

        $event = $this->create_event( 60, "Men's Singles", $competition, false );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_Event_Handler( $comp_service, $this->event_query_service() );
        $query   = new Get_Event_Query(
            event_id: 60,
            season: '2026',
            tab: 'draw'
        );

        $adapter = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            competition_shortcode_presenter: $this->competition_presenter
        );
        $result = $handler->handle( $query );
        $vm     = $adapter->present_event( $result );

        $this->assertSame( 'draw', $vm->vars['tab'] );
        $this->assertSame( 'tournament', $vm->vars['competition_type'] );
        $this->assertSame( 'mens-singles', $vm->vars['event_seo_name'] );
        $this->assertArrayHasKey( 'draw', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'matches', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'teams', $vm->vars['menu_options'] );
        $this->assertSame( 'list', $vm->vars['menu_options']['teams']['template'] );
        $this->assertArrayHasKey( 'players', $vm->vars['menu_options'] );
        $this->assertArrayNotHasKey( 'standings', $vm->vars['menu_options'] );

        $output = $this->view_presenter->render( $vm );

        $this->assertStringContainsString( "Men's Singles", $output );
        $this->assertStringContainsString( 'id="draw-tab"', $output );
        $this->assertStringContainsString( 'id="matches-tab"', $output );
        $this->assertStringContainsString( 'data-competition-type="tournament"', $output );
        $this->assertStringNotContainsString( 'id="standings-tab"', $output );
    }

    public function test_event_handler_to_page_render_box_event_hides_clubs_and_players(): void {
        $competition = new Competition(
            name: 'Box League',
            type: Competition_Type::LEAGUE,
            age_group: 'seniors',
            seasons: [ '2026' => [ 'name' => '2026', 'events' => [ 70 ] ] ],
            id: 30
        );
        $competition->is_championship = false;
        $competition->current_season = [ 'name' => '2026', 'events' => [ 70 ] ];

        $event = $this->create_event( 70, 'Box 1', $competition, true );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_Event_Handler( $comp_service, $this->event_query_service() );
        $query   = new Get_Event_Query(
            event_id: 70,
            season: '2026',
            tab: 'standings'
        );

        $adapter = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            competition_shortcode_presenter: $this->competition_presenter
        );
        $result = $handler->handle( $query );
        $vm     = $adapter->present_event( $result );

        $this->assertArrayHasKey( 'standings', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'teams', $vm->vars['menu_options'] );
        $this->assertArrayNotHasKey( 'clubs', $vm->vars['menu_options'] );
        $this->assertArrayNotHasKey( 'players', $vm->vars['menu_options'] );
    }

    public function test_tournament_handler_to_page_render_with_real_entity_and_no_post(): void {
        $tournament = new Tournament();
        $tournament->id = 99;
        $tournament->name = 'County Open 2026';
        $tournament->competition_id = 5;
        $tournament->season = '2026';
        $tournament->date_start = '2026-07-01';
        $tournament->date_end = '2026-07-10';
        $tournament->is_open = true;
        $tournament->is_started = true;
        $tournament->order_of_play = null;

        $competition = new Competition(
            name: 'County Open',
            type: Competition_Type::TOURNAMENT,
            age_group: 'seniors',
            id: 5
        );

        $club = new Club();
        $club->id = 1;
        $club->name = 'County Lawn Tennis Club';
        $club->shortcode = 'CLTC';

        $details = new Tournament_Details_DTO(
            tournament: $tournament,
            competition: $competition,
            club: $club
        );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_with_details' )->willReturn( $details );
        $tournament_service->method( 'get_tournaments' )->willReturn( [ $details ] );

        $handler = new Get_Tournament_Handler( $tournament_service );
        $query   = new Get_Tournament_Query(
            tournament: 'County Open 2026',
            tab: 'draws'
        );

        $adapter = new Tournament_Shortcode_Adapter( tournament_presenter: $this->tournament_presenter );
        $vm      = $adapter->present_tournament( $handler->handle( $query ) );

        $this->assertSame( 'draws', $vm->vars['tab'] );
        $this->assertSame( 'county-open-2026', $vm->vars['tournament_seo_name'] );
        $this->assertArrayHasKey( 'overview', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'events', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'draws', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'matches', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'players', $vm->vars['menu_options'] );
        $this->assertArrayHasKey( 'order_of_play', $vm->vars['menu_options'] );
        $this->assertFalse( $vm->vars['menu_options']['order_of_play']['available'] );

        $output = $this->view_presenter->render( $vm );

        $this->assertStringContainsString( 'id="tournament-99"', $output );
        $this->assertStringContainsString( 'County Open 2026', $output );
        $this->assertStringContainsString( 'CLTC', $output );
        $this->assertStringContainsString( '2026-07-01 to 2026-07-10', $output );
        $this->assertStringContainsString( 'id="draws-tab"', $output );
        $this->assertStringContainsString( 'id="racketmanager_tournament_selections"', $output );
        $this->assertStringContainsString( 'data-name="county-open-2026"', $output );
    }

    public function test_tournament_handler_routing_variants(): void {
        $tournament = new Tournament();
        $tournament->id = 88;
        $tournament->name = 'Autumn Trophy';
        $tournament->competition_id = 7;
        $tournament->season = '2026';
        $tournament->date_start = '2026-11-01';
        $tournament->date_end = gmdate( 'Y-m-d', strtotime( '+30 days' ) );
        $tournament->order_of_play = '1';

        $competition = new Competition(
            name: 'Autumn Trophy',
            type: Competition_Type::TOURNAMENT,
            age_group: 'seniors',
            id: 7
        );

        $details = new Tournament_Details_DTO(
            tournament: $tournament,
            competition: $competition,
            club: new Club()
        );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_with_details' )->willReturn( $details );
        $tournament_service->method( 'get_tournaments' )->willReturn( [ $details ] );

        $handler = new Get_Tournament_Handler( $tournament_service );
        $adapter = new Tournament_Shortcode_Adapter( tournament_presenter: $this->tournament_presenter );

        // 1. With match_day -> tab forced to matches
        $query1 = new Get_Tournament_Query( tournament: 88, match_day: '2026-09-02' );
        $vm1 = $adapter->present_tournament( $handler->handle( $query1 ) );
        $this->assertSame( 'matches', $vm1->vars['tab'] );
        $this->assertTrue( $vm1->vars['menu_options']['order_of_play']['available'] );

        // 2. With team_id -> tab forced to matches
        $query2 = new Get_Tournament_Query( tournament: 88, team_id: 123 );
        $vm2 = $adapter->present_tournament( $handler->handle( $query2 ) );
        $this->assertSame( 'matches', $vm2->vars['tab'] );

        // 3. With event -> tab forced to events
        $query3 = new Get_Tournament_Query( tournament: 88, event: 'mens-singles' );
        $vm3 = $adapter->present_tournament( $handler->handle( $query3 ) );
        $this->assertSame( 'events', $vm3->vars['tab'] );

        // 4. Default tab is overview; post_id passes through
        $vm4 = $adapter->present_tournament( $handler->handle( new Get_Tournament_Query( tournament: 88, post_id: 55 ) ) );
        $this->assertSame( 'overview', $vm4->vars['tab'] );
        $this->assertSame( 55, $vm4->vars['post_id'] );

        // 5. Empty end date hides order of play (HEAD behaviour)
        $tournament->date_end = '';
        $vm5 = $adapter->present_tournament( $handler->handle( new Get_Tournament_Query( tournament: 88 ) ) );
        $this->assertFalse( $vm5->vars['menu_options']['order_of_play']['available'] );

        // 6. Elapsed end date hides order of play
        $tournament->date_end = gmdate( 'Y-m-d', strtotime( '-2 days' ) );
        $vm6 = $adapter->present_tournament( $handler->handle( new Get_Tournament_Query( tournament: 88 ) ) );
        $this->assertFalse( $vm6->vars['menu_options']['order_of_play']['available'] );
    }

    public function test_query_tab_precedence_and_sanitization(): void {
        // Competition query: GET tab overrides atts and query_vars, and strips tags
        $comp_query = Get_Competition_Query::from_atts(
            atts: [ 'tab' => 'events' ],
            query_vars: [ 'tab' => 'clubs' ],
            get: [ 'tab' => '<script>alert("xss")</script>players' ]
        );
        $this->assertSame( 'alert("xss")players', $comp_query->tab );

        // Event query: GET tab overrides atts and query_vars
        $event_query = Get_Event_Query::from_atts(
            atts: [ 'tab' => 'clubs' ],
            query_vars: [ 'tab' => 'teams' ],
            get: [ 'tab' => '<b>matches</b>' ]
        );
        $this->assertSame( 'matches', $event_query->tab );

        // Tournament query: GET tab overrides atts and query_vars
        $tourn_query = Get_Tournament_Query::from_atts(
            atts: [ 'tab' => 'events' ],
            query_vars: [ 'tab' => 'draws' ],
            get: [ 'tab' => '<i>players</i>' ]
        );
        $this->assertSame( 'players', $tourn_query->tab );
    }
}
