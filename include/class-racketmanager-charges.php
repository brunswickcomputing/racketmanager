<?php
/**
 * Racketmanager_Charges API: Racketmanager_Charges class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Racketmanager_Charges
 */

namespace Racketmanager;

/**
 * Class to implement the charges object
 */
final class Racketmanager_Charges {
	/**
	 * Id
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Season
	 *
	 * @var string
	 */
	public $season;
	/**
	 * Competition id
	 *
	 * @var int
	 */
	public $competition_id;
	/**
	 * Competition
	 *
	 * @var object
	 */
	public $competition;
	/**
	 * Status
	 *
	 * @var string
	 */
	public $status;
	/**
	 * Date
	 *
	 * @var string
	 */
	public $date;
	/**
	 * Club fee
	 *
	 * @var string
	 */
	public $fee_club;
	/**
	 * Team fee
	 *
	 * @var string
	 */
	public $fee_team;

	/**
	 * Get class instance
	 *
	 * @param int $charges_id id.
	 */
	public static function get_instance( $charges_id ) {
		global $wpdb;
		if ( ! $charges_id ) {
			return false;
		}
		$charges = wp_cache_get( $charges_id, 'charges' );

		if ( ! $charges ) {
			$charges = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `id`, `competition_id`, `season`, `status`, `date`, `feeClub` as `fee_club`, `feeTeam` as `fee_team` FROM {$wpdb->racketmanager_charges} WHERE `id` = %d LIMIT 1",
					$charges_id
				)
			);  // db call ok.

			if ( ! $charges ) {
				return false;
			}

			$charges = new Racketmanager_Charges( $charges );

			wp_cache_set( $charges->id, $charges, 'charges' );
		}

		return $charges;
	}

	/**
	 * Construct class instance
	 *
	 * @param object $charges charges object.
	 */
	public function __construct( $charges = null ) {
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
	private function add() {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_charges} (`season`, `competition_id`, `status`, `date`, `feeClub`, `feeTeam`) VALUES (%s, %d, %s, %s, %d, %d)",
				$this->season,
				$this->competition_id,
				$this->status,
				$this->date,
				$this->fee_club,
				$this->fee_team
			)
		);
		$this->id = $wpdb->insert_id;
	}

	/**
	 * Set charge status
	 *
	 * @param string $status status value.
	 */
	public function set_status( $status ) {
		global $wpdb;
		$this->status = $status;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_charges} set `status` = %s WHERE `id` = %d",
				$status,
				$this->id
			)
		);  // db call ok.
		wp_cache_set( $this->id, $this, 'charges' );
	}

	/**
	 * Set club fee
	 *
	 * @param string $fee_club club fee value.
	 */
	public function set_club_fee( $fee_club ) {
		global $wpdb;
		$this->fee_club = $fee_club;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_charges} set `fee_club` = %d WHERE `id` = %d",
				$fee_club,
				$this->id
			)
		);  // db call ok.
		wp_cache_set( $this->id, $this, 'charges' );
	}

	/**
	 * Set team fee
	 *
	 * @param string $fee_team team fee value.
	 */
	public function set_team_fee( $fee_team ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_charges} set `fee_team` = %d WHERE `id` = %d",
				$fee_team,
				$this->id
			)
		);  // db call ok.
		wp_cache_delete( $this->id, 'charges' );
	}

	/**
	 * Set charge type
	 *
	 * @param string $type charge type.
	 */
	public function set_type( $type ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_charges} set `type` = %s WHERE `id` = %d",
				$type,
				$this->id
			)
		);  // db call ok.
		wp_cache_delete( $this->id, 'charges' );
	}

	/**
	 * Set competition type
	 *
	 * @param string $type competition type.
	 */
	public function set_competition_type( $type ) {
		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_charges} set `competitionType` = %s WHERE `id` = %d",
				$type,
				$this->id
			)
		);  // db call ok.
		wp_cache_delete( $this->id, 'charges' );
	}

	/**
	 * Set season
	 *
	 * @param string $season season.
	 */
	public function set_season( $season ) {
		global $wpdb;
		$this->season = $season;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_charges} set `season` = %s WHERE `id` = %d",
				$season,
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
	public function set_date( $date ) {
		global $wpdb;
		$this->date = $date;
		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_charges} set `date` = %s WHERE `id` = %d",
				$date,
				$this->id
			)
		);  // db call ok.
		wp_cache_set( $this->id, $this, 'charges' );
	}

	/**
	 * Delete charge
	 */
	public function delete() {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_charges} WHERE `id` = %d",
				$this->id
			)
		);
		wp_cache_delete( $this->id, 'charges' );
	}

	/**
	 * Does the charge have invoiecs
	 */
	public function has_invoices() {
		global $wpdb;

		return $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->racketmanager_invoices} WHERE `charge_id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Get club enties for charges
	 */
	public function get_club_entries() {
		global $racketmanager;
		$club_entries = array();
		$clubs        = $racketmanager->get_clubs(
			array(
				'type' => 'affiliated',
			)
		);
		foreach ( $clubs as $club ) {
			$club_entry = $this->get_club_entry( $club );
			if ( $club_entry ) {
				$club_entries[] = $club_entry;
			}
		}
		return $club_entries;
	}

	/**
	 * Get club enties for charges
	 *
	 * @param object $club club.
	 */
	public function get_club_entry( $club ) {
		$club_teams  = 0;
		$club_event  = array();
		$competition = get_competition( $this->competition_id );
		$events      = $competition->get_events();
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
				$club_event        = new \stdClass();
				$club_event->type  = $event->type;
				$club_event->count = $num_teams;
				$club_event->fee   = $this->fee_team * $num_teams;
				$club_events[]     = $club_event;
			}
			$club_teams += $num_teams;
		}
		if ( $club_teams > 0 ) {
			$club_entry            = new \stdClass();
			$club_entry->id        = $club->id;
			$club_entry->name      = $club->name;
			$club_entry->num_teams = $club_teams;
			$club_entry->fee_club  = $this->fee_club;
			$club_entry->fee_teams = $this->fee_team * $club_teams;
			$club_entry->fee       = $club_entry->fee_club + $club_entry->fee_teams;
			$club_entry->events    = $club_events;
			return $club_entry;
		} else {
			return false;
		}
	}
}
