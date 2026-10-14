<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Util;

require_once __DIR__ . '/../../wp-stubs.php';

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\View\Php_View_Renderer;
use RuntimeException;
use stdClass;

final class Php_View_Renderer_Test extends TestCase {

    private string $template_base;

    protected function setUp(): void {
        $this->template_base = __DIR__ . '/../../templates/';
    }

    public function test_render_outputs_correct_template(): void {
        $renderer = new Php_View_Renderer( $this->template_base );
        $vm = new stdClass();
        $vm->name = 'World';

        ob_start();
        try {
            $renderer->render( 'test/basic', $vm );
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertEquals( 'Hello World! Renderer: OK', $output );
    }

    public function test_render_extracts_additional_vars(): void {
        $renderer = new Php_View_Renderer( $this->template_base );
        $vm = new stdClass();
        $vm->name = 'Extra';

        ob_start();
        try {
            $renderer->render( 'test/basic', $vm, [ 'extra' => 'Information' ] );
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertEquals( 'Hello Extra! Extra: Information Renderer: OK', $output );
    }

    public function test_render_throws_exception_if_template_missing(): void {
        $renderer = new Php_View_Renderer( $this->template_base );
        $vm = new stdClass();

        $this->expectException( RuntimeException::class );
        $this->expectExceptionMessage( 'Template not found at:' );

        $renderer->render( 'test/missing', $vm );
    }

    public function test_render_provides_renderer_variable_for_recursion(): void {
        $renderer = new Php_View_Renderer( $this->template_base );
        $vm = new stdClass();
        $vm->name = 'Recursive';

        ob_start();
        try {
            $renderer->render( 'test/recursive', $vm );
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        $this->assertEquals( 'Hello Recursive! Renderer: OK', $output );
    }

    public function test_render_exposes_vars_for_array_view_model(): void {
        $renderer = new Php_View_Renderer( $this->template_base );
        $output   = $renderer->render_to_string( 'test/vars', [ 'a' => 'apple' ], [ 'b' => 'banana' ] );

        $this->assertSame( 'vars_count:2|a:apple|b:banana|extracted_a:apple', $output );
    }

    public function test_render_exposes_vars_for_object_view_model(): void {
        $renderer = new Php_View_Renderer( $this->template_base );
        $vm       = new stdClass();
        $output   = $renderer->render_to_string( 'test/vars', $vm, [ 'a' => 'cherry' ] );

        $this->assertSame( 'vars_count:1|a:cherry|b:none|extracted_a:cherry', $output );
    }

    public function test_render_protects_reserved_locals_from_clobbering_with_array_view_model(): void {
        $renderer = new Php_View_Renderer( $this->template_base );

        $malicious_vars = [
            'renderer'        => 'evil_renderer',
            'vm'              => 'evil_vm',
            'vars'            => 'evil_vars',
            'full_path'       => '/etc/passwd',
            'resolved_path'   => '/etc/hosts',
            'template_path'   => 'evil_template',
            'view_model'      => 'evil_view_model',
            'additional_vars' => 'evil_additional_vars',
            'custom'          => 'extracted_custom_value',
        ];

        $output = $renderer->render_to_string( 'test/reserved', [ 'a' => 'from_vm' ], $malicious_vars );

        $this->assertSame( 'renderer_ok:yes|vm_type:array|vars_type:array|vars_count:10|custom:extracted_custom_value', $output );
    }

    public function test_render_protects_reserved_locals_from_clobbering_with_object_view_model(): void {
        $renderer = new Php_View_Renderer( $this->template_base );
        $vm       = new stdClass();
        $vm->name = 'Safe';

        $malicious_vars = [
            'renderer'        => 'evil_renderer',
            'vm'              => 'evil_vm',
            'vars'            => 'evil_vars',
            'full_path'       => '/etc/passwd',
            'resolved_path'   => '/etc/hosts',
            'template_path'   => 'evil_template',
            'view_model'      => 'evil_view_model',
            'additional_vars' => 'evil_additional_vars',
            'custom'          => 'extracted_custom_value',
        ];

        $output = $renderer->render_to_string( 'test/reserved', $vm, $malicious_vars );

        $this->assertSame( 'renderer_ok:yes|vm_type:stdClass|vars_type:array|vars_count:9|custom:extracted_custom_value', $output );
    }

    public function test_render_to_string_cleans_buffer_on_exception(): void {
        $renderer    = new Php_View_Renderer( $this->template_base );
        $level_start = ob_get_level();

        try {
            $renderer->render_to_string( 'test/boom', [] );
            $this->fail( 'Expected RuntimeException' );
        } catch ( RuntimeException $e ) {
            $this->assertSame( 'render_error', $e->getMessage() );
        }

        $this->assertSame( $level_start, ob_get_level() );
    }

    public function test_render_preserves_precedence_and_vars_integrity_with_overriding_vars(): void {
        $renderer = new Php_View_Renderer( $this->template_base );

        // additional_vars overriding view_model, plus malicious variable keys
        $output = $renderer->render_to_string(
            'test/vars',
            [ 'a' => 'vm_a', 'b' => 'vm_b', 'additional_vars' => [ 'injected' => 'bad' ] ],
            [ 'b' => 'override_b', 'view_model' => 'corrupt' ]
        );

        // $vars should have 4 keys (a, b, additional_vars, view_model), $vars['a'] is 'vm_a', $vars['b'] is 'override_b', $a is extracted as 'vm_a'
        $this->assertSame( 'vars_count:4|a:vm_a|b:override_b|extracted_a:vm_a', $output );
    }
}
