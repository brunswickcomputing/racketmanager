<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Competition;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Query;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Query;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
use Racketmanager\Tests\Helpers\Creates_League_Query_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Competition_Request_Boundary_Test extends TestCase {
    use Creates_Event_Query_Service;
    use Creates_League_Query_Service;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        $this->backup_globals();
        $_GET = $_POST = [];
        $GLOBALS['query_vars'] = [];
        unset( $GLOBALS['wp_query'] );
        $GLOBALS['wp'] = (object) [ 'query_vars' => [] ];
        unset( $GLOBALS['wp_stubs_is_admin'], $GLOBALS['paginate_links_last_args'] );
    }

    protected function tearDown(): void {
        $_GET = $_POST = [];
        $GLOBALS['query_vars'] = [];
        unset( $GLOBALS['wp_query'], $GLOBALS['paginate_links_last_args'] );
        $this->restore_globals();
        parent::tearDown();
    }

    private function league(): League {
        return League::from_object( (object) [
            'id' => 42,
            'title' => 'Test League',
            'seasons' => [ '2025' => [ 'name' => '2025' ], '2026' => [ 'name' => '2026' ] ],
        ] );
    }

    public function test_league_season_precedence_and_force_overwrite(): void {
        $service = $this->league_query_service();
        $league = $this->league();
        $_POST['season'] = '2025';
        $_GET = [ 'season' => '2026', 'season_42' => '2025' ];
        $GLOBALS['wp']->query_vars['season'] = '2026';
        $service->set_season( $league, '2026' );
        self::assertSame( '2025', $league->get_season() );
        $service->set_season( $league, '2026', true );
        self::assertSame( '2026', $league->get_season() );
        unset( $_POST['season'] );
        self::assertSame( '2026', $service->resolve_requested_season( $league ) );
        unset( $_GET['season'] );
        self::assertSame( '2025', $service->resolve_requested_season( $league ) );
        unset( $_GET['season_42'] );
        self::assertSame( '2026', $service->resolve_requested_season( $league ) );
        $GLOBALS['wp']->query_vars = [];
        $service->set_season( $league, '2025' );
        self::assertSame( '2025', $league->get_season() );
    }

    public function test_match_day_precedence_over_explicit_default(): void {
        $service = $this->league_query_service();
        $league = $this->league();
        $_GET = [ 'match_day' => '2', 'match_day_42' => '3' ];
        $_POST['match_day'] = '4';
        $GLOBALS['wp']->query_vars['match_day'] = '5';
        $service->set_match_day( $league, 'current' );
        self::assertSame( 2, $league->match_day );
        unset( $_GET['match_day'] );
        self::assertSame( 3, $service->resolve_requested_match_day( $league ) );
        unset( $_GET['match_day_42'] );
        self::assertSame( 4, $service->resolve_requested_match_day( $league ) );
        unset( $_POST['match_day'] );
        self::assertSame( 5, $service->resolve_requested_match_day( $league ) );
        $GLOBALS['wp']->query_vars = [];
        $service->set_match_day( $league, -1 );
        self::assertSame( -1, $league->match_day );
    }

    public function test_query_var_preserves_named_match_day_selectors(): void {
        $service = $this->league_query_service();
        foreach ( [ 'current', 'last' ] as $selector ) {
            $GLOBALS['wp']->query_vars['match_day'] = $selector;
            self::assertSame( $selector, $service->resolve_requested_match_day( $this->league() ) );
        }
    }

    public static function relative_match_days(): array {
        return [
            'current' => [ 'current', 'current', 2 ],
            'last'    => [ 'last', 'last', 3 ],
            'next'    => [ 'next', 'next', 4 ],
            'latest'  => [ 'latest', 'current', 5 ],
        ];
    }

    #[DataProvider( 'relative_match_days' )]
    public function test_relative_match_day_is_resolved_by_repository_before_entity_mutation( string $selector, string $repository_type, int $resolved_day ): void {
        $league = $this->league();
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( self::once() )
            ->method( 'get_match_day' )
            ->with( 42, $league->current_season['name'] ?? '', $repository_type )
            ->willReturn( $resolved_day );
        $service = $this->league_query_service( $repository );

        $service->set_match_day( $league, $selector );

        self::assertSame( $resolved_day, $league->match_day );
        self::assertSame( $resolved_day, $league->get_match_query_args()['match_day'] );
    }

    public function test_numeric_match_day_does_not_query_repository(): void {
        $league = $this->league();
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( self::never() )->method( 'get_match_day' );
        $service = $this->league_query_service( $repository );

        $service->set_match_day( $league, 6 );

        self::assertSame( 6, $league->match_day );
    }

    public static function unresolved_relative_match_days(): array {
        return [
            'next null'    => [ 'next', null ],
            'last null'    => [ 'last', null ],
            'current null' => [ 'current', null ],
            'latest null'  => [ 'latest', null ],
            'next zero'    => [ 'next', 0 ],
            'last zero'    => [ 'last', 0 ],
            'current zero' => [ 'current', 0 ],
            'latest zero'  => [ 'latest', 0 ],
        ];
    }

    #[DataProvider( 'unresolved_relative_match_days' )]
    public function test_unresolved_relative_match_day_falls_back_to_day_one( string $selector, ?int $repository_result ): void {
        $league = $this->league();
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( self::once() )->method( 'get_match_day' )->willReturn( $repository_result );
        $service = $this->league_query_service( $repository );

        $service->set_match_day( $league, $selector );

        self::assertSame( 1, $league->match_day );
        self::assertSame( 1, $league->get_match_query_args()['match_day'] );
    }

    public static function invalid_match_day_selectors(): array {
        return [
            'unknown string' => [ 'bogus' ],
            'false'          => [ false ],
            'zero int'       => [ 0 ],
            'zero string'    => [ '0' ],
        ];
    }

    #[DataProvider( 'invalid_match_day_selectors' )]
    public function test_invalid_match_day_selector_falls_back_to_day_one_without_repository_read( mixed $selector ): void {
        $league = $this->league();
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( self::never() )->method( 'get_match_day' );
        $service = $this->league_query_service( $repository );

        $service->set_match_day( $league, $selector );

        self::assertSame( 1, $league->match_day );
        self::assertSame( 1, $league->get_match_query_args()['match_day'] );
    }

    #[DataProvider( 'page_types' )]
    public function test_page_sources_and_pagination_state( string $which, string $key ): void {
        $service = $this->league_query_service();
        $league = $this->league();
        $league->num_matches = $league->num_teams_total = 100;
        $league->num_matches_per_page = $league->num_teams_per_page = 5;
        $_GET = [ $key => '2', $key . '_42' => '4' ];
        $GLOBALS['wp']->query_vars = [ $key => '3', $key . '_42' => '5' ];
        self::assertSame( 2, $service->resolve_requested_page( $league, $which ) );
        unset( $_GET[ $key ] );
        self::assertSame( 3, $service->resolve_requested_page( $league, $which ) );
        unset( $GLOBALS['wp']->query_vars[ $key ] );
        self::assertSame( 4, $service->resolve_requested_page( $league, $which ) );
        unset( $_GET[ $key . '_42' ] );
        self::assertSame( 5, $service->resolve_requested_page( $league, $which ) );
        if ( 'teams' === $which ) {
            $service->set_num_teams( $league );
        } else {
            $service->get_page_links( $league );
        }
        $service->get_page_links( $league, $which );
        self::assertSame( 5, $league->{'current_page_' . $which} );
        $GLOBALS['wp']->query_vars = [];
        self::assertSame( 1, $service->resolve_requested_page( $league, $which ) );
    }

    public static function page_types(): array {
        return [ [ 'matches', 'match_paged' ], [ 'teams', 'team_paged' ] ];
    }

    #[DataProvider( 'invalid_values' )]
    public function test_empty_and_malformed_request_values_fall_through( mixed $invalid ): void {
        $service = $this->league_query_service();
        $league = $this->league();
        $_POST['season'] = $invalid;
        $_GET = [ 'season' => $invalid, 'season_42' => '2025', 'match_day' => $invalid, 'match_day_42' => '2', 'match_paged' => $invalid, 'match_paged_42' => '3', 'final' => $invalid ];
        self::assertSame( '2025', $service->resolve_requested_season( $league ) );
        self::assertSame( 2, $service->resolve_requested_match_day( $league ) );
        self::assertSame( 3, $service->resolve_requested_page( $league ) );
        self::assertNull( $service->resolve_requested_final() );
    }

    public static function invalid_values(): array {
        return [ [ null ], [ '' ], [ '   ' ], [ [] ], [ [ '2' ] ], [ false ], [ (object) [ 'value' => 2 ] ] ];
    }

    public function test_invalid_numeric_values_do_not_become_page_or_match_day_one(): void {
        $service = $this->league_query_service();
        foreach ( [ 'garbage', '2oops', '2.5', '0', '-2' ] as $value ) {
            $_GET = [ 'match_paged' => $value, 'match_paged_42' => '3', 'match_day' => $value, 'match_day_42' => '-1' ];
            self::assertSame( 3, $service->resolve_requested_page( $this->league() ) );
            self::assertSame( -1, $service->resolve_requested_match_day( $this->league() ) );
        }
    }

    public function test_event_request_season_overrides_attribute_unless_forced(): void {
        $event = Event::from_object( (object) [ 'id' => 93, 'name' => 'Event', 'seasons' => [ '2025' => [ 'name' => '2025' ], '2026' => [ 'name' => '2026' ] ] ] );
        $service = $this->event_query_service();
        $_GET = [ 'season' => '2025', 'season_93' => '2026' ];
        $GLOBALS['wp']->query_vars['season'] = '2025';
        $service->set_season( $event, '2026' );
        self::assertSame( '2025', $event->current_season['name'] );
        $service->set_season( $event, '2026', true );
        self::assertSame( '2026', $event->current_season['name'] );
        unset( $_GET['season'] );
        self::assertSame( '2026', $service->resolve_requested_season( $event ) );
        $_GET['season_93'] = [];
        self::assertSame( '2025', $service->resolve_requested_season( $event ) );
        $GLOBALS['wp']->query_vars['season'] = [];
        self::assertNull( $service->resolve_requested_season( $event ) );
    }

    public function test_standings_handler_request_season_beats_shortcode_season(): void {
        $_GET['season'] = '2025';
        $league = $this->league();
        $service = $this->createStub( League_Service::class );
        $service->method( 'get_league' )->willReturn( $league );
        $handler = new Get_League_Standings_Handler( $service, null, $this->league_query_service() );
        $handler->handle( new Get_League_Standings_Query( league_id: 42, season: '2026' ) );
        self::assertSame( '2025', $league->get_season() );
    }

    public function test_matches_handler_uses_requested_day_and_page_for_query_and_links(): void {
        $_GET = [ 'match_day' => '2', 'match_paged' => '3' ];
        $league = $this->league();
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( self::atLeastOnce() )->method( 'query_matches' )->willReturnCallback(
            static function ( int $id, array $args, bool $count ): array|int {
                self::assertSame( 2, $args['match_day'] );
                if ( $count ) {
                    return 30;
                }
                self::assertSame( 10, $args['offset'] );
                self::assertSame( 5, $args['limit'] );
                return [];
            }
        );
        $service = $this->createStub( League_Service::class );
        $service->method( 'get_league' )->willReturn( $league );
        $handler = new Get_League_Matches_Handler( $service, $this->league_query_service( $repository ) );
        $result = $handler->handle( new Get_League_Matches_Query( league_id: 42, template: '', limit: '5' ) );
        self::assertFalse( $result->is_error() );
        self::assertSame( 3, $league->current_page_matches );
        $this->league_query_service( $repository )->get_page_links( $league );
        self::assertSame( 3, $league->current_page_matches );
    }

    public function test_requested_final_is_passed_to_championship_factory(): void {
        $_GET['final'] = 'semi';
        $league = $this->league();
        $league->event = Event::from_object( (object) [ 'id' => 93, 'name' => 'Event', 'seasons' => $league->seasons ], \Racketmanager\Tests\Helpers\Competition_Test_Factory::create() );
        $league->mode = 'championship';
        $repository = $this->createStub( League_Repository_Interface::class );
        $repository->method( 'query_league_teams' )->willReturn( 8 );
        $this->league_query_service( $repository )->set_season( $league );
        self::assertSame( 'semi', $league->championship->get_current_final_key() );
        self::assertSame( 2, $league->championship->get_finals( 'semi' )['num_matches'] );
    }

    public function test_query_service_pagination_resets_for_each_request(): void {
        $league = $this->league();
        $repository = $this->createMock( League_Repository_Interface::class );
        $offsets = [];
        $repository->expects( self::exactly( 3 ) )->method( 'query_matches' )->willReturnCallback(
            static function ( int $id, array $args ) use ( &$offsets ): array {
                $offsets[] = $args['offset'];
                return [];
            }
        );
        $service = $this->league_query_service( $repository );
        foreach ( [ [ 'match_paged' => '2' ], [ 'match_paged_42' => '3' ], [] ] as $get ) {
            $_GET = $get;
            $service->get_matches( $league, [ 'limit' => 5 ] );
        }
        self::assertSame( [ 5, 10, 0 ], $offsets );
        self::assertSame( 1, $league->current_page_matches );
    }

    public function test_query_service_preserves_default_limit_for_boolean_true(): void {
        $league = $this->league();
        $league->num_matches_per_page = 5;
        $repository = $this->createMock( League_Repository_Interface::class );
        $repository->expects( self::once() )->method( 'query_matches' )
            ->with( 42, self::callback( static fn( array $args ): bool => 5 === $args['limit'] ), false )
            ->willReturn( [] );
        $this->league_query_service( $repository )->get_matches( $league, [ 'limit' => true ] );
        self::assertSame( 5, $league->num_matches_per_page );
    }

    public function test_archive_tab_resolution_does_not_require_domain_request_access(): void {
        $league = $this->league();
        $service = $this->league_query_service();
        self::assertNull( $service->resolve_requested_tab( $league ) );
        $_GET['team_42'] = '10';
        self::assertSame( 3, $service->resolve_requested_tab( $league ) );
        $_GET['match_paged_42'] = '2';
        self::assertSame( 2, $service->resolve_requested_tab( $league ) );
        $service->set_tab( $league, true );
        self::assertSame( 2, $league->archive_tab );
    }

    public function test_pagination_links_keep_the_winning_match_day(): void {
        $_GET = [ 'match_day' => '2', 'match_paged_42' => '3' ];
        $_POST['match_day'] = '4';
        $league = $this->league();
        $league->id = 42;
        $league->num_matches = 100;
        $league->num_matches_per_page = 5;
        $links = $this->league_query_service()->get_page_links( $league );
        $args = $GLOBALS['paginate_links_last_args'] ?? [];
        self::assertArrayHasKey( 'add_args', $args );
        self::assertArrayHasKey( 'base', $args );
        self::assertStringContainsString( 'match_paged_42=%#%', urldecode( $args['base'] ) );
        self::assertArrayNotHasKey( 'match_paged_42', $args['add_args'] );
        self::assertEquals( 2, $args['add_args']['match_day'] );
    }
}