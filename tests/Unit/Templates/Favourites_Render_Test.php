<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\General\Queries\Get_Favourites_Query;
use Racketmanager\Application\General\Queries\Get_Players_Handler;
use Racketmanager\Application\General\Queries\Get_Players_Query;
use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Domain\Player\User;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\Presentation_Service_Provider;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\User_Repository_Interface;
use Racketmanager\Services\Container\Providers\Club_Service_Provider;
use Racketmanager\Services\Container\Providers\Core_Service_Provider;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

class Favourites_Render_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Simple_Container $container;
    private Shortcode_Template_Renderer $renderer;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_user_meta'] = [];
        $GLOBALS['wp_stubs_is_user_logged_in'] = true;
        $GLOBALS['wp_stubs_get_current_user_id'] = 41;
        unset( $GLOBALS['racketmanager'], $GLOBALS['wp_stubs_stylesheet_directory'], $GLOBALS['wp_stubs_template_directory'] );

        $this->container = new Simple_Container();
        ( new Core_Service_Provider() )->register( $this->container );
        ( new Presentation_Service_Provider() )->register( $this->container );
        $this->container->set( 'club_repository', fn() => $this->createStub( Club_Repository_Interface::class ) );
        $this->container->set( 'club_presenter', fn() => new Club_Presenter() );
        $this->container->set( 'player_presenter', fn() => new Player_Presenter() );
        $this->renderer = $this->container->get( 'shortcode_template_renderer' );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_controls_use_current_users_metadata_without_global_plugin_and_do_not_leak_state(): void {
        foreach ( [ 'competition', 'league', 'club', 'team', 'player' ] as $type ) {
            $GLOBALS['wp_stubs_user_meta'][41][ 'favourite-' . $type ] = [ '12' ];
            foreach ( [ 'favourite', 'favourite-button' ] as $template ) {
                foreach ( [ 12, 13, 12 ] as $id ) {
                    $title = 12 === $id ? 'Remove favourite' : 'Add favourite';
                    $html = $this->render( $template, [ 'favourite_type' => $type, 'favourite_id' => $id ], 'includes' );
                    self::assertStringContainsString( 'title="' . $title . '"', $html );
                    self::assertStringContainsString( 'data-type="' . $type . '"', $html );
                    self::assertStringContainsString( 'data-favourite="' . $id . '"', $html );
                    self::assertSame( 12 === $id, str_contains( $html, 'fav-icon-svg-selected' ) );
                }
            }
        }
    }

    public function test_anonymous_visitors_do_not_get_controls_even_with_user_metadata(): void {
        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $GLOBALS['wp_stubs_user_meta'][41]['favourite-team'] = [ '12' ];
        foreach ( [ 'favourite', 'favourite-button' ] as $template ) {
            $html = $this->render( $template, [ 'favourite_type' => 'team', 'favourite_id' => 12 ], 'includes' );
            self::assertStringNotContainsString( 'data-js="add-favourite"', $html );
        }
    }

    public function test_presented_snapshot_not_globals_controls_rendering_and_is_immutable(): void {
        $GLOBALS['wp_stubs_user_meta'][41]['favourite-player'] = [ '12' ];
        $controls = $this->container->get( 'favourite_presenter' )->present_controls();
        self::assertTrue( ( new \ReflectionClass( $controls ) )->isReadOnly() );
        self::assertTrue( ( new \ReflectionClass( $controls->selected['player'][12] ) )->isReadOnly() );

        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        $GLOBALS['racketmanager'] = new class {
            public function is_user_favourite(): never {
                throw new \LogicException( 'Templates must not query the plugin' );
            }
        };
        $html = $this->container->get( 'view_renderer' )->render_to_string( 'includes/favourite-button', [
            'favourite_controls' => $controls,
            'favourite_type' => 'player',
            'favourite_id' => 12,
            'hidden' => true,
        ] );
        self::assertStringContainsString( 'title="Remove favourite"', $html );
        self::assertStringContainsString( 'data-status="1"', $html );
        self::assertStringContainsString( 'hidden-svg', $html );
    }

    public function test_current_user_and_metadata_changes_are_observed_by_shared_renderer(): void {
        $GLOBALS['wp_stubs_user_meta'][41]['favourite-club'] = [ '12' ];
        $vars = [ 'favourite_type' => 'club', 'favourite_id' => 12 ];
        self::assertStringContainsString( 'Remove favourite', $this->render( 'favourite-button', $vars, 'includes' ) );

        $GLOBALS['wp_stubs_get_current_user_id'] = 42;
        self::assertStringContainsString( 'Add favourite', $this->render( 'favourite-button', $vars, 'includes' ) );
        add_user_meta( 42, 'favourite-club', 12 );
        self::assertStringContainsString( 'Remove favourite', $this->render( 'favourite-button', $vars, 'includes' ) );
        delete_user_meta( 42, 'favourite-club', 12 );
        self::assertStringContainsString( 'Add favourite', $this->render( 'favourite-button', $vars, 'includes' ) );

        $GLOBALS['wp_stubs_get_current_user_id'] = 0;
        self::assertStringNotContainsString( 'data-js="add-favourite"', $this->render( 'favourite-button', $vars, 'includes' ) );
    }

    public function test_favourites_handler_renders_all_types_with_exact_links_and_no_domain_mutation(): void {
        $real_team = Team_Factory::from_array( [
            'id'      => 12,
            'title'   => 'Westcliff 1',
            'club_id' => 99,
        ] );
        self::assertNull( $real_team->club );

        $details = [
            'competition' => (object) [ 'id' => 12, 'name' => 'Mixed Doubles', 'competition' => (object) [ 'name' => 'Summer Open' ] ],
            'league' => (object) [ 'id' => 12, 'title' => 'Premier Division' ],
            'club' => Club_Test_Factory::create( id: 12, shortcode: 'Leigh TC' ),
            'team' => $real_team,
            'player' => Player_Test_Factory::create_sparse( [ 'id' => 12, 'display_name' => 'Jane Smith' ] ),
        ];
        $groups = [];
        foreach ( $details as $type => $detail ) {
            $groups[] = [ 'name' => $type, 'favourites' => [ (object) [ 'id' => '12', 'name' => $type . ' favourite', 'detail' => $detail ] ] ];
            $GLOBALS['wp_stubs_user_meta'][41][ 'favourite-' . $type ] = [ '12' ];
        }
        $before = serialize( $groups );
        $user = $this->createStub( User::class );
        $repository = $this->createMock( User_Repository_Interface::class );
        $repository->expects( self::once() )->method( 'find' )->with( 41 )->willReturn( $user );
        $repository->method( 'get_favourites' )->willReturn( $groups );
        $this->container->set( 'user_repository', $repository );

        $club_repo = $this->createMock( Club_Repository_Interface::class );
        $club_repo->expects( self::once() )->method( 'find_by_id' )->with( 99 )->willReturn( Club_Test_Factory::create( id: 99, shortcode: 'Westcliff TC' ) );
        $this->container->set( 'club_repository', $club_repo );

        $res = $this->container->get( 'get_favourites_handler' )->handle( new Get_Favourites_Query( is_logged_in: true, user_id: 41 ) );
        $html = $this->render( 'form-favourites', [ 'favourite_types' => $res->favourite_types ], 'form' );
        foreach ( [
            '/tournaments/summer-open/mixed-doubles/',
            '/league/premier-division/',
            '/clubs/leigh-tc/',
            '/clubs/westcliff-tc/competitions/',
            '/player/jane-smith/',
        ] as $url ) {
            self::assertStringContainsString( 'href="' . $url . '"', $html );
        }
        self::assertSame( 5, substr_count( $html, 'title="Remove favourite"' ) );
        self::assertSame( 5, substr_count( $html, 'data-favourite="12" data-status="1"' ) );
        self::assertSame( $before, serialize( $groups ) );

        $empty_html = $this->render( 'form-favourites', [ 'favourite_types' => [ [ 'name' => 'player', 'favourites' => [] ] ] ], 'form' );
        self::assertStringContainsString( 'No favourite found', $empty_html );
        self::assertStringNotContainsString( 'data-js="add-favourite"', $empty_html );
    }

    public function test_favourites_handler_with_real_team_and_dangling_or_null_details_degrades_gracefully_without_warnings(): void {
        $real_team_missing_club = Team_Factory::from_array( [
            'id'      => 14,
            'title'   => 'Orphan Team',
            'club_id' => 999,
        ] );

        $groups = [
            [
                'name' => 'team',
                'favourites' => [
                    (object) [ 'id' => '14', 'name' => 'Orphan Team', 'detail' => $real_team_missing_club ],
                ],
            ],
            [
                'name' => 'league',
                'favourites' => [
                    (object) [ 'id' => '99', 'name' => 'Deleted League', 'detail' => null ],
                ],
            ],
            [
                'name' => 'competition',
                'favourites' => [
                    (object) [ 'id' => '101', 'name' => null, 'detail' => null ],
                ],
            ],
        ];

        $user = $this->createStub( User::class );
        $repository = $this->createMock( User_Repository_Interface::class );
        $repository->expects( self::once() )->method( 'find' )->with( 41 )->willReturn( $user );
        $repository->method( 'get_favourites' )->willReturn( $groups );
        $this->container->set( 'user_repository', $repository );

        $club_repo = $this->createMock( Club_Repository_Interface::class );
        $club_repo->expects( self::once() )->method( 'find_by_id' )->with( 999 )->willReturn( null );
        $this->container->set( 'club_repository', $club_repo );

        $res = $this->container->get( 'get_favourites_handler' )->handle( new Get_Favourites_Query( is_logged_in: true, user_id: 41 ) );
        $html = $this->render( 'form-favourites', [ 'favourite_types' => $res->favourite_types ], 'form' );

        // Plain text is rendered when URLs cannot be generated
        self::assertStringContainsString( '<span class="nav-link__value">Orphan Team</span>', $html );
        self::assertStringContainsString( '<span class="nav-link__value">Deleted League</span>', $html );
        self::assertStringContainsString( '<span class="nav-link__value">Unavailable</span>', $html );
        self::assertStringNotContainsString( '<a class="nav--link media__link"', $html );
    }

    public function test_favourites_handler_still_denies_anonymous_requests_before_loading_user(): void {
        $repository = $this->createMock( User_Repository_Interface::class );
        $repository->expects( self::never() )->method( 'find' );
        $this->container->set( 'user_repository', $repository );
        $res = $this->container->get( 'get_favourites_handler' )->handle( new Get_Favourites_Query( is_logged_in: false, user_id: 41 ) );
        self::assertTrue( $res->is_error() );
        $html = $this->container->get( 'shortcode_view_presenter' )->render( \Racketmanager\Presentation\View_Models\Shortcode_View_Model::for_error( $res->error_message ?? '' ) );
        self::assertStringContainsString( 'You must be logged in to view favourites', $html );
        self::assertStringNotContainsString( 'data-js="add-favourite"', $html );
    }

    public function test_players_handler_search_and_favourites_use_same_snapshot_and_support_repeat_render(): void {
        $GLOBALS['wp_stubs_user_meta'][41]['favourite-player'] = [ '12' ];
        $player = Player_Test_Factory::create_sparse( [ 'id' => 12, 'display_name' => 'Jane Smith' ] );
        $favourites = [ (object) [ 'id' => '12', 'name' => 'Jane Smith', 'detail' => $player ] ];
        $user = $this->createStub( User::class );
        $users = $this->createMock( User_Repository_Interface::class );
        $users->expects( self::once() )->method( 'find' )->with( 41 )->willReturn( $user );
        $users->expects( self::once() )->method( 'get_favourites' )->with( $user, 'player' )->willReturn( $favourites );
        $players = $this->createMock( Player_Repository_Interface::class );
        $players->expects( self::once() )->method( 'find_players_summary' )->with( [ 'name' => 'Smith' ] )->willReturn( [
            new Players_List_DTO( [ 'ID' => 12, 'firstname' => 'Jane', 'surname' => 'Smith', 'display_name' => 'Jane Smith' ] ),
            new Players_List_DTO( [ 'ID' => 13, 'firstname' => 'John', 'surname' => 'Smith', 'display_name' => 'John Smith' ] ),
        ] );
        $handler = new Get_Players_Handler( $players, $users );
        $res = $handler->handle( new Get_Players_Query( search_string: 'Smith', is_logged_in: true, user_id: 41 ) );
        self::assertSame( $favourites, $res->favourites );

        $presenter = new Player_Presenter();
        $player_vm = $presenter->present_players_directory(
            search_string: $res->search_string ?? '',
            search_results: $this->renderer->render( 'players-list', [ 'players' => $res->players ] ),
            favourites: $res->favourites,
            template: 'players'
        );
        $vars = $player_vm->template_vars();

        foreach ( [ 1, 2 ] as $render_number ) {
            $html = $this->render( 'players', $vars );
            self::assertSame( 2, substr_count( $html, 'title="Remove favourite"' ), 'Search and directory, render ' . $render_number );
            self::assertSame( 1, substr_count( $html, 'title="Add favourite"' ) );
            self::assertStringContainsString( 'href="/player/jane-smith/"', $html );
        }
    }

    public function test_club_renderer_produces_favourite_state_for_header_without_plugin_global(): void {
        ( new Club_Service_Provider() )->register( $this->container );
        $club = Club_Test_Factory::create( id: 12, name: 'Leigh Tennis Club', shortcode: 'Leigh TC' );
        $vm = $this->container->get( 'club_presenter' )->present( $club, template: 'includes/club-header' );
        $GLOBALS['wp_stubs_user_meta'][41]['favourite-club'] = [ '12' ];
        $presenter = $this->container->get( 'club_shortcode_presenter' );
        $html = $presenter->render( $vm );
        self::assertStringContainsString( 'title="Remove favourite"', $html );
        self::assertStringContainsString( 'data-type="club" data-favourite="12"', $html );

        $GLOBALS['wp_stubs_is_user_logged_in'] = false;
        self::assertStringNotContainsString( 'data-js="add-favourite"', $presenter->render( $vm ) );
    }

    public function test_favourite_includes_have_no_global_or_service_lookups(): void {
        foreach ( [ 'favourites', 'favourite', 'favourite-button' ] as $name ) {
            $source = file_get_contents( RACKETMANAGER_PATH . 'templates/includes/' . $name . '.php' );
            self::assertDoesNotMatchRegularExpression( '/\$GLOBALS|global\s|is_user_logged_in\(|get_user_meta\(|is_user_favourite\(|seo_url\(|new\s/', $source );
        }
    }

    public function test_flat_and_typed_theme_overrides_receive_legacy_data_and_presented_favourites(): void {
        $theme_dir = RACKETMANAGER_PATH . 'tests/favourites-theme-' . uniqid( '', true );
        mkdir( $theme_dir . '/racketmanager/forms', 0777, true );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $theme_dir;
        $GLOBALS['wp_stubs_user_meta'][41]['favourite-league'] = [ '12' ];
        $vars = [ 'favourite_types' => [ [ 'name' => 'league', 'favourites' => [
            (object) [ 'id' => '12', 'name' => 'Premier Division', 'detail' => (object) [ 'id' => 12, 'title' => 'Premier Division' ] ],
        ] ] ] ];
        $template = '<?php echo $favourite_types[0]["favourites"][0]->name . "|" . $favourite_rows_by_type["league"][0]->url . "|" . $favourite_controls->selected["league"][12]->title;';
        $flat = $theme_dir . '/racketmanager/form-favourites.php';
        $typed = $theme_dir . '/racketmanager/forms/form-favourites.php';
        try {
            file_put_contents( $typed, $template );
            self::assertSame( 'Premier Division|/league/premier-division/|Remove favourite', $this->render( 'form-favourites', $vars, 'form' ) );
            file_put_contents( $flat, $template );
            self::assertSame( 'Premier Division|/league/premier-division/|Remove favourite', $this->render( 'form-favourites', $vars, 'form' ) );
        } finally {
            foreach ( [ $flat, $typed ] as $file ) {
                if ( is_file( $file ) ) {
                    unlink( $file );
                }
            }
            rmdir( $theme_dir . '/racketmanager/forms' );
            rmdir( $theme_dir . '/racketmanager' );
            rmdir( $theme_dir );
        }
    }

    private function render( string $template, array $vars, false|string $type = false ): string {
        $warnings = [];
        set_error_handler( static function ( int $severity, string $message ) use ( &$warnings ): bool {
            $warnings[] = $message;
            return true;
        } );
        try {
            $html = $this->renderer->render( $template, $vars, $type );
        } finally {
            restore_error_handler();
        }
        self::assertSame( [], $warnings );
        return $html;
    }
}