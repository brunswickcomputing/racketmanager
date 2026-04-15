<?php

namespace Racketmanager\Application\Fixture\DTOs;

/**
 * The read model for fixture result updates
 */
final class Fixture_Result_Read_Model {
    public string $msg;
    public ?int $home_points;
    public ?int $away_points;
    public ?int $winner_id;
    public ?array $sets;

    public function __construct(
        string $msg,
        ?int $home_points,
        ?int $away_points,
        ?int $winner_id,
        ?array $sets
    ) {
        $this->msg         = $msg;
        $this->home_points = $home_points;
        $this->away_points = $away_points;
        $this->winner_id   = $winner_id;
        $this->sets        = $sets;
    }

    public function to_array(): array {
        return array(
            'msg'         => $this->msg,
            'home_points' => $this->home_points,
            'away_points' => $this->away_points,
            'winner_id'   => $this->winner_id,
            'sets'        => $this->sets,
        );
    }
}
