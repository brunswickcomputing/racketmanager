<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Event\Queries\Get_Event_Clubs_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Draw_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Dropdown_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Partner_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Players_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Standings_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Team_Matches_Handler;
use Racketmanager\Application\Event\Queries\Get_Event_Teams_Handler;
use Racketmanager\Application\Event\Queries\Get_Team_Order_Players_Handler;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Event_Shortcode_Adapter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Event_Shortcode_Adapter_Test extends TestCase {
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

    private function create_adapter( array $overrides = [] ): Event_Shortcode_Adapter {
        return new Event_Shortcode_Adapter(
            $overrides['racketmanager'] ?? $this->createStub( RacketManager::class ),
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['event_handler'] ?? $this->createStub( Get_Event_Handler::class ),
            $overrides['standings_handler'] ?? $this->createStub( Get_Event_Standings_Handler::class ),
            $overrides['draw_handler'] ?? $this->createStub( Get_Event_Draw_Handler::class ),
            $overrides['matches_handler'] ?? $this->createStub( Get_Event_Matches_Handler::class ),
            $overrides['clubs_handler'] ?? $this->createStub( Get_Event_Clubs_Handler::class ),
            $overrides['teams_handler'] ?? $this->createStub( Get_Event_Teams_Handler::class ),
            $overrides['players_handler'] ?? $this->createStub( Get_Event_Players_Handler::class ),
            $overrides['partner_handler'] ?? $this->createStub( Get_Event_Partner_Handler::class ),
            $overrides['team_matches_handler'] ?? $this->createStub( Get_Event_Team_Matches_Handler::class ),
            $overrides['team_order_players_handler'] ?? $this->createStub( Get_Team_Order_Players_Handler::class ),
            $overrides['dropdown_handler'] ?? $this->createStub( Get_Event_Dropdown_Handler::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class )
        );
    }

    public function test_all_shortcode_methods_dispatch_to_handlers(): void {
        $methods = [
            'show_event'              => [ 'event_handler', Get_Event_Handler::class ],
            'show_event_standings'    => [ 'standings_handler', Get_Event_Standings_Handler::class ],
            'show_event_draw'         => [ 'draw_handler', Get_Event_Draw_Handler::class ],
            'show_event_matches'      => [ 'matches_handler', Get_Event_Matches_Handler::class ],
            'show_event_clubs'        => [ 'clubs_handler', Get_Event_Clubs_Handler::class ],
            'show_event_teams'        => [ 'teams_handler', Get_Event_Teams_Handler::class ],
            'show_event_players'      => [ 'players_handler', Get_Event_Players_Handler::class ],
            'show_event_partner'      => [ 'partner_handler', Get_Event_Partner_Handler::class ],
            'show_event_team_matches' => [ 'team_matches_handler', Get_Event_Team_Matches_Handler::class ],
            'show_team_order_players' => [ 'team_order_players_handler', Get_Team_Order_Players_Handler::class ],
            'show_dropdown'           => [ 'dropdown_handler', Get_Event_Dropdown_Handler::class ],
        ];

        foreach ( $methods as $method => [ $key, $class ] ) {
            $handler = $this->createMock( $class );
            $handler->expects( $this->once() )->method( 'handle' )->willReturn( Shortcode_View_Model::for_template( 'test' ) );
            $presenter = $this->createMock( Shortcode_View_Presenter::class );
            $presenter->expects( $this->once() )->method( 'render' )->willReturn( "output_for_{$method}" );

            $adapter = $this->create_adapter( [
                $key             => $handler,
                'view_presenter' => $presenter,
            ] );

            $result = $adapter->$method( [ 'id' => 10 ] );
            $this->assertSame( "output_for_{$method}", $result );
        }
    }

    public function test_normalize_atts_resolves_event_id_and_name_from_query_vars_and_globals(): void {
        global $wp, $event;

        $handler = $this->createMock( Get_Event_Handler::class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->callback( function ( $query ) {
                return 42 === $query->event_id;
            } ) )
            ->willReturn( Shortcode_View_Model::for_template( 'event' ) );

        $presenter = $this->createStub( Shortcode_View_Presenter::class );
        $presenter->method( 'render' )->willReturn( 'rendered_event' );

        $wp = (object) [ 'query_vars' => [ 'event_id' => '42' ] ];
        $adapter = $this->create_adapter( [
            'event_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $output = $adapter->show_event( [] );
        $this->assertSame( 'rendered_event', $output );

        // Test fallback to global $event and name resolution with hyphen replacement
        $wp = (object) [ 'query_vars' => [ 'event' => 'mens-singles' ] ];
        $name_handler = $this->createMock( Get_Event_Handler::class );
        $name_handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->callback( function ( $query ) {
                return 'mens singles' === $query->event_name;
            } ) )
            ->willReturn( Shortcode_View_Model::for_template( 'event' ) );

        $adapter2 = $this->create_adapter( [
            'event_handler'  => $name_handler,
            'view_presenter' => $presenter,
        ] );

        $output2 = $adapter2->show_event( [] );
        $this->assertSame( 'rendered_event', $output2 );
    }
}
