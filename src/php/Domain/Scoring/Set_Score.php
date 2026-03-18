<?php

namespace Racketmanager\Domain\Scoring;

/**
 * Value object representing a single set score.
 */
final class Set_Score implements \ArrayAccess
{
    public function __construct(
        private ?int $home_games,
        private ?int $away_games,
        private ?int $home_tiebreak = null,
        private ?int $away_tiebreak = null
    ) {
    }

    public function offsetExists($offset): bool
    {
        return in_array($offset, ['home', 'away', 'player1', 'player2', 'home_tb', 'away_tb', 'tiebreak'], true);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        switch ($offset) {
            case 'home':
            case 'player1':
                return $this->home_games;
            case 'away':
            case 'player2':
                return $this->away_games;
            case 'home_tb':
                return $this->home_tiebreak;
            case 'away_tb':
                return $this->away_tiebreak;
            case 'tiebreak':
                return $this->home_tiebreak ?: $this->away_tiebreak;
            default:
                return null;
        }
    }

    public function offsetSet($offset, $value): void
    {
        throw new \BadMethodCallException('Set_Score is immutable');
    }

    public function offsetUnset($offset): void
    {
        throw new \BadMethodCallException('Set_Score is immutable');
    }

    public function get_home_games(): ?int
    {
        return $this->home_games;
    }

    public function get_away_games(): ?int
    {
        return $this->away_games;
    }

    public function get_home_tiebreak(): ?int
    {
        return $this->home_tiebreak;
    }

    public function get_away_tiebreak(): ?int
    {
        return $this->away_tiebreak;
    }

    public function winner(): ?string
    {
        if (null === $this->home_games || null === $this->away_games) {
            return null;
        }

        if ($this->home_games > $this->away_games) {
            return 'home';
        }

        if ($this->away_games > $this->home_games) {
            return 'away';
        }

        if ($this->home_tiebreak !== null && $this->away_tiebreak !== null) {
            if ($this->home_tiebreak > $this->away_tiebreak) {
                return 'home';
            }
            if ($this->away_tiebreak > $this->home_tiebreak) {
                return 'away';
            }
        }

        return null;
    }
}
