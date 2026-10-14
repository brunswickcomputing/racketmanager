<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Query;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Query;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Query;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Team;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\Public\Shortcodes_Event;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Event_Shortcode_Handlers_Test extends TestCase {
    use Creates_Test_Container;
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

    public function test_event_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $handler      = new Get_Event_Handler( $comp_service );

        // 1. Missing event id / name
        $vm_missing = $handler->handle( new Get_Event_Query() );
        $this->assertTrue( $vm_missing->is_error() );
        $this->assertSame( 'Event not found', $vm_missing->error_message );

        // 2. Event not found in service
        $comp_service->method( 'get_event_by_id' )->willThrowException( new Competition_Not_Found_Exception( 'Not found' ) );
        $vm_not_found = $handler->handle( new Get_Event_Query( event_id: 999 ) );
        $this->assertTrue( $vm_not_found->is_error() );
        $this->assertSame( 'Event not found', $vm_not_found->error_message );

        // 3. Season not found
        $comp_service_success = $this->createStub( Competition_Service::class );
        $event_stub = $this->createStub( Event::class );
        $event_stub->id   = 10;
        $event_stub->name = 'Mens Singles';
        $event_stub->type = 'MS';
        $event_stub->seasons = [ [ 'name' => '2025' ] ];
        $event_stub->current_season = [];
        $event_stub->competition = (object) [ 'id' => 1, 'is_championship' => false ];
        $event_stub->method( 'get_teams' )->willReturn( [] );

        $comp_service_success->method( 'get_event_by_id' )->willReturn( $event_stub );
        $handler_success = new Get_Event_Handler( $comp_service_success );

        $vm_no_season = $handler_success->handle( new Get_Event_Query( event_id: 10, season: '2026' ) );
        $this->assertTrue( $vm_no_season->is_error() );
        $this->assertSame( 'Season not found for event', $vm_no_season->error_message );

        // 4. Success path
        $event_stub->current_season = [ 'name' => '2025' ];
        $vm_success = $handler_success->handle( new Get_Event_Query( event_name: 'Mens Singles', tab: 'draw' ) );
        $this->assertFalse( $vm_success->is_error() );
        $this->assertSame( 'event', $vm_success->template );
        $this->assertFalse( $vm_success->template_type );
        $this->assertSame( 'draw', $vm_success->vars['tab'] );
        $this->assertSame( $event_stub, $vm_success->vars['event'] );
    }

    public function test_event_standings_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $handler      = new Get_Event_Standings_Handler( $comp_service );

        $vm_err = $handler->handle( new Get_Event_Standings_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 5;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->method( 'get_leagues' )->willReturn( [ (object) [ 'id' => 1 ] ] );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_ok = $handler->handle( new Get_Event_Standings_Query( event_id: 5 ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'standings', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertCount( 1, $event_stub->leagues );
    }

    public function test_event_draw_handler_success_and_error(): void {
        $comp_service   = $this->createStub( Competition_Service::class );
        $league_service = $this->createStub( League_Service::class );
        $handler        = new Get_Event_Draw_Handler( $comp_service, $league_service );

        $vm_err = $handler->handle( new Get_Event_Draw_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 7;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->competition = (object) [ 'is_championship' => false ];
        $event_stub->method( 'get_leagues' )->willReturn( [] );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_ok = $handler->handle( new Get_Event_Draw_Query( event_id: 7 ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'draw', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );

        // Championship template parameter
        $vm_champ = $handler->handle( new Get_Event_Draw_Query( event_id: 7, template: 'championship' ) );
        $this->assertSame( 'draw-championship', $vm_champ->template );
    }

    public function test_event_matches_handler_success_and_error(): void {
        $comp_service   = $this->createStub( Competition_Service::class );
        $league_service = $this->createStub( League_Service::class );
        $handler        = new Get_Event_Matches_Handler( $comp_service, $league_service );

        $vm_err = $handler->handle( new Get_Event_Matches_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 8;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->competition = (object) [ 'is_championship' => false ];
        $event_stub->method( 'get_leagues' )->willReturn( [] );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_ok = $handler->handle( new Get_Event_Matches_Query( event_id: 8, template: 'custom' ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'matches-custom', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
    }

    public function test_event_clubs_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $handler      = new Get_Event_Clubs_Handler( $comp_service, $club_service );

        $vm_err = $handler->handle( new Get_Event_Clubs_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 12;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->method( 'get_clubs' )->willReturn( [] );
        $event_stub->method( 'get_teams' )->willReturn( [] );
        $event_stub->method( 'get_matches' )->willReturn( [] );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        // All clubs
        $vm_all = $handler->handle( new Get_Event_Clubs_Query( event_id: 12 ) );
        $this->assertFalse( $vm_all->is_error() );
        $this->assertSame( 'clubs', $vm_all->template );
        $this->assertSame( 'event', $vm_all->template_type );

        // Specific club found by shortcode
        $club_stub = $this->createStub( Club::class );
        $club_stub->id = 101;
        $club_stub->shortcode = 'ltc';

        $club_service->method( 'get_club_by_shortcode' )->willReturn( $club_stub );
        $event_club_obj = (object) [ 'club' => $club_stub ];
        $event_stub->method( 'get_club' )->willReturn( $event_club_obj );

        $vm_club = $handler->handle( new Get_Event_Clubs_Query( event_id: 12, club_id: 'ltc' ) );
        $this->assertFalse( $vm_club->is_error() );
        $this->assertSame( $event_club_obj, $vm_club->vars['event_club'] );

        // Specific club not found
        $club_service_err = $this->createStub( Club_Service::class );
        $club_service_err->method( 'get_club' )->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );
        $handler_club_err = new Get_Event_Clubs_Handler( $comp_service, $club_service_err );
        $vm_club_err = $handler_club_err->handle( new Get_Event_Clubs_Query( event_id: 12, club_id: 999 ) );
        $this->assertTrue( $vm_club_err->is_error() );
        $this->assertSame( 'Club not found', $vm_club_err->error_message );
    }

    public function test_event_teams_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $team_service = $this->createStub( Team_Service::class );
        $handler      = new Get_Event_Teams_Handler( $comp_service, $team_service );

        $vm_err = $handler->handle( new Get_Event_Teams_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 15;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->competition = (object) [ 'is_championship' => false ];
        $event_stub->method( 'get_teams' )->willReturn( [] );
        $event_stub->method( 'get_team_info' )->willReturn( (object) [ 'name' => 'Team A' ] );
        $event_stub->method( 'get_matches' )->willReturn( [] );
        $event_stub->method( 'get_players' )->willReturn( [] );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        // All teams
        $vm_all = $handler->handle( new Get_Event_Teams_Query( event_id: 15 ) );
        $this->assertFalse( $vm_all->is_error() );
        $this->assertSame( 'teams', $vm_all->template );

        // Single team lookup
        $team_stub = $this->createStub( Team::class );
        $team_stub->id = 20;
        $team_stub->title = 'Team 1';

        $team_service->method( 'get_team_by_id' )->willReturn( $team_stub );

        $vm_team = $handler->handle( new Get_Event_Teams_Query( event_id: 15, team: '20' ) );
        $this->assertFalse( $vm_team->is_error() );
        $this->assertSame( $team_stub, $event_stub->team );

        // Championship fallback to 'list'
        $event_stub->competition->is_championship = true;
        $vm_champ = $handler->handle( new Get_Event_Teams_Query( event_id: 15, team: null ) );
        $this->assertSame( 'teams-list', $vm_champ->template );
    }

    public function test_event_players_handler_numeric_id_and_name_and_all_players(): void {
        $comp_service   = $this->createStub( Competition_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $handler        = new Get_Event_Players_Handler( $comp_service, $player_service );

        $vm_err = $handler->handle( new Get_Event_Players_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 16;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->method( 'get_players' )->willReturn( [] );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        // 1. All players
        $vm_all = $handler->handle( new Get_Event_Players_Query( event_id: 16 ) );
        $this->assertFalse( $vm_all->is_error() );
        $this->assertSame( 'players', $vm_all->template );

        // 2. Numeric player lookup - found
        $player_stub = $this->createStub( Player::class );
        $player_stub->id = 55;
        $player_stub->fullname = 'Roger Federer';
        $player_stub->method( 'get_matches' )->willReturn( [] );
        $player_stub->method( 'get_stats' )->willReturn( [] );

        $player_service->method( 'get_player' )->willReturnCallback( function ( ?int $id ) use ( $player_stub ) {
            if ( 55 === $id ) {
                return $player_stub;
            }
            throw new Player_Not_Found_Exception( 'Player not found' );
        } );

        $vm_player = $handler->handle( new Get_Event_Players_Query( event_id: 16, player_id: 55 ) );
        $this->assertFalse( $vm_player->is_error() );
        $this->assertSame( $player_stub, $event_stub->player );

        // 3. Numeric player lookup - not found
        $vm_not_found = $handler->handle( new Get_Event_Players_Query( event_id: 16, player_id: 999 ) );
        $this->assertTrue( $vm_not_found->is_error() );
        $this->assertSame( 'Player not found', $vm_not_found->error_message );

        // 4. String player name lookup - found
        $player_service->method( 'get_player_by_name' )->willReturnCallback( function ( string $name ) use ( $player_stub ) {
            if ( 'Roger Federer' === $name ) {
                return $player_stub;
            }
            return null;
        } );

        $vm_name = $handler->handle( new Get_Event_Players_Query( event_id: 16, player_id: 'Roger Federer' ) );
        $this->assertFalse( $vm_name->is_error() );
        $this->assertSame( $player_stub, $event_stub->player );

        // 5. String player name lookup - not found
        $vm_name_not_found = $handler->handle( new Get_Event_Players_Query( event_id: 16, player_id: 'Unknown Player' ) );
        $this->assertTrue( $vm_name_not_found->is_error() );
        $this->assertSame( 'Player not found', $vm_name_not_found->error_message );
    }

    public function test_event_partner_handler_gender_rules_and_modal_errors(): void {
        $comp_service   = $this->createStub( Competition_Service::class );
        $player_service = $this->createStub( Player_Service::class );
        $handler        = new Get_Event_Partner_Handler( $comp_service, $player_service );

        // 1. Missing event id -> modal error
        $vm_no_event = $handler->handle( new Get_Event_Partner_Query() );
        $this->assertTrue( $vm_no_event->is_error() );
        $this->assertSame( 'Event not found', $vm_no_event->error_message );
        $this->assertSame( 'modal', $vm_no_event->error_variant );

        // 2. Event not found -> modal error
        $comp_service->method( 'get_event_by_id' )->willThrowException( new Competition_Not_Found_Exception( 'Not found' ) );
        $vm_event_404 = $handler->handle( new Get_Event_Partner_Query( event_id: 999 ) );
        $this->assertTrue( $vm_event_404->is_error() );
        $this->assertSame( 'Event not found', $vm_event_404->error_message );
        $this->assertSame( 'modal', $vm_event_404->error_variant );

        // 3. Success path with gender rule (male event -> partner male)
        $comp_service_ok = $this->createStub( Competition_Service::class );
        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 22;
        $event_stub->type = 'MD';
        $event_stub->current_season = [ 'name' => '2025' ];

        $comp_service_ok->method( 'get_event_by_id' )->willReturn( $event_stub );

        $partner_player = $this->createStub( Player::class );
        $partner_player->id = 300;
        $partner_player->fullname = 'Partner Name';
        $player_service->method( 'get_player' )->willReturn( $partner_player );

        $handler_ok = new Get_Event_Partner_Handler( $comp_service_ok, $player_service );

        $vm_ok = $handler_ok->handle( new Get_Event_Partner_Query( event_id: 22, partner_id: 300, gender: 'M' ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'partner-modal', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertSame( 'M', $vm_ok->vars['partner_gender'] );
        $this->assertSame( 'Partner Name', $vm_ok->vars['partner_name'] );
    }

    public function test_event_team_matches_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $handler      = new Get_Event_Team_Matches_Handler( $comp_service );

        // 1. Missing event id
        $vm_no_event = $handler->handle( new Get_Event_Team_Matches_Query() );
        $this->assertTrue( $vm_no_event->is_error() );
        $this->assertSame( 'No event id supplied', $vm_no_event->error_message );

        // 2. Missing team id
        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 33;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->method( 'get_matches' )->willReturn( [ (object) [ 'id' => 1 ] ] );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_no_team = $handler->handle( new Get_Event_Team_Matches_Query( event_id: 33 ) );
        $this->assertTrue( $vm_no_team->is_error() );
        $this->assertSame( 'No team id supplied', $vm_no_team->error_message );

        // 3. Success path
        $vm_ok = $handler->handle( new Get_Event_Team_Matches_Query( event_id: 33, team_id: 5, template: 'custom' ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'team-matches-custom', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertCount( 1, $vm_ok->vars['matches'] );
    }

    public function test_team_order_players_handler_roster_and_age_limits(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $reg_service  = $this->createStub( Registration_Service::class );
        $rm           = $this->createStub( RacketManager::class );
        $rm->method( 'get_options' )->willReturn( [ 'ageLimitCheck' => 'seniors' ] );

        $handler = new Get_Team_Order_Players_Handler( $comp_service, $club_service, $reg_service, $rm );

        // 1. Missing event id
        $vm_no_event = $handler->handle( new Get_Team_Order_Players_Query() );
        $this->assertTrue( $vm_no_event->is_error() );
        $this->assertSame( 'No event id supplied', $vm_no_event->error_message );

        // 2. Missing club id
        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 44;
        $event_stub->type = 'XD';
        $event_stub->age_limit = '45';
        $event_stub->age_offset = '5';
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->method( 'get_teams' )->willReturn( [] );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_no_club = $handler->handle( new Get_Team_Order_Players_Query( event_id: 44 ) );
        $this->assertTrue( $vm_no_club->is_error() );
        $this->assertSame( 'Club id not supplied', $vm_no_club->error_message );

        // 3. Club not found
        $club_service->method( 'get_club' )->willThrowException( new Club_Not_Found_Exception( 'Club not found' ) );
        $vm_club_404 = $handler->handle( new Get_Team_Order_Players_Query( event_id: 44, club_id: 999 ) );
        $this->assertTrue( $vm_club_404->is_error() );
        $this->assertSame( 'Club not found', $vm_club_404->error_message );

        // 4. Success path with age offset calculation (min_age 45 male, 40 female)
        $club_stub = $this->createStub( Club::class );
        $club_stub->id = 200;
        $club_stub->method( 'get_id' )->willReturn( 200 );

        $club_service_ok = $this->createStub( Club_Service::class );
        $club_service_ok->method( 'get_club' )->willReturn( $club_stub );
        $club_service_ok->method( 'can_user_update_as_captain' )->willReturn( true );

        $reg_service->method( 'get_registered_players_list' )->willReturn( [ (object) [ 'id' => 1 ] ] );

        $handler_ok = new Get_Team_Order_Players_Handler( $comp_service, $club_service_ok, $reg_service, $rm );
        $vm_ok = $handler_ok->handle( new Get_Team_Order_Players_Query( event_id: 44, club_id: 200 ) );

        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'team-players-list', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertTrue( $vm_ok->vars['can_update'] );
        $this->assertArrayHasKey( 'm', $vm_ok->vars['club_players'] );
        $this->assertArrayHasKey( 'f', $vm_ok->vars['club_players'] );
    }

    public function test_partner_query_normalises_string_ids_and_handler_accepts_them(): void {
        $query = Get_Event_Partner_Query::from_atts( [ 'id' => '5', 'partner_id' => '123', 'player_id' => '7', 'tournament_id' => '9' ] );
        $this->assertSame( 123, $query->partner_id );
        $this->assertSame( 7, $query->player_id );
        $this->assertSame( 9, $query->tournament_id );

        $comp_service = $this->createStub( Competition_Service::class );
        $event_stub   = $this->createStub( Event::class );
        $event_stub->id = 5;
        $event_stub->type = 'MD';
        $event_stub->current_season = [ 'name' => '2025' ];
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $partner = $this->createStub( Player::class );
        $partner->id = 123;
        $partner->fullname = 'Partner Name';
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )->method( 'get_player' )->with( 123 )->willReturn( $partner );

        $handler = new Get_Event_Partner_Handler( $comp_service, $player_service );
        $vm      = $handler->handle( $query );
        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'partner-modal', $vm->template );
        $this->assertSame( 123, $vm->vars['partner_id'] );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider( 'age_limit_provider' )]
    public function test_team_order_players_age_logic( string $age_limit, ?int $exp_max, ?int $exp_min ): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $club_service = $this->createStub( Club_Service::class );
        $reg_service  = $this->createMock( Registration_Service::class );
        $rm           = $this->createStub( RacketManager::class );
        $rm->method( 'get_options' )->willReturn( [ 'ageLimitCheck' => true ] );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 61;
        $event_stub->type = 'BD';
        $event_stub->age_limit = $age_limit;
        $event_stub->age_offset = '5';
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->method( 'get_teams' )->willReturn( [] );
        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $club_stub = $this->createStub( Club::class );
        $club_stub->id = 200;
        $club_stub->method( 'get_id' )->willReturn( 200 );
        $club_service->method( 'get_club' )->willReturn( $club_stub );
        $club_service->method( 'can_user_update_as_captain' )->willReturn( true );

        $reg_service->expects( $this->once() )
            ->method( 'get_registered_players_list' )
            ->with( 'active', null, 200, 'm', true, $exp_max, $exp_min )
            ->willReturn( [] );

        $handler = new Get_Team_Order_Players_Handler( $comp_service, $club_service, $reg_service, $rm );
        $vm      = $handler->handle( new Get_Team_Order_Players_Query( event_id: 61, club_id: 200 ) );
        $this->assertFalse( $vm->is_error() );
    }

    public static function age_limit_provider(): array {
        return [
            'junior' => [ '16', 16, null ],
            'senior' => [ '45', null, 45 ],
        ];
    }

    public function test_event_dropdown_handler_success_and_error(): void {
        $comp_service = $this->createStub( Competition_Service::class );
        $handler      = new Get_Event_Dropdown_Handler( $comp_service );

        $vm_err = $handler->handle( new Get_Event_Dropdown_Query() );
        $this->assertTrue( $vm_err->is_error() );

        $event_stub = $this->createStub( Event::class );
        $event_stub->id = 50;
        $event_stub->current_season = [ 'name' => '2025' ];
        $event_stub->seasons = [ [ 'name' => '2025' ], [ 'name' => '2024' ] ];
        $leagues = [ (object) [ 'id' => 1 ] ];
        $event_stub->method( 'get_leagues' )->willReturn( $leagues );

        $comp_service->method( 'get_event_by_id' )->willReturn( $event_stub );

        $vm_ok = $handler->handle( new Get_Event_Dropdown_Query( event_id: 50 ) );
        $this->assertFalse( $vm_ok->is_error() );
        $this->assertSame( 'dropdown', $vm_ok->template );
        $this->assertSame( 'event', $vm_ok->template_type );
        $this->assertSame( $leagues, $vm_ok->vars['leagues'] );
    }

    public function test_renderer_sets_and_restores_global_event(): void {
        $inner_renderer = new class implements View_Renderer_Interface {
            public ?int $observed_event_id = null;
            public function render( string $template_path, object|array $view_model, array $additional_vars = [] ): void {}
            public function render_to_string( string $template_path, object|array $view_model, array $additional_vars = [] ): string {
                $this->observed_event_id = isset( $GLOBALS['event'] ) && is_object( $GLOBALS['event'] ) ? ( $GLOBALS['event']->id ?? null ) : null;
                return 'rendered-output';
            }
            public function template_exists( string $template_path ): bool {
                return true;
            }
        };

        $renderer  = new Shortcode_Template_Renderer( $inner_renderer );
        $event_obj = (object) [ 'id' => 888, 'name' => 'Global Event Test' ];

        // 1. When $event was previously set to another value
        global $event;
        $event = 'previous_event_value';

        $output = $renderer->render( 'event', [ 'event' => $event_obj ], false );
        $this->assertSame( 'rendered-output', $output );
        $this->assertSame( 888, $inner_renderer->observed_event_id );
        $this->assertSame( 'previous_event_value', $GLOBALS['event'] );

        // 2. When $event was not previously set
        unset( $GLOBALS['event'] );
        $output_unset = $renderer->render( 'event', [ 'event' => $event_obj ], false );
        $this->assertSame( 'rendered-output', $output_unset );
        $this->assertSame( 888, $inner_renderer->observed_event_id );
        $this->assertFalse( array_key_exists( 'event', $GLOBALS ) );
    }

    public function test_shortcodes_event_facade_delegates_to_adapter(): void {
        $adapter = $this->createMock( Event_Shortcode_Adapter::class );
        $adapter->expects( $this->once() )->method( 'show_event' )->willReturn( 'facade_event' );
        $adapter->expects( $this->once() )->method( 'show_event_standings' )->willReturn( 'facade_standings' );
        $adapter->expects( $this->once() )->method( 'show_event_draw' )->willReturn( 'facade_draw' );
        $adapter->expects( $this->once() )->method( 'show_event_matches' )->willReturn( 'facade_matches' );
        $adapter->expects( $this->once() )->method( 'show_event_clubs' )->willReturn( 'facade_clubs' );
        $adapter->expects( $this->once() )->method( 'show_event_teams' )->willReturn( 'facade_teams' );
        $adapter->expects( $this->once() )->method( 'show_event_players' )->willReturn( 'facade_players' );
        $adapter->expects( $this->once() )->method( 'show_event_partner' )->willReturn( 'facade_partner' );
        $adapter->expects( $this->once() )->method( 'show_event_team_matches' )->willReturn( 'facade_team_matches' );
        $adapter->expects( $this->once() )->method( 'show_team_order_players' )->willReturn( 'facade_order_players' );
        $adapter->expects( $this->once() )->method( 'show_dropdown' )->willReturn( 'facade_dropdown' );

        $racketmanager = $this->create_app_with_services( [ 'event_shortcode_adapter' => $adapter ] );

        $facade = new Shortcodes_Event( $racketmanager );

        $this->assertSame( 'facade_event', $facade->show_event( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_standings', $facade->show_event_standings( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_draw', $facade->show_event_draw( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_matches', $facade->show_event_matches( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_clubs', $facade->show_event_clubs( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_teams', $facade->show_event_teams( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_players', $facade->show_event_players( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_partner', $facade->show_event_partner( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_team_matches', $facade->show_event_team_matches( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_order_players', $facade->show_team_order_players( [ 'id' => 1 ] ) );
        $this->assertSame( 'facade_dropdown', $facade->show_dropdown( [ 'id' => 1 ] ) );
    }
}
