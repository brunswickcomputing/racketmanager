<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

/**
 * What the current user may do on a tournament entry form.
 *
 * The own player or an administrator may change an open entry; once entries close only administrators may change
 * them, although the entrant (or an administrator) may still withdraw. Anyone else gets a read-only form.
 */
final readonly class Tournament_Entry_Access {
    public function __construct(
        public bool $can_manage,
        public bool $is_own_player,
        public bool $has_permission,
        public bool $changes_allowed,
        public bool $withdrawal_allowed
    ) {}

    public static function resolve( bool $is_open, bool $is_closed, int $user_id, int $player_id, bool $can_manage ): self {
        $is_own_player      = $user_id > 0 && $user_id === $player_id;
        $has_permission     = $is_own_player || $can_manage;
        $changes_allowed    = false;
        $withdrawal_allowed = false;

        if ( $has_permission ) {
            if ( $is_open ) {
                $changes_allowed    = true;
                $withdrawal_allowed = true;
            } else {
                $changes_allowed    = $can_manage;
                $withdrawal_allowed = $is_closed;
            }
        }

        return new self( $can_manage, $is_own_player, $has_permission, $changes_allowed, $withdrawal_allowed );
    }

    /**
     * @return array{can_manage: bool, is_own_player: bool, has_permission: bool, changes_allowed: bool, withdrawal_allowed: bool}
     */
    public function to_vars(): array {
        return [
            'can_manage'         => $this->can_manage,
            'is_own_player'      => $this->is_own_player,
            'has_permission'     => $this->has_permission,
            'changes_allowed'    => $this->changes_allowed,
            'withdrawal_allowed' => $this->withdrawal_allowed,
        ];
    }
}
