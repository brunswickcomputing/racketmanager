<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Handler;
use Racketmanager\Application\Login\Queries\Get_Login_Form_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Query;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Matches_List_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Presentation\View_Models\Matches_List_View_Model;
use Racketmanager\Presentation\View_Models\Team_Match_Row_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Request_State_Presentation_Test extends TestCase {
    use \Racketmanager\Tests\Helpers\Creates_League_Query_Service;
    use Manages_Wordpress_Globals;

    private Shortcode_Template_Renderer $renderer;

    protected function setUp(): void {
        $this->backup_globals();
        $_GET = [];
        unset( $GLOBALS['wp_query'], $GLOBALS['post'] );
        $this->renderer = new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
    }

    protected function tearDown(): void {
        $this->restore_globals();
    }

    public function test_daily_template_uses_view_model_form_target_without_query_global(): void {
        $presenter = new Matches_List_Presenter(
            $this->createStub( Competition_Service::class ),
            $this->createStub( Team_Service::class ),
            new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ),
            new Tournament_Presenter()
        );
        $vm = new Matches_List_View_Model(
            template: 'matches-daily', match_date: '2026-10-12',
            post_id: 87, form_action: 'https://example.test/daily-filter/'
        );

        $html = $presenter->render( $vm );

        self::assertStringContainsString( 'action="https://example.test/daily-filter/"', $html );
        self::assertStringContainsString( 'name="page_id" value="87"', $html );
    }

    #[DataProvider( 'login_actions' )]
    public function test_login_tab_comes_from_query_not_get( string $action, string $tab ): void {
        $options = $this->createStub( Option_Gateway_Interface::class );
        $options->method( 'get_option' )->willReturnCallback( static fn( $key, $default = null ) => 'users_can_register' === $key ? true : $default );
        $handler = new Get_Login_Form_Handler( $options, new Settings_Service( [] ) );
        $result = $handler->handle( new Get_Login_Form_Query( action: $action ) );
        $_GET['action'] = 'register' === $action ? 'login' : 'register';

        $html = $this->renderer->render( $result->template, $result->vars, $result->template_type );

        self::assertStringContainsString( 'data-tabid="' . $tab . '"', $html );
    }

    public static function login_actions(): array {
        return [ 'register' => [ 'register', 'registration' ], 'login' => [ '', 'login' ], 'unknown' => [ 'unknown', 'login' ] ];
    }

    public function test_tournament_handler_supplies_selection_form_target(): void {
        $tournament = new Tournament();
        $tournament->id = 8;
        $tournament->name = 'Summer Open';
        $tournament->season = '2026';
        $competition = $this->createStub( Competition::class );
        $competition->age_group = 'seniors';
        $service = $this->createStub( Tournament_Service::class );
        $service->method( 'get_tournament_with_details' )->willReturn( new Tournament_Details_DTO( $tournament, $competition, $this->createStub( Club::class ) ) );
        $service->method( 'get_tournaments' )->willReturn( [ $tournament ] );
        $handler = new Get_Tournament_Handler( $service, new Tournament_Presenter() );
        $result = $handler->handle( new Get_Tournament_Query( tournament: 'Summer Open', post_id: 73 ) );

        self::assertSame( 'https://example.test/page/73', $result->vars['form_action'] );
        // A theme can replace the prepared target without the partial rebuilding it.
        $vars = $result->vars;
        $vars['form_action'] = 'https://example.test/tournament-filter/';
        $html = $this->renderer->render( $result->template, $vars, $result->template_type );

        self::assertStringContainsString( 'action="https://example.test/tournament-filter/"', $html );
        self::assertStringContainsString( 'name="page_id" value="73"', $html );
    }

    public function test_league_matches_handler_supplies_selection_form_target(): void {
        $league = $this->league();
        $league->show_match_day_selection = true;
        $league->num_match_days = 3;
        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_matches' )->willReturn( [] );
        $league_query->method( 'get_league_teams' )->willReturn( [] );
        $service = $this->createStub( League_Service::class );
        $service->method( 'get_league' )->willReturn( $league );
        $handler = new Get_League_Matches_Handler( $service, $this->renderer, new Tournament_Presenter(), $league_query );
        $query = Get_League_Matches_Query::from_atts( [ 'league_id' => 1, 'template' => '', 'season' => '2026', 'post_id' => 74 ] );
        $result = $handler->handle( $query );

        self::assertSame( 74, $result->vars['post_id'] );
        self::assertSame( 'https://example.test/page/74', $result->vars['form_action'] );
        $vars = $result->vars;
        $vars['form_action'] = 'https://example.test/matches-filter/';
        $html = $this->renderer->render( $result->template, $vars, $result->template_type );

        self::assertStringContainsString( "action='https://example.test/matches-filter/'", $html );
        self::assertStringContainsString( 'name="page_id" value="74"', $html );
    }

    #[DataProvider( 'team_permissions' )]
    public function test_team_handler_prepares_permission_and_calendar( bool $logged_in, bool $admin, ?int $secretary_id, bool $can_edit ): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = $logged_in;
        $GLOBALS['wp_stubs_current_user_can'] = $admin;
        $GLOBALS['wp_stubs_get_current_user_id'] = 41;
        $team = $this->createStub( Team::class );
        $team->id = 12;
        $team->method( 'get_id' )->willReturn( 12 );
        $team->method( 'get_name' )->willReturn( 'Westcliff 1' );
        $secretary = null;
        if ( null !== $secretary_id ) {
            $secretary = $this->createStub( Player::class );
            $secretary->id = $secretary_id;
        }
        $details = new Team_Details_DTO( $team, match_secretary: $secretary );
        $league = $this->league();
        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_team_dtls' )->willReturn( (object) [ 'captain' => 'Captain', 'match_day' => '', 'match_time' => '', 'players' => [] ] );
        $league_query->method( 'get_league_team' )->willReturn( (object) [ 'rank' => 1, 'done_matches' => 0, 'points_formatted' => [ 'primary' => '0' ] ] );
        $league_query->method( 'get_matches' )->willReturn( [] );
        $league_query->method( 'get_players' )->willReturn( [] );
        $leagues = $this->createStub( League_Service::class );
        $leagues->method( 'get_league' )->willReturn( $league );
        $teams = $this->createStub( Team_Service::class );
        $teams->method( 'get_team_details' )->willReturn( $details );
        $stats = $this->createStub( Player_Stats_Service::class );
        $stats->method( 'populate_players_stats' )->willReturn( [] );
        $matches = $this->createStub( Tournament_Presenter::class );
        $matches->method( 'present_match_list' )->willReturn( [ new Team_Match_Row_View_Model( home_name: 'Westcliff 1', away_name: 'Leigh 1' ) ] );
        $handler = new Get_League_Teams_Handler(
            $leagues, $teams, $stats, $matches,
            new Team_Details_Presenter( $this->createStub( Option_Gateway_Interface::class ), new Settings_Service( [] ) ),
            $league_query
        );
        $result = $handler->handle( new Get_League_Teams_Query( league_id: 1, team_id: '12' ) );
        $vm = $result->vars['vm'];
        self::assertSame( $can_edit, $vm->user_can_edit_team );
        self::assertSame( 'https://example.test/wp-json/racketmanager/v1/export/calendar?league_id=1&team_id=12&season=2026', $vm->calendar_url );

        // Rendering must not recompute these values from user or domain state.
        $GLOBALS['wp_stubs_current_user_can'] = ! $can_edit;
        $GLOBALS['wp_stubs_is_user_logged_in'] = ! $can_edit;
        $league->current_season = [ 'name' => '2027' ];
        $html = $this->renderer->render( $result->template, $result->vars, $result->template_type );

        self::assertSame( $can_edit, str_contains( $html, 'id="teamEditLink"' ) );
        self::assertStringContainsString( 'href="' . esc_url( $vm->calendar_url ) . '"', $html );
    }

    public static function team_permissions(): array {
        return [
            'guest' => [ false, false, null, false ],
            'manager' => [ true, true, null, true ],
            'secretary' => [ true, false, 41, true ],
            'other member' => [ true, false, 42, false ],
            'no secretary' => [ true, false, null, false ],
        ];
    }

    private function league(): League {
        $league = $this->createStub( League::class );
        $league->id = 1;
        $league->title = 'Division 1';
        $league->current_season = [ 'name' => '2026' ];
        $league->is_championship = false;
        $event = $this->createStub( Event::class );
        $event->id = 5;
        $event->is_box = false;
        $event->competition = (object) [ 'type' => 'league', 'is_championship' => false, 'is_team_entry' => true, 'is_player_entry' => false ];
        $league->event = $event;
        return $league;
    }
}