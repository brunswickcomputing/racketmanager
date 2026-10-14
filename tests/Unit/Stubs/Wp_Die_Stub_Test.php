<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;
use WP_Error;

/**
 * Tests for the wp_die test stub.
 */
class Wp_Die_Stub_Test extends TestCase {

    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_last_wp_die'],
            $GLOBALS['wp_stubs_wp_die_handler'],
            $GLOBALS['wp_stubs_throw_on_wp_die']
        );
        parent::tearDown();
    }

    /**
     * @noinspection PhpUnreachableStatementInspection
     */
    public function test_wp_die_records_globals(): void {
        wp_die( 'Test message', 'Test Title', [ 'response' => 403 ] );

        self::assertIsArray( $GLOBALS['wp_stubs_last_wp_die'] );
        self::assertSame( 'Test message', $GLOBALS['wp_stubs_last_wp_die']['message'] );
        self::assertSame( 'Test Title', $GLOBALS['wp_stubs_last_wp_die']['title'] );
        self::assertSame( [ 'response' => 403 ], $GLOBALS['wp_stubs_last_wp_die']['args'] );
    }

    /**
     * @noinspection PhpUnreachableStatementInspection
     */
    public function test_wp_die_calls_custom_handler(): void {
        $GLOBALS['wp_stubs_wp_die_handler'] = function ( $message, $title, $args ): string {
            return "handled: {$message}|{$title}";
        };

        $result = wp_die( 'Custom msg', 'Custom title', [] );

        self::assertSame( 'handled: Custom msg|Custom title', $result );
    }

    public function test_wp_die_throws_runtime_exception_with_scalar_and_status(): void {
        $GLOBALS['wp_stubs_throw_on_wp_die'] = true;

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Forbidden access' );
        $this->expectExceptionCode( 403 );

        wp_die( 'Forbidden access', '', [ 'response' => 403 ] );
    }

    public function test_wp_die_throws_runtime_exception_with_numeric_args(): void {
        $GLOBALS['wp_stubs_throw_on_wp_die'] = true;

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Error occurred' );
        $this->expectExceptionCode( 404 );

        wp_die( 'Error occurred', '', 404 );
    }

    public function test_wp_die_throws_runtime_exception_with_default_500(): void {
        $GLOBALS['wp_stubs_throw_on_wp_die'] = true;

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Server error' );
        $this->expectExceptionCode( 500 );

        wp_die( 'Server error' );
    }

    public function test_wp_die_throws_runtime_exception_with_wp_error(): void {
        $GLOBALS['wp_stubs_throw_on_wp_die'] = true;

        $wpError = new WP_Error( 'invalid_token', 'Invalid auth token' );

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Invalid auth token' );
        $this->expectExceptionCode( 401 );

        wp_die( $wpError, '', [ 'response' => 401 ] );
    }

    public function test_wp_die_throws_runtime_exception_with_stringable_object(): void {
        $GLOBALS['wp_stubs_throw_on_wp_die'] = true;

        $stringable = new class {
            public function __toString(): string {
                return 'Stringable message';
            }
        };

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'Stringable message' );
        $this->expectExceptionCode( 500 );

        wp_die( $stringable );
    }

    public function test_wp_die_throws_runtime_exception_with_non_stringable_fallback(): void {
        $GLOBALS['wp_stubs_throw_on_wp_die'] = true;

        $nonStringable = new \stdClass();

        $this->expectException( \RuntimeException::class );
        $this->expectExceptionMessage( 'wp_die called' );
        $this->expectExceptionCode( 500 );

        wp_die( $nonStringable );
    }
}
