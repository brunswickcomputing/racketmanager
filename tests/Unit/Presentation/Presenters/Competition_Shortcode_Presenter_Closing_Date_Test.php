<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Services\View\Php_View_Renderer;

class Competition_Shortcode_Presenter_Closing_Date_Test extends TestCase {
    private function presenter( ?string $format ): Competition_Shortcode_Presenter {
        $gateway = null;
        if ( null !== $format ) {
            $gateway = new class( $format ) implements Option_Gateway_Interface {
                public function __construct( private string $format ) {}
                public function get_option( string $option, mixed $default_value = false ): mixed {
                    return 'date_format' === $option ? $this->format : $default_value;
                }
                public function update_option( string $option, mixed $value, bool|string|null $autoload = null ): bool {
                    return true;
                }
                public function delete_option( string $option ): bool {
                    return true;
                }
            };
        }
        return new Competition_Shortcode_Presenter( new Php_View_Renderer( RACKETMANAGER_PATH . 'templates/' ), null, null, $gateway );
    }

    public function test_uses_injected_gateway_date_format(): void {
        $this->assertSame( '2026-05-01', $this->presenter( 'Y-m-d' )->present_closing_date( '2026-05-01' ) );
        $this->assertSame( '01/05/2026', $this->presenter( 'd/m/Y' )->present_closing_date( '2026-05-01' ) );
    }

    public function test_null_and_empty_dates_return_empty_string(): void {
        $presenter = $this->presenter( 'd/m/Y' );
        $this->assertSame( '', $presenter->present_closing_date( null ) );
        $this->assertSame( '', $presenter->present_closing_date( '' ) );
    }

    public function test_default_construction_without_gateway_still_formats(): void {
        $this->assertNotSame( '', $this->presenter( null )->present_closing_date( '2026-05-01' ) );
    }
}
