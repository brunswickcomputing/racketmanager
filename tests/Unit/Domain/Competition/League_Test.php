<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionMethod;
use stdClass;

class Testable_League extends League {
    protected function resolve_standings_match( mixed $match ): mixed {
        return $match;
    }

    public function get_public_standings_data( int $team_id, array $data, array $matches = [] ): array {
        return $this->get_standings_data( $team_id, $data, $matches );
    }

    public function calculate_public_points( object $team, false|array $matches = false ): array {
        $method = new ReflectionMethod( League::class, 'calculate_points' );
        return $method->invoke( $this, $team, $matches );
    }
}

class League_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $GLOBALS['wpdb'] = new \wpdb();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    private function create_test_league( array $overrides = [] ): Testable_League {
        $league_raw = (object) [
            'id'       => $overrides['id'] ?? 1,
            'title'    => $overrides['title'] ?? 'Division 1',
            'event_id' => $overrides['event_id'] ?? 10,
            'settings' => $overrides['settings'] ?? [],
            'seasons'  => $overrides['seasons'] ?? [ '2024-25' ],
        ];

        $league = new Testable_League( $league_raw );
        $league->num_rubbers     = $overrides['num_rubbers'] ?? 6;
        $league->num_sets        = $overrides['num_sets'] ?? 2;
        $league->num_sets_to_win = $overrides['num_sets_to_win'] ?? 2;
        $league->scoring         = $overrides['scoring'] ?? 'TB';
        $league->point_rule      = $overrides['point_rule'] ?? 'three';
        $league->current_season  = $overrides['current_season'] ?? [ 'name' => '2024-25' ];

        return $league;
    }

    private function make_sets( array $scores ): array {
        $sets = [];
        foreach ( $scores as $index => $score ) {
            $sets[ $index + 1 ] = [
                'player1' => (string) $score[0],
                'player2' => (string) $score[1],
            ];
        }
        return $sets;
    }

    private function create_rubber( array $attributes = [] ): object {
        return (object) array_merge( [
            'id'           => 1,
            'winner_id'    => '10',
            'loser_id'     => '20',
            'home_points'  => 2,
            'away_points'  => 0,
            'is_walkover'  => false,
            'is_shared'    => false,
            'is_retired'   => false,
            'is_abandoned' => false,
            'custom'       => [],
            'sets'         => $this->make_sets( [ [ '6', '4' ], [ '6', '2' ] ] ),
        ], $attributes );
    }

    private function create_match( array $attributes = [], array $rubbers = [] ): \Racketmanager\Domain\Racketmanager_Match {
        $match = new class extends \Racketmanager\Domain\Racketmanager_Match {
            private array $rubbers_list = [];

            public function __construct() {
                $this->teams = [
                    'home' => (object) [ 'status' => '' ],
                    'away' => (object) [ 'status' => '' ],
                ];
                $this->is_cancelled = false;
                $this->status = 0;
            }

            public function set_test_rubbers( array $rubbers ): void {
                $this->rubbers_list = $rubbers;
                $this->league = (object) [ 'num_rubbers' => count( $rubbers ) ];
            }

            public function get_rubbers( ?int $player = null, bool $count = false ): array|int {
                if ( $count ) {
                    return count( $this->rubbers_list );
                }
                return $this->rubbers_list;
            }
        };

        $match->id = $attributes['id'] ?? 1;
        $match->home_team = (string) ( $attributes['home_team'] ?? '10' );
        $match->away_team = (string) ( $attributes['away_team'] ?? '20' );
        $match->home_points = isset( $attributes['home_points'] ) ? (float) $attributes['home_points'] : 2.0;
        $match->away_points = isset( $attributes['away_points'] ) ? (float) $attributes['away_points'] : 0.0;
        $match->winner_id = isset( $attributes['winner_id'] ) ? (int) $attributes['winner_id'] : 10;
        $match->loser_id = isset( $attributes['loser_id'] ) ? (int) $attributes['loser_id'] : 20;
        $match->status = $attributes['status'] ?? 0;
        $match->is_cancelled = $attributes['is_cancelled'] ?? false;
        $match->set_test_rubbers( $rubbers );

        return $match;
    }

    public function test_constructs_league_with_competition_settings_object(): void {
        $league_raw = (object) [
            'id'       => 1,
            'title'    => 'Division 1',
            'event_id' => 10,
            'settings' => [],
            'seasons'  => [ '2024-25' ],
        ];

        $league = new League( $league_raw );

        $this->assertSame( 1, $league->get_id() );
        $this->assertSame( 'Division 1', $league->get_name() );
        $this->assertSame( 'league', $league->mode );
        $this->assertSame( 'three', $league->point_rule );
        $this->assertSame( 'tennis', $league->sport );
    }

    public function test_standings_data_credits_shared_sets_when_rubber_is_retired_and_set_unplayed_with_empty_strings(): void {
        $league = $this->create_test_league();

        // Rubber retired after 1st set (set 2 unplayed, stored as empty strings as in Set_Score_Validator)
        $rubber = $this->create_rubber( [
            'is_retired' => true,
            'custom'     => [ 'retired' => 'away' ],
            'sets'       => $this->make_sets( [ [ '6', '4' ], [ '', '' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        // Home won set 1 (6-4), unplayed set 2 (empty strings) is credited as shared set for both teams
        $this->assertSame( 1, $home_data['sets_won'] );
        $this->assertSame( 0, $home_data['sets_allowed'] );
        $this->assertSame( 1, $home_data['sets_shared'] );
        $this->assertSame( 6, $home_data['games_won'] );
        $this->assertSame( 4, $home_data['games_allowed'] );
        $this->assertSame( 1, $home_data['rubbers_won'] );
        $this->assertSame( 1, $home_data['straight_set']['win'] );

        // Away lost set 1, unplayed set 2 is credited as shared set
        $this->assertSame( 0, $away_data['sets_won'] );
        $this->assertSame( 1, $away_data['sets_allowed'] );
        $this->assertSame( 1, $away_data['sets_shared'] );
        $this->assertSame( 4, $away_data['games_won'] );
        $this->assertSame( 6, $away_data['games_allowed'] );
        $this->assertSame( 1, $away_data['straight_set']['lost'] );
    }

    public function test_standings_data_credits_shared_sets_when_rubber_is_retired_and_set_unplayed_with_missing_key(): void {
        $league = $this->create_test_league();

        // Rubber retired after 1st set (set 2 unplayed and key omitted)
        $rubber = $this->create_rubber( [
            'is_retired' => true,
            'custom'     => [ 'retired' => 'away' ],
            'sets'       => $this->make_sets( [ [ '6', '4' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        $this->assertSame( 1, $home_data['sets_won'] );
        $this->assertSame( 0, $home_data['sets_allowed'] );
        $this->assertSame( 1, $home_data['sets_shared'] );
        $this->assertSame( 6, $home_data['games_won'] );
        $this->assertSame( 4, $home_data['games_allowed'] );

        $this->assertSame( 0, $away_data['sets_won'] );
        $this->assertSame( 1, $away_data['sets_allowed'] );
        $this->assertSame( 1, $away_data['sets_shared'] );
        $this->assertSame( 4, $away_data['games_won'] );
        $this->assertSame( 6, $away_data['games_allowed'] );
    }

    public function test_standings_data_credits_shared_sets_when_rubber_is_retired_with_multi_set_match(): void {
        $league = $this->create_test_league( [ 'num_sets' => 3 ] );

        // Rubber retired during set 2 (set 1 won 6-4, set 2 retired by away at 1-2, set 3 empty string unplayed)
        $rubber = $this->create_rubber( [
            'is_retired' => true,
            'custom'     => [ 'retired' => 'away' ],
            'sets'       => $this->make_sets( [ [ '6', '4' ], [ '1', '2' ], [ '', '' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        // Home won set 1 (6-4) and set 2 (by retirement); set 3 was unplayed and is shared
        $this->assertSame( 2, $home_data['sets_won'] );
        $this->assertSame( 0, $home_data['sets_allowed'] );
        $this->assertSame( 1, $home_data['sets_shared'] );
        $this->assertSame( 7, $home_data['games_won'] );
        $this->assertSame( 6, $home_data['games_allowed'] );

        // Away lost sets 1 & 2; set 3 was unplayed and is shared
        $this->assertSame( 0, $away_data['sets_won'] );
        $this->assertSame( 2, $away_data['sets_allowed'] );
        $this->assertSame( 1, $away_data['sets_shared'] );
        $this->assertSame( 6, $away_data['games_won'] );
        $this->assertSame( 7, $away_data['games_allowed'] );
    }

    public function test_standings_data_credits_shared_sets_when_rubber_is_abandoned(): void {
        $league = $this->create_test_league();

        // Rubber abandoned during 1st set (4-4), set 2 unplayed
        $rubber = $this->create_rubber( [
            'is_abandoned' => true,
            'home_points'  => 1.5,
            'away_points'  => 0.5,
            'sets'         => $this->make_sets( [ [ '4', '4' ], [ '', '' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        // Abandoned rubber: abandoned set and unplayed set are both shared sets
        $this->assertSame( 0, $home_data['sets_won'] );
        $this->assertSame( 0, $home_data['sets_allowed'] );
        $this->assertSame( 2, $home_data['sets_shared'] );
        $this->assertSame( 4, $home_data['games_won'] );
        $this->assertSame( 4, $home_data['games_allowed'] );
        $this->assertSame( 1, $home_data['split_set']['win'] );
        $this->assertSame( 0, $home_data['straight_set']['win'] );

        $this->assertSame( 0, $away_data['sets_won'] );
        $this->assertSame( 0, $away_data['sets_allowed'] );
        $this->assertSame( 2, $away_data['sets_shared'] );
        $this->assertSame( 4, $away_data['games_won'] );
        $this->assertSame( 4, $away_data['games_allowed'] );
        $this->assertSame( 1, $away_data['split_set']['lost'] );
        $this->assertSame( 0, $away_data['straight_set']['lost'] );
    }

    public function test_standings_data_walkover_rubber_allocations(): void {
        $league = $this->create_test_league();

        $rubber = $this->create_rubber( [
            'is_walkover' => true,
            'sets'        => [],
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        // Walkover: num_sets_to_win = 2, games_to_win = 6 -> 12 games
        $this->assertSame( 2, $home_data['sets_won'] );
        $this->assertSame( 12, $home_data['games_won'] );
        $this->assertSame( 1, $home_data['straight_set']['win'] );
        $this->assertSame( 1, $home_data['rubbers_won'] );

        $this->assertSame( 2, $away_data['sets_allowed'] );
        $this->assertSame( 12, $away_data['games_allowed'] );
        $this->assertSame( 1, $away_data['no_player'] );
        $this->assertSame( 1, $away_data['straight_set']['lost'] );
    }

    public function test_standings_data_split_and_straight_set_evaluations_for_home_team(): void {
        $league = $this->create_test_league();

        // Rubber 1: Home split set win (Home loses set 1 4-6, wins set 2 6-4, home_points = 1.5, away_points = 0.5)
        $rubber1 = $this->create_rubber( [
            'home_points' => 1.5,
            'away_points' => 0.5,
            'sets'        => $this->make_sets( [ [ '4', '6' ], [ '6', '4' ] ] ),
        ] );

        // Rubber 2: Home straight set win (Home wins set 1 6-2, set 2 6-3, home_points = 2, away_points = 0)
        $rubber2 = $this->create_rubber( [
            'sets'        => $this->make_sets( [ [ '6', '2' ], [ '6', '3' ] ] ),
        ] );

        // Rubber 3: Home split set loss (Home wins set 1 6-4, loses set 2 4-6, home_points = 0.5, away_points = 1.5)
        $rubber3 = $this->create_rubber( [
            'winner_id'   => '20',
            'loser_id'    => '10',
            'home_points' => 0.5,
            'away_points' => 1.5,
            'sets'        => $this->make_sets( [ [ '6', '4' ], [ '4', '6' ] ] ),
        ] );

        // Rubber 4: Home straight set loss (Home loses set 1 2-6, set 2 1-6, home_points = 0, away_points = 2)
        $rubber4 = $this->create_rubber( [
            'winner_id'   => '20',
            'loser_id'    => '10',
            'home_points' => 0,
            'away_points' => 2,
            'sets'        => $this->make_sets( [ [ '2', '6' ], [ '1', '6' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber1, $rubber2, $rubber3, $rubber4 ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );

        $this->assertSame( 4, $home_data['sets_won'] );
        $this->assertSame( 4, $home_data['sets_allowed'] );
        $this->assertSame( 35, $home_data['games_won'] );
        $this->assertSame( 37, $home_data['games_allowed'] );
        $this->assertSame( 1, $home_data['straight_set']['win'] );
        $this->assertSame( 1, $home_data['split_set']['win'] );
        $this->assertSame( 1, $home_data['split_set']['lost'] );
        $this->assertSame( 1, $home_data['straight_set']['lost'] );
        $this->assertSame( 2, $home_data['rubbers_won'] );
    }

    public function test_standings_data_split_and_straight_set_evaluations_for_away_team(): void {
        $league = $this->create_test_league();

        // Rubber 1: Away split set win (Away loses set 1 4-6, wins set 2 6-4, home_points = 0.5, away_points = 1.5)
        $rubber1 = $this->create_rubber( [
            'winner_id'   => '20',
            'loser_id'    => '10',
            'home_points' => 0.5,
            'away_points' => 1.5,
            'sets'        => $this->make_sets( [ [ '6', '4' ], [ '4', '6' ] ] ),
        ] );

        // Rubber 2: Away straight set win (Away wins set 1 6-2, set 2 6-3, home_points = 0, away_points = 2)
        $rubber2 = $this->create_rubber( [
            'winner_id'   => '20',
            'loser_id'    => '10',
            'home_points' => 0,
            'away_points' => 2,
            'sets'        => $this->make_sets( [ [ '2', '6' ], [ '3', '6' ] ] ),
        ] );

        // Rubber 3: Away split set loss (Away wins set 1 6-4, loses set 2 4-6, home_points = 1.5, away_points = 0.5)
        $rubber3 = $this->create_rubber( [
            'home_points' => 1.5,
            'away_points' => 0.5,
            'sets'        => $this->make_sets( [ [ '4', '6' ], [ '6', '4' ] ] ),
        ] );

        // Rubber 4: Away straight set loss (Away loses set 1 2-6, set 2 1-6, home_points = 2, away_points = 0)
        $rubber4 = $this->create_rubber( [
            'sets'        => $this->make_sets( [ [ '6', '2' ], [ '6', '1' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '20',
            'loser_id'  => '10',
        ], [ $rubber1, $rubber2, $rubber3, $rubber4 ] );

        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        $this->assertSame( 4, $away_data['sets_won'] );
        $this->assertSame( 4, $away_data['sets_allowed'] );
        $this->assertSame( 35, $away_data['games_won'] );
        $this->assertSame( 37, $away_data['games_allowed'] );
        $this->assertSame( 1, $away_data['straight_set']['win'] );
        $this->assertSame( 1, $away_data['split_set']['win'] );
        $this->assertSame( 1, $away_data['split_set']['lost'] );
        $this->assertSame( 1, $away_data['straight_set']['lost'] );
        $this->assertSame( 2, $away_data['rubbers_won'] );
    }

    public function test_standings_data_split_set_evaluations_for_abandoned_away_winner_with_points_threshold(): void {
        $league = $this->create_test_league( [ 'num_sets' => 3 ] );

        $rubber = $this->create_rubber( [
            'winner_id'    => 20,
            'loser_id'     => 10,
            'home_points'  => 0.5,
            'away_points'  => 1.5,
            'is_abandoned' => true,
            'sets'         => $this->make_sets( [ [ '4', '4' ], [ '', '' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => 10,
            'away_team' => 20,
        ], [ $rubber ] );

        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        $this->assertSame( 1, $away_data['split_set']['win'] );
    }

    public function test_calculate_points_for_team(): void {
        $league = $this->create_test_league();

        $team = (object) [
            'id'     => 10,
            'season' => '2024-25',
        ];

        $match1 = $this->create_match( [
            'home_team'   => '10',
            'away_team'   => '20',
            'home_points' => 4.5,
            'away_points' => 1.5,
            'winner_id'   => 10,
            'loser_id'    => 20,
        ] );

        $match2 = $this->create_match( [
            'home_team'   => '30',
            'away_team'   => '10',
            'home_points' => 2.0,
            'away_points' => 4.0,
            'winner_id'   => 10,
            'loser_id'    => 30,
        ] );

        $points = $league->calculate_public_points( $team, [ $match1, $match2 ] );

        $this->assertEquals( 8.5, $points['plus'] );
        $this->assertEquals( 3.5, $points['minus'] );
    }

    public function test_standings_data_handles_integer_rubber_ids_with_string_match_team_ids(): void {
        $league = $this->create_test_league();

        $rubber = $this->create_rubber( [
            'winner_id'   => 10,
            'loser_id'    => 20,
            'home_points' => 2,
            'away_points' => 0,
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => 10,
            'loser_id'  => 20,
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        $this->assertSame( 1, $home_data['rubbers_won'] );
        $this->assertSame( 1, $home_data['straight_set']['win'] );
        $this->assertSame( 0, $home_data['straight_set']['lost'] );
        $this->assertSame( 1, $away_data['straight_set']['lost'] );
        $this->assertSame( 0, $away_data['straight_set']['win'] );
    }

    public function test_standings_data_handles_integer_match_team_ids(): void {
        $league = $this->create_test_league();

        $rubber = $this->create_rubber( [
            'sets' => $this->make_sets( [ [ '6', '2' ], [ '6', '3' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => 10,
            'away_team' => 20,
            'winner_id' => 10,
            'loser_id'  => 20,
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        $this->assertSame( 2, $home_data['sets_won'] );
        $this->assertSame( 0, $home_data['sets_allowed'] );
        $this->assertSame( 12, $home_data['games_won'] );
        $this->assertSame( 5, $home_data['games_allowed'] );

        $this->assertSame( 0, $away_data['sets_won'] );
        $this->assertSame( 2, $away_data['sets_allowed'] );
        $this->assertSame( 5, $away_data['games_won'] );
        $this->assertSame( 12, $away_data['games_allowed'] );
    }

    public function test_standings_data_match_tiebreak_scoring(): void {
        $league = $this->create_test_league( [ 'num_sets' => 3, 'scoring' => 'TM' ] );

        // Rubber with 3 sets: set 1 home (6-4), set 2 away (4-6), set 3 MTB home (10-8)
        $rubber = $this->create_rubber( [
            'home_points' => 1.5,
            'away_points' => 0.5,
            'sets'        => $this->make_sets( [ [ '6', '4' ], [ '4', '6' ], [ '10', '8' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        // In MTB, 3rd set adds 1 game to winner, 0 to loser
        $this->assertSame( 2, $home_data['sets_won'] );
        $this->assertSame( 1, $home_data['sets_allowed'] );
        $this->assertSame( 11, $home_data['games_won'] ); // 6 + 4 + 1
        $this->assertSame( 10, $home_data['games_allowed'] ); // 4 + 6 + 0
        $this->assertSame( 1, $home_data['split_set']['win'] );

        $this->assertSame( 1, $away_data['sets_won'] );
        $this->assertSame( 2, $away_data['sets_allowed'] );
        $this->assertSame( 10, $away_data['games_won'] ); // 4 + 6 + 0
        $this->assertSame( 11, $away_data['games_allowed'] ); // 6 + 4 + 1
        $this->assertSame( 1, $away_data['split_set']['lost'] );
    }

    public function test_standings_data_explicit_shared_set_score(): void {
        $league = $this->create_test_league();

        $rubber = $this->create_rubber( [
            'home_points' => 1.5,
            'away_points' => 0.5,
            'sets'        => $this->make_sets( [ [ '6', '4' ], [ 'S', 'S' ] ] ),
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        $this->assertSame( 1, $home_data['sets_won'] );
        $this->assertSame( 0, $home_data['sets_allowed'] );
        $this->assertSame( 1, $home_data['sets_shared'] );

        $this->assertSame( 0, $away_data['sets_won'] );
        $this->assertSame( 1, $away_data['sets_allowed'] );
        $this->assertSame( 1, $away_data['sets_shared'] );
    }

    public function test_standings_data_full_match_walkover(): void {
        $league = $this->create_test_league( [ 'num_rubbers' => 2 ] );

        $rubber1 = $this->create_rubber( [
            'is_walkover' => true,
            'sets'        => [],
        ] );

        $rubber2 = $this->create_rubber( [
            'is_walkover' => true,
            'sets'        => [],
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber1, $rubber2 ] );

        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        $this->assertSame( 2, $away_data['no_player'] );
        $this->assertSame( 2, $away_data['no_team'] );
    }

    public function test_standings_data_shared_match_and_rubber_counts(): void {
        $league = $this->create_test_league();
        $league->num_rubbers = 2;

        $rubber1 = $this->create_rubber( [
            'winner_id'   => '10',
            'loser_id'    => '20',
            'home_points' => 2,
            'away_points' => 0,
        ] );

        $rubber2 = $this->create_rubber( [
            'is_shared'   => true,
            'winner_id'   => '-1',
            'loser_id'    => '-1',
            'home_points' => 1,
            'away_points' => 1,
            'sets'        => [],
        ] );

        $match = $this->create_match( [
            'home_team' => '10',
            'away_team' => '20',
            'winner_id' => '10',
            'loser_id'  => '20',
        ], [ $rubber1, $rubber2 ] );

        $home_data = $league->get_public_standings_data( 10, [], [ $match ] );
        $away_data = $league->get_public_standings_data( 20, [], [ $match ] );

        $this->assertSame( 1, $home_data['rubbers_won'] );
        $this->assertSame( 1, $home_data['rubbers_shared'] );
        $this->assertSame( 1, $home_data['matches_won'] );
        $this->assertSame( 0, $home_data['matches_shared'] );

        $this->assertSame( 0, $away_data['rubbers_won'] );
        $this->assertSame( 1, $away_data['rubbers_shared'] );
        $this->assertSame( 0, $away_data['matches_won'] );
        $this->assertSame( 0, $away_data['matches_shared'] );
    }

    public function test_calculate_points_under_rubber_count_and_games_rules(): void {
        $league = $this->create_test_league();

        $team = (object) [
            'id'     => 10,
            'season' => '2024-25',
        ];

        // Test with point_rule = 'tennisRubber'
        $league->point_rule = 'tennisRubber';
        $points_rc = $league->calculate_public_points( $team, [] );
        $this->assertIsArray( $points_rc );
        $this->assertArrayHasKey( 'plus', $points_rc );
        $this->assertArrayHasKey( 'minus', $points_rc );

        // Test with point_rule = 'score'
        $league->point_rule = 'score';
        $points_games = $league->calculate_public_points( $team, [] );
        $this->assertIsArray( $points_games );
        $this->assertArrayHasKey( 'plus', $points_games );
        $this->assertArrayHasKey( 'minus', $points_games );
    }
}
