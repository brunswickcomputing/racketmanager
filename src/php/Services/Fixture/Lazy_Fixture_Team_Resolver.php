<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Closure;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture_Team_Resolver_Interface;

/**
 * Lazily initialises and delegates fixture team resolution to an underlying resolver.
 *
 * The resolver factory is invoked only when team details are first requested. The
 * resolved instance is cached and reused for all subsequent resolution calls.
 */
final class Lazy_Fixture_Team_Resolver implements Fixture_Team_Resolver_Interface {
    private Closure $resolver;
    private ?Fixture_Team_Resolver_Interface $resolved = null;

    /**
     * Initialises the instance with the provided resolver callback.
     *
     * @param Closure $resolver Resolver callback to be stored by the instance.
     *
     * @return void
     */
    public function __construct( Closure $resolver ) {
        $this->resolver = $resolver;
    }

    /**
     * Retrieves the resolved fixture team resolver instance, initialising it when needed.
     *
     * @return Fixture_Team_Resolver_Interface Resolved fixture team resolver instance.
     */
    private function get_resolver(): Fixture_Team_Resolver_Interface {
        if ( null === $this->resolved ) {
            $this->resolved = ( $this->resolver )();
        }

        return $this->resolved;
    }

    /**
     * Retrieves details for the specified team.
     *
     * @param int|string|null $team_id Team identifier used to resolve the team details.
     *
     * @return Team_Details_DTO Team details data transfer object.
     */
    public function get_team_details( int|string|null $team_id ): Team_Details_DTO {
        return $this->get_resolver()->get_team_details( $team_id );
    }

    /**
     * Derives team details for the specified team reference.
     *
     * @param string $team_ref Reference used to identify the team.
     *
     * @return Team_Details_DTO|null Derived team details, or null if no details can be resolved.
     */
    public function derive_team_details( string $team_ref ): ?Team_Details_DTO {
        return $this->get_resolver()->derive_team_details( $team_ref );
    }
}
