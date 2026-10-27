<?php
/**
 * Handles registering Racketmanager custom REST endpoints.
 *
 * Class Rest_Resources
 *
 * @package Racketmanager
 */

declare( strict_types=1 );

namespace Racketmanager\Rest;

use LogicException;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event as Competition_Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Stripe_Settings;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Util\Util_Lookup;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class to implement the Rest_Resources object
 */
class Rest_Resources extends WP_REST_Controller {
    /**
     * Version
     *
     * @var string|int
     */
    public string|int $version;
    /**
     * Namespace
     *
     * @var string
     */
    public $namespace;
    /**
     * Plugin instance
     *
     * @var RacketManager
     */
    private RacketManager $racketmanager;
    private ?Competition_Service $competition_service;
    private ?Club_Service $club_service;
    private ?Season_Service $season_service;
    private Rest_Controller_Adapter $adapter;
    private ?League_Query_Service $league_query_service;
    private ?Event_Query_Service $event_query_service;
    private ?League_Repository_Interface $league_repository;
    private ?Event_Repository_Interface $event_repository;
    private ?Competition_Repository_Interface $competition_repository;

    /**
     * Constructor
     *
     * @param RacketManager                $plugin_instance
     * @param Competition_Service|null     $competition_service
     * @param Club_Service|null            $club_service
     * @param Season_Service|null          $season_service
     * @param Rest_Controller_Adapter|null $adapter
     * @param League_Query_Service|null    $league_query_service
     * @param Event_Query_Service|null     $event_query_service
     * @param League_Repository_Interface|null $league_repository
     * @param Event_Repository_Interface|null  $event_repository
     * @return void
     */
    public function __construct(
        $plugin_instance,
        ?Competition_Service $competition_service = null,
        ?Club_Service $club_service = null,
        ?Season_Service $season_service = null,
        ?Rest_Controller_Adapter $adapter = null,
        ?League_Query_Service $league_query_service = null,
        ?Event_Query_Service $event_query_service = null,
        ?League_Repository_Interface $league_repository = null,
        ?Event_Repository_Interface $event_repository = null
    ) {
        $this->racketmanager = $plugin_instance;
        $c                   = $this->racketmanager->container ?? null;
        if ( $c ) {
            $this->competition_service  = $competition_service ?? ( method_exists( $c, 'get' ) ? $c->get( 'competition_service' ) : null );
            $this->club_service         = $club_service ?? ( method_exists( $c, 'get' ) ? $c->get( 'club_service' ) : null );
            $this->season_service       = $season_service ?? ( method_exists( $c, 'get' ) ? $c->get( 'season_service' ) : null );
            $this->adapter              = $adapter ?? ( ( method_exists( $c, 'has' ) && $c->has( 'rest_controller_adapter' ) ) ? $c->get( 'rest_controller_adapter' ) : new Rest_Controller_Adapter( ( method_exists( $c, 'has' ) && $c->has( 'league_repository' ) ) ? $c->get( 'league_repository' ) : null ) );
            $this->league_query_service = $league_query_service ?? ( ( method_exists( $c, 'has' ) && $c->has( 'league_query_service' ) ) ? $c->get( 'league_query_service' ) : null );
            $this->event_query_service  = $event_query_service ?? ( ( method_exists( $c, 'has' ) && $c->has( 'event_query_service' ) ) ? $c->get( 'event_query_service' ) : null );
            $this->league_repository    = $league_repository ?? ( ( method_exists( $c, 'has' ) && $c->has( 'league_repository' ) ) ? $c->get( 'league_repository' ) : null );
            $this->event_repository     = $event_repository ?? ( ( method_exists( $c, 'has' ) && $c->has( 'event_repository' ) ) ? $c->get( 'event_repository' ) : null );
            $this->competition_repository = ( method_exists( $c, 'has' ) && $c->has( 'competition_repository' ) ) ? $c->get( 'competition_repository' ) : null;
        } else {
            $this->competition_service  = $competition_service;
            $this->club_service         = $club_service;
            $this->season_service       = $season_service;
            $this->adapter              = $adapter ?? new Rest_Controller_Adapter();
            $this->league_query_service = $league_query_service;
            $this->event_query_service  = $event_query_service;
            $this->league_repository    = $league_repository;
            $this->event_repository     = $event_repository;
            $this->competition_repository = null;
        }

        $this->version   = '1';
        $this->namespace = 'racketmanager/v' . $this->version;
    }

    private function league_query(): League_Query_Service {
        if ( ! $this->league_query_service instanceof League_Query_Service ) {
            throw new LogicException( 'League_Query_Service is required by Rest_Resources but was not configured.' );
        }
        return $this->league_query_service;
    }

    private function event_query(): Event_Query_Service {
        if ( ! $this->event_query_service instanceof Event_Query_Service ) {
            throw new LogicException( 'Event_Query_Service is required by Rest_Resources but was not configured.' );
        }
        return $this->event_query_service;
    }

    /**
     * Resolve a league from an id, name, League instance or raw row object.
     *
     * Mirrors the legacy `Racketmanager\get_league()` helper (without the implicit global fallback).
     *
     * @param object|int|string|null $league league id, name, League or row object.
     *
     * @return League|null
     */
    private function resolve_league( object|int|string|null $league ): ?League {
        if ( $league instanceof League ) {
            return $league;
        }
        if ( is_object( $league ) ) {
            $event = null;
            if ( isset( $league->event ) && is_object( $league->event ) ) {
                $event = $league->event instanceof Competition_Event ? $league->event : new Competition_Event( $league->event );
            } elseif ( ! empty( $league->event_id ) ) {
                $event = $this->event_repository?->find_by_id( (int) $league->event_id );
            }
            $resolved = League_Factory::from_object( $league, $event );
            if ( $resolved && $this->league_query_service instanceof League_Query_Service ) {
                $this->league_query_service->set_season( $resolved, false, true );
            }
            return $resolved;
        }
        if ( empty( $league ) ) {
            return null;
        }
        if ( is_numeric( $league ) ) {
            return $this->league_repository?->find_by_id( (int) $league );
        }
        return $this->league_repository?->find_by_name( (string) $league );
    }

    /**
     * Resolve an event from an id, name, Event instance or raw row object.
     *
     * Mirrors the legacy `Racketmanager\get_event()` helper (without the implicit global fallback).
     *
     * @param object|int|string|null $event       event id, name, Event or row object.
     * @param string                 $search_term 'id' or 'name'.
     *
     * @return Competition_Event|null
     */
    private function resolve_event( object|int|string|null $event, string $search_term = 'id' ): ?Competition_Event {
        if ( $event instanceof Competition_Event ) {
            return $event;
        }
        if ( is_object( $event ) ) {
            $competition = null;
            if ( ! empty( $event->competition_id ) ) {
                $competition = $this->competition_repository?->find_by_id( (int) $event->competition_id );
            }
            return new Competition_Event( $event, $competition instanceof Competition ? $competition : null );
        }
        if ( empty( $event ) ) {
            return null;
        }
        if ( 'name' === $search_term ) {
            return $this->event_repository?->find_by_name( (string) $event );
        }
        return $this->event_repository?->find_by_id( $event );
    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes(): void {
        $base      = 'stripe';
        register_rest_route(
            $this->namespace,
            '/' . $base,
            array(
                array(
                    'methods'             => array( WP_REST_Server::CREATABLE, WP_REST_Server::READABLE ),
                    'callback'            => array( $this, 'stripe_event' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                ),
            )
        );
        $base      = 'standings';
        register_rest_route(
            $this->namespace,
            '/' . $base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_standings' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => array(
                        'club'        => $this->get_arg( 'club' ),
                        'competition' => $this->get_arg( 'competition' ),
                        'event'       => $this->get_arg( 'event' ),
                        'league'      => $this->get_arg( 'league' ),
                        'season'      => $this->get_arg( 'season' ),
                    ),
                ),
            )
        );
        $base = 'fixtures';
        register_rest_route(
            $this->namespace,
            '/' . $base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_matches' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => array(
                        'club'        => $this->get_arg( 'club' ),
                        'competition' => $this->get_arg( 'competition' ),
                        'event'       => $this->get_arg( 'event' ),
                        'league'      => $this->get_arg( 'league' ),
                        'season'      => $this->get_arg( 'season' ),
                        'home'        => $this->get_arg( 'home' ),
                    ),
                ),
            )
        );
        $base = 'results';
        register_rest_route(
            $this->namespace,
            '/' . $base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_matches' ),
                    'permission_callback' => array( $this, 'get_item_permissions_check' ),
                    'args'                => array(
                        'club'        => $this->get_arg( 'club' ),
                        'competition' => $this->get_arg( 'competition' ),
                        'event'       => $this->get_arg( 'event' ),
                        'league'      => $this->get_arg( 'league' ),
                        'season'      => $this->get_arg( 'season' ),
                        'days'        => $this->get_arg( 'days' ),
                    ),
                ),
            )
        );
        register_rest_route(
            $this->namespace,
            '/' . $base . '/schema',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_public_item_schema' ),
                'permission_callback' => array( $this, 'get_item_permissions_check' ),
            )
        );
    }
    /**
     * Check if a given request has access to get items.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return bool|WP_Error
     */
    public function get_item_permissions_check( $request ): bool|WP_Error {
        return $this->adapter->check_resource_permissions( $request, 'public' );
    }
    /**
     * Get a collection of standings
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_standings( WP_REST_Request $request ): WP_Error|WP_REST_Response {
        $query   = $this->adapter->prepare_standings_query_from_request( $request );
        $season  = $query->season;
        $club    = $query->club;
        $events  = array();
        $league  = null;
        $club_id = null;
        $club_obj = null;
        if ( $club ) {
            $club_name = Seo_Slug::decode( $club );
            try {
                $club_obj = $this->club_service?->get_club_by_shortcode( $club_name );
                $club_id  = $club_obj?->id;
            } catch ( Club_Not_Found_Exception $e ) {
                return new WP_Error( 'rest_invalid_param', esc_html( $e->getMessage() ), array( 'status' => 400 ) );
            }
        }
        $is_league        = false;
        $competition_name = $query->competition;
        $event_name       = $query->event;
        $league_name      = $query->league;
        $validator        = new Validator();
        if ( $competition_name ) {
            $competition_name = Seo_Slug::decode( $competition_name );
            try {
                $events = $this->competition_service?->get_events_for_competition( $competition_name, null !== $season && '' !== $season ? (int) $season : null ) ?? array();
            } catch ( Competition_Not_Found_Exception $e ) {
                $validator->error      = true;
                $validator->err_msgs[] = $e;
            }
        } elseif ( $event_name ) {
            $event_name = Seo_Slug::decode( $event_name );
            $validator  = $validator->event( $event_name );
            if ( empty( $validator->error ) ) {
                $event = $this->resolve_event( $event_name, 'name' );
                if ( $event ) {
                    $validator = $validator->season_set( $season, $event->get_seasons() );
                    if ( empty( $validator->error ) ) {
                        $events[] = $event;
                    }
                }
            }
        } elseif ( $league_name ) {
            $league = Seo_Slug::decode( $league_name );
            $league = $this->resolve_league( $league );
            if ( $league ) {
                $is_league = true;
                $events[]  = $league->event;
            }
        } else {
            $validator->error = true;
            $validator->err_msgs[] = __( 'The standings grouping is missing', 'racketmanager' );
        }
        if ( ! empty( $validator->error ) ) {
            $return = $validator->get_details();
            $msg    = $return->err_msgs[0];
            return new WP_Error( 'rest_invalid_param', esc_html( (string) $msg ), array( 'status' => 400 ) );
        }
        $data = array();
        foreach ( $events as $event ) {
            $event = is_object( $event ) ? $event : $this->resolve_event( $event );
            if ( $event ) {
                if ( empty( $is_league ) ) {
                    $leagues = $this->event_query()->get_leagues( $event );
                } else {
                    $leagues = array( $league );
                }
                foreach ( $leagues as $league_item ) {
                    $league_item = $this->resolve_league( $league_item->id );
                    if ( null === $league_item ) {
                        continue;
                    }
                    $teams  = $this->league_query()->get_league_teams( $league_item,
                        array(
                            'season' => $season,
                            'club'   => $club_id,
                        )
                    );
                    $i      = 0;
                    foreach ( $teams as $team ) {
                        $team->league = $league_item->title;
                        $teams[ $i ]  = $team;
                        ++$i;
                    }
                    if ( $teams ) {
                        foreach ( $teams as $team ) {
                            $data[] = $this->adapter->build_standing_response( $team, ! empty( $club_obj ) ? $club_obj : null );
                        }
                    }
                }
            }
        }

        return $this->adapter->prepare_standings_response( $data );
    }
    /**
     * Get a collection of matches
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_matches( WP_REST_Request $request ): WP_Error|WP_REST_Response {
        $this->adapter->clear_title_caches();
        $matches    = null;
        $match_args = array();
        $query      = $this->adapter->prepare_matches_query_from_request( $request );
        $season     = $query->season;
        $club       = $query->club;
        $home       = $query->home;
        if ( $club ) {
            $club_name = Seo_Slug::decode( $club );
            try {
                $club_obj           = $this->club_service?->get_club_by_shortcode( $club_name );
                $match_args['club'] = $club_obj?->id;
                if ( $home ) {
                    $match_args['home_club'] = $club_obj?->id;
                }
            } catch ( Club_Not_Found_Exception $e ) {
                return new WP_Error( 'rest_invalid_param', esc_html( $e->getMessage() ), array( 'status' => 400 ) );
            }
        }
        $match_args['season'] = $season;
        if ( null !== $query->days ) {
            $match_args['time']    = 'latest';
            $match_args['days']    = $query->days;
            $match_args['history'] = $query->days;
        }
        $competition_name = $query->competition;
        $event_name       = $query->event;
        $league_name      = $query->league;
        $validator        = new Validator();
        if ( $competition_name ) {
            $competition_name = Seo_Slug::decode( $competition_name );
            $validator        = $validator->competition( $competition_name );
            if ( empty( $validator->error ) ) {
                $competition = $this->competition_service?->get_competition( $competition_name, 'name' );
                if ( $competition ) {
                    $validator = $validator->season_set( $season, $competition->get_seasons() );
                    if ( empty( $validator->error ) ) {
                        $match_args['competition_id'] = $competition->id;
                        $matches                      = $this->racketmanager->get_matches( $match_args );
                    }
                } else {
                    $validator->error = true;
                    $validator->err_msgs[] = __( 'Competition not found', 'racketmanager' );
                }
            }
        } elseif ( $event_name ) {
            $event_name = Seo_Slug::decode( $event_name );
            $validator  = $validator->event( $event_name );
            if ( empty( $validator->error ) ) {
                $event = $this->resolve_event( $event_name, 'name' );
                if ( $event ) {
                    $validator = $validator->season_set( $season, $event->get_seasons() );
                    if ( empty( $validator->error ) ) {
                        $matches = $this->event_query()->get_matches( $event, $match_args );
                    }
                } else {
                    $validator->error = true;
                    $validator->err_msgs[] = __( 'Event not found', 'racketmanager' );
                }
            }
        } elseif ( $league_name ) {
            $league = Seo_Slug::decode( $league_name );
            $league = $this->resolve_league( $league );
            if ( $league ) {
                $matches = $this->league_query()->get_matches( $league, $match_args );
            } else {
                $validator->error = true;
                $validator->err_msgs[] = __( 'League not found', 'racketmanager' );
            }
        } else {
            $validator->error = true;
            $validator->err_msgs[] = __( 'The matches grouping is missing', 'racketmanager' );
        }
        if ( ! empty( $validator->error ) ) {
            $return = $validator->get_details();
            $msg    = $return->err_msgs[0];
            return new WP_Error( 'rest_invalid_param', esc_html( (string) $msg ), array( 'status' => 400 ) );
        }
        $data = array();
        if ( is_array( $matches ) ) {
            foreach ( $matches as $match ) {
                $data[] = $this->adapter->build_match_response( $match );
            }
        }
        return $this->adapter->prepare_matches_response( $data );
    }
    /**
     * Prepare the match for the REST response
     *
     * @param object $match representation of the item.
     * @return object
     */
    public function prepare_match_for_response( object $match ): object {
        return $this->adapter->prepare_match_for_response( $match );
    }
    /**
     * Get item schema for results / matches
     *
     * @return array
     */
    public function get_item_schema(): array {
        return $this->adapter->get_item_schema();
    }
    /**
     * Get public item schema
     *
     * @return array
     */
    public function get_public_item_schema(): array {
        return $this->adapter->get_item_schema();
    }

    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function get_collection_params(): array {
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
    /**
     * Get argument details function
     *
     * @param string $type argument name.
     * @return array
     */
    private function get_arg(string $type ): array {
        return match ($type) {
            'club' => array(
                'description' => __('Club name', 'racketmanager'),
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => array( $this, 'data_arg_sanitize_callback' ),
                'enum' => $this->get_clubs(),
                'validate_callback' => array( $this, 'string_arg_validate_callback' ),
            ),
            'competition' => array(
                'description' => __('Competition name', 'racketmanager'),
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => array($this, 'data_arg_sanitize_callback'),
                'enum' => $this->get_competitions(),
                'validate_callback' => array($this, 'string_arg_validate_callback'),
            ),
            'event' => array(
                'description' => __('Event name', 'racketmanager'),
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => array($this, 'data_arg_sanitize_callback'),
                'enum' => $this->get_events(),
                'validate_callback' => array($this, 'string_arg_validate_callback'),
            ),
            'league' => array(
                'description' => __('League name', 'racketmanager'),
                'type' => 'string',
                'required' => false,
                'sanitize_callback' => array($this, 'data_arg_sanitize_callback'),
                'enum' => $this->get_leagues(),
                'validate_callback' => array($this, 'string_arg_validate_callback'),
            ),
            'season' => array(
                'description' => __('Season', 'racketmanager'),
                'type' => 'integer',
                'required' => true,
                'sanitize_callback' => array($this, 'data_arg_sanitize_callback'),
                'enum' => $this->get_seasons(),
                'validate_callback' => array($this, 'int_arg_validate_callback'),
            ),
            'days' => array(
                'description' => __('Number of days to look for results', 'racketmanager'),
                'type' => 'integer',
                'required' => false,
                'default' => 7,
            ),
            default => array(),
        };
    }
    /**
     * Sanitize a request argument based on details registered to the route.
     *
     * @param  mixed           $value   Value of the 'filter' argument.
     * @return string
     */
    public function data_arg_sanitize_callback(mixed $value): string {
        // It is as simple as returning the sanitized value.
        return sanitize_text_field( $value );
    }

    /**
     * Validate string argument function
     *
     * @param mixed $value value to check.
     * @param WP_REST_Request $request request object.
     * @param string $param parameter value.
     * @return true|WP_Error
     */
    public function string_arg_validate_callback( mixed $value, WP_REST_Request $request, string $param ): true|WP_Error {
        // If the argument is not a string, return an error.
        if ( ! is_string( $value ) ) {
            return new WP_Error( 'rest_invalid_param', esc_html__( 'The argument must be a string.', 'racketmanager' ), array( 'status' => 400 ) );
        }

        // Get the registered attributes for this endpoint request.
        $attributes = $request->get_attributes();

        // Grab the filter param schema.
        $args = $attributes['args'][ $param ];

        // If the param is not a value in our enum, then we should return an error as well.
        if ( ! in_array( $value, $args['enum'], true ) ) {
            /* translators: %1$s: value passed */
            return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not valid', 'racketmanager' ), $param ), array( 'status' => 400 ) );
        }
        return true;
    }

    /**
     * Validate integer argument function
     *
     * @param int|mixed $value value to check.
     * @param WP_REST_Request $request request object.
     * @param mixed $param parameter value.
     * @return true|WP_Error|null
     */
    public function int_arg_validate_callback( mixed $value, WP_REST_Request $request, mixed $param ): true|WP_Error|null {
        // If the argument is not an integer, return an error.
        if ( ! is_numeric( $value ) ) {
            return new WP_Error( 'rest_invalid_param', esc_html__( 'The argument must be an integer.', 'racketmanager' ), array( 'status' => 400 ) );
        }

        // Get the registered attributes for this endpoint request.
        $attributes = $request->get_attributes();

        // Grab the filter param schema.
        $args = $attributes['args'][ $param ];

        // If the param is not a value in our enum, then we should return an error as well.
        if ( ! in_array( $value, $args['enum'], true ) ) {
            /* translators: %1$s: value passed */
            return new WP_Error( 'rest_invalid_param', sprintf( __( '%1$s is not valid', 'racketmanager' ), $param ), array( 'status' => 400 ) );
        }
        return true;
    }
    /**
     * Get clubs function
     *
     * @return array
     */
    private function get_clubs(): array {
        $clubs = $this->club_service?->get_clubs() ?? array();
        if ( ! is_array( $clubs ) ) {
            return array();
        }
        foreach ( $clubs as $i => $club ) {
            $clubs[ $i ] = is_object( $club ) ? Seo_Slug::encode( (string) ( $club->shortcode ?? '' ) ) : Seo_Slug::encode( (string) $club );
        }
        return $clubs;
    }
    /**
     * Get competitions function
     *
     * @return array
     */
    private function get_competitions(): array {
        $competitions = $this->competition_service?->get_all() ?? array();
        if ( ! is_array( $competitions ) ) {
            return array();
        }
        foreach ( $competitions as $i => $competition ) {
            $competitions[ $i ] = is_object( $competition ) ? Seo_Slug::encode( (string) ( $competition->name ?? '' ) ) : Seo_Slug::encode( (string) $competition );
        }
        return $competitions;
    }
    /**
     * Get events function
     *
     * @return array
     */
    private function get_events(): array {
        $events = ( $this->racketmanager && method_exists( $this->racketmanager, 'get_events' ) ) ? $this->racketmanager->get_events() : array();
        if ( ! is_array( $events ) ) {
            return array();
        }
        foreach ( $events as $i => $event ) {
            $events[ $i ] = is_object( $event ) ? Seo_Slug::encode( (string) ( $event->name ?? '' ) ) : Seo_Slug::encode( (string) $event );
        }
        return $events;
    }
    /**
     * Get leagues function
     *
     * @return array
     */
    private function get_leagues(): array {
        $leagues = ( $this->racketmanager && method_exists( $this->racketmanager, 'get_leagues' ) ) ? $this->racketmanager->get_leagues() : array();
        if ( ! is_array( $leagues ) ) {
            return array();
        }
        foreach ( $leagues as $i => $league ) {
            $leagues[ $i ] = is_object( $league ) ? Seo_Slug::encode( (string) ( $league->title ?? '' ) ) : Seo_Slug::encode( (string) $league );
        }
        return $leagues;
    }
    /**
     * Get seasons function
     *
     * @return array
     */
    private function get_seasons(): array {
        $seasons = $this->season_service?->get_all_seasons() ?? array();
        if ( ! is_array( $seasons ) ) {
            return array();
        }
        foreach ( $seasons as $i => $season ) {
            $name = is_object( $season ) ? ( method_exists( $season, 'get_name' ) ? $season->get_name() : ( $season->name ?? '' ) ) : (string) $season;
            $seasons[ $i ] = Seo_Slug::encode( (string) $name );
        }
        return $seasons;
    }
    /**
     * Process stripe callback
     *
     * @param WP_REST_Request|null $request
     * @return WP_Error|WP_REST_Response
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function stripe_event( ?WP_REST_Request $request = null ): WP_Error|WP_REST_Response {
        $data           = null;
        $status         = 200;
        $stripe_details = new Stripe_Settings( $this->racketmanager->get_options( 'billing' ) );
        $finance_service = $this->racketmanager->container?->get( 'finance_service' );
        Stripe::setApiKey( $stripe_details->api_secret_key );
        $command        = $this->adapter->prepare_stripe_webhook_command_from_request( $request ?? new WP_REST_Request() );
        $payload        = $command->payload;
        try {
            $decoded = ! empty( $payload ) ? json_decode( $payload, true ) : null;
            if ( ! is_array( $decoded ) ) {
                throw new UnexpectedValueException( 'Invalid payload' );
            }
            $event = Event::constructFrom( $decoded );
        } catch( UnexpectedValueException ) {
            echo '⚠️  Webhook error while parsing basic request.';
            $status = 400;
            return new WP_REST_Response( $data, $status );
        }
        if ( $stripe_details->api_endpoint_key ) {
            // Only verify the event if there is an endpoint secret defined
            // Otherwise use the basic decoded event
            $sig_header = $command->signature_header ?? ( $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '' );
            try {
                $event = Webhook::constructEvent(
                    $payload, $sig_header, $stripe_details->api_endpoint_key
                );
            } catch( SignatureVerificationException ) {
                // Invalid signature
                echo '⚠️  Webhook error while validating signature.';
                $status = 400;
                return new WP_REST_Response( $data, $status );
            }
        }
        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $payment_intent = $event->data->object; // contains a \Stripe_Settings\PaymentIntent
                if ( $finance_service ) {
                    $stripe_details->update_payment( $finance_service, $payment_intent->id );
                }
                break;
            case 'payment_intent.processing':
                $payment_intent = $event->data->object; // contains a \Stripe_Settings\PaymentIntent
                if ( $finance_service ) {
                    $stripe_details->update_payment( $finance_service, $payment_intent->id, 'pending' );
                }
                break;
            case 'payment_intent.payment_failed':
                $payment_intent = $event->data->object; // contains a \Stripe_Settings\PaymentIntent
                if ( $finance_service ) {
                    $stripe_details->update_payment( $finance_service, $payment_intent->id, 'failed' );
                }
                break;
            case 'payment_method.attached':
            case 'payment_intent.created':
            case 'charge.succeeded':
            case 'charge.updated':
                break;
            default:
                // Unexpected event type
                error_log('Received unknown event type');
                error_log( $event->type );
                $status = 400;
        }
        return new WP_REST_Response( $data, $status );
    }
}
