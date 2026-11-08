<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Widgets;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Infrastructure\Wordpress\Widgets\Club_Widget;
use Racketmanager\Public_Api;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Club_Widget_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		unset( $GLOBALS['racketmanager'] );
		Public_Api::reset();
	}

	protected function tearDown(): void {
		Public_Api::reset();
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_widget_queries_clubs_via_injected_club_service(): void {
		$club = new Club();
		$club->set_id( 1 );
		$club->set_name( 'Test Tennis Club' );
		$club->set_shortcode( 'ttc' );

		$filter = function ( $args ) {
			return isset( $args['limit'] ) && 5 === $args['limit']
				&& isset( $args['club_type'] ) && 'affiliated' === $args['club_type']
				&& isset( $args['orderby'] ) && 'name' === $args['orderby'];
		};

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->with( $this->callback( $filter ) )
			->willReturn( array( $club ) );

		$widget = new Club_Widget( club_service: $club_service );

		$args = array(
			'widget_id'     => 'test-widget-1',
			'before_widget' => '<div class="widget">',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		);
		$instance = array(
			'title'     => 'Our Clubs',
			'num_items' => 5,
			'club_type' => 'affiliated',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Test Tennis Club', $output );
		$this->assertStringContainsString( 'Our Clubs', $output );
	}

	public function test_widget_queries_clubs_via_injected_club_repository(): void {
		$club = new Club();
		$club->set_id( 2 );
		$club->set_name( 'Repository Tennis Club' );
		$club->set_shortcode( 'rtc' );

		$filter = function ( $args ) {
			return isset( $args['limit'] ) && 999999 === $args['limit']
				&& isset( $args['club_type'] ) && 'all' === $args['club_type'];
		};

		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->once() )
			->method( 'find_all' )
			->with( $this->callback( $filter ) )
			->willReturn( array( $club ) );

		$widget = new Club_Widget( club_repository: $club_repository );

		$args = array(
			'widget_id'     => 'test-widget-2',
			'before_widget' => '<aside>',
			'after_widget'  => '</aside>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'     => 'Clubs',
			'num_items' => '',
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Repository Tennis Club', $output );
	}

	public function test_widget_queries_clubs_via_injected_container(): void {
		$club = new Club();
		$club->set_id( 3 );
		$club->set_name( 'Container Tennis Club' );
		$club->set_shortcode( 'ctc' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->willReturn( array( $club ) );

		$container = new Simple_Container();
		$container->set( Club_Service::class, fn() => $club_service );

		$widget = new Club_Widget( container: $container );

		$args = array(
			'widget_id'     => 'test-widget-3',
			'before_widget' => '<section>',
			'after_widget'  => '</section>',
			'before_title'  => '<h1>',
			'after_title'   => '</h1>',
		);
		$instance = array(
			'title'     => 'Container Clubs',
			'num_items' => 10,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Container Tennis Club', $output );
	}

	public function test_widget_queries_clubs_via_public_api_container(): void {
		$club = new Club();
		$club->set_id( 4 );
		$club->set_name( 'Public API Tennis Club' );
		$club->set_shortcode( 'patc' );

		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->once() )
			->method( 'find_all' )
			->willReturn( array( $club ) );

		$container = new Simple_Container();
		$container->set( Club_Repository_Interface::class, fn() => $club_repository );
		Public_Api::init( $container );

		$widget = new Club_Widget();

		$args = array(
			'widget_id'     => 'test-widget-4',
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h4>',
			'after_title'   => '</h4>',
		);
		$instance = array(
			'title'     => 'Public API Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Public API Tennis Club', $output );
	}

	public function test_widget_constructs_without_global_racketmanager(): void {
		$this->assertArrayNotHasKey( 'racketmanager', $GLOBALS );

		$widget = new Club_Widget();
		$this->assertInstanceOf( Club_Widget::class, $widget );
	}

	public function test_widget_update_returns_new_instance(): void {
		$widget   = new Club_Widget();
		$new_data = array( 'title' => 'New Title' );
		$old_data = array( 'title' => 'Old Title' );

		$this->assertSame( $new_data, $widget->update( $new_data, $old_data ) );
	}

	public function test_widget_form_renders_fields(): void {
		$widget   = new Club_Widget();
		$instance = array(
			'title'     => 'My Club Title',
			'club_name' => '1',
			'club_link' => '1',
			'orderby'   => 'asc',
			'num_items' => 8,
			'club_type' => 'all',
		);

		ob_start();
		$widget->form( $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'My Club Title', $output );
		$this->assertStringContainsString( 'value="8"', $output );
	}

	public function test_widget_returns_early_on_cache_hit(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;
		wp_cache_set( 'racketmanager', array( 'cached-widget-id' => 'Cached Widget Output' ), 'widget' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->never() )->method( 'get_clubs' );

		$widget = new Club_Widget( club_service: $club_service );

		$args = array(
			'widget_id'     => 'cached-widget-id',
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		);
		$instance = array(
			'title'     => 'Cached Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertSame( 'Cached Widget Output', $output );
	}

	public function test_widget_caches_output_in_non_preview_mode(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;

		$club = new Club();
		$club->set_id( 10 );
		$club->set_name( 'Cached Club' );
		$club->set_shortcode( 'cc' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->willReturn( array( $club ) );

		$widget = new Club_Widget( club_service: $club_service );

		$args = array(
			'widget_id'     => 'test-widget-cache-store',
			'before_widget' => '<aside>',
			'after_widget'  => '</aside>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'     => 'Live Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Cached Club', $output );

		$cache = wp_cache_get( 'racketmanager', 'widget' );
		$this->assertIsArray( $cache );
		$this->assertArrayHasKey( 'test-widget-cache-store', $cache );
		$this->assertStringContainsString( 'Cached Club', $cache['test-widget-cache-store'] );
	}

	public function test_widget_renders_empty_when_no_service_or_repository_resolvable(): void {
		$widget = new Club_Widget();

		$args = array(
			'widget_id'     => 'empty-widget',
			'before_widget' => '<section>',
			'after_widget'  => '</section>',
			'before_title'  => '<h1>',
			'after_title'   => '</h1>',
		);
		$instance = array(
			'title'     => 'No Resolvable Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<section>', $output );
		$this->assertStringContainsString( '</section>', $output );
		$this->assertStringNotContainsString( 'roll-club', $output );
		$this->assertStringNotContainsString( 'See all our clubs', $output );
	}

	public function test_widget_prioritizes_service_over_repository_when_both_injected(): void {
		$service_club = new Club();
		$service_club->set_id( 20 );
		$service_club->set_name( 'Service Preferred Club' );
		$service_club->set_shortcode( 'spc' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->willReturn( array( $service_club ) );

		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->never() )
			->method( 'find_all' );

		$widget = new Club_Widget(
			club_service: $club_service,
			club_repository: $club_repository
		);

		$args = array(
			'widget_id'     => 'test-widget-precedence',
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		);
		$instance = array(
			'title'     => 'Clubs Precedence',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Service Preferred Club', $output );
	}

	public function test_widget_renders_club_website_when_link_enabled(): void {
		$club = new Club();
		$club->set_id( 30 );
		$club->set_name( 'Website Club' );
		$club->set_shortcode( 'wc' );
		$club->set_website( 'https://example.com' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->willReturn( array( $club ) );

		$widget = new Club_Widget( club_service: $club_service );

		$args = array(
			'widget_id'     => 'test-widget-website-enabled',
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		);
		$instance = array(
			'title'     => 'Website Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '1',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Website Club', $output );
		$this->assertStringContainsString( '<a href="https://example.com" target="_blank">https://example.com</a>', $output );
	}

	public function test_widget_omits_club_website_when_link_disabled(): void {
		$club = new Club();
		$club->set_id( 31 );
		$club->set_name( 'No Link Club' );
		$club->set_shortcode( 'nlc' );
		$club->set_website( 'https://example.com' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->willReturn( array( $club ) );

		$widget = new Club_Widget( club_service: $club_service );

		$args = array(
			'widget_id'     => 'test-widget-website-disabled',
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		);
		$instance = array(
			'title'     => 'No Link Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'No Link Club', $output );
		$this->assertStringNotContainsString( 'https://example.com', $output );
	}

	public function test_widget_omits_club_website_when_website_is_null(): void {
		$club = new Club();
		$club->set_id( 32 );
		$club->set_name( 'Null Website Club' );
		$club->set_shortcode( 'nwc' );
		$club->website = null;

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->willReturn( array( $club ) );

		$widget = new Club_Widget( club_service: $club_service );

		$args = array(
			'widget_id'     => 'test-widget-null-website',
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		);
		$instance = array(
			'title'     => 'Null Website Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '1',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Null Website Club', $output );
		$this->assertStringNotContainsString( 'target="_blank"', $output );
	}

	public function test_widget_omits_club_details_when_club_name_disabled(): void {
		$club = new Club();
		$club->set_id( 33 );
		$club->set_name( 'Secret Tennis Club' );
		$club->set_shortcode( 'stc' );
		$club->set_facilities( '3 grass courts' );
		$club->set_address( '10 Court Lane' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->willReturn( array( $club ) );

		$widget = new Club_Widget( club_service: $club_service );

		$args = array(
			'widget_id'     => 'test-widget-club-name-disabled',
			'before_widget' => '<div>',
			'after_widget'  => '</div>',
			'before_title'  => '<h3>',
			'after_title'   => '</h3>',
		);
		$instance = array(
			'title'     => 'Hidden Name Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '0',
			'club_link' => '1',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'roll-club', $output );
		$this->assertStringNotContainsString( 'Secret Tennis Club', $output );
		$this->assertStringNotContainsString( '3 grass courts', $output );
	}

	public function test_widget_flushes_output_without_caching_in_preview_mode(): void {
		$GLOBALS['wp_stubs_cache_enabled'] = true;

		$club = new Club();
		$club->set_id( 34 );
		$club->set_name( 'Preview Mode Club' );
		$club->set_shortcode( 'pmc' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->willReturn( array( $club ) );

		$widget = $this->getMockBuilder( Club_Widget::class )
			->setConstructorArgs( array( false, $club_service ) )
			->onlyMethods( array( 'is_preview' ) )
			->getMock();

		$widget->method( 'is_preview' )->willReturn( true );

		$args = array(
			'widget_id'     => 'test-widget-preview-mode',
			'before_widget' => '<aside>',
			'after_widget'  => '</aside>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		$instance = array(
			'title'     => 'Preview Clubs',
			'num_items' => 5,
			'club_type' => 'all',
			'orderby'   => 'name',
			'club_name' => '1',
			'club_link' => '',
		);

		ob_start();
		$widget->widget( $args, $instance );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Preview Mode Club', $output );

		$cache = wp_cache_get( 'racketmanager', 'widget' );
		$this->assertFalse( $cache );
	}
}
