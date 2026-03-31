<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Fixture;

use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\Repository_Provider;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Registration_Service;

/**
 * Service to handle fixture update permissions.
 */
class Fixture_Permission_Service {

    private Fixture_Repository $fixture_repository;
    private Registration_Service $registration_service;
    private League_Repository $league_repository;
    private Team_Repository $team_repository;
    private Club_Repository $club_repository;
    private array $options;

    public function __construct( Repository_Provider $repository_provider, Service_Provider $service_provider ) {
        $this->fixture_repository   = $repository_provider->get_fixture_repository();
        $this->registration_service = $service_provider->get_registration_service();
        $this->league_repository    = $repository_provider->get_league_repository();
        $this->team_repository      = $repository_provider->get_team_repository();
        $this->club_repository      = $repository_provider->get_club_repository();
        
        global $racketmanager;
        $this->options = $racketmanager ? $racketmanager->get_options() : [];
    }

    /**
     * Check whether match update allowed
     *
     * @param int|Fixture $fixture_id
     *
     * @return object
     */
    public function is_update_allowed( int|Fixture $fixture_id ): object {
        $fixture = $this->get_fixture_entity( $fixture_id );
        if ( ! $fixture ) {
            return $this->build_permission_response( false, '', '', 'notFixtureFound' );
        }

        $context = $this->prepare_permission_context( $fixture );
        return $this->validate_and_evaluate_permissions( $fixture, $context );
    }

    /**
     * Get fixture entity from ID or instance.
     */
    private function get_fixture_entity( int|Fixture|null $fixture_id ): ?Fixture {
        if ( $fixture_id instanceof Fixture ) {
            return $fixture_id;
        }

        return $this->fixture_repository->find_by_id( (int) $fixture_id );
    }

    /**
     * Validate prerequisites and evaluate permissions.
     */
    private function validate_and_evaluate_permissions( Fixture $fixture, object $context ): object {
        if ( ! is_user_logged_in() || ! get_current_user_id() ) {
            return $this->build_permission_response( false, '', '', 'notLoggedIn' );
        }

        if ( ! $context->league ) {
            return $this->build_permission_response( false, '', '', 'notLeagueSet' );
        }

        return $this->evaluate_permissions( $fixture, $context );
    }

    /**
     * Build standard permission response object.
     */
    private function build_permission_response( bool $can_update, string $type = '', string $team = '', string $message = '', bool $approval = false, bool $update = false ): object {
        return (object) [
            'user_can_update'     => (bool) $can_update,
            'user_type'           => $type,
            'user_team'           => $team,
            'message'             => $message,
            'match_approval_mode' => (bool) $approval,
            'match_update'        => (bool) $update,
        ];
    }

    /**
     * Prepare context for permission evaluation.
     */
    private function prepare_permission_context( Fixture $fixture ): object {
        $home_team = $this->team_repository->find_by_id( (int) $fixture->get_home_team() );
        $away_team = $this->team_repository->find_by_id( (int) $fixture->get_away_team() );
        $league    = $this->league_repository->find_by_id( $fixture->get_league_id() );

        return (object) [
            'home_team' => $home_team,
            'away_team' => $away_team,
            'league'    => $league,
            'userid'    => $this->get_current_user_id(),
        ];
    }

    /**
     * Main permission evaluation logic.
     */
    private function evaluate_permissions( Fixture $fixture, object $context ): object {
        if ( $this->current_user_can( 'manage_racketmanager' ) ) {
            return $this->build_permission_response( true, 'admin', '', '', false, 'P' === $fixture->get_confirmed() );
        }

        if ( empty( $context->home_team ) || empty( $context->away_team ) || empty( $context->home_team->get_club_id() ) || empty( $context->away_team->get_club_id() ) ) {
            return $this->build_permission_response( false, '', '', 'notTeamSet' );
        }

        return $this->evaluate_non_admin_permissions( $fixture, $context );
    }

    /**
     * Wrapper for current_user_can to allow mocking.
     */
    protected function current_user_can( string $capability ): bool {
        return current_user_can( $capability );
    }

    /**
     * Wrapper for get_current_user_id to allow mocking.
     */
    protected function get_current_user_id(): int {
        return get_current_user_id();
    }

    /**
     * Evaluate permissions for non-admin users.
     */
    private function evaluate_non_admin_permissions( Fixture $fixture, object $context ): object {
        $user_role = $this->identify_user_role( $fixture, $context );
        if ( ! $user_role->type ) {
            $comp_type = $context->league->event->competition->type;
            $options = $this->get_options();
            $capability = $options[ $comp_type ]['matchCapability'] ?? 'none';
            $entry     = $options[ $comp_type ]['resultEntry'] ?? 'home';

            if ( 'player' === $capability ) {
                return $this->evaluate_regular_player_permissions( $fixture, $context, $entry );
            }

            return $this->build_permission_response( false, '', '', 'notCaptain' );
        }

        return $this->evaluate_role_permissions( $fixture, $context, $user_role );
    }

    /**
     * Identify the user's role in the context of the fixture.
     */
    private function identify_user_role( Fixture $fixture, object $context ): object {
        $sec_role = $this->identify_secretary_role( $context );
        if ( $sec_role->type ) {
            return $sec_role;
        }

        return $this->identify_captain_role( $fixture, $context );
    }

    /**
     * Identify if the user is a match secretary for either club.
     */
    private function identify_secretary_role( object $context ): object {
        $home_club = $this->club_repository->find( $context->home_team->get_club_id() );
        $away_club = $this->club_repository->find( $context->away_team->get_club_id() );

        if ( isset( $home_club->match_secretary->id ) && intval( $home_club->match_secretary->id ) === $context->userid ) {
            return (object) [ 'type' => 'matchsecretary', 'team' => 'home' ];
        }

        if ( isset( $away_club->match_secretary->id ) && intval( $away_club->match_secretary->id ) === $context->userid ) {
            return (object) [ 'type' => 'matchsecretary', 'team' => 'away' ];
        }

        return (object) [ 'type' => '', 'team' => '' ];
    }

    /**
     * Identify if the user is a captain for either team.
     */
    private function identify_captain_role( Fixture $fixture, object $context ): object {
        if ( $fixture->get_home_captain() && intval( $fixture->get_home_captain() ) === $context->userid ) {
            return (object) [ 'type' => 'captain', 'team' => 'home' ];
        }

        if ( $fixture->get_away_captain() && intval( $fixture->get_away_captain() ) === $context->userid ) {
            return (object) [ 'type' => 'captain', 'team' => 'away' ];
        }

        return (object) [ 'type' => '', 'team' => '' ];
    }

    /**
     * Evaluate permissions based on identified role and competition settings.
     */
    private function evaluate_role_permissions( Fixture $fixture, object $context, object $user_role ): object {
        $comp_type = $context->league->event->competition->type;
        $options = $this->get_options();
        $capability = $options[ $comp_type ]['matchCapability'] ?? 'none';
        $entry     = $options[ $comp_type ]['resultEntry'] ?? 'home';

        return $this->dispatch_role_evaluation( $fixture, $context, $user_role, $capability, $entry );
    }

    /**
     * Wrapper for options to allow mocking.
     */
    protected function get_options(): array {
        return $this->options;
    }

    /**
     * Dispatch permission evaluation based on capability setting.
     */
    private function dispatch_role_evaluation( Fixture $fixture, object $context, object $user_role, string $capability, string $entry ): object {
        if ( 'none' === $capability ) {
            return $this->build_permission_response( false, $user_role->type, $user_role->team, 'noMatchCapability' );
        }

        if ( 'captain' === $capability ) {
            return $this->evaluate_captain_permissions( $fixture, $user_role, $entry );
        }

        return $this->evaluate_player_capability_role_permissions( $fixture, $context, $user_role, $capability, $entry );
    }

    /**
     * Evaluate role permissions when capability is 'player' or other.
     */
    private function evaluate_player_capability_role_permissions( Fixture $fixture, object $context, object $user_role, string $capability, string $entry ): object {
        if ( 'player' === $capability ) {
            return $this->evaluate_player_capability_permissions( $fixture, $context, $user_role, $entry );
        }

        return $this->build_permission_response( false, $user_role->type, $user_role->team );
    }

    /**
     * Evaluate permissions for 'captain' match capability.
     */
    private function evaluate_captain_permissions( Fixture $fixture, object $user_role, string $entry ): object {
        if ( 'home' === $user_role->team ) {
            $can_update = 'P' === $fixture->get_confirmed() && empty( $fixture->get_winner_id() );
            $message = ! $can_update ? 'matchAlreadyConfirmed' : '';
            return $this->build_permission_response( $can_update, $user_role->type, 'home', $message, false, $can_update );
        }

        return $this->evaluate_away_captain_permissions( $fixture, $user_role, $entry );
    }

    /**
     * Evaluate permissions for away captain.
     */
    private function evaluate_away_captain_permissions( Fixture $fixture, object $user_role, string $entry ): object {
        if ( 'away' === $user_role->team ) {
            return $this->evaluate_away_captain_entry_permissions( $fixture, $user_role, $entry );
        }

        return $this->build_permission_response( false, $user_role->type, $user_role->team );
    }

    /**
     * Evaluate entry-specific permissions for away captain.
     */
    private function evaluate_away_captain_entry_permissions( Fixture $fixture, object $user_role, string $entry ): object {
        if ( 'home' === $entry ) {
            $can_update = 'P' === $fixture->get_confirmed() && empty( $fixture->get_winner_id() );
            $message = ! $can_update ? 'notHomeCaptain' : '';
            return $this->build_permission_response( $can_update, $user_role->type, 'away', $message, $can_update );
        }
        if ( 'either' === $entry ) {
            $can_update = 'P' === $fixture->get_confirmed() || empty( $fixture->get_winner_id() );
            $message = ! $can_update ? 'matchAlreadyConfirmed' : '';
            return $this->build_permission_response( $can_update, $user_role->type, 'away', $message );
        }
        return $this->build_permission_response( false, $user_role->type, $user_role->team );
    }

    /**
     * Evaluate permissions when 'player' match capability is active.
     */
    private function evaluate_player_capability_permissions( Fixture $fixture, object $context, object $user_role, string $entry ): object {
        // If the user is captain/secretary, they have broad permissions under 'player' capability
        if ( 'either' === $entry || 'home' === $user_role->team || ( 'away' === $user_role->team && 'home' === $entry ) ) {
            if ( 'P' === $fixture->get_confirmed() ) {
                $update = $this->determine_player_cap_update_mode( $fixture, $user_role->team );
                return $this->build_permission_response( true, $user_role->type, $user_role->team, '', $update === 'approval', $update === 'update' );
            }
            if ( empty( $fixture->get_winner_id() ) ) {
                return $this->build_permission_response( true, $user_role->type, $user_role->team );
            }
        }

        // Check if the user is a regular player
        return $this->evaluate_regular_player_permissions( $fixture, $context, $entry );
    }

    /**
     * Determine update mode (update vs approval) for player capability.
     */
    private function determine_player_cap_update_mode( Fixture $fixture, string $user_team ): string {
        if ( 'home' === $user_team ) {
            return $this->evaluate_home_player_cap_update_mode( $fixture );
        }
        if ( 'away' === $user_team ) {
            return $this->evaluate_away_player_cap_update_mode( $fixture );
        }
        return '';
    }

    /**
     * Evaluate update mode for home team player.
     */
    private function evaluate_home_player_cap_update_mode( Fixture $fixture ): string {
        if ( empty( $fixture->get_away_captain() ) ) {
            return 'update';
        }
        return empty( $fixture->get_home_captain() ) ? 'approval' : '';
    }

    /**
     * Evaluate update mode for away team player.
     */
    private function evaluate_away_player_cap_update_mode( Fixture $fixture ): string {
        if ( empty( $fixture->get_home_captain() ) ) {
            return 'update';
        }
        return empty( $fixture->get_away_captain() ) ? 'approval' : '';
    }

    /**
     * Evaluate permissions for a regular player (not captain/secretary).
     */
    private function evaluate_regular_player_permissions( Fixture $fixture, object $context, string $entry ): object {
        $player_team = $this->identify_player_team( $context );
        if ( ! $player_team ) {
            return $this->build_permission_response( false, '', '', 'notTeamPlayer' );
        }

        return $this->dispatch_regular_player_permissions( $fixture, $context, $entry, $player_team );
    }

    /**
     * Dispatch regular player permission evaluation based on the entry rule.
     */
    private function dispatch_regular_player_permissions( Fixture $fixture, object $context, string $entry, string $player_team ): object {
        if ( 'home' === $entry ) {
            return $this->evaluate_player_home_entry_permissions( $fixture, $player_team );
        }

        if ( 'either' === $entry ) {
            return $this->evaluate_player_either_entry_permissions( $fixture, $context->userid, $player_team );
        }

        return $this->build_permission_response( false, 'player', $player_team );
    }

    /**
     * Identify which team(s) the current user plays for.
     */
    private function identify_player_team( object $context ): string {
        $home_player = $this->registration_service->is_player_active_in_club( $context->home_team->get_club_id(), $context->userid );
        $away_player = $this->registration_service->is_player_active_in_club( $context->away_team->get_club_id(), $context->userid );

        return $this->derive_player_team_ref( $home_player, $away_player );
    }

    /**
     * Derive team reference from player activity status.
     */
    private function derive_player_team_ref( bool $home_player, bool $away_player ): string {
        if ( $home_player && $away_player ) {
            return 'both';
        }

        if ( $home_player ) {
            return 'home';
        }

        return $away_player ? 'away' : '';
    }

    /**
     * Evaluate player permissions for 'home' entry rule.
     */
    private function evaluate_player_home_entry_permissions( Fixture $fixture, string $player_team ): object {
        if ( empty( $fixture->get_winner_id() ) ) {
            $can = 'home' === $player_team || 'both' === $player_team;
            return $this->build_permission_response( $can, 'player', $player_team );
        }

        if ( 'P' === $fixture->get_confirmed() ) {
            $can = 'away' === $player_team || 'both' === $player_team;
            return $this->build_permission_response( $can, 'player', $player_team, '', $can );
        }

        return $this->build_permission_response( false, 'player', $player_team );
    }

    /**
     * Evaluate player permissions for 'either' entry rule.
     */
    private function evaluate_player_either_entry_permissions( Fixture $fixture, int $userid, string $player_team ): object {
        if ( 'P' === $fixture->get_confirmed() ) {
            return $this->evaluate_player_either_pending_permissions( $fixture, $userid, $player_team );
        }

        if ( empty( $fixture->get_winner_id() ) ) {
            return $this->build_permission_response( true, 'player', $player_team );
        }

        return $this->build_permission_response( false, 'player', $player_team );
    }

    /**
     * Evaluate player permissions for 'either' entry rule when a result is pending.
     */
    private function evaluate_player_either_pending_permissions( Fixture $fixture, int $userid, string $player_team ): object {
        $is_home = ( 'home' === $player_team || 'both' === $player_team );
        $is_away = ( 'away' === $player_team || 'both' === $player_team );

        if ( $is_home ) {
            $home_resp = $this->evaluate_player_either_home_pending_permissions( $fixture, $userid, $player_team );
            if ( $home_resp->user_can_update ) {
                return $home_resp;
            }
        }

        if ( $is_away ) {
            return $this->evaluate_player_either_away_pending_permissions( $fixture, $userid, $player_team );
        }

        return $this->build_permission_response( false, 'player', $player_team );
    }

    /**
     * Evaluate player permissions for 'either' entry rule when a result is pending for the home side.
     */
    private function evaluate_player_either_home_pending_permissions( Fixture $fixture, int $userid, string $player_team ): object {
        if ( empty( $fixture->get_home_captain() ) ) {
            return $this->build_permission_response( true, 'player', $player_team, '', true );
        }

        if ( (int) $fixture->get_home_captain() === $userid ) {
            return $this->build_permission_response( true, 'player', $player_team );
        }

        return $this->build_permission_response( false, 'player', $player_team );
    }

    /**
     * Evaluate player permissions for 'either' entry rule when a result is pending for the away side.
     */
    private function evaluate_player_either_away_pending_permissions( Fixture $fixture, int $userid, string $player_team ): object {
        if ( empty( $fixture->get_away_captain() ) ) {
            return $this->build_permission_response( true, 'player', $player_team, '', true );
        }

        if ( (int) $fixture->get_away_captain() === $userid ) {
            return $this->build_permission_response( true, 'player', $player_team );
        }

        return $this->build_permission_response( false, 'player', $player_team );
    }
}