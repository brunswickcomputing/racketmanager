<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Settings_Service;

class Settings_Service_Test extends TestCase {
    private array $test_options = [
        'rosters' => [
            'btm' => true,
            'rosterConfirmation' => 'auto',
        ],
        'league' => [
            'resultConfirmation' => 'manual',
        ],
        'checks' => [
            'ageLimitCheck' => true,
        ],
    ];

    public function test_constructor_with_options(): void {
        $service = new Settings_Service( $this->test_options );
        $this->assertEquals( $this->test_options, $service->get_all_options() );
    }

    public function test_get_all_options(): void {
        $service = new Settings_Service( $this->test_options );
        $this->assertEquals( $this->test_options, $service->get_all_options() );
    }

    public function test_constructor_without_options_uses_get_option(): void {
        // get_option('racketmanager', []) in wp-stubs.php returns [] by default
        $service = new Settings_Service();
        $this->assertEquals( [], $service->get_all_options() );
    }

    public function test_get_category_returns_existing_category(): void {
        $service = new Settings_Service( $this->test_options );
        $expected = [
            'btm' => true,
            'rosterConfirmation' => 'auto',
        ];
        $this->assertEquals( $expected, $service->get_category( 'rosters' ) );
    }

    public function test_get_category_returns_default_for_missing_category(): void {
        $service = new Settings_Service( $this->test_options );
        $this->assertEquals( [], $service->get_category( 'non_existent' ) );
        $this->assertEquals( [ 'fallback' => true ], $service->get_category( 'non_existent', [ 'fallback' => true ] ) );
    }

    public function test_get_option_returns_existing_option(): void {
        $service = new Settings_Service( $this->test_options );
        $this->assertEquals( 'auto', $service->get_option( 'rosters', 'rosterConfirmation' ) );
    }

    public function test_get_option_returns_default_for_missing_key_or_category(): void {
        $service = new Settings_Service( $this->test_options );
        
        // Missing key in existing category
        $this->assertNull( $service->get_option( 'rosters', 'missing_key' ) );
        $this->assertEquals( 'default_value', $service->get_option( 'rosters', 'missing_key', 'default_value' ) );
        
        // Missing category
        $this->assertNull( $service->get_option( 'missing_category', 'some_key' ) );
        $this->assertEquals( 'fallback', $service->get_option( 'missing_category', 'some_key', 'fallback' ) );
    }
}
