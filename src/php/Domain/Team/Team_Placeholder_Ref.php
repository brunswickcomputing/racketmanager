<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Team;

final class Team_Placeholder_Ref {
    public function __construct(
        public readonly Team_Placeholder_Role $role,
        public readonly ?int $fixture_id = null
    ) {}

    public function is_winner(): bool {
        return Team_Placeholder_Role::WINNER === $this->role;
    }

    public function is_loser(): bool {
        return Team_Placeholder_Role::LOSER === $this->role;
    }

    public function default_label(): string {
        return $this->is_winner() ? 'Winner of' : 'Loser of';
    }

    public static function parse( string $title ): ?self {
        $name_array = explode( '_', $title );
        if ( '1' === $name_array[0] ) {
            $role = Team_Placeholder_Role::WINNER;
        } elseif ( '2' === $name_array[0] ) {
            $role = Team_Placeholder_Role::LOSER;
        } else {
            return null;
        }

        $fixture_id = ( isset( $name_array[2] ) && is_numeric( $name_array[2] ) )
            ? (int) $name_array[2]
            : null;

        return new self( $role, $fixture_id );
    }
}
