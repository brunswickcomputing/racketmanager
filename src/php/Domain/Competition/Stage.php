<?php

namespace Racketmanager\Domain\Competition;

/**
 * Domain model representing a stage within a competition (division, draw, etc).
 */
final class Stage
{
    public function __construct(
        private int $id,
        private string $name,
        private string $type, // division, group, draw, bracket, box
        private ?int $event_id = null
    ) {
    }

    public function get_id(): int
    {
        return $this->id;
    }

    public function get_name(): string
    {
        return $this->name;
    }

    public function get_type(): string
    {
        return $this->type;
    }

    public function get_event_id(): ?int
    {
        return $this->event_id;
    }
}
