<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Competition;
use Racketmanager\Admin\Flash\Admin_Flash_Message_Store;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Redirector_Interface;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Admin_Competition_Delivery_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Admin_Redirector_Interface $redirector;
    private View_Renderer_Interface $renderer;
    private Admin_Flash_Message_Store $flash_store;
    private Action_Guard_Interface $guard;
    private Competition_Service $competition_service;
    private Finance_Service $finance_service;
    private Season_Service $season_service;
    private Club_Service $club_service;
    private Admin_Competition $screen;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->redirector          = $this->createMock( Admin_Redirector_Interface::class );
        $this->renderer            = $this->createMock( View_Renderer_Interface::class );
        $this->flash_store         = $this->createMock( Admin_Flash_Message_Store::class );
        $this->guard               = $this->createMock( Action_Guard_Interface::class );
        $this->competition_service = $this->createMock( Competition_Service::class );
        $this->finance_service     = $this->createMock( Finance_Service::class );
        $this->season_service      = $this->createMock( Season_Service::class );
        $this->club_service        = $this->createMock( Club_Service::class );

        $this->container = $this->create_admin_delivery_container(
            services: array(
                'competition_service' => $this->competition_service,
                'finance_service'     => $this->finance_service,
                'season_service'      => $this->season_service,
                'club_service'        => $this->club_service,
            ),
            guard: $this->guard,
            redirector: $this->redirector,
            renderer: $this->renderer,
            flash_store: $this->flash_store,
        );

        $racketmanager            = $this->createStub( RacketManager::class );
        $racketmanager->container = $this->container;
        $GLOBALS['racketmanager'] = $racketmanager;

        $this->screen = new Admin_Competition( $racketmanager );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_post_config_redirects_and_sets_flash(): void {
        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->competition_service->expects( self::once() )
            ->method( 'amend_details' )
            ->willReturn( 1 );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Competition updated', false, self::isArray() );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-leagues' ) );

        $this->renderer->expects( self::never() )->method( 'render' );

        $_GET['competition_id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array(
            'updateCompetitionConfig' => '1',
            'competition_id'          => '1',
            'competition_title'       => 'Premier League',
        );

        $this->screen->display_config_page();
    }

    public function test_get_config_page_renders_view_model(): void {
        $competition            = $this->createStub( Competition::class );
        $competition->id        = 1;
        $competition->name      = 'Premier League';
        $competition->age_group = 'open';
        $competition->method( 'get_type' )->willReturn( 'league' );
        $competition->method( 'get_settings' )->willReturn( array() );
        $competition->method( 'get_events' )->willReturn( array() );

        $this->competition_service->expects( self::atLeastOnce() )
            ->method( 'get_by_id' )
            ->with( 1 )
            ->willReturn( $competition );

        $this->competition_service->expects( self::once() )
            ->method( 'get_rules_options' )
            ->willReturn( array() );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( array() );

        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/includes/competition-config',
                self::isInstanceOf( Tournament_Competition_Config_Page_View_Model::class )
            );

        $_GET['competition_id'] = '1';

        $this->screen->display_config_page();
    }

    public function test_get_config_page_rehydrates_flash_form_data_and_errors(): void {
        $competition            = $this->createStub( Competition::class );
        $competition->id        = 1;
        $competition->name      = 'Premier League';
        $competition->age_group = 'open';
        $competition->method( 'get_type' )->willReturn( 'league' );
        $competition->method( 'get_settings' )->willReturn( array( 'max_teams' => 10, 'reverse_rubbers' => true ) );
        $competition->method( 'get_events' )->willReturn( array() );

        $this->competition_service->method( 'get_by_id' )->willReturn( $competition );
        $this->competition_service->method( 'get_rules_options' )->willReturn( array() );
        $this->club_service->method( 'get_clubs' )->willReturn( array() );

        $this->flash_store->method( 'pop' )->willReturn(
            array(
                'flash_data' => array(
                    'errors'    => array( 'max_teams' => 'Max teams must be numeric' ),
                    'form_data' => array(
                        'competition_title'        => 'Changed Title',
                        'max_teams'                => 'abc',
                        'default_match_start_time' => '19:30',
                        'min_start_time_weekday'   => '18:00',
                        'type'                     => 'tournament',
                        'unknown_key'              => 'evil',
                    ),
                ),
            )
        );

        $captured = null;
        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->willReturnCallback(
                function ( string $template, $vm ) use ( &$captured ): string {
                    $captured = $vm;
                    return '';
                }
            );

        $_GET['competition_id'] = '1';

        $this->screen->display_config_page();

        self::assertInstanceOf( Tournament_Competition_Config_Page_View_Model::class, $captured );
        $config = $captured->competition->config;
        self::assertSame( 'Changed Title', $captured->competition->name );
        self::assertSame( 'abc', $config->max_teams );
        self::assertSame( 'Max teams must be numeric', $captured->errors->message( 'max_teams' ) );
        self::assertSame( '18:00', $config->start_time['weekday']['min'] );
        self::assertIsArray( $config->default_match_start_time );
        self::assertSame( 19, $config->default_match_start_time['hour'] );
        self::assertSame( 30, $config->default_match_start_time['minutes'] );
        self::assertSame( 'league', $config->type );
        self::assertFalse( property_exists( $config, 'unknown_key' ) );
        self::assertEmpty( $config->reverse_rubbers, 'Unchecked checkbox must not retain the saved value on rehydration' );
    }

    public function test_post_season_modify_redirects_and_sets_flash(): void {
        $competition                = $this->createMock( Competition::class );
        $competition->id            = 1;
        $competition->is_league     = true;
        $competition->is_team_entry = true;
        $competition->method( 'get_season_by_name' )->willReturn( array(
            'name'             => '2025',
            'grade'            => 'B',
            'date_open'        => '2025-01-01',
            'date_end'         => '2025-06-30',
            'date_closing'     => '2025-01-15',
            'date_start'       => '2025-02-01',
            'max_teams'        => 10,
            'teams_per_club'   => 2,
            'teams_prom_relg'  => 2,
            'lowest_promotion' => 1,
            'num_match_days'   => 10,
            'round_length'     => 7,
            'home_away_diff'   => 0,
            'filler_weeks'     => 0,
            'match_dates'      => array(),
        ) );
        $competition->expects( self::once() )->method( 'update_season' )->willReturn( true );

        $this->guard->expects( self::once() )->method( 'assert_allowed' );
        $this->competition_service->expects( self::atLeastOnce() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Season updated', false, self::isArray() );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-leagues' ) );

        $_GET['competition_id'] = '1';
        $_GET['season']         = '2025';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array(
            'editSeason'       => '1',
            'competition_id'   => '1',
            'season'           => '2025',
            'grade'            => 'A',
            'dateOpen'         => '2025-01-01',
            'dateEnd'          => '2025-06-30',
            'dateClose'        => '2025-01-15',
            'dateStart'        => '2025-02-01',
            'max_teams'        => '10',
            'teams_per_club'   => '2',
            'teams_prom_relg'  => '2',
            'lowest_promotion' => '1',
            'num_match_days'   => '10',
            'round_length'     => '7',
        );

        $this->screen->display_season_modify_page();
    }

    public function test_get_season_modify_page_renders_view_model(): void {
        $competition       = $this->createStub( Competition::class );
        $competition->id   = 1;
        $competition->name = 'Premier League';
        $competition->method( 'get_type' )->willReturn( 'league' );
        $competition->method( 'get_seasons' )->willReturn( array(
            '2025' => array( 'name' => '2025' ),
        ) );
        $competition->method( 'get_season_by_name' )->willReturn( array(
            'name'  => '2025',
            'grade' => 'A',
        ) );

        $this->competition_service->expects( self::atLeastOnce() )
            ->method( 'get_competition' )
            ->with( 1 )
            ->willReturn( $competition );

        $this->finance_service->expects( self::once() )
            ->method( 'get_charges_by_criteria' )
            ->willReturn( array() );

        $this->season_service->expects( self::once() )
            ->method( 'get_all_seasons' )
            ->willReturn( array() );

        $this->club_service->expects( self::once() )
            ->method( 'get_clubs' )
            ->willReturn( array() );

        $this->flash_store->expects( self::once() )
            ->method( 'pop' )
            ->willReturn( array() );

        $this->renderer->expects( self::once() )
            ->method( 'render' )
            ->with(
                'admin/includes/season-edit',
                self::isInstanceOf( Season_Modify_Page_View_Model::class )
            );

        $_GET['competition_id'] = '1';
        $_GET['season']         = '2025';

        $this->screen->display_season_modify_page();
    }

    public function test_post_config_guard_failure_redirects_and_prevents_mutation(): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_allowed' )
            ->willThrowException( new Invalid_Status_Exception( 'Unauthorized' ) );

        $this->competition_service->expects( self::never() )->method( 'amend_details' );

        $this->flash_store->expects( self::once() )
            ->method( 'set' )
            ->with( 'Unauthorized', true, self::isArray() );

        $this->redirector->expects( self::once() )
            ->method( 'redirect' )
            ->with( self::stringContains( 'page=racketmanager-leagues' ) );

        $_GET['competition_id'] = '1';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array(
            'updateCompetitionConfig' => '1',
            'competition_id'          => '1',
        );

        $this->screen->display_config_page();
    }
}
