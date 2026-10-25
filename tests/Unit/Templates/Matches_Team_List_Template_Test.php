<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\View_Models\Team_Details_View_Model;
use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\Presentation\View_Models\Team_Row_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

class Matches_Team_List_Template_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;
    use Manages_Wordpress_Globals;
    use Creates_Team_Match_Row_Presenter;

    private string $template_path;
    private ?\Racketmanager\Presentation\Presenters\Team_Match_Row_Presenter $permission_presenter = null;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->template_path = dirname( __DIR__, 3 ) . '/templates/includes/matches-team-list.php';
        $GLOBALS['racketmanager'] = new class {
            public function is_user_favourite( string $type, int|string $id ): bool {
                return false;
            }
        };
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Present matches through the real row presenter, as the application boundary does before templates render.
     *
     * @return array<int|string, Team_Match_Row_View_Model|Tournament_Match_Card_View_Model>
     */
    private function rows( array $matches, array $options = [], ?bool $can_update = null, ?Fixture_Relation_Loader $loader = null ): array {
        return $this->create_team_match_row_presenter( user_can_update: $can_update, relation_loader: $loader )->present_many( $matches, $options );
    }

    public function test_renders_with_fixture_domain_objects() {
        global $racketmanager;
        $racketmanager = new class {
            public string $date_format = 'Y-m-d';
            public function is_player_in_club( array $args ): bool {
                return false;
            }
        };

        $fixture = Fixture_Test_Factory::create(
            id: 101,
            fixture_title: 'Team A vs Team B',
            link: 'https://example.test/match/101/',
            group: 'Group A',
            league_id: 5,
            start_time: '14:00',
        );
        $fixture->teams = [
            'home' => (object) [ 'id' => 1, 'title' => 'Team A', 'club' => (object) [ 'id' => 10 ] ],
            'away' => (object) [ 'id' => 2, 'title' => 'Team B', 'club' => (object) [ 'id' => 20 ] ],
        ];
        $fixture->league = (object) [
            'id'    => 5,
            'title' => 'Division 1',
            'event' => (object) [
                'id'          => 3,
                'competition' => (object) [
                    'type' => Competition_Type::LEAGUE,
                ],
            ],
        ];

        // Permission is resolved by the application service, not the entity.
        $this->assertFalse( method_exists( $fixture, 'is_update_allowed' ) );

        $matches = $this->rows( [ $fixture ] );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'match--team-match', $output );
        $this->assertStringContainsString( 'Team A', $output );
        $this->assertStringContainsString( 'Team B', $output );
        $this->assertStringContainsString( 'is-not-played', $output );
    }

    /**
     * @return array{0: \Racketmanager\Domain\Fixture\Fixture, 1: \ArrayObject}
     */
    private function build_fixture_with_permission( bool $can_update ): array {
        $calls      = new \ArrayObject();
        $permission = $this->createStub( \Racketmanager\Services\Fixture\Fixture_Permission_Service::class );
        $permission->method( 'is_update_allowed' )->willReturnCallback(
            static function ( $fixture ) use ( $calls, $can_update ) {
                $calls[] = $fixture;
                return (object) array( 'user_can_update' => $can_update );
            }
        );
        $this->permission_presenter = $this->create_team_match_row_presenter( permission_service: $permission );

        $fixture = Fixture_Test_Factory::create(
            id: 102,
            fixture_title: 'Team A vs Team B',
            link: 'https://example.test/match/102/',
            group: 'Group A',
            league_id: 5,
            start_time: '14:00',
        );
        $fixture->teams  = array(
            'home' => (object) array( 'id' => 1, 'title' => 'Team A', 'club' => (object) array( 'id' => 10 ) ),
            'away' => (object) array( 'id' => 2, 'title' => 'Team B', 'club' => (object) array( 'id' => 20 ) ),
        );
        $fixture->league = (object) array(
            'id'    => 5,
            'title' => 'Division 1',
            'event' => (object) array(
                'id'          => 3,
                'competition' => (object) array( 'type' => Competition_Type::LEAGUE ),
            ),
        );

        return array( $fixture, $calls );
    }

    public function test_authorised_user_sees_result_entry_link_via_fixture_service(): void {
        [ $fixture, $calls ] = $this->build_fixture_with_permission( true );
        $matches             = $this->permission_presenter->present_many( array( $fixture ) );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertSame( array( $fixture ), $calls->getArrayCopy() );
        $this->assertStringContainsString( 'match__button', $output );
        $this->assertStringContainsString( 'https://example.test/match/102/result/', $output );
    }

    public function test_unauthorised_user_does_not_see_result_entry_link(): void {
        [ $fixture, $calls ] = $this->build_fixture_with_permission( false );
        $matches             = $this->permission_presenter->present_many( array( $fixture ) );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertCount( 1, $calls );
        $this->assertStringNotContainsString( 'match__button', $output );
        $this->assertStringNotContainsString( '/result/', $output );
    }

    public function test_unloaded_fixture_relations_are_enriched_for_match_list_template(): void {
        global $racketmanager;

        $league = $this->createStub( League::class );
        $league->title = 'Division 1';
        $event = $this->createStub( Event::class );
        $event->competition = (object) array( 'type' => Competition_Type::LEAGUE );
        $league->event = $event;
        $league_repository = $this->createMock( League_Repository_Interface::class );
        $league_repository->expects( $this->once() )->method( 'find_by_id' )->with( 5 )->willReturn( $league );

        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Home Team' );
        $home_team->method( 'get_team_type' )->willReturn( 'T' );
        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Away Team' );
        $away_team->method( 'get_team_type' )->willReturn( 'T' );
        $resolver = $this->createStub( Fixture_Team_Resolver_Interface::class );
        $resolver->method( 'get_team_details' )->willReturnMap( array(
            array( 1, new Team_Details_DTO( $home_team ) ),
            array( 2, new Team_Details_DTO( $away_team ) ),
        ) );

        $fixture = Fixture_Test_Factory::create(
            id: 103,
            league_id: 5,
            home_team: 1,
            away_team: 2,
            fixture_title: '',
            link: 'https://example.test/match/103/',
            start_time: '14:00',
        );
        $this->assertNull( $fixture->get_league() );
        $this->assertEmpty( $fixture->get_loaded_teams() );

        // Relations are loaded at the application boundary, not inside the template.
        $loader = new Fixture_Relation_Loader( $league_repository, $resolver );

        // The row presenter enriches the unloaded fixture; the template only reads the view model.
        $matches = $this->rows( array( $fixture ), loader: $loader );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertSame( $league, $fixture->get_league() );
        $this->assertSame( 'Home Team - Away Team', $fixture->get_title() );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Home Team', $output );
        $this->assertStringContainsString( 'Away Team', $output );
    }

    public function test_fixture_presentation_adapter_renders_with_authorized_result_link_and_no_container(): void {
        global $racketmanager;
        $racketmanager = null;

        $fixture = Fixture_Test_Factory::create(
            id: 201,
            fixture_title: 'Team Alpha vs Team Beta',
            link: 'https://example.test/match/201/',
            league_id: 10,
            date: '2026-10-18',
            start_time: '18:30',
            home_team: 1,
            away_team: 2
        );

        $league = (object) [ 'id' => 10, 'title' => 'Premier League' ];
        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Team Alpha' );
        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Team Beta' );

        $adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            league: $league,
            home_team_dtls: new Team_Details_DTO( $home_team ),
            away_team_dtls: new Team_Details_DTO( $away_team ),
            link: 'https://example.test/match/201/',
            is_update_allowed: (object) [ 'user_can_update' => true ]
        );

        $matches = $this->rows( [ $adapter ] );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Team Alpha', $output );
        $this->assertStringContainsString( 'Team Beta', $output );
        $this->assertStringContainsString( 'match__button', $output );
        $this->assertStringContainsString( 'https://example.test/match/201/result/', $output );
    }

    public function test_fixture_presentation_adapter_renders_unauthorized_with_no_container(): void {
        global $racketmanager;
        $racketmanager = null;

        $fixture = Fixture_Test_Factory::create(
            id: 202,
            fixture_title: 'Team Alpha vs Team Beta',
            link: 'https://example.test/match/202/',
            league_id: 10,
            date: '2026-10-18',
            start_time: '18:30',
            home_team: 1,
            away_team: 2
        );

        $league = (object) [ 'id' => 10, 'title' => 'Premier League' ];
        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Team Alpha' );
        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Team Beta' );

        $adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            league: $league,
            home_team_dtls: new Team_Details_DTO( $home_team ),
            away_team_dtls: new Team_Details_DTO( $away_team ),
            link: 'https://example.test/match/202/',
            is_update_allowed: (object) [ 'user_can_update' => false ]
        );

        $matches = $this->rows( [ $adapter ] );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Team Alpha', $output );
        $this->assertStringContainsString( 'Team Beta', $output );
        $this->assertStringNotContainsString( 'match__button', $output );
        $this->assertStringNotContainsString( '/result/', $output );
    }

    public function test_matches_team_list_group_renders_without_global_racketmanager(): void {
        global $racketmanager;
        $racketmanager = null;

        $fixture = Fixture_Test_Factory::create(
            id: 203,
            link: 'https://example.test/match/203/',
            date: '2026-10-18',
            start_time: '19:00',
            home_team: 1,
            away_team: 2
        );
        $home_team = $this->createStub( Team::class );
        $home_team->method( 'get_name' )->willReturn( 'Home' );
        $away_team = $this->createStub( Team::class );
        $away_team->method( 'get_name' )->willReturn( 'Away' );

        $adapter = new Fixture_Presentation_Adapter(
            fixture: $fixture,
            league: (object) [ 'id' => 10, 'title' => 'Division 1' ],
            home_team_dtls: new Team_Details_DTO( $home_team ),
            away_team_dtls: new Team_Details_DTO( $away_team ),
            link: 'https://example.test/match/203/'
        );

        $matches_list = [
            '2026-10-18' => $this->create_team_match_row_presenter()->present_group( '2026-10-18', [ $adapter ] ),
        ];

        ob_start();
        include dirname( $this->template_path ) . '/matches-team-list-group.php';
        $output = ob_get_clean();

        $this->assertStringContainsString( 'module-divider', $output );
        $this->assertStringContainsString( '2026-10-18', $output );
    }

    public function test_team_include_template_renders_cleanly(): void {
        $team = (object) [
            'id' => 50,
            'title' => 'Leigh 1',
            'info' => (object) [
                'captain' => 'John Doe',
                'match_day' => 'Monday',
                'match_time' => '19:00:00',
            ],
        ];
        $league = (object) [
            'id' => 1,
            'is_championship' => false,
            'current_season' => [ 'name' => '2026' ],
            'event' => (object) [
                'id' => 2,
                'competition' => (object) [
                    'is_team_entry' => true,
                    'is_player_entry' => false,
                ],
            ],
        ];
        $item_link = 'https://example.test/leagues/division-1/2026';

        $presenter = new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );
        $team = $presenter->present_team_row( $team, $league, $item_link );

        ob_start();
        include dirname( $this->template_path ) . '/team.php';
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Leigh 1', $output );
        $this->assertStringContainsString( 'John Doe', $output );
        $this->assertStringContainsString( 'Monday', $output );
        $this->assertStringContainsString( '19:00', $output );
    }

    public function test_team_include_renders_via_team_row_view_model(): void {
        $row_vm = new Team_Row_View_Model(
            id: 42,
            title: 'Westcliff 1',
            initials: 'W1',
            link: '/leagues/division-1/2026/team/westcliff-1/',
            data_attrs: 'class="nav--link tabDataLink"',
            event_id: 10,
            suffix: '-10-42',
            calendar_url: 'https://example.test/calendar.ics',
            is_team_entry: true,
            is_player_entry: false,
            captain_name: 'Jane Captain',
            captain_contactno: '0123456789',
            captain_contactemail: 'captain@example.test',
            match_day: 'Tuesday',
            match_time: '19:30:00',
            match_time_formatted: '19:30'
        );

        $team = $row_vm;
        ob_start();
        include dirname( $this->template_path, 2 ) . '/includes/team.php';
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Westcliff 1', $output );
        $this->assertStringContainsString( 'W1', $output );
        $this->assertStringContainsString( '/leagues/division-1/2026/team/westcliff-1/', $output );
        $this->assertStringContainsString( 'Jane Captain', $output );
        $this->assertStringContainsString( 'Tuesday', $output );
        $this->assertStringContainsString( '19:30', $output );
    }

    public function test_team_details_template_renders_cleanly(): void {
        $team = $this->createStub( Team::class );
        $team->method( 'get_id' )->willReturn( 12 );
        $team->method( 'get_name' )->willReturn( 'Westcliff 1' );
        $team->matches = [];
        $team->players = [];
        $team->info = (object) [
            'captain' => 'Jane Smith',
            'match_day' => 'Tuesday',
            'match_time' => '19:30:00',
        ];

        $club = $this->createStub( Club::class );
        $club->method( 'get_id' )->willReturn( 8 );
        $club->method( 'get_name' )->willReturn( 'Westcliff Lawn Tennis Club' );
        $club->shortcode = 'WLTC';
        $club->name = 'Westcliff Lawn Tennis Club';
        $club->address = 'The Ridgeway, Westcliff';
        $club->website = 'https://westclifftennis.org.uk';

        $team_details = (object) [
            'team' => $team,
            'club' => $club,
            'match_secretary' => (object) [
                'id' => 99,
                'display_name' => 'Secretary Person',
                'contactno' => '07123456789',
            ],
        ];

        $object = (object) [
            'id' => 1,
            'title' => 'Division 1',
            'name' => 'Division 1',
            'is_championship' => false,
            'current_season' => [ 'name' => '2026' ],
            'team' => $team,
            'event' => (object) [
                'id' => 5,
                'name' => 'Men\'s Doubles Event',
                'competition' => (object) [
                    'type' => Competition_Type::LEAGUE,
                    'is_team_entry' => true,
                    'is_player_entry' => false,
                ],
            ],
        ];

        $vm = new Team_Details_View_Model(
            show_wtn: true,
            format: 'S',
            help_text: 'Singles World Tennis Number',
            standings_link: '/league/division-1/2026/',
            club_link: '/clubs/wltc/',
            player_links: [],
            match_time_formatted: '19:30'
        );

        ob_start();
        $league = (object) []; // Needs to be present if used by template
        include dirname( $this->template_path, 2 ) . '/team-details.php';
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Westcliff 1', $output );
        $this->assertStringContainsString( 'Westcliff Lawn Tennis Club', $output );
        $this->assertStringContainsString( 'Jane Smith', $output );
        $this->assertStringContainsString( 'Tuesday', $output );
        $this->assertStringContainsString( '19:30', $output );
    }

    public function test_get_league_teams_handler_renders_team_details_with_wtn_and_precomputed_links(): void {
        $GLOBALS['racketmanager'] = new class {
            public function is_user_favourite( string $type, int|string $id ): bool {
                return false;
            }
        };

        $team = $this->createStub( Team::class );
        $team->id = 12;
        $team->title = 'Westcliff 1';
        $team->method( 'get_id' )->willReturn( 12 );
        $team->method( 'get_name' )->willReturn( 'Westcliff 1' );
        $team->matches = [];
        $team->info = (object) [
            'captain' => 'Jane Smith',
            'match_day' => 'Tuesday',
            'match_time' => '19:30:00',
        ];

        $player = $this->createStub( Player::class );
        $player->id = 55;
        $player->method( 'get_id' )->willReturn( 55 );
        $player->method( 'get_fullname' )->willReturn( 'Alice Walker' );
        $player->wtn = [ 'D' => '24.5', 'S' => '22.1' ];
        $player->played = 4;
        $player->matches_won = 3;
        $player->matches_lost = 1;
        $player->win_pct = 75;

        $team->players = [ $player ];

        $club = $this->createStub( Club::class );
        $club->shortcode = 'WLTC';
        $club->name = 'Westcliff Lawn Tennis Club';
        $club->address = 'The Ridgeway, Westcliff';
        $club->website = 'https://westclifftennis.org.uk';

        $team_details = new Team_Details_DTO(
            team: $team,
            club: $club,
            match_secretary: null,
            is_withdrawn: false
        );

        $league = $this->createStub( League::class );
        $league->id = 1;
        $league->title = 'Division 1';
        $league->name = 'Division 1';
        $league->is_championship = false;
        $league->current_season = [ 'name' => '2026' ];

        $event = $this->createStub( Event::class );
        $event->id = 5;
        $event->name = 'Men\'s Doubles';
        $event->type = 'MD';
        $event->competition = (object) [
            'type' => Competition_Type::LEAGUE,
            'is_team_entry' => true,
            'is_player_entry' => false,
        ];
        $league->event = $event;
        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_team_dtls' )->willReturn( $team->info );
        $league_query->method( 'get_league_team' )->willReturn( (object) [
            'rank' => '1',
            'done_matches' => 5,
            'points_formatted' => [ 'primary' => '10' ],
        ] );
        $league_query->method( 'get_matches' )->willReturn( [] );
        $league_query->method( 'get_players' )->willReturn( [ $player ] );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturn( $team_details );

        $player_stats_service = $this->createStub( Player_Stats_Service::class );
        $player_stats_service->method( 'populate_players_stats' )->willReturn( [ $player ] );

        $tournament_presenter = $this->createStub( Tournament_Presenter::class );
        $tournament_presenter->method( 'present_match_list' )->willReturn( [] );

        $settings_service = new Settings_Service( [
            'display' => [ 'wtn' => 1 ],
        ] );

        $team_details_presenter = new Team_Details_Presenter( new Wp_Option_Gateway(), $settings_service );

        $handler = new Get_League_Teams_Handler(
            league_service: $league_service,
            team_service: $team_service,
            player_stats_service: $player_stats_service,
            tournament_presenter: $tournament_presenter,
            team_details_presenter: $team_details_presenter,
            league_query_service: $league_query
        );

        $result = $handler->handle( new Get_League_Teams_Query( league_id: 1, team_id: '12' ) );
        $this->assertEquals( 'team-details', $result->template );
        $this->assertTrue( $result->vars['vm']->show_wtn );
        $this->assertEquals( 'D', $result->vars['vm']->format );
        $this->assertEquals( '19:30', $result->vars['vm']->match_time_formatted );
        $this->assertEquals( '/clubs/wltc/', $result->vars['vm']->club_link );
        $this->assertEquals( '/league/division-1/2026/', $result->vars['vm']->standings_link );
        $this->assertEquals( '/league/division-1/2026/player/alice-walker/', $result->vars['vm']->player_links[55] );

        $warnings = [];
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            $renderer = new Php_View_Renderer( dirname( $this->template_path, 2 ) . '/' );
            $output   = $renderer->render_to_string( 'team-details', $result->vars );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'Westcliff 1', $output );
        $this->assertStringContainsString( 'Alice Walker', $output );
        $this->assertStringContainsString( '24.5', $output );
        $this->assertStringContainsString( 'Doubles World Tennis Number', $output );
        $this->assertStringContainsString( '19:30', $output );
        $this->assertStringContainsString( '/clubs/wltc/', $output );
        $this->assertStringContainsString( 'data-link="/league/division-1/2026/"', $output );
        $this->assertStringContainsString( '/league/division-1/2026/player/alice-walker/', $output );
    }

    public function test_get_league_teams_handler_renders_team_details_without_wtn_when_disabled(): void {
        $GLOBALS['racketmanager'] = new class {
            public function is_user_favourite( string $type, int|string $id ): bool {
                return false;
            }
        };

        $team = $this->createStub( Team::class );
        $team->id = 12;
        $team->title = 'Westcliff 1';
        $team->method( 'get_id' )->willReturn( 12 );
        $team->method( 'get_name' )->willReturn( 'Westcliff 1' );
        $team->matches = [];
        $team->info = (object) [
            'captain' => 'Jane Smith',
            'match_day' => 'Tuesday',
            'match_time' => '19:30:00',
        ];

        $player = $this->createStub( Player::class );
        $player->id = 55;
        $player->method( 'get_id' )->willReturn( 55 );
        $player->method( 'get_fullname' )->willReturn( 'Alice Walker' );
        $player->wtn = [ 'D' => '24.5', 'S' => '22.1' ];
        $player->played = 4;
        $player->matches_won = 3;
        $player->matches_lost = 1;
        $player->win_pct = 75;

        $team->players = [ $player ];

        $club = $this->createStub( Club::class );
        $club->shortcode = 'WLTC';
        $club->name = 'Westcliff Lawn Tennis Club';
        $club->address = 'The Ridgeway, Westcliff';
        $club->website = 'https://westclifftennis.org.uk';

        $team_details = new Team_Details_DTO(
            team: $team,
            club: $club,
            match_secretary: null,
            is_withdrawn: false
        );

        $league = $this->createStub( League::class );
        $league->id = 1;
        $league->title = 'Division 1';
        $league->name = 'Division 1';
        $league->is_championship = false;
        $league->current_season = [ 'name' => '2026' ];

        $event = $this->createStub( Event::class );
        $event->id = 5;
        $event->name = 'Men\'s Doubles';
        $event->type = 'MD';
        $event->competition = (object) [
            'type' => Competition_Type::LEAGUE,
            'is_team_entry' => true,
            'is_player_entry' => false,
        ];
        $league->event = $event;
        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_team_dtls' )->willReturn( $team->info );
        $league_query->method( 'get_league_team' )->willReturn( (object) [
            'rank' => '1',
            'done_matches' => 5,
            'points_formatted' => [ 'primary' => '10' ],
        ] );
        $league_query->method( 'get_matches' )->willReturn( [] );
        $league_query->method( 'get_players' )->willReturn( [ $player ] );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturn( $team_details );

        $player_stats_service = $this->createStub( Player_Stats_Service::class );
        $player_stats_service->method( 'populate_players_stats' )->willReturn( [ $player ] );

        $tournament_presenter = $this->createStub( Tournament_Presenter::class );
        $tournament_presenter->method( 'present_match_list' )->willReturn( [] );

        $settings_service = new Settings_Service( [
            'display' => [ 'wtn' => 0 ],
        ] );

        $team_details_presenter = new Team_Details_Presenter( new Wp_Option_Gateway(), $settings_service );

        $handler = new Get_League_Teams_Handler(
            league_service: $league_service,
            team_service: $team_service,
            player_stats_service: $player_stats_service,
            tournament_presenter: $tournament_presenter,
            team_details_presenter: $team_details_presenter,
            league_query_service: $league_query
        );

        $result = $handler->handle( new Get_League_Teams_Query( league_id: 1, team_id: '12' ) );
        $this->assertEquals( 'team-details', $result->template );
        $this->assertFalse( $result->vars['vm']->show_wtn );
        $this->assertNull( $result->vars['vm']->format );
        $this->assertNull( $result->vars['vm']->help_text );

        $warnings = [];
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            $renderer = new Php_View_Renderer( dirname( $this->template_path, 2 ) . '/' );
            $output   = $renderer->render_to_string( 'team-details', $result->vars );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'Westcliff 1', $output );
        $this->assertStringNotContainsString( 'Doubles World Tennis Number', $output );
    }

    public function test_renders_team_match_row_view_model_directly(): void {
        $vm = new Team_Match_Row_View_Model(
            id: 999,
            is_selected: true,
            show_header: true,
            league_title: 'Premier Division',
            match_link: 'https://example.test/match/999/',
            final_name: 'Cup Final',
            match_day: 1,
            match_day_text: 'Match Day 1',
            leg: 2,
            leg_text: 'Leg 2',
            show_date: true,
            date: '2026-10-18',
            formatted_date: '18 Oct 2026',
            status_message: 'Postponed',
            is_highlighted: true,
            status_class: 'winner',
            status_text: 'W',
            is_pending: false,
            score_class: '',
            score_text: null,
            home_name: 'Leigh CC 1',
            home_is_withdrawn: false,
            home_withdrawn_title: null,
            home_points: '5',
            away_name: 'Westcliff 2',
            away_is_withdrawn: true,
            away_withdrawn_title: 'Westcliff 2 has withdrawn',
            away_points: '1',
            user_can_update: true,
            update_link: 'https://example.test/match/999/result/',
            pencil_icon_url: 'https://example.test/pencil.svg',
            is_tournament: false
        );

        $matches = [ $vm ];

        $warnings = [];
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            ob_start();
            include $this->template_path;
            $output = ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'is-selected', $output );
        $this->assertStringContainsString( 'Premier Division', $output );
        $this->assertStringContainsString( 'Cup Final', $output );
        $this->assertStringContainsString( 'Leg 2', $output );
        $this->assertStringContainsString( '18 Oct 2026', $output );
        $this->assertStringContainsString( 'Postponed', $output );
        $this->assertStringContainsString( 'winner', $output );
        $this->assertStringContainsString( 'Leigh CC 1', $output );
        $this->assertStringContainsString( 'Westcliff 2 has withdrawn', $output );
        $this->assertStringContainsString( 'https://example.test/match/999/result/', $output );
    }

    public function test_renders_team_matches_group_view_model(): void {
        $vm = new Team_Match_Row_View_Model(
            id: 1001,
            show_header: false,
            home_name: 'Team Red',
            away_name: 'Team Blue',
            is_pending: true,
            score_class: 'is-not-played',
            score_text: '19:30'
        );

        $group = new Team_Matches_Group_View_Model(
            key: 'round-1',
            header: 'Round 1 Fixtures',
            matches: [ $vm ]
        );

        $matches_list = [ $group ];

        $warnings = [];
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            ob_start();
            include dirname( $this->template_path ) . '/matches-team-list-group.php';
            $output = ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'Round 1 Fixtures', $output );
        $this->assertStringContainsString( 'Team Red', $output );
        $this->assertStringContainsString( 'Team Blue', $output );
        $this->assertStringContainsString( '19:30', $output );
    }

    public function test_renders_club_matches_with_winner_loser_tie_badges(): void {
        $fixture_win = Fixture_Test_Factory::create(
            id: 301,
            link: 'https://example.test/match/301/',
            home_team: 10,
            away_team: 20,
            winner_id: 10,
            loser_id: 20,
            home_points: '4',
            away_points: '2'
        );
        $fixture_win->teams = [
            'home' => (object) [ 'id' => 10, 'title' => 'Club Team A', 'club' => (object) [ 'id' => 100 ] ],
            'away' => (object) [ 'id' => 20, 'title' => 'Club Team B', 'club' => (object) [ 'id' => 200 ] ],
        ];

        $fixture_loss = Fixture_Test_Factory::create(
            id: 302,
            link: 'https://example.test/match/302/',
            home_team: 10,
            away_team: 20,
            winner_id: 20,
            loser_id: 10,
            home_points: '1',
            away_points: '5'
        );
        $fixture_loss->teams = [
            'home' => (object) [ 'id' => 10, 'title' => 'Club Team A', 'club' => (object) [ 'id' => 100 ] ],
            'away' => (object) [ 'id' => 20, 'title' => 'Club Team B', 'club' => (object) [ 'id' => 200 ] ],
        ];

        $fixture_tie = Fixture_Test_Factory::create(
            id: 303,
            link: 'https://example.test/match/303/',
            home_team: 10,
            away_team: 20,
            winner_id: null,
            loser_id: -1,
            home_points: '3',
            away_points: '3'
        );
        $fixture_tie->teams = [
            'home' => (object) [ 'id' => 10, 'title' => 'Club Team A', 'club' => (object) [ 'id' => 100 ] ],
            'away' => (object) [ 'id' => 20, 'title' => 'Club Team B', 'club' => (object) [ 'id' => 200 ] ],
        ];

        // Render with current_club = 100
        $current_club = 100;
        $matches      = $this->rows( [ $fixture_win, $fixture_loss, $fixture_tie ], [ 'current_club' => $current_club ] );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'match__status winner', $output );
        $this->assertStringContainsString( 'match__status loser', $output );
        $this->assertStringContainsString( 'match__status tie', $output );
    }

    public function test_renders_match_option_modal_with_dto_and_row_vm(): void {
        $fixture = Fixture_Test_Factory::create(
            id: 401,
            link: 'https://example.test/match/401/',
            home_team: 10,
            away_team: 20,
            date: '2026-10-18',
            start_time: '19:00'
        );
        $league = $this->createStub( League::class );
        $league->id = 1;
        $league->title = 'Division 1';
        $league->method( 'get_name' )->willReturn( 'Division 1' );
        $event  = $this->createStub( Event::class );
        $event->id = 2;
        $event->name = 'Men Singles';
        $comp   = $this->createStub( Competition::class );
        $comp->method( 'get_type' )->willReturn( 'league' );

        $dto = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $comp
        );

        $row_vm = $this->create_team_match_row_presenter()->present( $dto, [ 'show_header' => true ] );

        $modal  = 'test-modal';
        $title  = '(Re)schedule fixture';
        $option = 'schedule_match';
        $action = 'setMatchDate';
        $button = 'Save Date';
        $match  = $fixture;
        $vm     = $row_vm;

        $warnings = [];
        set_error_handler( function ( $errno, $errstr, $errfile, $errline ) use ( &$warnings ) {
            $warnings[] = "$errstr on $errfile:$errline";
            return true;
        } );

        try {
            ob_start();
            include dirname( $this->template_path, 2 ) . '/match/match-option-modal.php';
            $output = ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( '(Re)schedule fixture', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Save Date', $output );
        $this->assertStringContainsString( 'data-action="set-match-date"', $output );
    }
}
