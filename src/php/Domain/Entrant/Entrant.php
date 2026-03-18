<?php

namespace Racketmanager\Domain\Entrant;

/**
 * Interface for any competition entrant (team or player).
 */
interface Entrant
{
    public function id(): int|string;
    public function display_name(): string;
    public function type(): string;
}
