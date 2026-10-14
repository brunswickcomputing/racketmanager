<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

class Manages_Wordpress_Globals_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private object $helper;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->helper = new class {
            use Manages_Wordpress_Globals {
                backup_globals as public;
                restore_globals as public;
            }
        };
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_restores_present_superglobals_and_unsets_absent_globals(): void {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_HOST']      = 'example.test';
        $_GET['test_key']          = 'initial_value';
        $_POST['post_key']         = 'initial_post';
        $_FILES['file_key']        = [ 'name' => 'test.txt' ];
        $_REQUEST['req_key']       = 'initial_req';
        unset( $GLOBALS['racketmanager'], $GLOBALS['wpdb'], $GLOBALS['wp_mail_calls'] );

        $this->helper->backup_globals();

        // Mutate in test
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST']      = 'mutated.test';
        $_GET['test_key']          = 'mutated_value';
        $_POST['post_key']         = 'mutated_post';
        $_FILES['file_key']        = [ 'name' => 'mutated.txt' ];
        $_REQUEST['req_key']       = 'mutated_req';
        $GLOBALS['racketmanager']  = new stdClass();
        $GLOBALS['wpdb']           = $this->createStub( wpdb::class );
        $GLOBALS['wp_mail_calls']  = [ 'mail1' ];

        // Restore
        $this->helper->restore_globals();

        $this->assertSame( 'GET', $_SERVER['REQUEST_METHOD'] );
        $this->assertSame( 'example.test', $_SERVER['HTTP_HOST'] );
        $this->assertSame( 'initial_value', $_GET['test_key'] );
        $this->assertSame( 'initial_post', $_POST['post_key'] );
        $this->assertSame( [ 'name' => 'test.txt' ], $_FILES['file_key'] );
        $this->assertSame( 'initial_req', $_REQUEST['req_key'] );
        $this->assertArrayNotHasKey( 'racketmanager', $GLOBALS );
        $this->assertArrayNotHasKey( 'wpdb', $GLOBALS );
        $this->assertArrayNotHasKey( 'wp_mail_calls', $GLOBALS );
    }

    public function test_restores_absent_server_request_method(): void {
        unset( $_SERVER['REQUEST_METHOD'] );

        $this->helper->backup_globals();

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $this->helper->restore_globals();

        $this->assertArrayNotHasKey( 'REQUEST_METHOD', $_SERVER );
    }

    public function test_restores_existing_global_objects(): void {
        $rm_mock   = new stdClass();
        $wpdb_mock = $this->createStub( wpdb::class );

        $GLOBALS['racketmanager'] = $rm_mock;
        $GLOBALS['wpdb']          = $wpdb_mock;

        $this->helper->backup_globals();

        $GLOBALS['racketmanager'] = new stdClass();
        $GLOBALS['wpdb']          = null;

        $this->helper->restore_globals();

        $this->assertSame( $rm_mock, $GLOBALS['racketmanager'] );
        $this->assertSame( $wpdb_mock, $GLOBALS['wpdb'] );
    }

    public function test_manages_nonce_verify_stub_lifecycle(): void {
        unset( $GLOBALS['wp_stubs_wp_verify_nonce_return'] );

        $this->helper->backup_globals();

        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;

        $this->helper->restore_globals();

        $this->assertArrayNotHasKey( 'wp_stubs_wp_verify_nonce_return', $GLOBALS );

        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;

        $this->helper->backup_globals();

        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;

        $this->helper->restore_globals();

        $this->assertFalse( $GLOBALS['wp_stubs_wp_verify_nonce_return'] );
    }

    public function test_manages_filesystem_and_cache_stub_keys(): void {
        $keys = array( 'wp_filesystem', 'wp_stubs_filesystem_method', 'wp_stubs_wp_filesystem_return', 'wp_stubs_cache_deleted' );
        foreach ( $keys as $key ) {
            unset( $GLOBALS[ $key ] );
        }
        $GLOBALS['wp_stubs_cache_deleted'] = array( 'a' );

        $this->helper->backup_globals();

        foreach ( $keys as $key ) {
            $GLOBALS[ $key ] = 'changed';
        }

        $this->helper->restore_globals();

        $this->assertArrayNotHasKey( 'wp_filesystem', $GLOBALS );
        $this->assertArrayNotHasKey( 'wp_stubs_filesystem_method', $GLOBALS );
        $this->assertArrayNotHasKey( 'wp_stubs_wp_filesystem_return', $GLOBALS );
        $this->assertSame( array( 'a' ), $GLOBALS['wp_stubs_cache_deleted'] );
        unset( $GLOBALS['wp_stubs_cache_deleted'] );
    }
}
