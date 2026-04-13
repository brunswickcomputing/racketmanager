<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Export_Admin_Controller;
use Racketmanager\RacketManager;
use Racketmanager\Services\Exporter;
use Racketmanager\Services\Container\Simple_Container;

require_once __DIR__ . '/../../../wp-stubs.php';

// Stub missing WordPress functions
if ( ! function_exists( 'check_admin_referer' ) ) {
    function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) { return true; }
}
if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) { return $text; }
}
if ( ! function_exists( 'esc_html_e' ) ) {
    function esc_html_e( $text, $domain = 'default' ) { echo $text; }
}

#[AllowMockObjectsWithoutExpectations]
final class Export_Admin_Controller_Test extends TestCase {

    private $racketmanager;
    private $exporter;
    private $container;
    private $original_racketmanager;

    protected function setUp(): void {
        parent::setUp();
        $this->original_racketmanager = $GLOBALS['racketmanager'] ?? null;
        $this->racketmanager = $this->createMock( RacketManager::class );
        $this->container = $this->createMock( Simple_Container::class );
        $this->exporter = $this->createMock( Exporter::class );

        $this->racketmanager->container = $this->container;
        $this->container->method( 'get' )->willReturnCallback( function( $id ) {
            if ( 'exporter' === $id ) return $this->exporter;
            return $this->createMock( \stdClass::class );
        } );

        $GLOBALS['racketmanager'] = $this->racketmanager;
        $_GET = [];
    }

    protected function tearDown(): void {
        $GLOBALS['racketmanager'] = $this->original_racketmanager;
        parent::tearDown();
    }

    public function test_handle_export_exits_early_if_no_export_param(): void {
        $controller = new Export_Admin_Controller( $this->racketmanager );
        $controller->handle_export();
        $this->assertTrue(true);
    }

    public function test_handle_export_calendar_works(): void {
        $_GET['racketmanager_export'] = 'calendar';
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->exporter->method( 'calendar' )->willReturn( 'ICS CONTENT' );

        $controller = $this->getMockBuilder( Export_Admin_Controller::class )
            ->setConstructorArgs( [$this->racketmanager] )
            ->onlyMethods( ['terminate'] )
            ->getMock();
        
        $controller->expects( self::once() )->method( 'terminate' );

        ob_start();
        $controller->handle_export();
        $output = ob_get_clean();
        
        self::assertSame( 'ICS CONTENT', $output );
        unset($GLOBALS['wp_stubs_current_user_can']);
    }

    public function test_handle_export_report_results_works(): void {
        $_GET['racketmanager_export'] = 'report_results';
        $GLOBALS['wp_stubs_current_user_can'] = true;

        $this->exporter->method( 'report_results' )->willReturn( 'CSV CONTENT' );

        $controller = $this->getMockBuilder( Export_Admin_Controller::class )
            ->setConstructorArgs( [$this->racketmanager] )
            ->onlyMethods( ['terminate'] )
            ->getMock();
        
        $controller->expects( self::once() )->method( 'terminate' );

        ob_start();
        $controller->handle_export();
        $output = ob_get_clean();
        
        self::assertSame( 'CSV CONTENT', $output );
        unset($GLOBALS['wp_stubs_current_user_can']);
    }
}
