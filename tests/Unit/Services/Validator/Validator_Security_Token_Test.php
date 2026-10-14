<?php

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Validator\Validator;

class Validator_Security_Token_Test extends TestCase {
    private array $original_request;
    private mixed $original_nonce_result;
    private bool $had_nonce_result;

    protected function setUp(): void {
        parent::setUp();
        $this->original_request    = $_REQUEST;
        $this->had_nonce_result   = array_key_exists( 'wp_stubs_wp_verify_nonce_return', $GLOBALS );
        $this->original_nonce_result = $GLOBALS['wp_stubs_wp_verify_nonce_return'] ?? null;
        $_REQUEST                 = array();
    }

    protected function tearDown(): void {
        $_REQUEST = $this->original_request;
        if ( $this->had_nonce_result ) {
            $GLOBALS['wp_stubs_wp_verify_nonce_return'] = $this->original_nonce_result;
        } else {
            unset( $GLOBALS['wp_stubs_wp_verify_nonce_return'] );
        }
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
