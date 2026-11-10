<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\View;

use PHPUnit\Framework\TestCase;
use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;
use RuntimeException;

class Shortcode_Template_Renderer_Test extends TestCase {
    private string $theme_dir = '';

    protected function setUp(): void {
        parent::setUp();
        $this->theme_dir = sys_get_temp_dir() . '/rm-theme-' . uniqid( '', true );
        mkdir( $this->theme_dir . '/racketmanager', 0777, true );
        unset( $GLOBALS['wp_stubs_stylesheet_directory'], $GLOBALS['wp_stubs_template_directory'] );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_stylesheet_directory'], $GLOBALS['wp_stubs_template_directory'] );
        foreach ( glob( $this->theme_dir . '/racketmanager/*.php' ) ?: [] as $file ) {
            unlink( $file );
        }
        rmdir( $this->theme_dir . '/racketmanager' );
        rmdir( $this->theme_dir );
        parent::tearDown();
    }

    private function create_template_renderer( View_Renderer_Interface $renderer ): Shortcode_Template_Renderer {
        return new Shortcode_Template_Renderer( $renderer, new Club_Presenter(), new Player_Presenter() );
    }

    public function test_uses_type_directory_and_injects_default_presenters(): void {
        $captured = [];
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            static function ( string $path, $vm, array $vars ) use ( &$captured ): string {
                $captured = [ 'path' => $path, 'vars' => $vars ];

                return 'html';
            }
        );

        $output = ( $this->create_template_renderer( $renderer ) )->render( 'score', [ 'a' => 1 ], 'match' );

        $this->assertSame( 'html', $output );
        $this->assertSame( 'match/score', $captured['path'] );
        $this->assertSame( 1, $captured['vars']['a'] );
        $this->assertArrayHasKey( 'club_presenter', $captured['vars'] );
        $this->assertArrayHasKey( 'player_presenter', $captured['vars'] );
    }

    public function test_root_templates_have_no_directory_prefix_and_unknown_types_fall_back_to_root(): void {
        $paths    = [];
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            static function ( string $path ) use ( &$paths ): string {
                $paths[] = $path;

                return '';
            }
        );
        $subject = $this->create_template_renderer( $renderer );

        $subject->render( 'clubs' );
        $subject->render( 'clubs', [], 'nonsense' );

        $this->assertSame( [ 'clubs', 'clubs' ], $paths );
    }

    public function test_flat_theme_override_wins_over_plugin_template(): void {
        file_put_contents( $this->theme_dir . '/racketmanager/score.php', '<?php echo "theme:" . $vm->answer . ":" . $vars["answer"];' );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( $this->never() )->method( 'render_to_string' );

        $output = ( $this->create_template_renderer( $renderer ) )->render( 'score', [ 'answer' => 42 ], 'match' );

        $this->assertSame( 'theme:42:42', $output );
    }

    public function test_flat_theme_override_protects_closure_locals_from_clobbering(): void {
        file_put_contents(
            $this->theme_dir . '/racketmanager/clobber.php',
            '<?php echo "vars_is_array:" . (is_array($vars) ? "yes" : "no") . ":answer:" . ($vars["answer"] ?? "none");'
        );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( $this->never() )->method( 'render_to_string' );

        $output = ( $this->create_template_renderer( $renderer ) )->render(
            'clobber',
            [
                'answer' => 42,
                'vars'   => 'evil_vars_string',
                '__file' => '/etc/passwd',
            ],
            'match'
        );

        $this->assertSame( 'vars_is_array:yes:answer:42', $output );
    }

    public function test_parent_theme_flat_override_is_also_honoured(): void {
        file_put_contents( $this->theme_dir . '/racketmanager/players.php', 'parent-theme' );
        $GLOBALS['wp_stubs_template_directory'] = $this->theme_dir;

        $output = ( $this->create_template_renderer( $this->createStub( View_Renderer_Interface::class ) ) )->render( 'players', [], 'club' );

        $this->assertSame( 'parent-theme', $output );
    }

    public function test_override_that_throws_leaves_output_buffer_level_unchanged(): void {
        file_put_contents( $this->theme_dir . '/racketmanager/boom.php', '<?php echo "partial"; throw new \RuntimeException( "boom" );' );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;
        $level                                    = ob_get_level();

        try {
            ( $this->create_template_renderer( $this->createStub( View_Renderer_Interface::class ) ) )->render( 'boom' );
            $this->fail( 'Expected exception' );
        } catch ( RuntimeException $e ) {
            $this->assertSame( 'boom', $e->getMessage() );
        }

        $this->assertSame( $level, ob_get_level() );
    }

    public function test_missing_template_renders_alert_naming_template_and_directory(): void {
        $calls    = [];
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            static function ( string $path, $vm, array $vars ) use ( &$calls ): string {
                $calls[] = [ 'path' => $path, 'vars' => $vars ];
                if ( 'club/nope' === $path ) {
                    throw new Template_Not_Found_Exception( 'missing' );
                }

                return 'alert-html';
            }
        );

        $output = ( $this->create_template_renderer( $renderer ) )->render( 'nope', [], 'club' );

        $this->assertSame( 'alert-html', $output );
        $this->assertSame( 'alert', $calls[1]['path'] );
        $this->assertSame( 'danger', $calls[1]['vars']['class'] );
        $this->assertStringContainsString( 'Could not load template nope.php from templates/club directory', $calls[1]['vars']['msg'] );
    }

    public function test_render_alert_variant_selects_alert_template(): void {
        $paths    = [];
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            static function ( string $path ) use ( &$paths ): string {
                $paths[] = $path;

                return '';
            }
        );
        $subject = $this->create_template_renderer( $renderer );

        $subject->render_alert( 'x' );
        $subject->render_alert( 'x', 'modal' );

        $this->assertSame( [ 'alert', 'alert-modal' ], $paths );
    }

    public function test_view_presenter_renders_templates_and_errors(): void {
        $calls    = [];
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            static function ( string $path, $vm, array $vars ) use ( &$calls ): string {
                $calls[] = [ 'path' => $path, 'vars' => $vars ];

                return 'out:' . $path;
            }
        );
        $presenter = new Shortcode_View_Presenter( $this->create_template_renderer( $renderer ) );

        $this->assertSame( 'out:club/roles', $presenter->render( new Shortcode_View_Model( 'roles', [ 'x' => 1 ], 'club' ) ) );
        $this->assertSame( 1, $calls[0]['vars']['x'] );

        $this->assertSame( 'out:alert-modal', $presenter->render( Shortcode_View_Model::error( 'bad', 'modal' ) ) );
        $this->assertSame( 'bad', $calls[1]['vars']['msg'] );

        $this->assertSame( 'out:alert', $presenter->render_error( 'worse' ) );
        $this->assertSame( 'worse', $calls[2]['vars']['msg'] );
    }

    public function test_theme_override_binds_vm_and_does_not_extract_locals(): void {
        file_put_contents(
            $this->theme_dir . '/racketmanager/scope-check.php',
            '<?php echo "vm_foo:" . ($vm->foo ?? "none") . "|local_foo:" . ($foo ?? "none");'
        );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;

        $renderer = $this->createStub( View_Renderer_Interface::class );
        $output   = ( $this->create_template_renderer( $renderer ) )->render( 'scope-check', [ 'foo' => 'bar' ] );

        $this->assertSame( 'vm_foo:bar|local_foo:none', $output );
    }

    public function test_theme_override_flattens_vm_array_without_nesting(): void {
        file_put_contents(
            $this->theme_dir . '/racketmanager/vm-array.php',
            '<?php echo "score:" . $vm->score . "|winner:" . $vm->winner . "|has_nested_vm:" . (isset($vm->vm) ? "yes" : "no");'
        );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;

        $renderer = $this->createStub( View_Renderer_Interface::class );
        $output   = ( $this->create_template_renderer( $renderer ) )->render(
            'vm-array',
            [ 'vm' => [ 'score' => 10, 'winner' => 'Alice' ] ]
        );

        $this->assertSame( 'score:10|winner:Alice|has_nested_vm:no', $output );
    }

    public function test_theme_override_passes_exact_object_vm_instance(): void {
        file_put_contents(
            $this->theme_dir . '/racketmanager/vm-identity.php',
            '<?php $GLOBALS["rm_shortcode_seen_vm"] = $vm;'
        );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;

        $custom_vm     = new \stdClass();
        $custom_vm->id = 888;

        $renderer = $this->createStub( View_Renderer_Interface::class );
        ( $this->create_template_renderer( $renderer ) )->render(
            'vm-identity',
            [ 'vm' => $custom_vm ]
        );

        $this->assertSame( $custom_vm, $GLOBALS['rm_shortcode_seen_vm'] );
        unset( $GLOBALS['rm_shortcode_seen_vm'] );
    }

    public function test_render_passes_exact_object_vm_instance_to_view_renderer(): void {
        $captured_vm = null;
        $renderer    = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            static function ( string $path, $vm, array $vars ) use ( &$captured_vm ): string {
                $captured_vm = $vm;
                return 'rendered';
            }
        );

        $custom_vm     = new \stdClass();
        $custom_vm->id = 999;

        $output = ( $this->create_template_renderer( $renderer ) )->render(
            'team-list',
            [ 'vm' => $custom_vm, 'page' => 1 ],
            'club'
        );

        $this->assertSame( 'rendered', $output );
        $this->assertSame( $custom_vm, $captured_vm );
    }
}
