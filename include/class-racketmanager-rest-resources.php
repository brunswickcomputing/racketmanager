<?php
/**
 * Handles registering Racketmanager custom REST endpoints.
 *
 * Class Racketmanager_Rest_Resources
 *
 * @package Racketmanager
 */

namespace Racketmanager;

use WP_REST_Server;
use WP_REST_Controller;
use WP_REST_Response;
use WP_Error;

/**
 * Class to implement the Racketmanager_Rest_Resources object
 */
class Racketmanager_Rest_Resources extends WP_REST_Controller {
	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		$version   = '1';
		$namespace = 'racketmanager/v' . $version;
		$base      = 'standings';
		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_standings' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'club'        => array(
							'description' => __( 'club name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
						'season'      => array(
							'description' => __( 'season', 'racketmanager' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'competition' => array(
							'description' => __( 'Competition name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
						'event'       => array(
							'description' => __( 'Event name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
					),
				),
			)
		);
		$base = 'fixtures';
		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_matches' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'club'        => array(
							'description' => __( 'club name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
						'season'      => array(
							'description' => __( 'season', 'racketmanager' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'competition' => array(
							'description' => __( 'Competition name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
						'event'       => array(
							'description' => __( 'Event name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
						'league'      => array(
							'description' => __( 'League name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
					),
				),
			)
		);
		$base = 'results';
		register_rest_route(
			$namespace,
			'/' . $base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_matches' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'club'        => array(
							'description' => __( 'club name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
						'days'        => array(
							'description' => __( 'Number of days to look for results', 'racketmanager' ),
							'type'        => 'integer',
							'required'    => false,
							'default'     => 7,
						),
						'season'      => array(
							'description' => __( 'season', 'racketmanager' ),
							'type'        => 'integer',
							'required'    => true,
						),
						'competition' => array(
							'description' => __( 'Competition name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
						'event'       => array(
							'description' => __( 'Event name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
						'league'      => array(
							'description' => __( 'League name', 'racketmanager' ),
							'type'        => 'string',
							'required'    => false,
						),
					),
				),
			)
		);
		register_rest_route(
			$namespace,
			'/' . $base . '/schema',
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $this, 'get_public_item_schema' ),
			)
		);
	}
	/**
	 * Get a collection of standings
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_standings( $request ) {
		$season = isset( $request['season'] ) ? $request['season'] : null;
		if ( isset( $request['club'] ) ) {
			if ( is_numeric( $request['club'] ) ) {
				$club_id = intval( $request['club'] );
				$club    = get_club( $club_id );
			} else {
				$club_name = un_seo_url( $request['club'] );
				$club      = get_club( $club_name, 'shortcode' );
				if ( $club ) {
					$club_id = $club->id;
				}
			}
		} else {
			$club    = '';
			$club_id = '';
		}
		if ( isset( $request['competition'] ) ) {
			$competition = un_seo_url( sanitize_text_field( wp_unslash( $request['competition'] ) ) );
			$competition = get_competition( $competition, 'name' );
			if ( $competition ) {
				$events = $competition->get_events();
			}
		} elseif ( isset( $request['event'] ) ) {
			$event = un_seo_url( sanitize_text_field( wp_unslash( $request['event'] ) ) );
			$event = get_event( $event, 'name' );
			if ( $event ) {
				$events[] = $event;
			}
		} else {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The standings grouping is missing', 'racketmanager' ), array( 'status' => 400 ) );
		}
		$data = array();
		foreach ( $events as $event ) {
			$event = get_event( $event );
			if ( $event ) {
				$leagues = $event->get_leagues();
				foreach ( $leagues as $league ) {
					$league = get_league( $league->id );
					$teams  = $league->get_league_teams(
						array(
							'season' => $season,
							'club'   => $club_id,
						)
					);
					$i      = 0;
					foreach ( $teams as $team ) {
						$team->league = $league->title;
						$teams[ $i ]  = $team;
						++$i;
					}
					if ( $teams ) {
						foreach ( $teams as $team ) {
							$json_result = new \stdClass();
							if ( ! empty( $club ) ) {
								$json_result->club = str_replace( '"', '', $club->shortcode );
							}
							$json_result->league = $team->league;
							$json_result->season = $team->season;
							$json_result->team   = $team->title;
							$json_result->rank   = $team->rank;
							$json_result->status = $team->status;
							$json_result->played = $team->done_matches;
							$json_result->won    = $team->won_matches;
							$json_result->drawn  = $team->draw_matches;
							$json_result->lost   = $team->lost_matches;
							$json_result->points = $team->points['plus'];
							$data[]              = $json_result;
						}
					}
				}
			}
		}

		return new WP_REST_Response( $data, 200 );
	}
	/**
	 * Get a collection of matches
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_matches( $request ) {
		global $racketmanager;
		$season = isset( $request['season'] ) ? $request['season'] : null;
		if ( isset( $request['club'] ) ) {
			if ( is_numeric( $request['club'] ) ) {
				$club_id = intval( $request['club'] );
				$club    = get_club( $club_id );
			} else {
				$club_name = un_seo_url( $request['club'] );
				$club      = get_club( $club_name, 'shortcode' );
				if ( $club ) {
					$club_id = $club->id;
				}
			}
		} else {
			$club    = '';
			$club_id = '';
		}
		$match_args           = array();
		$match_args['season'] = $season;
		if ( $club_id ) {
			$match_args['affiliatedClub'] = $club_id;
		}
		if ( isset( $request['days'] ) ) {
			$match_args['time']    = 'latest';
			$match_args['days']    = $request['days'];
			$match_args['history'] = $request['days'];
		}
		if ( isset( $request['competition'] ) ) {
			$competition = un_seo_url( sanitize_text_field( wp_unslash( $request['competition'] ) ) );
			$competition = get_competition( $competition, 'name' );
			if ( $competition ) {
				$match_args['competition_id'] = $competition->id;
				$matches                      = $racketmanager->get_matches( $match_args );
			}
		} elseif ( isset( $request['event'] ) ) {
			$event = un_seo_url( sanitize_text_field( wp_unslash( $request['event'] ) ) );
			$event = get_event( $event, 'name' );
			if ( $event ) {
				$matches = $event->get_matches( $match_args );
			}
		} elseif ( isset( $request['league'] ) ) {
			$league = un_seo_url( sanitize_text_field( wp_unslash( $request['league'] ) ) );
			$league = get_league( $league, 'name' );
			if ( $league ) {
				$matches = $league->get_matches( $match_args );
			}
		} else {
			return new WP_Error( 'rest_invalid_param', esc_html__( 'The matches grouping is missing', 'racketmanager' ), array( 'status' => 400 ) );
		}
		$data = array();
		foreach ( $matches as $match ) {
			$itemdata = $this->prepare_item_for_response( $match, $request );
			$data[]   = $this->prepare_response_for_collection( $itemdata );
		}
		return new WP_REST_Response( $data, 200 );
	}
	/**
	 * Prepare the item for the REST response
	 *
	 * @param object          $match representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response( $match, $request ) {
		$json_result             = new \stdClass();
		$json_result->league     = str_replace( '"', '', $match->league->title );
		$json_result->home_team  = str_replace( '"', '', $match->teams['home']->title );
		$json_result->away_team  = str_replace( '"', '', $match->teams['away']->title );
		$json_result->match_date = substr( $match->date, 0, 10 );
		$json_result->match_time = $match->start_time;
		if ( $match->winner_id ) {
			$json_result->score = str_replace( '"', '', $match->score );
		}
		return $json_result;
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
			'search'   => array(
				'description'       => 'Limit results to those matching a string.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}
}
