<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container;

use Racketmanager\Admin\Admin_Screens_Service_Provider;
use Racketmanager\RacketManager;
use Racketmanager\Services\Container\Providers\Admin_Service_Provider;
use Racketmanager\Services\Container\Providers\Club_Service_Provider;
use Racketmanager\Services\Container\Providers\Competition_Service_Provider;
use Racketmanager\Services\Container\Providers\Core_Service_Provider;
use Racketmanager\Services\Container\Providers\Fixture_Service_Provider;
use Racketmanager\Services\Container\Providers\Player_Service_Provider;
use Racketmanager\Services\Container\Providers\Service_Provider_Interface;

/**
 * Boots and registers all modular domain service providers in the Simple_Container.
 */
final class Container_Bootstrap {
    /**
     * Boot the dependency injection container by registering all domain service providers.
     *
     * @param ?RacketManager $app
     * @return Simple_Container
     */
    public static function boot( ?RacketManager $app = null ): Simple_Container {
        $container = new Simple_Container();
        if ( $app ) {
            $app->container = $container;
        }

        /** @var Service_Provider_Interface[] $providers */
        $providers = [
            new Core_Service_Provider( $app ),
            new Club_Service_Provider( $app ),
            new Player_Service_Provider( $app ),
            new Fixture_Service_Provider( $app ),
            new Competition_Service_Provider( $app ),
            new Admin_Service_Provider( $app ),
            new Admin_Screens_Service_Provider( $app ),
        ];

        foreach ( $providers as $provider ) {
            $provider->register( $container );
        }

        return $container;
    }
}
