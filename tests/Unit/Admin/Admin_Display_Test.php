<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin;

use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Tests\Helpers\Creates_Admin_Delivery_Container;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionClass;
use ReflectionProperty;

require_once __DIR__ . '/../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Admin_Display_Test extends TestCase {
    use Creates_Admin_Delivery_Container;
    use Manages_Wordpress_Globals;

    private Simple_Container $container;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wp_stubs_added_actions'] = [];
        $GLOBALS['wp_stubs_added_filters'] = [];
        unset( $GLOBALS['wp_stubs_translate_callback'] );

        $this->container = $this->create_admin_delivery_container();
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_translate_callback'] );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_constructor_defers_translations_and_registers_init_hook(): void {
        $translation_calls = 0;
        $GLOBALS['wp_stubs_translate_callback'] = static function ( string $text, string $domain ) use ( &$translation_calls ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
            ++$translation_calls;
            return $text;
        };

        $racketmanager = new ReflectionClass( RacketManager::class )->newInstanceWithoutConstructor();
        $admin_display = new Admin_Display( $racketmanager, $this->container );

        self::assertSame( 0, $translation_calls, 'Admin_Display must not execute translation lookups during constructor execution.' );

        $ref = new ReflectionProperty( Admin_Display::class, 'no_permission' );
        self::assertSame(
            'You do not have permission to perform this task',
            $ref->getValue( $admin_display ),
            'Default fallback permission string must be preserved on instantiation.'
        );

        $actions = $GLOBALS['wp_stubs_added_actions']['init'] ?? [];
        $registered = false;
        foreach ( $actions as $action ) {
            if ( isset( $action['callback'] ) && [ $admin_display, 'load_translations' ] === $action['callback'] ) {
                $registered = true;
                break;
            }
        }

        self::assertTrue( $registered, 'Admin_Display must register [ $this, \'load_translations\' ] on the init action.' );
    }

    public function test_load_translations_updates_no_permission_property(): void {
        $racketmanager = new ReflectionClass( RacketManager::class )->newInstanceWithoutConstructor();
        $admin_display = new Admin_Display( $racketmanager, $this->container );

        $ref = new ReflectionProperty( Admin_Display::class, 'no_permission' );

        $GLOBALS['wp_stubs_translate_callback'] = static function ( string $text, string $domain ): string {
            if ( 'You do not have permission to perform this task' === $text && 'racketmanager' === $domain ) {
                return 'Sie haben keine Berechtigung für diese Aktion';
            }
            return $text;
        };

        $admin_display->load_translations();

        self::assertSame(
            'Sie haben keine Berechtigung für diese Aktion',
            $ref->getValue( $admin_display ),
            'Invoking load_translations must update no_permission with translated text.'
        );

        // Verify idempotency
        $admin_display->load_translations();
        self::assertSame(
            'Sie haben keine Berechtigung für diese Aktion',
            $ref->getValue( $admin_display ),
            'Subsequent calls to load_translations must remain stable and idempotent.'
        );
    }

    public function test_constructor_throws_exception_when_container_is_missing(): void {
        $this->expectException( LogicException::class );
        $this->expectExceptionMessageIsOrContains( 'Admin_Display requires a Simple_Container instance.' );

        new Admin_Display( null, null );
    }

    public function test_plugin_textdomain_is_registered_on_init_action(): void {
        $source = (string) file_get_contents( dirname( __DIR__, 3 ) . '/racketmanager.php' ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        self::assertMatchesRegularExpression(
            '/add_action\(\s*\'init\'\s*,\s*static\s+function\s*\(\)\s*:\s*void\s*\{\s*load_plugin_textdomain\(\s*\'racketmanager\'\s*,\s*false\s*,\s*\'racketmanager\/languages\'\s*\);\s*}\s*\);/',
            $source,
            'racketmanager.php must register load_plugin_textdomain within an init action hook.'
        );
    }
}
