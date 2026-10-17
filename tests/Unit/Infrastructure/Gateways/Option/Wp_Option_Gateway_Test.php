<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Gateways\Option;

use PHPUnit\Framework\TestCase;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;

class Wp_Option_Gateway_Test extends TestCase {

    public function test_implements_option_gateway_interface(): void {
        $gateway = new Wp_Option_Gateway();
        $this->assertInstanceOf( Option_Gateway_Interface::class, $gateway );
    }

    public function test_get_option(): void {
        $gateway = new Wp_Option_Gateway();
        $result = $gateway->get_option( 'non_existent_test_option', 'default_val' );
        $this->assertSame( 'default_val', $result );
    }
}
