<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Services\Admin\Security\Action_Guard_Interface;

/**
 * Resolves League admin controllers from the delivery container so tests exercise the real wiring and dispatchers.
 */
trait Resolves_League_Admin_Controllers {
    use Creates_Admin_Delivery_Container;

    protected Action_Guard_Interface $guard;

    /**
     * @param array<string, object> $services Service overrides for the delivery container.
     */
    protected function resolve_controller( string $id, array $services = array() ): object {
        $this->guard = $this->createMock( Action_Guard_Interface::class );
        $container   = $this->create_admin_delivery_container( services: $services, guard: $this->guard );

        return $container->get( $id );
    }

    protected function deny_capability( string $capability ): void {
        $this->guard->expects( self::once() )
            ->method( 'assert_capability' )
            ->with( $capability )
            ->willThrowException( new \Racketmanager\Exceptions\Invalid_Status_Exception( 'Permission denied' ) );
    }
}
