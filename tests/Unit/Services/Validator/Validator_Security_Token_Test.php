<?php

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Validator_Security_Token_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $_REQUEST = array();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_explicit_request_with_missing_nonce_overrides_request_global(): void {
        $_REQUEST = array( 'security' => 'valid-global-token' );
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;

        $details = ( new Validator() )->check_security_token( 'security', 'ajax-nonce', array() )->get_details();

        $this->assertTrue( $details->error );
        $this->assertSame( 403, $details->status );
        $this->assertStringContainsString( 'problem with your request', $details->msg );
    }

    public function test_explicit_request_with_invalid_nonce_returns_invalid_token_error(): void {
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = false;

        $details = ( new Validator() )->check_security_token( 'security', 'ajax-nonce', array( 'security' => 'invalid' ) )->get_details();

        $this->assertTrue( $details->error );
        $this->assertSame( 403, $details->status );
        $this->assertStringContainsString( 'expired or is invalid', $details->msg );
    }

    public function test_legacy_call_reads_nonce_from_request_global(): void {
        $_REQUEST = array( 'security' => 'valid-global-token' );
        $GLOBALS['wp_stubs_wp_verify_nonce_return'] = true;

        $details = ( new Validator() )->check_security_token()->get_details();

        $this->assertFalse( $details->error );
        $this->assertNull( $details->status );
    }
}
