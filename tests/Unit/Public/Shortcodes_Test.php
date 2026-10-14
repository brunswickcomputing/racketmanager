<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Public;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Public\Shortcodes;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Tests\Helpers\Creates_Test_Container;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

class Shortcodes_Test extends TestCase {
    use Creates_Test_Container;
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $wpdb_mock         = $this->createStub( wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        $GLOBALS['wpdb']   = $wpdb_mock;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_show_daily_matches_with_null_league_on_fixture(): void {
        $fixture    = Fixture_Test_Factory::create( id: 201, league_id: null );
        $shortcodes = $this->create_shortcodes_instance( [ $fixture ] );
        $output     = $shortcodes->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertNull( $fixture->league );
        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Daily matches', $output );
        $this->assertStringContainsString( 'Red Club 1st Team', $output );
        $this->assertStringContainsString( 'Blue Club 2nd Team', $output );
        $this->assertStringContainsString( 'href="https://example.test/match/201/"', $output );
    }

    #[DataProvider( 'permission_display_provider' )]
    public function test_show_daily_matches_permission_link_rendering( bool $user_can_update, bool $should_contain_result_link ): void {
        $fixture            = Fixture_Test_Factory::create( id: 201 );
        $container          = $this->create_test_container();
        $permission_service = $this->createStub( Fixture_Permission_Service::class );
        $permission_service->method( 'is_update_allowed' )->willReturn( (object) [ 'user_can_update' => $user_can_update ] );
        $container->set( 'fixture_permission_service', fn() => $permission_service );

        $shortcodes = $this->create_shortcodes_instance( [ $fixture ], $container );
        $output     = $shortcodes->show_daily_matches( [ 'match_date' => '2026-10-12' ] );

        $this->assertIsString( $output );
        $this->assertStringContainsString( 'href="https://example.test/match/201/"', $output );
        if ( $should_contain_result_link ) {
            $this->assertStringContainsString( 'href="https://example.test/match/201/result/"', $output );
            $this->assertStringContainsString( 'class="btn match__btn"', $output );
        } else {
            $this->assertStringNotContainsString( 'href="https://example.test/match/201/result/"', $output );
            $this->assertStringNotContainsString( 'class="btn match__btn"', $output );
        }
    }

    public static function permission_display_provider(): array {
        return [
            'update allowed shows result button'    => [ true, true ],
            'update not allowed hides result button' => [ false, false ],
        ];
    }

    public function test_show_latest_results_with_null_league_on_fixture(): void {
        $fixture   = Fixture_Test_Factory::create(
            id: 202, league_id: 10, home_team: '3', away_team: '4',
            date: '2026-10-10', start_time: '19:00', group: 'Group 2', location: 'Court 2',
        );
        $container = $this->create_test_container( [
            '3' => [ 'name' => 'Green Team', 'type' => 'T' ],
            '4' => [ 'name' => 'Yellow Team', 'type' => 'T' ],
        ] );
        $this->configure_league_repository( $container, 10, 'Division 2' );

        $shortcodes = $this->create_shortcodes_instance( [ $fixture ], $container );
        $output     = $shortcodes->show_latest_results( [ 'days' => 7 ] );

        $this->assertIsString( $output );
        $this->assertStringContainsString( 'Latest Results', $output );
        $this->assertStringContainsString( 'Division 2', $output );
        $this->assertStringContainsString( 'Green Team', $output );
        $this->assertStringContainsString( 'Yellow Team', $output );
    }

    private function create_shortcodes_instance( array $matches = [], ?Simple_Container $container = null ): Shortcodes {
        global $racketmanager;

        $container ??= $this->create_test_container();
        $app_mock              = $this->createStub( RacketManager::class );
        $app_mock->container   = $container;
        $app_mock->date_format = 'Y-m-d';
        $app_mock->method( 'get_matches' )->willReturn( $matches );
        $racketmanager         = $app_mock;

        return new Shortcodes( $app_mock );
    }
}
