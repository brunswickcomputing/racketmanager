<?php

namespace Racketmanager\Domain\Entrant;

/**
 * Value object representing a player as a competition entrant.
 */
final class Player_Entrant implements Entrant
{
    public function __construct(
        private int|string $id,
        private string $name
    ) {
    }

    public function id(): int|string
    {
        return $this->id;
    }

    public function display_name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return 'player';
    }
}
