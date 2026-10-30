<?php
/**
 * RacketManager standalone functions
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Factory;
use Racketmanager\Domain\Club\Club_Role;
use Racketmanager\Domain\Club\Club_Role_Factory;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Factory;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\Communication\Message;
use Racketmanager\Domain\Communication\Message_Factory;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Rubber;
use Racketmanager\Domain\Fixture\Rubber_Factory;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\Player_Factory;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Player\User_Factory;
use Racketmanager\Domain\Result\Results_Checker;
use Racketmanager\Domain\Result\Results_Checker_Factory;
use Racketmanager\Domain\Season\Season;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Team\Team_Factory;
use Racketmanager\Domain\Tournament\Tournament_Entry;
use Racketmanager\Domain\Tournament\Tournament_Entry_Factory;
use Racketmanager\Infrastructure\Legacy\Legacy_User;
use Racketmanager\Infrastructure\Legacy\Legacy_User_Factory;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Season_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Message_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Rubber_Repository;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;

/**
 * Send debug code to the JavaScript console
 *
 * @param object|array|string|int|null $data Optional message that will be sent the error_log before the backtrace.
 */
function debug_to_console( object|array|string|int|null $data ): void {
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
 * @deprecated 10.1.0 Use \Racketmanager\Domain\Shared\Seo_Slug::encode() instead.
 * @see \Racketmanager\Domain\Shared\Seo_Slug::encode()
 *
 * @param string $string_field query string.
 */
function seo_url( string $string_field ): string {
    return Seo_Slug::encode( $string_field );
}
/**
 * Reverses SEO friendly string
 *
 * @deprecated 10.1.0 Use \Racketmanager\Domain\Shared\Seo_Slug::decode() instead.
 * @see \Racketmanager\Domain\Shared\Seo_Slug::decode()
 *
 * @param string $string_field query string.
 */
function un_seo_url( string $string_field ): string {
    return Seo_Slug::decode( $string_field );
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
 * Get the current page url
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
 * @deprecated 10.1.0 Use the `club_repository` container entry (Club_Repository_Interface::find_by_id()) instead.
 * @see \Racketmanager\Repositories\Interfaces\Club_Repository_Interface::find_by_id()
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
        $_club = Club_Factory::from_object( $club );
    } else {
        global $racketmanager;
        if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'club_repository' ) ) {
            $club_repository = $racketmanager->container->get( 'club_repository' );
        } else {
            $club_repository = new Club_Repository();
        }
        $_club = $club_repository->find_by_id( $club, $search_term );
    }
    if ( ! $_club ) {
        return null;
    } else {
        return $_club;
    }
}

/**
 * Resolve the competition repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `competition_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\Competition_Repository_Interface
 *
 * @return Competition_Repository_Interface
 */
function get_competition_repository_for_helpers(): Competition_Repository_Interface {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'competition_repository' ) ) {
        return $racketmanager->container->get( 'competition_repository' );
    }

    return new Competition_Repository();
}

/**
 * Get Competition object
 *
 * @deprecated 10.0.0 Use \Racketmanager\Services\Competition_Service::get_competition() instead.
 * @see \Racketmanager\Services\Competition_Service::get_competition()
 *
 * @param Competition|int|string|null $competition Competition ID or competition object. Defaults to global $competition.
 * @param string|null                 $search_term type of search.
 *
 * @return Competition|null competition|null
 */
function get_competition( Competition|int|string|null $competition = null, ?string $search_term = 'id' ): ?Competition {
    trigger_error( 'get_competition() is deprecated since version 10.0.0! Use \Racketmanager\Services\Competition_Service::get_competition() instead.', E_USER_DEPRECATED ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
    if ( null === $competition || 0 === $competition || '' === $competition ) {
        if ( isset( $GLOBALS['competition'] ) ) {
            $global_comp = $GLOBALS['competition'];
            if ( $global_comp instanceof Competition ) {
                return $global_comp;
            }
            if ( is_object( $global_comp ) ) {
                if ( ! isset( $global_comp->sport ) ) {
                    $global_comp->sport = '';
                }
                return Competition_Factory::from_object( $global_comp );
            }
            if ( is_string( $global_comp ) && '' !== $global_comp ) {
                // When WordPress register_globals populates %competition% query var, it is a slug/name string
                $comp_name = un_seo_url( $global_comp );
                global $racketmanager;
                if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'competition_service' ) ) {
                    try {
                        return $racketmanager->container->get( 'competition_service' )->get_competition( $comp_name, 'name' );
                    } catch ( \Exception $e ) {
                        return null;
                    }
                }
                return get_competition_repository_for_helpers()->find_by_name( $comp_name );
            }
            if ( is_int( $global_comp ) ) {
                return get_competition_repository_for_helpers()->find_by_id( $global_comp );
            }
        }
        return null;
    }

    if ( $competition instanceof Competition ) {
        return $competition;
    }

    if ( is_object( $competition ) ) {
        if ( ! isset( $competition->sport ) ) {
            $competition->sport = '';
        }
        return Competition_Factory::from_object( $competition );
    }

    if ( is_string( $competition ) && ! is_numeric( $competition ) && 'id' === $search_term ) {
        $search_term = 'name';
    }

    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'competition_service' ) ) {
        try {
            return $racketmanager->container->get( 'competition_service' )->get_competition( $competition, $search_term );
        } catch ( \Exception $e ) {
            return null;
        }
    }
    $competition_repository = get_competition_repository_for_helpers();
    return 'name' === $search_term
        ? $competition_repository->find_by_name( (string) $competition )
        : $competition_repository->find_by_id( (int) $competition );
}

/**
 * Resolve the event repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `event_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\Event_Repository_Interface
 *
 * @return Event_Repository_Interface
 */
function get_event_repository_for_helpers() {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'event_repository' ) ) {
        return $racketmanager->container->get( 'event_repository' );
    }

    return new Event_Repository();
}

/**
 * Get Event object
 *
 * @deprecated 10.1.0 Use the `event_repository` container entry (Event_Repository_Interface::find_by_id() / find_by_name()) instead.
 * @see \Racketmanager\Repositories\Interfaces\Event_Repository_Interface::find_by_id()
 *
 * @param object|int|string|null $event Event ID or event object. Defaults to global $event.
 * @param string $search_term type of search.
 *
 * @return Event|null event|null
 */
function get_event( object|int|string|null $event = null, string $search_term = 'id' ): Event|null {
    if ( empty( $event ) && isset( $GLOBALS['event'] ) ) {
        $event = $GLOBALS['event'];
    }

    if ( $event instanceof Event ) {
        $_event = $event;
    } elseif ( is_object( $event ) ) {
        $_competition = null;
        if ( ! empty( $event->competition_id ) ) {
            $competition_repo = get_competition_repository_for_helpers();
            $_competition     = $competition_repo->find_by_id( (int) $event->competition_id );
        }
        $_event = Event_Factory::from_object( $event, $_competition instanceof Competition ? $_competition : null );
    } else {
        $event_repo = get_event_repository_for_helpers();
        $_event     = 'name' === $search_term ? $event_repo->find_by_name( (string) $event ) : $event_repo->find_by_id( $event );
    }

    if ( ! $_event ) {
        return null;
    }

    return $_event;
}

/**
 * Resolve the league repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `league_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\League_Repository_Interface
 *
 * @return League_Repository_Interface
 */
function get_league_repository_for_helpers() {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'league_repository' ) ) {
        return $racketmanager->container->get( 'league_repository' );
    }

    return new League_Repository( null, fn() => get_event_repository_for_helpers(), fn() => get_league_query_service() );
}

/**
 * Resolve the fixture repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `fixture_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface
 *
 * @return Fixture_Repository_Interface
 */
function get_fixture_repository_for_helpers() {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'fixture_repository' ) ) {
        return $racketmanager->container->get( 'fixture_repository' );
    }

    return new Fixture_Repository(
        null,
        null,
        get_rubber_repository_for_helpers(),
        get_league_repository_for_helpers(),
        get_team_repository_for_helpers()
    );
}

/**
 * Resolve the club repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `club_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\Club_Repository_Interface
 *
 * @return Club_Repository_Interface
 */
function get_club_repository_for_helpers(): Club_Repository_Interface {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'club_repository' ) ) {
        return $racketmanager->container->get( 'club_repository' );
    }

    return new Club_Repository();
}

/**
 * Resolve the league team repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `league_team_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface
 *
 * @return League_Team_Repository_Interface
 */
function get_league_team_repository_for_helpers() {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'league_team_repository' ) ) {
        return $racketmanager->container->get( 'league_team_repository' );
    }

    return new League_Team_Repository(
        null,
        get_club_repository_for_helpers(),
        get_team_repository_for_helpers()
    );
}

/**
 * Resolve the team repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `team_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\Team_Repository_Interface
 *
 * @return Team_Repository_Interface
 */
function get_team_repository_for_helpers() {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'team_repository' ) ) {
        return $racketmanager->container->get( 'team_repository' );
    }

    return new Team_Repository();
}

/**
 * Resolve the rubber repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `rubber_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface
 *
 * @return Rubber_Repository_Interface
 */
function get_rubber_repository_for_helpers() {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'rubber_repository' ) ) {
        return $racketmanager->container->get( 'rubber_repository' );
    }

    return new Rubber_Repository();
}

/**
 * Resolve the Event query service for legacy procedural callers and templates.
 *
 * Event no longer stores repositories, so code outside the container (legacy
 * admin screens, templates) runs Event data methods through this service.
 *
 * @deprecated 10.1.0 Inject the `event_query_service` container entry instead.
 * @see \Racketmanager\Services\Competition\Event_Query_Service
 *
 * @return Event_Query_Service
 */
function get_event_query_service(): Event_Query_Service {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'event_query_service' ) ) {
        return $racketmanager->container->get( 'event_query_service' );
    }

    return new Event_Query_Service(
        get_event_repository_for_helpers(),
        fn() => get_fixture_repository_for_helpers(),
        fn() => get_rubber_repository_for_helpers(),
        fn() => get_league_repository_for_helpers(),
        fn() => get_team_repository_for_helpers(),
        fn() => new Club_Repository(),
        fn() => new Player_Repository()
    );
}

/**
 * Resolve the League query service for legacy procedural callers and templates.
 *
 * League no longer stores repositories, so code outside the container (legacy
 * admin screens, templates) runs League data methods through this service.
 *
 * @deprecated 10.1.0 Inject the `league_query_service` container entry instead.
 * @see \Racketmanager\Services\Competition\League_Query_Service
 *
 * @return League_Query_Service
 */
function get_league_query_service(): League_Query_Service {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'league_query_service' ) ) {
        return $racketmanager->container->get( 'league_query_service' );
    }

    return new League_Query_Service(
        get_league_repository_for_helpers(),
        fn() => get_fixture_repository_for_helpers(),
        fn() => get_rubber_repository_for_helpers(),
        fn() => get_team_repository_for_helpers(),
        fn() => get_league_team_repository_for_helpers(),
        fn() => new Club_Repository(),
        fn() => new Player_Repository()
    );
}

/**
 * Get League object
 *
 * @deprecated 10.1.0 Use the `league_repository` container entry (League_Repository_Interface::find_by_id() / find_by_name()) instead.
 * @see \Racketmanager\Repositories\Interfaces\League_Repository_Interface::find_by_id()
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
    } elseif ( is_object( $league ) || $league ) {
        if ( is_object( $league ) ) {
            $event = null;
            if ( isset( $league->event ) && ( $league->event instanceof Event || is_object( $league->event ) ) ) {
                $event = $league->event instanceof Event ? $league->event : Event_Factory::from_object( $league->event );
            } elseif ( ! empty( $league->event_id ) ) {
                $event_repo = get_event_repository_for_helpers();
                $event      = $event_repo ? $event_repo->find_by_id( (int) $league->event_id ) : null;
            }
            $_league = League_Factory::from_object( $league, $event );
            if ( $_league ) {
                $service = get_league_query_service();
                $service->set_season( $_league, false, true );
            }
        } else {
            $league_repository = get_league_repository_for_helpers();
            if ( is_numeric( $league ) ) {
                $_league = $league_repository->find_by_id( (int) $league );
            } else {
                $_league = $league_repository->find_by_name( (string) $league );
            }
        }
    } else {
        $_league = null;
    }
    if ( ! $_league ) {
        return null;
    }
    return $_league;
}


/**
 * Get Fixture object
 *
 * @deprecated 2.0.0 Returns \Racketmanager\Domain\Fixture\Fixture instead of legacy Racketmanager_Match.
 *                   Use \Racketmanager\Repositories\Fixture_Repository::find_by_id() instead.
 * @see \Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface::find_by_id()
 *
 * @param object|int|null $match Match/Fixture ID or object. Defaults to global $match.
 *
 * @return Fixture|null
 */
function get_match( object|int|null $match = null ): ?Fixture {
    trigger_error( 'get_match() is deprecated since version 2.0.0! Use \Racketmanager\Repositories\Fixture_Repository::find_by_id() instead.', E_USER_DEPRECATED ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
    if ( empty( $match ) && isset( $GLOBALS['match'] ) ) {
        $match = $GLOBALS['match'];
    }

    if ( $match instanceof Fixture ) {
        return $match;
    }

    if ( is_object( $match ) ) {
        if ( isset( $match->id ) ) {
            $match = $match->id;
        } else {
            return null;
        }
    }

    if ( $match ) {
        global $racketmanager;
        if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'fixture_repository' ) ) {
            $fixture_repo = $racketmanager->container->get( 'fixture_repository' );
        } else {
            $fixture_repo = new \Racketmanager\Repositories\Fixture_Repository();
        }
        return $fixture_repo->find_by_id( (int) $match );
    }

    return null;
}

/**
 * Get Rubber object
 *
 * @deprecated 2.0.0 Use \Racketmanager\Repositories\Rubber_Repository::find_by_id() instead.
 * @see \Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface::find_by_id()
 *
 * @param object|int|null $rubber Rubber ID or rubber object. Defaults to global $rubber.
 *
 * @return Rubber|null Rubber|null
 */
function get_rubber( object|int|null $rubber = null ): Rubber|null {
    trigger_error( 'get_rubber() is deprecated since version 2.0.0! Use \Racketmanager\Repositories\Rubber_Repository::find_by_id() instead.', E_USER_DEPRECATED ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
    if ( empty( $rubber ) && isset( $GLOBALS['rubber'] ) ) {
        $rubber = $GLOBALS['rubber'];
    }

    if ( $rubber instanceof Rubber ) {
        $_rubber = $rubber;
    } elseif ( is_object( $rubber ) ) {
        $_rubber = Rubber_Factory::from_object( $rubber );
    } elseif ( $rubber ) {
        global $racketmanager;
        if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'rubber_repository' ) ) {
            $rubber_repo = $racketmanager->container->get( 'rubber_repository' );
            $_rubber     = $rubber_repo->find_by_id( (int) $rubber );
        } elseif ( isset( $racketmanager->container ) && $racketmanager->container->has( 'registration_service' ) ) {
            $rubber_repo = new \Racketmanager\Repositories\Rubber_Repository( $racketmanager->container->get( 'registration_service' ) );
            $_rubber     = $rubber_repo->find_by_id( (int) $rubber );
        } else {
            $rubber_repo = new \Racketmanager\Repositories\Rubber_Repository();
            $_rubber     = $rubber_repo->find_by_id( (int) $rubber );
        }
    } else {
        $_rubber = null;
    }

    if ( ! $_rubber ) {
        return null;
    }

    return $_rubber;
}


/**
 * Get Tournament Entry object
 *
 * @deprecated 10.1.0 Use the `tournament_entry_repository` container entry (Tournament_Entry_Repository_Interface::find_by_id()) instead.
 * @see \Racketmanager\Repositories\Interfaces\Tournament_Entry_Repository_Interface::find_by_id()
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
        $_tournament_entry = Tournament_Entry_Factory::from_object( $tournament_entry );
    } elseif ( $tournament_entry ) {
        global $racketmanager;
        if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'tournament_entry_repository' ) ) {
            $entry_repo = $racketmanager->container->get( 'tournament_entry_repository' );
        } else {
            $entry_repo = new \Racketmanager\Repositories\Tournament_Entry_Repository();
        }
        if ( 'key' === $search_term && is_string( $tournament_entry ) && str_contains( $tournament_entry, '_' ) ) {
            [ $t_id, $p_id ]   = explode( '_', $tournament_entry, 2 );
            $_tournament_entry = $entry_repo->find_by_tournament_and_player( (int) $t_id, (int) $p_id );
        } else {
            $_tournament_entry = $entry_repo->find_by_id( $tournament_entry );
        }
    } else {
        $_tournament_entry = null;
    }
    if ( ! $_tournament_entry ) {
        return null;
    }
    return $_tournament_entry;
}
/**
 * Get Team object
 *
 * @deprecated 10.1.0 Use the `team_repository` container entry (Team_Repository_Interface::find_by_id()) instead.
 * @see \Racketmanager\Repositories\Interfaces\Team_Repository_Interface::find_by_id()
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
        $_team = Team_Factory::from_object( $team );
    } else {
        $team_repo = get_team_repository_for_helpers();
        $_team     = $team_repo->find_by_id( $team );
    }

    if ( ! $_team ) {
        return null;
    }

    return $_team;
}

/**
 * Get Player object
 *
 * @deprecated 10.1.0 Use the `player_repository` container entry (Player_Repository_Interface::find()) instead.
 * @see \Racketmanager\Repositories\Interfaces\Player_Repository_Interface::find()
 *
 * @param int|string|null $player Player ID or player object. Defaults to global $player.
 * @param string $search_term search type term (defaults to id).
 *
 * @return object|null player|null
 */
function get_player( int|string|object|null $player = null, string $search_term = 'id' ): ?object {
    if ( empty( $player ) && isset( $GLOBALS['player'] ) ) {
        $player = $GLOBALS['player'];
    }
    if ( ! $player ) {
        return null;
    }
    global $racketmanager;
    $player_repository = null;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'player_repository' ) ) {
        $player_repository = $racketmanager->container->get( 'player_repository' );
    }
    $player_repository = $player_repository ?? new Player_Repository();
    if ( $player instanceof Player ) {
        $_player = $player;
    } elseif ( is_object( $player ) ) {
        $_player = Player_Factory::from_object( $player );
    } else {
        $_player = $player_repository->find( $player, $search_term );
    }
    if ( ! $_player ) {
        return null;
    }
    return $_player;
}

/**
 * Get User object
 *
 * @deprecated 10.1.0 Use the `user_repository` container entry (User_Repository_Interface::find()) instead.
 * @see \Racketmanager\Repositories\Interfaces\User_Repository_Interface::find()
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
        $id = (int) ( $user->ID ?? $user->id ?? 0 );
        if ( $id > 0 ) {
            global $racketmanager;
            if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'user_repository' ) ) {
                $user_repo = $racketmanager->container->get( 'user_repository' );
            } else {
                $user_repo = new \Racketmanager\Repositories\User_Repository();
            }
            $_user = $user_repo->find( $id ) ?? User_Factory::from_object( $user );
        } else {
            $_user = User_Factory::from_object( $user );
        }
    } elseif ( $user ) {
        global $racketmanager;
        if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'user_repository' ) ) {
            $user_repo = $racketmanager->container->get( 'user_repository' );
        } else {
            $user_repo = new \Racketmanager\Repositories\User_Repository();
        }
        $_user = $user_repo->find( $user );
    } else {
        $_user = null;
    }
    if ( ! $_user ) {
        return null;
    }

    if ( $_user instanceof Legacy_User ) {
        return $_user;
    }

    return to_legacy_user( $_user );
}

/**
 * Wrap a domain User as a compatibility Legacy_User with an injected message repository.
 *
 * @deprecated 10.1.0 Use the `legacy_user_factory` container entry (Legacy_User_Factory::from_user()) instead.
 * @see \Racketmanager\Infrastructure\Legacy\Legacy_User_Factory::from_user()
 */
function to_legacy_user( User $user ): Legacy_User {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'legacy_user_factory' ) ) {
        return $racketmanager->container->get( 'legacy_user_factory' )->from_user( $user );
    }

    $message_repository = ( isset( $racketmanager->container ) && $racketmanager->container->has( 'message_repository' ) )
        ? $racketmanager->container->get( 'message_repository' )
        : new Message_Repository();

    return ( new Legacy_User_Factory( $message_repository ) )->from_user( $user );
}

/**
 * Get LeagueTeam object
 *
 * @deprecated 10.1.0 Use the `league_team_repository` container entry (League_Team_Repository_Interface::find()) instead.
 * @see \Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface::find()
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
        $league_team_repository = get_league_team_repository_for_helpers();
        $_league_team           = $league_team_repository->hydrate( $league_team );
    } elseif ( $league_team ) {
        $league_team_repository = get_league_team_repository_for_helpers();
        $_league_team           = $league_team_repository->find( $league_team );
    } else {
        $_league_team = null;
    }

    if ( ! $_league_team ) {
        return null;
    }
    return $_league_team;
}

/**
 * Get a result check object
 *
 * @deprecated 10.1.0 Use the `results_checker_repository` container entry (Results_Checker_Repository_Interface::find_by_id()) instead.
 * @see \Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface::find_by_id()
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
        $_results_check = Results_Checker_Factory::from_object( $results_check );
    } elseif ( $results_check ) {
        global $racketmanager;
        $repository     = $racketmanager->container->get( 'results_checker_repository' );
        $_results_check = $repository->find_by_id( $results_check );
    } else {
        $_results_check = null;
    }

    if ( ! $_results_check ) {
        return null;
    }

    return $_results_check;
}

/**
 * Get message object
 *
 * @deprecated 10.1.0 Use the `message_repository` container entry (Message_Repository_Interface::find()) instead.
 * @see \Racketmanager\Repositories\Interfaces\Message_Repository_Interface::find()
 *
 * @param int|null $message message ID or message object. Defaults to global $message.
 *
 * @return Message|null message|null
 */
function get_message( object|int|string|null $message = null ): Message|null {
    if ( empty( $message ) && isset( $GLOBALS['message'] ) ) {
        $message = $GLOBALS['message'];
    }

    if ( $message instanceof Message ) {
        $_message = $message;
    } elseif ( is_object( $message ) ) {
        $_message = Message_Factory::from_object( $message );
    } elseif ( $message ) {
        global $racketmanager;
        if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'message_repository' ) ) {
            $message_repo = $racketmanager->container->get( 'message_repository' );
        } else {
            $message_repo = new \Racketmanager\Repositories\Message_Repository();
        }
        $_message = $message_repo->find( $message );
    } else {
        $_message = null;
    }

    if ( ! $_message ) {
        return null;
    }

    return $_message;
}
/**
 * Add player IDs from a legacy match's home and away teams.
 *
 * @param array $match_players array of players.
 * @param object $match match details.
 *
 * @return array
 * @deprecated 2.0.0 Use Fixture_Detail_Service::collect_fixture_player_ids().
 * @see \Racketmanager\Services\Fixture\Fixture_Detail_Service::collect_fixture_player_ids()
 */
function match_add_players( array $match_players, object $match ): array {
    trigger_error( 'match_add_players() is deprecated since version 2.0.0! Use Fixture_Detail_Service::collect_fixture_player_ids() instead.', E_USER_DEPRECATED ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error
    foreach ( array( 'home', 'away' ) as $side ) {
        $team = $match->teams[ $side ];
        if ( ! empty( $team->players ) ) {
            foreach ( $team->players as $player ) {
                $match_players[] = $player->ID;
            }
        }
    }
    return $match_players;
}
/**
 * Resolve the club role repository for procedural helpers, preferring the container.
 *
 * @deprecated 10.1.0 Inject the `club_role_repository` container entry instead.
 * @see \Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface
 *
 * @return Club_Role_Repository_Interface
 */
function get_club_role_repository_for_helpers(): Club_Role_Repository_Interface {
    global $racketmanager;
    if ( isset( $racketmanager->container ) && $racketmanager->container->has( 'club_role_repository' ) ) {
        return $racketmanager->container->get( 'club_role_repository' );
    }

    return new Club_Role_Repository();
}

/**
 * Get club role object
 *
 * @param object|int|null $club_role ID. Defaults to global $club_role.
 *
 * @return Club_Role|null
 * @deprecated 10.0.0 Use \Racketmanager\Repositories\Club_Role_Repository::find() instead.
 * @see \Racketmanager\Repositories\Club_Role_Repository::find()
 */
function get_club_role( object|int|null $club_role = null ): ?Club_Role {
    trigger_error( 'get_club_role() is deprecated since version 10.0.0! Use \Racketmanager\Repositories\Club_Role_Repository::find() instead.', E_USER_DEPRECATED ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_trigger_error

    if ( null === $club_role ) {
        if ( isset( $GLOBALS['club_role'] ) ) {
            $global_role = $GLOBALS['club_role'];
            if ( $global_role instanceof Club_Role ) {
                return $global_role;
            }
            if ( is_object( $global_role ) ) {
                return Club_Role_Factory::from_object( $global_role );
            }
            if ( is_numeric( $global_role ) && (int) $global_role > 0 ) {
                return get_club_role_repository_for_helpers()->find( (int) $global_role );
            }
        }
        return null;
    }

    if ( $club_role instanceof Club_Role ) {
        return $club_role;
    }

    if ( is_object( $club_role ) ) {
        return Club_Role_Factory::from_object( $club_role );
    }

    if ( ! empty( $club_role ) ) {
        $club_role_repository = get_club_role_repository_for_helpers();
        return $club_role_repository->find( $club_role );
    }

    return null;
}
