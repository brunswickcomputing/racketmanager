<?php
/**
 * Player_Repository_Interface interface
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories\Interfaces;

use Racketmanager\Domain\Player;
use WP_User;

/**
 * Interface to implement the Player repository
 */
interface Player_Repository_Interface {
    public function add( Player $player );
    public function save_btm( int $player_id, int $btm ): bool;
    public function save_contact_no( int $player_id, string $contact_no ): bool;
    public function update( Player $player, array $updates ): bool;
    public function find_all( array $args = array() ): array;
    public function get_active_players(): array;
    public function find( int|string $player_id, string $search_type = 'id' ): ?Player;
    public function find_by_btm( $player_id ): mixed;
    public function find_by_email( $player_id ): false|WP_User;
    public function find_by_login( $player_id ): false|WP_User;
    public function find_by_name( $player_id ): false|WP_User;
    public function has_club_associations( int $player_id ): bool;
    public function delete( int $player_id ): bool;
    public function find_player_ids_by_club( int $club_id ): array;
    public function find_club_players_with_details(?int $club_id = null, ?string $status = null, ?string $gender = null, ?string $active = null, bool $system = false, ?int $max_age = null, ?int $min_age = null): array;
    public function get_titles( $player_id ): array;
    public function find_active_players_by_competition_and_season( int $competition_id, string $season ): array;
    public function find_by_team( int $team_id ): array;
}
