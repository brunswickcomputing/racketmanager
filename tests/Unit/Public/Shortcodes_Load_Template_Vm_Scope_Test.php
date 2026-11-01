<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Public;

require_once __DIR__ . '/../../wp-stubs.php';

use PHPUnit\Framework\TestCase;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use stdClass;

final class Shortcodes_Load_Template_Vm_Scope_Test extends TestCase {
    private string $theme_dir = '';

    protected function setUp(): void {
        parent::setUp();
        $this->theme_dir = sys_get_temp_dir() . '/rm-shortcodes-theme-' . uniqid( '', true );
        mkdir( $this->theme_dir . '/racketmanager', 0777, true );
        $GLOBALS['wp_stubs_stylesheet_directory'] = $this->theme_dir;
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_stylesheet_directory'] );
        foreach ( glob( $this->theme_dir . '/racketmanager/*.php' ) ?: [] as $file ) {
            @unlink( $file );
        }
        @rmdir( $this->theme_dir . '/racketmanager' );
        @rmdir( $this->theme_dir );
        parent::tearDown();
    }

    private function create_shortcodes(): Shortcodes {
        $app = $this->createStub( RacketManager::class );
        return new Shortcodes( $app );
    }

    public function test_load_template_theme_override_receives_vm_with_passed_vars_and_default_presenters(): void {
        file_put_contents(
            $this->theme_dir . '/racketmanager/test-tpl.php',
            '<?php echo "title:" . $vm->title . "|has_club_presenter:" . (property_exists($vm, "club_presenter") ? "yes" : "no") . "|has_player_presenter:" . (property_exists($vm, "player_presenter") ? "yes" : "no");'
        );

        $shortcodes = $this->create_shortcodes();
        $output     = $shortcodes->load_template( 'test-tpl', [ 'title' => 'Championship' ] );

        $this->assertSame( 'title:Championship|has_club_presenter:yes|has_player_presenter:yes', $output );
    }

    public function test_load_template_gives_precedence_to_passed_vm_object(): void {
        file_put_contents(
            $this->theme_dir . '/racketmanager/vm-obj.php',
            '<?php echo "vm_class:" . get_class($vm) . "|id:" . $vm->id; $GLOBALS["rm_shortcodes_seen_vm"] = $vm;'
        );

        $custom_vm     = new stdClass();
        $custom_vm->id = 555;

        $shortcodes = $this->create_shortcodes();
        $output     = $shortcodes->load_template( 'vm-obj', [ 'vm' => $custom_vm ] );

        $this->assertSame( 'vm_class:stdClass|id:555', $output );
        $this->assertSame( $custom_vm, $GLOBALS['rm_shortcodes_seen_vm'] );
        unset( $GLOBALS['rm_shortcodes_seen_vm'] );
    }

    public function test_load_template_flattens_passed_vm_array_without_nesting(): void {
        file_put_contents(
            $this->theme_dir . '/racketmanager/vm-arr.php',
            '<?php echo "score:" . $vm->score . "|winner:" . $vm->winner . "|has_nested_vm:" . (isset($vm->vm) ? "yes" : "no");'
        );

        $shortcodes = $this->create_shortcodes();
        $output     = $shortcodes->load_template(
            'vm-arr',
            [ 'vm' => [ 'score' => 21, 'winner' => 'Dave' ] ]
        );

        $this->assertSame( 'score:21|winner:Dave|has_nested_vm:no', $output );
    }

    public function test_load_template_does_not_extract_variables_into_local_scope(): void {
        file_put_contents(
            $this->theme_dir . '/racketmanager/no-locals.php',
            '<?php echo "local_set:" . (isset($extracted_var) ? "yes" : "no") . "|vm_val:" . ($vm->extracted_var ?? "none");'
        );

        $shortcodes = $this->create_shortcodes();
        $output     = $shortcodes->load_template( 'no-locals', [ 'extracted_var' => 'isolated' ] );

        $this->assertSame( 'local_set:no|vm_val:isolated', $output );
    }

    public function test_load_template_plugin_branch_binds_vm_when_no_theme_override_exists(): void {
        unset( $GLOBALS['wp_stubs_template_directory'] );

        $shortcodes = $this->create_shortcodes();
        $output     = $shortcodes->load_template( 'alert', [ 'msg' => 'Plugin branch message', 'class' => 'warning' ] );

        $this->assertStringContainsString( 'Plugin branch message', $output );
        $this->assertStringContainsString( 'alert--warning', $output );
    }

    public function test_load_template_plugin_branch_reports_missing_template(): void {
        $shortcodes = $this->create_shortcodes();
        $output     = $shortcodes->load_template( 'definitely-not-a-template', [ 'a' => 1 ] );

        $this->assertStringContainsString( 'definitely-not-a-template', $output );
    }
}
