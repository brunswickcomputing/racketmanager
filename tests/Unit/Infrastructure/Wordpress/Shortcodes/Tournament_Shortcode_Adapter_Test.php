<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Tournament\DTOs\Latest_Tournament_Result;
use Racketmanager\Application\Tournament\DTOs\Order_Of_Play_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Draws_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Events_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Match_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Matches_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Overview_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Players_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Winners_Result;
use Racketmanager\Application\Tournament\DTOs\Tournament_Withdrawal_Result;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Latest_Tournament_Query;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Draws_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Events_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Match_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Matches_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Overview_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Players_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Winners_Query;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Tournament_Withdrawal_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_Entry_DTO;
use Racketmanager\Domain\DTO\Tournament\Tournament_Event_DTO;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Order_Of_Play_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;

class Tournament_Shortcode_Adapter_Test extends TestCase {
    private function create_adapter( array $overrides = [] ): Tournament_Shortcode_Adapter {
        return new Tournament_Shortcode_Adapter(
            $overrides['racketmanager'] ?? $this->createStub( RacketManager::class ),
            $overrides['view_presenter'] ?? $this->createStub( Shortcode_View_Presenter::class ),
            $overrides['tournament_handler'] ?? $this->createStub( Get_Tournament_Handler::class ),
            $overrides['overview_handler'] ?? $this->createStub( Get_Tournament_Overview_Handler::class ),
            $overrides['events_handler'] ?? $this->createStub( Get_Tournament_Events_Handler::class ),
            $overrides['draws_handler'] ?? $this->createStub( Get_Tournament_Draws_Handler::class ),
            $overrides['players_handler'] ?? $this->createStub( Get_Tournament_Players_Handler::class ),
            $overrides['winners_handler'] ?? $this->createStub( Get_Tournament_Winners_Handler::class ),
            $overrides['matches_handler'] ?? $this->createStub( Get_Tournament_Matches_Handler::class ),
            $overrides['match_handler'] ?? $this->createStub( Get_Tournament_Match_Handler::class ),
            $overrides['order_of_play_handler'] ?? $this->createStub( Get_Order_Of_Play_Handler::class ),
            $overrides['latest_tournament_handler'] ?? $this->createStub( Get_Latest_Tournament_Handler::class ),
            $overrides['withdrawal_handler'] ?? $this->createStub( Get_Tournament_Withdrawal_Handler::class ),
            $overrides['template_renderer'] ?? $this->createStub( Shortcode_Template_Renderer::class ),
            $overrides['competition_service'] ?? null,
            $overrides['tournament_presenter'] ?? null,
            $overrides['player_presenter'] ?? null,
            $overrides['order_of_play_presenter'] ?? new Order_Of_Play_Presenter(
                $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
                $this->createStub( Competition_Service::class ),
                $this->createStub( \Racketmanager\Services\Team_Service::class )
            )
        );
    }

    private function assert_delegation_to_handler_and_presenter(
        string $handler_class,
        string $query_class,
        mixed $result,
        string $rendered_output,
        string $handler_key,
        string $method,
        array $atts
    ): void {
        $handler = $this->createMock( $handler_class );
        $handler->expects( $this->once() )
            ->method( 'handle' )
            ->with( $this->isInstanceOf( $query_class ) )
            ->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->isInstanceOf( Shortcode_View_Model::class ) )
            ->willReturn( $rendered_output );

        $adapter = $this->create_adapter( [
            $handler_key     => $handler,
            'view_presenter' => $presenter,
        ] );

        $output = $adapter->$method( $atts );
        $this->assertSame( $rendered_output, $output );
    }

    public function test_show_tournament_delegates_to_handler_and_presenter(): void {
        $tournament = $this->createStub( Tournament::class );
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Handler::class,
            Get_Tournament_Query::class,
            new Tournament_Result( tournament: $tournament, template: '' ),
            '<div>Tournament Output</div>',
            'tournament_handler',
            'show_tournament',
            [ 'tournament' => 'Championship' ]
        );
    }

    public function test_show_tournament_overview_delegates_to_handler_and_presenter(): void {
        $tournament = $this->createStub( Tournament::class );
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Overview_Handler::class,
            Get_Tournament_Overview_Query::class,
            new Tournament_Overview_Result( tournament: $tournament, template: '' ),
            '<div>Overview Output</div>',
            'overview_handler',
            'show_tournament_overview',
            [ 'id' => 123 ]
        );
    }

    public function test_show_tournament_events_delegates_to_handler_and_presenter(): void {
        $tournament = $this->createStub( Tournament::class );
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Events_Handler::class,
            Get_Tournament_Events_Query::class,
            new Tournament_Events_Result( tournament: $tournament, events: [] ),
            '<div>Events Output</div>',
            'events_handler',
            'show_tournament_events',
            [ 'id' => 123 ]
        );
    }

    public function test_show_draws_delegates_to_handler_and_presenter(): void {
        $tournament = $this->createStub( Tournament::class );
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Draws_Handler::class,
            Get_Tournament_Draws_Query::class,
            new Tournament_Draws_Result( tournament: $tournament, draws: [] ),
            '<div>Draws Output</div>',
            'draws_handler',
            'show_draws',
            [ 'id' => 123 ]
        );
    }

    public function test_show_tournament_players_delegates_to_handler_and_presenter(): void {
        $tournament = $this->createStub( Tournament::class );
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Players_Handler::class,
            Get_Tournament_Players_Query::class,
            new Tournament_Players_Result( tournament: $tournament, players: [] ),
            '<div>Players Output</div>',
            'players_handler',
            'show_tournament_players',
            [ 'id' => 123 ]
        );
    }

    public function test_show_tournament_winners_delegates_to_handler_and_presenter(): void {
        $tournament = $this->createStub( Tournament::class );
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Winners_Handler::class,
            Get_Tournament_Winners_Query::class,
            new Tournament_Winners_Result( tournament: $tournament, winners: [] ),
            '<div>Winners Output</div>',
            'winners_handler',
            'show_tournament_winners',
            [ 'id' => 123 ]
        );
    }

    public function test_show_tournament_matches_delegates_to_handler_and_presenter(): void {
        $tournament     = $this->createStub( Tournament::class );
        $tournament->id = 123;
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Matches_Handler::class,
            Get_Tournament_Matches_Query::class,
            new Tournament_Matches_Result( tournament: $tournament, tournament_fixtures: [] ),
            '<div>Matches Output</div>',
            'matches_handler',
            'show_tournament_matches',
            [ 'id' => 123 ]
        );
    }

    public function test_show_tournament_match_delegates_to_handler_and_presenter(): void {
        $tournament = $this->createStub( Tournament::class );
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Match_Handler::class,
            Get_Tournament_Match_Query::class,
            new Tournament_Match_Result( tournament: $tournament ),
            '<div>Match Output</div>',
            'match_handler',
            'show_tournament_match',
            [ 'match_id' => 456 ]
        );
    }

    public function test_show_tournament_match_passes_request_referrer_to_the_query(): void {
        $this->assert_match_query_referrer( 'https://example.test/tournament/open/draw/', 'https://example.test/tournament/open/draw/' );
    }

    public function test_show_tournament_match_passes_no_referrer_when_request_has_none(): void {
        $this->assert_match_query_referrer( false, null );
    }

    private function assert_match_query_referrer( string|false $request_referrer, ?string $expected ): void {
        $previous = $GLOBALS['wp_stubs_wp_get_referer'] ?? null;
        $GLOBALS['wp_stubs_wp_get_referer'] = $request_referrer;
        try {
            $result  = new Tournament_Match_Result( tournament: $this->createStub( Tournament::class ) );
            $handler = $this->createMock( Get_Tournament_Match_Handler::class );
            $handler->expects( $this->once() )
                ->method( 'handle' )
                ->with( $this->callback( fn( Get_Tournament_Match_Query $query ): bool => $expected === $query->referrer && 456 === $query->fixture_id ) )
                ->willReturn( $result );
            $presenter = $this->createStub( Shortcode_View_Presenter::class );
            $presenter->method( 'render' )->willReturn( '<div>Match Output</div>' );

            $adapter = $this->create_adapter( [ 'match_handler' => $handler, 'view_presenter' => $presenter ] );
            $this->assertSame( '<div>Match Output</div>', $adapter->show_tournament_match( [ 'match_id' => 456 ] ) );
        } finally {
            if ( null === $previous ) {
                unset( $GLOBALS['wp_stubs_wp_get_referer'] );
            } else {
                $GLOBALS['wp_stubs_wp_get_referer'] = $previous;
            }
        }
    }

    public function test_show_order_of_play_delegates_to_handler_and_presenter(): void {
        $this->assert_delegation_to_handler_and_presenter(
            Get_Order_Of_Play_Handler::class,
            Get_Order_Of_Play_Query::class,
            new Order_Of_Play_Result( finals_order: [] ),
            '<div>Order of Play Output</div>',
            'order_of_play_handler',
            'show_order_of_play',
            [ 'id' => 123 ]
        );
    }

    public function test_show_latest_tournament_delegates_to_handler_and_presenter(): void {
        $this->assert_delegation_to_handler_and_presenter(
            Get_Latest_Tournament_Handler::class,
            Get_Latest_Tournament_Query::class,
            new Latest_Tournament_Result( redirect_url: '/tournament/active/' ),
            '<script>location.href = "/tournament/active/"</script>',
            'latest_tournament_handler',
            'show_latest_tournament',
            [ 'age_group' => 'seniors' ]
        );
    }

    public function test_show_tournament_withdrawal_modal_delegates_to_handler_and_presenter(): void {
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Withdrawal_Handler::class,
            Get_Tournament_Withdrawal_Query::class,
            new Tournament_Withdrawal_Result( tournament_id: 44 ),
            '<div class="tournament-withdrawal-modal"></div>',
            'withdrawal_handler',
            'show_tournament_withdrawal_modal',
            [ 'id' => 44 ]
        );
    }

    public function test_show_tournament_withdrawal_alias_delegates_to_modal(): void {
        $this->assert_delegation_to_handler_and_presenter(
            Get_Tournament_Withdrawal_Handler::class,
            Get_Tournament_Withdrawal_Query::class,
            new Tournament_Withdrawal_Result( tournament_id: 44 ),
            '<div class="tournament-withdrawal-modal"></div>',
            'withdrawal_handler',
            'show_tournament_withdrawal',
            [ 'id' => 44 ]
        );
    }

    public function test_show_tournament_events_renders_allowed_single_event(): void {
        $event       = new Tournament_Event_DTO( 42, 'Men Singles', [ new Tournament_Event_Entry_DTO( 101, 'Alice Example', null, null, [] ) ], 4 );
        $resolved    = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        $service     = $this->createMock( Competition_Service::class );
        $service->expects( $this->once() )
            ->method( 'resolve_competition_event' )
            ->with( $competition, $event, '2026' )
            ->willReturn( $resolved );

        $tournament = $this->createStub( Tournament::class );
        $tournament->season = '2026';
        $tournament->method( 'get_competition_object' )->willReturn( $competition );
        $tournament->method( 'is_event_allowed' )->willReturnCallback( static fn( $identifier ): bool => $event === $identifier || $resolved === $identifier );
        $result     = new Tournament_Events_Result(
            tournament: $tournament,
            event: $event
        );

        $handler = $this->createMock( Get_Tournament_Events_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->isInstanceOf( Shortcode_View_Model::class ) )
            ->willReturn( '<div>Allowed Event</div>' );

        $adapter = $this->create_adapter( [
            'events_handler'      => $handler,
            'view_presenter'      => $presenter,
            'competition_service' => $service,
        ] );

        $this->assertSame( '<div>Allowed Event</div>', $adapter->show_tournament_events( [ 'id' => 8 ] ) );
    }

    public function test_show_tournament_events_rejects_disallowed_single_event(): void {
        $event      = 'forged-event-slug';
        $tournament = $this->create_tournament_stub( false );
        $result     = new Tournament_Events_Result(
            tournament: $tournament,
            event: $event
        );

        $handler = $this->createMock( Get_Tournament_Events_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ): bool {
                return $rendered->is_error()
                    && 'Event not found for tournament' === $rendered->error_message;
            } ) )
            ->willReturn( 'error view' );

        $adapter = $this->create_adapter( [
            'events_handler' => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( 'error view', $adapter->show_tournament_events( [ 'id' => 8, 'event' => 99 ] ) );
    }

    public function test_show_tournament_events_filters_list_to_allowed_events(): void {
        $allowed    = (object) [ 'id' => 42, 'name' => 'Men Singles' ];
        $denied     = (object) [ 'id' => 99, 'name' => 'Closed Event' ];
        $tournament = $this->create_tournament_stub( null, [ 42 ] );
        $result     = new Tournament_Events_Result(
            tournament: $tournament,
            events: [ $allowed, $denied ]
        );

        $handler = $this->createMock( Get_Tournament_Events_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ) use ( $allowed ): bool {
                $events = array_values( $rendered->vars['events'] );
                return ! $rendered->is_error()
                    && 'events' === $rendered->template
                    && 1 === count( $events )
                    && ( $events[0]->id ?? null ) === $allowed->id;
            } ) )
            ->willReturn( '<div>Filtered Events</div>' );

        $adapter = $this->create_adapter( [
            'events_handler' => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( '<div>Filtered Events</div>', $adapter->show_tournament_events( [ 'id' => 8 ] ) );
    }

    public function test_show_draws_filters_list_to_allowed_draws(): void {
        $allowed    = (object) [ 'id' => 42, 'name' => 'Men Singles' ];
        $denied     = (object) [ 'id' => 99, 'name' => 'Closed Draw' ];
        $tournament = $this->create_tournament_stub( null, [ 42 ] );
        $result     = new Tournament_Draws_Result(
            tournament: $tournament,
            draws: [ $allowed, $denied ]
        );

        $handler = $this->createMock( Get_Tournament_Draws_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ) use ( $allowed ): bool {
                $draws = array_values( $rendered->vars['draws'] );
                return ! $rendered->is_error()
                    && 'draws' === $rendered->template
                    && 1 === count( $draws )
                    && ( $draws[0]->id ?? null ) === $allowed->id;
            } ) )
            ->willReturn( '<div>Filtered Draws</div>' );

        $adapter = $this->create_adapter( [
            'draws_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( '<div>Filtered Draws</div>', $adapter->show_draws( [ 'id' => 8 ] ) );
    }

    public function test_show_draws_renders_allowed_single_draw_without_replacing_view_data(): void {
        $draw        = new Tournament_Event_DTO( 42, 'Men Singles', [], 4 );
        $resolved    = $this->createStub( Event::class );
        $competition = $this->createStub( Competition::class );
        $service     = $this->createMock( Competition_Service::class );
        $service->expects( $this->once() )
            ->method( 'resolve_competition_event' )
            ->with( $competition, $draw, '2026' )
            ->willReturn( $resolved );

        $tournament = $this->createStub( Tournament::class );
        $tournament->season = '2026';
        $tournament->method( 'get_competition_object' )->willReturn( $competition );
        $tournament->method( 'is_event_allowed' )->willReturnCallback( static fn( $identifier ): bool => $draw === $identifier || $resolved === $identifier );
        $result = new Tournament_Draws_Result(
            tournament: $tournament,
            draw: $draw
        );

        $handler = $this->createMock( Get_Tournament_Draws_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->isInstanceOf( Shortcode_View_Model::class ) )
            ->willReturn( '<div>Allowed Draw</div>' );

        $adapter = $this->create_adapter( [
            'draws_handler'       => $handler,
            'view_presenter'      => $presenter,
            'competition_service' => $service,
        ] );

        $this->assertSame( '<div>Allowed Draw</div>', $adapter->show_draws( [ 'id' => 8 ] ) );
    }

    public function test_show_draws_rejects_disallowed_single_draw(): void {
        $draw       = (object) [ 'id' => 99, 'name' => 'Forged Draw' ];
        $tournament = $this->create_tournament_stub( false );
        $result     = new Tournament_Draws_Result(
            tournament: $tournament,
            draw: $draw
        );

        $handler = $this->createMock( Get_Tournament_Draws_Handler::class );
        $handler->expects( $this->once() )->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ): bool {
                return $rendered->is_error()
                    && 'Event not found for tournament' === $rendered->error_message;
            } ) )
            ->willReturn( 'error view' );

        $adapter = $this->create_adapter( [
            'draws_handler'  => $handler,
            'view_presenter' => $presenter,
        ] );

        $this->assertSame( 'error view', $adapter->show_draws( [ 'id' => 8, 'draw' => 99 ] ) );
    }

    public function test_show_tournament_events_fails_closed_for_raw_event_without_competition_service(): void {
        $competition = $this->createStub( Competition::class );
        $tournament  = $this->createStub( Tournament::class );
        $tournament->season = '2026';
        $tournament->method( 'get_competition_object' )->willReturn( $competition );
        $tournament->method( 'is_event_allowed' )->willReturn( true );
        $result = new Tournament_Events_Result(
            tournament: $tournament,
            event: 42
        );

        $handler = $this->createStub( Get_Tournament_Events_Handler::class );
        $handler->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static fn( Shortcode_View_Model $rendered ): bool =>
                $rendered->is_error() && 'Event not found for tournament' === $rendered->error_message
            ) )
            ->willReturn( 'error view' );

        $adapter = $this->create_adapter( [ 'events_handler' => $handler, 'view_presenter' => $presenter ] );

        $this->assertSame( 'error view', $adapter->show_tournament_events( [ 'id' => 8 ] ) );
    }

    public function test_show_tournament_events_rejects_raw_event_the_service_cannot_resolve(): void {
        $competition = $this->createStub( Competition::class );
        $service     = $this->createMock( Competition_Service::class );
        $service->expects( $this->once() )->method( 'resolve_competition_event' )->willReturn( null );
        $tournament = $this->createMock( Tournament::class );
        $tournament->season = '2026';
        $tournament->method( 'get_competition_object' )->willReturn( $competition );
        $tournament->expects( $this->never() )->method( 'is_event_allowed' );
        $result = new Tournament_Events_Result(
            tournament: $tournament,
            event: 1
        );

        $handler = $this->createStub( Get_Tournament_Events_Handler::class );
        $handler->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static fn( Shortcode_View_Model $rendered ): bool => $rendered->is_error() ) )
            ->willReturn( 'error view' );

        $adapter = $this->create_adapter( [
            'events_handler'      => $handler,
            'view_presenter'      => $presenter,
            'competition_service' => $service,
        ] );

        $this->assertSame( 'error view', $adapter->show_tournament_events( [ 'id' => 8 ] ) );
    }

    public function test_show_tournament_events_list_loads_candidate_events_once_and_passes_them_to_tournament(): void {
        $item_one    = (object) [ 'id' => 42, 'name' => 'Men Singles' ];
        $item_two    = (object) [ 'id' => 1, 'name' => 'Foreign' ];
        $candidates  = [ $this->createStub( Event::class ) ];
        $competition = $this->createStub( Competition::class );
        $service     = $this->createMock( Competition_Service::class );
        $service->expects( $this->once() )
            ->method( 'get_competition_events' )
            ->with( $competition )
            ->willReturn( $candidates );

        $received   = [];
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_competition_object' )->willReturn( $competition );
        $tournament->method( 'is_event_allowed' )->willReturnCallback(
            static function ( $item, $for_entry = false, $events = [] ) use ( &$received ): bool {
                $received[] = [ $item->id, $for_entry, $events ];
                return 42 === $item->id;
            }
        );
        $result = new Tournament_Events_Result(
            tournament: $tournament,
            events: [ $item_one, $item_two ]
        );

        $handler = $this->createStub( Get_Tournament_Events_Handler::class );
        $handler->method( 'handle' )->willReturn( $result );

        $presenter = $this->createMock( Shortcode_View_Presenter::class );
        $presenter->expects( $this->once() )
            ->method( 'render' )
            ->with( $this->callback( static function ( Shortcode_View_Model $rendered ) use ( $item_one ): bool {
                $events = array_values( $rendered->vars['events'] );
                return 1 === count( $events )
                    && ( $events[0]->id ?? null ) === $item_one->id;
            } ) )
            ->willReturn( 'list' );

        $adapter = $this->create_adapter( [
            'events_handler'      => $handler,
            'view_presenter'      => $presenter,
            'competition_service' => $service,
        ] );

        $this->assertSame( 'list', $adapter->show_tournament_events( [ 'id' => 8 ] ) );
        $this->assertSame( [ [ 42, false, $candidates ], [ 1, false, $candidates ] ], $received );
    }

    /**
     * @param bool|null $allow_all When true/false, always allow/deny. When null, allow only IDs in $allowed_ids.
     * @param int[]     $allowed_ids
     */
    private function create_tournament_stub( ?bool $allow_all = true, array $allowed_ids = [] ): Tournament {
        $tournament = $this->createStub( Tournament::class );
        $tournament->method( 'get_competition_object' )->willReturn( null );
        if ( null === $allow_all ) {
            $tournament->method( 'is_event_allowed' )->willReturnCallback(
                static function ( $item ) use ( $allowed_ids ): bool {
                    $id = is_object( $item ) ? (int) ( $item->id ?? 0 ) : (int) $item;
                    return in_array( $id, $allowed_ids, true );
                }
            );
        } else {
            $tournament->method( 'is_event_allowed' )->willReturn( $allow_all );
        }

        return $tournament;
    }
}
