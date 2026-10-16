<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Container\Providers;

use Racketmanager\Services\Container\Simple_Container;

/**
 * Interface for modular service providers registering services in Simple_Container.
 */
interface Service_Provider_Interface {
    /**
     * Register domain services into the container.
     *
     * @param Simple_Container $container The service container instance.
     * @return void
     */
    public function register( Simple_Container $container ): void;
}
