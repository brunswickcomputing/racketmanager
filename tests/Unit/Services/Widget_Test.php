<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Widget;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

#[AllowMockObjectsWithoutExpectations]
final class Widget_Test extends TestCase {
	use Manages_Wordpress_Globals;

	protected function setUp(): void {
		parent::setUp();
		$this->backup_globals();
		unset( $GLOBALS['racketmanager'] );
	}

	protected function tearDown(): void {
		$this->restore_globals();
		parent::tearDown();
	}

	public function test_widget_queries_clubs_via_injected_club_service(): void {
		$club = new Club();
		$club->set_id( 1 );
		$club->set_name( 'Test Tennis Club' );
		$club->set_shortcode( 'ttc' );

		$club_service = $this->createMock( Club_Service::class );
		$club_service->expects( $this->once() )
			->method( 'get_clubs' )
			->with( $this->callback( function ( $args ) {
				return isset( $args['limit'] ) && 5 === $args['limit']
					&& isset( $args['club_type'] ) && 'affiliated' === $args['club_type']
					&& isset( $args['orderby'] ) && 'name' === $args['orderby'];
			} ) )
			->willReturn( array( $club ) );

		$widget = new Widget( club_service: $club_service );

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

		$club_repository = $this->createMock( Club_Repository_Interface::class );
		$club_repository->expects( $this->once() )
			->method( 'find_all' )
			->with( $this->callback( function ( $args ) {
				return isset( $args['limit'] ) && 999999 === $args['limit']
					&& isset( $args['club_type'] ) && 'all' === $args['club_type'];
			} ) )
			->willReturn( array( $club ) );

		$widget = new Widget( club_repository: $club_repository );

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

	public function test_widget_constructs_without_global_racketmanager(): void {
		$this->assertArrayNotHasKey( 'racketmanager', $GLOBALS );

		$widget = new Widget();
		$this->assertInstanceOf( Widget::class, $widget );
	}
}
