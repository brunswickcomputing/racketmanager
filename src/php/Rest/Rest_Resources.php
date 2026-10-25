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

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Rest\Rest_Controller_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
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
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\seo_url;
use function Racketmanager\un_seo_url;

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

    /**
     * Constructor
     *
     * @param RacketManager                $plugin_instance
     * @param Competition_Service|null     $competition_service
     * @param Club_Service|null            $club_service
     * @param Season_Service|null          $season_service
     * @param Rest_Controller_Adapter|null $adapter
     * @return void
     */
    public function __construct(
        $plugin_instance,
        ?Competition_Service $competition_service = null,
        ?Club_Service $club_service = null,
        ?Season_Service $season_service = null,
        ?Rest_Controller_Adapter $adapter = null
    ) {
        $this->racketmanager = $plugin_instance;
        $c                   = $this->racketmanager->container ?? null;
        if ( $c ) {
            $this->competition_service = $competition_service ?? ( method_exists( $c, 'get' ) ? $c->get( 'competition_service' ) : null );
            $this->club_service        = $club_service ?? ( method_exists( $c, 'get' ) ? $c->get( 'club_service' ) : null );
            $this->season_service      = $season_service ?? ( method_exists( $c, 'get' ) ? $c->get( 'season_service' ) : null );
            $this->adapter             = $adapter ?? ( ( method_exists( $c, 'has' ) && $c->has( 'rest_controller_adapter' ) ) ? $c->get( 'rest_controller_adapter' ) : new Rest_Controller_Adapter() );
        } else {
            $this->competition_service = $competition_service;
            $this->club_service        = $club_service;
            $this->season_service      = $season_service;
            $this->adapter             = $adapter ?? new Rest_Controller_Adapter();
        }

        $this->version   = '1';
        $this->namespace = 'racketmanager/v' . $this->version;
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
            $club_name = un_seo_url( $club );
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
            $competition_name = un_seo_url( $competition_name );
            try {
                $events = $this->competition_service?->get_events_for_competition( $competition_name, null !== $season && '' !== $season ? (int) $season : null ) ?? array();
            } catch ( Competition_Not_Found_Exception $e ) {
                $validator->error      = true;
                $validator->err_msgs[] = $e;
            }
        } elseif ( $event_name ) {
            $event_name = un_seo_url( $event_name );
            $validator  = $validator->event( $event_name );
            if ( empty( $validator->error ) ) {
                $event = get_event( $event_name, 'name' );
                if ( $event ) {
                    $validator = $validator->season_set( $season, $event->get_seasons() );
                    if ( empty( $validator->error ) ) {
                        $events[] = $event;
                    }
                }
            }
        } elseif ( $league_name ) {
            $league = un_seo_url( $league_name );
            $league = get_league( $league );
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
            $event = is_object( $event ) ? $event : get_event( $event );
            if ( $event ) {
                if ( empty( $is_league ) ) {
                    $leagues = \Racketmanager\get_event_query_service()->get_leagues( $event );
                } else {
                    $leagues = array( $league );
                }
                foreach ( $leagues as $league_item ) {
                    $league_item = get_league( $league_item->id );
                    if ( null === $league_item ) {
                        continue;
                    }
                    $teams  = \Racketmanager\get_league_query_service()->get_league_teams( $league_item,
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
            $club_name = un_seo_url( $club );
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
            $competition_name = un_seo_url( $competition_name );
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
            $event_name = un_seo_url( $event_name );
            $validator  = $validator->event( $event_name );
            if ( empty( $validator->error ) ) {
                $event = get_event( $event_name, 'name' );
                if ( $event ) {
                    $validator = $validator->season_set( $season, $event->get_seasons() );
                    if ( empty( $validator->error ) ) {
                        $matches = \Racketmanager\get_event_query_service()->get_matches( $event, $match_args );
                    }
                } else {
                    $validator->error = true;
                    $validator->err_msgs[] = __( 'Event not found', 'racketmanager' );
                }
            }
        } elseif ( $league_name ) {
            $league = un_seo_url( $league_name );
            $league = get_league( $league );
            if ( $league ) {
                $matches = \Racketmanager\get_league_query_service()->get_matches( $league, $match_args );
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
            $clubs[ $i ] = is_object( $club ) ? seo_url( (string) ( $club->shortcode ?? '' ) ) : seo_url( (string) $club );
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
            $competitions[ $i ] = is_object( $competition ) ? seo_url( (string) ( $competition->name ?? '' ) ) : seo_url( (string) $competition );
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
            $events[ $i ] = is_object( $event ) ? seo_url( (string) ( $event->name ?? '' ) ) : seo_url( (string) $event );
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
            $leagues[ $i ] = is_object( $league ) ? seo_url( (string) ( $league->title ?? '' ) ) : seo_url( (string) $league );
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
            $seasons[ $i ] = seo_url( (string) $name );
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
