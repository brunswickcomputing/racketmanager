<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use BackedEnum;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Presentation\View_Models\Draw_Fixture_View_Model;
use Racketmanager\Presentation\View_Models\Draw_Opponent_View_Model;
use Racketmanager\Presentation\View_Models\Draw_Player_View_Model;
use Racketmanager\Presentation\View_Models\Draw_Set_Score_View_Model;
use Racketmanager\Util\Util;
use function __;
use function is_array;
use function is_numeric;
use function is_object;
use function method_exists;
use function sprintf;
use function str_replace;
use function substr;
use function trim;

/**
 * Presenter for converting knockout draw fixtures into logic-less view models.
 *
 * Accepted fixture inputs:
 * - Fixture_Details_DTO (or an equivalent object exposing ->fixture/->home_team/->away_team/->link),
 *   as produced by Fixture_Detail_Service for tournament and event/cup draws;
 * - a raw Fixture entity, for which no team details are available (rendered as placeholders).
 *
 * Persisted set scores are arrays keyed player1/player2/tiebreak; Set_Score value objects are also supported.
 */
class Draw_Presenter {
    /**
     * Present a draw fixture row into a view model.
     */
    public static function present_fixture(
        mixed $fixture_details,
        ?object $tournament = null,
        ?object $league = null,
        ?object $competition = null,
        ?object $event = null
    ): Draw_Fixture_View_Model {
        if ( $fixture_details instanceof Draw_Fixture_View_Model ) {
            return $fixture_details;
        }

        if ( $fixture_details instanceof Fixture ) {
            $fixture       = $fixture_details;
            $home_team_dto = null;
            $away_team_dto = null;
            $detail_link   = '';
        } elseif ( is_object( $fixture_details ) && isset( $fixture_details->fixture ) && is_object( $fixture_details->fixture ) ) {
            $fixture       = $fixture_details->fixture;
            $league        = $fixture_details->league ?? $league;
            $event         = $fixture_details->event ?? $event;
            $competition   = $fixture_details->competition ?? $competition;
            $home_team_dto = $fixture_details->home_team ?? null;
            $away_team_dto = $fixture_details->away_team ?? null;
            $detail_link   = (string) ( $fixture_details->link ?? '' );
        } else {
            return new Draw_Fixture_View_Model( skip_render: true );
        }

        if ( ! empty( $fixture->leg ) && 2 !== (int) $fixture->leg ) {
            return new Draw_Fixture_View_Model( skip_render: true );
        }

        $home_team = self::resolve_team( $home_team_dto );
        $away_team = self::resolve_team( $away_team_dto );

        $match_link = self::build_match_link( $fixture, $tournament, $league, $home_team, $away_team, $detail_link );

        $home_team_id = self::team_id( $home_team );
        $away_team_id = self::team_id( $away_team );

        $winner_side = null;
        $loser_side  = null;
        $is_tie      = false;
        if ( empty( $fixture->leg ) ) {
            if ( ! empty( $fixture->winner_id ) ) {
                if ( null !== $home_team_id && (string) $fixture->winner_id === (string) $home_team_id ) {
                    $winner_side = 'home';
                    $loser_side  = 'away';
                } elseif ( null !== $away_team_id && (string) $fixture->winner_id === (string) $away_team_id ) {
                    $winner_side = 'away';
                    $loser_side  = 'home';
                } elseif ( '-1' === (string) $fixture->winner_id ) {
                    $is_tie = true;
                }
            }
        } elseif ( ! empty( $fixture->winner_id_tie ) ) {
            if ( null !== $home_team_id && (string) $fixture->winner_id_tie === (string) $home_team_id ) {
                $winner_side = 'home';
                $loser_side  = 'away';
            } elseif ( null !== $away_team_id && (string) $fixture->winner_id_tie === (string) $away_team_id ) {
                $winner_side = 'away';
                $loser_side  = 'home';
            }
        }

        $is_team_entry = self::is_team_entry( $competition );
        $is_tournament = ! empty( $tournament ) || self::is_tournament( $competition );

        $opponents = [];
        foreach ( [ 'home', 'away' ] as $side ) {
            $team_dto = 'home' === $side ? $home_team_dto : $away_team_dto;
            $team     = 'home' === $side ? $home_team : $away_team;
            $team_id  = 'home' === $side ? $home_team_id : $away_team_id;

            $team_title = (string) ( $team->title ?? '' );
            $is_winner  = ( $winner_side === $side );
            $is_loser   = ( $loser_side === $side );

            [ $message_class, $message_text, $status_class, $status_text ] = self::status_display( $fixture, $side, $is_winner, $is_loser, $is_tie );

            $opponents[ $side ] = new Draw_Opponent_View_Model(
                team_id: $team_id,
                team_name: self::team_display_name( $team_title, $event ),
                has_team: null !== $team && is_numeric( $team_id ),
                winner_class: $is_winner ? 'winner' : null,
                team_url: self::build_team_url( $team_id, $team_title, $is_tournament, $competition, $league ),
                is_withdrawn: $team_dto instanceof Team_Details_DTO ? $team_dto->is_withdrawn : ! empty( $team_dto->is_withdrawn ),
                withdrawn_text: $team_title . ' ' . __( 'has withdrawn', 'racketmanager' ),
                players: self::present_players( $team, $tournament ),
                match_message_class: $message_class,
                match_message_text: $message_text,
                match_status_class: $status_class,
                match_status_text: $status_text,
                scores: self::present_scores( $fixture, $side, $winner_side, $is_team_entry )
            );
        }

        return new Draw_Fixture_View_Model(
            match_link: $match_link,
            home: $opponents['home'],
            away: $opponents['away'],
            skip_render: false
        );
    }

    /**
     * Present all finals in a league.
     *
     * Each presented final exposes its rows as Draw_Fixture_View_Model instances under `fixtures`.
     *
     * @param array $finals
     * @param object|null $tournament
     * @param object|null $league
     * @param object|null $competition
     * @param object|null $event
     * @return array
     */
    public static function present_finals(
        array $finals,
        ?object $tournament = null,
        ?object $league = null,
        ?object $competition = null,
        ?object $event = null
    ): array {
        $presented = [];
        foreach ( $finals as $f_idx => $final ) {
            $final_obj          = is_object( $final ) ? clone $final : (object) $final;
            $raw_items          = $final_obj->fixtures ?? ( $final_obj->matches ?? [] );
            $presented_fixtures = [];
            foreach ( $raw_items as $item ) {
                $presented_fixtures[] = self::present_fixture( $item, $tournament, $league, $competition, $event );
            }
            $final_obj->fixtures = $presented_fixtures;
            $presented[ $f_idx ] = $final_obj;
        }

        return $presented;
    }

    private static function resolve_team( mixed $team_dto ): ?object {
        if ( $team_dto instanceof Team_Details_DTO ) {
            return $team_dto->team;
        }
        if ( is_object( $team_dto ) && isset( $team_dto->team ) && is_object( $team_dto->team ) ) {
            return $team_dto->team;
        }

        return null;
    }

    private static function team_id( ?object $team ): int|string|null {
        if ( null === $team ) {
            return null;
        }

        return method_exists( $team, 'get_id' ) ? $team->get_id() : ( $team->id ?? null );
    }

    private static function name_of( object $subject ): string {
        return (string) ( method_exists( $subject, 'get_name' ) ? $subject->get_name() : ( $subject->name ?? '' ) );
    }

    private static function build_match_link( object $fixture, ?object $tournament, ?object $league, ?object $home_team, ?object $away_team, string $detail_link ): string {
        $home_id = $fixture->home_team ?? null;
        $away_id = $fixture->away_team ?? null;
        if ( ! ( is_numeric( $home_id ) && (int) $home_id >= 1 && is_numeric( $away_id ) && (int) $away_id >= 1 ) ) {
            return '';
        }
        if ( empty( $tournament ) ) {
            return $detail_link;
        }

        return '/tournament/' . Util::seo_url( self::name_of( $tournament ) )
            . '/match/' . Util::seo_url( (string) ( $league->title ?? '' ) )
            . '/' . Util::seo_url( (string) ( $home_team->title ?? '' ) ) . '-vs-' . Util::seo_url( (string) ( $away_team->title ?? '' ) )
            . '/' . ( $fixture->id ?? '' ) . '/';
    }

    private static function is_team_entry( ?object $competition ): bool {
        if ( null === $competition ) {
            return false;
        }
        if ( method_exists( $competition, 'is_team_entry' ) ) {
            return (bool) $competition->is_team_entry();
        }

        return ! empty( $competition->is_team_entry );
    }

    private static function is_tournament( ?object $competition ): bool {
        if ( null === $competition ) {
            return false;
        }
        if ( method_exists( $competition, 'is_tournament' ) ) {
            return (bool) $competition->is_tournament();
        }

        return ! empty( $competition->is_tournament );
    }

    private static function team_display_name( string $team_title, ?object $event ): string {
        $event_type = $event->type ?? '';
        if ( $event_type instanceof BackedEnum ) {
            $event_type = $event_type->value;
        }

        return match ( substr( (string) $event_type, 0, 1 ) ) {
            'M'     => str_replace( 'Mens ', '', $team_title ),
            'W'     => str_replace( 'Ladies ', '', $team_title ),
            'X'     => str_replace( 'Mixed ', '', $team_title ),
            default => $team_title,
        };
    }

    private static function build_team_url( int|string|null $team_id, string $team_title, bool $is_tournament, ?object $competition, ?object $league ): ?string {
        if ( ! is_numeric( $team_id ) || -1 === (int) $team_id || $is_tournament || null === $competition || null === $league ) {
            return null;
        }
        $comp_type = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? 'league' );
        if ( $comp_type instanceof BackedEnum ) {
            $comp_type = $comp_type->value;
        }

        return '/' . Util::seo_url( (string) $comp_type ) . 's/' . Util::seo_url( self::name_of( $competition ) )
            . '/' . Util::seo_url( (string) ( $league->title ?? '' ) ) . '/' . Util::seo_url( $team_title );
    }

    /**
     * @return Draw_Player_View_Model[]
     */
    private static function present_players( ?object $team, ?object $tournament ): array {
        $raw_players = $team->players ?? [];
        if ( empty( $raw_players ) || ! is_array( $raw_players ) ) {
            return [];
        }
        $tournament_slug = empty( $tournament ) ? null : Util::seo_url( self::name_of( $tournament ) );
        $players         = [];
        foreach ( $raw_players as $player ) {
            if ( is_object( $player ) ) {
                $name = method_exists( $player, 'get_fullname' ) ? (string) $player->get_fullname() : (string) ( $player->fullname ?? ( $player->display_name ?? '' ) );
            } else {
                $name = (string) $player;
            }
            $name      = trim( $name );
            $url       = ( null !== $tournament_slug && '' !== $name ) ? '/tournament/' . $tournament_slug . '/player/' . Util::seo_url( $name ) . '/' : null;
            $players[] = new Draw_Player_View_Model( name: $name, url: $url );
        }

        return $players;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: string, 3: ?string}
     */
    private static function status_display( object $fixture, string $side, bool $is_winner, bool $is_loser, bool $is_tie ): array {
        if ( $is_winner ) {
            return [ null, null, 'winner', 'W' ];
        }
        if ( $is_loser ) {
            if ( ! empty( $fixture->is_walkover ) ) {
                return [ 'match-warning', __( 'Walkover', 'racketmanager' ), 'blank', null ];
            }
            if ( ! empty( $fixture->is_retired ) ) {
                return [ 'match-warning', __( 'Retired', 'racketmanager' ), 'blank', null ];
            }

            return [ null, null, 'blank', null ];
        }
        if ( $is_tie ) {
            return [ 'match-warning', __( 'Not played', 'racketmanager' ), 'tie', 'T' ];
        }
        if ( empty( $fixture->leg ) && ! empty( $fixture->host ) && $side === $fixture->host ) {
            return [ 'match-info', __( 'H', 'racketmanager' ), 'blank', null ];
        }

        return [ null, null, 'blank', null ];
    }

    /**
     * Build the score cells for one side, mirroring the legacy draw precedence:
     * team-entry points, two-legged tie points, per-rubber sets, then fixture sets.
     *
     * @return Draw_Set_Score_View_Model[]
     */
    private static function present_scores( object $fixture, string $side, ?string $winner_side, bool $is_team_entry ): array {
        $winner_class = ( null !== $winner_side && $winner_side === $side ) ? 'winner' : null;
        if ( empty( $fixture->leg ) && $is_team_entry ) {
            if ( null === $winner_side ) {
                return [];
            }
            $points = 'home' === $side ? ( $fixture->home_points ?? null ) : ( $fixture->away_points ?? null );

            return [ new Draw_Set_Score_View_Model( games: sprintf( '%g', (float) $points ), is_winner: null !== $winner_class, winner_class: $winner_class ) ];
        }
        if ( ! empty( $fixture->leg ) ) {
            if ( null === $winner_side || '-1' === (string) ( $fixture->home_team ?? '' ) || '-1' === (string) ( $fixture->away_team ?? '' ) ) {
                return [];
            }
            $points = 'home' === $side ? ( $fixture->home_points_tie ?? null ) : ( $fixture->away_points_tie ?? null );

            return [ new Draw_Set_Score_View_Model( games: sprintf( '%g', (float) $points ), is_winner: null !== $winner_class, winner_class: $winner_class ) ];
        }
        $scores = [];
        if ( ! empty( $fixture->rubbers ) && is_array( $fixture->rubbers ) ) {
            foreach ( $fixture->rubbers as $rubber ) {
                foreach ( self::sets_of( $rubber ) as $set ) {
                    $score = self::present_set( $set, $side, true );
                    if ( null !== $score ) {
                        $scores[] = $score;
                    }
                }
            }

            return $scores;
        }
        $sets = is_array( $fixture->custom ?? null ) ? ( $fixture->custom['sets'] ?? [] ) : [];
        if ( is_array( $sets ) ) {
            foreach ( $sets as $set ) {
                $score = self::present_set( $set, $side, false );
                if ( null !== $score ) {
                    $scores[] = $score;
                }
            }
        }

        return $scores;
    }

    private static function sets_of( mixed $rubber ): array {
        if ( ! is_object( $rubber ) ) {
            return [];
        }
        $custom = method_exists( $rubber, 'get_custom' ) ? $rubber->get_custom() : ( $rubber->custom ?? [] );
        $sets   = is_array( $custom ) ? ( $custom['sets'] ?? [] ) : [];

        return is_array( $sets ) ? $sets : [];
    }

    /**
     * Present one set for one side.
     *
     * Fixture sets show the tiebreak value on the set winner only; rubber sets show an empty tiebreak marker
     * whenever a tiebreak is recorded (legacy behaviour).
     */
    private static function present_set( mixed $set, string $side, bool $is_rubber ): ?Draw_Set_Score_View_Model {
        if ( $set instanceof Set_Score ) {
            $val     = 'home' === $side ? $set->get_home_games() : $set->get_away_games();
            $val_alt = 'home' === $side ? $set->get_away_games() : $set->get_home_games();
            if ( null === $val ) {
                return null;
            }
            $is_set_win = $val > $val_alt;
            if ( $is_rubber ) {
                $side_tb      = 'home' === $side ? $set->get_home_tiebreak() : $set->get_away_tiebreak();
                $has_tiebreak = ! empty( $side_tb );
                $tiebreak     = '';
            } else {
                $tiebreak     = $set->get_home_tiebreak() ?: $set->get_away_tiebreak();
                $has_tiebreak = ! empty( $tiebreak ) && $is_set_win;
                $tiebreak     = $has_tiebreak ? (string) $tiebreak : '';
            }

            return new Draw_Set_Score_View_Model(
                games: (string) $val,
                is_winner: $is_set_win,
                winner_class: $is_set_win ? 'winner' : null,
                has_tiebreak: $has_tiebreak,
                tiebreak: $tiebreak
            );
        }
        if ( ! is_array( $set ) ) {
            return null;
        }
        $set_ref     = 'home' === $side ? 'player1' : 'player2';
        $set_ref_alt = 'home' === $side ? 'player2' : 'player1';
        if ( ! isset( $set[ $set_ref ] ) || '' === $set[ $set_ref ] ) {
            return null;
        }
        $val        = $set[ $set_ref ];
        $is_set_win = $val > ( $set[ $set_ref_alt ] ?? null );
        if ( $is_rubber ) {
            $has_tiebreak = isset( $set['tiebreak'] );
            $tiebreak     = '';
        } else {
            $has_tiebreak = isset( $set['tiebreak'] ) && $is_set_win;
            $tiebreak     = $has_tiebreak ? (string) $set['tiebreak'] : '';
        }

        return new Draw_Set_Score_View_Model(
            games: (string) $val,
            is_winner: $is_set_win,
            winner_class: $is_set_win ? 'winner' : null,
            has_tiebreak: $has_tiebreak,
            tiebreak: $tiebreak
        );
    }
}
