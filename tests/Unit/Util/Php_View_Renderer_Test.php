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
}
