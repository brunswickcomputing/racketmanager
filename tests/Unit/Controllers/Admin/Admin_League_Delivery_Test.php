<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_League;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Competition\Competition_Overview_DTO;
use Racketmanager\RacketManager;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

final class Admin_League_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use \Racketmanager\Tests\Helpers\Creates_Event_Query_Service;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Competition_Service $competition_service;
    private \Racketmanager\Services\Season_Service $season_service;
    private Admin_League $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->container           = $this->create_admin_delivery_container();
        $this->competition_service = $this->createStub( Competition_Service::class );
        $this->season_service      = $this->createStub( \Racketmanager\Services\Season_Service::class );
        $this->season_service->method( 'get_all_seasons' )->willReturn( array( '2026' ) );
        $this->container->set( 'competition_service', $this->competition_service );
        $this->container->set( 'season_service', $this->season_service );

        $plugin                   = $this->createStub( RacketManager::class );
        $plugin->container        = $this->container;
        $GLOBALS['racketmanager'] = $plugin;

        $this->screen = new Admin_League( $plugin );
    }

    private function rebuild_screen(): void {
        $plugin            = $this->createStub( RacketManager::class );
        $plugin->container = $this->container;
        $this->screen      = new Admin_League( $plugin );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState( false )]
    public function test_match_edit_page_uses_requested_final_round(): void {
        $_GET = array( 'league_id' => '42', 'season' => '2026', 'final' => 'semi', 'mode' => 'edit' );
        $_POST = array();
        $competition = Competition_Test_Factory::create( id: 1, type: Competition_Type::CUP );
        $event = \Racketmanager\Domain\Competition\Event::from_object( (object) array(
            'id' => 93,
            'name' => 'Event',
            'seasons' => array( '2026' => array( 'name' => '2026' ) ),
        ), $competition );
        $league = \Racketmanager\Domain\Competition\League::from_object( (object) array( 'id' => 42, 'title' => 'Finals' ), $event );
        $league->mode = 'championship';
        $league->num_teams_total = 8;
        $league->set_season( '2026' );
        $GLOBALS['wp_stubs_leagues'][42] = $league;
        $repository = $this->createMock( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $repository->method( 'query_league_teams' )->willReturn( 8 );
        $repository->expects( self::once() )->method( 'query_matches' )
            ->with( 42, self::callback( static fn( array $args ): bool => 'semi' === $args['final'] ), false )
            ->willReturn( array() );
        $this->container->set( 'league_query_service', new \Racketmanager\Services\Competition\League_Query_Service(
            $repository,
            $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class )
        ) );
        $this->rebuild_screen();
        ob_start();
        try {
            $this->screen->display_match_page();
            $html = (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
        self::assertSame( 'semi', $league->championship->get_current_final_key() );
        self::assertStringContainsString( 'Edit Matches - Semi', $html );
    }

    public function test_display_overview_page_renders_entries_with_entry_urls(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-leagues', 'view' => 'overview', 'competition_id' => '1', 'season' => '2026' );

        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Mixed League',
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_map(
                '2026',
                array(
                    'date_closing' => '2026-12-31',
                    'entries'      => array(),
                )
            )
        );
        $overview = new Competition_Overview_DTO(
            (object) array(
                'id'          => 1,
                'name'        => 'Mixed League',
                'settings'    => wp_json_encode( array( 'competition_code' => 'ML' ) ),
                'num_events'  => 0,
                'num_teams'   => 0,
                'num_clubs'   => 1,
                'num_players' => 0,
            )
        );
        $entries = array(
            (object) array(
                'id'         => 1,
                'shortcode'  => 'WTC',
                'team_count' => 2,
            ),
        );

        $competition_service = $this->createMock( Competition_Service::class );
        $this->container->set( 'competition_service', $competition_service );
        $plugin            = $this->createStub( RacketManager::class );
        $plugin->container = $this->container;
        $screen            = new Admin_League( $plugin );

        $competition_service->expects( self::once() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );
        $competition_service->expects( self::once() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );
        $competition_service->expects( self::once() )
            ->method( 'get_events_with_details_for_competition' )
            ->with( 1, 2026 )
            ->willReturn( array() );
        $competition_service->expects( self::once() )
            ->method( 'get_competition_overview' )
            ->with( 1, 2026 )
            ->willReturn( $overview );
        $competition_service->expects( self::once() )
            ->method( 'get_clubs_for_competition' )
            ->with( 1, '2026' )
            ->willReturn( $entries );

        $warnings = array();
        set_error_handler(
            static function ( int $severity, string $message ) use ( &$warnings ): bool {
                $warnings[] = $message;
                return true;
            }
        );
        ob_start();
        try {
            $screen->display_overview_page();
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        $this->assertSame( array(), $warnings );
        $this->assertStringContainsString( 'WTC', $output );
        $this->assertStringContainsString( '/entry-form/mixed-league/2026/wtc/', $output );
        $this->assertStringContainsString( '2', $output );
    }

    public function test_display_event_page_renders_leagues_table_with_precomputed_matches(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-leagues', 'view' => 'event', 'event_id' => '10', 'season' => '2026' );

        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Mixed League',
            type: Competition_Type::LEAGUE,
        );

        $champ_ref = new \ReflectionClass( \Racketmanager\Domain\Tournament\Championship::class );
        $champ_obj = $champ_ref->newInstanceWithoutConstructor();
        $prop      = $champ_ref->getProperty( 'num_teams_first_round' );
        $prop->setAccessible( true );
        $prop->setValue( $champ_obj, 16 );

        $league_champ                  = \Racketmanager\Domain\Competition\League::from_object( (object) array( 'id' => 101, 'title' => 'Division Cup' ) );
        $league_champ->is_championship = true;
        $league_champ->num_teams_total = 8;
        $league_champ->championship    = $champ_obj;

        $league_std                  = \Racketmanager\Domain\Competition\League::from_object( (object) array( 'id' => 102, 'title' => 'Division 1' ) );
        $league_std->is_championship = false;
        $league_std->num_teams_total = 6;

        $league_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $league_repository->method( 'query_matches' )->willReturn( 30 );
        $this->container->set( 'league_repository', fn() => $league_repository );
        $this->container->set( 'league_query_service', new \Racketmanager\Services\Competition\League_Query_Service(
            $league_repository,
            $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class )
        ) );

        $event                  = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $event->id              = 10;
        $event->name            = 'Mixed Doubles';
        $event->competition     = $competition;
        $event->is_championship = false;
        $event->current_season  = array( 'name' => '2026' );
        $this->container->set(
            'event_query_service',
            $this->event_query_service( $this->event_repository_with_results( array( 'query_leagues' => array( $league_champ, $league_std ) ) ) )
        );

        $GLOBALS['wp_stubs_events'][10] = $event;
        $this->rebuild_screen();

        $warnings = array();
        set_error_handler(
            static function ( int $severity, string $message ) use ( &$warnings ): bool {
                $warnings[] = $message;
                return true;
            }
        );
        ob_start();
        try {
            $this->screen->display_event_page();
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        $this->assertSame( array(), $warnings );
        $this->assertStringContainsString( 'Division Cup', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertMatchesRegularExpression( '#<td[^>]*>\s*16\s*</td>#', $output );
        $this->assertMatchesRegularExpression( '#<td[^>]*>\s*30\s*</td>#', $output );
    }

    public function test_display_constitution_page_renders_constitution_table_and_team_counts(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET                      = array( 'page' => 'racketmanager-leagues', 'view' => 'constitution', 'event_id' => '10', 'season' => '2026' );

        $competition = Competition_Test_Factory::create(
            id: 1,
            name: 'Mixed League',
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_map(
                '2026',
                array(
                    'date_start' => '2026-10-01',
                    'date_end'   => '2027-03-31',
                )
            )
        );

        $league1                  = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $league1->id              = 101;
        $league1->title           = 'Division 1';

        $team1                   = (object) array(
            'table_id'         => 1,
            'old_league_title' => 'Division 1',
            'old_league_id'    => 101,
            'league_id'        => 101,
            'title'            => 'Leigh 1',
            'team_id'          => 501,
            'status'           => 'champion',
            'old_rank'         => 1,
            'rank'             => 1,
            'points_plus'      => 20,
            'add_points'       => 0,
            'rating'           => '10.5',
            'profile'          => '1',
        );

        $event                  = $this->createMock( \Racketmanager\Domain\Competition\Event::class );
        $event->id              = 10;
        $event->name            = 'Mixed Doubles';
        $event->competition     = $competition;
        $event->is_box          = false;
        $event->leagues         = array( $league1 );
        $event->current_season  = array( 'name' => '2026' );
        $event->expects( self::once() )->method( 'get_seasons' )->willReturn( array( array( 'name' => '2026' ) ) );

        $event_repository = $this->createMock( \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::class );
        $event_repository->expects( self::once() )->method( 'query_leagues' )->willReturn( array( $league1 ) );
        $event_repository->expects( self::once() )->method( 'query_teams' )
            ->with( 10, self::callback( static fn( array $args ): bool => true === $args['count'] && '2026' === $args['season'] ) )
            ->willReturn( 6 );
        $event_repository->expects( self::once() )->method( 'query_constitution' )->willReturn( array( $team1 ) );
        $league_repository = $this->createStub( \Racketmanager\Repositories\Interfaces\League_Repository_Interface::class );
        $league_repository->method( 'find_by_id' )->willReturn( $league1 );
        $league_repository->method( 'query_league_teams' )->willReturn( 6 );
        $this->container->set( 'league_repository', fn() => $league_repository );
        $this->container->set( 'league_query_service', new \Racketmanager\Services\Competition\League_Query_Service(
            $league_repository,
            $this->createStub( \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::class ),
            $this->createStub( \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::class )
        ) );
        $this->container->set(
            'event_query_service',
            $this->event_query_service( $event_repository, null, null, $league_repository )
        );

        $GLOBALS['wp_stubs_events'][10] = $event;
        $this->rebuild_screen();

        $warnings = array();
        set_error_handler(
            static function ( int $severity, string $message ) use ( &$warnings ): bool {
                $warnings[] = $message;
                return true;
            }
        );
        ob_start();
        try {
            $this->screen->display_constitution_page();
        } finally {
            $output = (string) ob_get_clean();
            restore_error_handler();
        }

        $this->assertSame( array(), $warnings );
        $this->assertStringContainsString( 'Constitution - Mixed Doubles 2026', $output );
        $this->assertStringContainsString( 'Division 1', $output );
        $this->assertStringContainsString( 'Leigh 1', $output );
        $this->assertMatchesRegularExpression( '#<div[^>]*class="[^"]*col-4[^"]*text-end[^"]*"[^>]*>\s*6\s*</div>#', $output );
    }
}
