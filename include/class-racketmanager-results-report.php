<?php
/**
 * Racketmanager_Results_Report API: Racketmanager_Results_Report class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Racketmanager_Results_Report
 */

namespace Racketmanager;

/**
 * Class to implement the results report object
 */
final class Racketmanager_Results_Report {
	/**
	 * Id
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Season
	 *
	 * @var int
	 */
	public $match_id;
	/**
	 * Results report object stored in database as string
	 *
	 * @var string
	 */
	public $result_object;
	/**
	 * Results report object
	 *
	 * @var object
	 */
	public $data;
	/**
	 * Get class instance
	 *
	 * @param int $results_report_id id.
	 */
	public static function get_instance( $results_report_id ) {
		global $wpdb;
		if ( ! $results_report_id ) {
			return false;
		}
		$results_report = wp_cache_get( $results_report_id, 'results_report' );

		if ( ! $results_report ) {
			$results_report = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT `id`, `match_id`, `result_object` FROM {$wpdb->racketmanager_results_report} WHERE `id` = %d LIMIT 1",
					$results_report_id
				)
			);  // db call ok.

			if ( ! $results_report ) {
				return false;
			}

			$results_report = new Racketmanager_Results_Report( $results_report );

			wp_cache_set( $results_report->id, $results_report, 'results_report' );
		}

		return $results_report;
	}

	/**
	 * Construct class instance
	 *
	 * @param object $results_report results_report object.
	 */
	public function __construct( $results_report = null ) {
		if ( ! is_null( $results_report ) ) {
			foreach ( get_object_vars( $results_report ) as $key => $value ) {
				$this->$key = $value;
			}
			if ( ! $this->data ) {
				$this->data = json_decode( $this->result_object );
			}
			if ( ! $this->result_object ) {
				$this->result_object = wp_json_encode( $this->data );
			}
			if ( ! isset( $this->id ) ) {
				$this->add();
			}
		}
	}

	/**
	 * Add new results report
	 */
	private function add() {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_results_report} (`match_id`, `result_object`) VALUES (%d, %s)",
				$this->match_id,
				$this->result_object,
			)
		);
		$this->id = $wpdb->insert_id;
	}
	/**
	 * Delete results report
	 */
	public function delete() {
		global $wpdb;

		$wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_results_report} WHERE `id` = %d",
				$this->id
			)
		);
	}
}
