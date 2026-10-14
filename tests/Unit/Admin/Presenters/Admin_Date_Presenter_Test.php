<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

final class Admin_Date_Presenter_Test extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_options'] );
        parent::tearDown();
    }

    public function test_format_date_uses_wordpress_date_format(): void {
        $GLOBALS['wp_stubs_options'] = array( 'date_format' => 'j M Y' );
        $presenter                   = new Admin_Date_Presenter();

        $this->assertSame( '4 Apr 2026', $presenter->format_date( '2026-04-04 18:30:00' ) );
    }

    public function test_format_time_uses_wordpress_time_format(): void {
        $GLOBALS['wp_stubs_options'] = array( 'time_format' => 'H:i' );
        $presenter                   = new Admin_Date_Presenter();

        $this->assertSame( '18:30', $presenter->format_time( '2026-04-04 18:30:00' ) );
    }

    public function test_empty_and_zero_dates_return_na(): void {
        $presenter = new Admin_Date_Presenter();

        $this->assertSame( 'N/A', $presenter->format_date( null ) );
        $this->assertSame( 'N/A', $presenter->format_date( '' ) );
        $this->assertSame( 'N/A', $presenter->format_date( '0000-00-00 00:00:00' ) );
        $this->assertSame( 'N/A', $presenter->format_time( null ) );
    }

    public function test_explicit_format_overrides_wordpress_option(): void {
        $GLOBALS['wp_stubs_options'] = array( 'date_format' => 'j M Y' );
        $presenter                   = new Admin_Date_Presenter();

        $this->assertSame( '2026-04-04', $presenter->format_date( '2026-04-04 18:30:00', 'Y-m-d' ) );
    }
}
