<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Tournament_Matches_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Matches_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\DTO\Admin\Championship\Draw_Action_Response_DTO;
use Racketmanager\Domain\Tournament;
use Racketmanager\Services\Admin\Championship\Draw_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Matches_Admin_Service;
use Racketmanager\Services\Tournament_Service;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;

require_once __DIR__ . '/../wp-stubs.php';

final class Tournament_Matches_Admin_Controller_Test extends TestCase {

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    /**
     * @throws ReflectionException
     */
    public function test_matches_page_get_multiple_matches_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament = $this->create_tournament_instance( 1, 'Club Championship', '2026' );
        
        $league = (object) array(
            'id' => 10,
            'title' => 'Men\'s Singles',
            'event' => (object) array(
                'competition' => (object) array(
                    'id' => 5,
                    'name' => 'Tournament 2026',
                    'type' => 'tournament',
                ),
            ),
            'championship' => (object) array(
                'is_consolation' => false,
            ),
            'current_season' => array(
                'name' => '2026',
                'num_match_days' => 5,
            ),
        );

        $tournament_service = $this->createMock( Tournament_Service::class );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard
            ->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( 'edit_matches' );

        $dispatcher = $this->createMock( Draw_Action_Dispatcher::class );

        $matches_admin_service = $this->createMock( Tournament_Matches_Admin_Service::class );
        $matches_admin_service->method( 'prepare_matches_view_model' )->willReturn(
            new Tournament_Matches_Page_View_Model(
                league: $league,
                tournament: $tournament,
                competition: $league->event->competition,
                season: '2026',
                form_title: 'Matches',
                submit_title: 'Matches',
                matches: array(),
                edit: true,
                bulk: false,
                is_finals: false,
                mode: 'edit',
                teams: array(),
                single_cup_game: false,
                max_matches: 0,
                final_key: '',
                home_title: '',
                away_title: '',
                match_day: null
            )
        );

        // Mock global functions
        if ( ! function_exists( 'Racketmanager\get_league' ) ) {
            eval( 'namespace Racketmanager; function get_league($id) { return $GLOBALS["test_league"] ?? null; }' );
        }
        $GLOBALS['test_league'] = $league;

        $controller = new Tournament_Matches_Admin_Controller(
            $matches_admin_service,
            $dispatcher,
            $guard
        );

        $result = $controller->matches_page(
            array(
                'tournament' => '1',
                'league_id' => '10',
                'view' => 'matches',
            ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Matches_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'Matches', $result['view_model']->form_title );
        self::assertSame( 10, $result['view_model']->league->id );
        self::assertFalse( $result['view_model']->single_cup_game );
        
        unset($GLOBALS['test_league']);
    }

    /**
     * @throws ReflectionException
     */
    public function test_matches_page_get_single_match_returns_view_model(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $tournament = $this->create_tournament_instance( 1, 'Club Championship', '2026' );
        
        $league = (object) array(
            'id' => 10,
            'title' => 'Men\'s Singles',
            'event' => (object) array(
                'competition' => (object) array(
                    'id' => 5,
                    'name' => 'Tournament 2026',
                    'type' => 'tournament',
                ),
            ),
            'championship' => new class {
                public function get_finals($key) { return ['key' => $key]; }
                public function get_final_teams($key) { return []; }
            },
            'current_season' => array(
                'name' => '2026',
                'num_match_days' => 5,
            ),
        );

        $match = (object) array(
            'id' => 100,
            'home_team' => 20,
            'away_team' => 21,
            'match_day' => 1,
        );

        $tournament_service = $this->createMock( Tournament_Service::class );

        $guard = $this->createMock( Action_Guard_Interface::class );
        $guard->method( 'assert_capability' )->with( 'edit_matches' );

        $dispatcher = $this->createMock( Draw_Action_Dispatcher::class );

        $matches_admin_service = $this->createMock( Tournament_Matches_Admin_Service::class );
        $matches_admin_service->method( 'prepare_matches_view_model' )->willReturn(
            new Tournament_Matches_Page_View_Model(
                league: $league,
                tournament: $tournament,
                competition: $league->event->competition,
                season: '2026',
                form_title: 'Edit Match',
                submit_title: 'Edit Match',
                matches: array($match),
                edit: true,
                bulk: false,
                is_finals: false,
                mode: 'edit',
                teams: array(),
                single_cup_game: true,
                max_matches: 1,
                final_key: '',
                home_title: 'Team A',
                away_title: 'Team B',
                match_day: 1
            )
        );

        // Mock global functions
        if ( ! function_exists( 'Racketmanager\get_match' ) ) {
            eval( 'namespace Racketmanager; function get_match($id) { return $GLOBALS["test_match"] ?? null; }' );
        }
        if ( ! function_exists( 'Racketmanager\get_team' ) ) {
            eval( 'namespace Racketmanager; function get_team($id) { return $GLOBALS["test_team"] ?? null; }' );
        }
        $GLOBALS['test_league'] = $league;
        $GLOBALS['test_match'] = $match;
        $GLOBALS['test_team'] = (object) ['title' => 'Test Team'];

        $controller = new Tournament_Matches_Admin_Controller(
            $matches_admin_service,
            $dispatcher,
            $guard
        );

        $result = $controller->matches_page(
            array(
                'tournament' => '1',
                'league_id' => '10',
                'view' => 'match',
                'edit' => '100',
            ),
            array()
        );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Matches_Page_View_Model::class, $result['view_model'] );
        self::assertSame( 'Edit Match', $result['view_model']->form_title );
        self::assertTrue( $result['view_model']->single_cup_game );
        self::assertCount( 1, $result['view_model']->matches );
        self::assertSame( 100, $result['view_model']->matches[0]->id );
        
        unset($GLOBALS['test_league']);
        unset($GLOBALS['test_match']);
    }

    /**
     * @throws ReflectionException
     */
    public function test_matches_page_post_triggers_dispatcher_and_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $tournament_service = $this->createMock( Tournament_Service::class );
        $guard = $this->createMock( Action_Guard_Interface::class );
        $dispatcher = $this->createMock( Draw_Action_Dispatcher::class );

        $guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->with( 'racketmanager_nonce', 'racketmanager_manage-matches', 'edit_matches' );

        $dispatcher->expects( self::once() )
            ->method( 'handle' )
            ->willReturn( new Draw_Action_Response_DTO( message: 'Matches updated', message_type: Admin_Message_Type::SUCCESS ) );

        $matches_admin_service = $this->createMock( Tournament_Matches_Admin_Service::class );

        $controller = new Tournament_Matches_Admin_Controller(
            $matches_admin_service,
            $dispatcher,
            $guard
        );

        $result = $controller->matches_page(
            array(
                'tournament' => '1',
                'league_id' => '10',
                'view' => 'matches',
            ),
            array(
                'league_id' => '10',
                'updateLeague' => 'match',
            )
        );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'view=matches', $result['redirect'] );
        self::assertSame( 'Matches updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    /**
     * @throws ReflectionException
     */
    private function create_tournament_instance( int $id, string $name, string $season ): Tournament {
        $reflection = new ReflectionClass( Tournament::class );

        /** @var Tournament $tournament */
        $tournament = $reflection->newInstanceWithoutConstructor();

        $this->set_property( $tournament, 'id', $id );
        $this->set_property( $tournament, 'name', $name );
        $this->set_property( $tournament, 'season', $season );

        return $tournament;
    }

    private function set_property( object $object, string $property_name, mixed $value ): void {
        $reflection = new ReflectionObject( $object );

        while ( false !== $reflection ) {
            if ( $reflection->hasProperty( $property_name ) ) {
                $property = $reflection->getProperty( $property_name );
                $property->setAccessible( true );
                $property->setValue( $object, $value );
                return;
            }

            $reflection = $reflection->getParentClass();
        }

        self::fail( sprintf( 'Property "%s" not found on %s', $property_name, $object::class ) );
    }
}
