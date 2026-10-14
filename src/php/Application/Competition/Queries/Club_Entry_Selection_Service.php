<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;

class Club_Entry_Selection_Service {
    public function __construct(
        private readonly Club_Service $club_service,
        private readonly Player_Service $player_service
    ) {}

    public function available_clubs( bool $is_admin, int $user_id, ?int $club_id, ?string $entry_level ): object|int|bool|array {
        if ( $is_admin ) {
            $args = [ 'type' => 'affiliated' ];
            if ( $club_id ) {
                $args['club'] = $club_id;
                $args['count'] = true;
            }
            return $this->club_service->get_clubs( $args ) ?: false;
        }

        if ( ! $entry_level ) {
            return false;
        }

        return $this->player_service->find_all_associated_clubs( $user_id, $entry_level, $club_id ) ?: false;
    }
}