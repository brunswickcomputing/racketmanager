<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Overview_Query;
use Racketmanager\Application\Competition\Queries\Get_Competitions_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competitions_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Competition_Header_View_Model;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Presentation\View_Models\Competition_Overview_View_Model;
use Racketmanager\Presentation\View_Models\Event_Header_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Header_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use stdClass;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;

class Public_Headers_And_Lists_Templates_Test extends TestCase {
    use Creates_Competition_Season_Service;
    private string $templates_dir;

    protected function setUp(): void {
        parent::setUp();
        $this->templates_dir = dirname( __DIR__, 3 ) . '/templates/';
    }

    public function test_tournament_header_renders_from_view_model(): void {
        $tournament_header = new Tournament_Header_View_Model(
            name: 'Wimbledon Championships',
            venue_name: 'All England Lawn Tennis Club',
            date_start_display: '29 Jun 2026',
            date_end_display: '12 Jul 2026',
            has_date_range: true,
            show_entry_button: true,
            entry_url: '/tournament/entry-form/wimbledon-championships/'
        );

        ob_start();
        include $this->templates_dir . 'includes/tournament-header.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Wimbledon Championships', $output );
        $this->assertStringContainsString( 'All England Lawn Tennis Club', $output );
        $this->assertStringContainsString( '29 Jun 2026', $output );
        $this->assertStringContainsString( '12 Jul 2026', $output );
        $this->assertStringContainsString( 'href="/tournament/entry-form/wimbledon-championships/"', $output );
        $this->assertStringContainsString( 'Enter', $output );
    }

    public function test_tournament_header_does_not_present_legacy_tournament_objects(): void {
        $tournament = (object) [
            'name'       => 'Spring Open',
            'venue_name' => 'Westcliff Hardcourts',
            'date_start' => '2026-05-01',
            'date'       => '2026-05-10',
            'is_open'    => true,
        ];
        $entry_option = true;

        ob_start();
        include $this->templates_dir . 'includes/tournament-header.php';
        $output = (string) ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_competition_header_renders_from_view_model(): void {
        $competition_header = new Competition_Header_View_Model(
            name: 'Summer Premier League',
            season_title: '2026',
            icon_path: 'assets/icons/bootstrap-icons.svg#table',
            venue_name: 'Central Courts',
            date_start_display: '01 May 2026',
            date_end_display: '31 Aug 2026',
            has_date_range: true,
            entry_link: 'https://example.com/enter',
            show_season_selector: true,
            post_id: 123,
            pagename: 'leagues/summer-premier',
            form_action: 'https://example.com/leagues/summer-premier',
            seasons: [
                [ 'name' => '2025' ],
                [ 'name' => '2026' ],
            ],
            current_season_name: '2026'
        );

        ob_start();
        include $this->templates_dir . 'includes/competition-header.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Summer Premier League - 2026', $output );
        $this->assertStringContainsString( 'Central Courts', $output );
        $this->assertStringContainsString( '01 May 2026', $output );
        $this->assertStringContainsString( '31 Aug 2026', $output );
        $this->assertStringContainsString( 'https://example.com/enter', $output );
        $this->assertStringContainsString( 'value="2026"', $output );
        $this->assertStringContainsString( 'selected', $output );
    }

    public function test_competition_header_does_not_present_legacy_competition_objects(): void {
        $competition = (object) [
            'name'       => 'Autumn Cup',
            'entry_link' => '',
            'type'       => Competition_Type::CUP,
        ];
        $competition_season = [
            'name'       => '2026',
            'venue_name' => 'Leigh TC',
            'date_start' => '2026-09-01',
            'date_end'   => '2026-10-31',
        ];
        $seasons = [ [ 'name' => '2026' ] ];
        $post_id = '456';
        $pagename = 'cups/autumn';

        ob_start();
        include $this->templates_dir . 'includes/competition-header.php';
        $output = (string) ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_event_header_renders_from_view_model(): void {
        $event_header = new Event_Header_View_Model(
            name: 'Men’s Singles Championship',
            header_level: 2,
            icon_path: 'assets/icons/lta-icons.svg#icon-bracket',
            competition_name: 'Summer Tournament',
            competition_link: '/summer-tournament/2026/',
            date_start_display: '01 Jun 2026',
            date_end_display: '15 Jun 2026',
            has_date_range: true,
            show_season_selector: true,
            post_id: 789,
            pagename: '/tournaments/summer-tournament/',
            form_action: 'https://example.com/tournaments/summer',
            season_label: 'Season',
            seasons: [ [ 'name' => '2026' ] ],
            curr_season: '2026'
        );

        ob_start();
        include $this->templates_dir . 'includes/event-header.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( '<h2 class="media__title">Men’s Singles Championship</h2>', $output );
        $this->assertStringContainsString( 'Summer Tournament', $output );
        $this->assertStringContainsString( '/summer-tournament/2026/', $output );
        $this->assertStringContainsString( '01 Jun 2026', $output );
        $this->assertStringContainsString( '15 Jun 2026', $output );
    }

    public function test_competition_list_renders_from_view_model_items(): void {
        $competition_list = [
            new Competition_List_Item_View_Model(
                name: 'Division 1 Men',
                link: '/leagues/division-1/2026/',
                icon_path: 'assets/icons/bootstrap-icons.svg#table',
                venue_name: 'Various Clubs',
                date_start_display: '01 May 2026',
                date_end_display: '31 Aug 2026',
                has_date_range: true,
                is_open: true,
                entry_url: '/league/entry-form/division-1-men/2026/'
            ),
        ];

        ob_start();
        include $this->templates_dir . 'includes/competition-list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'Division 1 Men', $output );
        $this->assertStringContainsString( '/leagues/division-1/2026/', $output );
        $this->assertStringContainsString( 'Various Clubs', $output );
        $this->assertStringContainsString( '01 May 2026', $output );
        $this->assertStringContainsString( '31 Aug 2026', $output );
        $this->assertStringContainsString( '/league/entry-form/division-1-men/2026/', $output );
        $this->assertStringContainsString( 'Enter', $output );
    }

    public function test_competition_list_does_not_present_legacy_objects(): void {
        $competition_list = [
            (object) [
                'name'           => 'Men Cup',
                'season'         => '2026',
                'venue_name'     => 'Central Venue',
                'date_start'     => '2026-06-01',
                'date_end'       => '2026-06-30',
                'is_open'        => true,
                'current_season' => [ 'name' => '2026' ],
            ],
        ];
        $competition_type = 'cup';

        ob_start();
        include $this->templates_dir . 'includes/competition-list.php';
        $output = (string) ob_get_clean();

        $this->assertSame( '', $output );
    }

    public function test_competition_overview_renders_from_view_model(): void {
        $overview_vm = new Competition_Overview_View_Model(
            venue_name: 'National Tennis Centre',
            num_events: 5,
            num_teams: 32,
            num_players: 128,
            grade: 'Grade 3',
            timeline_items: [
                [
                    'key'           => 'entry_open',
                    'label'         => 'Entry opens',
                    'date'          => '01 Apr 2026',
                    'item_class'    => 'is-entry-open',
                    'current_class' => 'is-current is-success',
                ],
                [
                    'key'           => 'closing_deadline',
                    'label'         => 'Closing deadline',
                    'date'          => '15 May 2026',
                    'item_class'    => 'is-entry-closed',
                    'current_class' => '',
                ],
            ]
        );

        ob_start();
        include $this->templates_dir . 'competition/overview.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'National Tennis Centre', $output );
        $this->assertStringContainsString( '5', $output );
        $this->assertStringContainsString( '32', $output );
        $this->assertStringContainsString( '128', $output );
        $this->assertStringContainsString( 'Grade 3', $output );
        $this->assertStringContainsString( 'Entry opens', $output );
        $this->assertStringContainsString( '01 Apr 2026', $output );
        $this->assertStringContainsString( 'Closing deadline', $output );
        $this->assertStringContainsString( '15 May 2026', $output );
    }

    public function test_get_competitions_handler_produces_view_model_and_renders_template(): void {
        $comp = (object) [
            'name'           => 'Summer League',
            'season'         => '2026',
            'venue_name'     => 'Local Tennis Clubs',
            'date_start'     => '2026-05-01',
            'date_end'       => '2026-08-31',
            'is_open'        => true,
            'current_season' => [ 'name' => '2026' ],
        ];

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_criteria' )->willReturn( [ $comp ] );

        $tourn_service = $this->createStub( Tournament_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $player_query_service = $this->createStub( Player_Query_Service::class );
        $player = new Player();
        $player->id = 42;
        $player->display_name = 'John Smith';
        $player_service->method( 'get_player' )->willReturn( $player );
        $player_query_service->method( 'get_competitions' )->willReturn( [ $comp ] );
        $competition_presenter = new Competition_Shortcode_Presenter( new Php_View_Renderer( $this->templates_dir ) );

        $handler = new Get_Competitions_Handler(
            competition_service: $comp_service,
            tournament_service: $tourn_service,
            player_service: $player_service,
            player_query_service: $player_query_service
        );

        $query = new Get_Competitions_Query( type: 'league', user_id: 42 );
        $vm    = ( new Competition_Shortcode_Adapter( competition_shortcode_presenter: $competition_presenter ) )->present_competitions( $handler->handle( $query ) );

        $this->assertEquals( 'competitions', $vm->template );
        $this->assertArrayHasKey( 'competition_items', $vm->vars );
        $this->assertNotEmpty( $vm->vars['competition_items'] );
        $this->assertInstanceOf( Competition_List_Item_View_Model::class, $vm->vars['competition_items'][0] );

        $presenter = new Shortcode_View_Presenter( new Shortcode_Template_Renderer( new Php_View_Renderer( $this->templates_dir ) ) );
        $output    = $presenter->render( $vm );

        $this->assertStringContainsString( 'Summer League 2026', $output );
        $this->assertStringContainsString( 'Local Tennis Clubs', $output );
        $this->assertStringContainsString( 'href="/summer-league/2026/"', $output );
        $this->assertStringNotContainsString( '/player/john-smith/', $output );
    }

    public function test_player_competition_list_keeps_player_profile_suffix(): void {
        $competition = (object) [
            'name'           => 'Summer League',
            'season'         => '2026',
            'is_open'        => false,
            'current_season' => [ 'name' => '2026' ],
        ];
        $player = (object) [ 'id' => 42, 'display_name' => 'John Smith' ];
        $competition_presenter = new Competition_Shortcode_Presenter( new Php_View_Renderer( $this->templates_dir ) );
        $player_presenter = new Player_Presenter( $competition_presenter );
        $player_vm = $player_presenter->present_player(
            player: $player,
            player_competitions: [ 'league' => [ $competition ] ]
        );
        $competition_list = $player_vm->player_competitions['league'];
        $full_width = true;

        ob_start();
        include $this->templates_dir . 'includes/competition-list.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'href="/summer-league/2026/player/john-smith/"', $output );
    }

    public function test_get_competition_overview_handler_produces_view_model_and_renders_template(): void {
        $competition = Competition_Test_Factory::create(
            id: 10,
            name: 'Essex Championship',
            seasons: Competition_Test_Factory::season_map(
                '2026',
                [
                    'venue'        => 130,
                    'date_open'    => '2026-03-01',
                    'date_closing' => '2026-05-01',
                    'date_start'   => '2026-06-01',
                    'date_end'     => '2026-06-15',
                ]
            )
        );

        $overview_data = (object) [
            'id'          => 10,
            'name'        => 'Essex Championship',
            'num_events'  => 4,
            'num_teams'   => 16,
            'num_clubs'   => 8,
            'num_players' => 48,
            'settings'    => json_encode( [ 'grade' => 'Grade 4' ] ),
        ];
        $overview = new Competition_Overview_DTO( $overview_data );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_competition_overview' )->willReturn( $overview );

        $competition_presenter = new Competition_Shortcode_Presenter( new Php_View_Renderer( $this->templates_dir ) );
        $handler = new Get_Competition_Overview_Handler( $comp_service, $this->competition_season_service( [ 130 => 'Thorpe Bay Lawn Tennis Club' ] ) );
        $query   = new Get_Competition_Overview_Query( competition_id: 10, season: 2026 );
        $vm      = ( new Competition_Shortcode_Adapter( competition_shortcode_presenter: $competition_presenter ) )->present_overview( $handler->handle( $query ) );

        $this->assertEquals( 'overview', $vm->template );
        $this->assertArrayHasKey( 'overview_vm', $vm->vars );
        $this->assertInstanceOf( Competition_Overview_View_Model::class, $vm->vars['overview_vm'] );

        $presenter = new Shortcode_View_Presenter( new Shortcode_Template_Renderer( new Php_View_Renderer( $this->templates_dir ) ) );
        $output    = $presenter->render( $vm );

        $this->assertStringContainsString( 'Thorpe Bay Lawn Tennis Club', $output );
        $this->assertStringContainsString( 'Grade 4', $output );
        $this->assertStringContainsString( 'Entry opens', $output );
    }
}
