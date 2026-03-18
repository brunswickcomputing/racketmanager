<?php

namespace Racketmanager\Domain;

use Racketmanager\Domain\Scoring\Set_Score;

class Result {
    /**
     * @param float $home_points
     * @param float $away_points
     * @param int|null $winner_id
     * @param int|null $loser_id
     * @param int|null $status
     * @param bool $is_walkover
     * @param Set_Score[] $sets
     * @param array $custom
     */
    public function __construct(
        private float $home_points,
        private float $away_points,
        private ?int $winner_id = null,
        private ?int $loser_id = null,
        private ?int $status = null,
        private bool $is_walkover = false,
        private array $sets = [],
        private array $custom = [],
    ) {
    }

    public function get_home_points(): float {
        return $this->home_points;
    }

    public function get_away_points(): float {
        return $this->away_points;
    }

    public function get_winner_id(): ?int {
        return $this->winner_id;
    }

    public function get_loser_id(): ?int {
        return $this->loser_id;
    }

    public function get_status(): ?int {
        return $this->status;
    }

    public function is_walkover(): bool {
        return $this->is_walkover;
    }

    public function get_sets(): array {
        return $this->sets;
    }

    public function get_custom(): array {
        return $this->custom;
    }
}
