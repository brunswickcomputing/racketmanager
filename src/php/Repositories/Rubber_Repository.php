<?php
declare( strict_types=1 );
/**
 * Rubber_Repository class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Repositories
 */

namespace Racketmanager\Repositories;

use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Fixture\Fixture_Time_Parser;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Registration_Service;
use wpdb;

/**
 * Class to implement the rubber repository
 */
class Rubber_Repository implements Rubber_Repository_Interface {
    private wpdb $wpdb;
    private string $table_name;
    private string $players_table_name;
    private ?Registration_Service $registration_service;

    public function __construct( ?Registration_Service $registration_service = null ) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_name = $this->wpdb->prefix . 'racketmanager_rubbers';
        $this->players_table_name = $this->wpdb->prefix . 'racketmanager_rubber_players';
        $this->registration_service = $registration_service;
    }

    private function get_registration_service(): ?Registration_Service {
        return $this->registration_service;
    }

    public function save( object $entity ): bool|int {
        /** @var Rubber $entity */
        $data = array(
            'date'          => Fixture_Time_Parser::to_mysql_datetime( $entity->get_date() ),
            'match_id'      => $entity->get_match_id(),
            'rubber_number' => $entity->get_rubber_number(),
            'type'          => $entity->get_type(),
            'group'         => $entity->get_group(),
            'home_points'   => $entity->get_home_points(),
            'away_points'   => $entity->get_away_points(),
            'winner_id'     => $entity->get_winner_id(),
            'loser_id'      => $entity->get_loser_id(),
            'post_id'       => $entity->get_post_id(),
            'custom'        => maybe_serialize( $entity->get_custom() ),
            'status'        => $entity->get_status(),
        );

        $format = array(
            '%s', // date
            '%d', // match_id
            '%d', // rubber_number
            '%s', // type
            '%s', // group
            '%f', // home_points
            '%f', // away_points
            '%s', // winner_id
            '%s', // loser_id
            '%d', // post_id
            '%s', // custom
            '%d', // status
        );

        if ( empty( $entity->get_id() ) ) {
            $inserted = $this->wpdb->insert( $this->table_name, $data, $format );
            if ( $inserted ) {
                $entity->set_id( (int) $this->wpdb->insert_id );
                wp_cache_set( $entity->get_id(), $entity, 'rubbers' );
                return (int) $this->wpdb->insert_id;
            }
            return false;
        } else {
            $updated = $this->wpdb->update(
                $this->table_name,
                $data,
                array(
                    'id' => $entity->get_id(),
                ),
                $format,
                array(
                    '%d',
                )
            ) !== false;
            if ( false !== $updated ) {
                wp_cache_set( $entity->get_id(), $entity, 'rubbers' );
                return true;
            }
            return false;
        }
    }

    public function find_by_id( $id ): ?Rubber {
        if ( empty( $id ) ) {
            return null;
        }
        $rubber = wp_cache_get( $id, 'rubbers' );

        if ( ! $rubber ) {
            $rubber = $this->wpdb->get_row(
                $this->wpdb->prepare(
                    "SELECT `id`, `match_id`, `group`, DATE_FORMAT(`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(`date`, '%%e') AS day, DATE_FORMAT(`date`, '%%c') AS month, DATE_FORMAT(`date`, '%%Y') AS year, DATE_FORMAT(`date`, '%%H') AS `hour`, DATE_FORMAT(`date`, '%%i') AS `minutes`, `home_points`, `away_points`, `winner_id`, `loser_id`, `post_id`, `type`, `custom`, `rubber_number`, `status` FROM $this->table_name WHERE `id` = %d LIMIT 1",
                    $id
                )
            );

            if ( ! $rubber ) {
                return null;
            }
            $rubber = new Rubber( $rubber );
            $rubber->players = $this->find_players_by_rubber_id( (int) $rubber->get_id() );

            wp_cache_set( (int) $rubber->get_id(), $rubber, 'rubbers' );
        }

        return $rubber;
    }

    /**
     * Find all rubbers for a given fixture ID.
     *
     * @param int $fixture_id
     * @param int|null $player_id Optional player ID to filter by.
     *
     * @return Rubber[]
     */
    public function find_by_fixture_id( int $fixture_id, ?int $player_id = null ): array {
        if ( $player_id ) {
            $sql = $this->wpdb->prepare(
                "SELECT r.`id`, r.`match_id`, r.`group`, DATE_FORMAT(r.`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(r.`date`, '%%e') AS day, DATE_FORMAT(r.`date`, '%%c') AS month, DATE_FORMAT(r.`date`, '%%Y') AS year, DATE_FORMAT(r.`date`, '%%H') AS `hour`, DATE_FORMAT(r.`date`, '%%i') AS `minutes`, r.`home_points`, r.`away_points`, r.`winner_id`, r.`loser_id`, r.`post_id`, r.`type`, r.`custom`, r.`rubber_number`, r.`status` FROM $this->table_name r, $this->players_table_name rp WHERE r.`match_id` = %d AND r.`id` = rp.`rubber_id` AND rp.`player_id` = %d ORDER BY r.`date` ASC, r.`id` ASC",
                $fixture_id,
                $player_id
            );
        } else {
            $sql = $this->wpdb->prepare(
                "SELECT r.`id`, r.`match_id`, r.`group`, DATE_FORMAT(r.`date`, '%%Y-%%m-%%d %%H:%%i') AS date, DATE_FORMAT(r.`date`, '%%e') AS day, DATE_FORMAT(r.`date`, '%%c') AS month, DATE_FORMAT(r.`date`, '%%Y') AS year, DATE_FORMAT(r.`date`, '%%H') AS `hour`, DATE_FORMAT(r.`date`, '%%i') AS `minutes`, r.`home_points`, r.`away_points`, r.`winner_id`, r.`loser_id`, r.`post_id`, r.`type`, r.`custom`, r.`rubber_number`, r.`status` FROM $this->table_name r WHERE r.`match_id` = %d ORDER BY r.`date` ASC, r.`id` ASC",
                $fixture_id
            );
        }

        $results = $this->wpdb->get_results( $sql );

        $rubbers = [];
        if ( is_array( $results ) ) {
            foreach ( $results as $row ) {
                $rubber = new Rubber( $row );
                $rubber->players = $this->find_players_by_rubber_id( (int) $rubber->get_id() );
                $rubbers[] = $rubber;
            }
        }

        return $rubbers;
    }

    /**
     * Count rubbers for a given fixture ID.
     *
     * @param int $fixture_id
     * @param int|null $player_id Optional player ID to filter by.
     *
     * @return int
     */
    public function count_by_fixture_id( int $fixture_id, ?int $player_id = null ): int {
        if ( ! $player_id ) {
            return (int) $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT count(*) FROM $this->table_name WHERE `match_id` = %d",
                    $fixture_id
                )
            );
        }

        $sql = $this->wpdb->prepare(
            "SELECT count(*) FROM $this->table_name r, $this->players_table_name rp WHERE r.`id` = rp.`rubber_id` AND r.`match_id` = %d AND rp.`player_id` = %d",
            $fixture_id,
            $player_id
        );

        return (int) $this->wpdb->get_var( $sql );
    }
    /**
     * Delete rubbers for a given fixture ID.
     *
     * @param int $fixture_id
     * @return bool
     */
    public function delete_by_fixture_id( int $fixture_id ): bool {
        $rubbers = $this->find_by_fixture_id( $fixture_id );
        foreach ( $rubbers as $rubber ) {
            $this->delete_players( (int) $rubber->get_id() );
            wp_cache_delete( (int) $rubber->get_id(), 'rubbers' );
        }
        return $this->wpdb->delete(
            $this->table_name,
            array( 'match_id' => $fixture_id ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Update rubber dates for a given fixture ID.
     *
     * @param int $fixture_id
     * @param string $date
     * @return bool
     */
    public function update_date_by_fixture_id( int $fixture_id, string $date ): bool {
        $canonical_date = Fixture_Time_Parser::to_mysql_datetime( $date );
        $updated = $this->wpdb->update(
            $this->table_name,
            array( 'date' => $canonical_date ),
            array( 'match_id' => $fixture_id ),
            array( '%s' ),
            array( '%d' )
        ) !== false;
        wp_cache_flush_group( 'rubbers' );
        return $updated;
    }

    /**
     * Delete a rubber by its ID.
     *
     * @param int $id
     *
     * @return bool True on success, false on failure.
     */
    public function delete( int $id ): bool {
        $this->delete_players( $id );
        $deleted = $this->wpdb->delete( $this->table_name, array( 'id' => $id ), array( '%d' ) ) !== false;
        wp_cache_delete( $id, 'rubbers' );
        return $deleted;
    }

    /**
     * Save players for a given rubber.
     *
     * @param int $rubber_id
     * @param array $players
     * @return bool
     */
    public function save_players( int $rubber_id, array $players ): bool {

        foreach ( $players as $player_team => $player_ref ) {
            if ( ! is_array( $player_ref ) ) {
                continue;
            }
            foreach ( $player_ref as $player_num => $player ) {
                if ( empty( $player ) ) {
                    continue;
                }
                $club_player_id = 0;
                $player_id      = 0;
                if ( is_object( $player ) ) {
                    $club_player_id = (int) ( $player->registration_id ?? $player->club_player_id ?? $player->id ?? 0 );
                    $player_id      = (int) ( $player->user_id ?? $player->player_id ?? 0 );
                } elseif ( is_numeric( $player ) ) {
                    $club_player_id = (int) $player;
                }

                if ( $club_player_id && ! $player_id ) {
                    $registration_service = $this->get_registration_service();
                    if ( ! $registration_service ) {
                        throw new \LogicException( 'Registration_Service is required to save players by ID.' );
                    }
                    $club_player = $registration_service->get_registration( $club_player_id );
                    if ( $club_player ) {
                        $player_id      = (int) ( $club_player->user_id ?? 0 );
                        $club_player_id = (int) ( $club_player->registration_id ?? $club_player_id );
                    }
                }

                if ( $club_player_id && $player_id ) {
                    $this->wpdb->query(
                        $this->wpdb->prepare(
                            "REPLACE INTO $this->players_table_name ( `rubber_id`, `player_ref`, `player_team`, `player_id`, `club_player_id` ) VALUES ( %d, %d, %s, %d, %d )",
                            $rubber_id,
                            $player_num,
                            $player_team,
                            $player_id,
                            $club_player_id
                        )
                    );
                }
            }
        }
        wp_cache_delete( $rubber_id, 'rubbers' );
        return true;
    }

    /**
     * Delete players for a given rubber.
     *
     * @param int $rubber_id
     * @return bool
     */
    public function delete_players( int $rubber_id ): bool {
        return $this->wpdb->delete(
            $this->players_table_name,
            array( 'rubber_id' => $rubber_id ),
            array( '%d' )
        ) !== false;
    }

    /**
     * Reset rubber result in database and cache.
     *
     * @param int $rubber_id
     * @return bool
     */
    public function reset_result( int $rubber_id ): bool {
        $this->delete_players( $rubber_id );
        $updated = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE $this->table_name SET `home_points` = null, `away_points` = null, `winner_id` = 0, `loser_id` = 0, `custom` = null, `status` = null WHERE `id` = %d",
                $rubber_id
            )
        );
        $rubber = wp_cache_get( $rubber_id, 'rubbers' );
        if ( $rubber instanceof Rubber ) {
            $rubber->reset_result();
            wp_cache_set( $rubber_id, $rubber, 'rubbers' );
        } else {
            wp_cache_delete( $rubber_id, 'rubbers' );
        }
        return $updated !== false;
    }

    /**
     * Find players for a given rubber.
     *
     * @param int $rubber_id
     * @return array
     */
    public function find_players_by_rubber_id( int $rubber_id ): array {

        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT `id`, `player_ref`, `player_team`, `player_id`, `club_player_id` FROM $this->players_table_name WHERE `rubber_id` = %d",
                $rubber_id
            )
        );

        $players = array();
        if ( is_array( $results ) && ! empty( $results ) ) {
            $registration_service = $this->get_registration_service();
            if ( ! $registration_service ) {
                throw new \LogicException( 'Registration_Service is required to hydrate rubber players.' );
            }
            $results_checker_table = $this->wpdb->prefix . 'racketmanager_results_checker';
            foreach ( $results as $player ) {
                $registered_player              = $registration_service->get_registration( (int) $player->club_player_id );
                $registered_player->description = null;
                $registered_player->class       = null;

                $player_errors = $this->wpdb->get_results(
                    $this->wpdb->prepare(
                        "SELECT `description` FROM $results_checker_table WHERE `rubber_id` = %d AND `player_id` = %d AND (`status` IS NULL OR `status` != 1)",
                        $rubber_id,
                        $player->player_id,
                    )
                );
                if ( $player_errors ) {
                    $registered_player->class = 'is-ineligible';
                    foreach ( $player_errors as $player_error ) {
                        if ( ! empty( $registered_player->description ) ) {
                            $registered_player->description .= ', ';
                        }
                        $registered_player->description .= $player_error->description;
                    }
                }
                $players[ $player->player_team ][ $player->player_ref ] = $registered_player;
            }
        }
        return $players;
    }
}
