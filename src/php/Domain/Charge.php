<?php
namespace Racketmanager\Domain;

use stdClass;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;

/**
 * Class to implement the charges object (moved to PSR-4)
 */
final class Charge {
    /**
     * Id
     *
     * @var int|null
     */
    public ?int $id = null;
    /**
     * Season
     *
     * @var string
     */
    public string $season;
    /**
     * Competition id
     *
     * @var int
     */
    public int $competition_id;
    /**
     * Competition
     *
     * @var object|null
     */
    public null|object $competition;
    /**
     * Status
     *
     * @var string
     */
    public string $status;
    /**
     * Date
     *
     * @var string
     */
    public string $date;
    /**
     * Club fee
     *
     * @var null|float
     */
    public null|float $fee_competition;
    /**
     * Team fee
     *
     * @var null|float
     */
    public null|float $fee_event;
    /**
     * Total
     *
     * @var float
     */
    public float $total;
    /**
     * Get class instance
     *
     * @param int|string $charge_id id.
     */
    public static function get_instance( int|string $charge_id ) {
        global $wpdb;
        if ( ! $charge_id ) {
            return false;
        }
        if ( is_numeric( $charge_id ) ) {
            $search = $wpdb->prepare(
                '`id` = %d',
                intval( $charge_id )
            );
        } else {
            $search_terms   = explode( '_', $charge_id );
            $competition_id = $search_terms[0];
            $season         = $search_terms[1];
            $search         = $wpdb->prepare(
                '`competition_id` = %d AND `season` = %s',
                intval( $competition_id ),
                $season,
            );
        }
        $charge = wp_cache_get( $charge_id, 'charges' );

        if ( ! $charge ) {
            $charge = $wpdb->get_row(
                "SELECT `id`, `competition_id`, `season`, `status`, `date`, `fee_competition`, `fee_event` FROM $wpdb->racketmanager_charges WHERE $search LIMIT 1",
            );

            if ( ! $charge ) {
                return false;
            }

            $charge = new Charge( $charge );

            wp_cache_set( $charge->id, $charge, 'charges' );
        }

        return $charge;
    }

    /**
     * Construct class instance
     *
     * @param object|null $charges charges object.
     */
    public function __construct( ?object $charges = null ) {
        if ( ! is_null( $charges ) ) {
            foreach ( get_object_vars( $charges ) as $key => $value ) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Delete charge
     */
    public function delete(): void {
        global $wpdb;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "DELETE FROM $wpdb->racketmanager_charges WHERE `id` = %d",
                $this->id
            )
        );
        wp_cache_delete( $this->id, 'charges' );
    }

    /**
     * Get player entries for charges
     *
     * @param object $player player.
     * @return object|null player entry or false
     */
    public function get_player_entry( object $player ): object|false {
        $player_events = array();
        $entered       = 0;
        $competition   = get_competition( $this->competition_id );
        if ( $competition ) {
            $entry         = new stdClass();
            $entry->id     = $player->id;
            $entry->name   = $player->display_name;
            $events        = $competition->get_events();
            foreach ( $events as $event ) {
                $event      = get_event( $event->id );
                $is_entered = $event->get_teams(
                    array(
                        'player' => $player->id,
                        'season' => $this->season,
                        'count'  => true,
                    )
                );
                if ( $is_entered ) {
                    $player_event        = new stdClass();
                    $player_event->type  = $event->type;
                    $player_event->count = $is_entered;
                    $player_event->fee   = $this->fee_event;
                    $player_events[]     = $player_event;
                    ++$entered;
                }
            }
            $entry->num_teams       = $entered;
            $entry->fee_competition = $this->fee_competition;
            $entry->fee_events      = $this->fee_event * $entered;
            $entry->fee             = $entry->fee_competition + $entry->fee_events;
            $entry->events          = $player_events;
            $entry->paid            = 0;
            return $entry;
        } else {
            return false;
        }
    }

    public function get_id(): int|null {
        return $this->id;
    }

    public function get_competition_id(): int {
        return $this->competition_id;
    }

    public function get_season(): string {
        return $this->season;
    }

    public function get_date(): string {
        return $this->date;
    }

    public function get_status(): string {
        return $this->status;
    }

    public function get_fee_competition(): float|null {
        return $this->fee_competition;
    }

    public function get_fee_event(): float|null {
        return $this->fee_event;
    }

    public function set_id( int $insert_id ): void {
        $this->id = $insert_id;
    }

    /**
     * Set charge status
     *
     * @param string $status status value.
     */
    public function set_status( string $status ): void {
        $this->status = $status;
    }

    /**
     * Set club fee
     *
     * @param float|null $fee_competition
     */
    public function set_fee_competition( float|null $fee_competition ): void {
        $this->fee_competition = number_format( $fee_competition,2, '.', '');
    }

    /**
     * Set team fee
     *
     * @param float|null $fee_event team fee value.
     */
    public function set_fee_event( float|null $fee_event ): void {
        $this->fee_event = number_format( $fee_event,2, '.', '');
    }
    /**
     * Set season
     *
     * @param string $season season.
     */
    public function set_season( string $season ): void {
        $this->season = $season;
    }
    /**
     * Set competition id
     *
     * @param int $competition_id competition id.
     */
    public function set_competition_id( int $competition_id ): void {
        $this->competition_id = $competition_id;
    }

    /**
     * Set date
     *
     * @param string $date date.
     */
    public function set_date( string $date ): void {
        $this->date = $date;
    }

}
