<?php
/**
 * RacketManager standalone functions
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

use Racketmanager\Domain\Charge;
use Racketmanager\Domain\Club;
use Racketmanager\Domain\Club_Player;
use Racketmanager\Domain\Club_Role;
use Racketmanager\Domain\Competition;
use Racketmanager\Domain\Event;
use Racketmanager\Domain\Invoice;
use Racketmanager\Domain\League;
use Racketmanager\Domain\League_Team;
use Racketmanager\Domain\Message;
use Racketmanager\Domain\Player;
use Racketmanager\Domain\Player_Error;
use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Domain\Results_Checker;
use Racketmanager\Domain\Results_Report;
use Racketmanager\Domain\Rubber;
use Racketmanager\Domain\Season;
use Racketmanager\Domain\Team;
use Racketmanager\Domain\Tournament;
use Racketmanager\Domain\Tournament_Entry;
use Racketmanager\Domain\User;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Services\Exporter;

/**
 * Send debug code to the Javascript console
 *
 * @param object|array|string|null $data Optional message that will be sent the error_log before the backtrace.
 */
function debug_to_console( object|array|string|null $data ): void {
    if ( is_array( $data ) || is_object( $data ) ) {
        if ( is_array( $data ) ) {
            error_log( 'PHP: array' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        } else {
            error_log( 'PHP: object' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
        if ( ! wp_doing_ajax() ) {
            echo "<script>console.log('PHP: " . wp_json_encode( $data ) . "');</script>";
        }
        error_log( 'PHP: "' . wp_json_encode( $data ) . '"' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    } else {
        if ( ! wp_doing_ajax() ) {
            echo "<script>console.log( 'PHP: " . esc_html( $data ) . "' ) ;</script>";
        }
        error_log( 'PHP: "' . $data . '"' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}

/**
 * Send the output from a backtrace to the error_log
 *
 * @param string $message Optional message that will be sent the error_log before the backtrace.
 */
function log_trace( string $message = '' ): void {
    $trace = debug_backtrace(); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
    if ( $message ) {
        error_log( $message ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
    $caller        = array_shift( $trace );
    $function_name = $caller['function'];
    error_log( sprintf( '%s: Called from %s:%s', $function_name, $caller['file'], $caller['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    foreach ( $trace as $entry_id => $entry ) {
        $entry['file'] = $entry['file'] ?? '-';
        $entry['line'] = $entry['line'] ?? '-';
        if ( empty( $entry['class'] ) ) {
            error_log( sprintf( '%s %3s. %s() %s:%s', $function_name, $entry_id + 1, $entry['function'], $entry['file'], $entry['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        } else {
            error_log( sprintf( '%s %3s. %s->%s() %s:%s', $function_name, $entry_id + 1, $entry['class'], $entry['function'], $entry['file'], $entry['line'] ) ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }
}
/**
 * Create SEO friendly string
 *
 * @param string $string_field query string.
 */
function seo_url( string $string_field ): string {
    // Lower case everything.
    $string_field = strtolower( $string_field );
    // Make alphanumeric (removes all other characters).
    $string_field = preg_replace( '/[^a-z0-9_\s-]/', '', $string_field );
    // Clean up multiple whitespaces.
    $string_field = preg_replace( '/\s+/', ' ', $string_field );
    // Convert dash to underscore.
    $string_field = str_replace( '-', '_', $string_field );
    // Convert whitespaces to dash.
    return preg_replace( '/\s/', '-', $string_field );
}
/**
 * Reverses SEO friendly string
 *
 * @param string $string_field query string.
 */
function un_seo_url( string $string_field ): string {
    // Convert dash to whitespaces.
    $string_field = str_replace( '-', ' ', $string_field );
    // Convert underscore to dash.
    $string_field = str_replace( '_', '-', $string_field );
    // Lower case everything.
    return strtolower( $string_field );
}
/**
 * Create formatted url
 */
function create_new_url_querystring(): void {
    add_rewrite_tag( '%competition_name%', '(.+?)' );
    add_rewrite_tag( '%competition_type%', '(.+?)' );
    add_rewrite_tag( '%round%', '(.+?)' );
    add_rewrite_tag( '%league_name%', '(.+?)' );
    add_rewrite_tag( '%league_id%', '([^/]*)' );
    add_rewrite_tag( '%match_id%', '([^/]*)' );
    add_rewrite_tag( '%season%', '([0-9]{4})' );
    add_rewrite_tag( '%match_day%', '([0-9]{1,2})' );
    add_rewrite_tag( '%team%', '(.+?)' );
    add_rewrite_tag( '%teamHome%', '(.+?)' );
    add_rewrite_tag( '%teamAway%', '(.+?)' );
    add_rewrite_tag( '%club_name%', '(.+?)' );
    add_rewrite_tag( '%match_date%', '(.+?)' );
    add_rewrite_tag( '%type%', '(.+?)' );
    add_rewrite_tag( '%tournament%', '(.+?)' );
    add_rewrite_tag( '%player_id%', '(.+?)' );
    add_rewrite_tag( '%id%', '(.+?)' );
    add_rewrite_tag( '%days%', '([0-9]{1,3})' );
    add_rewrite_tag( '%event%', '(.+?)' );
    add_rewrite_tag( '%draw%', '(.+?)' );
    add_rewrite_tag( '%tab%', '(.+?)' );
    add_rewrite_tag( '%player%', '(.+?)' );
    add_rewrite_tag( '%leg%', '([0-9]{1})' );
    add_rewrite_tag( '%action%', '(.+?)' );
    add_rewrite_tag( '%competition%', '(.+?)' );
    add_rewrite_tag( '%btm%', '([0-9]+)' );
    add_rewrite_tag( '%invoice%', '([0-9]+)' );
    add_rewrite_tag( '%age_group%', '(.+?)' );
}
add_action( 'init', 'Racketmanager\create_new_url_querystring' );

/**
 * Create calendar download
 */
function racketmanager_download(): void {
    if ( isset( $_GET['racketmanager_export'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $exporter = new Exporter();
        if ( 'calendar' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $exporter->calendar();
        } elseif ( 'fixtures' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $exporter->fixtures();
        } elseif ( 'results' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $exporter->results();
        } elseif ( 'report_results' === $_GET['racketmanager_export'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $exporter->report_results();
        } else {
            esc_html_e( 'Export function not found', 'racketmanager' );
            exit();
        }
    }
}
add_action( 'init', 'Racketmanager\racketmanager_download' );
/**
 * Get current page url
 */
function wp_get_current_url(): ?string {
    if ( isset( $_SERVER['REQUEST_URI'] ) ) {
        return home_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
    } else {
        return null;
    }
}

/**
 * Get Club object
 *
 * @param object|int|string|null $club Club ID or club object. Defaults to global $club.
 * @param string $search_term type of search.
 *
 * @return Club|null club|null
 */
function get_club( object|int|string|null $club = null, string $search_term = 'id' ): Club|null {
    if ( empty( $club ) && isset( $GLOBALS['club'] ) ) {
        $club = $GLOBALS['club'];
    }
    if ( $club instanceof Club ) {
        $_club = $club;
    } elseif ( is_object( $club ) ) {
        $_club = new Club( $club );
    } else {
        $club_repository = new Club_Repository();
        $_club           = $club_repository->find( $club, $search_term );
    }
    if ( ! $_club ) {
        return null;
    } else {
        return $_club;
    }
}

/**
 * Get Competition object
 *
 * @param Competition|int|string|null $competition Competition ID or competition object. Defaults to global $competition.
 * @param string|null $search_term type of search.
 *
 * @return Competition|null competition|null
 */
function get_competition( Competition|int|string|null $competition = null, ?string $search_term = 'id' ): ?Competition {
    if ( empty( $competition ) && isset( $GLOBALS['competition'] ) ) {
        $competition = $GLOBALS['competition'];
    }

    if ( $competition instanceof Competition ) {
        $_competition = $competition;
    } elseif ( is_object( $competition ) ) {
        // check if specific sports class exists.
        if ( ! isset( $competition->sport ) ) {
            $competition->sport = '';
        }
        $instance = 'Racketmanager\sports\Competition_' . ucfirst( $competition->sport );
        if ( class_exists( $instance ) ) {
            $_competition = new $instance( $competition );
        } else {
            $_competition = new Competition( $competition );
        }
    } else {
        $_competition = Competition::get_instance( $competition, $search_term );
    }

    if ( ! $_competition ) {
        return null;
    }

    return $_competition;
}

/**
 * Get Event object
 *
 * @param int|string|Event|null $event Event ID or event object. Defaults to global $event.
 * @param string $search_term type of search.
 *
 * @return Event|null event|null
 */
function get_event( int|string|Event|null $event = null, string $search_term = 'id' ): Event|null {
    if ( empty( $event ) && isset( $GLOBALS['event'] ) ) {
        $event = $GLOBALS['event'];
    }

    if ( $event instanceof Event ) {
        $_event = $event;
    } elseif ( is_object( $event ) ) {
        // check if specific sports class exists.
        if ( ! isset( $event->competition->sport ) ) {
            $event->competition->sport = '';
        }
        $instance = 'Racketmanager\sports\Event_' . ucfirst( $event->competition->sport );
        if ( class_exists( $instance ) ) {
            $_event = new $instance( $event );
        } else {
            $_event = new Event( $event );
        }
    } else {
        $_event = Event::get_instance( $event, $search_term );
    }

    if ( ! $_event ) {
        return null;
    }

    return $_event;
}

/**
 * Get League object
 *
 * @param object|int|string|null $league League ID or league object. Defaults to global $league.
 *
 * @return League|null League|null
 */
function get_league( object|int|string|null $league = null ): ?League {
    if ( empty( $league ) && isset( $GLOBALS['league'] ) ) {
        $league = $GLOBALS['league'];
    }
    if ( $league instanceof League ) {
        $_league = $league;
    } elseif ( is_object( $league ) ) {
        // check if specific sports class exists.
        if ( ! isset( $league->sport ) ) {
            $league->sport = '';
        }
        $instance = 'Racketmanager\sports\League_' . ucfirst( $league->sport );
        if ( class_exists( $instance ) ) {
            $_league = new $instance( $league );
        } else {
            $_league = new League( $league );
        }
    } else {
        $_league = League::get_instance( $league );
    }
    if ( ! $_league ) {
        return null;
    }
    return $_league;
}

/**
 * Get Racketmanager_Match object
 *
 * @param object|int|null $match Match ID or match object. Defaults to global $match.
 *
 * @return Racketmanager_Match|null Racketmanager_Match|null
 */
function get_match( object|int|null $match = null ): Racketmanager_Match|null {
    if ( empty( $match ) && isset( $GLOBALS['match'] ) ) {
        $match = $GLOBALS['match'];
    }

    if ( $match instanceof Racketmanager_Match ) {
        $_match = $match;
    } elseif ( is_object( $match ) ) {
        $_match = new Racketmanager_Match( $match );
    } else {
        $_match = Racketmanager_Match::get_instance( $match );
    }

    if ( ! $_match ) {
        return null;
    }

    return $_match;
}

/**
 * Get Rubber object
 *
 * @param object|int|null $rubber Rubber ID or rubber object. Defaults to global $rubber.
 *
 * @return Rubber|null Rubber|null
 */
function get_rubber( object|int|null $rubber = null ): Rubber|null {
    if ( empty( $rubber ) && isset( $GLOBALS['rubber'] ) ) {
        $rubber = $GLOBALS['rubber'];
    }

    if ( $rubber instanceof Rubber ) {
        $_rubber = $rubber;
    } elseif ( is_object( $rubber ) ) {
        $_rubber = new Rubber( $rubber );
    } else {
        $_rubber = Rubber::get_instance( $rubber );
    }

    if ( ! $_rubber ) {
        return null;
    }

    return $_rubber;
}

/**
 * Get Tournament object
 *
 * @param object|int|string|null $tournament Tournament ID or tournament object. Defaults to global $tournament.
 * @param string $search_term search term - defaults to id.
 *
 * @return Tournament|null tournament|null
 */
function get_tournament( object|int|string|null $tournament = null, string $search_term = 'id' ): Tournament|null {
    if ( empty( $tournament ) && isset( $GLOBALS['tournament'] ) ) {
        $tournament = $GLOBALS['tournament'];
    }

    if ( $tournament instanceof Tournament ) {
        $_tournament = $tournament;
    } elseif ( is_object( $tournament ) ) {
        $_tournament = new Tournament( $tournament );
    } else {
        $_tournament = Tournament::get_instance( $tournament, $search_term );
    }

    if ( ! $_tournament ) {
        return null;
    }

    return $_tournament;
}
/**
 * Get Tournament Entry object
 *
 * @param object|int|string|null $tournament_entry tournament entry ID or tournament entry object. Defaults to global $tournament.
 * @param string $search_term search term - defaults to id.
 *
 * @return object|null tournament|null
 */
function get_tournament_entry( object|int|string|null $tournament_entry = null, string $search_term = 'id' ): Tournament_Entry|null {
    if ( empty( $tournament_entry ) && isset( $GLOBALS['tournament_entry'] ) ) {
        $tournament_entry = $GLOBALS['tournament_entry'];
    }
    if ( $tournament_entry instanceof Tournament_Entry ) {
        $_tournament_entry = $tournament_entry;
    } elseif ( is_object( $tournament_entry ) ) {
        $_tournament_entry = new Tournament_Entry( $tournament_entry );
    } else {
        $_tournament_entry = Tournament_Entry::get_instance( $tournament_entry, $search_term );
    }
    if ( ! $_tournament_entry ) {
        return null;
    }
    return $_tournament_entry;
}
/**
 * Get Team object
 *
 * @param object|int|null|string $team Team ID or team object. Defaults to global $team.
 *
 * @return object|null Team|null
 */
function get_team( object|int|string|null $team = null ): ?object {
    if ( empty( $team ) && isset( $GLOBALS['team'] ) ) {
        $team = $GLOBALS['team'];
    }

    if ( $team instanceof Team ) {
        $_team = $team;
    } elseif ( is_object( $team ) ) {
        $_team = new Team( $team );
    } else {
        $_team = Team::get_instance( $team );
    }

    if ( ! $_team ) {
        return null;
    }

    return $_team;
}

/**
 * Get Player object
 *
 * @param int|string|null $player Player ID or player object. Defaults to global $player.
 * @param string $search_term search type term (defaults to id).
 *
 * @return object|null player|null
 */
function get_player( int|string|null $player = null, string $search_term = 'id' ): ?object {
    if ( empty( $player ) && isset( $GLOBALS['player'] ) ) {
        $player = $GLOBALS['player'];
    }
    if ( $player instanceof Player ) {
        $_player = $player;
    } else {
        $player_repository = new Player_Repository();
        $_player           = $player_repository->find( $player, $search_term );
    }
    if ( ! $_player ) {
        return null;
    }
    return $_player;
}

/**
 * Get User object
 *
 * @param object|int|string|null $user User ID or user object. Defaults to global $user.
 *
 * @return object|null user|null
 */
function get_user( object|int|string|null $user = null ): ?object {
    if ( empty( $user ) && isset( $GLOBALS['user'] ) ) {
        $user = $GLOBALS['user'];
    }
    if ( $user instanceof User ) {
        $_user = $user;
    } elseif ( is_object( $user ) ) {
        $_user = new User( $user );
    } else {
        $_user = User::get_instance( $user );
    }
    if ( ! $_user ) {
        return null;
    }
    return $_user;
}

/**
 * Get Charge object
 *
 * @param object|int|string|null $charges Charge ID or charges object. Defaults to global $charges.
 *
 * @return object|null charges|null
 */
function get_charge( object|int|string|null $charges = null ): ?object {
    if ( empty( $charges ) && isset( $GLOBALS['charges'] ) ) {
        $charges = $GLOBALS['charges'];
    }

    if ( $charges instanceof Charge ) {
        $_charges = $charges;
    } elseif ( is_object( $charges ) ) {
        $_charges = new Charge( $charges );
    } else {
        $_charges = Charge::get_instance( $charges );
    }

    if ( ! $_charges ) {
        return null;
    }

    return $_charges;
}

/**
 * Get LeagueTeam object
 *
 * @param object|int|null $league_team LeagueTeam ID or LeagueTeam object. Defaults to global $league_team.
 * @return object|null
 */
function get_league_team( object|int|null $league_team = null ): League_Team|null {
    if ( empty( $league_team ) && isset( $GLOBALS['league_team'] ) ) {
        $league_team = $GLOBALS['league_team'];
    }

    if ( $league_team instanceof League_Team ) {
        $_league_team = $league_team;
    } elseif ( is_object( $league_team ) ) {
        $_league_team = new League_Team( $league_team );
    } else {
        $_league_team = League_Team::get_instance( $league_team );
    }

    if ( ! $_league_team ) {
        return null;
    }

    return $_league_team;
}
/**
 * Get results report object
 *
 * @param object|int|null $results_report results_report ID or results_report object. Defaults to global $results_report.
 *
 * @return object|null results_report|null
 */
function get_results_report( object|int|null $results_report = null ): Results_Report|null {
    if ( empty( $results_report ) && isset( $GLOBALS['results_report'] ) ) {
        $results_report = $GLOBALS['results_report'];
    }

    if ( $results_report instanceof Results_Report ) {
        $_results_report = $results_report;
    } elseif ( is_object( $results_report ) ) {
        $_results_report = new Results_Report( $results_report );
    } else {
        $_results_report = Results_Report::get_instance( $results_report );
    }

    if ( ! $_results_report ) {
        return null;
    }

    return $_results_report;
}
/**
 * Get results check object
 *
 * @param int|null|object $results_check results_check ID or results_check object. Defaults to global $results_check.
 * @return object|null results_check|null
 */
function get_result_check( object|int|null $results_check = null ): ?object {
    if ( empty( $results_check ) && isset( $GLOBALS['results_check'] ) ) {
        $results_check = $GLOBALS['results_check'];
    }

    if ( $results_check instanceof Results_Checker ) {
        $_results_check = $results_check;
    } elseif ( is_object( $results_check ) ) {
        $_results_check = new Results_Checker( $results_check );
    } else {
        $_results_check = Results_Checker::get_instance( $results_check );
    }

    if ( ! $_results_check ) {
        return null;
    }

    return $_results_check;
}

/**
 * Get message object
 *
 * @param int|null $message message ID or message object. Defaults to global $message.
 *
 * @return Message|null message|null
 */
function get_message( ?int $message = null ): Message|null {
    if ( empty( $message ) && isset( $GLOBALS['message'] ) ) {
        $message = $GLOBALS['message'];
    }

    if ( $message instanceof Message ) {
        $_message = $message;
    } elseif ( is_object( $message ) ) {
        $_message = new Message( $message );
    } else {
        $_message = Message::get_instance( $message );
    }

    if ( ! $_message ) {
        return null;
    }

    return $_message;
}
/**
 * Undocumented function
 *
 * @param array $match_players array of players.
 * @param object $match match details.
 *
 * @return array
 */
function match_add_players( array $match_players, object $match ): array {
    $teams = array( 'home', 'away' );
    foreach ( $teams as $team ) {
        $team = $match->teams[ $team ];
        if ( ! empty( $team->players ) ) {
            foreach ( $team->players as $player ) {
                $match_players[] = $player->ID;
            }
        }
    }
    return $match_players;
}
/**
 * Get club role object
 *
 * @param object|int|null $club_role ID. Defaults to global $club_role.
 *
 * @return Club_Role|null
 */
function get_club_role( object|int|null $club_role = null ): ?Club_Role {
    if ( empty( $club_role ) && isset( $GLOBALS['club_role'] ) ) {
        $club_role = $GLOBALS['club_role'];
    }
    if ( $club_role instanceof Club_Role ) {
        $_club_role = $club_role;
    } elseif ( is_object( $club_role ) ) {
        $_club_role = new Club_Role( $club_role );
    } elseif( $club_role ) {
        $club_role_repository = new Club_Role_Repository();
        $_club_role           = $club_role_repository->find( $club_role );
    } else {
        $_club_role = null;
    }

    if ( ! $_club_role ) {
        return null;
    }
    return $_club_role;
}
/**
 * Get season object
 *
 * @param int|null $season season ID or season object. Defaults to global $season.
 * @return Season|null season|null
 */
function get_season( ?int $season = null ): Season|null {
    if ( empty( $season ) && isset( $GLOBALS['season'] ) ) {
        $season = $GLOBALS['season'];
    }
    if ( $season instanceof Season ) {
        $_season = $season;
    } elseif ( is_object( $season ) ) {
        $_season = new Season( $season );
    } else {
        $_season = Season::get_instance( $season );
    }
    if ( ! $_season ) {
        return null;
    }
    return $_season;
}
