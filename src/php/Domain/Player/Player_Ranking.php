<?php
declare( strict_types=1 );
namespace Racketmanager\Domain\Player;

use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable Value Object representing a player's rating, ranking, and points.
 */
final readonly class Player_Ranking implements JsonSerializable {

    public function __construct(
        private ?int $rank = null,
        private ?float $rating = null,
        private string $raw_value = '',
        private float $points = 0.0,
        private string $discipline = 'singles'
    ) {
        if ( null !== $this->rank && $this->rank < 1 ) {
            throw new InvalidArgumentException( 'Player rank must be a positive integer.' );
        }
        if ( $this->points < 0.0 ) {
            throw new InvalidArgumentException( 'Player points cannot be negative.' );
        }
    }

    /**
     * Shared parser for raw WTN/rating inputs.
     * Validates numeric values (>= 0) and captures the raw display string for presentation fidelity.
     */
    public static function from_raw( mixed $val, string $discipline = 'singles' ): self {
        if ( null === $val || '' === $val ) {
            return new self(
                rank: null,
                rating: null,
                raw_value: '',
                points: 0.0,
                discipline: $discipline
            );
        }

        $str_val = is_scalar( $val ) ? (string) $val : '';
        $trimmed = trim( $str_val );

        if ( is_numeric( $trimmed ) && (float) $trimmed >= 0.0 ) {
            return new self(
                rank: null,
                rating: (float) $trimmed,
                raw_value: $str_val,
                points: 0.0,
                discipline: $discipline
            );
        }

        return new self(
            rank: null,
            rating: null,
            raw_value: $str_val,
            points: 0.0,
            discipline: $discipline
        );
    }

    public static function unranked( ?float $rating = null, float $points = 0.0, string $discipline = 'singles' ): self {
        $raw = null !== $rating ? (string) $rating : '';
        return new self(
            rank: null,
            rating: $rating,
            raw_value: $raw,
            points: $points,
            discipline: $discipline
        );
    }

    public static function create( int $rank, ?float $rating = null, float $points = 0.0, string $discipline = 'singles' ): self {
        $raw = null !== $rating ? (string) $rating : '';
        return new self(
            rank: $rank,
            rating: $rating,
            raw_value: $raw,
            points: $points,
            discipline: $discipline
        );
    }

    public function get_rank(): ?int {
        return $this->rank;
    }

    public function get_rating(): ?float {
        return $this->rating;
    }

    public function get_raw_value(): string {
        return $this->raw_value;
    }

    public function get_points(): float {
        return $this->points;
    }

    public function get_discipline(): string {
        return $this->discipline;
    }

    public function is_ranked(): bool {
        return null !== $this->rank;
    }

    public function has_rating(): bool {
        return null !== $this->rating;
    }

    public function equals( self $other ): bool {
        return $this->rank === $other->rank
            && $this->rating === $other->rating
            && $this->raw_value === $other->raw_value
            && $this->points === $other->points
            && $this->discipline === $other->discipline;
    }

    public function jsonSerialize(): array {
        return [
            'rank'       => $this->rank,
            'rating'     => $this->rating,
            'raw_value'  => $this->raw_value,
            'points'     => $this->points,
            'discipline' => $this->discipline,
        ];
    }
}
