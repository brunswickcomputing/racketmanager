<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Admin\Commands;

final readonly class Moderate_Results_Checker_Command {
    public function __construct(
        public string $action,
        public array $resultsChecker
    ) {
        Command_Validation::ids( $this->resultsChecker, 'resultsChecker' );
        Command_Validation::choice( $this->action, [ 'approve', 'handle', 'delete' ] );
        if ( [] === $this->resultsChecker ) {
            throw new \InvalidArgumentException( 'No actions flagged' );
        }
    }
}
