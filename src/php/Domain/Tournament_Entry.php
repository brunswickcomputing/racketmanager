<?php

namespace Racketmanager\Domain;

/**
 * Class to implement the Tournament Entry object (moved to PSR-4)
 */
final class Tournament_Entry {
    /**
     * Id
     *
     * @var int|null
     */
    public int|null $id = null;
    /**
     * Tournament id
     *
     * @var int
     */
    public int $tournament_id;
    /**
     * Player id
     *
     * @var int
     */
    public int $player_id;
    /**
     * Status
     *
     * @var int
     */
    public int $status;
    /**
     * Fee
     *
     * @var int|null
     */
    public ?int $fee;
    /**
     * Club id
     *
     * @var int|null
     */
    public ?int $club_id;
    /**
     * Club
     *
     * @var object|null
     */
    public null|object $club = null;

    /**
     * Constructor
     *
     * @param object|null $tournament_entry Tournament Entry object.
     */
    public function __construct( ?object $tournament_entry = null ) {
        if ( is_null( $tournament_entry ) ) {
            return;
        }
        $this->id            = $tournament_entry->id ?? null;
        $this->tournament_id = $tournament_entry->tournament_id;
        $this->player_id     = $tournament_entry->player_id;
        $this->status        = $tournament_entry->status;
        $this->fee           = $tournament_entry->fee;
        $this->club_id       = $tournament_entry->club_id ?? null;
    }

    /**
     * Retrieve tournament entry instance
     *
     * @param int|string $tournament_entry_id tournament entry id.
     * @param string $search_term search term - defaults to id.
     *
     * @return object|false
     */
    public static function get_instance( int|string $tournament_entry_id, string $search_term = 'id' ): object|false {
        global $wpdb;
        if ( ! $tournament_entry_id ) {
            return false;
        }
        switch ( $search_term ) {
            case 'key':
                $search_terms  = explode( '_', $tournament_entry_id );
                $tournament_id = $search_terms[0];
                $player_id     = $search_terms[1];
                $search        = $wpdb->prepare(
                    '`tournament_id` = %d AND `player_id` = %d',
                    intval( $tournament_id ),
                    $player_id,
                );
                break;
            case 'id':
            default:
                $search = $wpdb->prepare(
                    '`id` = %d',
                    $tournament_entry_id
                );
                break;
        }
        $tournament_entry = wp_cache_get( $tournament_entry_id, 'tournament_entries' );
        if ( ! $tournament_entry ) {
            $tournament_entry = $wpdb->get_row(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT `id`, `tournament_id`, `player_id`, `status`, `fee`, `club_id` FROM $wpdb->racketmanager_tournament_entries WHERE $search"
            );
            if ( ! $tournament_entry ) {
                return false;
            }
            $tournament_entry = new Tournament_Entry( $tournament_entry );
            wp_cache_set( $tournament_entry_id, $tournament_entry, 'tournament_entries' );
        }

        return $tournament_entry;
    }

    public function get_id(): ?int {
        return $this->id;
    }

    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }

    public function get_tournament_id(): int {
        return $this->tournament_id;
    }

    /**
     * @param int $tournament_id
     */
    public function set_tournament_id( int $tournament_id ): void {
        $this->tournament_id = $tournament_id;
    }

    public function get_player_id(): int {
        return $this->player_id;
    }

    public function set_player_id( int $player_id ): void {
        $this->player_id = $player_id;
    }

    public function get_status(): int {
        return $this->status;
    }

    /**
     * Set tournament entry status
     *
     * @param int $status status.
     */
    public function set_status( int $status ): void {
        $this->status = $status;
    }

    public function get_fee(): ?int {
        return $this->fee;
    }

    /**
     * Set tournament entry fee
     *
     * @param int $fee tournament fee.
     */
    public function set_fee( int $fee ): void {
        $this->fee = $fee;
    }

    public function get_club_id(): ?int {
        return $this->club_id;
    }

    public function set_club_id( int $club_id ): void {
        $this->club_id = $club_id;
    }

}
