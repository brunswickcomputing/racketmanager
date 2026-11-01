<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\View;

require_once __DIR__ . '/../../../wp-stubs.php';

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
use Racketmanager\Tests\Helpers\Typed_Scope_Vm;

/**
 * Covers the non-override branch of Shortcode_Template_Renderer, i.e. delegation to the real
 * Php_View_Renderer with plugin templates, including a readonly typed view model plus extras.
 */
final class Shortcode_Template_Renderer_Plugin_Branch_Test extends TestCase {
    private string $base = '';

    protected function setUp(): void {
        parent::setUp();
        unset( $GLOBALS['wp_stubs_stylesheet_directory'], $GLOBALS['wp_stubs_template_directory'] );
        $this->base = sys_get_temp_dir() . '/rm-plugin-branch-' . uniqid( '', true ) . '/';
        mkdir( $this->base . 'match', 0777, true );
    }

    protected function tearDown(): void {
        foreach ( glob( $this->base . 'match/*.php' ) ?: [] as $file ) {
            unlink( $file );
        }
        rmdir( $this->base . 'match' );
        rmdir( $this->base );
        parent::tearDown();
    }

    public function test_plugin_template_receives_array_vars_as_vm_without_extraction(): void {
        file_put_contents(
            $this->base . 'match/plain.php',
            '<?php echo "vm_title:" . $vm->title . "|local_title:" . ( isset( $title ) ? "yes" : "no" ) . "|presenter:" . ( isset( $vm->club_presenter ) ? "yes" : "no" );'
        );

        $subject = new Shortcode_Template_Renderer( new Php_View_Renderer( $this->base ) );
        $output  = $subject->render( 'plain', [ 'title' => 'Cup' ], 'match' );

        $this->assertSame( 'vm_title:Cup|local_title:no|presenter:yes', $output );
    }

    public function test_plugin_template_receives_readonly_typed_vm_untouched_and_extras_via_vars(): void {
        file_put_contents(
            $this->base . 'match/typed.php',
            '<?php echo get_class( $vm ) . "|title:" . $vm->title . "|extra:" . $vars["extra"] . "|presenter:" . ( isset( $vars["club_presenter"] ) ? "yes" : "no" ) . "|vm_has_extra:" . ( property_exists( $vm, "extra" ) ? "yes" : "no" );'
        );

        $vm      = new Typed_Scope_Vm( 'Typed title' );
        $subject = new Shortcode_Template_Renderer( new Php_View_Renderer( $this->base ) );
        $output  = $subject->render( 'typed', [ 'vm' => $vm, 'extra' => 'supplemental' ], 'match' );

        $this->assertSame( Typed_Scope_Vm::class . '|title:Typed title|extra:supplemental|presenter:yes|vm_has_extra:no', $output );
    }

    public function test_real_plugin_alert_template_renders_message_through_the_renderer(): void {
        $subject = new Shortcode_Template_Renderer( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ) );
        $output  = $subject->render_alert( 'Something broke' );

        $this->assertStringContainsString( 'Something broke', $output );
        $this->assertStringContainsString( 'alert--danger', $output );
    }
}
