<?php
declare(strict_types=1);

namespace Racketmanager {
    if ( ! function_exists( 'Racketmanager\show_alert' ) ) {
        function show_alert( string $message, string $type = 'info' ): string {
            return sprintf( "<div class='alert alert-%s'>%s</div>", $type, $message );
        }
    }

    if ( ! class_exists( 'Racketmanager\RacketManager' ) ) {
        class RacketManager {
            public static $container;
            public $shortcodes;
            public array $options = [];

            public function __construct() {
                $this->shortcodes = new class {
                    public function load_template( string $slug, array $args = [], string $sub_directory = '' ): string {
                        return 'Template: ' . $slug;
                    }
                };
                self::$container  = new class {
                    public function get( string $id ): object {
                        try {
                            switch ( $id ) {
                                case 'competition_service':
                                    return (new \ReflectionClass( 'Racketmanager\Services\Competition_Service' ))->newInstanceWithoutConstructor();
                                case 'club_service':
                                    return (new \ReflectionClass( 'Racketmanager\Services\Club_Service' ))->newInstanceWithoutConstructor();
                                case 'player_service':
                                    return (new \ReflectionClass( 'Racketmanager\Services\Player_Service' ))->newInstanceWithoutConstructor();
                                case 'registration_service':
                                    return (new \ReflectionClass( 'Racketmanager\Services\Registration_Service' ))->newInstanceWithoutConstructor();
                                case 'event_service':
                                    return (new \ReflectionClass( 'Racketmanager\Services\Event_Service' ))->newInstanceWithoutConstructor();
                                case 'league_service':
                                    return (new \ReflectionClass( 'Racketmanager\Services\League_Service' ))->newInstanceWithoutConstructor();
                                case 'fixture_service':
                                    return (new \ReflectionClass( 'Racketmanager\Services\Fixture_Service' ))->newInstanceWithoutConstructor();
                                case 'team_service':
                                    return (new \ReflectionClass( 'Racketmanager\Services\Team_Service' ))->newInstanceWithoutConstructor();
                            }
                        } catch ( \ReflectionException $e ) {
                        }
                        return new \stdClass();
                    }
                };
            }

            public function get_options(): array {
                return [
                    'league'     => [
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

            public function set_message( string $message ): void {}
            public function load_options(): void {
                $this->options = [];
                $this->date_format = 'Y-m-d';
            }
        }
    }
}

namespace {
    function maybe_unserialize( $data ) {
        if ( is_string( $data ) && preg_match( '/^([adObis]):/', $data ) ) {
            return unserialize( $data );
        }
        return $data;
    }

    if ( ! function_exists( 'get_option' ) ) {
        function get_option( $option, $default = false ) {
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
        class wpdb {
            public $prefix = 'wp_';
            public $racketmanager_teams = 'wp_racketmanager_teams';
            public $racketmanager_league_teams = 'wp_racketmanager_league_teams';
            public $racketmanager_results_checker = 'wp_racketmanager_results_checker';
            public $racketmanager_matches = 'wp_racketmanager_matches';
            public $get_results_callback;
            public $get_row_callback;
            public $update_callback;
            public $insert_id = 0;
            private $data = [];
            private $last_id = 0;

            public function prepare( $query, ...$args ) {
                if ( empty($args) ) return $query;
                if ( is_array($args[0]) && count($args) === 1 ) $args = $args[0];
                
                $prepared_args = [];
                foreach ($args as $arg) {
                    if (is_string($arg)) {
                        $prepared_args[] = "'" . addslashes($arg) . "'";
                    } else {
                        $prepared_args[] = $arg;
                    }
                }

                $query = str_replace( "'%s'", '%s', $query );
                $query = str_replace( '%d', '%s', $query );
                $query = str_replace( '%f', '%s', $query );
                
                try {
                    return vsprintf( $query, $prepared_args );
                } catch (\Throwable $e) {
                    return $query;
                }
            }
            public function get_row( $query, $output = 'OBJECT' ) { 
                if ( $this->get_row_callback ) return ($this->get_row_callback)($query);
                $results = $this->get_results($query, $output);
                return !empty($results) ? $results[0] : null;
            }
            public function get_results( $query, $output = 'OBJECT' ) { 
                if ( $this->get_results_callback ) return ($this->get_results_callback)($query);
                
                // Very simple mock query parser for tests
                if (preg_match('/SELECT \* FROM (\w+) WHERE `id` = (\d+)/', $query, $matches)) {
                    $table = $matches[1];
                    $id = (int)$matches[2];
                    $found = array_filter($this->data[$table] ?? [], fn($row) => $row->id == $id);
                    return array_values($found);
                }
                if (preg_match('/SELECT \* FROM (\w+) WHERE `match_id` = (\d+)/', $query, $matches)) {
                    $table = $matches[1];
                    $match_id = (int)$matches[2];
                    $found = array_filter($this->data[$table] ?? [], fn($row) => ($row->match_id ?? null) == $match_id);
                    return array_values($found);
                }
                
                return array(); 
            }
            public function get_var( $query, $x = 0, $y = 0 ) { 
                if (preg_match('/SELECT count\(\*\) FROM (\w+) WHERE match_id = (\d+)/', $query, $matches)) {
                    $table = $matches[1];
                    $match_id = (int)$matches[2];
                    return count(array_filter($this->data[$table] ?? [], fn($row) => ($row->match_id ?? null) == $match_id));
                }
                if (preg_match('/SELECT count\(\*\) FROM (\w+) WHERE `match_id` = (\d+)/', $query, $matches)) {
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
                if (isset($where['id'])) {
                    foreach ($this->data[$table] ?? [] as $row) {
                        if ($row->id == $where['id']) {
                            foreach ($data as $k => $v) $row->$k = $v;
                        }
                    }
                }
                return true; 
            }
            public function delete( $table, $where, $where_format = null ) { 
                if (isset($where['match_id'])) {
                    $this->data[$table] = array_filter($this->data[$table] ?? [], fn($row) => ($row->match_id ?? null) != $where['match_id']);
                }
                return true; 
            }
            public function show_errors() {}
            public function hide_errors() {}
        }
    }

    if ( ! isset( $GLOBALS['wpdb'] ) ) {
        $GLOBALS['wpdb'] = new wpdb();
    }

    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', __DIR__ . '/' );
    }

    if ( ! function_exists( 'add_action' ) ) {
        function add_action( $tag, $function_to_add, $priority = 10, $accepted_args = 1 ) {
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

    if ( ! function_exists( 'sanitize_text_field' ) ) {
        function sanitize_text_field( $value ) {
            return is_string( $value ) ? trim( strip_tags( $value ) ) : $value;
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
                $pairs[] = rawurlencode( (string) $k ) . '=' . rawurlencode( (string) $v );
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

    if ( ! function_exists( 'current_time' ) ) {
        function current_time( string $type, $gmt = 0 ): string {
            return date( 'Y-m-d H:i:s' );
        }
    }

    if ( ! function_exists( 'show_alert' ) ) {
        function show_alert( string $message, string $type = 'info' ): void {
        }
    }

    if ( ! function_exists( 'is_user_logged_in' ) ) {
        function is_user_logged_in(): bool {
            return ( $GLOBALS['wp_stubs_get_current_user_id'] ?? 1 ) > 0;
        }
    }

    if ( ! function_exists( 'get_current_user_id' ) ) {
        function get_current_user_id(): int {
            return $GLOBALS['wp_stubs_get_current_user_id'] ?? 1;
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

    if ( ! function_exists( 'wp_die' ) ) {
        function wp_die( $message = '', $title = '', $args = array() ) {
            die( $message );
        }
    }

    if ( ! function_exists( 'status_header' ) ) {
        function status_header( $code, $description = '' ) {}
    }

    if ( ! function_exists( 'wp_send_json_success' ) ) {
        function wp_send_json_success( $data = null, $status_code = null, $options = 0 ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            echo json_encode( array( 'success' => true, 'data' => $data ) );
            die();
        }
    }

    if ( ! function_exists( 'wp_send_json_error' ) ) {
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
        function is_admin() {
            return false;
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

    if ( ! function_exists( 'wp_cache_delete' ) ) {
        function wp_cache_delete( $id, $group = '' ) {
            return true;
        }
    }

    if ( ! function_exists( 'esc_url_raw' ) ) {
        function esc_url_raw( $url ) {
            return $url;
        }
    }

    if ( ! function_exists( 'esc_url' ) ) {
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

    if ( ! function_exists( '__' ) ) {
        function __( string $text, string $domain = '' ): string {
            return $text;
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
                    public function get( string $id ): object {
                        $reflection = null;
                        try {
                            switch ( $id ) {
                                case 'competition_service':
                                    $reflection = new \ReflectionClass( 'Racketmanager\Services\Competition_Service' );
                                    break;
                                case 'club_service':
                                    $reflection = new \ReflectionClass( 'Racketmanager\Services\Club_Service' );
                                    break;
                                case 'player_service':
                                    $reflection = new \ReflectionClass( 'Racketmanager\Services\Player_Service' );
                                    break;
                                case 'registration_service':
                                    $reflection = new \ReflectionClass( 'Racketmanager\Services\Registration_Service' );
                                    break;
                            }
                        } catch ( \ReflectionException ) {
                            return new \stdClass();
                        }

                        if ( $reflection ) {
                            return $reflection->newInstanceWithoutConstructor();
                        }

                        return new \stdClass();
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
        class WP_Error {
            public array $errors = array();

            public function __construct( $code = '', $message = '', $data = '' ) {
                if ( ! empty( $code ) ) {
                    $this->add( $code, $message, $data );
                }
            }

            public function add( $code, $message, $data = '' ): void {
                $this->errors[ $code ][] = $message;
            }

            public function get_error_messages( $code = '' ) {
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

            public function get_error_message( $code = '' ): mixed {
                $messages = $this->get_error_messages( $code );
                return $messages[0] ?? '';
            }
        }
    }

    if ( ! function_exists( 'is_wp_error' ) ) {
        function is_wp_error( $thing ): bool {
            return $thing instanceof WP_Error;
        }
    }
}

namespace Racketmanager {
    if ( ! function_exists( 'Racketmanager\seo_url' ) ) {
        function seo_url( string $string_field ): string {
            $string_field = strtolower( $string_field );
            $string_field = preg_replace( '/[^a-z0-9_\s-]/', '', $string_field );
            $string_field = preg_replace( '/\s+/', ' ', $string_field );
            $string_field = str_replace( '-', '_', $string_field );
            return preg_replace( '/\s/', '-', $string_field );
        }
    }

    if ( ! function_exists( 'Racketmanager\get_league' ) ) {
        function get_league( $id ) {
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
        function get_event( $event_id ) {
            return new class((object)['id'=>$event_id]) extends \Racketmanager\Domain\Competition\Event {
                public function __construct($obj) {
                    $this->id = $obj->id;
                    $comp = new \stdClass();
                    $comp->settings = ['point_rule' => 'three', 'mode' => 'league'];
                    $comp->type = 'league';
                    $comp->is_player_entry = false;
                    $comp->standings = [];
                    $comp->current_season = ['name' => '2024-25'];
                    $this->competition = $comp;
                    $this->seasons = json_encode([['name' => '2024-25']]);
                    $this->name = 'Test Event';
                }
                public function get_num_sets(): int { return 3; }
                public function get_num_rubbers(): ?int { return 6; }
                public function get_season_event($season = false, bool $index = false): array|false|string { return '2024-25'; }
                public function get_seasons(): array { return [['name' => '2024-25']]; }
                public function set_num_leagues(bool $total = false): void {}
                public function set_season(?string $season = null, bool $force = false): void {}
            };
        }
    }

    if ( ! function_exists( 'Racketmanager\get_league' ) ) {
        function get_league( $league_id ) {
            try {
                $league = (new \ReflectionClass( 'Racketmanager\Domain\Competition\League' ))->newInstanceWithoutConstructor();
                $league->id = $league_id;
                return $league;
            } catch ( \ReflectionException $e ) {
                return (object) [ 'id' => $league_id ];
            }
        }
    }
}
