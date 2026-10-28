<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Club_Entry_Selection_Service;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Entry_Query;
use Racketmanager\Application\League\Queries\Get_League_Players_Handler;
use Racketmanager\Application\League\Queries\Get_League_Players_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Season_Collection;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Email_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Notification_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\League_Standings_View_Model;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

final class Public_Links_Modernization_Test extends TestCase {
    use Creates_Event_Query_Service;
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;
    use Manages_Wordpress_Globals;

    private Php_View_Renderer $view_renderer;
    private Shortcode_Template_Renderer $template_renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $template_path           = defined( 'RACKETMANAGER_PATH' ) ? RACKETMANAGER_PATH . 'templates/' : dirname( __DIR__, 3 ) . '/templates/';
        $this->view_renderer     = new Php_View_Renderer( $template_path );
        $this->template_renderer = new Shortcode_Template_Renderer( $this->view_renderer );
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

    public function test_email_event_constitution_renders_with_presenter_urls_and_no_globals(): void {
        unset( $GLOBALS['racketmanager'] );

        $event                 = new stdClass();
        $event->name           = 'Premier Division';
        $event->is_box         = false;
        $event->current_season = [ 'name' => '2024' ];

        $league1        = new stdClass();
        $league1->id    = 101;
        $league1->title = 'Men Division 1';

        $league2        = new stdClass();
        $league2->id    = 102;
        $league2->title = 'Women Division 1';

        $event->leagues = [ $league1, $league2 ];

        $email_presenter = new Email_Shortcode_Presenter(
            tournament_service: $this->createStub( Tournament_Service::class ),
            competition_service: $this->createStub( Competition_Service::class ),
            team_service: $this->createStub( Team_Service::class ),
            fixture_link_service: $this->createStub( \Racketmanager\Services\Fixture\Fixture_Link_Service::class ),
            permission_service: $this->createStub( \Racketmanager\Services\Fixture\Fixture_Permission_Service::class ),
            site_url: 'https://tennis.example.com',
            site_name: 'Test Tennis League'
        );

        $vars = $email_presenter->present_event_constitution( $event );
        $this->assertSame( 'https://tennis.example.com', $vars['site_url'] );
        $this->assertSame( 'https://tennis.example.com/league/men-division-1/2024/', $vars['league_urls'][101] );
        $this->assertSame( 'https://tennis.example.com/league/women-division-1/2024/', $vars['league_urls'][102] );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $vars ) {
            return $this->view_renderer->render_to_string( 'event/constitution', [], $vars );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'https://tennis.example.com/league/men-division-1/2024/', $html );
        $this->assertStringContainsString( 'https://tennis.example.com/league/women-division-1/2024/', $html );
        $this->assertStringContainsString( 'Men Division 1', $html );
        $this->assertStringContainsString( 'Women Division 1', $html );
    }

    public function test_notification_presenter_event_constitution_renders_correctly(): void {
        unset( $GLOBALS['racketmanager'] );

        $event                 = new stdClass();
        $event->name           = 'Box League Event';
        $event->is_box         = true;
        $event->current_season = [ 'name' => '2024-r1' ];

        $league        = new stdClass();
        $league->id    = 201;
        $league->title = 'Group A';

        $event->leagues = [ $league ];

        $notif_presenter = new Notification_Presenter(
            team_repository: $this->createStub( \Racketmanager\Repositories\Interfaces\Team_Repository_Interface::class ),
            club_repository: $this->createStub( \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::class ),
            tournament_repository: $this->createStub( \Racketmanager\Repositories\Interfaces\Tournament_Repository_Interface::class ),
            fixture_repository: $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
            fixture_link_service: $this->createStub( \Racketmanager\Services\Fixture\Fixture_Link_Service::class ),
            team_service: $this->createStub( Team_Service::class ),
            competition_service: $this->createStub( Competition_Service::class ),
            permission_service: $this->createStub( \Racketmanager\Services\Fixture\Fixture_Permission_Service::class ),
            site_url: 'https://tennis.example.com',
            site_name: 'Test Tennis',
            league_query_service: $this->createStub( League_Query_Service::class )
        );

        $vars = $notif_presenter->present_event_constitution( $event, '2024-r1' );
        $this->assertSame( 'https://tennis.example.com/league/group-a/round-2024-r1/', $vars['league_urls'][201] );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $vars ) {
            return $this->view_renderer->render_to_string( 'event/constitution', [], $vars );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'https://tennis.example.com/league/group-a/round-2024-r1/', $html );
    }

    public function test_standings_and_standings_last5_render_with_presenter_team_links(): void {
        $league                 = new stdClass();
        $league->id             = 10;
        $league->title          = 'Division One';
        $league->current_season = [ 'name' => '2024' ];
        $league->event          = (object) [
            'is_box'      => false,
            'competition' => (object) [ 'type' => Competition_Type::LEAGUE ],
        ];

        $team1                    = new stdClass();
        $team1->id                = 1;
        $team1->title             = 'Club A 1';
        $team1->is_withdrawn      = false;
        $team1->rank              = 1;
        $team1->status_text       = '';
        $team1->status_icon       = 'dash-lg';
        $team1->point_adjustments = [];
        $team1->points            = 10;
        $team1->points_plus       = 10;
        $team1->points_minus      = 0;
        $team1->played            = 5;
        $team1->won               = 3;
        $team1->draw              = 1;
        $team1->draws             = 1;
        $team1->lost              = 1;
        $team1->win_percentage    = 60.0;
        $team1->win_percent       = '60.0%';
        $team1->done_matches      = 5;
        $team1->won_matches       = 3;
        $team1->draw_matches      = 1;
        $team1->lost_matches      = 1;
        $team1->rubbers_won       = 12;
        $team1->rubbers_lost      = 8;
        $team1->rubbers_total     = 20;
        $team1->sets_won          = 24;
        $team1->sets_lost         = 16;
        $team1->sets_allowed      = 16;
        $team1->games_won         = 150;
        $team1->games_lost        = 120;
        $team1->games_allowed     = 120;
        $team1->add_points        = 0;
        $team1->points_formatted  = [ 'primary' => '10' ];
        $team1->last5             = [];

        $league->teams = [ $team1 ];

        $presenter = new League_Standings_Presenter(
            renderer: $this->view_renderer,
            club_presenter: new Club_Presenter(),
            player_presenter: new Player_Presenter()
        );

        $vm = new League_Standings_View_Model(
            template: '',
            league: $league,
            teams: [ $team1 ],
            widget: false,
            season: '2024',
            team_links: [
                1 => '/custom/league/division-one/2024/team/club-a-1/',
            ]
        );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $presenter, $vm ) {
            return $presenter->render( $vm );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( '/custom/league/division-one/2024/team/club-a-1/', $html );
        $this->assertStringContainsString( 'Club A 1', $html );

        // Test standings-last5
        $vm_last5 = new League_Standings_View_Model(
            template: 'last5',
            league: $league,
            teams: [ $team1 ],
            widget: false,
            season: '2024',
            team_links: [
                1 => '/custom/league/division-one/2024/team/club-a-1/',
            ]
        );

        $warnings_last5 = [];
        $html_last5     = $this->render_and_capture_warnings( function () use ( $presenter, $vm_last5 ) {
            return $presenter->render( $vm_last5 );
        }, $warnings_last5 );

        $this->assertEmpty( $warnings_last5 );
        $this->assertStringContainsString( '/custom/league/division-one/2024/team/club-a-1/', $html_last5 );

        // Test presenter computes team_links automatically when not supplied in view model
        $vm_unsupplied = new League_Standings_View_Model(
            template: '',
            league: $league,
            teams: [ $team1 ],
            widget: false,
            season: '2024',
            team_links: []
        );

        $warnings_unsupplied = [];
        $html_unsupplied     = $this->render_and_capture_warnings( function () use ( $presenter, $vm_unsupplied ) {
            return $presenter->render( $vm_unsupplied );
        }, $warnings_unsupplied );

        $this->assertEmpty( $warnings_unsupplied );
        $this->assertStringContainsString( '/league/division-one/2024/team/club-a-1/', $html_unsupplied );
    }

    public function test_player_titles_render_with_precalculated_urls(): void {
        $player_presenter = new Player_Presenter();

        $titles = [
            '2024' => [
                'Annual Open Tournament' => [
                    (object) [
                        'team_id'    => 1,
                        'winner_id'  => 1,
                        'tournament' => 'Annual Open Tournament',
                        'draw'       => 'Men Singles Championship',
                        'title'      => 'Champion',
                    ],
                    (object) [
                        'team_id'    => 2,
                        'winner_id'  => 1,
                        'tournament' => 'Annual Open Tournament',
                        'draw'       => 'Men Doubles Championship',
                        'title'      => 'Finalist',
                    ],
                ],
            ],
        ];

        $presented_titles = $player_presenter->present_titles( $titles );
        $this->assertSame(
            '/tournament/annual-open-tournament/',
            $presented_titles['2024']['Annual Open Tournament'][0]->tournament_url
        );
        $this->assertSame(
            '/tournament/annual-open-tournament/draw/men-singles-championship/',
            $presented_titles['2024']['Annual Open Tournament'][0]->draw_url
        );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $presented_titles ) {
            return $this->view_renderer->render_to_string( 'player/titles', [], [
                'titles' => $presented_titles,
            ] );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( '/tournament/annual-open-tournament/', $html );
        $this->assertStringContainsString( '/tournament/annual-open-tournament/draw/men-singles-championship/', $html );
        $this->assertStringContainsString( 'Champion', $html );
        $this->assertStringContainsString( 'Finalist', $html );
    }

    public function test_club_event_template_renders_with_precalculated_player_links(): void {
        $club            = new stdClass();
        $club->id        = 5;
        $club->name      = 'Westcliff Hardcourt';
        $club->shortcode = 'WHC';

        $event                 = new stdClass();
        $event->name           = 'Mens Singles League';
        $event->is_championship = false;
        $event->current_season = [
            'name'            => '2024',
            'num_match_days'  => 2,
        ];
        $event->competition    = (object) [
            'type' => Competition_Type::LEAGUE,
        ];

        $player_stat            = new stdClass();
        $player_stat->fullname  = 'John Smith';
        $player_stat->matchdays = [];

        $player_links = [
            'John Smith' => '/leagues/mens-singles-league/2024/player/john-smith/',
        ];

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $club, $event, $player_stat, $player_links ) {
            return $this->view_renderer->render_to_string( 'club/event', [], [
                'club'           => $club,
                'event'          => $event,
                'current_season' => $event->current_season,
                'player_stats'   => [ $player_stat ],
                'player_links'   => $player_links,
            ] );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( '/leagues/mens-singles-league/2024/player/john-smith/', $html );
        $this->assertStringContainsString( 'John Smith', $html );
    }

    public function test_entry_form_clubs_list_renders_with_precalculated_club_urls(): void {
        $competition_presenter = new Competition_Shortcode_Presenter();
        $tournament_presenter  = new Tournament_Presenter();

        $club_selection = $this->createStub( Club_Entry_Selection_Service::class );
        $club_selection->method( 'available_clubs' )->willReturn( [
            (object) [
                'id'        => 10,
                'shortcode' => 'WHC',
            ],
            (object) [
                'id'        => 20,
                'shortcode' => 'LTC',
            ],
        ] );

        $competition_service = $this->createStub( Competition_Service::class );
        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Summer League',
            type: Competition_Type::LEAGUE,
            seasons: [
                '2024' => [
                    'name'         => '2024',
                    'date_start'   => '2024-05-01',
                    'date_end'     => '2024-08-31',
                    'date_closing' => '2024-04-15',
                ],
            ]
        );
        $competition_service->method( 'get_by_id' )->willReturn( $competition );

        $handler = new Get_Competition_Entry_Handler(
            club_entry_selection: $club_selection,
            competition_service: $competition_service,
            tournament_service: $this->createStub( Tournament_Service::class ),
            player_service: $this->createStub( Player_Service::class ),
            club_service: $this->createStub( Club_Service::class ),
            team_service: $this->createStub( Team_Service::class ),
            registration_service: $this->createStub( Registration_Service::class ),
            competition_entry_service: $this->createStub( Competition_Entry_Service::class ),
            finance_service: $this->createStub( Finance_Service::class ),
            competition_presenter: $competition_presenter,
            tournament_presenter: $tournament_presenter,
            event_query_service: $this->event_query_service()
        );

        $query = new Get_Competition_Entry_Query(
            competition_name: 'Summer League',
            season: '2024',
            club_name: null,
            user_id: 1,
            is_admin: true
        );

        $vm = $handler->handle( $query );
        $this->assertSame( 'entry-form-clubs-list', $vm->template );
        $this->assertArrayHasKey( 'club_urls', $vm->vars );
        $this->assertSame( 'whc/', $vm->vars['club_urls'][10] );
        $this->assertSame( 'ltc/', $vm->vars['club_urls'][20] );

        $warnings = [];
        $view_presenter = new \Racketmanager\Presentation\Presenters\Shortcode_View_Presenter( $this->template_renderer );
        $html     = $this->render_and_capture_warnings( function () use ( $view_presenter, $vm ) {
            return $view_presenter->render( $vm );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'href="whc/"', $html );
        $this->assertStringContainsString( 'href="ltc/"', $html );
        $this->assertStringContainsString( 'WHC', $html );
        $this->assertStringContainsString( 'LTC', $html );
    }

    public function test_competition_presenter_presents_club_event_player_links(): void {
        $competition_presenter = new Competition_Shortcode_Presenter();

        $event                 = new stdClass();
        $event->id             = 55;
        $event->name           = 'Summer League Men';
        $event->is_championship = false;
        $event->current_season = [
            'name'           => '2024',
            'num_match_days' => 1,
        ];
        $event->competition    = (object) [
            'type' => Competition_Type::LEAGUE,
        ];

        $player_stats = [
            (object) [
                'fullname'  => 'Alice Bob',
                'matchdays' => [],
            ],
            (object) [
                'fullname'  => 'Charlie Dave',
                'matchdays' => [],
            ],
        ];

        $links = $competition_presenter->present_club_event_player_links( $event, $player_stats, '2024' );
        $this->assertSame(
            '/leagues/summer-league-men/2024/player/alice-bob/',
            $links['Alice Bob']
        );
        $this->assertSame(
            '/leagues/summer-league-men/2024/player/charlie-dave/',
            $links['Charlie Dave']
        );
    }

    public function test_last_5_matches_template_renders_with_real_fixture_and_tooltips_with_zero_warnings(): void {
        $fixture1 = new \Racketmanager\Domain\Fixture\Fixture();
        $fixture1->id = 101;
        $fixture1->home_team = '1';
        $fixture1->away_team = '2';
        $fixture1->date = '2024-05-10';
        $fixture1->home_points = '6';
        $fixture1->away_points = '0';
        $fixture1->winner_id = 1;
        $fixture1->loser_id = 2;
        $fixture1->teams = [
            'home' => (object) [ 'title' => 'Red Team' ],
            'away' => (object) [ 'title' => 'Blue Team' ],
        ];

        $fixture2 = new \Racketmanager\Domain\Fixture\Fixture();
        $fixture2->id = 102;
        $fixture2->home_team = '2';
        $fixture2->away_team = '1';
        $fixture2->date = '2024-05-17';
        $fixture2->home_points = '0';
        $fixture2->away_points = '6';
        $fixture2->winner_id = 1;
        $fixture2->loser_id = 2;
        $fixture2->teams = [
            'home' => (object) [ 'title' => 'Blue Team' ],
            'away' => (object) [ 'title' => 'Red Team' ],
        ];

        $fixture_presenter = new \Racketmanager\Presentation\Presenters\Fixture_Presenter(
            link_service: $this->createStub( \Racketmanager\Services\Fixture\Fixture_Link_Service::class ),
            option_gateway: $this->createStub( \Racketmanager\Application\Ports\Option_Gateway_Interface::class )
        );

        $tooltip1 = $fixture_presenter->present_last_5_tooltip( $fixture1 );
        $tooltip2 = $fixture_presenter->present_last_5_tooltip( $fixture2 );

        $this->assertStringContains_or_matches( '6 - 0 - Red Team - Blue Team', $tooltip1 );
        $this->assertStringContains_or_matches( '0 - 6 - Blue Team - Red Team', $tooltip2 );

        $match_tooltips = [
            101 => $tooltip1,
            102 => $tooltip2,
        ];

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $fixture1, $fixture2, $match_tooltips ) {
            return $this->view_renderer->render_to_string( 'league/last-5', [], [
                'matches'        => [ $fixture1, $fixture2 ],
                'team_id'        => 1,
                'match_tooltips' => $match_tooltips,
            ] );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'match__status winner', $html );
        $this->assertStringContainsString( 'W', $html );
        $this->assertStringContainsString( 'title="' . esc_attr( $tooltip1 ) . '"', $html );
        $this->assertStringContainsString( 'title="' . esc_attr( $tooltip2 ) . '"', $html );
    }

    private function create_event( Competition $competition, array $props = [] ): Event {
        return new class( $competition, $props ) extends Event {
            public function __construct( Competition $competition, array $props ) {
                $this->id          = $props['id'] ?? 10;
                $this->name        = $props['name'] ?? 'Winter League';
                $this->is_box      = $props['is_box'] ?? false;
                $this->competition = $competition;
            }
        };
    }

    public function test_league_players_tennis_template_renders_with_precomputed_links_and_zero_warnings(): void {
        $competition = Competition_Test_Factory::create( 5, 'Winter League Competition', Competition_Type::LEAGUE );
        $event       = $this->create_event( $competition, [ 'id' => 10, 'name' => 'Winter League' ] );

        $player1 = new class extends Player {
            public function __construct() {
                $this->id          = 101;
                $this->firstname   = 'Alice';
                $this->surname     = 'Smith';
                $this->played      = 5;
                $this->matches_won = 4;
                $this->matches_lost= 1;
                $this->win_pct     = 80.0;
                $this->team        = (object) [
                    'id'    => 201,
                    'title' => 'St Saviours 1',
                ];
            }
            public function get_fullname(): string {
                return 'Alice Smith';
            }
        };

        $player2 = new class extends Player {
            public function __construct() {
                $this->id          = 102;
                $this->firstname   = 'Bob';
                $this->surname     = 'Jones';
                $this->played      = 0;
                $this->matches_won = 0;
                $this->matches_lost= 0;
                $this->win_pct     = 0.0;
                $this->team        = (object) [
                    'id'    => 202,
                    'title' => 'Westcliff 2',
                ];
            }
            public function get_fullname(): string {
                return 'Bob Jones';
            }
        };

        $league = new class extends League {
            public function __construct() {}
        };
        $league->id             = 50;
        $league->title          = 'Division 1';
        $league->sport          = 'tennis';
        $league->event          = $event;
        $league->current_season = [ 'name' => '2026' ];
        $league->players        = [ $player1, $player2 ];

        $player_presenter = new Player_Presenter();
        $player_links     = $player_presenter->present_league_player_links( $league, $league->players );

        $this->assertSame( '/league/division-1/2026/player/alice-smith/', $player_links[101]['player_link'] );
        $this->assertSame( '/league/division-1/2026/team/st-saviours-1/', $player_links[101]['team_link'] );
        $this->assertSame( '/league/division-1/2026/player/bob-jones/', $player_links[102]['player_link'] );
        $this->assertSame( '/league/division-1/2026/team/westcliff-2/', $player_links[102]['team_link'] );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $league, $player_links ) {
            return $this->view_renderer->render_to_string( 'league/players-tennis', [], [
                'league'       => $league,
                'player_links' => $player_links,
            ] );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'href="/league/division-1/2026/player/alice-smith/"', $html );
        $this->assertStringContainsString( 'href="/league/division-1/2026/team/st-saviours-1/"', $html );
        $this->assertStringContainsString( 'data-link="/league/division-1/2026/player/alice-smith/"', $html );
        $this->assertStringContainsString( 'data-link="/league/division-1/2026/team/st-saviours-1/"', $html );
        $this->assertStringContainsString( 'data-link-id="101"', $html );
        $this->assertStringContainsString( 'data-link-id="201"', $html );
        $this->assertStringContainsString( 'Alice Smith', $html );
        $this->assertStringContainsString( 'St Saviours 1', $html );
        $this->assertStringContainsString( 'Bob Jones', $html );
        $this->assertStringContainsString( 'Westcliff 2', $html );
        $this->assertStringContainsString( '4-1 (5)', $html );
    }

    public function test_league_players_tennis_single_player_branch_renders_with_zero_warnings(): void {
        $competition = Competition_Test_Factory::create( 5, 'Winter League Competition', Competition_Type::LEAGUE );
        $event       = $this->create_event( $competition, [ 'id' => 10, 'name' => 'Winter League' ] );

        $player = new class extends Player {
            public function __construct() {
                $this->id          = 101;
                $this->firstname   = 'Alice';
                $this->surname     = 'Smith';
                $this->display_name= 'Alice Smith';
                $this->matches     = [];
                $this->statistics  = [];
            }
            public function get_fullname(): string {
                return 'Alice Smith';
            }
        };

        $league = new class extends League {
            public function __construct() {}
        };
        $league->id             = 50;
        $league->title          = 'Division 1';
        $league->sport          = 'tennis';
        $league->event          = $event;
        $league->current_season = [ 'name' => '2026' ];
        $league->player         = $player;

        $player_presenter = new Player_Presenter();
        $player_vm        = $player_presenter->present_player( $player );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $league, $player_vm ) {
            return $this->view_renderer->render_to_string( 'league/players-tennis', [], [
                'league'    => $league,
                'player_vm' => $player_vm,
            ] );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'Alice Smith', $html );
        $this->assertStringContainsString( 'Matches', $html );
    }

    public function test_tournament_overview_template_renders_with_dates_view_model_and_zero_warnings(): void {
        $tournament = (object) [
            'id'              => 25,
            'name'            => 'Summer Tournament 2026',
            'venue_name'      => 'Leigh LTC',
            'num_events'      => 8,
            'num_entries'     => 64,
            'phase'           => 'open',
            'date_open'       => '2026-03-23',
            'date_closing'    => '2026-06-01',
            'date_withdrawal' => '2026-06-05',
            'date_start'      => '2026-06-15',
            'date_end'        => '2026-06-28',
        ];

        $presenter = new Tournament_Presenter( 'j M Y' );
        $dates_vm  = $presenter->present_dates( $tournament );

        $this->assertSame( '23 Mar 2026', $dates_vm->date_open_display );
        $this->assertSame( '1 Jun 2026', $dates_vm->date_closing_display );
        $this->assertSame( '5 Jun 2026', $dates_vm->date_withdrawal_display );
        $this->assertSame( '15 Jun 2026', $dates_vm->date_start_display );
        $this->assertSame( '28 Jun 2026', $dates_vm->date_end_display );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $tournament, $dates_vm ) {
            return $this->view_renderer->render_to_string( 'tournament/overview', [], [
                'tournament'       => $tournament,
                'tournament_dates' => $dates_vm,
            ] );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'Leigh LTC', $html );
        $this->assertStringContainsString( '8', $html );
        $this->assertStringContainsString( '64', $html );
        $this->assertStringContainsString( '23 Mar 2026', $html );
        $this->assertStringContainsString( '1 Jun 2026', $html );
        $this->assertStringContainsString( '5 Jun 2026', $html );
        $this->assertStringContainsString( '15 Jun 2026', $html );
        $this->assertStringContainsString( '28 Jun 2026', $html );
        $this->assertStringContainsString( 'is-entry-open is-current is-success', $html );
    }

    public function test_tournament_overview_template_handles_null_dates_with_fallbacks_and_zero_warnings(): void {
        $tournament = (object) [
            'id'              => 26,
            'name'            => 'TBD Tournament',
            'venue_name'      => 'TBD Venue',
            'num_events'      => 0,
            'num_entries'     => 0,
            'phase'           => 'draft',
            'date_open'       => null,
            'date_closing'    => null,
            'date_withdrawal' => null,
            'date_start'      => null,
            'date_end'        => null,
        ];

        $presenter = new Tournament_Presenter( 'j M Y' );
        $dates_vm  = $presenter->present_dates( $tournament );

        $this->assertSame( 'N/A', $dates_vm->date_open_display );
        $this->assertSame( 'N/A', $dates_vm->date_closing_display );
        $this->assertSame( 'N/A', $dates_vm->date_withdrawal_display );
        $this->assertSame( 'N/A', $dates_vm->date_start_display );
        $this->assertSame( 'TBC', $dates_vm->date_end_display );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $tournament, $dates_vm ) {
            return $this->view_renderer->render_to_string( 'tournament/overview', [], [
                'tournament'       => $tournament,
                'tournament_dates' => $dates_vm,
            ] );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'N/A', $html );
        $this->assertStringContainsString( 'TBC', $html );
    }

    public function test_get_league_players_handler_to_real_renderer_list_branch(): void {
        $competition = Competition_Test_Factory::create( 1, 'Winter League Competition', Competition_Type::LEAGUE );
        $event       = $this->create_event( $competition, [ 'id' => 10, 'name' => 'Winter League' ] );

        $team1 = (object) [ 'id' => 201, 'name' => 'St Saviours 1', 'title' => 'St Saviours 1' ];
        $player1 = new class extends Player {
            public function __construct() {
                $this->id          = 101;
                $this->firstname   = 'Alice';
                $this->surname     = 'Smith';
                $this->display_name= 'Alice Smith';
                $this->played      = 5;
                $this->matches_won = 4;
                $this->matches_lost= 1;
                $this->win_pct     = 80;
            }
            public function get_fullname(): string {
                return 'Alice Smith';
            }
        };
        $player1->team = $team1;

        $team2 = (object) [ 'id' => 202, 'name' => 'Westcliff 2', 'title' => 'Westcliff 2' ];
        $player2 = new class extends Player {
            public function __construct() {
                $this->id          = 102;
                $this->firstname   = 'Bob';
                $this->surname     = 'Jones';
                $this->display_name= 'Bob Jones';
                $this->played      = 3;
                $this->matches_won = 1;
                $this->matches_lost= 2;
                $this->win_pct     = 33;
            }
            public function get_fullname(): string {
                return 'Bob Jones';
            }
        };
        $player2->team = $team2;

        $league = new class extends League {
            public function __construct() {}
            public function get_id(): int {
                return 50;
            }
            public function set_season( false|string $season = false, bool $force_overwrite = false, ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?string $final = null ): void {
                $this->current_season = [ 'name' => $season ?: '2026' ];
            }
        };
        $league->id             = 50;
        $league->title          = 'Division 1';
        $league->sport          = 'tennis';
        $league->event          = $event;
        $league->current_season = [ 'name' => '2026' ];
        $league->players        = [ $player1, $player2 ];

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $league_repo = $this->createStub( League_Repository_Interface::class );
        $league_repo->method( 'get_player_team' )->willReturnCallback( function( $league_id, $args ) use ( $team1, $team2 ) {
            return ( 101 === (int) ( $args['player'] ?? 0 ) ) ? $team1 : $team2;
        } );
        $player_service   = $this->createStub( Player_Service::class );
        $player_presenter = new Player_Presenter();
        $query_service    = $this->createStub( Player_Query_Service::class );
        $query_service->method( 'get_matches' )->willReturn( [] );
        $stats_service = $this->createStub( Player_Stats_Service::class );
        $stats_service->method( 'get_stats' )->willReturn( [] );
        $stats_service->method( 'populate_players_stats' )->willReturnCallback( fn( $players ) => $players );

        $league_query = $this->createMock( League_Query_Service::class );
        $league_query->expects( $this->once() )->method( 'get_players' )->with( $league )->willReturn( [ $player1, $player2 ] );

        $handler = new Get_League_Players_Handler(
            $league_service,
            $league_repo,
            $player_service,
            $this->template_renderer,
            $query_service,
            $stats_service,
            $player_presenter,
            $league_query
        );
        $vm = $handler->handle( new Get_League_Players_Query( league_id: 50 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'players-tennis', $vm->template );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $vm ) {
            return $this->view_renderer->render_to_string( 'league/' . $vm->template, [], $vm->vars );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'href="/league/division-1/2026/player/alice-smith/"', $html );
        $this->assertStringContainsString( 'href="/league/division-1/2026/team/st-saviours-1/"', $html );
        $this->assertStringContainsString( 'href="/league/division-1/2026/player/bob-jones/"', $html );
        $this->assertStringContainsString( 'href="/league/division-1/2026/team/westcliff-2/"', $html );
        $this->assertStringContainsString( 'Alice Smith', $html );
        $this->assertStringContainsString( 'Bob Jones', $html );
    }

    public function test_get_league_players_handler_to_real_renderer_single_player_branch(): void {
        $competition = Competition_Test_Factory::create( 1, 'Winter League Competition', Competition_Type::LEAGUE );
        $event       = $this->create_event( $competition, [ 'id' => 10, 'name' => 'Winter League' ] );

        $player = new class extends Player {
            public function __construct() {
                $this->id          = 101;
                $this->firstname   = 'Alice';
                $this->surname     = 'Smith';
                $this->display_name= 'Alice Smith';
                $this->matches     = [];
                $this->statistics  = [];
            }
            public function get_fullname(): string {
                return 'Alice Smith';
            }
        };

        $league = new class extends League {
            public function __construct() {}
        };
        $league->id             = 50;
        $league->title          = 'Division 1';
        $league->sport          = 'tennis';
        $league->event          = $event;
        $league->current_season = [ 'name' => '2026' ];
        $league->player         = $player;

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $league_repo = $this->createStub( League_Repository_Interface::class );
        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );

        $player_presenter = new Player_Presenter();
        $query_service    = $this->createStub( Player_Query_Service::class );
        $query_service->method( 'get_matches' )->willReturn( [] );
        $stats_service = $this->createStub( Player_Stats_Service::class );
        $stats_service->method( 'get_stats' )->willReturn( [] );

        $handler = new Get_League_Players_Handler(
            $league_service,
            $league_repo,
            $player_service,
            $this->template_renderer,
            $query_service,
            $stats_service,
            $player_presenter,
            $this->league_query_service( $league_repo )
        );
        $vm      = $handler->handle( new Get_League_Players_Query( league_id: 50, player_id: '101' ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'players-tennis', $vm->template );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $vm ) {
            return $this->view_renderer->render_to_string( 'league/' . $vm->template, [], $vm->vars );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'Alice Smith', $html );
        $this->assertStringContainsString( 'Matches', $html );
    }

    public function test_get_tournament_overview_handler_to_real_renderer_success(): void {
        $overview = (object) [
            'id'              => 25,
            'name'            => 'Summer Tournament 2026',
            'venue_name'      => 'Leigh LTC',
            'num_events'      => 8,
            'num_entries'     => 64,
            'phase'           => 'open',
            'date_open'       => '2026-03-23',
            'date_closing'    => '2026-06-01',
            'date_withdrawal' => '2026-06-05',
            'date_start'      => '2026-06-15',
            'date_end'        => '2026-06-28',
        ];

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_overview' )->willReturn( $overview );

        $presenter = new Tournament_Presenter( 'j M Y' );
        $handler   = new Get_Tournament_Overview_Handler( $tournament_service, $presenter );
        $vm        = $handler->handle( new Get_Tournament_Overview_Query( tournament_id: 25 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'overview', $vm->template );
        $this->assertArrayHasKey( 'tournament_dates', $vm->vars );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $vm ) {
            return $this->view_renderer->render_to_string( 'tournament/' . $vm->template, [], $vm->vars );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'Leigh LTC', $html );
        $this->assertStringContainsString( '23 Mar 2026', $html );
        $this->assertStringContainsString( '1 Jun 2026', $html );
        $this->assertStringContainsString( '5 Jun 2026', $html );
        $this->assertStringContainsString( '15 Jun 2026', $html );
        $this->assertStringContainsString( '28 Jun 2026', $html );
    }

    public function test_get_tournament_overview_handler_to_real_renderer_zero_closing_date(): void {
        $overview = (object) [
            'id'              => 26,
            'name'            => 'Tournament T3',
            'venue_name'      => 'Westcliff Hard',
            'num_events'      => 4,
            'num_entries'     => 16,
            'phase'           => 'open',
            'date_open'       => '2026-03-23',
            'date_closing'    => '0000-00-00',
            'date_withdrawal' => '0000-00-00',
            'date_start'      => '2026-06-15',
            'date_end'        => '2026-06-28',
        ];

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournament_overview' )->willReturn( $overview );

        $presenter = new Tournament_Presenter( 'j M Y' );
        $handler   = new Get_Tournament_Overview_Handler( $tournament_service, $presenter );
        $vm        = $handler->handle( new Get_Tournament_Overview_Query( tournament_id: 26 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'N/A', $vm->vars['tournament_dates']->date_closing_display );
        $this->assertSame( 'N/A', $vm->vars['tournament_dates']->date_withdrawal_display );

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $vm ) {
            return $this->view_renderer->render_to_string( 'tournament/' . $vm->template, [], $vm->vars );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'Westcliff Hard', $html );
        $this->assertStringContainsString( '23 Mar 2026', $html );
        $this->assertStringContainsString( 'N/A', $html );
        $this->assertStringNotContainsString( '-0001', $html );
    }

    public function test_club_event_template_renders_championship_rounds_with_primary_league_and_zero_warnings(): void {
        $club = (object) [
            'id'        => 1,
            'name'      => 'Leigh LTC',
            'shortcode' => 'LLTC',
        ];

        $primary_league = (object) [
            'id'           => 10,
            'championship' => new class {
                public int $num_rounds = 2;
                public function get_finals( false|int|string $round = false ): ?array {
                    $finals = [
                        'final' => [ 'round' => 1, 'name' => 'Final' ],
                        'semi'  => [ 'round' => 2, 'name' => 'Semi Final' ],
                    ];
                    return false !== $round ? ( $finals[ $round ] ?? null ) : $finals;
                }
            },
        ];

        $event = (object) [
            'id'              => 5,
            'name'            => 'Cup Mens Singles',
            'is_championship' => true,
            'primary_league'  => 10,
            'current_season'  => [
                'name' => '2026',
            ],
            'competition'     => (object) [
                'type' => Competition_Type::CUP,
            ],
            'seasons'         => [ '2026' ],
            'get_seasons'     => fn() => [ '2026' ],
        ];

        $match_stat = (object) [
            'team_id'       => 10,
            'match_winner'  => 10,
            'match_loser'   => 20,
            'rubber_winner' => 10,
            'rubber_loser'  => 20,
            'team_title'    => 'LLTC Team 1',
            'rubber_number' => 1,
            'match_day'     => 1,
            'final_round'   => 'final',
        ];

        $player_stat = (object) [
            'fullname'  => 'Bob Builder',
            'matchdays' => [ $match_stat ],
        ];

        $warnings = [];
        $html     = $this->render_and_capture_warnings( function () use ( $club, $event, $player_stat, $primary_league ) {
            return $this->view_renderer->render_to_string( 'club/event', [], [
                'club'           => $club,
                'event'          => $event,
                'current_season' => $event->current_season,
                'player_stats'   => [ $player_stat ],
                'player_links'   => [ 'Bob Builder' => '/player/bob-builder/' ],
                'primary_league' => $primary_league,
            ] );
        }, $warnings );

        $this->assertEmpty( $warnings );
        $this->assertStringContainsString( 'Player Matches', $html );
        $this->assertStringContainsString( 'Semi Final', $html );
        $this->assertStringContainsString( 'Final', $html );
        $this->assertStringContainsString( 'Bob Builder', $html );
        $this->assertStringContainsString( '/player/bob-builder/', $html );
    }

    private function assertStringContains_or_matches( string $needle, string $haystack ): void {
        $this->assertStringContainsString( $needle, $haystack );
    }
}
