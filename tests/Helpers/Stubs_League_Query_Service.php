<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use PHPUnit\Framework\MockObject\MockObject;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Container\Simple_Container;
use stdClass;

/**
 * Installs a mocked League_Query_Service into the global container used by
 * \Racketmanager\get_league_query_service(), for code that still resolves it via the helper.
 */
trait Stubs_League_Query_Service {
    private bool $league_query_global_installed = false;
    private mixed $league_query_previous_global = null;

    /**
     * @return League_Query_Service&MockObject
     */
    protected function install_league_query_service_mock( bool $expectations = false ): League_Query_Service {
        $service = $expectations ? $this->createMock( League_Query_Service::class ) : $this->createStub( League_Query_Service::class );
        $this->install_league_query_service( $service );

        return $service;
    }

    protected function install_league_query_service( League_Query_Service $service ): void {
        global $racketmanager;
        if ( ! $this->league_query_global_installed ) {
            $this->league_query_previous_global = $racketmanager ?? null;
            $this->league_query_global_installed = true;
            $racketmanager = ( is_object( $racketmanager ?? null ) && property_exists( $racketmanager, 'container' ) )
                ? clone $racketmanager
                : new stdClass();
        }
        $container = new Simple_Container();
        $container->set( 'league_query_service', fn() => $service );
        $racketmanager->container = $container;
    }

    protected function restore_league_query_service_global(): void {
        if ( $this->league_query_global_installed ) {
            global $racketmanager;
            $racketmanager                       = $this->league_query_previous_global;
            $this->league_query_global_installed = false;
        }
    }
}
