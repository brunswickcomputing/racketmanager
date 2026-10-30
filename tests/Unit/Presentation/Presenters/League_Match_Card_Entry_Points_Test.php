<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Clubs_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Query;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Query;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\League_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Fixture_Presentation_Adapter;
use Racketmanager\Presentation\Presenters\Matches_List_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;
use Racketmanager\Presentation\View_Models\Team_Matches_Group_View_Model;
use Racketmanager\Presentation\View_Models\Tournament_Match_Card_View_Model;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;

/**
 * End-to-end coverage of the league/team entry points that render tournament match cards:
 * handler -> Tournament_Presenter -> real templates (matches.php, team-details.php, matches-team-list.php).
 */
class League_Match_Card_Entry_Points_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;
    use Creates_Competition_Season_Service;
    use Manages_Wordpress_Globals;
    use Creates_Team_Match_Row_Presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['racketmanager'] = new class {
            public string $date_format = 'Y-m-d';
            public function is_user_favourite( mixed $type = null, mixed $id = null ): bool {
                return false;
            }
            public function is_player_in_club( array $args = [] ): bool {
                return false;
            }
            public function get_options( ?string $key = null ): array {
                return [];
            }
        };
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function renderer(): Shortcode_Template_Renderer {
        return new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
    }

    private function competition_presenter(): Competition_Shortcode_Presenter {
        return new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
    }

    /**
     * Build Event query dependencies from repository fixtures instead of Event method stubs.
     *
     * @param Fixture[] $fixtures
     * @param League[]  $leagues
     */
    private function event_query_service_with_fixtures( array $fixtures = [], array $leagues = [] ): Event_Query_Service {
        $event_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
        $event_repository->method( 'query_leagues' )->willReturn( $leagues );
        $event_repository->method( 'query_matches' )->willReturnCallback(
            static fn( int $event_id, array $args = [] ): array => array_map(
                static fn( Fixture $fixture ): int => (int) $fixture->id,
                $fixtures
            )
        );

        $fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_by_id     = [];
        foreach ( $fixtures as $fixture ) {
            $fixture_by_id[ (int) $fixture->id ] = $fixture;
        }
        $fixture_repository->method( 'find_by_id_with_relations' )->willReturnCallback(
            static fn( int|string $fixture_id ): ?Fixture => $fixture_by_id[ (int) $fixture_id ] ?? null
        );

        return $this->event_query_service( $event_repository, $fixture_repository );
    }

    private function create_player( int $id, string $fullname ): Player {
        $player = $this->createStub( Player::class );
        $player->method( 'get_id' )->willReturn( $id );
        $player->method( 'get_fullname' )->willReturn( $fullname );
        $player->id           = $id;
        $player->display_name = $fullname;
        return $player;
    }

    private function create_team_details( int $id, string $name, array $players ): Team_Details_DTO {
        $team = $this->createStub( Team::class );
        $team->method( 'get_id' )->willReturn( $id );
        $team->method( 'get_name' )->willReturn( $name );
        $team->method( 'has_player' )->willReturnCallback( static fn( int $player_id ) => in_array( $player_id, array_map( static fn( $p ) => $p->get_id(), $players ), true ) );
        $team->players = $players;
        return new Team_Details_DTO( $team );
    }

    private function create_competition( Competition_Type $type, int $id = 1 ): Competition {
        $competition                  = $this->createStub( Competition::class );
        $competition->id              = $id;
        $competition->name            = 'Championship Tourney';
        $competition->type            = $type;
        $competition->is_team_entry   = $type->is_team_entry();
        $competition->is_player_entry = $type->is_player_entry();
        $competition->is_championship = true;
        $competition->current_season  = [ 'name' => '2026' ];
        $competition->method( 'get_id' )->willReturn( $id );
        $competition->method( 'is_event_allowed' )->willReturn( true );
        return $competition;
    }

    private function create_event( Competition $competition ): Event {
        $event = new class extends Event {
            public function __construct() {
                $this->id             = 12;
                $this->name           = 'Mens Singles';
                $this->type           = 'MS';
                $this->is_box         = true;
                $this->current_season = [ 'name' => '2026' ];
            }
            public function set_season( ?string $season = null, bool $force_overwrite = false, bool $strict = false ): void {}
        };
        $event->competition = $competition;
        return $event;
    }

    /**
     * Played fixture with set scores and a tie-break, plus the enriched detail returned by the detail service.
     *
     * @return array{0: Fixture, 1: Fixture_Details_DTO}
     */
    private function build_played_fixture( League $league, Event $event, Competition $competition, bool $user_can_update = true ): array {
        $fixture = Fixture_Test_Factory::create(
            id: 701,
            league_id: 3,
            home_team: '10',
            away_team: '20',
            winner_id: 10,
            loser_id: 20,
            season: '2026',
            match_day: 1,
            custom: [
                'sets' => [
                    new Set_Score( 6, 4 ),
                    new Set_Score( 7, 6, 7, 5 ),
                ],
            ]
        );
        $detail = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $competition,
            home_team: $this->create_team_details( 10, 'Team Federer', [ $this->create_player( 101, 'Roger Federer' ) ] ),
            away_team: $this->create_team_details( 20, 'Team Nadal', [ $this->create_player( 102, 'Rafael Nadal' ) ] ),
            is_update_allowed: (object) [ 'user_can_update' => $user_can_update ]
        );

        return [ $fixture, $detail ];
    }

    private function create_league( Event $event, array $matches ): League {
        $league = new class extends League {
            public function __construct() {
                $this->id             = 3;
                $this->title          = 'Mens Singles Division';
                $this->match_day      = -1;
                $this->current_season = [ 'name' => '2026' ];
                $this->seasons        = [ '2026' ];
            }
            public function set_template( string $type, string $template ): void {}
            public function set_season( false|string $season = false, bool $force_overwrite = false, ?string $final = null ): void {}
            public function set_match_day( int $match_day = 1 ): void {}
            public function set_group( string $group = '', bool $force_overwrite = false ): void {}
            public function set_num_matches( bool $total = false ): void {}
        };
        $league->event        = $event;
        $league->championship = new class extends Championship {
            public function __construct() {}
            public function get_finals( false|int|string $key = false ): ?array {
                return [ [ 'name' => 'Final', 'key' => 'final' ] ];
            }
        };
        return $league;
    }

    private function league_query_service_with_matches( array $fixtures ): \Racketmanager\Services\Competition\League_Query_Service {
        $league_repository = $this->createStub( League_Repository_Interface::class );
        $league_repository->method( 'query_matches' )->willReturn(
            array_map( static fn( Fixture $fixture ): object => (object) [ 'id' => $fixture->get_id() ], $fixtures )
        );

        $fixture_repository = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repository->method( 'find_by_id' )->willReturnCallback(
            static function ( int $id ) use ( $fixtures ): ?Fixture {
                foreach ( $fixtures as $fixture ) {
                    if ( $fixture->get_id() === $id ) {
                        return $fixture;
                    }
                }
                return null;
            }
        );

        return $this->league_query_service( $league_repository, null, $fixture_repository );
    }

    private function detail_service_expecting( Fixture $fixture, Fixture_Details_DTO $detail, bool $is_tournament ): Fixture_Detail_Service {
        $service = $this->createMock( Fixture_Detail_Service::class );
        $service->expects( $this->once() )
            ->method( 'get_fixture_with_details' )
            ->with( $fixture, $is_tournament )
            ->willReturn( $detail );
        return $service;
    }

    private function assert_played_card_markup( string $html ): void {
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        $this->assertStringContainsString( '<span class="player-row__tie-break">7</span>', $html );
        // League pages have no Tournament context: the card links to the league match page.
        $this->assertStringContainsString( '/match/mens-singles-division/2026/day1/team-federer-vs-team-nadal/', $html );
        $this->assertStringContainsString( 'href="/match/mens-singles-division/2026/day1/team-federer-vs-team-nadal/" class="btn match__btn"', $html );
        $this->assertStringNotContainsString( 'Match details unavailable', $html );
    }

    public function test_league_matches_handler_renders_tournament_competition_cards_in_real_matches_template(): void {
        $competition            = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                  = $this->create_event( $competition );
        $league                 = $this->create_league( $event, [] );
        [ $fixture, $detail ]   = $this->build_played_fixture( $league, $event, $competition );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $presenter = new Tournament_Presenter( 'd/m/Y', $this->detail_service_expecting( $fixture, $detail, true ), team_match_row_presenter: $this->create_team_match_row_presenter() );

        $handler = new Get_League_Matches_Handler( $league_service, $this->league_query_service_with_matches( [ $fixture ] ) );
        $adapter = new League_Shortcode_Adapter(
            tournament_presenter: $presenter,
            template_renderer: $this->renderer()
        );
        $vm      = $adapter->present_matches( $handler->handle( new Get_League_Matches_Query( league_id: 3, template: '' ) ) );

        $this->assertSame( 'matches', $vm->template );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $vm->vars['matches'] );

        $html = ( new Shortcode_View_Presenter( $this->renderer() ) )->render( $vm );
        $this->assertStringContainsString( 'class="tournament-matches"', $html );
        $this->assert_played_card_markup( $html );
    }

    public function test_league_matches_handler_renders_explicit_state_for_unresolvable_fixture(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $fixture              = Fixture_Test_Factory::create( id: 702, league_id: 3 );
        $league               = $this->create_league( $event, [ $fixture ] );

        $service = $this->createStub( Fixture_Detail_Service::class );
        $service->method( 'get_fixture_with_details' )->willThrowException( new \Racketmanager\Exceptions\Fixture_Not_Found_Exception( 'gone' ) );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $presenter = new Tournament_Presenter( 'd/m/Y', $service, team_match_row_presenter: $this->create_team_match_row_presenter() );
        $handler   = new Get_League_Matches_Handler( $league_service, $this->league_query_service_with_matches( [ $fixture ] ) );
        $adapter   = new League_Shortcode_Adapter(
            tournament_presenter: $presenter,
            template_renderer: $this->renderer()
        );

        $html = ( new Shortcode_View_Presenter( $this->renderer() ) )->render( $adapter->present_matches( $handler->handle( new Get_League_Matches_Query( league_id: 3, template: '' ) ) ) );

        $this->assertStringContainsString( 'Match details unavailable', $html );
        $this->assertStringNotContainsString( 'player-row', $html );
    }

    public function test_league_teams_handler_renders_player_entry_cards_in_real_team_details_template(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        $league->teams        = [ (object) [ 'rank' => 1, 'done_matches' => 1, 'points_formatted' => [ 'primary' => 2 ] ] ];
        $league->team_index   = [ 10 => 0 ];
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $team = new class extends Team {
            public function __construct() {
                $this->id    = 10;
                $this->title = 'Team Federer';
            }
        };
        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturn( new Team_Details_DTO( team: $team ) );
        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturn( [] );

        $team_presenter = new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );
        $presenter = new Tournament_Presenter( 'd/m/Y', $this->detail_service_expecting( $fixture, $detail, true ), team_match_row_presenter: $this->create_team_match_row_presenter() );
        $handler   = new Get_League_Teams_Handler( $league_service, $team_service, $stats, $this->league_query_service_with_matches( [ $fixture ] ) );
        $adapter   = new League_Shortcode_Adapter(
            tournament_presenter: $presenter,
            team_details_presenter: $team_presenter,
            template_renderer: $this->renderer()
        );
        $vm        = $adapter->present_teams( $handler->handle( new Get_League_Teams_Query( league_id: 3, team_id: '10' ) ) );

        $this->assertSame( 'team-details', $vm->template );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $league->team->matches );

        $html = ( new Shortcode_View_Presenter( $this->renderer() ) )->render( $vm );
        $this->assert_played_card_markup( $html );
    }

    public function test_played_card_renders_no_update_button_when_user_not_authorized(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition, user_can_update: false );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $presenter = new Tournament_Presenter( 'd/m/Y', $this->detail_service_expecting( $fixture, $detail, true ), team_match_row_presenter: $this->create_team_match_row_presenter() );

        $handler = new Get_League_Matches_Handler( $league_service, $this->league_query_service_with_matches( [ $fixture ] ) );
        $adapter = new League_Shortcode_Adapter(
            tournament_presenter: $presenter,
            template_renderer: $this->renderer()
        );
        $vm      = $adapter->present_matches( $handler->handle( new Get_League_Matches_Query( league_id: 3, template: '' ) ) );

        $html = ( new Shortcode_View_Presenter( $this->renderer() ) )->render( $vm );
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringNotContainsString( 'button--update-result', $html );
        $this->assertStringNotContainsString( '/match/mens-singles-division/2026/day1/team-federer-vs-team-nadal/', $html );
    }

    public function test_league_teams_handler_keeps_team_matches_for_team_entry_league(): void {
        $competition = $this->create_competition( Competition_Type::LEAGUE );
        $event       = $this->create_event( $competition );
        $fixture     = Fixture_Test_Factory::create( id: 703, league_id: 3 );
        $league      = $this->create_league( $event, [ $fixture ] );
        $league->teams      = [ (object) [ 'rank' => 1, 'done_matches' => 1, 'points_formatted' => [ 'primary' => 2 ] ] ];
        $league->team_index = [ 10 => 0 ];

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );
        $team = new class extends Team {
            public function __construct() {
                $this->id    = 10;
                $this->title = 'Team A';
            }
        };
        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturn( new Team_Details_DTO( team: $team ) );
        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturn( [] );

        $service = $this->createMock( Fixture_Detail_Service::class );
        $service->expects( $this->never() )->method( 'get_fixture_with_details' );

        $team_presenter = new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );
        $presenter = new Tournament_Presenter( 'd/m/Y', $service, team_match_row_presenter: $this->create_team_match_row_presenter() );
        $handler   = new Get_League_Teams_Handler( $league_service, $team_service, $stats, $this->league_query_service_with_matches( [ $fixture ] ) );
        $adapter   = new League_Shortcode_Adapter(
            tournament_presenter: $presenter,
            team_details_presenter: $team_presenter,
            template_renderer: $this->renderer()
        );
        $adapter->present_teams( $handler->handle( new Get_League_Teams_Query( league_id: 3, team_id: '10' ) ) );

        $this->assertCount( 1, $league->team->matches );
        $this->assertInstanceOf( Team_Match_Row_View_Model::class, $league->team->matches[0] );
        $this->assertSame( 703, $league->team->matches[0]->id );
    }

    public function test_match_list_converts_tournament_fixtures_for_team_list_template(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );
        $fixture->set_league( $league );

        $presenter = new Tournament_Presenter( 'd/m/Y', $this->detail_service_expecting( $fixture, $detail, true ), team_match_row_presenter: $this->create_team_match_row_presenter() );
        $matches   = $presenter->present_match_list( [ 5 => $fixture ] );

        $this->assertArrayHasKey( 5, $matches );
        $this->assertInstanceOf( Tournament_Match_Card_View_Model::class, $matches[5] );
        $this->assertStringContainsString( 'match--list', $matches[5]->match_display_class );

        ob_start();
        include RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
        $html = (string) ob_get_clean();

        $this->assertStringContainsString( '<ul class="match-group">', $html );
        $this->assert_played_card_markup( $html );
    }

    public function test_team_list_template_rejects_unpresented_fixture_explicitly(): void {
        $matches = [ Fixture_Test_Factory::create( id: 704, league_id: 3 ) ];

        $level = ob_get_level();
        ob_start();
        try {
            include RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
            $this->fail( 'The template must refuse raw fixtures.' );
        } catch ( \UnexpectedValueException $e ) {
            $this->assertStringContainsString( 'Team_Match_Row_View_Model', $e->getMessage() );
        } finally {
            while ( ob_get_level() > $level ) {
                ob_end_clean();
            }
        }
    }

    public function test_match_list_presents_non_tournament_fixtures_as_team_rows(): void {
        $service = $this->createMock( Fixture_Detail_Service::class );
        $service->expects( $this->never() )->method( 'get_fixture_with_details' );
        $presenter = new Tournament_Presenter( 'd/m/Y', $service, team_match_row_presenter: $this->create_team_match_row_presenter() );

        $league_fixture         = Fixture_Test_Factory::create( id: 705 );
        $league_fixture->league = (object) [ 'event' => (object) [ 'competition' => (object) [ 'type' => 'league' ] ] ];

        $matches = $presenter->present_match_list( [ $league_fixture ] );

        $this->assertCount( 1, $matches );
        $this->assertInstanceOf( Team_Match_Row_View_Model::class, $matches[0] );
        $this->assertSame( 705, $matches[0]->id );
    }

    public function test_get_league_championship_handler_renders_real_template_with_match_cards(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        $league->is_championship = true;
        $championship         = new class extends Championship {
            public function __construct() {}
            public function __get( string $name ): mixed {
                return 'num_teams_first_round' === $name ? 0 : parent::__get( $name );
            }
            public function get_finals( false|int|string $key = false ): ?array {
                return [
                    [
                        'name' => 'Final',
                        'key'  => 'final',
                    ],
                ];
            }
        };
        $league->championship = $championship;

        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );
        $fixture->set_league( $league );

        $fixture_repo = $this->createStub( Fixture_Repository_Interface::class );
        $fixture_repo->method( 'find_by_id' )->willReturn( $fixture );
        $fixture_repo->method( 'find_by_league_and_final' )->willReturn( [ $fixture ] );

        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( $league );

        $team_service = $this->createStub( Team_Service::class );
        $comp_service = $this->createStub( Competition_Service::class );
        $link_service = $this->createStub( Fixture_Link_Service::class );
        $perm_service = $this->createStub( Fixture_Permission_Service::class );
        $rubber_repo  = $this->createStub( Rubber_Repository_Interface::class );

        $detail_service = $this->detail_service_expecting( $fixture, $detail, true );
        $presenter      = new Tournament_Presenter( 'd/m/Y', $detail_service, team_match_row_presenter: $this->create_team_match_row_presenter() );

        $handler = new Get_League_Championship_Handler(
            $league_service,
            $fixture_repo,
            $rubber_repo,
            $this->league_query_service_with_matches( [ $fixture ] )
        );
        $adapter = new League_Shortcode_Adapter(
            competition_service: $comp_service,
            team_service: $team_service,
            fixture_link_service: $link_service,
            fixture_permission_service: $perm_service,
            template_renderer: $this->renderer(),
            tournament_presenter: $presenter
        );

        $vm = $adapter->present_championship( $handler->handle( new Get_League_Championship_Query( league_id: 3, template: 'matches' ) ) );

        $this->assertSame( 'championship-matches', $vm->template );
        $this->assertNotEmpty( $vm->vars['finals'] );
        $final_data = array_values( $vm->vars['finals'] )[0];
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $final_data->matches );

        $html = ( new Shortcode_View_Presenter( $this->renderer() ) )->render( $vm );
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        $this->assertStringNotContainsString( 'Match details unavailable', $html );
    }

    public function test_event_teams_handler_renders_player_entry_cards_in_real_team_details_template(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $competition->method( 'is_event_allowed' )->willReturn( true );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );

        $team = new class extends Team {
            public function __construct() {
                $this->id    = 10;
                $this->title = 'Team Federer';
            }
        };

        $event_stub = new class extends Event {
            public function __construct() {
                $this->id             = 12;
                $this->name           = 'Mens Singles';
                $this->current_season = [ 'name' => '2026' ];
            }
            public function set_season( ?string $season = null, bool $force_overwrite = false, bool $strict = false ): void {}
        };
        $event_stub->competition  = $competition;

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_by_id' )->willReturn( $team );

        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturn( [] );

        $presenter = new Tournament_Presenter( 'd/m/Y', $this->detail_service_expecting( $fixture, $detail, true ), team_match_row_presenter: $this->create_team_match_row_presenter() );
        $handler   = new Get_Event_Teams_Handler( $comp_service, $stats, $team_service, $this->event_query_service_with_fixtures( [ $fixture ] ) );
        $adapter   = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            competition_shortcode_presenter: $this->competition_presenter(),
            tournament_presenter: $presenter,
            team_details_presenter: new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() )
        );

        $vm = $adapter->present_teams( $handler->handle( new Get_Event_Teams_Query( event_id: 12, team: '10' ) ) );

        $this->assertSame( 'teams', $vm->template );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $event_stub->team->matches );

        $card = $event_stub->team->matches[0];
        ob_start();
        require RACKETMANAGER_PATH . 'templates/tournament/match.php';
        $html = (string) ob_get_clean();
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringNotContainsString( 'Match details unavailable', $html );
    }

    public function test_event_team_details_hides_league_row_header_in_presented_matches(): void {
        $competition = $this->create_competition( Competition_Type::LEAGUE );
        $event       = new class extends Event {
            public function __construct() {
                $this->id             = 12;
                $this->name           = 'Mens Singles';
                $this->current_season = [ 'name' => '2026' ];
            }

            public function set_season( ?string $season = null, bool $force_overwrite = false, bool $strict = false ): void {}
        };
        $event->competition = $competition;

        $fixture = Fixture_Test_Factory::create(
            id: 706,
            league_id: 3,
            home_team: 10,
            away_team: 20,
            winner_id: 10,
            loser_id: 20,
            home_points: '4',
            away_points: '2'
        );
        $fixture->teams = [
            'home' => (object) [ 'id' => 10, 'title' => 'Team Federer', 'club' => (object) [ 'id' => 50 ] ],
            'away' => (object) [ 'id' => 20, 'title' => 'Team Nadal', 'club' => (object) [ 'id' => 60 ] ],
        ];
        $fixture->league      = (object) [ 'id' => 3, 'title' => 'Mens Singles Division' ];

        $team = new class extends Team {
            public function __construct() {
                $this->id    = 10;
                $this->title = 'Team Federer';
            }
        };
        $competition_service = $this->createStub( Competition_Service::class );
        $competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_by_id' )->willReturn( $team );
        $stats     = $this->createStub( Player_Stats_Service::class );
        $presenter = new Tournament_Presenter( 'd/m/Y', team_match_row_presenter: $this->create_team_match_row_presenter() );
        $handler   = new Get_Event_Teams_Handler( $competition_service, $stats, $team_service, $this->event_query_service_with_fixtures( [ $fixture ] ) );
        $adapter   = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            competition_shortcode_presenter: $this->competition_presenter(),
            tournament_presenter: $presenter,
            team_details_presenter: new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() )
        );

        $vm  = $adapter->present_teams( $handler->handle( new Get_Event_Teams_Query( event_id: 12, team: '10' ) ) );
        $row = $event->team->matches[0];

        $this->assertFalse( $vm->is_error(), (string) $vm->error_message );
        $this->assertInstanceOf( Team_Match_Row_View_Model::class, $row );
        $this->assertFalse( $row->show_header );
        $this->assertSame( 'W', $row->status_text );

        $event->is_championship       = false;
        $event->team->standings       = (object) [
            'rank'           => 1,
            'done_matches'   => 1,
            'points_formatted' => [ 'primary' => 2 ],
        ];
        $event->team->info = (object) [
            'captain'      => '',
            'contactno'    => '',
            'contactemail' => '',
            'players'      => [],
            'match_time'   => null,
        ];
        $team_details = new Team_Details_DTO( team: $team );
        $vm           = ( new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() ) )->present( $event, $team_details );
        $object       = $event;
        ob_start();
        require RACKETMANAGER_PATH . 'templates/team-details.php';
        $html = (string) ob_get_clean();
        $this->assertStringContainsString( 'Team Federer', $html );
        $this->assertStringNotContainsString( 'Mens Singles Division', $html );
        $this->assertStringNotContainsString( 'match__header--up', $html );
    }

    public function test_event_championship_draw_hides_league_headers_in_real_template(): void {
        $competition = $this->create_competition( Competition_Type::LEAGUE );
        $event       = $this->create_event( $competition );
        $league      = $this->create_league( $event, [] );
        [ $fixture ] = $this->build_played_fixture( $league, $event, $competition );
        $fixture->set_league( $league );
        $fixture->teams = [
            'home' => (object) [ 'id' => 10, 'title' => 'Team Federer', 'club' => (object) [ 'id' => 50 ] ],
            'away' => (object) [ 'id' => 20, 'title' => 'Team Nadal', 'club' => (object) [ 'id' => 60 ] ],
        ];

        $competition_service = $this->createStub( Competition_Service::class );
        $competition_service->method( 'get_event_by_id' )->willReturn( $event );
        $handler = new Get_Event_Matches_Handler(
            $competition_service,
            null,
            $this->event_query_service_with_fixtures( [], [ $league ] ),
            $this->league_query_service_with_matches( [ $fixture ] )
        );
        $adapter = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            tournament_presenter: new Tournament_Presenter( 'd/m/Y', team_match_row_presenter: $this->create_team_match_row_presenter() )
        );

        $vm    = $adapter->present_matches( $handler->handle( new Get_Event_Matches_Query( event_id: 12 ) ) );
        $final = $vm->vars['event']->leagues[0]->finals[0];
        $row   = $final->matches[0];

        $this->assertInstanceOf( Team_Match_Row_View_Model::class, $row );
        $this->assertFalse( $row->show_header );

        $league = $vm->vars['event']->leagues[0];
        $finals = $league->finals;
        ob_start();
        require RACKETMANAGER_PATH . 'templates/championship-matches.php';
        $html = (string) ob_get_clean();
        $this->assertStringContainsString( 'Team Federer', $html );
        $this->assertStringNotContainsString( 'Mens Singles Division', $html );
        $this->assertStringNotContainsString( 'match__header--up', $html );
    }

    public function test_matches_list_presenter_group_by_league_converts_tournament_fixtures(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );
        $fixture->set_league( $league );

        $comp_service = $this->createStub( Competition_Service::class );
        $team_service = $this->createStub( Team_Service::class );
        $renderer     = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
        $presenter    = new Tournament_Presenter( 'd/m/Y', $this->detail_service_expecting( $fixture, $detail, true ), team_match_row_presenter: $this->create_team_match_row_presenter() );

        $list_presenter = new Matches_List_Presenter(
            $comp_service,
            $team_service,
            $renderer,
            $presenter
        );

        $grouped = $list_presenter->group_by_league( [ $fixture ] );
        $this->assertArrayHasKey( 'Mens Singles Division', $grouped );
        $this->assertInstanceOf( Team_Matches_Group_View_Model::class, $grouped['Mens Singles Division'] );
        $this->assertCount( 1, $grouped['Mens Singles Division']->matches );
        $this->assertInstanceOf( Tournament_Match_Card_View_Model::class, $grouped['Mens Singles Division']->matches[0] );
    }

    public function test_match_card_handles_team_and_club_not_found_safely(): void {
        $fixture = Fixture_Test_Factory::create( id: 708, league_id: 3 );

        $service = $this->createStub( Fixture_Detail_Service::class );
        $service->method( 'get_fixture_with_details' )->willThrowException( new Team_Not_Found_Exception( 'Team missing' ) );

        $presenter = new Tournament_Presenter( 'd/m/Y', $service, team_match_row_presenter: $this->create_team_match_row_presenter() );
        $card = $presenter->present_match_card( $fixture );

        $this->assertTrue( $card->is_missing );
        $this->assertSame( 708, $card->id );

        $service_club = $this->createStub( Fixture_Detail_Service::class );
        $service_club->method( 'get_fixture_with_details' )->willThrowException( new Club_Not_Found_Exception( 'Club missing' ) );

        $presenter_club = new Tournament_Presenter( 'd/m/Y', $service_club, team_match_row_presenter: $this->create_team_match_row_presenter() );
        $card_club = $presenter_club->present_match_card( $fixture );

        $this->assertTrue( $card_club->is_missing );
        $this->assertSame( 708, $card_club->id );
    }

    public function test_match_card_handles_invalid_argument_exception_safely(): void {
        $fixture = Fixture_Test_Factory::create( id: 709, league_id: 3 );

        $service = $this->createStub( Fixture_Detail_Service::class );
        $service->method( 'get_fixture_with_details' )->willThrowException( new \Racketmanager\Exceptions\Invalid_Argument_Exception( 'invalid' ) );

        $presenter = new Tournament_Presenter( 'd/m/Y', $service, team_match_row_presenter: $this->create_team_match_row_presenter() );
        $card = $presenter->present_match_card( $fixture );

        $this->assertTrue( $card->is_missing );
        $this->assertSame( 709, $card->id );
    }

    public function test_event_matches_renders_real_template_with_match_cards(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );
        $fixture->set_league( $league );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $presenter = new Tournament_Presenter( 'd/m/Y', $this->detail_service_expecting( $fixture, $detail, true ), team_match_row_presenter: $this->create_team_match_row_presenter() );
        $handler   = new Get_Event_Matches_Handler( $comp_service, null, $this->event_query_service_with_fixtures( [], [ $league ] ), $this->league_query_service_with_matches( [ $fixture ] ) );
        $adapter   = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            tournament_presenter: $presenter
        );
        $vm = $adapter->present_matches( $handler->handle( new Get_Event_Matches_Query( event_id: 12 ) ) );

        $this->assertSame( 'matches', $vm->template, (string) $vm->error_message );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $vm->vars['event']->leagues[0]->finals[0]->matches );

        $html = ( new Shortcode_View_Presenter( $this->renderer() ) )->render( $vm );
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        $this->assertStringNotContainsString( 'Match details unavailable', $html );
    }

    public function test_event_draw_handler_handles_championship_finals(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );
        $fixture->set_league( $league );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $handler = new Get_Event_Draw_Handler( $comp_service, null, $this->createStub( Fixture_Detail_Service::class ), $this->event_query_service_with_fixtures( [], [ $league ] ), $this->league_query_service_with_matches( [ $fixture ] ) );
        $adapter = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            competition_shortcode_presenter: new Competition_Shortcode_Presenter()
        );
        $vm      = $adapter->present_draw( $handler->handle( new Get_Event_Draw_Query( event_id: 12 ) ) );

        $this->assertSame( 'draw', $vm->template, (string) $vm->error_message );
        $this->assertSame( [ $fixture ], $vm->vars['event']->leagues[0]->finals[0]->matches );
    }

    public function test_event_clubs_renders_real_template_with_match_cards(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );
        $fixture->set_league( $league );

        // Event_Query_Service::get_club() groups the matches/results returned by the event repository by date.
        $fixture->date = '2026-06-01 10:00:00';

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event );

        $club            = $this->createStub( Club::class );
        $club->id        = 5;
        $club->name      = 'Leigh';
        $club->shortcode = 'Leigh Club';
        $club->address   = '';
        $club->method( 'get_id' )->willReturn( 5 );

        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club' )->willReturn( $club );
        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturn( [] );

        $detail_service = $this->createMock( Fixture_Detail_Service::class );
        $detail_service->expects( $this->exactly( 2 ) )
            ->method( 'get_fixture_with_details' )
            ->with( $fixture, true )
            ->willReturn( $detail );

        $presenter = new Tournament_Presenter( 'd/m/Y', $detail_service, team_match_row_presenter: $this->create_team_match_row_presenter() );
        $handler   = new Get_Event_Clubs_Handler( $comp_service, $club_service, $stats, $this->event_query_service_with_fixtures( [ $fixture ] ) );
        $adapter   = new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter(
            competition_shortcode_presenter: $this->competition_presenter(),
            tournament_presenter: $presenter
        );
        $vm = $adapter->present_clubs( $handler->handle( new Get_Event_Clubs_Query( event_id: 12, club_id: '5' ) ) );

        $this->assertSame( 'clubs', $vm->template, (string) $vm->error_message );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $vm->vars['event_club']->matches['2026-06-01']->matches );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $vm->vars['event_club']->results['2026-06-01']->matches );

        $html = ( new Shortcode_View_Presenter( $this->renderer() ) )->render( $vm );
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        $this->assertStringNotContainsString( 'Match details unavailable', $html );
    }

    public function test_competition_clubs_renders_real_template_with_match_cards(): void {
        $competition          = $this->create_competition( Competition_Type::TOURNAMENT, 1 );
        $event                = $this->create_event( $competition );
        $league               = $this->create_league( $event, [] );
        [ $fixture, $detail ] = $this->build_played_fixture( $league, $event, $competition );
        $fixture->set_league( $league );
        $fixture->date        = '2026-06-01 10:00:00';

        $club            = $this->createStub( Club::class );
        $club->id        = 5;
        $club->name      = 'Leigh';
        $club->shortcode = 'Leigh Club';
        $club->address   = '';
        $club->method( 'get_id' )->willReturn( 5 );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );
        $comp_service->method( 'get_matches_for_competition' )->willReturn( [ $fixture ] );
        $comp_service->method( 'get_teams_by_query' )->willReturn( [] );
        $comp_service->method( 'get_players_for_competition_query' )->willReturn( [] );

        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_club' )->willReturn( $club );

        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturn( [] );

        $detail_service = $this->createMock( Fixture_Detail_Service::class );
        $detail_service->expects( $this->exactly( 2 ) )
            ->method( 'get_fixture_with_details' )
            ->with( $fixture, true )
            ->willReturn( $detail );

        $presenter = new Tournament_Presenter( 'd/m/Y', $detail_service, team_match_row_presenter: $this->create_team_match_row_presenter() );
        $handler   = new Get_Competition_Clubs_Handler( $comp_service, $club_service, $stats, $this->competition_season_service() );
        $vm        = ( new \Racketmanager\Infrastructure\Wordpress\Shortcodes\Competition_Shortcode_Adapter(
            competition_shortcode_presenter: $this->competition_presenter(),
            tournament_presenter: $presenter
        ) )->present_clubs( $handler->handle( new Get_Competition_Clubs_Query( competition_id: 1, club_identifier: '5', template: '' ) ) );

        $this->assertSame( 'clubs', $vm->template, (string) $vm->error_message );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $vm->vars['competition_club']->matches['2026-06-01']->matches );
        $this->assertContainsOnlyInstancesOf( Tournament_Match_Card_View_Model::class, $vm->vars['competition_club']->results['2026-06-01']->matches );

        $html = ( new Shortcode_View_Presenter( $this->renderer() ) )->render( $vm );
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'Rafael Nadal', $html );
        $this->assertStringContainsString( '<span class="match__status winner">W</span>', $html );
        $this->assertStringNotContainsString( 'Match details unavailable', $html );
    }
}
