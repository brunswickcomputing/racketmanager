<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Scoring\Set_Score;
use stdClass;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Admin_Championship_Finals_Template_Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_options'] );
        parent::tearDown();
    }

    private function create_named_team( string $name ): object {
        return new class( $name ) {
            public function __construct( private string $name ) {}
            public function get_name(): string {
                return $this->name;
            }
        };
    }

    private function create_league(
        Competition_Type $type = Competition_Type::CUP,
        bool $is_tournament = false,
        bool $is_cup = true,
        int $num_sets = 1
    ): stdClass {
        $competition                = new stdClass();
        $competition->id            = 10;
        $competition->type          = $type;
        $competition->is_tournament = $is_tournament;
        $competition->is_cup        = $is_cup;
        $competition->is_team_entry = true;

        $event              = new stdClass();
        $event->id          = 20;
        $event->competition = $competition;

        $league                 = new stdClass();
        $league->id             = 30;
        $league->event          = $event;
        $league->num_rubbers    = 0;
        $league->num_sets       = $num_sets;
        $league->current_season = array( 'name' => '2026' );
        $league->championship   = new class() {
            public function get_current_final_key(): string {
                return 'final';
            }
        };

        return $league;
    }

    private function create_match( array $overrides = array() ): stdClass {
        $match              = new stdClass();
        $match->id          = 101;
        $match->date        = '2026-07-12 14:00:00';
        $match->season      = '2026';
        $match->final       = 'final';
        $match->home_team   = '1';
        $match->away_team   = '2';
        $match->location    = 'Centre Court';
        $match->hour        = '14';
        $match->minutes     = '00';
        $match->link        = '/match/101/';
        $match->sets        = array();
        $match->custom      = array();
        $match->home_points = null;
        $match->away_points = null;

        foreach ( $overrides as $key => $value ) {
            $match->$key = $value;
        }

        return $match;
    }

    private function create_fixture_details(
        object $league,
        object $match,
        object $home_team,
        object $away_team
    ): stdClass {
        $fixture_details              = new stdClass();
        $fixture_details->fixture     = $match;
        $fixture_details->league      = $league;
        $fixture_details->event       = $league->event;
        $fixture_details->competition = $league->event->competition;
        $fixture_details->home_team   = isset( $home_team->team ) || isset( $home_team->title ) ? $home_team : (object) array( 'team' => $home_team );
        $fixture_details->away_team   = isset( $away_team->team ) || isset( $away_team->title ) ? $away_team : (object) array( 'team' => $away_team );

        return $fixture_details;
    }

    private function present_finals_round(
        array $fixtures,
        ?object $tournament,
        object $league,
        string $season = '2026',
        string $key = 'final',
        string $name = 'Final',
        int $num_matches = 1
    ): array {
        $presenter = new Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() );
        return $presenter->present_finals(
            array(
                (object) array(
                    'key'         => $key,
                    'name'        => $name,
                    'num_matches' => $num_matches,
                    'fixtures'    => $fixtures,
                ),
            ),
            $tournament,
            $league,
            $season
        );
    }

    private function render_template( string $template_name, array $vars = array() ): string {
        extract( $vars, EXTR_SKIP );
        $errors = array();
        set_error_handler(
            static function ( int $errno, string $errstr ) use ( &$errors ): bool {
                $errors[] = $errstr;
                return true;
            }
        );
        $level = ob_get_level();
        ob_start();
        try {
            include dirname( __DIR__, 3 ) . '/templates/admin/championship/' . $template_name;
            $output = (string) ob_get_clean();
        } finally {
            while ( ob_get_level() > $level ) {
                ob_end_clean();
            }
            restore_error_handler();
        }

        $this->assertSame( array(), $errors );
        return $output;
    }

    public function test_finals_template_renders_preformatted_dates_and_times_with_date_presenter(): void {
        $league     = $this->create_league( Competition_Type::TOURNAMENT, true, false, 3 );
        $tournament = (object) array( 'id' => 5 );
        $home_team  = $this->create_named_team( 'Roger Federer' );
        $away_team  = $this->create_named_team( 'Rafael Nadal' );

        $match = $this->create_match(
            array(
                'id'          => 99,
                'link'        => '/match/99/',
                'sets'        => array(
                    1 => new Set_Score( 6, 4 ),
                    2 => new Set_Score( 7, 6, 7, 5 ),
                    3 => new Set_Score( 6, 3 ),
                ),
                'home_points' => 3,
                'away_points' => 0,
            )
        );
        $match->custom = array( 'sets' => $match->sets );

        $fixture_details  = $this->create_fixture_details( $league, $match, $home_team, $away_team );
        $presented_finals = $this->present_finals_round( array( $fixture_details ), $tournament, $league );

        $vm = new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'fixtures',
            season: '2026',
            finals: $presented_finals,
        );

        $fixtures = $presented_finals[0]->fixtures;

        $output = $this->render_template(
            'finals.php',
            array(
                'vm'       => $vm,
                'fixtures' => $fixtures,
            )
        );

        $this->assertStringContainsString( '12 Jul 2026', $output );
        $this->assertStringContainsString( '14:00', $output );
        $this->assertStringContainsString( 'Roger Federer - Rafael Nadal', $output );
        $this->assertStringContainsString( 'Centre Court', $output );
        $this->assertStringNotContainsString( 'mysql2date', $output );
    }

    public function test_finals_template_renders_na_when_date_or_hour_is_missing(): void {
        $league     = $this->create_league();
        $home_team  = $this->create_named_team( 'Team A' );
        $away_team  = $this->create_named_team( 'Team B' );
        $match      = $this->create_match(
            array(
                'date'     => null,
                'location' => null,
            )
        );
        unset( $match->hour, $match->minutes );

        $fixture_details  = $this->create_fixture_details( $league, $match, $home_team, $away_team );
        $tournament       = (object) array( 'id' => 5 );
        $presented_finals = $this->present_finals_round( array( $fixture_details ), $tournament, $league );

        $vm = new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'fixtures',
            season: '2026',
            finals: $presented_finals,
        );

        $fixtures = $presented_finals[0]->fixtures;

        $output = $this->render_template(
            'finals.php',
            array(
                'vm'       => $vm,
                'fixtures' => $fixtures,
            )
        );

        $this->assertStringContainsString( 'N/A', $output );
        $this->assertStringContainsString( 'Team A - Team B', $output );
    }

    public function test_finals_template_renders_cup_fixture_from_supplied_finals_without_view_model(): void {
        $league    = $this->create_league();
        $home_team = $this->create_named_team( 'Cup Home' );
        $away_team = $this->create_named_team( 'Cup Away' );

        $match = $this->create_match(
            array(
                'id'       => 202,
                'date'     => '2026-05-09 11:00:00',
                'location' => 'Club Court',
                'hour'     => null,
                'minutes'  => null,
                'link'     => '/match/202/',
            )
        );

        $fixture_details = $this->create_fixture_details( $league, $match, $home_team, $away_team );
        $finals          = $this->present_finals_round( array( $fixture_details ), null, $league );

        $output = $this->render_template(
            'finals.php',
            array(
                'league' => $league,
                'finals' => $finals,
                'season' => '2026',
            )
        );

        $this->assertStringContainsString( 'Cup Home - Cup Away', $output );
        $this->assertStringContainsString( '9 May 2026', $output );
        $this->assertStringContainsString( 'Club Court', $output );
        $this->assertStringContainsString( 'page=racketmanager-cups', $output );
        $this->assertStringContainsString( 'view=match', $output );
        $this->assertStringContainsString( 'competition_id=10', $output );
        $this->assertStringContainsString( 'edit=202', $output );
        $this->assertStringContainsString( '<option value="final"', $output );
    }

    public function test_finals_template_renders_without_warnings_when_no_finals_supplied(): void {
        $league = $this->create_league();
        $output = $this->render_template(
            'finals.php',
            array(
                'league' => $league,
                'season' => '2026',
            )
        );

        $this->assertStringContainsString( 'championship-block', $output );
        $this->assertStringNotContainsString( '<table', $output );
    }

    public function test_final_results_template_renders_presented_finals_with_team_names_and_winner(): void {
        $league    = $this->create_league();
        $home_team = $this->create_named_team( 'Carlos Alcaraz' );
        $away_team = $this->create_named_team( 'Jannik Sinner' );

        $match = $this->create_match(
            array(
                'date'        => '2026-07-25 15:30:00',
                'location'    => null,
                'hour'        => null,
                'minutes'     => null,
                'winner_id'   => 1,
                'home_points' => 3,
                'away_points' => 2,
            )
        );

        $fixture_details = $this->create_fixture_details( $league, $match, $home_team, $away_team );
        $finals          = $this->present_finals_round(
            fixtures: array( $fixture_details ),
            tournament: null,
            league: $league,
            name: 'Championship Final'
        );

        $output = $this->render_template(
            'final-results.php',
            array(
                'finals' => $finals,
                'league' => $league,
                'season' => '2026',
            )
        );

        $this->assertStringContainsString( 'Championship Final', $output );
        $this->assertStringContainsString( 'Carlos Alcaraz', $output );
        $this->assertStringContainsString( 'Jannik Sinner', $output );
        $this->assertStringContainsString( 'team team-left winner', $output );
        $this->assertStringContainsString( 'title="Match winner"', $output );
        $this->assertStringContainsString( '<strong>3-2</strong>', $output );
    }

    public function test_final_results_template_renders_legacy_raw_finals_with_team_names_and_host_styling(): void {
        $league    = $this->create_league();
        $home_team = (object) array( 'team' => (object) array( 'title' => 'Legacy Home' ) );
        $away_team = (object) array( 'team' => (object) array( 'title' => 'Legacy Away' ) );

        $match = $this->create_match(
            array(
                'id'        => 202,
                'home_team' => '10',
                'away_team' => '20',
                'host'      => 'away',
                'winner_id' => null,
            )
        );

        $fixture_details = $this->create_fixture_details( $league, $match, $home_team, $away_team );

        $finals = array(
            (object) array(
                'key'         => 'final',
                'name'        => 'Cup Final',
                'num_matches' => 1,
                'fixtures'    => array( $fixture_details ),
            ),
        );

        $output = $this->render_template(
            'final-results.php',
            array(
                'finals' => $finals,
                'league' => $league,
                'season' => '2026',
            )
        );

        $this->assertStringContainsString( 'Cup Final', $output );
        $this->assertStringContainsString( 'Legacy Home', $output );
        $this->assertStringContainsString( 'Legacy Away', $output );
        $this->assertStringContainsString( 'team team-right host', $output );
        $this->assertStringContainsString( 'title="Home team"', $output );
        $this->assertStringContainsString( '-', $output );
    }

    public function test_final_results_template_renders_without_warnings_when_no_finals_supplied(): void {
        $league = $this->create_league();
        $output = $this->render_template(
            'final-results.php',
            array(
                'league' => $league,
                'season' => '2026',
            )
        );

        $this->assertStringContainsString( 'championship-block', $output );
    }
}
