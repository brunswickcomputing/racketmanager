<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

/**
 * Minimal readonly typed view model, mirroring the shape of the production view models
 * (final readonly, no dynamic properties).
 */
final readonly class Typed_Scope_Vm {
    public function __construct( public string $title ) {}
}
