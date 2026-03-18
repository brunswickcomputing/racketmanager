<?php

namespace Racketmanager\Domain\Competition;

use Racketmanager\Domain\League;
use Racketmanager\Domain\Championship;

/**
 * Domain model representing a stage within a competition (division, draw, etc).
 */
final class Stage
{
    private ?Championship $championship = null;

    public function __construct(
        private int $id,
        private string $name,
        private string $type, // division, group, draw, bracket, box
        private ?int $event_id = null
    ) {
    }

    public static function from_league(League $league): self
    {
        $stage = new self(
            $league->get_id(),
            $league->get_name(),
            $league->is_championship ? 'draw' : 'division', // Simplified for now
            $league->get_event_id()
        );

        if ($league->is_championship && isset($league->championship)) {
            $stage->set_championship($league->championship);
        }

        return $stage;
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

    public function get_championship(): ?Championship
    {
        return $this->championship;
    }

    public function set_championship(Championship $championship): void
    {
        $this->championship = $championship;
    }
}
