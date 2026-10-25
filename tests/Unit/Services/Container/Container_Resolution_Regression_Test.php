<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Container;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin;
use Racketmanager\Admin\Admin_Display;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Validator\Player_Details_Validator;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Container_Resolution_Regression_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private RacketManager $app;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $wpdb_mock = $this->createStub( wpdb::class );
        $wpdb_mock->prefix = 'wp_';
        $GLOBALS['wpdb'] = $wpdb_mock;

        $this->app = $this->createStub( RacketManager::class );
        $this->app->method( 'get_options' )->willReturn( [] );
        $this->app->site_url = 'https://example.com';
        $this->app->site_name = 'Test Site';
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_player_and_registration_service_chain_resolves_without_circular_dependency(): void {
        $container = Container_Bootstrap::boot( $this->app );

        $player_details_validator = $container->get( 'player_details_validator' );
        $this->assertInstanceOf( Player_Details_Validator::class, $player_details_validator );

        $player_service = $container->get( 'player_service' );
        $this->assertInstanceOf( Player_Service::class, $player_service );

        $registration_service = $container->get( 'registration_service' );
        $this->assertInstanceOf( Registration_Service::class, $registration_service );

        $player_validation_service = $container->get( 'player_validation_service' );
        $this->assertInstanceOf( Player_Validation_Service::class, $player_validation_service );

        $team_service = $container->get( 'team_service' );
        $this->assertInstanceOf( Team_Service::class, $team_service );

        $competition_repo = $container->get( 'competition_repository' );
        $this->assertInstanceOf( Competition_Repository::class, $competition_repo );

        $option_service = $container->get( 'option_service' );
        $this->assertInstanceOf( Option_Service::class, $option_service );

        $admin_display = $container->get( 'admin_display' );
        $this->assertInstanceOf( Admin_Display::class, $admin_display );

        $admin = $container->get( 'admin' );
        $this->assertInstanceOf( Admin::class, $admin );
    }
}
