<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Query;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Query;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Query;
use Racketmanager\Presentation\Presenters\Invoice_Presenter;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Query;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Query;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Query;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Application\General\Queries\Get_Players_Query;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Query;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Query;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Club_Player;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\DTO\Finance\Invoice_Full_Details_DTO;
use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Domain\Invoice;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\User;
use Racketmanager\Domain\Value_Objects\Currency;
use Racketmanager\Presentation\View_Models\Club_Membership_View_Model;
use Racketmanager\Exceptions\Invoice_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Player_Query_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

class General_Shortcode_Handlers_Test extends TestCase {
    use Creates_Test_Container;
    use Manages_Wordpress_Globals;

    private RacketManager $racketmanager;
    private View_Renderer_Interface $renderer;
    private Shortcode_View_Presenter $view_presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->racketmanager             = $this->createStub( RacketManager::class );
        $this->racketmanager->site_name  = 'Test Club';
        $this->renderer                  = new class implements View_Renderer_Interface {
            public string $last_template = '';
            public array $last_vars      = [];

            public function render( string $template, object|array $model = [], array $additional_vars = [] ): void {
                $this->last_template = $template;
                $this->last_vars     = $additional_vars;
            }

            public function render_to_string( string $template, object|array $model = [], array $additional_vars = [] ): string {
                $this->last_template = $template;
                $this->last_vars     = $additional_vars;
                return "rendered:{$template}";
            }

            public function template_exists( string $template ): bool {
                return true;
            }
        };

        $template_renderer     = new Shortcode_Template_Renderer( $this->renderer );
        $this->view_presenter = new Shortcode_View_Presenter( $template_renderer );
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

        $template_renderer = new Shortcode_Template_Renderer( $this->renderer );

        $handler = new Get_Players_Handler(
            $player_repository,
            $user_repository,
            $template_renderer,
            new Player_Presenter()
        );
        $query   = new Get_Players_Query(
            template: 'custom',
            search_string: 'Smith',
            is_logged_in: true,
            user_id: 10
        );

        $vm = $handler->handle( $query );

        $this->assertSame( 'players-custom', $vm->template );
        $this->assertSame( 'Smith', $vm->vars['search_string'] );
        $this->assertIsString( $vm->vars['search_results'] );
        $this->assertSame( 'rendered:players-list', $vm->vars['search_results'] );
        $this->assertSame( [ 42 ], $vm->vars['favourites'] );
    }

    public function test_get_players_handler_without_search_and_guest(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->never() )->method( 'find_players_summary' );

        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->never() )->method( 'find' );

        $handler = new Get_Players_Handler(
            $player_repository,
            $user_repository,
            new Shortcode_Template_Renderer( $this->renderer ),
            new Player_Presenter()
        );
        $query   = new Get_Players_Query(
            template: null,
            search_string: '',
            is_logged_in: false,
            user_id: null
        );

        $vm = $handler->handle( $query );

        $this->assertSame( 'players', $vm->template );
        $this->assertSame( '', $vm->vars['search_string'] );
        $this->assertNull( $vm->vars['search_results'] );
        $this->assertSame( [], $vm->vars['favourites'] );
    }

    public function test_get_players_handler_user_not_found(): void {
        $player_repository = $this->createMock( Player_Repository_Interface::class );
        $player_repository->expects( $this->never() )->method( 'find_players_summary' );

        $user_repository = $this->createMock( User_Repository_Interface::class );
        $user_repository->expects( $this->once() )
            ->method( 'find' )
            ->with( 999 )
            ->willReturn( null );

        $handler = new Get_Players_Handler(
            $player_repository,
            $user_repository,
            new Shortcode_Template_Renderer( $this->renderer ),
            new Player_Presenter()
        );
        $query   = new Get_Players_Query(
            template: null,
            search_string: '',
            is_logged_in: true,
            user_id: 999
        );

        $vm = $handler->handle( $query );

        $this->assertSame( [], $vm->vars['favourites'] );
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

    public function test_get_players_handler_renders_real_players_list_template(): void {
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

        $handler = new Get_Players_Handler(
            $player_repository,
            $this->createStub( User_Repository_Interface::class ),
            new Shortcode_Template_Renderer( new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' ) ),
            new Player_Presenter()
        );
        $vm = $handler->handle( new Get_Players_Query( template: null, search_string: 'Smith', is_logged_in: false, user_id: null ) );

        $this->assertIsString( $vm->vars['search_results'] );
        $this->assertStringContainsString( '1 results', $vm->vars['search_results'] );
        $this->assertStringContainsString( 'id="searchResults"', $vm->vars['search_results'] );
        $this->assertStringContainsString( 'Jane Smith', $vm->vars['search_results'] );
        $this->assertStringContainsString( '/player/jane-smith/', $vm->vars['search_results'] );
    }

    public function test_get_player_handler_not_found_by_btm(): void {
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )
            ->method( 'find_player_by_btm' )
            ->with( 'BTM404' )
            ->willReturn( null );
        $player_service->expects( $this->never() )->method( 'get_player_by_name' );

        $handler = new Get_Player_Handler(
            $this->createStub( Player_Presenter::class ),
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $player_service,
            null,
            null
        );
        $vm = $handler->handle( new Get_Player_Query( template: null, player_name: null, btm: 'BTM404' ) );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Player not found', $vm->error_message );
    }

    public function test_get_player_handler_not_found(): void {
        $player_service = $this->createMock( Player_Service::class );
        $player_service->expects( $this->once() )
            ->method( 'get_player_by_name' )
            ->with( 'non-existent' )
            ->willReturn( null );

        $handler = new Get_Player_Handler(
            $this->createStub( Player_Presenter::class ),
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $player_service,
            null,
            null
        );
        $query   = new Get_Player_Query(
            template: null,
            player_name: 'non-existent',
            btm: null
        );

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Player not found', $vm->error_message );
    }

    public function test_get_player_handler_success(): void {
        $player = new Player();
        $player->id = 42;
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

        $player_presenter = new Player_Presenter( new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) ) );

        $handler = new Get_Player_Handler(
            $player_presenter,
            $player_query_service,
            $player_stats_service,
            $player_service,
            $registration_service,
            $tournament_service
        );
        $query   = new Get_Player_Query( template: 'profile', player_name: null, btm: 'BTM123' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'player-profile', $vm->template );
        $this->assertSame( $player, $vm->vars['player'] );
        $this->assertCount( 1, $vm->vars['clubs'] );
        $this->assertInstanceOf( \Racketmanager\Presentation\View_Models\Club_Link_View_Model::class, $vm->vars['clubs'][0] );
        $this->assertSame( 'Club A', $vm->vars['clubs'][0]->club_name );
        $this->assertSame( '/clubs/club-a/', $vm->vars['clubs'][0]->club_url );
        $this->assertSame( [ 'Title 1' ], $vm->vars['titles'] );
        $this->assertCount( 1, $vm->vars['player_competitions']['tournament'] );
        $this->assertInstanceOf( Competition_List_Item_View_Model::class, $vm->vars['player_competitions']['tournament'][0] );
        $this->assertSame( 'Summer Open', $vm->vars['player_competitions']['tournament'][0]->name );
        $this->assertSame( '/tournament/summer-open/player/john-smith/', $vm->vars['player_competitions']['tournament'][0]->link );
        $this->assertCount( 1, $vm->vars['player_competitions']['cup'] );
        $this->assertInstanceOf( Competition_List_Item_View_Model::class, $vm->vars['player_competitions']['cup'][0] );
        $this->assertSame( 'County Cup 2026', $vm->vars['player_competitions']['cup'][0]->name );
        $this->assertSame( '/county-cup/2026/player/john-smith/', $vm->vars['player_competitions']['cup'][0]->link );
        $this->assertCount( 1, $vm->vars['player_competitions']['league'] );
        $this->assertInstanceOf( Competition_List_Item_View_Model::class, $vm->vars['player_competitions']['league'][0] );
        $this->assertSame( 'County Cup 2026', $vm->vars['player_competitions']['league'][0]->name );
        $this->assertSame( 10, $player->stats['played'] );
    }

    public function test_get_player_handler_renders_presented_header_with_club_wtn_and_stats(): void {
        $player = new Player();
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

        $handler = new Get_Player_Handler( new Player_Presenter(), $query_service, $stats_service, $player_service, $registration_service );
        $vm      = $handler->handle( new Get_Player_Query( player_name: 'Roger Federer' ) );
        $this->assertFalse( $vm->is_error() );

        $renderer = new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
        $warnings = [];
        set_error_handler( static function ( int $severity, string $message ) use ( &$warnings ): bool {
            $warnings[] = $message;
            return true;
        } );
        try {
            $html = $renderer->render( $vm->template, $vm->vars, $vm->type ?? false );
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
        $player = new Player();
        $player->id = 55;

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player_by_name' )->willReturn( $player );
        $player_service->method( 'get_titles_for_player' )->willReturn( [] );

        $player_query_service = $this->createStub( Player_Query_Service::class );
        $player_query_service->method( 'get_competitions' )->willReturn( [] );
        $player_stats_service = $this->createStub( Player_Stats_Service::class );
        $player_stats_service->method( 'get_career_stats' )->willReturn( [] );

        $handler = new Get_Player_Handler(
            new Player_Presenter( new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) ) ),
            $player_query_service,
            $player_stats_service,
            $player_service,
            null,
            null
        );
        $query   = new Get_Player_Query( template: null, player_name: 'John Doe', btm: null );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'player', $vm->template );
        $this->assertSame( $player, $vm->vars['player'] );
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

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'You must be logged in to view favourites', $vm->error_message );
    }

    public function test_get_favourites_handler_when_logged_in(): void {
        $favourite_types = [ [ 'name' => 'player', 'favourites' => [ 10, 20 ] ] ];
        $user = $this->createStub( \Racketmanager\Domain\User::class );

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

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'form-favourites-custom', $vm->template );
        $this->assertSame( 'form', $vm->template_type );
        $this->assertSame( $favourite_types, $vm->vars['favourite_types'] );
    }

    public function test_get_invoice_handler_not_found(): void {
        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_full_invoice_details' )
            ->willThrowException( new Invoice_Not_Found_Exception( 'Invoice missing' ) );
        $settings_service = $this->createStub( Settings_Service::class );
        $option_gateway   = $this->createStub( Option_Gateway_Interface::class );

        $handler = new Get_Invoice_Handler( $finance_service, $settings_service, $option_gateway, new Invoice_Presenter() );
        $query   = new Get_Invoice_Query( id: 999 );

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'Invoice missing', $vm->error_message );
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
            ->with( 'billing', [] )
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

        $handler = new Get_Invoice_Handler( $finance_service, $settings_service, $option_gateway, new Invoice_Presenter() );
        $query   = new Get_Invoice_Query( id: 10 );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'invoice', $vm->template );
        $this->assertSame( 2026001, $vm->vars['invoice_number'] );
        $this->assertSame( [ 'billingCurrency' => 'GBP', 'vat_rate' => 20 ], $vm->vars['billing'] );
        $this->assertSame( 'Leigh & Westcliff Tennis', $vm->vars['organisation_name'] );
        $this->assertInstanceOf( \Racketmanager\Presentation\View_Models\Invoice_View_Model::class, $vm->vars['invoice'] );
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
            ->with( 'billing', [] )
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

        $handler = new Get_Invoice_Handler( $finance_service, $settings_service, $option_gateway, new Invoice_Presenter() );
        $query   = new Get_Invoice_Query( id: 20 );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'invoice', $vm->template );
        $this->assertSame( 'Leigh & Westcliff Tennis Club', $vm->vars['organisation_name'] );
        $this->assertSame( [ 'vat_number' => 'GB123456789', 'billingCurrency' => 'GBP' ], $vm->vars['billing'] );
        $this->assertInstanceOf( \Racketmanager\Presentation\View_Models\Invoice_View_Model::class, $vm->vars['invoice'] );
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

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'modal', $vm->error_variant );
    }

    public function test_get_purchase_order_handler_success(): void {
        $invoice     = $this->createStub( Invoice::class );
        $invoice->id = 5;

        $finance_service = $this->createStub( Finance_Service::class );
        $finance_service->method( 'get_invoice' )
            ->willReturn( $invoice );

        $handler = new Get_Purchase_Order_Handler( $finance_service );
        $query   = new Get_Purchase_Order_Query( id: 5, modal: 'true', template: 'dialog' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'purchase-order-modal-dialog', $vm->template );
        $this->assertSame( 'club', $vm->template_type );
        $this->assertSame( $invoice, $vm->vars['invoice'] );
        $this->assertSame( 'true', $vm->vars['modal'] );
    }

    public function test_get_purchase_order_query_parsing_with_non_numeric_id(): void {
        $query = Get_Purchase_Order_Query::from_atts( [ 'id' => 'xyz-invalid', 'modal' => 'true' ] );

        $this->assertSame( 0, $query->id );
        $this->assertSame( 'true', $query->modal );
    }

    public function test_get_memberships_handler_requires_login(): void {
        $handler = new Get_Memberships_Handler();
        $query   = new Get_Memberships_Query( is_logged_in: false, user_id: null );

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'You must be logged in to view memberships', $vm->error_message );
    }

    public function test_get_memberships_handler_success(): void {
        $player = $this->createStub( Player::class );
        $player->method( 'get_id' )->willReturn( 42 );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'get_player' )->willReturn( $player );

        $player_entity = new Player();
        $player_entity->id = 42;
        $player_entity->display_name = 'John Doe';

        $club_active = new Club();
        $club_active->id = 1;
        $club_active->name = 'Current Club Display';
        $club_active->shortcode = 'Current Club';

        $reg_active = new Club_Player( (object) [
            'id'           => 10,
            'player_id'    => 42,
            'club_id'      => 1,
            'created_date' => '2026-01-10 10:00:00',
        ] );
        $dto_active = new Club_Player_DTO( $player_entity, $club_active, $reg_active );

        $club_removed = new Club();
        $club_removed->id = 2;
        $club_removed->name = 'Old Club Display';
        $club_removed->shortcode = 'Old Club';

        $reg_removed = new Club_Player( (object) [
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

        $option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $option_gateway->method( 'get_option' )->willReturnCallback(
            fn( string $option, mixed $default = false ) => 'date_format' === $option ? 'd/m/Y' : $default
        );
        $player_presenter = new Player_Presenter( option_gateway: $option_gateway );

        $handler = new Get_Memberships_Handler( $player_service, $reg_service, $player_presenter );
        $query   = new Get_Memberships_Query( is_logged_in: true, user_id: 42, template: 'compact' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'player-clubs-compact', $vm->template );
        $this->assertSame( 'account', $vm->template_type );
        $this->assertCount( 1, $vm->vars['clubs'] );
        $this->assertInstanceOf( Club_Membership_View_Model::class, $vm->vars['clubs'][0] );
        $this->assertSame( 'Current Club', $vm->vars['clubs'][0]->club_name );
        $this->assertSame( 'Current Club Display', $vm->vars['clubs'][0]->club_display_name );
        $this->assertSame( '2026-01-10 10:00:00', $vm->vars['clubs'][0]->approval_date );
        $this->assertSame( '10/01/2026', $vm->vars['clubs'][0]->approval_date_display );
        $this->assertCount( 1, $vm->vars['clubs_archive'] );
        $this->assertInstanceOf( Club_Membership_View_Model::class, $vm->vars['clubs_archive'][0] );
        $this->assertSame( 'Old Club', $vm->vars['clubs_archive'][0]->club_name );
        $this->assertSame( 'Old Club Display', $vm->vars['clubs_archive'][0]->club_display_name );
        $this->assertSame( '2025-01-10 10:00:00', $vm->vars['clubs_archive'][0]->approval_date );
        $this->assertSame( '10/01/2025', $vm->vars['clubs_archive'][0]->approval_date_display );
        $this->assertSame( '2025-12-01 12:00:00', $vm->vars['clubs_archive'][0]->removal_date );
        $this->assertSame( '01/12/2025', $vm->vars['clubs_archive'][0]->removal_date_display );
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

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'players-list', $vm->template );
        $this->assertSame( [ $player1, $player2 ], $vm->vars['players'] );
    }

    public function test_get_team_order_handler_no_clubs(): void {
        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( [] );

        $event_repo = $this->createStub( Event_Repository_Interface::class );

        $handler = new Get_Team_Order_Handler( $club_service, $event_repo );
        $query   = new Get_Team_Order_Query();

        $vm = $handler->handle( $query );

        $this->assertTrue( $vm->is_error() );
        $this->assertSame( 'No clubs found', $vm->error_message );
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

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'team-order-entry', $vm->template );
        $this->assertNotEmpty( $vm->vars['clubs'] );
        $this->assertNotEmpty( $vm->vars['events'] );
        $this->assertIsArray( $vm->vars['event_types'] );
        $this->assertIsArray( $vm->vars['age_groups'] );
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

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'team-order-entry', $vm->template );
        $this->assertCount( 1, $vm->vars['clubs'] );
        $this->assertCount( 1, $vm->vars['events'] );
    }

    public function test_get_alert_handler_success(): void {
        $handler = new Get_Alert_Handler();
        $query   = new Get_Alert_Query( msg: 'Attention needed', type: 'warning', template: 'banner' );

        $vm = $handler->handle( $query );

        $this->assertFalse( $vm->is_error() );
        $this->assertSame( 'alert-banner', $vm->template );
        $this->assertSame( 'Attention needed', $vm->vars['msg'] );
        $this->assertSame( 'warning', $vm->vars['class'] );
    }

    public function test_real_template_alert_rendering(): void {
        $real_renderer     = new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $real_renderer );
        $presenter         = new Shortcode_View_Presenter( $template_renderer );

        $handler = new Get_Alert_Handler();
        $query   = new Get_Alert_Query( msg: 'Notice message', type: 'info' );
        $vm      = $handler->handle( $query );

        $output = $presenter->render( $vm );

        $this->assertStringContainsString( 'Notice message', $output );
        $this->assertStringContainsString( 'alert--info', $output );
    }

    public function test_real_template_players_list_rendering(): void {
        $real_renderer     = new Php_View_Renderer( dirname( __DIR__, 5 ) . '/templates/' );
        $template_renderer = new Shortcode_Template_Renderer( $real_renderer );
        $presenter         = new Shortcode_View_Presenter( $template_renderer );

        $player_repo = $this->createMock( Player_Repository_Interface::class );
        $player_repo->expects( $this->once() )
            ->method( 'find_players_summary' )
            ->with( [ 'name' => null ] )
            ->willReturn( [] );

        $handler = new Get_Player_Search_Handler( $player_repo );

        $query = new Get_Player_Search_Query();
        $vm    = $handler->handle( $query );

        $output = $presenter->render( $vm );

        $this->assertStringContainsString( 'No players found', $output );
    }

    public function test_adapter_and_facade_delegation(): void {
        $players_handler        = new Get_Players_Handler(
            $this->createStub( Player_Repository_Interface::class ),
            $this->createStub( User_Repository_Interface::class ),
            new Shortcode_Template_Renderer( $this->renderer ),
            new Player_Presenter()
        );
        $player_handler         = new Get_Player_Handler(
            $this->createStub( Player_Presenter::class ),
            $this->createStub( Player_Query_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $this->createStub( Player_Service::class ),
            null,
            null
        );
        $favourites_handler     = new Get_Favourites_Handler(
            $this->createStub( User_Repository_Interface::class ),
            $this->createStub( Club_Repository_Interface::class )
        );
        $invoice_handler        = new Get_Invoice_Handler(
            $this->createStub( Finance_Service::class ),
            $this->createStub( Settings_Service::class ),
            $this->createStub( Option_Gateway_Interface::class ),
            new Invoice_Presenter()
        );
        $purchase_order_handler = new Get_Purchase_Order_Handler();
        $memberships_handler    = new Get_Memberships_Handler();
        $player_search_handler  = new Get_Player_Search_Handler(
            $this->createStub( Player_Repository_Interface::class )
        );
        $team_order_handler     = new Get_Team_Order_Handler(
            $this->createStub( Club_Service::class ),
            $this->createStub( Event_Repository_Interface::class )
        );
        $alert_handler          = new Get_Alert_Handler();

        $adapter = new General_Shortcode_Adapter(
            $this->racketmanager,
            $this->view_presenter,
            $players_handler,
            $player_handler,
            $favourites_handler,
            $invoice_handler,
            $purchase_order_handler,
            $memberships_handler,
            $player_search_handler,
            $team_order_handler,
            $alert_handler
        );

        $this->racketmanager->container = $this->create_container_with_services( [ 'general_shortcode_adapter' => $adapter ] );

        $facade = new Shortcodes( $this->racketmanager );

        $this->assertNotEmpty( $facade->show_players( [] ) );
        $this->assertNotEmpty( $facade->show_player_search( [] ) );
        $this->assertNotEmpty( $facade->show_alert( [ 'msg' => 'Hi', 'type' => 'info' ] ) );
    }
}
