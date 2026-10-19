<?php
declare( strict_types=1 );

/**
 * Admin Plan Presenter
 *
 * @package RacketManager
 * @subpackage Admin/Presenters
 */

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Admin\View_Models\Cup_Plan_Page_View_Model;
use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Plan_Final_Card_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Plan_Page_View_Model;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Player;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;
use Throwable;
use function ceil;
use function count;
use function floor;
use function gmdate;
use function in_array;
use function is_array;
use function is_numeric;
use function strtotime;

/**
 * Builds the cup and tournament planner view models: sizing, default courts, schedule slots,
 * per-slot player clash names and the draggable final cards.
 */
final readonly class Admin_Plan_Presenter {

    public function __construct(
        private Fixture_Detail_Service $fixture_detail_service,
        private Player_Repository_Interface $player_repository,
        private Fixture_Relation_Loader $relation_loader,
    ) {
    }

    /**
     * Build view model for cup planner admin page.
     *
     * @param object    $competition
     * @param object    $cup_season
     * @param string    $season
     * @param array     $final_matches Fixture or Fixture_Details_DTO entries.
     * @param string    $tab
     * @param int       $num_events
     * @param Error_Bag $errors
     *
     * @return Cup_Plan_Page_View_Model
     */
    public function present_cup_plan(
        object $competition,
        object $cup_season,
        string $season,
        array $final_matches,
        string $tab,
        int $num_events,
        Error_Bag $errors,
    ): Cup_Plan_Page_View_Model {
        $num_matches = count( $final_matches );
        if ( empty( $num_events ) ) {
            $sizing_courts = 1;
            $max_schedules = 0;
        } else {
            $sizing_courts = $num_events;
            $max_schedules = (int) ceil( $num_matches / $sizing_courts ) + 1;
        }

        $time_increment = (string) ( $cup_season->time_increment ?? '' );
        if ( '01:00:00' === $time_increment ) {
            $max_schedules *= 2;
        }

        $column_width = (int) floor( 12 / $sizing_courts );
        $match_length = '' === $time_increment ? 0 : (int) strtotime( $time_increment );
        $court_count  = (int) ( $cup_season->num_courts ?? 0 );
        $start_ref    = (string) ( $cup_season->starttime ?? '' );

        $order_of_play = $this->normalise_order_of_play(
            is_array( $cup_season->orderofplay ?? null ) ? $cup_season->orderofplay : null,
            $court_count,
            $start_ref,
            'matches'
        );

        $schedule_slots = $this->build_schedule_slots( $order_of_play, 'matches', $court_count, $max_schedules, $start_ref, $match_length );

        return new Cup_Plan_Page_View_Model(
            competition: $competition,
            cup_season: $cup_season,
            season: $season,
            final_matches: $final_matches,
            tab: $tab,
            num_events: $num_events,
            order_of_play: $order_of_play,
            errors: $errors,
            max_schedules: $max_schedules,
            column_width: $column_width,
            match_length: $match_length,
            schedule_slots: $schedule_slots,
            final_cards: $this->present_final_cards( $final_matches ),
        );
    }

    /**
     * Build view model for tournament planner admin page.
     *
     * @param object    $tournament
     * @param array     $final_matches Fixture_Details_DTO or Fixture entries.
     * @param string    $tab
     * @param Error_Bag $errors
     * @param array     $submitted Submitted config form values (redisplayed after a failed save).
     *
     * @return Tournament_Plan_Page_View_Model
     */
    public function present_tournament_plan(
        object $tournament,
        array $final_matches,
        string $tab,
        Error_Bag $errors,
        array $submitted = array(),
    ): Tournament_Plan_Page_View_Model {
        $num_matches = count( $final_matches );
        $court_count = (int) $tournament->get_num_courts();
        if ( 0 === $court_count ) {
            $sizing_courts = 1;
            $max_schedules = 0;
        } else {
            $sizing_courts = $court_count;
            $max_schedules = (int) ceil( $num_matches / $sizing_courts ) + 1;
        }

        $time_increment = (string) ( $tournament->get_time_increment() ?? '' );
        if ( '01:00:00' === $time_increment ) {
            $max_schedules *= 2;
        }

        $column_width   = (int) floor( 12 / $sizing_courts );
        $fixture_length = '' === $time_increment ? 0 : (int) strtotime( $time_increment );
        $start_ref      = (string) ( $tournament->get_start_time() ?? '' );
        $stored_order   = $tournament->get_order_of_play();

        $order_of_play = $this->normalise_order_of_play(
            is_array( $stored_order ) ? $stored_order : null,
            $court_count,
            $start_ref,
            'fixtures'
        );

        $schedule_slots = $this->build_schedule_slots( $order_of_play, 'fixtures', $court_count, $max_schedules, $start_ref, $fixture_length );

        return new Tournament_Plan_Page_View_Model(
            tournament: $tournament,
            final_matches: $final_matches,
            tab: $tab,
            order_of_play: $order_of_play,
            errors: $errors,
            max_schedules: $max_schedules,
            column_width: $column_width,
            fixture_length: $fixture_length,
            schedule_slots: $schedule_slots,
            final_cards: $this->present_final_cards( $final_matches ),
            config_values: array(
                'start_time'     => (string) ( $submitted['startTime'] ?? $start_ref ),
                'time_increment' => (string) ( $submitted['timeIncrement'] ?? $time_increment ),
                'num_courts'     => (string) ( $submitted['numCourtsAvailable'] ?? $court_count ),
            ),
        );
    }

    /**
     * Use the stored order of play when it matches the number of courts, otherwise build defaults.
     *
     * @param array|null $stored
     * @param int        $court_count
     * @param string     $start_time
     * @param string     $items_key   'matches' (cup) or 'fixtures' (tournament).
     *
     * @return array<int, array{court:string,start_time:string,matches?:array,fixtures?:array}>
     */
    private function normalise_order_of_play( ?array $stored, int $court_count, string $start_time, string $items_key ): array {
        $order_of_play = array();
        if ( null !== $stored && count( $stored ) === $court_count ) {
            foreach ( $stored as $court => $details ) {
                $details                 = (array) $details;
                $order_of_play[ $court ] = array(
                    'court'      => (string) ( $details['court'] ?? 'Court ' . ( (int) $court + 1 ) ),
                    'start_time' => (string) ( $details['start_time'] ?? $start_time ),
                    $items_key   => is_array( $details[ $items_key ] ?? null ) ? $details[ $items_key ] : array(),
                );
            }

            return $order_of_play;
        }

        for ( $i = 0; $i < $court_count; $i++ ) {
            $order_of_play[ $i ] = array(
                'court'      => 'Court ' . ( $i + 1 ),
                'start_time' => $start_time,
                $items_key   => array(),
            );
        }

        return $order_of_play;
    }

    /**
     * Build the schedule grid: one slot per time row with a cell per court and the names of clashing players.
     *
     * @param array  $order_of_play
     * @param string $items_key
     * @param int    $court_count
     * @param int    $max_schedules
     * @param string $start_ref
     * @param int    $step
     *
     * @return object[]
     */
    private function build_schedule_slots( array $order_of_play, string $items_key, int $court_count, int $max_schedules, string $start_ref, int $step ): array {
        $start_time  = '' === $start_ref ? 0 : (int) strtotime( $start_ref );
        $time_offset = 0;
        $slots       = array();

        for ( $slot = 0; $slot < $max_schedules; $slot++ ) {
            $scheduled_players = array();
            $player_warnings   = array();
            $cells             = array();

            for ( $court = 0; $court < $court_count; $court++ ) {
                $fixture_id = isset( $order_of_play[ $court ][ $items_key ][ $slot ] ) ? (int) $order_of_play[ $court ][ $items_key ][ $slot ] : null;
                if ( $fixture_id ) {
                    foreach ( $this->fixture_detail_service->collect_fixture_player_ids( $fixture_id ) as $player_id ) {
                        if ( in_array( $player_id, $scheduled_players, true ) ) {
                            $name = $this->player_name( (int) $player_id );
                            if ( null !== $name ) {
                                $player_warnings[] = $name;
                            }
                        }
                        $scheduled_players[] = $player_id;
                    }
                }
                $cells[] = (object) array(
                    'court'       => $court,
                    'slot'        => $slot,
                    'fixture_id'  => $fixture_id,
                    'time_offset' => $time_offset,
                );
            }

            $slots[] = (object) array(
                'time_formatted'  => gmdate( 'H:i', $start_time ),
                'time_offset'     => $time_offset,
                'courts'          => $cells,
                'player_warnings' => $player_warnings,
            );

            $start_time  += $step;
            $time_offset += $step;
        }

        return $slots;
    }

    private function player_name( int $player_id ): ?string {
        $player = $this->player_repository->find_by_id( $player_id );
        if ( $player instanceof Player ) {
            return $player->get_fullname();
        }

        return null;
    }

    /**
     * @param array $final_matches
     *
     * @return Plan_Final_Card_View_Model[]
     */
    private function present_final_cards( array $final_matches ): array {
        $cards = array();
        foreach ( $final_matches as $final_match ) {
            if ( $final_match instanceof Fixture_Details_DTO || $final_match instanceof Fixture ) {
                $cards[] = $this->present_final_card( $final_match );
            }
        }

        return $cards;
    }

    private function present_final_card( Fixture_Details_DTO|Fixture $match ): Plan_Final_Card_View_Model {
        $dto     = $match instanceof Fixture_Details_DTO ? $match : null;
        $fixture = $dto ? $dto->fixture : $match;
        $league  = $dto ? $dto->league : $fixture->get_league();

        $home_ref = (string) $fixture->get_home_team();
        $away_ref = (string) $fixture->get_away_team();
        $home_set = is_numeric( $home_ref );
        $away_set = is_numeric( $away_ref );

        if ( $dto ) {
            $home_title = $home_set ? $dto->home_team?->team->get_name() : ( $dto->prev_home_fixture_title ?? $home_ref );
            $away_title = $away_set ? $dto->away_team?->team->get_name() : ( $dto->prev_away_fixture_title ?? $away_ref );
            $home_club  = $home_set ? $dto->home_team?->club?->shortcode : null;
            $away_club  = $away_set ? $dto->away_team?->club?->shortcode : null;
        } else {
            // Competition::get_matches() only loads the league, so load team read data here.
            $this->relation_loader->load_teams( $fixture );
            $teams      = $fixture->get_loaded_teams();
            $home_title = $home_set ? ( $teams['home']->title ?? '' ) : $this->placeholder_title( $fixture, $home_ref );
            $away_title = $away_set ? ( $teams['away']->title ?? '' ) : $this->placeholder_title( $fixture, $away_ref );
            $home_club  = $home_set ? ( $teams['home']->club->shortcode ?? null ) : null;
            $away_club  = $away_set ? ( $teams['away']->club->shortcode ?? null ) : null;
        }

        return new Plan_Final_Card_View_Model(
            id: (int) $fixture->get_id(),
            league_title: (string) ( $league->title ?? '' ),
            btn_type: ( $home_set && $away_set ) ? 'success' : 'warning',
            home_title: (string) $home_title,
            away_title: (string) $away_title,
            is_home_pending: ! $home_set,
            is_away_pending: ! $away_set,
            home_club: null !== $home_club ? (string) $home_club : null,
            away_club: null !== $away_club ? (string) $away_club : null,
        );
    }

    /**
     * Resolve a "winner of / loser of" title for a placeholder team reference, falling back to the reference.
     */
    private function placeholder_title( Fixture $fixture, string $team_ref ): string {
        $league = $fixture->get_league();
        if ( '' === $team_ref || ! $league ) {
            return $team_ref;
        }
        try {
            $title = $this->fixture_detail_service->resolve_placeholder_title( $team_ref, (string) $fixture->get_season(), $league, $fixture->get_final() );
        } catch ( Throwable ) {
            $title = null;
        }

        return $title ?? $team_ref;
    }
}
