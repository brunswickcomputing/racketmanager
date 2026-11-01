<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\View;


use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Tests\Helpers\Typed_Scope_Vm;
use stdClass;

final class Php_View_Renderer_Vm_Scope_Test extends TestCase {
    private string $temp_dir;

    protected function setUp(): void {
        parent::setUp();
        $this->temp_dir = sys_get_temp_dir() . '/rm-test-vm-scope-' . uniqid( '', true ) . '/';
        mkdir( $this->temp_dir, 0777, true );
    }

    protected function tearDown(): void {
        $this->remove_directory_recursively( rtrim( $this->temp_dir, '/' ) );
        parent::tearDown();
    }

    private function remove_directory_recursively( string $dir ): void {
        if ( ! is_dir( $dir ) ) {
            return;
        }
        $items = array_diff( scandir( $dir ) ?: [], [ '.', '..' ] );
        foreach ( $items as $item ) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if ( is_dir( $path ) ) {
                $this->remove_directory_recursively( $path );
            } else {
                @unlink( $path );
            }
        }
        @rmdir( $dir );
    }

    public function test_render_injects_typed_object_vm_directly(): void {
        $template_file = $this->temp_dir . 'object-vm.php';
        file_put_contents(
            $template_file,
            '<?php echo "vm_class:" . get_class($vm) . "|title:" . $vm->title . "|score:" . $vm->score; ?>'
        );

        $renderer = new Php_View_Renderer( $this->temp_dir );
        $vm = new stdClass();
        $vm->title = 'Championship Final';
        $vm->score = 100;

        $output = $renderer->render_to_string( 'object-vm', $vm );

        $this->assertSame( 'vm_class:stdClass|title:Championship Final|score:100', $output );
    }

    public function test_render_encapsulates_array_input_into_object_vm(): void {
        $template_file = $this->temp_dir . 'array-vm.php';
        file_put_contents(
            $template_file,
            '<?php echo "is_object:" . (is_object($vm) ? "yes" : "no") . "|player:" . $vm->player . "|rank:" . $vm->rank; ?>'
        );

        $renderer = new Php_View_Renderer( $this->temp_dir );
        $data = [
            'player' => 'Alice Smith',
            'rank'   => 1,
        ];

        $output = $renderer->render_to_string( 'array-vm', $data );

        $this->assertSame( 'is_object:yes|player:Alice Smith|rank:1', $output );
    }

    public function test_render_prefers_explicit_vm_in_vars_array_when_object(): void {
        $template_file = $this->temp_dir . 'explicit-vm.php';
        file_put_contents(
            $template_file,
            '<?php echo "vm_id:" . $vm->id . "|extra:" . $vm->extra; ?>'
        );

        $renderer = new Php_View_Renderer( $this->temp_dir );
        $custom_vm = new stdClass();
        $custom_vm->id = 777;
        $custom_vm->extra = 'custom_val';

        $output = $renderer->render_to_string( 'explicit-vm', [ 'vm' => $custom_vm ] );

        $this->assertSame( 'vm_id:777|extra:custom_val', $output );
    }

    public function test_render_merges_additional_vars_into_encapsulated_vm(): void {
        $template_file = $this->temp_dir . 'merged-vars.php';
        file_put_contents(
            $template_file,
            '<?php echo "base:" . $vm->base . "|added:" . $vm->added; ?>'
        );

        $renderer = new Php_View_Renderer( $this->temp_dir );
        $output = $renderer->render_to_string( 'merged-vars', [ 'base' => 'original' ], [ 'added' => 'extension' ] );

        $this->assertSame( 'base:original|added:extension', $output );
    }

    public function test_render_does_not_extract_variables_into_local_scope(): void {
        $template_file = $this->temp_dir . 'no-extract.php';
        file_put_contents(
            $template_file,
            '<?php echo "item_set:" . (isset($item) ? "yes" : "no") . "|vm_item:" . $vm->item; ?>'
        );

        $renderer = new Php_View_Renderer( $this->temp_dir );
        $output = $renderer->render_to_string( 'no-extract', [ 'item' => 'test' ] );

        $this->assertSame( 'item_set:no|vm_item:test', $output );
    }

    public function test_render_handles_vm_array_in_vars_without_nesting(): void {
        $template_file = $this->temp_dir . 'vm-array.php';
        file_put_contents(
            $template_file,
            '<?php echo "score:" . $vm->score . "|winner:" . $vm->winner . "|round:" . $vm->round . "|has_nested_vm:" . (isset($vm->vm) ? "yes" : "no"); ?>'
        );

        $renderer = new Php_View_Renderer( $this->temp_dir );
        $output = $renderer->render_to_string(
            'vm-array',
            [ 'vm' => [ 'score' => 99, 'winner' => 'Bob' ], 'round' => 3 ]
        );

        $this->assertSame( 'score:99|winner:Bob|round:3|has_nested_vm:no', $output );
    }

    public function test_render_keeps_readonly_typed_vm_untouched_and_exposes_extras_via_vars(): void {
        file_put_contents(
            $this->temp_dir . 'typed-vm.php',
            '<?php echo get_class( $vm ) . "|title:" . $vm->title . "|extra:" . $vars["extra"] . "|vm_has_extra:" . ( property_exists( $vm, "extra" ) ? "yes" : "no" );'
        );

        $renderer = new Php_View_Renderer( $this->temp_dir );
        $vm       = new Typed_Scope_Vm( 'Readonly title' );

        $output = $renderer->render_to_string( 'typed-vm', $vm, [ 'extra' => 'supplemental', 'title' => 'OVERRIDE' ] );

        $this->assertSame( Typed_Scope_Vm::class . '|title:Readonly title|extra:supplemental|vm_has_extra:no', $output );
        $this->assertSame( 'Readonly title', $vm->title );
    }

    public function test_render_passes_the_exact_typed_vm_instance(): void {
        file_put_contents(
            $this->temp_dir . 'identity.php',
            '<?php $GLOBALS["rm_seen_vm"] = $vm;'
        );

        $renderer = new Php_View_Renderer( $this->temp_dir );
        $vm       = new Typed_Scope_Vm( 'Same' );

        $renderer->render_to_string( 'identity', $vm, [ 'extra' => 1 ] );

        $this->assertSame( $vm, $GLOBALS['rm_seen_vm'] );
        unset( $GLOBALS['rm_seen_vm'] );
    }
}
