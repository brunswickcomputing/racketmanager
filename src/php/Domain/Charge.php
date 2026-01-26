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
     * @var int
     */
    public int $id;
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
     * @var string|float
     */
    public string|float $fee_competition;
    /**
     * Team fee
     *
     * @var string|float
     */
    public string|float $fee_event;
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
            );  // db call ok.

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

            if ( ! isset( $this->id ) ) {
                $this->add();
            }
            $this->competition = get_competition( $this->competition_id );
        }
    }

    /**
     * Add new charge
     */
    private function add(): void {
        global $wpdb;
        if ( empty( $this->status ) ) {
            $this->status = 'draft';
        }
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "INSERT INTO $wpdb->racketmanager_charges (`season`, `competition_id`, `status`, `date`, `fee_competition`, `fee_event`) VALUES (%s, %d, %s, %s, %d, %d)",
                $this->season,
                $this->competition_id,
                $this->status,
                $this->date,
                $this->fee_competition,
                $this->fee_event
            )
        );
        $this->id = $wpdb->insert_id;
    }
    public function update( object $update ): bool {
        $updates = false;
        if ( floatval( $this->fee_competition ) !== floatval( $update->fee_competition ) ) {
            $this->set_club_fee( $update->fee_competition );
            $updates = true;
        }
        if ( floatval($this->fee_event ) !== floatval( $update->fee_event ) ) {
            $this->set_team_fee( $update->fee_event );
            $updates = true;
        }
        if ( $this->status !== $_POST['status'] ) {
            $this->set_status( sanitize_text_field( wp_unslash( $_POST['status'] ) ) );
            $updates = true;
        }
        if ( $this->competition_id !== $update->competition_id ) {
            $this->set_competition_id( $update->competition_id );
            $updates = true;
        }
        if ( $this->date !== $update->date ) {
            $this->set_date( $update->date );
            $updates = true;
        }
        if ( $this->season !== $update->season ) {
            $this->set_season( $update->season );
            $updates = true;
        }
        return $updates;
    }
    /**
     * Set charge status
     *
     * @param string $status status value.
     */
    public function set_status( string $status ): void {
        global $wpdb;
        $this->status = $status;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_charges set `status` = %s WHERE `id` = %d",
                $status,
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'charges' );
    }

    /**
     * Set club fee
     *
     * @param float|string $fee_competition club fee value.
     */
    public function set_club_fee( float|string $fee_competition ): void {
        global $wpdb;
        $this->fee_competition = $fee_competition;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_charges set `fee_competition` = %d WHERE `id` = %d",
                $fee_competition,
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'charges' );
    }

    /**
     * Set team fee
     *
     * @param float|string $fee_event team fee value.
     */
    public function set_team_fee( float|string $fee_event ): void {
        global $wpdb;
        $this->fee_event = $fee_event;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_charges set `fee_event` = %d WHERE `id` = %d",
                $fee_event,
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'charges' );
    }
    /**
     * Set season
     *
     * @param string $season season.
     */
    public function set_season( string $season ): void {
        global $wpdb;
        $this->season = $season;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_charges set `season` = %s WHERE `id` = %d",
                $season,
                $this->id
            )
        );
        wp_cache_set( $this->id, $this, 'charges' );
    }
    /**
     * Set competition id
     *
     * @param int $competition_id competition id.
     */
    public function set_competition_id( int $competition_id ): void {
        global $wpdb;
        $this->competition_id = $competition_id;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_charges set `competition_id` = %d WHERE `id` = %d",
                $competition_id,
                $this->id
            )
        );
        wp_cache_set( $this->id, $this, 'charges' );
    }

    /**
     * Set date
     *
     * @param string $date date.
     */
    public function set_date( string $date ): void {
        global $wpdb;
        $this->date = $date;
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_charges set `date` = %s WHERE `id` = %d",
                $date,
                $this->id
            )
        );  // db call ok.
        wp_cache_set( $this->id, $this, 'charges' );
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
     * Does the charge have invoices
     */
    public function has_invoices(): ?int {
        global $wpdb;

        return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "SELECT count(*) FROM $wpdb->racketmanager_invoices WHERE `charge_id` = %d",
                $this->id
            )
        );
    }
    /**
     * Get invoices
     */
    public function get_invoices() {
        global $racketmanager;
        return $racketmanager->get_invoices( array( 'charge' => $this->id ) );
    }
    /**
     * Get club entries for charges
     */
    public function get_club_entries(): array {
        global $racketmanager;
        $club_entries = array();
        $clubs        = $racketmanager->get_clubs();
        foreach ( $clubs as $club ) {
            $club_entry = $this->get_club_entry( $club );
            if ( $club_entry ) {
                $club_entries[] = $club_entry;
            }
        }
        return $club_entries;
    }
    /**
     * Get club entries for charges
     *
     * @param object $club club.
     */
    public function get_club_entry( object $club ): false|object {
        $club_teams  = 0;
        $club_events = array();
        $competition = get_competition( $this->competition_id );
        if ( $competition ) {
            $events = $competition->get_events();
            foreach ( $events as $event ) {
                $event     = get_event( $event->id );
                $num_teams = $event->get_teams(
                    array(
                        'club'   => $club->id,
                        'season' => $this->season,
                        'count'  => true,
                    )
                );
                if ( $num_teams > 0 ) {
                    $club_event        = new stdClass();
                    $club_event->type  = $event->type;
                    $club_event->count = $num_teams;
                    $club_event->fee   = $this->fee_event * $num_teams;
                    $club_events[]     = $club_event;
                }
                $club_teams += $num_teams;
            }
            if ( $club_teams > 0 ) {
                $club_entry                  = new stdClass();
                $club_entry->id              = $club->id;
                $club_entry->name            = $club->name;
                $club_entry->num_teams       = $club_teams;
                $club_entry->fee_competition = $this->fee_competition;
                $club_entry->fee_events      = $this->fee_event * $club_teams;
                $club_entry->fee             = $club_entry->fee_competition + $club_entry->fee_events;
                $club_entry->events          = $club_events;
                return $club_entry;
            } else {
                return false;
            }
        } else {
            return false;
        }
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
    /**
     * Generate and send invoices
     */
    public function send_invoices(): void {
        global $racketmanager;
        $sent            = false;
        $charges_entries = $this->get_club_entries();
        foreach ( $charges_entries as $entry ) {
            $invoice                 = new stdClass();
            $invoice->charge_id      = $this->id;
            $invoice->club_id        = $entry->id;
            $invoice->date           = $this->date;
            $invoice                 = new Invoice( $invoice );
            $invoice->set_amount( $entry->fee );
            $invoice->set_details( $entry );
            $sent = $invoice->send();
            if ( $sent ) {
                $invoice->set_status( 'sent' );
            }
        }
        if ( $sent ) {
            $racketmanager->set_message( __( 'Invoices sent', 'racketmanager' ) );
            $this->set_status( 'final' );
        } else {
            $racketmanager->set_message( __( 'No invoices sent', 'racketmanager' ), true );
        }
    }
}
