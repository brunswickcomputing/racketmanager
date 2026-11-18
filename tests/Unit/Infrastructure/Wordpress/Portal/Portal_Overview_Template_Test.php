<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Portal;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Overview_Option_Item;
use Racketmanager\Admin\View_Models\Overview_Page_View_Model;
use Racketmanager\Application\Ports\Manage_Url_Resolver_Interface;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Route_Mapper;
use Racketmanager\Infrastructure\Wordpress\Routing\Manage_Url_Resolver;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\View\Php_View_Renderer;

final class Portal_Overview_Template_Test extends TestCase {

    private Simple_Container $container;
    private Manage_Url_Resolver $resolver;
    private Php_View_Renderer $renderer;

    protected function setUp(): void {
        parent::setUp();
        global $racketmanager;
        $this->container = new Simple_Container();
        $this->resolver  = new Manage_Url_Resolver( new Manage_Route_Mapper(), force_portal_context: true );
        $this->container->set( Manage_Url_Resolver_Interface::class, fn() => $this->resolver );
        $racketmanager = (object) [ 'container' => $this->container ];

        $this->renderer = new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' );
    }

    protected function tearDown(): void {
        global $racketmanager;
        $racketmanager = null;
        parent::tearDown();
    }

    public function test_overview_view_model_constructs_default_service_options(): void {
        $vm = new Overview_Page_View_Model();

        $this->assertSame( 'Overview', $vm->title );
        $this->assertSame( 'RacketManager', $vm->service_name );
        $this->assertNotEmpty( $vm->service_summary );
        $this->assertCount( 4, $vm->service_pillars );
        $this->assertCount( 11, $vm->options );

        $expected_slugs = [
            'leagues',
            'cups',
            'tournaments',
            'clubs',
            'results',
            'players',
            'seasons',
            'finances',
            'settings',
            'import',
            'documentation',
        ];

        $actual_slugs = array_map( static fn( Overview_Option_Item $opt ) => $opt->slug, $vm->options );
        $this->assertSame( $expected_slugs, $actual_slugs );

        $template_vars = $vm->to_template_vars();
        $this->assertArrayHasKey( 'title', $template_vars );
        $this->assertArrayHasKey( 'service_summary', $template_vars );
        $this->assertArrayHasKey( 'options', $template_vars );
    }

    public function test_overview_option_item_to_array(): void {
        $item = new Overview_Option_Item(
            'leagues',
            'Leagues',
            'Competition Management',
            'Manage divisions and schedules',
            'https://example.test/manage/leagues',
            'Competitive',
            [ 'Feature 1', 'Feature 2' ]
        );

        $array = $item->to_array();
        $this->assertSame( 'leagues', $array['slug'] );
        $this->assertSame( 'Leagues', $array['title'] );
        $this->assertSame( 'Competition Management', $array['category'] );
        $this->assertSame( 'Competitive', $array['badge'] );
        $this->assertSame( [ 'Feature 1', 'Feature 2' ], $array['capabilities'] );
    }

    public function test_renders_portal_overview_template(): void {
        $vm   = new Overview_Page_View_Model();
        $html = $this->renderer->render_to_string( 'portal/overview', $vm );

        $this->assertStringContainsString( 'RacketManager &mdash; Overview', $html );
        $this->assertStringContainsString( 'Competitions', $html );
        $this->assertStringContainsString( 'Scorecard', $html );
        $this->assertStringContainsString( 'Clubs, Teams', $html );
        $this->assertStringContainsString( 'Financial Operations', $html );

        // Options check
        $this->assertStringContainsString( 'Leagues', $html );
        $this->assertStringContainsString( 'Cups', $html );
        $this->assertStringContainsString( 'Tournaments', $html );
        $this->assertStringContainsString( 'Clubs', $html );
        $this->assertStringContainsString( 'Results', $html );
        $this->assertStringContainsString( 'Players', $html );
        $this->assertStringContainsString( 'Seasons', $html );
        $this->assertStringContainsString( 'Finances', $html );
        $this->assertStringContainsString( 'Settings', $html );
        $this->assertStringContainsString( 'Import Tools', $html );
        $this->assertStringContainsString( 'Documentation', $html );

        // URLs check
        $this->assertStringContainsString( home_url( '/manage/leagues' ), $html );
        $this->assertStringContainsString( home_url( '/manage/cups' ), $html );
        $this->assertStringContainsString( home_url( '/manage/tournaments' ), $html );
        $this->assertStringContainsString( home_url( '/manage/clubs' ), $html );
        $this->assertStringContainsString( home_url( '/manage/results' ), $html );
        $this->assertStringContainsString( home_url( '/manage/players' ), $html );
        $this->assertStringContainsString( home_url( '/manage/seasons' ), $html );
        $this->assertStringContainsString( home_url( '/manage/finances' ), $html );
        $this->assertStringContainsString( home_url( '/manage/settings' ), $html );
        $this->assertStringContainsString( home_url( '/manage/import' ), $html );
        $this->assertStringContainsString( home_url( '/manage/documentation' ), $html );
    }
}
