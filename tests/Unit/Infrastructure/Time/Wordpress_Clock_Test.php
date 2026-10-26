<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Time;

use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Ports\Clock_Interface;
use Racketmanager\Infrastructure\Time\Wordpress_Clock;

final class Wordpress_Clock_Test extends TestCase {

    public function test_implements_the_domain_port(): void {
        $this->assertInstanceOf( Clock_Interface::class, new Wordpress_Clock() );
    }

    public function test_now_formats_to_the_same_mysql_string_as_current_time(): void {
        $clock = new Wordpress_Clock();

        $before = current_time( 'mysql' );
        $now    = $clock->now()->format( 'Y-m-d H:i:s' );
        $after  = current_time( 'mysql' );

        $this->assertMatchesRegularExpression( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $now );
        $this->assertGreaterThanOrEqual( $before, $now );
        $this->assertLessThanOrEqual( $after, $now );
    }

    public function test_now_carries_the_site_timezone(): void {
        $now = ( new Wordpress_Clock() )->now();

        $this->assertEquals( wp_timezone(), $now->getTimezone() );
        $this->assertInstanceOf( DateTimeZone::class, $now->getTimezone() );
    }
}
