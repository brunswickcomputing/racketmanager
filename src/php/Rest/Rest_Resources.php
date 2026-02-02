<?php
/**
 * Handles registering Racketmanager custom REST endpoints.
 *
 * Class Rest_Resources
 *
 * @package Racketmanager
 */

namespace Racketmanager\Rest;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Stripe_Settings;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Util\Util_Lookup;
use stdClass;
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
use function Racketmanager\get_competition;
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
    /**
     * @var callable|object
     */
    private Competition_Service $competition_service;
    private Club_Service $club_service;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct( $plugin_instance ) {
        $this->racketmanager        = $plugin_instance;
        $c                          = $this->racketmanager->container;
        $this->competition_service  = $c->get( 'competition_service' );
        $this->club_service         = $c->get( 'club_service' );

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
                    'methods'             => array( WP_REST_Server::CREATABLE, WP_REST_Server::READABLE),
                    'callback'            => array( $this, 'stripe_event' ),
                    'permission_callback' => '__return_true',
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
                    'permission_callback' => '__return_true',
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
                    'permission_callback' => '__return_true',
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
                    'permission_callback' => '__return_true',
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
                'permission_callback' => '__return_true',
            )
        );
    }
    /**
     * Get a collection of standings
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_standings( WP_REST_Request $request ): WP_Error|WP_REST_Response {
        $season  = $request['season'] ?? null;
        $club    = $request['club'] ?? null;
        $events  = array();
        $league  = null;
        $club_id = null;
        if ( $club ) {
            $club_name = un_seo_url( $request['club'] );
            try {
                $club    = $this->club_service->get_club_by_shortcode( $club_name );
                $club_id = $club?->id;
            } catch ( Club_Not_Found_Exception $e ) {
                return new WP_Error( 'rest_invalid_param', esc_html( $e->getMessage() ), array( 'status' => 400 ) );
            }
        }
        $is_league = false;
        $competition_name = isset( $request['competition'] ) ? sanitize_text_field( wp_unslash( $request['competition'] ) ) : null;
        $event_name       = isset( $request['event'] ) ? sanitize_text_field( wp_unslash( $request['event'] ) ) : null;
        $league_name      = isset( $request['league'] ) ? sanitize_text_field( $request['league'] ) : null;
        $validator        = new Validator();
        if ( $competition_name ) {
            $competition_name = un_seo_url( $competition_name );
            try {
                $events = $this->competition_service->get_events_for_competition( $competition_name, $season );
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
            return new WP_Error( 'rest_invalid_param', esc_html( $msg ), array( 'status' => 400 ) );
        }
        $data = array();
        foreach ( $events as $event ) {
            $event = get_event( $event );
            if ( $event ) {
                if ( empty( $is_league ) ) {
                    $leagues = $event->get_leagues();
                } else {
                    $leagues[] = $league;
                }
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
                            $json_result = new stdClass();
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
    public function get_matches( WP_REST_Request $request ): WP_Error|WP_REST_Response {
        $matches    = null;
        $match_args = array();
        $season     = $request['season'] ?? null;
        $club       = $request['club'] ?? null;
        $home       = $request['home'] ?? null;
        if ( $club ) {
            $club_name = un_seo_url( $request['club'] );
            try {
                $club               = $this->club_service->get_club_by_shortcode( $club_name );
                $match_args['club'] = $club->id;
                if ( $home ) {
                    $match_args['home_club'] = $club->id;
                }
            } catch ( Club_Not_Found_Exception $e ) {
                return new WP_Error( 'rest_invalid_param', esc_html( $e->getMessage() ), array( 'status' => 400 ) );
            }
        }
        $match_args['season'] = $season;
        if ( isset( $request['days'] ) ) {
            $match_args['time']    = 'latest';
            $match_args['days']    = $request['days'];
            $match_args['history'] = $request['days'];
        }
        $competition_name = isset( $request['competition'] ) ? sanitize_text_field( wp_unslash( $request['competition'] ) ) : null;
        $event_name       = isset( $request['event'] ) ? sanitize_text_field( wp_unslash( $request['event'] ) ) : null;
        $league_name      = isset( $request['league'] ) ? sanitize_text_field( $request['league'] ) : null;
        $validator        = new Validator();
        if ( $competition_name ) {
            $competition_name = un_seo_url( $competition_name );
            $validator        = $validator->competition( $competition_name );
            if ( empty( $validator->error ) ) {
                $competition = get_competition( $competition_name, 'name' );
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
                        $matches = $event->get_matches( $match_args );
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
                $matches = $league->get_matches( $match_args );
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
            return new WP_Error( 'rest_invalid_param', esc_html( $msg ), array( 'status' => 400 ) );
        }
        $data = array();
        foreach ( $matches as $match ) {
            $item_data = $this->prepare_match_for_response( $match );
            /** @noinspection PhpParamsInspection */
            $data[]    = $this->prepare_response_for_collection( $item_data );
        }
        return new WP_REST_Response( $data, 200 );
    }
    /**
     * Prepare the match for the REST response
     *
     * @param object $match representation of the item.
     * @return object
     */
    public function prepare_match_for_response(object $match ): object {
        $json_result             = new stdClass();
        $json_result->league     = str_replace( '"', '', $match->league->title );
        $json_result->home_team  = str_replace( '"', '', $match->teams['home']->title );
        $json_result->away_team  = str_replace( '"', '', $match->teams['away']->title );
        $json_result->match_date = substr( $match->date, 0, 10 );
        $json_result->match_time = $match->start_time;
        if ( $match->winner_id ) {
            $json_result->score  = str_replace( '"', '', $match->score );
            if ( ! is_null( $match->status ) ) {
                $json_result->status = Util_Lookup::get_match_status( $match->status );
            } else {
                $json_result->status = $match->status;
            }
        }
        return $json_result;
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
        $clubs = $this->club_service->get_clubs();
        foreach ( $clubs as $i => $club ) {
            $clubs[ $i ] = seo_url( $club->shortcode );
        }
        return $clubs;
    }
    /**
     * Get competitions function
     *
     * @return array
     */
    private function get_competitions(): array {
        $competitions = $this->competition_service->get_all();
        foreach ( $competitions as $i => $competition ) {
            $competitions[ $i ] = seo_url( $competition->name );
        }
        return $competitions;
    }
    /**
     * Get events function
     *
     * @return array
     */
    private function get_events(): array {
        $events = $this->racketmanager->get_events();
        foreach ( $events as $i => $event ) {
            $events[ $i ] = seo_url( $event->name );
        }
        return $events;
    }
    /**
     * Get leagues function
     *
     * @return array
     */
    private function get_leagues(): array {
        $leagues = $this->racketmanager->get_leagues();
        foreach ( $leagues as $i => $league ) {
            $leagues[ $i ] = seo_url( $league->title );
        }
        return $leagues;
    }
    /**
     * Get seasons function
     *
     * @return array
     */
    private function get_seasons(): array {
        $seasons = $this->racketmanager->get_seasons();
        foreach ( $seasons as $i => $season ) {
            $seasons[ $i ] = seo_url( $season->name );
        }
        return $seasons;
    }
    /**
     * Process stripe callback
     *
     * @return WP_Error|WP_REST_Response
     * @noinspection PhpPossiblePolymorphicInvocationInspection
     */
    public function stripe_event(): WP_Error|WP_REST_Response {
        $data           = null;
        $status         = 200;
        $stripe_details = new Stripe_Settings( $this->racketmanager );
        Stripe::setApiKey( $stripe_details->api_secret_key );
        $payload = @file_get_contents('php://input');
        try {
            $event = Event::constructFrom(
                json_decode($payload, true)
            );
        } catch( UnexpectedValueException ) {
            echo '⚠️  Webhook error while parsing basic request.';
            $status = 400;
            return new WP_REST_Response( $data, $status );
        }
        if ( $stripe_details->api_endpoint_key ) {
            // Only verify the event if there is an endpoint secret defined
            // Otherwise use the basic decoded event
            $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
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
                $stripe_details->update_payment( $payment_intent->id);
                // Then define and call a method to handle the successful payment intent.
                // handlePaymentIntentSucceeded($payment_intent);
                break;
            case 'payment_intent.processing':
                $payment_intent = $event->data->object; // contains a \Stripe_Settings\PaymentIntent
                $stripe_details->update_payment( $payment_intent->id, 'pending' );
                break;
            case 'payment_intent.payment_failed':
                $payment_intent = $event->data->object; // contains a \Stripe_Settings\PaymentIntent
                $stripe_details->update_payment( $payment_intent->id, 'failed' );
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
