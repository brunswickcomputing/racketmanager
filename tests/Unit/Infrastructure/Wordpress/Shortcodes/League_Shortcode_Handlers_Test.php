<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Application\League\Queries\Get_League_Archive_Handler;
use Racketmanager\Application\League\Queries\Get_League_Archive_Query;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Query;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Handler;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Query;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Query;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Query;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Query;
use Racketmanager\Application\League\Queries\Get_League_Players_Handler;
use Racketmanager\Application\League\Queries\Get_League_Players_Query;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Season_Dropdown_Query;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\League_Archive_Presenter;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\League_Standings_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
use Racketmanager\Tests\Helpers\Creates_League_Query_Service;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

class League_Shortcode_Handlers_Test extends TestCase {
    use Creates_Event_Query_Service;
    use Creates_League_Query_Service;
    use Creates_Test_Container;
    use Creates_Team_Match_Row_Presenter;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        global $wp;
        $wp = (object) [ 'query_vars' => [] ];
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_renderer(): Shortcode_Template_Renderer {
        $php_renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        return new Shortcode_Template_Renderer( $php_renderer );
    }

    public function test_championship_handler_success_and_error(): void {
        $league_service = $this->createStub( League_Service::class );
        $fixture_repo   = $this->createStub( Fixture_Repository_Interface::class );
        $comp_service   = $this->createStub( Competition_Service::class );
        $team_service   = $this->createStub( Team_Service::class );
        $link_service   = $this->createStub( Fixture_Link_Service::class );
        $perm_service   = $this->createStub( Fixture_Permission_Service::class );
        $renderer       = $this->create_renderer();

        $handler = new Get_League_Championship_Handler(
            $league_service,
            $fixture_repo,
            $comp_service,
            $team_service,
            $link_service,
            $perm_service,
            $renderer,
            $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class ),
            new Tournament_Presenter( 'd/m/Y' ),
            $this->league_query_service()
        );

        // Error path: league not found
        $query_error = new Get_League_Championship_Query( league_id: 999 );
        $vm_error    = $handler->handle( $query_error );
        $this->assertTrue( $vm_error->is_error() );
        $this->assertSame( 'League not found', $vm_error->error_message );

        // Success path
        $league_obj = new class extends League {
            public function __construct() {
                $this->id    = 1;
                $this->title = 'Test Championship League';
                $this->sport = 'tennis';
            }
            public function get_season(): string { return '2026'; }
            public function set_template( string $type, string $template ): void {}
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        $query = new Get_League_Championship_Query( league_id: 1, template: 'custom' );
        $vm    = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'championship-custom', $vm->template );
        $this->assertFalse( $vm->template_type );
    }

    public function test_championship_draw_attaches_rubbers_to_plain_fixture(): void {
        $fixture = \Racketmanager\Domain\Fixture\Fixture_Factory::from_array( [ 'id' => 78, 'league_id' => 4, 'home_team' => '10', 'away_team' => '20' ] );
        $rubber = ( new \ReflectionClass( \Racketmanager\Domain\Fixture\Rubber::class ) )->newInstanceWithoutConstructor();
        $rubber->id = 91;

        $championship = $this->createStub( \Racketmanager\Domain\Tournament\Championship::class );
        $championship->method( '__get' )->willReturnMap( [
            [ 'num_teams_first_round', 2 ]
        ] );
        $championship->method( 'get_finals' )->willReturn( [ [ 'key' => 'final', 'name' => 'Final', 'num_matches' => 1 ] ] );
        $league = new class extends League {
            public function __construct() {
                $this->id = 4;
                $this->seasons = array( '2026' => array( 'name' => '2026' ) );
                $this->current_season = array( 'name' => '2026' );
                $this->set_match_query_arg( 'season', '2026' );
                $this->event_id = 0;
                $this->num_rubbers = 1;
                $this->num_sets = 3;
            }
            public function get_season(): string { return '2026'; }
            public function set_template( string $type, string $template ): void {}
        };
        $league->championship = $championship;

        $leagues = $this->createStub( League_Service::class );
        $leagues->method( 'get_league' )->willReturn( $league );
        $fixtures = $this->createMock( Fixture_Repository_Interface::class );
        $fixtures->expects( $this->once() )->method( 'find_by_id' )->with( 78 )->willReturn( $fixture );
        $rubbers = $this->createMock( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class );
        $rubbers->expects( $this->once() )->method( 'find_by_fixture_id' )->with( 78 )->willReturn( [ $rubber ] );
        $query_league_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $query_league_repository->method( 'query_matches' )->willReturn( [ (object) [ 'id' => 78 ] ] );
        $query_fixtures = $this->createStub( Fixture_Repository_Interface::class );
        $query_fixtures->method( 'find_by_id' )->willReturn( $fixture );
        $competition = $this->createStub( Competition_Service::class );
        $league_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $league_repository->method( 'find_by_id' )->willReturn( $league );
        $competition->method( 'get_league_repository' )->willReturn( $league_repository );
        $teams = $this->createStub( Team_Service::class );
        $teams->method( 'get_team_details' )->willReturnCallback( static function ( int $id ) {
            return new \Racketmanager\Domain\DTO\Team\Team_Details_DTO( Team::from_object( (object) [ 'id' => $id, 'title' => 'Team ' . $id ] ) );
        } );

        $handler = new Get_League_Championship_Handler(
            $leagues, $fixtures, $competition, $teams,
            $this->createStub( Fixture_Link_Service::class ), $this->createStub( Fixture_Permission_Service::class ),
            $this->create_renderer(), $rubbers,
            new Tournament_Presenter( 'd/m/Y', team_match_row_presenter: $this->create_team_match_row_presenter() ),
            $this->league_query_service( $query_league_repository, null, $query_fixtures )
        );

        $view = $handler->handle( new Get_League_Championship_Query( league_id: 4, template: 'custom' ) );
        $row = $view->vars['finals'][0]->matches[1];
        $this->assertInstanceOf( \Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model::class, $row );
        $this->assertFalse( $row->show_header );
        $this->assertSame( $rubber, $row->match->rubbers[1] );

        $league = $view->vars['league'];
        $finals = $view->vars['finals'];
        ob_start();
        require RACKETMANAGER_PATH . 'templates/championship-matches.php';
        $html = (string) ob_get_clean();
        $this->assertStringNotContainsString( 'match__header--up', $html );
    }

    public function test_archive_handler_success_and_by_name(): void {
        $league_service = $this->createStub( League_Service::class );
        $comp_service   = $this->createStub( Competition_Service::class );
        $renderer       = $this->create_renderer();

        $handler = new Get_League_Archive_Handler( $league_service, $comp_service, $renderer, new League_Archive_Presenter(), $this->event_query_service() );

        // Error path: league not found
        $query_error = new Get_League_Archive_Query( league_name: 'Nonexistent League' );
        $vm_error    = $handler->handle( $query_error );
        $this->assertTrue( $vm_error->is_error() );
        $this->assertSame( 'League not found', $vm_error->error_message );

        // Success path: league by name
        $league_obj = new class extends League {
            public function __construct() {
                $this->id       = 10;
                $this->title    = 'Premier Division';
                $this->sport    = 'tennis';
                $this->event_id = 5;
            }
        };
        $event_obj = new class extends Event {
            public string $title = 'Main Event';
            public function __construct() {
                $this->id   = 5;
                $this->name = 'Main Event';
            }
            public function get_seasons(): array { return [ '2025', '2026' ]; }
        };

        $league_service->method( 'get_league' )->willReturn( $league_obj );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_obj );

        $query = new Get_League_Archive_Query( league_name: 'Premier Division' );
        $vm    = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'archive', $vm->template );
        $this->assertFalse( $vm->template_type );
        $this->assertSame( $league_obj, $vm->vars['league'] );
        $this->assertSame( 'standings', $vm->vars['tab'] );
        $this->assertInstanceOf( \Racketmanager\Presentation\View_Models\League_Archive_View_Model::class, $vm->vars['archive_vm'] );
        $this->assertSame( 'Premier Division', $vm->vars['archive_vm']->league_title );
        $this->assertSame( 'premier-division', $vm->vars['archive_vm']->league_seo_name );
        $this->assertSame( 'last5', $league_obj->templates['standings'] );
        $this->assertSame( 'list', $league_obj->templates['teams'] );
        $this->assertTrue( $league_obj->is_archive );
    }

    public function test_archive_query_from_atts_query_var_resolution(): void {
        $_GET['tab'] = 'standings';

        // match_day 0 converts to -1 and tab matches
        $q1 = Get_League_Archive_Query::from_atts( [], [ 'match_day' => '0' ], 'Default League' );
        $this->assertSame( '-1', $q1->match_day );
        $this->assertSame( 'matches', $q1->tab );
        $this->assertSame( 'Default League', $q1->league_name );

        // team_id routes to matches
        $q2 = Get_League_Archive_Query::from_atts( [], [ 'team_id' => '12' ] );
        $this->assertSame( 'matches', $q2->tab );

        // player_id routes to players even when leftover $_GET['tab'] is set
        $q3 = Get_League_Archive_Query::from_atts( [], [ 'player_id' => '34' ] );
        $this->assertSame( 'players', $q3->tab );

        // team routes to teams
        $q4 = Get_League_Archive_Query::from_atts( [], [ 'team' => 'team-slug' ] );
        $this->assertSame( 'teams', $q4->tab );

        // explicit tab takes precedence unless match_day / team_id
        $q5 = Get_League_Archive_Query::from_atts( [ 'tab' => 'crosstable' ] );
        $this->assertSame( 'crosstable', $q5->tab );

        // injected get_params are used when query vars do not supply a tab
        $q6 = Get_League_Archive_Query::from_atts( [], [], null, [ 'tab' => 'players' ] );
        $this->assertSame( 'players', $q6->tab );

        unset( $_GET['tab'] );
    }

    public function test_teams_handler_list_and_details_with_rank_guard(): void {
        $league_service = $this->createStub( League_Service::class );
        $team_service   = $this->createStub( Team_Service::class );

        $team_presenter = new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );
        $stub_fixtures  = new \ArrayObject();
        $query_league_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $query_league_repository->method( 'query_matches' )->willReturnCallback( static fn(): array => array_map( static fn( $fixture ) => (object) [ 'id' => $fixture->id ], $stub_fixtures->getArrayCopy() ) );
        $query_fixtures = $this->createStub( Fixture_Repository_Interface::class );
        $query_fixtures->method( 'find_by_id' )->willReturnCallback( static function ( int $id ) use ( $stub_fixtures ) {
            foreach ( $stub_fixtures as $fixture ) {
                if ( $fixture->id === $id ) {
                    return $fixture;
                }
            }
            return null;
        } );
        $handler = new Get_League_Teams_Handler(
            $league_service,
            $team_service,
            $this->createStub( Player_Stats_Service::class ),
            new Tournament_Presenter( team_match_row_presenter: $this->create_team_match_row_presenter() ),
            $team_presenter,
            $this->league_query_service( $query_league_repository, null, $query_fixtures )
        );

        // Error: league not found
        $query_error = new Get_League_Teams_Query( league_id: 999 );
        $vm_error    = $handler->handle( $query_error );
        $this->assertTrue( $vm_error->is_error() );
        $this->assertSame( 'League not found', $vm_error->error_message );

        $league_obj = new class extends League {
            public function __construct() {
                $this->id    = 1;
                $this->title = 'Test League';
                $this->seasons        = array( '2026' => array( 'name' => '2026' ) );
                $this->current_season = array( 'name' => '2026' );
                $this->set_match_query_arg( 'season', '2026' );
            }
            public function set_template( string $type, string $template ): void {}
            public function set_group( string $group = '', bool $force_overwrite = false ): void {}
        };
        $fixture         = Fixture_Test_Factory::create( id: 901, league_id: 1, home_team: 7, away_team: 8 );
        $fixture->teams  = [
            'home' => (object) [ 'id' => 7, 'title' => 'Westcliff 1', 'club' => (object) [ 'id' => 11 ] ],
            'away' => (object) [ 'id' => 8, 'title' => 'Leigh 1', 'club' => (object) [ 'id' => 12 ] ],
        ];
        $fixture->league = (object) [ 'id' => 1, 'title' => 'Test League' ];
        $stub_fixtures->exchangeArray( [ $fixture ] );
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        // Teams list
        $query_list = new Get_League_Teams_Query( league_id: 1 );
        $vm_list    = $handler->handle( $query_list );
        $this->assertFalse( $vm_list->is_error() );
        $this->assertSame( 'teams-list', $vm_list->template );
        $this->assertFalse( $vm_list->template_type );
        $league_obj->teams      = [ (object) [ 'rank' => 1 ], (object) [ 'rank' => '' ] ];
        $league_obj->team_index = [ 7 => 0, 8 => 1 ];

        // Team details success
        $team_obj        = new class extends Team { public function __construct() { $this->id = 7; $this->title = 'Westcliff 1'; } };
        $team_details    = new Team_Details_DTO( team: $team_obj );
        $team_service->method( 'get_team_details' )->willReturnCallback(
            function( int|string|null $team_id ) use ( $team_details ) {
                if ( 8 === (int) $team_id ) {
                    $unranked_team = new class extends Team { public function __construct() { $this->id = 8; $this->title = 'Unranked Team'; } };
                    return new Team_Details_DTO( team: $unranked_team );
                }
                return $team_details;
            }
        );

        $query_detail = new Get_League_Teams_Query( league_id: 1, team_id: '7' );
        $vm_detail    = $handler->handle( $query_detail );
        $this->assertFalse( $vm_detail->is_error() );
        $this->assertSame( 'team-details', $vm_detail->template );
        $this->assertFalse( $vm_detail->template_type );
        $this->assertSame( $team_details, $vm_detail->vars['team_details'] );
        $row = $league_obj->team->matches[0];
        $this->assertFalse( $row->show_header );

        $matches = $league_obj->team->matches;
        ob_start();
        require RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
        $html = (string) ob_get_clean();
        $this->assertStringNotContainsString( 'match__header--up', $html );
        $this->assertStringNotContainsString( 'Test League', $html );

        // Team rank guard error (empty rank in league standings)
        $query_unranked = new Get_League_Teams_Query( league_id: 1, team_id: '8' );
        $vm_unranked    = $handler->handle( $query_unranked );
        $this->assertTrue( $vm_unranked->is_error() );
        $this->assertSame( 'Team not found in league', $vm_unranked->error_message );
    }

    public function test_teams_handler_decorates_nonempty_players(): void {
        $league_service = $this->createStub( League_Service::class );
        $team_service   = $this->createStub( Team_Service::class );

        $league_obj = new class extends League {
            public function __construct() {
                $this->id             = 1;
                $this->title          = 'Test League';
                $this->current_season = [ 'name' => '2025' ];
            }
            public function set_template( string $type, string $template ): void {}
            public function set_group( string $group = '', bool $force_overwrite = false ): void {}
            public function get_matches( array $query_args = [], ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?\Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface $fixture_repository = null, ?\Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface $rubber_repository = null ): array|int|object { return []; }
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        $team_obj = new class extends Team { public function __construct() { $this->id = 7; $this->title = 'Westcliff 1'; } };
        $team_service->method( 'get_team_details' )->willReturn( new Team_Details_DTO( team: $team_obj ) );

        [ $low, $high, $stats ] = $this->build_stat_players( $league_obj, '2025', 'league' );

        $team_presenter = new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );
        $league_query   = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_players' )->willReturn( [ $low, $high ] );
        $league_query->method( 'get_team_dtls' )->willReturn( (object) [ 'name' => 'T' ] );
        $league_query->method( 'get_league_team' )->willReturn( (object) [ 'rank' => 1 ] );
        $handler = new Get_League_Teams_Handler( $league_service, $team_service, $stats, new Tournament_Presenter(), $team_presenter, $league_query );
        $vm      = $handler->handle( new Get_League_Teams_Query( league_id: 1, team_id: '7' ) );

        $this->assertFalse( $vm->is_error() );
        $this->assert_decorated_and_sorted( $low, $high, $team_obj->players );
    }

    public function test_players_handler_list_decorates_stats_and_team_by_season(): void {
        $league_service = $this->createStub( League_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $renderer       = $this->create_renderer();

        $league_obj = new class extends League {
            public function __construct() {
                $this->id             = 1;
                $this->title          = 'Test League';
                $this->sport          = 'tennis';
                $this->current_season = [ 'name' => '2025' ];
            }
            public function set_season( false|string $season = false, bool $force_overwrite = false, ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?string $final = null ): void {}
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        [ $low, $high, $stats ] = $this->build_stat_players( $league_obj, '2025', 'league' );

        $found_team = (object) [ 'id' => 55, 'title' => 'Found Team' ];
        $repo       = $this->createMock( League_Repository_Interface::class );
        $repo->expects( $this->exactly( 2 ) )
            ->method( 'get_player_team' )
            ->willReturnCallback(
                function ( $league_id, $args ) use ( $found_team, $high ) {
                    $this->assertSame( 1, $league_id );
                    $this->assertSame( '2025', $args['season'] );
                    return $args['player'] === $high->get_id() ? $found_team : null;
                }
            );

        $league_query = $this->createMock( League_Query_Service::class );
        $league_query->expects( $this->once() )->method( 'get_players' )->with( $league_obj )->willReturn( [ $low, $high ] );
        $handler = new Get_League_Players_Handler( $league_service, $repo, $player_service, $renderer, $this->createStub( Player_Query_Service::class ), $stats, new Player_Presenter(), $league_query );
        $vm      = $handler->handle( new Get_League_Players_Query( league_id: 1 ) );

        $this->assertFalse( $vm->is_error() );
        $this->assert_decorated_and_sorted( $low, $high, $league_obj->players );
        $this->assertSame( $found_team, $high->team );
        $this->assertInstanceOf( \stdClass::class, $low->team );
        $this->assertEquals( new \stdClass(), $low->team );
    }


    /**
     * Build two players (low wins first) and a real stats service over a mocked query service.
     *
     * @return array{0: Player, 1: Player, 2: Player_Stats_Service, 3: \PHPUnit\Framework\MockObject\MockObject}
     */
    private function build_stat_players( object $grouping, string $season, string $source ): array {
        $low             = new Player();
        $low->id         = 31;
        $low->statistics = [ 's' => [ 'played' => [ 'winner' => 1, 'loser' => 2 ] ] ];
        $high             = new Player();
        $high->id         = 32;
        $high->statistics = [ 's' => [ 'played' => [ 'winner' => 4, 'loser' => 0 ] ] ];

        $query_service = $this->createMock( Player_Query_Service::class );
        $query_service->expects( $this->exactly( 2 ) )
            ->method( 'get_matches' )
            ->with( $this->isInstanceOf( Player::class ), $grouping, $season, $source )
            ->willReturn( [] );

        return [ $low, $high, new Player_Stats_Service( $query_service ), $query_service ];
    }

    private function assert_decorated_and_sorted( Player $low, Player $high, array $players ): void {
        $this->assertSame( [ $high, $low ], $players );
        $this->assertSame( 4, $high->matches_won );
        $this->assertSame( 4, $high->played );
        $this->assertSame( 1, $low->matches_won );
        $this->assertSame( 3, $low->played );
        $this->assertArrayHasKey( 'total', $high->stats );
        $this->assertSame( [], $high->matches );
    }

    public function test_players_handler_numeric_id_and_name(): void {
        $league_service = $this->createStub( League_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $renderer       = $this->create_renderer();

        $query_service = $this->createStub( Player_Query_Service::class );
        $query_service->method( 'get_matches' )->willReturn( [] );
        $stats_service = $this->createStub( Player_Stats_Service::class );
        $stats_service->method( 'get_stats' )->willReturn( [] );
        $league_repo   = $this->createStub( League_Repository_Interface::class );
        $handler       = new Get_League_Players_Handler( $league_service, $league_repo, $player_service, $renderer, $query_service, $stats_service, new Player_Presenter(), $this->league_query_service( $league_repo ) );

        $league_obj = new class extends League {
            public function __construct() {
                $this->id    = 1;
                $this->title = 'Test League';
                $this->sport = 'tennis';
            }
            public function set_season( false|string $season = false, bool $force_overwrite = false, ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?string $final = null ): void {}
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        $player_obj = new Player();
        $player_obj->id = 12;
        $player_obj->display_name = 'John Doe';

        $player_service->method( 'get_player' )->willReturnCallback(
            function( int $id ) use ( $player_obj ) {
                if ( 999 === $id ) {
                    throw new \Exception( 'Player not found' );
                }
                return $player_obj;
            }
        );
        $player_service->method( 'get_player_by_name' )->willReturn( $player_obj );

        // Player not found error
        $query_error = new Get_League_Players_Query( league_id: 1, player_id: '999' );
        $vm_error    = $handler->handle( $query_error );
        $this->assertTrue( $vm_error->is_error() );
        $this->assertSame( 'Player not found', $vm_error->error_message );

        // Player by numeric id
        $query_num = new Get_League_Players_Query( league_id: 1, player_id: '12' );
        $vm_num    = $handler->handle( $query_num );
        $this->assertFalse( $vm_num->is_error() );
        $this->assertSame( 'players-tennis', $vm_num->template );
        $this->assertSame( 'league', $vm_num->template_type );
        $this->assertSame( [], $league_obj->player->matches );
        $this->assertSame( [], $league_obj->player->stats );

        // Player by name
        $query_name = new Get_League_Players_Query( league_id: 1, player_id: 'John Doe' );
        $vm_name    = $handler->handle( $query_name );
        $this->assertFalse( $vm_name->is_error() );
        $this->assertSame( 'players-tennis', $vm_name->template );

        // All players list
        $query_all = new Get_League_Players_Query( league_id: 1 );
        $vm_all    = $handler->handle( $query_all );
        $this->assertFalse( $vm_all->is_error() );
        $this->assertSame( 'players-tennis', $vm_all->template );
        $this->assertArrayHasKey( 'player_links', $vm_all->vars );
    }

    public function test_matches_handler_nonce_and_empty_template(): void {
        $league_service = $this->createStub( League_Service::class );
        $renderer       = $this->create_renderer();

        $league_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $league_repository->expects( $this->exactly( 2 ) )
            ->method( 'get_match_day' )
            ->with( 1, '', 'current' )
            ->willReturn( 2 );
        $handler = new Get_League_Matches_Handler( $league_service, $renderer, new Tournament_Presenter(), $this->league_query_service( $league_repository ) );

        $league_obj = new class extends League {
            public int $set_match_day_calls = 0;
            public mixed $last_match_day    = null;
            public function __construct() {
                $this->id        = 1;
                $this->title     = 'Test League';
                $this->sport     = 'tennis';
                $this->match_day = -1;
            }
            public function set_template( string $type, string $template ): void {}
            public function set_season( false|string $season = false, bool $force_overwrite = false, ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?string $final = null ): void {}
            public function set_match_day( int $match_day = 1 ): void {
                $this->set_match_day_calls++;
                $this->last_match_day = $match_day;
            }
            public function set_num_matches( bool $total = false, ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null ): void {}
            public function get_matches( array $query_args = [], ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?\Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface $fixture_repository = null, ?\Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface $rubber_repository = null ): array|int|object { return []; }
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        // Default template (daily matches)
        $query_default = new Get_League_Matches_Query( league_id: 1, template: 'daily' );
        $vm_default    = $handler->handle( $query_default );
        $this->assertFalse( $vm_default->is_error() );
        $this->assertSame( 'matches-daily', $vm_default->template );
        $this->assertFalse( $vm_default->template_type );
        $this->assertSame( 2, $league_obj->last_match_day );

        // By matchday template (sets match day to -1)
        $query_by_md = new Get_League_Matches_Query( league_id: 1, template: 'by_matchday' );
        $vm_by_md    = $handler->handle( $query_by_md );
        $this->assertFalse( $vm_by_md->is_error() );
        $this->assertSame( -1, $league_obj->last_match_day );

        // Empty template (preserves matches-sport or matches)
        $query_empty = new Get_League_Matches_Query( league_id: 1, template: '' );
        $vm_empty    = $handler->handle( $query_empty );
        $this->assertFalse( $vm_empty->is_error() );
        $this->assertSame( 'matches', $vm_empty->template );

        // Numeric match day
        $query_md = new Get_League_Matches_Query( league_id: 1, matches: '3', template: 'table' );
        $vm_md    = $handler->handle( $query_md );
        $this->assertFalse( $vm_md->is_error() );
        $this->assertSame( 3, $league_obj->last_match_day );
    }

    public function test_crosstable_dropdowns_and_last5_handlers(): void {
        $league_service = $this->createStub( League_Service::class );
        $renderer       = $this->create_renderer();

        $league_obj = new class extends League {
            public function __construct() {
                $this->id    = 1;
                $this->title = 'Test League';
                $this->sport = 'tennis';
            }
            public function set_template( string $type, string $template ): void {}
            public function set_season( false|string $season = false, bool $force_overwrite = false, ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?string $final = null ): void {}
            public function set_group( string $group = '', bool $force_overwrite = false ): void {}
            public function get_matches( array $query_args = [], ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?\Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface $fixture_repository = null, ?\Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface $rubber_repository = null ): array|int|object { return []; }
        };
        $league_service->method( 'get_league' )->willReturn( $league_obj );

        // Crosstable
        $crosstable_handler = new Get_League_Crosstable_Handler( $league_service, $renderer, $this->league_query_service() );
        $vm_ct             = $crosstable_handler->handle( new Get_League_Crosstable_Query( league_id: 1 ) );
        $this->assertFalse( $vm_ct->is_error() );
        $this->assertSame( 'crosstable', $vm_ct->template );
        $this->assertSame( 'league', $vm_ct->template_type );

        // Season dropdown
        $season_handler = new Get_League_Season_Dropdown_Handler( $league_service, $renderer );
        $vm_sd         = $season_handler->handle( new Get_League_Season_Dropdown_Query( league_id: 1, season: '2026' ) );
        $this->assertFalse( $vm_sd->is_error() );
        $this->assertSame( 'season-dropdown', $vm_sd->template );
        $this->assertSame( 'league', $vm_sd->template_type );

        // Match dropdown
        $match_handler = new Get_League_Match_Dropdown_Handler( $league_service, $renderer, $this->league_query_service() );
        $vm_md        = $match_handler->handle( new Get_League_Match_Dropdown_Query( league_id: 1, season: '2026' ) );
        $this->assertFalse( $vm_md->is_error() );
        $this->assertSame( 'match-dropdown', $vm_md->template );
        $this->assertSame( 'league', $vm_md->template_type );

        // Last 5
        $last5_handler = new Get_League_Last_5_Handler( $league_service, $renderer, $this->createStub( Fixture_Presenter::class ), $this->league_query_service() );
        $vm_l5        = $last5_handler->handle( new Get_League_Last_5_Query( league_id: 1, team_id: 5 ) );
        $this->assertFalse( $vm_l5->is_error() );
        $this->assertSame( 'last-5', $vm_l5->template );
        $this->assertSame( 'league', $vm_l5->template_type );
    }

    public function test_last_5_handler_renders_unloaded_fixtures_with_team_names_and_no_warnings(): void {
        $completed              = new Fixture();
        $completed->id          = 101;
        $completed->home_team   = '10';
        $completed->away_team   = '20';
        $completed->date        = '2026-10-06';
        $completed->home_points = '6';
        $completed->away_points = '0';
        $completed->winner_id   = 10;
        $completed->loser_id    = 20;

        $titled                = new Fixture();
        $titled->id            = 102;
        $titled->home_team     = '20';
        $titled->away_team     = '10';
        $titled->date          = '2026-10-11';
        $titled->home_points   = '3';
        $titled->away_points   = '3';
        $titled->fixture_title = 'Division final';
        $titled->winner_id     = -1;
        $titled->loser_id      = -1;

        $pending            = new Fixture();
        $pending->id        = 103;
        $pending->home_team = '10';
        $pending->away_team = '20';
        $pending->date      = '2026-10-20';

        $league = new class( [ $completed, $titled, $pending ] ) extends League {
            public function fixtures(): array { return $this->fixtures; }
            public function __construct( private array $fixtures ) {
                $this->id = 1;
                $this->seasons        = array( '2026' => array( 'name' => '2026' ) );
                $this->current_season = array( 'name' => '2026' );
                $this->set_match_query_arg( 'season', '2026' );
            }
            public function set_season( false|string $season = false, bool $force_overwrite = false, ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?string $final = null ): void {}
        };
        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $league_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $league_repo->method( 'query_league_teams' )->willReturnCallback( static fn( int $league_id, array $args, bool $count = false ): array|int => $count ? 2 : [
            (object) [ 'table_id' => 10, 'id' => 10, 'title' => 'Red Team' ],
            (object) [ 'table_id' => 20, 'id' => 20, 'title' => 'Blue Team' ],
        ] );
        $red_team = \Racketmanager\Domain\Competition\League_Team::from_object( (object) [ 'table_id' => 10, 'id' => 10, 'title' => 'Red Team' ] );
        $blue_team = \Racketmanager\Domain\Competition\League_Team::from_object( (object) [ 'table_id' => 20, 'id' => 20, 'title' => 'Blue Team' ] );
        $league_team_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface::class );
        $league_team_repo->method( 'find_by_id' )->willReturnMap( [ [ 10, $red_team ], [ 20, $blue_team ] ] );
        $options = $this->createStub( Option_Gateway_Interface::class );
        $options->method( 'get_option' )->willReturn( 'd/m/Y' );
        $fixture_presenter = new Fixture_Presenter( $this->createStub( Fixture_Link_Service::class ), $options );
                $query_fixtures = $this->createStub( Fixture_Repository_Interface::class );
        $query_fixtures->method( 'find_by_id' )->willReturnCallback( static function ( int $id ) use ( $league ) {
            foreach ( $league->fixtures() as $fixture ) {
                if ( $fixture->id === $id ) {
                    return $fixture;
                }
            }
            return null;
        } );
        $league_repo->method( 'query_matches' )->willReturnCallback( static fn(): array => array_map( static fn( $fixture ) => (object) [ 'id' => $fixture->id ], $league->fixtures() ) );
        $handler = new Get_League_Last_5_Handler( $league_service, $this->create_renderer(), $fixture_presenter, $this->league_query_service( $league_repo, $league_team_repo, $query_fixtures ) );

        $warnings = [];
        set_error_handler( static function ( int $code, string $message ) use ( &$warnings ): bool {
            $warnings[] = $message;
            return true;
        } );
        try {
            $vm = $handler->handle( new Get_League_Last_5_Query( league_id: 1, team_id: 10 ) );
            $html = ( new Shortcode_View_Presenter( $this->create_renderer() ) )->render( $vm );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertNull( $completed->teams );
        $this->assertNull( $pending->teams );
        $this->assertSame( '6 - 0 - Red Team - Blue Team [06/10/2026]', $vm->vars['match_tooltips'][101] );
        $this->assertSame( 'Division final [11/10/2026]', $vm->vars['match_tooltips'][102] );
        $this->assertSame( 'Next Match: Red Team - Blue Team [20/10/2026]', $vm->vars['match_tooltips'][103] );
        foreach ( $vm->vars['match_tooltips'] as $tooltip ) {
            $this->assertStringContainsString( 'title="' . esc_attr( $tooltip ) . '"', $html );
        }
        $this->assertStringNotContainsString( 'Unknown', $html );
    }

    public function test_last_5_handler_caches_team_titles_per_league_and_season_and_handles_byes_and_unknowns(): void {
        $f_normal            = new Fixture();
        $f_normal->id        = 201;
        $f_normal->home_team = '10';
        $f_normal->away_team = '20';
        $f_normal->date      = '2026-10-06';
        $f_normal->home_points = '4';
        $f_normal->away_points = '2';
        $f_normal->winner_id = 10;
        $f_normal->loser_id  = 20;

        $f_bye            = new Fixture();
        $f_bye->id        = 202;
        $f_bye->home_team = '10';
        $f_bye->away_team = '-1';
        $f_bye->date      = '2026-10-13';
        $f_bye->home_points = '6';
        $f_bye->away_points = '0';
        $f_bye->winner_id = 10;
        $f_bye->loser_id  = -1;

        $f_unknown            = new Fixture();
        $f_unknown->id        = 203;
        $f_unknown->home_team = '999';
        $f_unknown->away_team = '10';
        $f_unknown->date      = '2026-10-20';

        $league = new class( [ $f_normal, $f_bye, $f_unknown ] ) extends League {
            public function fixtures(): array { return $this->fixtures; }
            public function __construct( private array $fixtures ) {
                $this->id     = 1;
                $this->season = '2026';
                $this->seasons        = array( '2026' => array( 'name' => '2026' ) );
                $this->current_season = array( 'name' => '2026' );
                $this->set_match_query_arg( 'season', '2026' );
            }
            public function set_season( false|string $season = false, bool $force_overwrite = false, ?\Racketmanager\Repositories\Interfaces\League_Repository_Interface $league_repository = null, ?string $final = null ): void {}
        };

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $teams_call_count = 0;
        $league_repo      = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $league_repo->method( 'query_league_teams' )->willReturnCallback( function( int $league_id, array $args, bool $count = false ) use ( &$teams_call_count ): array|int {
            if ( ! $count ) {
                $teams_call_count++;
            }
            if ( $count ) {
                return 2;
            }
            return [
                (object) [ 'table_id' => 10, 'id' => 10, 'title' => 'Alpha Team' ],
                (object) [ 'table_id' => 20, 'id' => 20, 'title' => 'Beta Team' ],
            ];
        } );
        $alpha_team = \Racketmanager\Domain\Competition\League_Team::from_object( (object) [ 'table_id' => 10, 'id' => 10, 'title' => 'Alpha Team' ] );
        $beta_team  = \Racketmanager\Domain\Competition\League_Team::from_object( (object) [ 'table_id' => 20, 'id' => 20, 'title' => 'Beta Team' ] );
        $league_team_repo = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface::class );
        $league_team_repo->method( 'find_by_id' )->willReturnMap( [ [ 10, $alpha_team ], [ 20, $beta_team ] ] );
        $options = $this->createStub( Option_Gateway_Interface::class );
        $options->method( 'get_option' )->willReturn( 'd/m/Y' );
        $fixture_presenter = new Fixture_Presenter( $this->createStub( Fixture_Link_Service::class ), $options );
                $query_fixtures = $this->createStub( Fixture_Repository_Interface::class );
        $query_fixtures->method( 'find_by_id' )->willReturnCallback( static function ( int $id ) use ( $league ) {
            foreach ( $league->fixtures() as $fixture ) {
                if ( $fixture->id === $id ) {
                    return $fixture;
                }
            }
            return null;
        } );
        $league_repo->method( 'query_matches' )->willReturnCallback( static fn(): array => array_map( static fn( $fixture ) => (object) [ 'id' => $fixture->id ], $league->fixtures() ) );
        $handler = new Get_League_Last_5_Handler( $league_service, $this->create_renderer(), $fixture_presenter, $this->league_query_service( $league_repo, $league_team_repo, $query_fixtures ) );

        $warnings = [];
        set_error_handler( static function ( int $code, string $message ) use ( &$warnings ): bool {
            $warnings[] = $message;
            return true;
        } );

        try {
            // First call for Team 10 in 2026 season -> 1 call from the handler's team lookup
            $vm1 = $handler->handle( new Get_League_Last_5_Query( league_id: 1, team_id: 10 ) );
            $this->assertSame( 1, $teams_call_count );

            // Second call for Team 20 in same 2026 season -> +0 calls; the handler's own team lookup is served from cache
            $vm2 = $handler->handle( new Get_League_Last_5_Query( league_id: 1, team_id: 20 ) );
            $this->assertSame( 1, $teams_call_count );

            // Change season on league to 2027 -> +1 call: a handler lookup, since the cache is refreshed for the new season
            $league->season = '2027';
            $vm3 = $handler->handle( new Get_League_Last_5_Query( league_id: 1, team_id: 10 ) );
            $this->assertSame( 2, $teams_call_count );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        // Assert normal match has resolved names
        $this->assertSame( '4 - 2 - Alpha Team - Beta Team [06/10/2026]', $vm1->vars['match_tooltips'][201] );
        // Assert bye match falls back cleanly to 'Bye' placeholder without warning
        $this->assertSame( '6 - 0 - Alpha Team - Bye [13/10/2026]', $vm1->vars['match_tooltips'][202] );
        // Assert unknown team falls back to 'Unknown' placeholder without warning
        $this->assertSame( 'Next Match: Unknown - Alpha Team [20/10/2026]', $vm1->vars['match_tooltips'][203] );
    }

    public function test_renderer_sets_and_restores_global_league(): void {
        $inner_renderer = new class implements View_Renderer_Interface {
            public ?int $observed_league_id = null;
            public function render( string $template_path, object|array $view_model, array $additional_vars = [] ): void {}
            public function render_to_string( string $template_path, object|array $view_model, array $additional_vars = [] ): string {
                $this->observed_league_id = isset( $GLOBALS['league'] ) && is_object( $GLOBALS['league'] ) ? ( $GLOBALS['league']->id ?? null ) : null;
                return 'rendered-output';
            }
            public function template_exists( string $template_path ): bool {
                return true;
            }
        };

        $renderer   = new Shortcode_Template_Renderer( $inner_renderer );
        $league_obj = (object) [ 'id' => 42, 'title' => 'Global League Test' ];

        // 1. When $league was previously set to another value
        global $league;
        $league = 'previous_value';

        $output = $renderer->render( 'standings', [ 'league' => $league_obj ], false );
        $this->assertSame( 'rendered-output', $output );
        $this->assertSame( 42, $inner_renderer->observed_league_id );
        $this->assertSame( 'previous_value', $GLOBALS['league'] );

        // 2. When $league was not previously set
        unset( $GLOBALS['league'] );
        $output_unset = $renderer->render( 'standings', [ 'league' => $league_obj ], false );
        $this->assertSame( 'rendered-output', $output_unset );
        $this->assertSame( 42, $inner_renderer->observed_league_id );
        $this->assertFalse( array_key_exists( 'league', $GLOBALS ) );
    }
}
