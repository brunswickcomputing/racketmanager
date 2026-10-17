<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;

class Functions_Helper_Test extends TestCase {

    public function test_production_functions_helpers_subprocess(): void {
        $script = dirname( __DIR__ ) . '/Fixtures/production-functions-helpers.php';
        exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( $script ), $output, $exit_code );

        $this->assertSame( 0, $exit_code, implode( "\n", $output ) );
        $this->assertSame( 'ok', implode( "\n", $output ) );
    }
}
