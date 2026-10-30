<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Query;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Query;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Query;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Query;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Query;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Query;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Query;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Query;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Query;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO;
use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Domain\Finance\Invoice;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Club_Membership_View_Model;
use Racketmanager\Presentation\View_Models\Club_Link_View_Model;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Presentation\View_Models\Invoice_View_Model;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

class General_Shortcode_Handlers_Test extends TestCase {
    use Creates_Test_Container;
    use Manages_Wordpress_Globals;

    private Shortcode_Template_Renderer $template_renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->template_renderer = new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_players_handler_with_search_and_favourites(): void {
        $player = new Players_List_DTO( [
            'id'           => 42,
            'firstname'    => 'John',
            'surname'      => 'Smith',
            'display_name' => 'John Smith',
        ] );

        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )
            ->method( 'find_players_summary' )
            ->with( [ 'name' => 'Smith' ] )
            ->willReturn( [ $player ] );

        $user = $this->createStub( User::class );

        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )
            ->method( 'get_favourites' )
            ->with( $user, 'player' )
            ->willReturn( [ 42 ] );
        $user_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 10 )
            ->willReturn( $user );

        $handler = new Get_Players_Handler( $player_repository, $user_repository );
        $query   = new Get_Players_Query(
            template: 'custom',
            search_string: 'Smith',
            is_logged_in: true,
            user_id: 10
        );

        $result = $handler->handle( $query );

        $this->assertSame( 'custom', $result->template );
        $this->assertSame( 'Smith', $result->search_string );
        $this->assertSame( [ $player ], $result->players );
        $this->assertSame( [ 42 ], $result->favourites );
    }

    public function test_get_players_handler_without_search_and_guest(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->never() )->method( 'find_players_summary' );

        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->never() )->method( 'find' );

        $handler = new Get_Players_Handler( $player_repository, $user_repository );
        $query   = new Get_Players_Query(
            template: null,
            search_string: '',
            is_logged_in: false,
            user_id: null
        );

        $result = $handler->handle( $query );

        $this->assertNull( $result->template );
        $this->assertSame( '', $result->search_string );
        $this->assertSame( [], $result->players );
        $this->assertSame( [], $result->favourites );
    }

    public function test_get_players_handler_user_not_found(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->never() )->method( 'find_players_summary' );

        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 999 )
            ->willReturn( null );

        $handler = new Get_Players_Handler( $player_repository, $user_repository );
        $query   = new Get_Players_Query(
            template: null,
            search_string: '',
            is_logged_in: true,
            user_id: 999
        );

        $result = $handler->handle( $query );

        $this->assertSame( [], $result->favourites );
    }

    public function test_get_players_query_from_atts(): void {
        $query = Get_Players_Query::from_atts(
            [ 'template' => 'grid' ],
            [ 'q' => 'Jones' ],
            []
        );

        $this->assertSame( 'grid', $query->template );
        $this->assertSame( 'Jones', $query->search_string );
    }

    public function test_players_list_template_renders_search_results(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $GLOBALS['racketmanager'] = new class {
            public function is_user_favourite( string $type, int $id ): bool {
                return false;
            }
        };

        $player = new Players_List_DTO( [
            'ID'           => 42,
            'firstname'    => 'Jane',
            'surname'      => 'Smith',
            'display_name' => 'Jane Smith',
        ] );

        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->once() )
            ->method( 'find_players_summary' )
            ->with( [ 'name' => 'Smith' ] )
            ->willReturn( [ $player ] );

        $handler = new Get_Players_Handler( $player_repository, $this->createStub( User_Repository_Interface::class ) );
        $result  = $handler->handle( new Get_Players_Query( template: null, search_string: 'Smith', is_logged_in: false, user_id: null ) );

        $html = $this->template_renderer->render( 'players-list', [ 'players' => $result->players ] );

        $this->assertStringContainsString( '1 results', $html );
        $this->assertStringContainsString( 'id="searchResults"', $html );
        $this->assertStringContainsString( 'Jane Smith', $html );
        $this->assertStringContainsString( '/player/jane-smith/', $html );
    }

    public function test_get_player_handler_not_found_by_btm(): void {
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )
            ->method( 'find_player_by_btm' )
            ->with( 'BTM404' )
            ->willReturn( null );
        $player_service->expects( $this->never() )->method( 'get_player_by_name' );

        $handler = new Get_Player_Handler(
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $player_service,
            null,
            null
        );
        $result  = $handler->handle( new Get_Player_Query( template: null, player_name: null, btm: 'BTM404' ) );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Player not found', $result->error_message );
    }

    public function test_get_player_handler_not_found(): void {
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )
            ->method( 'get_player_by_name' )
            ->with( 'non-existent' )
            ->willReturn( null );

        $handler = new Get_Player_Handler(
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $player_service,
            null,
            null
        );
        $result  = $handler->handle( new Get_Player_Query( template: null, player_name: 'non-existent', btm: null ) );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Player not found', $result->error_message );
    }

    public function test_get_player_handler_success(): void {
        $player               = new Player();
        $player->id           = 42;
        $player->display_name = 'John Smith';

        $registration_service = $this->createStub( Registration_Service::class );
        $registration_service->method( 'get_clubs_for_player' )->willReturn( [ 'Club A' ] );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_titles_for_player' )->willReturn( [ 'Title 1' ] );
        $player_service->method( 'find_player_by_btm' )->willReturn( $player );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournaments_for_player' )->willReturn( [ (object) [ 'name' => 'Summer Open', 'date_start' => '2026-06-01', 'date_end' => '2026-06-07' ] ] );

        $player_query_service = $this->createStub( Player_Query_Service::class );
        $player_query_service->method( 'get_competitions' )->willReturn( [ (object) [ 'name' => 'County Cup', 'season' => '2026' ] ] );
        $player_stats_service = $this->createStub( Player_Stats_Service::class );
        $player_stats_service->method( 'get_career_stats' )->willReturn( [ 'played' => 10, 'won' => 8 ] );

        $handler = new Get_Player_Handler(
            $player_query_service,
            $player_stats_service,
            $player_service,
            $registration_service,
            $tournament_service
        );
        $result  = $handler->handle( new Get_Player_Query( template: 'profile', player_name: null, btm: 'BTM123' ) );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'profile', $result->template );
        $this->assertSame( $player, $result->player );
        $this->assertSame( [ 'Club A' ], $result->clubs );
        $this->assertSame( [ 'Title 1' ], $result->titles );
        $this->assertCount( 1, $result->player_competitions['tournament'] );
        $this->assertCount( 1, $result->player_competitions['cup'] );
        $this->assertCount( 1, $result->player_competitions['league'] );
        $this->assertSame( 10, $player->stats['played'] );
    }

    public function test_get_player_result_is_presented_as_profile_view_model(): void {
        $player               = new Player();
        $player->id           = 42;
        $player->display_name = 'John Smith';

        $registration_service = $this->createStub( Registration_Service::class );
        $registration_service->method( 'get_clubs_for_player' )->willReturn( [ 'Club A' ] );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_titles_for_player' )->willReturn( [ 'Title 1' ] );
        $player_service->method( 'find_player_by_btm' )->willReturn( $player );

        $tournament_service = $this->createStub( Tournament_Service::class );
        $tournament_service->method( 'get_tournaments_for_player' )->willReturn( [ (object) [ 'name' => 'Summer Open', 'date_start' => '2026-06-01', 'date_end' => '2026-06-07' ] ] );

        $player_query_service = $this->createStub( Player_Query_Service::class );
        $player_query_service->method( 'get_competitions' )->willReturn( [ (object) [ 'name' => 'County Cup', 'season' => '2026' ] ] );
        $player_stats_service = $this->createStub( Player_Stats_Service::class );
        $player_stats_service->method( 'get_career_stats' )->willReturn( [ 'played' => 10, 'won' => 8 ] );

        $handler = new Get_Player_Handler(
            $player_query_service,
            $player_stats_service,
            $player_service,
            $registration_service,
            $tournament_service
        );
        $result  = $handler->handle( new Get_Player_Query( template: 'profile', player_name: null, btm: 'BTM123' ) );

        $presenter = new Player_Presenter( new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) ) );
        $vars      = $presenter->present_player(
            player: $result->player,
            clubs: $result->clubs,
            titles: $result->titles,
            player_competitions: $result->player_competitions,
            is_page_header: true,
            template: 'player-profile'
        )->template_vars();

        $this->assertCount( 1, $vars['clubs'] );
        $this->assertInstanceOf( Club_Link_View_Model::class, $vars['clubs'][0] );
        $this->assertSame( 'Club A', $vars['clubs'][0]->club_name );
        $this->assertSame( '/clubs/club-a/', $vars['clubs'][0]->club_url );
        $this->assertSame( [ 'Title 1' ], $vars['titles'] );
        $this->assertInstanceOf( Competition_List_Item_View_Model::class, $vars['player_competitions']['tournament'][0] );
        $this->assertSame( 'Summer Open', $vars['player_competitions']['tournament'][0]->name );
        $this->assertSame( '/tournament/summer-open/player/john-smith/', $vars['player_competitions']['tournament'][0]->link );
        $this->assertSame( 'County Cup 2026', $vars['player_competitions']['cup'][0]->name );
        $this->assertSame( '/county-cup/2026/player/john-smith/', $vars['player_competitions']['cup'][0]->link );
        $this->assertSame( 'County Cup 2026', $vars['player_competitions']['league'][0]->name );
    }

    public function test_get_player_handler_renders_presented_header_with_club_wtn_and_stats(): void {
        $player               = new Player();
        $player->id           = 42;
        $player->firstname    = 'Roger';
        $player->surname      = 'Federer';
        $player->display_name = 'Roger Federer';
        $player->wtn          = [ 'singles' => '25.4' ];
        $player->statistics   = [
            'total' => [
                'played'       => 10,
                'matches_won'  => 8,
                'matches_lost' => 2,
                'win_pct'      => 80.0,
            ],
        ];

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player_by_name' )->willReturn( $player );
        $player_service->method( 'get_titles_for_player' )->willReturn( [] );
        $registration_service = $this->createStub( Registration_Service::class );
        $registration_service->method( 'get_clubs_for_player' )->willReturn( [ 'Club A' ] );
        $query_service = $this->createStub( Player_Query_Service::class );
        $query_service->method( 'get_competitions' )->willReturn( [] );
        $stats_service = $this->createStub( Player_Stats_Service::class );
        $stats_service->method( 'get_career_stats' )->willReturn( [] );

        $handler = new Get_Player_Handler( $query_service, $stats_service, $player_service, $registration_service );
        $result  = $handler->handle( new Get_Player_Query( player_name: 'Roger Federer' ) );
        $this->assertFalse( $result->is_error() );

        $vars = ( new Player_Presenter() )->present_player(
            player: $result->player,
            clubs: $result->clubs,
            titles: $result->titles,
            player_competitions: $result->player_competitions,
            is_page_header: true,
            template: 'player'
        )->template_vars();

        $warnings = [];
        set_error_handler( static function ( int $severity, string $message ) use ( &$warnings ): bool {
            $warnings[] = $message;
            return true;
        } );
        try {
            $html = $this->template_renderer->render( 'player', $vars );
        } finally {
            restore_error_handler();
        }

        $this->assertSame( [], $warnings );
        $this->assertStringContainsString( 'Roger Federer', $html );
        $this->assertStringContainsString( 'RF', $html );
        $this->assertStringContainsString( 'href="/clubs/club-a/"', $html );
        $this->assertStringContainsString( '25.4', $html );
        $this->assertStringContainsString( '8-2 (10)', $html );
    }

    public function test_get_player_handler_success_by_name(): void {
        $player     = new Player();
        $player->id = 55;

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player_by_name' )->willReturn( $player );
        $player_service->method( 'get_titles_for_player' )->willReturn( [] );

        $player_query_service = $this->createStub( Player_Query_Service::class );
        $player_query_service->method( 'get_competitions' )->willReturn( [] );
        $player_stats_service = $this->createStub( Player_Stats_Service::class );
        $player_stats_service->method( 'get_career_stats' )->willReturn( [] );

        $handler = new Get_Player_Handler(
            $player_query_service,
            $player_stats_service,
            $player_service,
            null,
            null
        );
        $result  = $handler->handle( new Get_Player_Query( template: null, player_name: 'John Doe', btm: null ) );

        $this->assertFalse( $result->is_error() );
        $this->assertNull( $result->template );
        $this->assertSame( $player, $result->player );
    }

    public function test_get_player_query_parsing(): void {
        $query = Get_Player_Query::from_atts(
            [ 'template' => 'card' ],
            [ 'player_id' => 'john-doe', 'btm' => 'BTM123' ]
        );

        $this->assertSame( 'card', $query->template );
        $this->assertSame( 'john doe', $query->player_name );
        $this->assertSame( 'BTM123', $query->btm );
    }

    public function test_get_favourites_handler_requires_login(): void {
        $user_repository = $this->createStub( User_Repository_Interface::class );
        $club_repository = $this->createStub( Club_Repository_Interface::class );
        $handler         = new Get_Favourites_Handler( $user_repository, $club_repository );
        $query           = new Get_Favourites_Query(
            template: null,
            is_logged_in: false,
            user_id: null
        );

        $result = $handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'You must be logged in to view favourites', $result->error_message );
    }

    public function test_get_favourites_handler_when_logged_in(): void {
        $favourite_types = [ [ 'name' => 'player', 'favourites' => [ 10, 20 ] ] ];
        $user            = $this->createStub( User::class );

        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 42 )
            ->willReturn( $user );
        $user_repository->method( 'get_favourites' )->willReturn( $favourite_types );
        $club_repository = $this->createStub( Club_Repository_Interface::class );

        $handler = new Get_Favourites_Handler( $user_repository, $club_repository );
        $query   = new Get_Favourites_Query(
            template: 'custom',
            is_logged_in: true,
            user_id: 42
        );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'custom', $result->template );
        $this->assertSame( $favourite_types, $result->favourite_types );
    }

    public function test_get_invoice_handler_not_found(): void {
        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_full_invoice_details' )
            ->willThrowException( new Invoice_Not_Found_Exception( 'Invoice missing' ) );
        $settings_service = $this->createStub( Settings_Service::class );
        $option_gateway   = $this->createStub( Option_Gateway_Interface::class );

        $handler = new Get_Invoice_Handler( $finance_service, $settings_service, $option_gateway );
        $query   = new Get_Invoice_Query( id: 999 );

        $result = $handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Invoice missing', $result->error_message );
    }

    public function test_get_invoice_handler_success(): void {
        $invoice = $this->createStub( Invoice::class );
        $invoice->method( 'get_invoice_number' )->willReturn( 2026001 );

        $invoice_dto          = $this->createStub( Invoice_Full_Details_DTO::class );
        $invoice_dto->invoice = $invoice;

        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_full_invoice_details' )
            ->willReturn( $invoice_dto );
        $finance_service->method( 'get_currency' )->willReturn( Currency::GBP );

        $settings_service = $this->createMock( Settings_Service::class );
        $settings_service->expects( $this->once() )
            ->method( 'get_category' )
            ->with( 'billing' )
            ->willReturn( [ 'billingCurrency' => 'GBP', 'vat_rate' => 20 ] );

        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )
            ->willReturnCallback( function ( $key, $default = null ) {
                return match ( $key ) {
                    'blogname'    => 'Leigh &amp; Westcliff Tennis',
                    'siteurl'     => 'https://leighleague.org',
                    'date_format' => 'd-m-Y',
                    default       => $default,
                };
            } );

        $handler = new Get_Invoice_Handler( $finance_service, $settings_service, $option_gateway );
        $query   = new Get_Invoice_Query( id: 10 );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( '2026001', $result->invoice_number );
        $this->assertSame( [ 'billingCurrency' => 'GBP', 'vat_rate' => 20 ], $result->billing );
        $this->assertSame( 'Leigh & Westcliff Tennis', $result->organisation_name );
        $this->assertSame( 'https://leighleague.org', $result->site_url );
        $this->assertSame( 'd-m-Y', $result->date_format );
        $this->assertSame( $invoice_dto, $result->invoice );
    }

    public function test_get_invoice_result_is_presented_as_invoice_view_model(): void {
        $invoice = $this->createStub( Invoice::class );
        $invoice->method( 'get_invoice_number' )->willReturn( 2026001 );

        $invoice_dto          = $this->createStub( Invoice_Full_Details_DTO::class );
        $invoice_dto->invoice = $invoice;

        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_full_invoice_details' )->willReturn( $invoice_dto );
        $finance_service->method( 'get_currency' )->willReturn( Currency::GBP );

        $settings_service = $this->createStub( Settings_Service::class );
        $settings_service->method( 'get_category' )->willReturn( [] );

        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            fn( $key, $default = null ) => 'date_format' === $key ? 'd-m-Y' : ( 'blogname' === $key ? 'Leigh Tennis' : $default )
        );

        $result = ( new Get_Invoice_Handler( $finance_service, $settings_service, $option_gateway ) )->handle( new Get_Invoice_Query( id: 10 ) );

        $invoice_vm = ( new Invoice_Presenter() )->present_invoice(
            invoice: $result->invoice,
            billing: $result->billing,
            organisation_name: $result->organisation_name,
            site_url: $result->site_url,
            date_format: $result->date_format,
            is_user_logged_in: $result->is_user_logged_in,
            login_url: $result->login_url
        );

        $this->assertInstanceOf( Invoice_View_Model::class, $invoice_vm );
    }

    public function test_get_invoice_handler_with_injected_settings_and_gateway(): void {
        $invoice = $this->createStub( Invoice::class );
        $invoice->method( 'get_invoice_number' )->willReturn( 2026002 );

        $invoice_dto          = $this->createStub( Invoice_Full_Details_DTO::class );
        $invoice_dto->invoice = $invoice;

        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_full_invoice_details' )
            ->willReturn( $invoice_dto );
        $finance_service->method( 'get_currency' )->willReturn( Currency::GBP );

        $settings_service = $this->createMock( Settings_Service::class );
        $settings_service->expects( $this->once() )
            ->method( 'get_category' )
            ->with( 'billing' )
            ->willReturn( [ 'vat_number' => 'GB123456789' ] );

        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )
            ->willReturnCallback( function ( $key, $default = null ) {
                return match ( $key ) {
                    'blogname'    => 'Leigh & Westcliff Tennis Club',
                    'siteurl'     => 'https://leighleague.org',
                    'date_format' => 'd-m-Y',
                    default       => $default,
                };
            } );

        $handler = new Get_Invoice_Handler( $finance_service, $settings_service, $option_gateway );
        $query   = new Get_Invoice_Query( id: 20 );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( '2026002', $result->invoice_number );
        $this->assertSame( 'Leigh & Westcliff Tennis Club', $result->organisation_name );
        $this->assertSame( [ 'vat_number' => 'GB123456789', 'billingCurrency' => 'GBP' ], $result->billing );
    }

    public function test_get_invoice_query_parsing_with_non_numeric_id(): void {
        $query = Get_Invoice_Query::from_atts( [ 'id' => 'abc-invalid', 'template' => 'custom' ] );

        $this->assertSame( 0, $query->id );
        $this->assertSame( 'custom', $query->template );
    }

    public function test_get_purchase_order_handler_modal_error(): void {
        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_invoice' )
            ->willThrowException( new Invoice_Not_Found_Exception( 'PO not found' ) );

        $handler = new Get_Purchase_Order_Handler( $finance_service );
        $query   = new Get_Purchase_Order_Query( id: 5, modal: 'true' );

        $result = $handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'PO not found', $result->error_message );
    }

    public function test_get_purchase_order_handler_success(): void {
        $invoice     = $this->createStub( Invoice::class );
        $invoice->id = 5;

        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_invoice' )
            ->willReturn( $invoice );

        $handler = new Get_Purchase_Order_Handler( $finance_service );
        $query   = new Get_Purchase_Order_Query( id: 5, modal: 'true', template: 'dialog' );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'dialog', $result->template );
        $this->assertSame( $invoice, $result->invoice );
        $this->assertSame( 'true', $result->modal );
    }

    public function test_get_purchase_order_query_parsing_with_non_numeric_id(): void {
        $query = Get_Purchase_Order_Query::from_atts( [ 'id' => 'xyz-invalid', 'modal' => 'true' ] );

        $this->assertSame( 0, $query->id );
        $this->assertSame( 'true', $query->modal );
    }

    public function test_get_memberships_handler_requires_login(): void {
        $handler = new Get_Memberships_Handler();
        $query   = new Get_Memberships_Query( is_logged_in: false, user_id: null );

        $result = $handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'You must be logged in to view memberships', $result->error_message );
    }

    public function test_get_memberships_handler_success(): void {
        $player = $this->createStub( Player::class );
        $player->method( 'get_id' )->willReturn( 42 );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );

        $player_entity               = new Player();
        $player_entity->id           = 42;
        $player_entity->display_name = 'John Doe';

        $club_active               = new Club();
        $club_active->id           = 1;
        $club_active->name         = 'Current Club Display';
        $club_active->shortcode    = 'Current Club';

        $reg_active = Club_Player::from_object( (object) [
            'id'           => 10,
            'player_id'    => 42,
            'club_id'      => 1,
            'created_date' => '2026-01-10 10:00:00',
        ] );
        $dto_active = new Club_Player_DTO( $player_entity, $club_active, $reg_active );

        $club_removed            = new Club();
        $club_removed->id        = 2;
        $club_removed->name      = 'Old Club Display';
        $club_removed->shortcode = 'Old Club';

        $reg_removed = Club_Player::from_object( (object) [
            'id'           => 11,
            'player_id'    => 42,
            'club_id'      => 2,
            'created_date' => '2025-01-10 10:00:00',
            'removed_date' => '2025-12-01 12:00:00',
        ] );
        $dto_removed = new Club_Player_DTO( $player_entity, $club_removed, $reg_removed );

        $reg_service = $this->createStub( Registration_Service::class );
        $reg_service->method( 'get_clubs_for_player' )->willReturnCallback( function ( $id, $type = null ) use ( $dto_active, $dto_removed ) {
            return 'removed' === $type ? [ $dto_removed ] : [ $dto_active ];
        } );

        $handler = new Get_Memberships_Handler( $player_service, $reg_service );
        $query   = new Get_Memberships_Query( is_logged_in: true, user_id: 42, template: 'compact' );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'compact', $result->template );
        $this->assertSame( [ $dto_active ], $result->clubs );
        $this->assertSame( [ $dto_removed ], $result->clubs_archive );
    }

    public function test_memberships_are_presented_as_club_membership_view_models(): void {
        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            fn( string $option, mixed $default = false ) => 'date_format' === $option ? 'd/m/Y' : $default
        );
        $presenter = new Player_Presenter( option_gateway: $option_gateway );

        $player_entity               = new Player();
        $player_entity->id           = 42;
        $player_entity->display_name = 'John Doe';

        $club_active            = new Club();
        $club_active->id        = 1;
        $club_active->name      = 'Current Club Display';
        $club_active->shortcode = 'Current Club';

        $reg_active = Club_Player::from_object( (object) [
            'id'           => 10,
            'player_id'    => 42,
            'club_id'      => 1,
            'created_date' => '2026-01-10 10:00:00',
        ] );
        $dto_active = new Club_Player_DTO( $player_entity, $club_active, $reg_active );

        $vm = $presenter->present_club_membership( $dto_active );

        $this->assertInstanceOf( Club_Membership_View_Model::class, $vm );
        $this->assertSame( 'Current Club', $vm->club_name );
        $this->assertSame( 'Current Club Display', $vm->club_display_name );
        $this->assertSame( '2026-01-10 10:00:00', $vm->approval_date );
        $this->assertSame( '10/01/2026', $vm->approval_date_display );
    }

    public function test_get_player_search_handler_success(): void {
        $player1 = new Players_List_DTO( (object) [
            'id'           => 1,
            'display_name' => 'Roger Federer',
        ] );

        $player2 = new Players_List_DTO( (object) [
            'id'           => 2,
            'display_name' => 'Roger Rasheed',
        ] );

        $player_repo = $this->createMock( Player_Repository_Interface::class );
        $player_repo->expects( $this->once() )
            ->method( 'find_players_summary' )
            ->with( [ 'name' => 'Roger' ] )
            ->willReturn( [ $player1, $player2 ] );

        $handler = new Get_Player_Search_Handler( $player_repo );
        $query   = new Get_Player_Search_Query( search: 'Roger' );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( [ $player1, $player2 ], $result->players );
    }

    public function test_get_team_order_handler_no_clubs(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( [] );

        $event_repo = $this->createStub( Event_Repository_Interface::class );

        $handler = new Get_Team_Order_Handler( $club_service, $event_repo );
        $query   = new Get_Team_Order_Query();

        $result = $handler->handle( $query );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'No clubs found', $result->error_message );
    }

    public function test_get_team_order_handler_success(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( [ new stdClass() ] );

        $event_repo = $this->createMock( Event_Repository_Interface::class );
        $event_repo->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'entry_type' => 'team', 'reverse_rubbers' => true ] )
            ->willReturn( [ new stdClass() ] );

        $handler = new Get_Team_Order_Handler( $club_service, $event_repo );
        $query   = new Get_Team_Order_Query( template: 'entry' );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'entry', $result->template );
        $this->assertNotEmpty( $result->clubs );
        $this->assertNotEmpty( $result->events );
        $this->assertIsArray( $result->event_types );
        $this->assertIsArray( $result->age_groups );
    }

    public function test_get_team_order_handler_with_injected_event_repository(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( [ new stdClass() ] );

        $event_repo = $this->createMock( Event_Repository_Interface::class );
        $event_repo->expects( $this->once() )
            ->method( 'find_all' )
            ->with( [ 'entry_type' => 'team', 'reverse_rubbers' => true ] )
            ->willReturn( [ (object) [ 'id' => 10, 'name' => 'Men Division 1' ] ] );

        $handler = new Get_Team_Order_Handler( $club_service, $event_repo );
        $query   = new Get_Team_Order_Query( template: 'entry' );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertCount( 1, $result->clubs );
        $this->assertCount( 1, $result->events );
    }

    public function test_get_alert_handler_success(): void {
        $handler = new Get_Alert_Handler();
        $query   = new Get_Alert_Query( msg: 'Attention needed', type: 'warning', template: 'banner' );

        $result = $handler->handle( $query );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( 'banner', $result->template );
        $this->assertSame( 'Attention needed', $result->msg );
        $this->assertSame( 'warning', $result->type );
    }

    public function test_real_template_alert_rendering(): void {
        $result = ( new Get_Alert_Handler() )->handle( new Get_Alert_Query( msg: 'Notice message', type: 'info' ) );

        $output = $this->template_renderer->render( 'alert', [
            'msg'   => $result->msg,
            'class' => $result->type,
        ] );

        $this->assertStringContainsString( 'Notice message', $output );
        $this->assertStringContainsString( 'alert--info', $output );
    }

    public function test_real_template_players_list_rendering(): void {
        $player_repo = $this->createMock( Player_Repository_Interface::class );
        $player_repo->expects( $this->once() )
            ->method( 'find_players_summary' )
            ->with( [ 'name' => null ] )
            ->willReturn( [] );

        $result = ( new Get_Player_Search_Handler( $player_repo ) )->handle( new Get_Player_Search_Query() );

        $output = $this->template_renderer->render( 'players-list', [ 'players' => $result->players ] );

        $this->assertStringContainsString( 'No players found', $output );
    }
}
