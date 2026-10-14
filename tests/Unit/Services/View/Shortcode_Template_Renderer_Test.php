<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\View;

use PHPUnit\Framework\TestCase;
use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Presenters\Shortcode_View_Presenter;
use Racketmanager\Services\View\Shortcode_Template_Renderer;
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

    public function test_uses_type_directory_and_injects_default_presenters(): void {
        $captured = [];
        $renderer = $this->createStub( View_Renderer_Interface::class );
        $renderer->method( 'render_to_string' )->willReturnCallback(
            static function ( string $path, $vm, array $vars ) use ( &$captured ): string {
                $captured = [ 'path' => $path, 'vars' => $vars ];

                return 'html';
            }
        );

        $output = ( new Shortcode_Template_Renderer( $renderer ) )->render( 'score', [ 'a' => 1 ], 'match' );

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
        $subject = new Shortcode_Template_Renderer( $renderer );

        $subject->render( 'clubs' );
        $subject->render( 'clubs', [], 'nonsense' );

        $this->assertSame( [ 'clubs', 'clubs' ], $paths );
    }

    public function test_flat_theme_override_wins_over_plugin_template(): void {
        file_put_contents( $this->theme_dir . '/racketmanager/score.php', '<?php echo "theme:" . $answer . ":" . $vars["answer"];' );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;

        $renderer = $this->createMock( View_Renderer_Interface::class );
        $renderer->expects( $this->never() )->method( 'render_to_string' );

        $output = ( new Shortcode_Template_Renderer( $renderer ) )->render( 'score', [ 'answer' => 42 ], 'match' );

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

        $output = ( new Shortcode_Template_Renderer( $renderer ) )->render(
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

        $output = ( new Shortcode_Template_Renderer( $this->createStub( View_Renderer_Interface::class ) ) )->render( 'players', [], 'club' );

        $this->assertSame( 'parent-theme', $output );
    }

    public function test_override_that_throws_leaves_output_buffer_level_unchanged(): void {
        file_put_contents( $this->theme_dir . '/racketmanager/boom.php', '<?php echo "partial"; throw new \RuntimeException( "boom" );' );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;
        $level                                    = ob_get_level();

        try {
            ( new Shortcode_Template_Renderer( $this->createStub( View_Renderer_Interface::class ) ) )->render( 'boom' );
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

        $output = ( new Shortcode_Template_Renderer( $renderer ) )->render( 'nope', [], 'club' );

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
        $subject = new Shortcode_Template_Renderer( $renderer );

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
        $presenter = new Shortcode_View_Presenter( new Shortcode_Template_Renderer( $renderer ) );

        $this->assertSame( 'out:club/roles', $presenter->render( new Shortcode_View_Model( 'roles', [ 'x' => 1 ], 'club' ) ) );
        $this->assertSame( 1, $calls[0]['vars']['x'] );

        $this->assertSame( 'out:alert-modal', $presenter->render( Shortcode_View_Model::error( 'bad', 'modal' ) ) );
        $this->assertSame( 'bad', $calls[1]['vars']['msg'] );

        $this->assertSame( 'out:alert', $presenter->render_error( 'worse' ) );
        $this->assertSame( 'worse', $calls[2]['vars']['msg'] );
    }
}
