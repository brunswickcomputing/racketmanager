<?php
declare( strict_types=1 );
namespace Racketmanager\Tests\Unit\Domain\Competition;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Settings;
class Competition_Settings_Test extends TestCase {
    private array $settings_data = [
        'mode' => 'advanced',
        'sport' => 'squash',
        'entry_type' => 'player',
        'num_courts_available' => [1 => 2, 2 => 3],
    ];
    public function test_all() {
        $settings = new Competition_Settings( $this->settings_data );
        $this->assertEquals( $this->settings_data, $settings->all() );
    }
    public function test_get() {
        $settings = new Competition_Settings( $this->settings_data );
        $this->assertEquals( 'advanced', $settings->get('mode') );
        $this->assertEquals( 'default_val', $settings->get('non_existent', 'default_val') );
    }
    public function test_with() {
        $settings = new Competition_Settings( $this->settings_data );
        $new_settings = $settings->with( 'mode', 'pro' );
        $this->assertNotSame( $settings, $new_settings );
        $this->assertEquals( 'pro', $new_settings->get('mode') );
        $this->assertEquals( 'advanced', $settings->get('mode') );
    }
    public function test_mode() {
        $settings = new Competition_Settings( $this->settings_data );
        $this->assertEquals( 'advanced', $settings->mode() );
        $empty_settings = new Competition_Settings( [] );
        $this->assertEquals( 'default', $empty_settings->mode() );
    }
    public function test_entry_type() {
        $settings = new Competition_Settings( $this->settings_data );
        $this->assertEquals( 'player', $settings->entry_type() );
        $empty_settings = new Competition_Settings( [] );
        $this->assertEquals( 'team', $empty_settings->entry_type() );
    }
    public function test_sport() {
        $settings = new Competition_Settings( $this->settings_data );
        $this->assertEquals( 'squash', $settings->sport() );
        $empty_settings = new Competition_Settings( [] );
        $this->assertEquals( 'tennis', $empty_settings->sport() );
    }
    public function test_num_courts_available() {
        $settings = new Competition_Settings( $this->settings_data );
        $this->assertEquals( [1 => 2, 2 => 3], $settings->num_courts_available() );
        $empty_settings = new Competition_Settings( [] );
        $this->assertEquals( [], $empty_settings->num_courts_available() );
    }
}
