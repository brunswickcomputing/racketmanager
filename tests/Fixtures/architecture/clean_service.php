<?php
declare( strict_types=1 );

/**
 * Architecture scanner fixture: a layered service with typed dependencies and no legacy coupling.
 * Not autoloadable and never executed.
 */

namespace Racketmanager\Tests\Fixtures\Architecture;

use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Tests\Fixtures\Architecture\Typed\Club_Hydration_Data;

final readonly class Clean_Service {
    public function __construct(
        private Club_Repository_Interface $clubs,
        private Club_Hydration_Data|int $seed,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function names(): array {
        $names = [];
        foreach ( $this->clubs->get_all() as $club ) {
            $names[] = $club->name;
        }
        try {
            $names = array_merge( $names, $this->extract( $this->seed ) );
        } catch ( \RuntimeException | \InvalidArgumentException ) {
            return $names;
        }

        return $names;
    }

    /**
     * Same name as the global `extract()` function: a method call must not be reported as a function call.
     *
     * @return array<int, string>
     */
    private function extract( mixed $seed ): array {
        return is_int( $seed ) ? [ (string) $seed ] : [];
    }
}
