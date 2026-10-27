<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Rest;

use Racketmanager\Domain\DTO\Rest\Rest_Match_Response;
use Racketmanager\Domain\DTO\Rest\Rest_Matches_Query;
use Racketmanager\Domain\DTO\Rest\Rest_Standing_Response;
use Racketmanager\Domain\DTO\Rest\Rest_Standings_Query;
use Racketmanager\Domain\DTO\Rest\Rest_Stripe_Webhook_Command;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Export\DTO\Export_Criteria;
use Racketmanager\Services\Export\Formatters\Export_Formatter_Interface;
use Racketmanager\Util\Util_Lookup;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Driving adapter for WordPress REST API request/response translation,
 * permission verification, and stream delivery.
 */
class Rest_Controller_Adapter {

    /**
     * Memoized league titles keyed by league ID.
     *
     * @var array<string, string>
     */
    private array $league_title_cache = array();

    /**
     * Memoized team titles keyed by team ID.
     *
     * @var array<string, string>
     */
    private array $team_title_cache = array();

    /**
     * @param League_Repository_Interface|null $league_repository Used to resolve league titles from a league id when the match carries none.
     */
    public function __construct( private readonly ?League_Repository_Interface $league_repository = null ) {
    }

    /**
     * Clear memoized title caches.
     *
     * @return void
     */
    public function clear_title_caches(): void {
        $this->league_title_cache = array();
        $this->team_title_cache   = array();
    }

    /**
     * Parse and sanitize request parameters into typed Export_Criteria.
     *
     * @param WP_REST_Request $request
     * @return Export_Criteria
     */
    public function prepare_criteria_from_request( WP_REST_Request $request ): Export_Criteria {
        $criteria = new Export_Criteria();
        $criteria->league_id      = $request->get_param( 'league_id' ) ? (int) $request->get_param( 'league_id' ) : null;
        $criteria->season         = $request->get_param( 'season' ) ? sanitize_text_field( (string) $request->get_param( 'season' ) ) : null;
        $criteria->club_id        = $request->get_param( 'club_id' ) ? (int) $request->get_param( 'club_id' ) : null;
        $criteria->competition_id = $request->get_param( 'competition_id' ) ? (int) $request->get_param( 'competition_id' ) : null;
        $criteria->team_id        = $request->get_param( 'team_id' ) ? (int) $request->get_param( 'team_id' ) : null;
        $criteria->date_from      = $request->get_param( 'date_from' ) ? sanitize_text_field( (string) $request->get_param( 'date_from' ) ) : null;
        $criteria->date_to        = $request->get_param( 'date_to' ) ? sanitize_text_field( (string) $request->get_param( 'date_to' ) ) : null;
        $criteria->format         = $request->get_param( 'format' ) ? sanitize_text_field( (string) $request->get_param( 'format' ) ) : 'json';

        return $criteria;
    }

    /**
     * Parse and sanitize request parameters into typed Rest_Standings_Query.
     *
     * @param WP_REST_Request $request
     * @return Rest_Standings_Query
     */
    public function prepare_standings_query_from_request( WP_REST_Request $request ): Rest_Standings_Query {
        $club        = $request->get_param( 'club' ) ? sanitize_text_field( (string) $request->get_param( 'club' ) ) : null;
        $competition = $request->get_param( 'competition' ) ? sanitize_text_field( wp_unslash( (string) $request->get_param( 'competition' ) ) ) : null;
        $event       = $request->get_param( 'event' ) ? sanitize_text_field( wp_unslash( (string) $request->get_param( 'event' ) ) ) : null;
        $league      = $request->get_param( 'league' ) ? sanitize_text_field( (string) $request->get_param( 'league' ) ) : null;
        $season      = $request->get_param( 'season' ) ? sanitize_text_field( (string) $request->get_param( 'season' ) ) : null;

        return new Rest_Standings_Query(
            club: $club,
            competition: $competition,
            event: $event,
            league: $league,
            season: $season,
        );
    }

    /**
     * Parse and sanitize request parameters into typed Rest_Matches_Query.
     *
     * @param WP_REST_Request $request
     * @return Rest_Matches_Query
     */
    public function prepare_matches_query_from_request( WP_REST_Request $request ): Rest_Matches_Query {
        $club        = $request->get_param( 'club' ) ? sanitize_text_field( (string) $request->get_param( 'club' ) ) : null;
        $competition = $request->get_param( 'competition' ) ? sanitize_text_field( wp_unslash( (string) $request->get_param( 'competition' ) ) ) : null;
        $event       = $request->get_param( 'event' ) ? sanitize_text_field( wp_unslash( (string) $request->get_param( 'event' ) ) ) : null;
        $league      = $request->get_param( 'league' ) ? sanitize_text_field( (string) $request->get_param( 'league' ) ) : null;
        $season      = $request->get_param( 'season' ) ? sanitize_text_field( (string) $request->get_param( 'season' ) ) : null;
        $home_param  = $request->get_param( 'home' );
        $home        = null !== $home_param ? (bool) $home_param : null;
        $days_param  = $request->get_param( 'days' );
        $days        = null !== $days_param ? (int) $days_param : null;

        return new Rest_Matches_Query(
            club: $club,
            competition: $competition,
            event: $event,
            league: $league,
            season: $season,
            home: $home,
            days: $days,
        );
    }

    /**
     * Parse incoming payload and signature into typed Rest_Stripe_Webhook_Command.
     *
     * @param WP_REST_Request $request
     * @return Rest_Stripe_Webhook_Command
     */
    public function prepare_stripe_webhook_command_from_request( WP_REST_Request $request ): Rest_Stripe_Webhook_Command {
        $payload = $request->get_body();
        if ( empty( $payload ) ) {
            $raw     = @file_get_contents( 'php://input' );
            $payload = false !== $raw ? $raw : '';
        }
        $sig_header = $request->get_header( 'http_stripe_signature' )
            ?? ( $request->get_header( 'stripe_signature' )
            ?? ( $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null ) );

        return new Rest_Stripe_Webhook_Command(
            payload: $payload,
            signature_header: $sig_header ? (string) $sig_header : null,
        );
    }

    /**
     * Verify export permission for an incoming request.
     *
     * @param WP_REST_Request $request
     * @param string          $export_type
     * @return bool|WP_Error
     */
    public function check_export_permissions( WP_REST_Request $request, string $export_type = 'public' ): bool|WP_Error {
        // Public exports (calendar, fixtures, results) are readable by any client.
        if ( in_array( $export_type, array( 'calendar', 'fixtures', 'results', 'public' ), true ) ) {
            return true;
        }

        // Sensitive exports (report-results, admin reports) require edit_matches, edit_leagues, or manage_racketmanager.
        if ( 'report_results' === $export_type || 'report-results' === $export_type ) {
            if ( current_user_can( 'edit_matches' ) || current_user_can( 'edit_leagues' ) || current_user_can( 'manage_racketmanager' ) ) {
                return true;
            }

            $status = function_exists( 'is_user_logged_in' ) && is_user_logged_in() ? 403 : 401;

            return new WP_Error(
                'rest_forbidden',
                __( 'Sorry, you are not allowed to export match reports.', 'racketmanager' ),
                array( 'status' => $status )
            );
        }

        // Deny unknown export types by default.
        $status = function_exists( 'is_user_logged_in' ) && is_user_logged_in() ? 403 : 401;

        return new WP_Error(
            'rest_forbidden',
            __( 'Sorry, you are not allowed to access this export.', 'racketmanager' ),
            array( 'status' => $status )
        );
    }

    /**
     * Verify resource permission for general REST endpoints.
     *
     * @param WP_REST_Request $request
     * @param string          $resource
     * @return bool|WP_Error
     */
    public function check_resource_permissions( WP_REST_Request $request, string $resource = 'public' ): bool|WP_Error {
        if ( in_array( $resource, array( 'standings', 'fixtures', 'results', 'schema', 'stripe', 'public' ), true ) ) {
            return true;
        }

        if ( 'report_results' === $resource || 'report-results' === $resource ) {
            return $this->check_export_permissions( $request, 'report_results' );
        }

        $status = function_exists( 'is_user_logged_in' ) && is_user_logged_in() ? 403 : 401;

        return new WP_Error(
            'rest_forbidden',
            __( 'Sorry, you are not allowed to access this resource.', 'racketmanager' ),
            array( 'status' => $status )
        );
    }

    /**
     * Prepare a match object representation for REST response.
     *
     * @param object $match
     * @return object
     */
    public function prepare_match_for_response( object $match ): object {
        return $this->build_match_response( $match )->to_object();
    }

    /**
     * Build a typed response DTO for a single match / fixture.
     *
     * @param object $match
     * @return Rest_Match_Response
     */
    public function build_match_response( object $match ): Rest_Match_Response {
        $league_title = $this->resolve_league_title( $match );

        $teams = null;
        if ( isset( $match->teams ) && is_array( $match->teams ) && ! empty( $match->teams ) ) {
            $teams = $match->teams;
        }

        $home_obj = is_array( $teams ) ? ( $teams['home'] ?? null ) : null;
        $away_obj = is_array( $teams ) ? ( $teams['away'] ?? null ) : null;

        $home_val = method_exists( $match, 'get_home_team' ) ? $match->get_home_team() : ( $match->home_team ?? null );
        $away_val = method_exists( $match, 'get_away_team' ) ? $match->get_away_team() : ( $match->away_team ?? null );

        $home_title = $this->resolve_team_title( $home_val, is_object( $home_obj ) ? $home_obj : null );
        $away_title = $this->resolve_team_title( $away_val, is_object( $away_obj ) ? $away_obj : null );

        $winner_id   = method_exists( $match, 'get_winner_id' ) ? $match->get_winner_id() : ( $match->winner_id ?? null );
        $has_result  = ! empty( $winner_id );
        $score       = null;
        $status      = null;

        if ( $has_result ) {
            if ( isset( $match->score ) && '' !== $match->score && null !== $match->score ) {
                $score = str_replace( '"', '', (string) $match->score );
            } else {
                $home_points = method_exists( $match, 'get_home_points' ) ? $match->get_home_points() : ( $match->home_points ?? ( $match->home_score ?? null ) );
                $away_points = method_exists( $match, 'get_away_points' ) ? $match->get_away_points() : ( $match->away_points ?? ( $match->away_score ?? null ) );
                if ( null !== $home_points && null !== $away_points && '' !== (string) $home_points && '' !== (string) $away_points ) {
                    $score = sprintf( '%g - %g', (float) $home_points, (float) $away_points );
                } else {
                    $score = '';
                }
            }

            $raw_status = method_exists( $match, 'get_status' ) ? $match->get_status() : ( $match->status ?? null );
            if ( ! is_null( $raw_status ) && '' !== (string) $raw_status ) {
                $status = Util_Lookup::get_match_status( (int) $raw_status );
            }
        }

        $raw_date = method_exists( $match, 'get_date' ) ? $match->get_date() : ( $match->date ?? '' );
        $raw_time = $match->start_time ?? ( $match->time ?? ( method_exists( $match, 'get_start_time' ) ? $match->get_start_time() : '' ) );

        return new Rest_Match_Response(
            league: str_replace( '"', '', (string) $league_title ),
            home_team: str_replace( '"', '', (string) $home_title ),
            away_team: str_replace( '"', '', (string) $away_title ),
            match_date: substr( (string) ( $raw_date ?? '' ), 0, 10 ),
            match_time: substr( (string) ( $raw_time ?? '' ), 0, 5 ),
            has_result: $has_result,
            score: $score,
            status: $status,
        );
    }

    /**
     * Resolve and memoize the league title for a match.
     *
     * @param object $match
     * @return string
     */
    protected function resolve_league_title( object $match ): string {
        $league_obj = $match->league ?? null;
        if ( is_string( $league_obj ) && '' !== $league_obj ) {
            return str_replace( '"', '', $league_obj );
        }

        if ( is_object( $league_obj ) ) {
            $name  = method_exists( $league_obj, 'get_name' ) ? $league_obj->get_name() : '';
            $title = '' !== $name ? $name : ( $league_obj->title ?? ( method_exists( $league_obj, 'get_title' ) ? $league_obj->get_title() : '' ) );
            return str_replace( '"', '', (string) $title );
        }

        $league_id = method_exists( $match, 'get_league_id' ) ? $match->get_league_id() : ( $match->league_id ?? null );
        if ( null !== $league_id && '' !== (string) $league_id ) {
            $cache_key = (string) $league_id;
            if ( isset( $this->league_title_cache[ $cache_key ] ) ) {
                return $this->league_title_cache[ $cache_key ];
            }

            global $racketmanager;
            if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'competition_service' ) ) {
                try {
                    $comp_service = $racketmanager->container->get( 'competition_service' );
                    $league_repo  = method_exists( $comp_service, 'get_league_repository' ) ? $comp_service->get_league_repository() : null;
                    if ( $league_repo ) {
                        $league = $league_repo->find_by_id( (int) $league_id );
                        if ( $league ) {
                            $name  = method_exists( $league, 'get_name' ) ? $league->get_name() : '';
                            $title = '' !== $name ? $name : ( $league->title ?? ( method_exists( $league, 'get_title' ) ? $league->get_title() : '' ) );
                            $title = str_replace( '"', '', (string) $title );
                            $this->league_title_cache[ $cache_key ] = $title;
                            return $title;
                        }
                    }
                } catch ( League_Not_Found_Exception | Invalid_Argument_Exception ) {
                    // Gracefully fall through
                }
            }

            if ( method_exists( $match, 'get_league' ) ) {
                try {
                    $league = $match->get_league();
                    if ( is_object( $league ) ) {
                        $name  = method_exists( $league, 'get_name' ) ? $league->get_name() : '';
                        $title = '' !== $name ? $name : ( $league->title ?? ( method_exists( $league, 'get_title' ) ? $league->get_title() : '' ) );
                        $title = str_replace( '"', '', (string) $title );
                        $this->league_title_cache[ $cache_key ] = $title;
                        return $title;
                    }
                } catch ( League_Not_Found_Exception | Invalid_Argument_Exception ) {
                    // Gracefully fall through
                }
            }

            if ( null !== $this->league_repository && is_numeric( $league_id ) && (int) $league_id > 0 ) {
                $league = $this->league_repository->find_by_id( (int) $league_id );
                if ( is_object( $league ) ) {
                    $name  = method_exists( $league, 'get_name' ) ? $league->get_name() : '';
                    $title = '' !== $name ? $name : ( $league->title ?? ( method_exists( $league, 'get_title' ) ? $league->get_title() : '' ) );
                    $title = str_replace( '"', '', (string) $title );
                    $this->league_title_cache[ $cache_key ] = $title;
                    return $title;
                }
            }

            $this->league_title_cache[ $cache_key ] = '';
            return '';
        }

        if ( method_exists( $match, 'get_league' ) ) {
            try {
                $league = $match->get_league();
                if ( is_object( $league ) ) {
                    $name  = method_exists( $league, 'get_name' ) ? $league->get_name() : '';
                    $title = '' !== $name ? $name : ( $league->title ?? ( method_exists( $league, 'get_title' ) ? $league->get_title() : '' ) );
                    return str_replace( '"', '', (string) $title );
                }
            } catch ( League_Not_Found_Exception | Invalid_Argument_Exception ) {
                return '';
            }
        }

        return '';
    }

    /**
     * Resolve and memoize the team title for a match side.
     *
     * @param mixed       $team_val
     * @param object|null $team_obj
     * @return string
     */
    protected function resolve_team_title( mixed $team_val, ?object $team_obj = null ): string {
        if ( is_object( $team_obj ) ) {
            $name  = method_exists( $team_obj, 'get_name' ) ? $team_obj->get_name() : '';
            $title = '' !== $name ? $name : ( $team_obj->title ?? ( $team_obj->name ?? '' ) );
            return (string) $title;
        }

        if ( null === $team_val || '' === $team_val ) {
            return '';
        }

        if ( is_object( $team_val ) ) {
            $name  = method_exists( $team_val, 'get_name' ) ? $team_val->get_name() : '';
            $title = '' !== $name ? $name : ( $team_val->title ?? ( $team_val->name ?? '' ) );
            return (string) $title;
        }

        if ( ! is_numeric( $team_val ) ) {
            return (string) $team_val;
        }

        $team_id   = (int) $team_val;
        $cache_key = (string) $team_id;
        if ( isset( $this->team_title_cache[ $cache_key ] ) ) {
            return $this->team_title_cache[ $cache_key ];
        }

        global $racketmanager;
        $title = '';

        if ( isset( $racketmanager->container ) ) {
            if ( $racketmanager->container->has( 'team_repository' ) ) {
                try {
                    $team = $racketmanager->container->get( 'team_repository' )->find_by_id( $team_id );
                    if ( $team ) {
                        $name  = method_exists( $team, 'get_name' ) ? $team->get_name() : '';
                        $title = '' !== $name ? $name : ( $team->title ?? ( $team->name ?? '' ) );
                    }
                } catch ( Team_Not_Found_Exception | Invalid_Argument_Exception ) {
                    $title = '';
                }
            } elseif ( $racketmanager->container->has( 'team_service' ) ) {
                try {
                    $details = $racketmanager->container->get( 'team_service' )->get_team_details( $team_id );
                    if ( $details && $details->team ) {
                        $team  = $details->team;
                        $name  = method_exists( $team, 'get_name' ) ? $team->get_name() : '';
                        $title = '' !== $name ? $name : ( $team->title ?? ( $team->name ?? '' ) );
                    }
                } catch ( Team_Not_Found_Exception | Club_Not_Found_Exception | Invalid_Argument_Exception ) {
                    $title = '';
                }
            }
        }

        $this->team_title_cache[ $cache_key ] = (string) $title;
        return (string) $title;
    }

    /**
     * Build a typed response DTO for a single standings row.
     *
     * @param object      $team  league team row (with league title already resolved).
     * @param object|null $club  club filter, when applied.
     * @return Rest_Standing_Response
     */
    public function build_standing_response( object $team, ?object $club = null ): Rest_Standing_Response {
        $points = $team->points ?? null;
        $points = is_array( $points ) || $points instanceof \ArrayAccess ? ( $points['plus'] ?? 0 ) : 0;

        return new Rest_Standing_Response(
            league: $this->scalar_or_null( $team->league ?? null ),
            season: $this->scalar_or_null( $team->season ?? null ),
            team: $this->scalar_or_null( $team->title ?? null ),
            rank: $this->scalar_or_null( $team->rank ?? null ),
            status: $this->scalar_or_null( $team->status ?? null ),
            played: $this->scalar_or_null( $team->done_matches ?? null ),
            won: $this->scalar_or_null( $team->won_matches ?? null ),
            drawn: $this->scalar_or_null( $team->draw_matches ?? null ),
            lost: $this->scalar_or_null( $team->lost_matches ?? null ),
            points: $this->scalar_or_null( $points ),
            club: null !== $club ? str_replace( '"', '', (string) ( $club->shortcode ?? '' ) ) : null,
        );
    }

    /**
     * Prepare REST response for standings collection.
     *
     * Typed Rest_Standing_Response items are serialised to the legacy object shape;
     * any other items are passed through unchanged for backward compatibility.
     *
     * @param array<int, Rest_Standing_Response|mixed> $data
     * @param int                                      $status
     * @return WP_REST_Response
     */
    public function prepare_standings_response( array $data, int $status = 200 ): WP_REST_Response {
        return new WP_REST_Response( $this->serialise_collection( $data ), $status );
    }

    /**
     * Prepare REST response for matches collection.
     *
     * Typed Rest_Match_Response items are serialised to the legacy object shape;
     * any other items are passed through unchanged for backward compatibility.
     *
     * @param array<int, Rest_Match_Response|mixed> $data
     * @param int                                   $status
     * @return WP_REST_Response
     */
    public function prepare_matches_response( array $data, int $status = 200 ): WP_REST_Response {
        return new WP_REST_Response( $this->serialise_collection( $data ), $status );
    }

    /**
     * Serialise typed response DTOs within a collection.
     *
     * @param array $data
     * @return array
     */
    private function serialise_collection( array $data ): array {
        return array_map(
            static fn( mixed $item ): mixed => ( $item instanceof Rest_Match_Response || $item instanceof Rest_Standing_Response ) ? $item->to_object() : $item,
            $data
        );
    }

    /**
     * Keep scalar values unchanged (preserving JSON types); non-scalar values become null.
     *
     * @param mixed $value
     * @return int|float|string|bool|null
     */
    private function scalar_or_null( mixed $value ): int|float|string|bool|null {
        return is_scalar( $value ) ? $value : null;
    }

    /**
     * Get the JSON schema for match resource items.
     *
     * @return array
     */
    public function get_item_schema(): array {
        return array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'match',
            'type'       => 'object',
            'properties' => array(
                'league'     => array(
                    'description' => esc_html__( 'League of the match.', 'racketmanager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'home_team'  => array(
                    'description' => esc_html__( 'Home team of the match.', 'racketmanager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'away_team'  => array(
                    'description' => esc_html__( 'Away team of the match.', 'racketmanager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'match_date' => array(
                    'description' => esc_html__( 'Date of the match.', 'racketmanager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'match_time' => array(
                    'description' => esc_html__( 'Time of the match.', 'racketmanager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'score'      => array(
                    'description' => esc_html__( 'Score of the match.', 'racketmanager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
                'status'     => array(
                    'description' => esc_html__( 'Status of the match.', 'racketmanager' ),
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit', 'embed' ),
                    'readonly'    => true,
                ),
            ),
        );
    }

    /**
     * Prepare a REST response configured for file attachment delivery.
     *
     * @param string $content
     * @param string $filename
     * @param string $mime_type
     * @return WP_REST_Response
     */
    public function prepare_export_response( string $content, string $filename, string $mime_type ): WP_REST_Response {
        $response = new WP_REST_Response( $content, 200 );
        $response->header( 'Content-Type', $mime_type );
        $response->header( 'Content-Disposition', 'attachment; filename="' . $filename . '"' );

        return $response;
    }

    /**
     * Intercept and serve raw export output (CSV, ICS) without JSON encoding.
     *
     * @param bool             $served
     * @param WP_REST_Response $result
     * @param WP_REST_Request  $request
     * @param WP_REST_Server   $server
     * @return bool
     */
    public function serve_raw_export( bool $served, WP_REST_Response $result, WP_REST_Request $request, WP_REST_Server $server ): bool {
        $route = $request->get_route();

        if ( ! str_contains( $route, '/racketmanager/v1/export' ) ) {
            return $served;
        }

        $headers = $result->get_headers();

        if ( isset( $headers['Content-Type'] ) && (
            Export_Formatter_Interface::CONTENT_TYPE_CSV === $headers['Content-Type'] ||
            Export_Formatter_Interface::CONTENT_TYPE_ICS === $headers['Content-Type']
        ) ) {
            $server->send_headers( $headers );
            echo $result->get_data(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            return true;
        }

        return $served;
    }
}
