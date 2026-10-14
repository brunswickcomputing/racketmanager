<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Favourites_Handler;
use Racketmanager\Application\General\Queries\Get_Invoice_Handler;
use Racketmanager\Application\General\Queries\Get_Memberships_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Purchase_Order_Handler;
use Racketmanager\Application\General\Queries\Get_Team_Order_Handler;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\General_Shortcode_Adapter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

class General_Shortcode_Adapter_Test extends TestCase {
    private General_Shortcode_Adapter $adapter;
    private Get_Players_Handler $players_handler;
    private Get_Player_Handler $player_handler;
    private Get_Favourites_Handler $favourites_handler;
    private Get_Invoice_Handler $invoice_handler;
    private Get_Purchase_Order_Handler $purchase_order_handler;
    private Get_Memberships_Handler $memberships_handler;
    private Get_Player_Search_Handler $player_search_handler;
    private Get_Team_Order_Handler $team_order_handler;
    private Get_Alert_Handler $alert_handler;
    private Shortcode_View_Presenter $view_presenter;
    private Shortcode_Template_Renderer $template_renderer;
    private Shortcodes $legacy_handler;

    protected function setUp(): void {
        parent::setUp();

        $racketmanager                  = $this->createStub( RacketManager::class );
        $this->players_handler        = $this->createStub( Get_Players_Handler::class );
        $this->player_handler         = $this->createStub( Get_Player_Handler::class );
        $this->favourites_handler     = $this->createStub( Get_Favourites_Handler::class );
        $this->invoice_handler        = $this->createStub( Get_Invoice_Handler::class );
        $this->purchase_order_handler = $this->createStub( Get_Purchase_Order_Handler::class );
        $this->memberships_handler    = $this->createStub( Get_Memberships_Handler::class );
        $this->player_search_handler  = $this->createStub( Get_Player_Search_Handler::class );
        $this->team_order_handler     = $this->createStub( Get_Team_Order_Handler::class );
        $this->alert_handler          = $this->createStub( Get_Alert_Handler::class );
        $this->view_presenter         = $this->createStub( Shortcode_View_Presenter::class );
        $this->template_renderer      = $this->createStub( Shortcode_Template_Renderer::class );
        $this->legacy_handler         = $this->createStub( Shortcodes::class );

        $this->adapter = new General_Shortcode_Adapter(
            racketmanager: $racketmanager,
            view_presenter: $this->view_presenter,
            players_handler: $this->players_handler,
            player_handler: $this->player_handler,
            favourites_handler: $this->favourites_handler,
            invoice_handler: $this->invoice_handler,
            purchase_order_handler: $this->purchase_order_handler,
            memberships_handler: $this->memberships_handler,
            player_search_handler: $this->player_search_handler,
            team_order_handler: $this->team_order_handler,
            alert_handler: $this->alert_handler,
            template_renderer: $this->template_renderer
        );
    }

    public function test_show_players_renders_via_handler(): void {
        $this->players_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'players' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="players-list"></div>' );

        $output = $this->adapter->show_players( [ 'template' => 'custom' ] );
        $this->assertSame( '<div class="players-list"></div>', $output );
    }

    public function test_show_player_renders_via_handler(): void {
        $this->player_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'player' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="player-profile">John Doe</div>' );

        $output = $this->adapter->show_player( [ 'player_id' => 'john-doe' ] );
        $this->assertSame( '<div class="player-profile">John Doe</div>', $output );
    }

    public function test_show_favourites_renders_via_handler(): void {
        $this->favourites_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'form-favourites' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="favourites">Favs</div>' );

        $output = $this->adapter->show_favourites( [] );
        $this->assertSame( '<div class="favourites">Favs</div>', $output );
    }

    public function test_show_invoice_renders_via_handler(): void {
        $this->invoice_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'invoice' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="invoice">INV-100</div>' );

        $output = $this->adapter->show_invoice( [ 'id' => 100 ] );
        $this->assertSame( '<div class="invoice">INV-100</div>', $output );
    }

    public function test_show_purchase_order_renders_via_handler(): void {
        $this->purchase_order_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'purchase-order-modal' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="po-modal">PO</div>' );

        $output = $this->adapter->show_purchase_order( [ 'id' => 100, 'modal' => 'true' ] );
        $this->assertSame( '<div class="po-modal">PO</div>', $output );
    }

    public function test_show_memberships_renders_via_handler(): void {
        $this->memberships_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'player-clubs' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="memberships">Clubs</div>' );

        $output = $this->adapter->show_memberships( [] );
        $this->assertSame( '<div class="memberships">Clubs</div>', $output );
    }

    public function test_show_player_search_renders_via_handler(): void {
        $this->player_search_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'players-list' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="player-search">Results</div>' );

        $output = $this->adapter->show_player_search( [ 'search' => 'Smith' ] );
        $this->assertSame( '<div class="player-search">Results</div>', $output );
    }

    public function test_show_team_order_renders_via_handler(): void {
        $this->team_order_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'team-order' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="team-order">Order</div>' );

        $output = $this->adapter->show_team_order( [] );
        $this->assertSame( '<div class="team-order">Order</div>', $output );
    }

    public function test_show_alert_renders_via_handler(): void {
        $this->alert_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'alert' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="alert alert-info">Test message</div>' );

        $output = $this->adapter->show_alert( [ 'type' => 'info', 'msg' => 'Test message' ] );
        $this->assertSame( '<div class="alert alert-info">Test message</div>', $output );
    }

    public function test_load_template_renders_via_template_renderer(): void {
        $this->template_renderer
            ->method( 'render' )
            ->willReturn( '<div>hello</div>' );

        $output = $this->adapter->load_template( 'alert', [ 'msg' => 'hello' ], false );
        $this->assertSame( '<div>hello</div>', $output );
    }

    public function test_facade_delegates_to_adapter(): void {
        $racketmanager                  = $this->createStub( RacketManager::class );
        $container                      = new \Racketmanager\Services\Container\Simple_Container();
        $container->set( 'general_shortcode_adapter', fn() => $this->adapter );
        $racketmanager->container = $container;

        $facade = new Shortcodes( $racketmanager );

        $this->players_handler
            ->method( 'handle' )
            ->willReturn( Shortcode_View_Model::for_template( 'players' ) );
        $this->view_presenter
            ->method( 'render' )
            ->willReturn( '<div class="players">Players List</div>' );

        $this->assertSame( '<div class="players">Players List</div>', $facade->show_players( [] ) );
    }
}
