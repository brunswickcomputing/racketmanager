<?php
declare(strict_types=1);

namespace Racketmanager {
    if ( ! function_exists( 'Racketmanager\show_alert' ) ) {
        function show_alert( string $message, string $type = 'info', ?string $template = null ): string {
            return sprintf( "<div class='alert alert-%s'>%s</div>", $type, $message );
        }
    }

    if ( ! function_exists( 'Racketmanager\the_match_time' ) ) {
        function the_match_time( $start_time ): void {
            if ( '00:00' === $start_time ) {
                echo '';
            } else {
                echo (string) $start_time;
            }
        }
    }

    if ( ! function_exists( 'Racketmanager\latest_results' ) ) {
        function latest_results( int $club_id = 0, array $args = array() ): void {}
    }

    if ( ! function_exists( 'Racketmanager\daily_matches' ) ) {
        function daily_matches( array $args = array() ): void {}
    }

    if ( ! function_exists( 'Racketmanager\upcoming_matches' ) ) {
        function upcoming_matches( int $club_id = 0, array $args = array() ): void {}
    }

    if ( ! function_exists( 'Racketmanager\get_league_team' ) ) {
        function get_league_team( $id ) {
            return $GLOBALS['wp_stubs_league_teams'][ $id ] ?? null;
        }
    }

    if ( ! function_exists( 'Racketmanager\player_search' ) ) {
        function player_search( string $search_string, array $args = [] ): string {
            return "<div>Player search for: $search_string</div>";
        }
    }

    if ( ! function_exists( 'Racketmanager\event_dropdown' ) ) {
        function event_dropdown( int $competition_id, array $args = array() ): string {
            return "<select name='event_id'></select>";
        }
    }

    if ( ! function_exists( 'Racketmanager\league_dropdown' ) ) {
        function league_dropdown( int $event_id, array $args = array() ): string {
            return "<select name='league_id'></select>";
        }
    }

    if ( ! function_exists( 'Racketmanager\season_dropdown' ) ) {
        function season_dropdown( int $league_id, array $args = array() ): string {
            return "<select name='season'></select>";
        }
    }

    if ( ! function_exists( 'Racketmanager\match_dropdown' ) ) {
        function match_dropdown( int $league_id, array $args = array() ): string {
            return "<select name='match_id'></select>";
        }
    }

    if ( ! function_exists( 'Racketmanager\team_order_players' ) ) {
        function team_order_players( int $event_id, array $args = array() ): string {
            return "<div class='team-order-players'></div>";
        }
    }

    if ( ! function_exists( 'Racketmanager\event_team_match_dropdown' ) ) {
        function event_team_match_dropdown( int $event_id, array $args = array() ): string {
            return "<select name='team_matches'></select>";
        }
    }

    if ( ! function_exists( 'Racketmanager\show_team_edit_modal' ) ) {
        function show_team_edit_modal( int $team_id, array $args = array() ): string {
            return "<div class='modal-team-edit'></div>";
        }
    }

    if ( ! function_exists( 'Racketmanager\is_lta_number_required' ) ) {
        function is_lta_number_required(): bool {
            return $GLOBALS['wp_stubs_is_lta_number_required'] ?? false;
        }
    }

    if ( ! function_exists( 'Racketmanager\do_action' ) ) {
        function do_action( string $hook_name, ...$arg ): void {}
    }

    if ( ! class_exists( 'Racketmanager\RacketManager' ) ) {
        eval('
            class RacketManager {
                public static $container;
                public $shortcodes;
                public array $options = [];

                public function __construct() {
                    $this->shortcodes = new class {
                        public function load_template( string $slug, array $args = [], string $sub_directory = "" ): string {
                            return "Template: " . $slug;
                        }
                    };
                    self::$container  = new class {
                        public function has( string $id ): bool {
                            return in_array( $id, [
                                "competition_service",
                                "club_service",
                                "player_service",
                                "registration_service",
                                "event_service",
                                "league_service",
                                "fixture_service",
                                "team_service"
                            ], true );
                        }
                        public function get( string $id ): object {
                            try {
                                switch ( $id ) {
                                    case "competition_service":
                                        return (new \ReflectionClass( "Racketmanager\Services\Competition_Service" ))->newInstanceWithoutConstructor();
                                    case "club_service":
                                        return (new \ReflectionClass( "Racketmanager\Services\Club_Service" ))->newInstanceWithoutConstructor();
                                    case "player_service":
                                        return (new \ReflectionClass( "Racketmanager\Services\Player_Service" ))->newInstanceWithoutConstructor();
                                    case "registration_service":
                                        return (new \ReflectionClass( "Racketmanager\Services\Registration_Service" ))->newInstanceWithoutConstructor();
                                    case "event_service":
                                        return (new \ReflectionClass( "Racketmanager\Services\Event_Service" ))->newInstanceWithoutConstructor();
                                    case "league_service":
                                        return (new \ReflectionClass( "Racketmanager\Services\League_Service" ))->newInstanceWithoutConstructor();
                                    case "fixture_service":
                                        return (new \ReflectionClass( "Racketmanager\Services\Fixture_Service" ))->newInstanceWithoutConstructor();
                                    case "team_service":
                                        return (new \ReflectionClass( "Racketmanager\Services\Team_Service" ))->newInstanceWithoutConstructor();
                                }
                            } catch ( \ReflectionException $e ) {
                            }
                            return new \stdClass();
                        }
                    };
                }

                public function get_options(): array {
                    return [
                        "league"     => [
                            "resultConfirmation" => "manual",
                        ],
                        "tournament" => [
                            "resultConfirmation" => "manual",
                        ],
                    ];
                }

                public function get_confirmation_email( string $type ): string {
                    return "admin@example.test";
                }

                public function set_message( string $message ): void {}
                public function is_user_favourite( $type = null, $id = null ): bool { return false; }
                public function load_options(): void {
                    $this->options = [];
                    $this->date_format = "Y-m-d";
                }
            }
        ');
    }
}

namespace {

    use JetBrains\PhpStorm\NoReturn;

    function maybe_unserialize( $data ) {
        if ( is_string( $data ) && preg_match( '/^([adObis]):/', $data ) ) {
            return unserialize( $data );
        }
        return $data;
    }

    if ( ! function_exists( 'stripslashes_deep' ) ) {
        function stripslashes_deep( $value ) {
            return $value;
        }
    }

    if ( ! function_exists( 'apply_filters' ) ) {
        function apply_filters( $hook_name, $value, ...$args ) {
            return $value;
        }
    }

    if ( ! function_exists( 'do_action' ) ) {
        function do_action( string $hook_name, ...$arg ): void {}
    }

    if ( ! function_exists( 'home_url' ) ) {
        function home_url( string $path = '', ?string $scheme = null ): string {
            return 'https://example.test' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
        }
    }

    if ( ! function_exists( 'trailingslashit' ) ) {
        function trailingslashit( string $value ): string {
            return rtrim( $value, '/\\' ) . '/';
        }
    }

    if ( ! function_exists( 'shortcode_atts' ) ) {
        function shortcode_atts( array $pairs, $atts, string $shortcode = '' ): array {
            $atts = (array) $atts;
            $out  = array();
            foreach ( $pairs as $name => $default ) {
                if ( array_key_exists( $name, $atts ) ) {
                    $out[ $name ] = $atts[ $name ];
                } else {
                    $out[ $name ] = $default;
                }
            }
            return $out;
        }
    }

    if ( ! function_exists( 'get_stylesheet_directory' ) ) {
        function get_stylesheet_directory(): string {
            return $GLOBALS['wp_stubs_stylesheet_directory'] ?? '';
        }
    }

    if ( ! function_exists( 'get_template_directory' ) ) {
        function get_template_directory(): string {
            return $GLOBALS['wp_stubs_template_directory'] ?? '';
        }
    }

    if ( ! function_exists( 'wp_nonce_field' ) ) {
        function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ): string {
            $html = '<input type="hidden" name="' . htmlspecialchars( (string) $name ) . '" value="test_nonce" />';
            if ( $echo ) {
                echo $html;
            }
            return $html;
        }
    }

    if ( ! function_exists( 'retrieve_password' ) ) {
        function retrieve_password( string $user_login = '' ) {
            if ( isset( $GLOBALS['wp_stubs_retrieve_password_callback'] ) ) {
                return ( $GLOBALS['wp_stubs_retrieve_password_callback'] )( $user_login );
            }
            if ( $user_login === 'error@example.test' ) {
                return new WP_Error( 'invalid_email', 'Invalid email' );
            }
            return true;
        }
    }

    if ( ! function_exists( 'get_option' ) ) {
        function get_option( $option, $default = false ) {
            if ( isset( $GLOBALS['wp_stubs_options'] ) && array_key_exists( $option, $GLOBALS['wp_stubs_options'] ) ) {
                return $GLOBALS['wp_stubs_options'][ $option ];
            }
            if ($option === 'racketmanager_options') {
                return is_array($default) ? $default : array();
            }
            if ($option === 'racketmanager_settings') {
                return [
                    'date_format' => 'Y-m-d',
                    'league' => [
                        'resultConfirmation' => 'manual',
                    ],
                    'tournament' => [
                        'resultConfirmation' => 'manual',
                    ],
                ];
            }
            return $default;
        }
    }

    if ( ! class_exists( 'wpdb' ) ) {
        eval('
        #[\AllowDynamicProperties]
        class wpdb {
            public $prefix = "wp_";
            public $racketmanager = "wp_racketmanager_leagues";
            public $racketmanager_leagues = "wp_racketmanager_leagues";
            public $racketmanager_league_teams = "wp_racketmanager_league_teams";
            public $racketmanager_teams = "wp_racketmanager_teams";
            public $racketmanager_matches = "wp_racketmanager_matches";
            public $racketmanager_rubbers = "wp_racketmanager_rubbers";
            public $racketmanager_club_players = "wp_racketmanager_club_players";
            public $racketmanager_competitions = "wp_racketmanager_competitions";
            public $racketmanager_team_events = "wp_racketmanager_team_events";
            public $racketmanager_clubs = "wp_racketmanager_clubs";
            public $racketmanager_seasons = "wp_racketmanager_seasons";
            public $racketmanager_competitions_seasons = "wp_racketmanager_competitions_seasons";
            public $racketmanager_results_checker = "wp_racketmanager_results_checker";
            public $racketmanager_tournaments = "wp_racketmanager_tournaments";
            public $racketmanager_charges = "wp_racketmanager_charges";
            public $racketmanager_invoices = "wp_racketmanager_invoices";
            public $racketmanager_events = "wp_racketmanager_events";
            public $racketmanager_rubber_players = "wp_racketmanager_rubber_players";
            public $racketmanager_results_report = "wp_racketmanager_results_report";
            public $racketmanager_messages = "wp_racketmanager_messages";
            public $racketmanager_team_players = "wp_racketmanager_team_players";
            public $racketmanager_tournament_entries = "wp_racketmanager_tournament_entries";
            public $racketmanager_player_errors = "wp_racketmanager_player_errors";
            public $racketmanager_club_roles = "wp_racketmanager_club_roles";
            public $get_results_callback;
            public $get_row_callback;
            public $update_callback;
            public $last_query;
            public $insert_id = 0;
            private $data = [];
            private $last_id = 0;

            public function prepare( $query, ...$args ) {
                if ( empty($args) ) return $query;
                if ( is_array($args[0]) && count($args) === 1 ) $args = $args[0];
                
                $prepared_args = [];
                foreach ($args as $arg) {
                    if (is_string($arg)) {
                        $prepared_args[] = "\'" . addslashes($arg) . "\'";
                    } else {
                        $prepared_args[] = $arg;
                    }
                }

                $query = str_replace( "\'%s\'", "%s", $query );
                $query = str_replace( "%d", "%s", $query );
                $query = str_replace( "%f", "%s", $query );
                
                try {
                    return vsprintf( $query, $prepared_args );
                } catch (\Throwable $e) {
                    return $query;
                }
            }
            public function get_row( $query, $output = "OBJECT" ) { 
                if ( $this->get_row_callback ) return ($this->get_row_callback)($query);
                $results = $this->get_results($query, $output);
                return !empty($results) ? $results[0] : null;
            }
            public function get_results( $query, $output = "OBJECT" ) { 
                if ( $this->get_results_callback ) return ($this->get_results_callback)($query);
                
                // Very simple mock query parser for tests
                if (preg_match("/SELECT \* FROM (\w+) WHERE `id` = (\d+)/", $query, $matches)) {
                    $table = $matches[1];
                    $id = (int)$matches[2];
                    $found = array_filter($this->data[$table] ?? [], fn($row) => $row->id == $id);
                    return array_values($found);
                }
                if (preg_match("/SELECT \* FROM (\w+) WHERE `match_id` = (\d+)/", $query, $matches)) {
                    $table = $matches[1];
                    $match_id = (int)$matches[2];
                    $found = array_filter($this->data[$table] ?? [], fn($row) => ($row->match_id ?? null) == $match_id);
                    return array_values($found);
                }
                
                return array(); 
            }
            public function get_var( $query, $x = 0, $y = 0 ) { 
                if (preg_match("/SELECT count\(\*\) FROM (\w+) WHERE match_id = (\d+)/", $query, $matches)) {
                    $table = $matches[1];
                    $match_id = (int)$matches[2];
                    return count(array_filter($this->data[$table] ?? [], fn($row) => ($row->match_id ?? null) == $match_id));
                }
                if (preg_match("/SELECT count\(\*\) FROM (\w+) WHERE `match_id` = (\d+)/", $query, $matches)) {
                    $table = $matches[1];
                    $match_id = (int)$matches[2];
                    return count(array_filter($this->data[$table] ?? [], fn($row) => ($row->match_id ?? null) == $match_id));
                }
                return null; 
            }
            public function insert( $table, $data, $format = null ) { 
                $this->last_id++;
                $this->insert_id = $this->last_id;
                $row = (object)$data;
                $row->id = $this->insert_id;
                $this->data[$table][] = $row;
                return true; 
            }
            public function update( $table, $data, $where, $format = null, $where_format = null ) { 
                if ( $this->update_callback ) return ($this->update_callback)($table, $data, $where);
                if (isset($where["id"])) {
                    foreach ($this->data[$table] ?? [] as $row) {
                        if ($row->id == $where["id"]) {
                            foreach ($data as $k => $v) $row->$k = $v;
                        }
                    }
                }
                return true; 
            }
            public function delete( $table, $where, $where_format = null ) { 
                if (isset($where["match_id"])) {
                    $this->data[$table] = array_filter($this->data[$table] ?? [], fn($row) => ($row->match_id ?? null) != $where["match_id"]);
                }
                return true; 
            }
            public function query( $query ) { $this->last_query = $query; return true; }
            public function show_errors() {}
            public function hide_errors() {}
        }
        ');
    }

    if ( ! isset( $GLOBALS['wpdb'] ) ) {
        $GLOBALS['wpdb'] = new wpdb( 'user', 'password', 'dbname', 'localhost');
    }

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    if ( ! function_exists( 'add_action' ) ) {
        function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
            if ( isset( $GLOBALS['wp_action_hooks'] ) ) {
                $GLOBALS['wp_action_hooks'][$tag][] = $function_to_add;
            }
            return true;
        }
    }

    if ( ! function_exists( 'add_filter' ) ) {
        function add_filter( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
            return true;
        }
    }

    if ( ! function_exists( 'add_rewrite_tag' ) ) {
        function add_rewrite_tag( $tag, $regex, $query = '' ) {
            return true;
        }
    }

    if ( ! function_exists( 'absint' ) ) {
        function absint( $x ) {
            return abs( intval( $x ) );
        }
    }

    if ( ! function_exists( 'wp_unslash' ) ) {
        function wp_unslash( $value ) {
            return $value;
        }
    }
    if ( ! function_exists( 'wp_strip_all_tags' ) ) {
        function wp_strip_all_tags( $value ) {
            return strip_tags( $value );
        }
    }

    if ( ! function_exists( 'sanitize_text_field' ) ) {
        function sanitize_text_field( $value ) {
            return is_string( $value ) ? trim( strip_tags( $value ) ) : $value;
        }
    }

    if ( ! function_exists( 'sanitize_email' ) ) {
        function sanitize_email( $email ) {
            return is_string( $email ) ? trim( $email ) : $email;
        }
    }

    if ( ! function_exists( 'sanitize_textarea_field' ) ) {
        function sanitize_textarea_field( $value ) {
            return is_string( $value ) ? trim( $value ) : $value;
        }
    }

    if ( ! function_exists( 'admin_url' ) ) {
        function admin_url( string $path = '' ): string {
            return 'https://example.test/wp-admin/' . ltrim( $path, '/' );
        }
    }

    if ( ! function_exists( 'add_query_arg' ) ) {
        function add_query_arg( array|string $arg1, mixed $arg2 = '', string $arg3 = '' ): string {
            if ( is_array( $arg1 ) ) {
                $args = $arg1;
                $url  = is_string( $arg2 ) ? $arg2 : '';
            } else {
                $args = array(
                    $arg1 => $arg2,
                );
                $url = $arg3;
            }

            $pairs = array();
            foreach ( $args as $k => $v ) {
                if ( null === $v || '' === $v ) {
                    continue;
                }
                $pairs[] = rawurlencode( $k ) . '=' . rawurlencode( (string) $v );
            }
            $qs = implode( '&', $pairs );

            return $url . ( $qs ? ( str_contains( $url, '?' ) ? '&' : '?' ) . $qs : '' );
        }
    }

    if ( ! function_exists( 'mysql2date' ) ) {
        function mysql2date( string $format, string $date_string, bool $translate = true ): string|int|false {
            $timestamp = strtotime( $date_string );
            return $timestamp ? date( $format, $timestamp ) : false;
        }
    }

    if ( ! function_exists( 'rest_do_request' ) ) {
        function rest_do_request( $request ) {
            $GLOBALS['wp_stubs_last_rest_request'] = $request;
            if ( isset( $GLOBALS['wp_stubs_rest_do_request_callback'] ) && is_callable( $GLOBALS['wp_stubs_rest_do_request_callback'] ) ) {
                return ( $GLOBALS['wp_stubs_rest_do_request_callback'] )( $request );
            }
            return $GLOBALS['wp_stubs_rest_do_request_return'] ?? new WP_REST_Response();
        }
    }

    if ( ! function_exists( 'rest_url' ) ) {
        function rest_url( string $path = '' ): string {
            return 'https://example.test/wp-json/' . ltrim( $path, '/' );
        }
    }

    if ( ! function_exists( 'current_time' ) ) {
        function current_time( string $type, $gmt = 0 ): string {
            return date( 'Y-m-d H:i:s' );
        }
    }

    if ( ! function_exists( 'show_alert' ) ) {
        function show_alert( string $message, string $type = 'info', ?string $template = null ): void {
        }
    }

    if ( ! function_exists( 'is_user_logged_in' ) ) {
        function is_user_logged_in(): bool {
            if ( isset( $GLOBALS['wp_stubs_is_user_logged_in'] ) ) {
                return (bool) $GLOBALS['wp_stubs_is_user_logged_in'];
            }
            return ( $GLOBALS['wp_stubs_get_current_user_id'] ?? 1 ) > 0;
        }
    }

    if ( ! function_exists( 'get_current_user_id' ) ) {
        function get_current_user_id(): int {
            return $GLOBALS['wp_stubs_get_current_user_id'] ?? $GLOBALS['wp_stubs_current_user_id'] ?? 1;
        }
    }

    if ( ! function_exists( 'wp_get_current_user' ) ) {
        function wp_get_current_user() {
            return (object) [ 'ID' => $GLOBALS['wp_stubs_get_current_user_id'] ?? 1 ];
        }
    }

    if ( ! function_exists( 'maybe_serialize' ) ) {
        function maybe_serialize( $data ) {
            if ( is_array( $data ) || is_object( $data ) ) {
                return serialize( $data );
            }
            return $data;
        }
    }

    if ( ! function_exists( 'wp_cache_get' ) ) {
        function wp_cache_get( $id, $group = '' ) {
            return false;
        }
    }

    if ( ! function_exists( 'wp_cache_set' ) ) {
        function wp_cache_set( $id, $data, $group = '', $expire = 0 ) {
            return true;
        }
    }

    if ( ! function_exists( 'wp_verify_nonce' ) ) {
        function wp_verify_nonce( $nonce, $action = -1 ) {
            return $GLOBALS['wp_stubs_wp_verify_nonce_return'] ?? true;
        }
    }

    if ( ! function_exists( 'wp_die' ) ) {
        function wp_die( mixed $message = '', string|int $title = '', array|int|string $args = [] ): mixed {
            $GLOBALS['wp_stubs_last_wp_die'] = [
                'message' => $message,
                'title'   => $title,
                'args'    => $args,
            ];

            if ( isset( $GLOBALS['wp_stubs_wp_die_handler'] ) && is_callable( $GLOBALS['wp_stubs_wp_die_handler'] ) ) {
                return ( $GLOBALS['wp_stubs_wp_die_handler'] )( $message, $title, $args );
            }

            if ( ! empty( $GLOBALS['wp_stubs_throw_on_wp_die'] ) ) {
                $status = match ( true ) {
                    is_array( $args ) && isset( $args['response'] ) && is_numeric( $args['response'] ) => (int) $args['response'],
                    is_numeric( $args ) => (int) $args,
                    default => 500,
                };

                $errorMessage = match ( true ) {
                    $message instanceof \WP_Error || ( is_object( $message ) && method_exists( $message, 'get_error_message' ) ) => $message->get_error_message(),
                    is_scalar( $message ) || ( is_object( $message ) && method_exists( $message, '__toString' ) ) => (string) $message,
                    default => 'wp_die called',
                };

                throw new \RuntimeException( $errorMessage, $status );
            }

            return null;
        }
    }

    if ( ! function_exists( 'status_header' ) ) {
        function status_header( $code, $description = '' ) {}
    }

    if ( ! function_exists( 'wp_send_json_success' ) ) {
        #[NoReturn]
        function wp_send_json_success( $data = null, $status_code = null, $options = 0 ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            echo json_encode( array( 'success' => true, 'data' => $data ) );
            die();
        }
    }

    if ( ! function_exists( 'wp_send_json_error' ) ) {
        #[NoReturn]
        function wp_send_json_error( $data = null, $status_code = null, $options = 0 ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            echo json_encode( array( 'success' => false, 'data' => $data ) );
            die();
        }
    }

    if ( ! function_exists( 'stripslashes' ) ) {
        function stripslashes( $value ) {
            return is_string( $value ) ? str_replace( "\\", "", $value ) : $value;
        }
    }

    if ( ! function_exists( 'is_admin' ) ) {
        function is_admin(): bool {
            return (bool) ( $GLOBALS['wp_stubs_is_admin'] ?? false );
        }
    }

    if ( ! function_exists( 'paginate_links' ) ) {
        function paginate_links( $args = '' ) {
            return '';
        }
    }
}

namespace Racketmanager\Util {
    if ( ! function_exists( 'Racketmanager\Util\get_users' ) ) {
        function get_users( $args = [] ) {
            return [];
        }
    }
}

namespace {
    if ( ! function_exists( 'is_serialized' ) ) {
        function is_serialized( $data ): bool {
            return is_string( $data ) && preg_match( '/^([adObis]):/', $data );
        }
    }

    if ( ! function_exists( 'wp_register_script' ) ) {
        function wp_register_script( $handle, $src, $deps = array(), $ver = false, $in_footer = false ): bool {
            return true;
        }
    }

    if ( ! function_exists( 'wp_enqueue_script' ) ) {
        function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ): void {}
    }

    if ( ! function_exists( 'plugins_url' ) ) {
        function plugins_url( $path = '', $plugin = '' ): string {
            return 'https://example.com/wp-content/plugins/' . ltrim( $path, '/' );
        }
    }

    if ( ! function_exists( 'wp_cache_delete' ) ) {
        function wp_cache_delete( $id, $group = '' ) {
            $GLOBALS['wp_stubs_cache_deleted'][] = array( $id, $group );
            return true;
        }
    }

    if ( ! function_exists( 'selected' ) ) {
        function selected( $selected, $current = true, $echo = true ) {
            $result = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
            if ( $echo ) {
                echo $result;
            }
            return $result;
        }
    }

    if ( ! function_exists( 'checked' ) ) {
        function checked( $checked, $current = true, $echo = true ) {
            $result = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
            if ( $echo ) {
                echo $result;
            }
            return $result;
        }
    }

    if ( ! function_exists( 'disabled' ) ) {
        function disabled( $disabled, $current = true, $echo = true ) {
            $result = ( (string) $disabled === (string) $current ) ? ' disabled="disabled"' : '';
            if ( $echo ) {
                echo $result;
            }
            return $result;
        }
    }

    if ( ! function_exists( 'get_permalink' ) ) {
        function get_permalink( $post = 0, $leavename = false ) {
            return 'https://example.test/page/' . ( is_object( $post ) ? ( $post->ID ?? 0 ) : $post );
        }
    }

    if ( ! function_exists( 'sanitize_url' ) ) {
        function sanitize_url( string $url, array $protocols = array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' ) ): string {
            return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
        }
    }

    if ( ! function_exists( 'wp_validate_redirect' ) ) {
        function wp_validate_redirect( string $location, string $default = '' ): string {
            return $location ?: $default;
        }
    }

    if ( ! function_exists( 'wp_signon' ) ) {
        function wp_signon( array $credentials = array(), $secure_cookie = '' ) {
            $GLOBALS['wp_stubs_last_signon_args'] = $credentials;
            if ( isset( $GLOBALS['wp_stubs_signon_error'] ) ) {
                return $GLOBALS['wp_stubs_signon_error'];
            }
            return (object) array( 'ID' => 1, 'user_login' => $credentials['user_login'] ?? 'user' );
        }
    }

    if ( ! function_exists( 'get_user_meta' ) ) {
        function get_user_meta( int $user_id, string $key = '', bool $single = false ) {
            $user_meta = $GLOBALS['wp_stubs_user_meta'][ $user_id ][ $key ] ?? array();
            if ( $single ) {
                return is_array( $user_meta ) ? ( $user_meta[0] ?? '' ) : $user_meta;
            }
            return is_array( $user_meta ) ? $user_meta : array( $user_meta );
        }
    }

    if ( ! function_exists( 'add_user_meta' ) ) {
        function add_user_meta( int $user_id, string $meta_key, $meta_value, bool $unique = false ): bool {
            if ( ! isset( $GLOBALS['wp_stubs_user_meta'][ $user_id ][ $meta_key ] ) ) {
                $GLOBALS['wp_stubs_user_meta'][ $user_id ][ $meta_key ] = array();
            }
            $GLOBALS['wp_stubs_user_meta'][ $user_id ][ $meta_key ][] = (string) $meta_value;
            return true;
        }
    }

    if ( ! function_exists( 'delete_user_meta' ) ) {
        function delete_user_meta( int $user_id, string $meta_key, $meta_value = '' ): bool {
            if ( isset( $GLOBALS['wp_stubs_user_meta'][ $user_id ][ $meta_key ] ) ) {
                if ( $meta_value !== '' ) {
                    $GLOBALS['wp_stubs_user_meta'][ $user_id ][ $meta_key ] = array_values( array_filter(
                        $GLOBALS['wp_stubs_user_meta'][ $user_id ][ $meta_key ],
                        fn( $val ) => (string) $val !== (string) $meta_value
                    ) );
                } else {
                    unset( $GLOBALS['wp_stubs_user_meta'][ $user_id ][ $meta_key ] );
                }
            }
            return true;
        }
    }

    if ( ! function_exists( 'esc_url_raw' ) ) {
        function esc_url_raw( $url ) {
            return $url;
        }
    }

    if ( ! function_exists( 'esc_url' ) ) {
        function sanitize_title( $title ) {
            return strtolower( str_replace( ' ', '-', $title ) );
        }

        function esc_url( $url ) {
            return $url;
        }
    }

    if ( ! function_exists( 'esc_attr' ) ) {
        function esc_attr( $text ) {
            return $text;
        }
    }

    if ( ! function_exists( 'wp_json_encode' ) ) {
        function wp_json_encode( $data ) {
            return json_encode( $data );
        }
    }

    if ( ! function_exists( 'wp_next_scheduled' ) ) {
        function wp_next_scheduled( $hook, $args = [] ) {
            return false;
        }
    }

    if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
        function wp_clear_scheduled_hook( $hook, $args = [] ) {
            return 0;
        }
    }

    if ( ! function_exists( 'wp_schedule_single_event' ) ) {
        function wp_schedule_single_event( $timestamp, $hook, $args = [], $wp_error = false ) {
            return true;
        }
    }

    if ( ! function_exists( '__' ) ) {
        function __( string $text, string $domain = '' ): string {
            return $text;
        }
    }

    if ( ! function_exists( '_e' ) ) {
        function _e( string $text, string $domain = '' ): void {
            echo $text;
        }
    }

    if ( ! function_exists( '_n' ) ) {
        function _n( string $single, string $plural, $number, string $domain = '' ): string {
            return $number == 1 ? $single : $plural;
        }
    }

    if ( ! isset( $GLOBALS['wp'] ) ) {
        $GLOBALS['wp'] = new class {
            public function set_query_var( $key, $value ) {}
        };
    }

    if ( ! isset( $GLOBALS['racketmanager'] ) ) {
        $GLOBALS['racketmanager'] = new class {
            public $container;
            public string $date_format = 'Y-m-d';
            public string $site_url = 'https://example.test';
            public string $site_name = 'RacketManager';
            public $shortcodes;

            public function __construct() {
                $this->shortcodes = new class {
                    public function load_template( string $slug, array $args = [], string $sub_directory = '' ): string {
                        return 'Template: ' . $slug;
                    }
                };
                $this->container = new class {
                    public function has( string $id ): bool {
                        return in_array( $id, [
                            'competition_service',
                            'club_service',
                            'player_service',
                            'registration_service',
                        ], true );
                    }
                    public function get( string $id ): object {
                        $reflection = null;
                        try {
                            switch ( $id ) {
                                case 'competition_service':
                                    $reflection = new ReflectionClass( 'Racketmanager\Services\Competition_Service' );
                                    break;
                                case 'club_service':
                                    $reflection = new ReflectionClass( 'Racketmanager\Services\Club_Service' );
                                    break;
                                case 'player_service':
                                    $reflection = new ReflectionClass( 'Racketmanager\Services\Player_Service' );
                                    break;
                                case 'registration_service':
                                    $reflection = new ReflectionClass( 'Racketmanager\Services\Registration_Service' );
                                    break;
                            }
                        } catch ( Exception ) {
                            return new stdClass();
                        }

                        if ( $reflection ) {
                            return $reflection->newInstanceWithoutConstructor();
                        }

                        return new stdClass();
                    }
                };
            }

            public function get_options(): array {
                return [
                    'league' => [
                        'resultConfirmation' => 'manual',
                    ],
                    'tournament' => [
                        'resultConfirmation' => 'manual',
                    ],
                ];
            }

            public function get_confirmation_email( string $type ): string {
                return 'admin@example.test';
            }
        };
    }

    if ( ! function_exists( 'get_userdata' ) ) {
        function get_userdata( $user_id ) {
            return (object) [
                'display_name' => 'User ' . $user_id,
                'user_email'   => 'user' . $user_id . '@example.test',
            ];
        }
    }

    if ( ! function_exists( 'get_query_var' ) ) {
        function get_query_var( $var, $default = '' ) {
            return $GLOBALS['query_vars'][ $var ] ?? $default;
        }
    }

    if ( ! function_exists( 'is_user_logged_in' ) ) {
        function is_user_logged_in(): bool {
            return $GLOBALS['wp_stubs_is_user_logged_in'] ?? false;
        }
    }

    if ( ! function_exists( 'get_current_user_id' ) ) {
        function get_current_user_id(): int {
            return $GLOBALS['wp_stubs_current_user_id'] ?? 0;
        }
    }

    if ( ! function_exists( 'wp_get_current_user' ) ) {
        function wp_get_current_user() {
            $user_id = get_current_user_id();
            return (object) [
                'ID' => $user_id,
                'display_name' => 'User ' . $user_id,
                'user_email' => 'user' . $user_id . '@example.test',
            ];
        }
    }

    if ( ! function_exists( 'home_url' ) ) {
        function home_url( $path = '' ): string {
            return 'https://example.test' . ( $path ? '/' . ltrim( $path, '/' ) : '' );
        }
    }

    if ( ! function_exists( 'wp_mail' ) ) {
        function wp_mail( $to, $subject, $message, $headers = '', $attachments = array() ) {
            $GLOBALS['wp_mail_calls'][] = array(
                'to'          => $to,
                'subject'     => $subject,
                'message'     => $message,
                'headers'     => $headers,
                'attachments' => $attachments,
            );
            return true;
        }
    }

    if ( ! defined( 'RACKETMANAGER_PATH' ) ) {
        define( 'RACKETMANAGER_PATH', dirname( __DIR__ ) . '/' );
    }

    if ( ! defined( 'RACKETMANAGER_URL' ) ) {
        define( 'RACKETMANAGER_URL', 'https://example.test/wp-content/plugins/racketmanager/' );
    }

    if ( ! defined( 'RACKETMANAGER_IS_INVALID' ) ) {
        define( 'RACKETMANAGER_IS_INVALID', 'is-invalid' );
    }

    if ( ! defined( 'RACKETMANAGER_CHECKED' ) ) {
        define( 'RACKETMANAGER_CHECKED', 'checked' );
    }

    if ( ! defined( 'RACKETMANAGER_FROM_EMAIL' ) ) {
        define( 'RACKETMANAGER_FROM_EMAIL', 'From: ' );
    }

    if ( ! defined( 'RACKETMANAGER_CC_EMAIL' ) ) {
        define( 'RACKETMANAGER_CC_EMAIL', 'Cc: ' );
    }

    if ( ! function_exists( 'current_user_can' ) ) {
        function current_user_can( $capability ) {
            return $GLOBALS['wp_stubs_current_user_can'] ?? false;
        }
    }

    if ( ! class_exists( 'WP_Error' ) ) {
        eval('
        class WP_Error {
            public array $errors = array();
            public array $error_data = array();

            public function __construct( $code = "", $message = "", $data = "" ) {
                if ( ! empty( $code ) ) {
                    $this->add( $code, $message, $data );
                }
            }

            public function add( $code, $message, $data = "" ): void {
                $this->errors[ $code ][] = $message;
                if ( ! empty( $data ) ) {
                    $this->error_data[ $code ] = $data;
                }
            }

            public function add_data( $data, $code = "" ): void {
                if ( empty( $code ) ) {
                    $code = $this->get_error_code();
                }
                $this->error_data[ $code ] = $data;
            }

            public function get_error_code(): string|int {
                $codes = $this->get_error_codes();
                return $codes[0] ?? "";
            }

            public function get_error_messages( $code = "" ) {
                if ( empty( $code ) ) {
                    $all = array();
                    foreach ( $this->errors as $messages ) {
                        $all = array_merge( $all, $messages );
                    }
                    return $all;
                }
                return $this->errors[ $code ] ?? array();
            }

            public function get_error_codes(): array {
                return array_keys( $this->errors );
            }

            public function get_error_message( $code = "" ): mixed {
                $messages = $this->get_error_messages( $code );
                return $messages[0] ?? "";
            }

            public function get_error_data( $code = "" ) {
                if ( empty( $code ) ) {
                    $code = $this->get_error_code();
                }
                return $this->error_data[ $code ] ?? null;
            }
        }
        ');
    }

    if ( ! function_exists( 'is_wp_error' ) ) {
        function is_wp_error( $thing ): bool {
            return $thing instanceof WP_Error;
        }
    }

    if ( ! function_exists( 'esc_html' ) ) {
        function esc_html( $text ) {
            return $text;
        }
    }

    if ( ! function_exists( 'esc_html_e' ) ) {
        function esc_html_e( $text, $domain = 'default' ) {
            echo esc_html( $text );
        }
    }

    if ( ! function_exists( 'esc_html__' ) ) {
        function esc_html__( $text, $domain = 'default' ) {
            return esc_html( $text );
        }
    }

    if ( ! function_exists( 'wp_specialchars_decode' ) ) {
        function wp_specialchars_decode( $string, $quote_style = ENT_NOQUOTES ) {
            $string = (string) $string;
            if ( 0 === strlen( $string ) ) {
                return '';
            }
            if ( false === strpos( $string, '&' ) ) {
                return $string;
            }
            return htmlspecialchars_decode( $string, $quote_style );
        }
    }

    if ( ! function_exists( 'esc_attr_e' ) ) {
        function esc_attr_e( $text, $domain = 'default' ) {
            echo esc_attr( $text );
        }
    }

    if ( ! function_exists( 'esc_attr__' ) ) {
        function esc_attr__( $text, $domain = 'default' ) {
            return esc_attr( $text );
        }
    }

    if ( ! function_exists( '__' ) ) {
        function __( $text, $domain = 'default' ) {
            return $text;
        }
    }

    if ( ! class_exists( 'WP_REST_Request' ) ) {
        eval('
        class WP_REST_Request implements ArrayAccess {
            private $method;
            private $route;
            private $params = [];
            private $body = "";
            private $headers = [];

            public function __construct( $method = "", $route = "", $params = [] ) {
                $this->method = $method;
                $this->route  = $route;
                $this->params = $params;
            }

            public function set_param( $key, $value ) {
                $this->params[ $key ] = $value;
            }

            public function get_param( $key ) {
                return $this->params[ $key ] ?? null;
            }

            public function set_query_params( $params ) {
                $this->params = array_merge( $this->params, $params );
            }

            public function get_route() {
                return $this->route;
            }

            public function set_route( $route ) {
                $this->route = $route;
            }

            public function set_body( $body ) {
                $this->body = (string) $body;
            }

            public function get_body() {
                return $this->body;
            }

            public function set_header( $key, $value ) {
                $this->headers[ strtolower( $key ) ] = $value;
            }

            public function get_header( $key ) {
                return $this->headers[ strtolower( $key ) ] ?? null;
            }

            public function offsetExists( $offset ): bool {
                return isset( $this->params[ $offset ] );
            }

            public function offsetGet( $offset ): mixed {
                return $this->params[ $offset ] ?? null;
            }

            public function offsetSet( $offset, $value ): void {
                $this->params[ $offset ] = $value;
            }

            public function offsetUnset( $offset ): void {
                unset( $this->params[ $offset ] );
            }

            public function get_attributes() {
                return [];
            }
        }
        ');
    }

    if ( ! class_exists( 'WP_REST_Response' ) ) {
        eval('
        class WP_REST_Response {
            private $data;
            private $status;
            private $headers = [];

            public function __construct( $data = null, $status = 200, $headers = [] ) {
                $this->data    = $data;
                $this->status  = $status;
                $this->headers = $headers;
            }

            public function header( $key, $value ) {
                $this->headers[ $key ] = $value;
            }

            public function get_status() {
                return $this->status;
            }

            public function get_data() {
                return $this->data;
            }

            public function get_headers() {
                return $this->headers;
            }

            public function is_error() {
                return $this->get_status() >= 400;
            }
        }
        ');
    }

    if ( ! function_exists( 'register_rest_route' ) ) {
        function register_rest_route( string $namespace, string $route, array $args = array(), bool $override = false ): bool {
            $GLOBALS['wp_stubs_registered_rest_routes'][ $namespace ][ $route ] = $args;
            return true;
        }
    }

    if ( ! class_exists( 'WP_REST_Controller' ) ) {
        eval('
        class WP_REST_Controller {
            public function prepare_response_for_collection( $response ) {
                return $response;
            }
        }
        ');
    }

    if ( ! class_exists( 'WP_REST_Server' ) ) {
        eval('
        class WP_REST_Server {
            const READABLE = "GET";
            const CREATABLE = "POST";
            const EDITABLE = "POST, PUT, PATCH";
            const DELETABLE = "DELETE";
            const ALLMETHODS = "GET, POST, PUT, PATCH, DELETE";

            public function send_header( $key, $value ) {}
            public function send_headers( $headers ) {}
            public function serve_request( $path = null ) {}
        }
        ');
    }
}

namespace Racketmanager {

    use Racketmanager\Domain\Competition\Event;
    use ReflectionClass;
    use ReflectionException;
    use stdClass;

    if ( ! function_exists( 'Racketmanager\seo_url' ) ) {
        function seo_url( string $string_field ): string {
            $string_field = strtolower( $string_field );
            $string_field = preg_replace( '/[^a-z0-9_\s-]/', '', $string_field );
            $string_field = preg_replace( '/\s+/', ' ', $string_field );
            $string_field = str_replace( '-', '_', $string_field );
            return preg_replace( '/\s/', '-', $string_field );
        }
    }

    if ( ! function_exists( 'Racketmanager\un_seo_url' ) ) {
        function un_seo_url( string $string_field ): string {
            $string_field = str_replace( '-', ' ', $string_field );
            $string_field = str_replace( '_', '-', $string_field );
            return $string_field;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_league' ) ) {
        function get_league( $id = null ) {
            if ( null === $id ) {
                return $GLOBALS['league'] ?? null;
            }
            if ( isset( $GLOBALS['wp_stubs_leagues'][ $id ] ) ) {
                return $GLOBALS['wp_stubs_leagues'][ $id ];
            }
            return null;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_match' ) ) {
        function get_match( $id ) {
            if ( isset( $GLOBALS['wp_stubs_matches'][ $id ] ) ) {
                return $GLOBALS['wp_stubs_matches'][ $id ];
            }
            return null;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_rubber' ) ) {
        function get_rubber( $id ) {
            if ( isset( $GLOBALS['wp_stubs_rubbers'][ $id ] ) ) {
                return $GLOBALS['wp_stubs_rubbers'][ $id ];
            }
            return null;
        }
    }


    if ( ! function_exists( 'Racketmanager\get_team' ) ) {
        function get_team( $id ) {
            if ( isset( $GLOBALS['wp_stubs_teams'][ $id ] ) ) {
                return $GLOBALS['wp_stubs_teams'][ $id ];
            }
            return null;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_club' ) ) {
        function get_club( $id ) {
            if ( isset( $GLOBALS['wp_stubs_clubs'][ $id ] ) ) {
                return $GLOBALS['wp_stubs_clubs'][ $id ];
            }
            if ( isset( $GLOBALS['wp_stubs_clubs'][ (string) $id ] ) ) {
                return $GLOBALS['wp_stubs_clubs'][ (string) $id ];
            }
            return $GLOBALS['wp_stubs_clubs']['default'] ?? null;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_player' ) ) {
        function get_player( $id, $search = 'id' ) {
            if ( isset( $GLOBALS['wp_stubs_players'][ $search ][ $id ] ) ) {
                return $GLOBALS['wp_stubs_players'][ $search ][ $id ];
            }
            if ( $search === 'email' ) {
                return null;
            }
            if ( isset( $GLOBALS['wp_stubs_players'][ $id ] ) ) {
                return $GLOBALS['wp_stubs_players'][ $id ];
            }
            return $GLOBALS['wp_stubs_players']['default'] ?? null;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_user' ) ) {
        function get_user( $id ) {
            if ( isset( $GLOBALS['wp_stubs_users'][ $id ] ) ) {
                return $GLOBALS['wp_stubs_users'][ $id ];
            }
            return $GLOBALS['wp_stubs_users']['default'] ?? null;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_club_role' ) ) {
        function get_club_role( $id ) {
            if ( isset( $GLOBALS['wp_stubs_club_roles'][ $id ] ) ) {
                return $GLOBALS['wp_stubs_club_roles'][ $id ];
            }
            return $GLOBALS['wp_stubs_club_roles']['default'] ?? null;
        }
    }

    if ( ! function_exists( 'Racketmanager\show_club_role_modal' ) ) {
        function show_club_role_modal( $club_role_id, array $args = array() ): string {
            return 'club-role-modal:' . (int) $club_role_id . ':' . ( $args['modal'] ?? '' );
        }
    }

    if ( ! function_exists( 'Racketmanager\show_purchase_order_modal' ) ) {
        function show_purchase_order_modal( $invoice_id, array $args = array() ): string {
            return 'purchase-order-modal:' . (int) $invoice_id;
        }
    }

    if ( ! function_exists( 'Racketmanager\show_invoice' ) ) {
        function show_invoice( $invoice_id, array $args = array() ): string {
            return 'invoice:' . (int) $invoice_id;
        }
    }

    if ( ! function_exists( 'Racketmanager\show_message' ) ) {
        function show_message( $message_id ): string {
            return 'message-content:' . (int) $message_id;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_message' ) ) {
        function get_message( $message_id ) {
            if ( isset( $GLOBALS['wp_stubs_messages'][ $message_id ] ) ) {
                return $GLOBALS['wp_stubs_messages'][ $message_id ];
            }
            return $GLOBALS['wp_stubs_messages']['default'] ?? null;
        }
    }

    if ( ! function_exists( 'Racketmanager\tournament_withdrawal_modal' ) ) {
        function tournament_withdrawal_modal( $tournament_id, array $args = array() ): string {
            return 'tournament-withdrawal-modal:' . (int) $tournament_id;
        }
    }

    if ( ! function_exists( 'Racketmanager\event_partner_modal' ) ) {
        function event_partner_modal( $event_id, array $args = array() ): string {
            if ( isset( $GLOBALS['racketmanager_event_partner_modal_callback'] ) ) {
                return ( $GLOBALS['racketmanager_event_partner_modal_callback'] )( $event_id, $args );
            }
            return 'event-partner-modal:' . (int) $event_id;
        }
    }

    if ( ! function_exists( 'Racketmanager\result_notification' ) ) {
        function result_notification( $match_id, $args = array() ) {
            return 'Result notification for match ' . $match_id;
        }
    }

    if ( ! function_exists( 'Racketmanager\captain_result_notification' ) ) {
        function captain_result_notification( $match_id, $args = array() ) {
            return 'Captain result notification for match ' . $match_id;
        }
    }

    if ( ! function_exists( 'Racketmanager\match_team_withdrawn_notification' ) ) {
        function match_team_withdrawn_notification( $match_id, $args = array() ) {
            return 'Team withdrawn notification for match ' . $match_id;
        }
    }

    if ( ! function_exists( 'Racketmanager\get_event' ) ) {
        /**
         * Stub for get_event() in test environments.
         *
         * @param int|string|Event|null $event_id
         * @param string                $search_term
         * @return Event|null
         */
        function get_event( int|string|Event|null $event_id = null, string $search_term = 'id' ): ?Event {
            if ( $event_id instanceof Event ) {
                return $event_id;
            }

            if ( array_key_exists( $event_id, $GLOBALS['wp_stubs_events'] ?? [] ) ) {
                return $GLOBALS['wp_stubs_events'][ $event_id ];
            }

            if ( empty( $event_id ) ) {
                return null;
            }

            return new class( (int) $event_id ) extends Event {
                private const DEFAULT_SEASON = '2024-25';

                /**
                 * @noinspection PhpMissingParentConstructorInspection
                 */
                public function __construct( int $id ) {
                    $this->id   = $id;
                    $this->name = 'Test Event';
                    $this->type = 'doubles';

                    $default_season = [ 'name' => self::DEFAULT_SEASON ];

                    $this->competition = (object) [
                        'settings'        => new \Racketmanager\Domain\Competition\Competition_Settings( [
                            'point_rule' => 'three',
                            'mode'       => 'league',
                            'sport'      => 'tennis',
                        ] ),
                        'type'            => \Racketmanager\Domain\Competition\Competition_Type::LEAGUE,
                        'is_player_entry' => false,
                        'standings'       => [],
                        'current_season'  => $default_season,
                    ];

                    $this->seasons     = json_encode( [ $default_season ] );
                    $this->num_sets    = 3;
                    $this->num_rubbers = 6;
                    $this->standings   = [];
                }

                public function get_num_sets(): int {
                    return $this->num_sets;
                }

                public function get_num_rubbers(): ?int {
                    return $this->num_rubbers;
                }

                public function get_season_event( $season = false, bool $index = false ): array|false|string {
                    return self::DEFAULT_SEASON;
                }

                public function get_seasons(): array {
                    return [ [ 'name' => self::DEFAULT_SEASON ] ];
                }

                public function set_num_leagues( bool $total = false ): void {}

                public function set_season( ?string $season = null, bool $force_overwrite = false ): void {}
            };
        }
    }

    if ( ! function_exists( 'Racketmanager\show_standings' ) ) {
        function show_standings( string $key ): bool {
            global $league;
            if ( isset( $league->standings[ $key ] ) && 1 === (int) $league->standings[ $key ] ) {
                return true;
            }
            return false;
        }
    }
}

namespace Racketmanager {

    function match_header( int $match_id, array $args = array() ): string {
        return "match header content";
    }

    if ( ! function_exists( 'Racketmanager\get_match' ) ) {
        function get_match( $match_id ): object {
            if ( isset( $GLOBALS['wp_stubs_matches'][ $match_id ] ) ) {
                return $GLOBALS['wp_stubs_matches'][ $match_id ];
            }
            $match        = new stdClass();
            $match->id    = $match_id;
            $match->date  = '2023-01-01';
            $match->link  = 'https://example.com';
            $match->home_team = 'Home';
            $match->away_team = 'Away';
            $match->season = '2023';
            $match->match_day = 1;
            $match->league = new stdClass();
            $match->league->event = new stdClass();
            $match->league->event->get_season_by_name = function($season) {
                return ['match_dates' => ['2023-01-01']];
            };
            // Mocking method call on object
            return new class($match) {
                private array $properties = [];
                public function __construct($match) {
                    foreach(get_object_vars($match) as $k => $v) {
                        $this->properties[$k] = $v;
                    }
                }
                public function __get($name) {
                    return $this->properties[$name] ?? null;
                }
                public function __isset($name) {
                    return isset($this->properties[$name]);
                }
                public function get_season_by_name($name): array { return [ 'match_dates' => ['2023-01-01']]; }
            };
        }
    }
}

namespace {
    if ( ! function_exists( 'wp_stubs_reset_export_rest_globals' ) ) {
        /**
         * Reset global state tracked by export / REST / admin-referer stubs so tests are order-independent.
         */
        function wp_stubs_reset_export_rest_globals(): void {
            unset(
                $GLOBALS['wp_stubs_last_rest_request'],
                $GLOBALS['wp_stubs_rest_do_request_return'],
                $GLOBALS['wp_stubs_rest_do_request_callback'],
                $GLOBALS['wp_stubs_is_admin'],
                $GLOBALS['wp_stubs_last_check_admin_referer'],
                $GLOBALS['wp_stubs_check_admin_referer_return'],
                $GLOBALS['wp_stubs_last_wp_die'],
                $GLOBALS['wp_stubs_current_user_can']
            );
        }
    }
    if ( ! function_exists( 'check_admin_referer' ) ) {
        function check_admin_referer( $action = -1, $query_arg = '_wpnonce' ) {
            $GLOBALS['wp_stubs_last_check_admin_referer'] = array(
                'action'    => $action,
                'query_arg' => $query_arg,
            );
            if ( isset( $GLOBALS['wp_stubs_check_admin_referer_return'] ) ) {
                return $GLOBALS['wp_stubs_check_admin_referer_return'];
            }
            return 1;
        }
    }
    if ( ! function_exists( 'wp_register_script' ) ) {
        function wp_register_script( $handle, $src, $deps = array(), $ver = false, $args = array() ) {}
    }
    if ( ! function_exists( 'wp_enqueue_script' ) ) {
        function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $args = array() ) {}
    }
    if ( ! function_exists( 'plugins_url' ) ) {
        function plugins_url( $path = '', $plugin = '' ) { return 'https://test.local/plugins/' . $path; }
    }
    if ( ! function_exists( 'site_url' ) ) {
        function site_url( $path = '', $scheme = null ) {
            return 'https://example.com' . ( $path ? '/' . ltrim( (string) $path, '/' ) : '' );
        }
    }
    if ( ! function_exists( 'wp_login_url' ) ) {
        function wp_login_url( $redirect = '', $force_reauth = false ) {
            $login_url = 'https://example.com/wp-login.php';
            if ( ! empty( $redirect ) ) {
                $login_url .= '?redirect_to=' . urlencode( (string) $redirect );
            }
            return $login_url;
        }
    }
    if ( ! function_exists( 'wp_registration_url' ) ) {
        function wp_registration_url() {
            return 'https://example.com/wp-login.php?action=register';
        }
    }
    if ( ! function_exists( 'wp_lostpassword_url' ) ) {
        function wp_lostpassword_url( $redirect = '' ) {
            $url = 'https://example.com/wp-login.php?action=lostpassword';
            if ( ! empty( $redirect ) ) {
                $url .= '&redirect_to=' . urlencode( (string) $redirect );
            }
            return $url;
        }
    }
    if ( ! function_exists( 'wp_get_password_hint' ) ) {
        function wp_get_password_hint() {
            return 'Hint: The password should be at least twelve characters long.';
        }
    }
    if ( ! function_exists( 'wp_kses' ) ) {
        function wp_kses( $string, $allowed_html, $allowed_protocols = array() ) {
            return (string) $string;
        }
    }
    if ( ! function_exists( 'get_filesystem_method' ) ) {
        function get_filesystem_method( $args = array(), $context = '', $allow_relaxed_file_ownership = false ) {
            return $GLOBALS['wp_stubs_filesystem_method'] ?? 'direct';
        }
    }
    if ( ! function_exists( 'request_filesystem_credentials' ) ) {
        function request_filesystem_credentials( $form_post, $type = '', $error = false, $context = '', $extra_fields = null, $allow_relaxed_file_ownership = false ) {
            return $GLOBALS['wp_stubs_filesystem_credentials'] ?? true;
        }
    }
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        function WP_Filesystem( $args = false, $context = false, $allow_relaxed_file_ownership = false ) {
            return $GLOBALS['wp_stubs_wp_filesystem_return'] ?? true;
        }
    }
    if ( ! function_exists( 'wp_remote_get' ) ) {
        function wp_remote_get( $url, $args = array() ) {
            if ( isset( $GLOBALS['wp_stubs_remote_get_callback'] ) && is_callable( $GLOBALS['wp_stubs_remote_get_callback'] ) ) {
                return ( $GLOBALS['wp_stubs_remote_get_callback'] )( $url, $args );
            }
            if ( isset( $GLOBALS['wp_stubs_remote_get_return'][ $url ] ) ) {
                return $GLOBALS['wp_stubs_remote_get_return'][ $url ];
            }
            return $GLOBALS['wp_stubs_remote_get_default'] ?? array( 'response' => array( 'code' => 200 ), 'body' => '', 'cookies' => array() );
        }
    }
    if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
        function wp_remote_retrieve_response_code( $response ) {
            if ( is_wp_error( $response ) || ! is_array( $response ) ) {
                return '';
            }
            return $response['response']['code'] ?? 200;
        }
    }
    if ( ! function_exists( 'wp_remote_retrieve_cookies' ) ) {
        function wp_remote_retrieve_cookies( $response ) {
            if ( is_wp_error( $response ) || ! is_array( $response ) ) {
                return array();
            }
            return $response['cookies'] ?? array();
        }
    }
    if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
        function wp_remote_retrieve_body( $response ) {
            if ( is_wp_error( $response ) || ! is_array( $response ) ) {
                return '';
            }
            return $response['body'] ?? '';
        }
    }
    if ( ! defined( 'RACKETMANAGER_VERSION' ) ) {
        define( 'RACKETMANAGER_VERSION', '1.0.0' );
    }
    if ( ! defined( 'WP_CONTENT_DIR' ) ) {
        define( 'WP_CONTENT_DIR', sys_get_temp_dir() . '/wp-content' );
    }
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', sys_get_temp_dir() . '/wordpress/' );
    }
}
