<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Tournament;
use Racketmanager\Domain\Scoring\Set_Score;
use stdClass;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Admin_Championship_Finals_Template_Test extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_options'] );
        parent::tearDown();
    }

    public function test_finals_template_renders_preformatted_dates_and_times_with_date_presenter(): void {
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );

        $competition                  = new stdClass();
        $competition->id              = 10;
        $competition->type            = Competition_Type::TOURNAMENT;
        $competition->is_tournament   = true;
        $competition->is_cup          = false;
        $competition->is_team_entry   = true;

        $event              = new stdClass();
        $event->id          = 20;
        $event->competition = $competition;

        $league                 = new stdClass();
        $league->id             = 30;
        $league->event          = $event;
        $league->num_rubbers    = 0;
        $league->num_sets       = 3;
        $league->current_season = array( 'name' => '2026' );
        $league->championship   = new class() {
            public function get_current_final_key(): string {
                return 'final';
            }
        };

        $tournament     = new stdClass();
        $tournament->id = 5;

        $home_team = new class() {
            public function get_name(): string {
                return 'Roger Federer';
            }
        };
        $away_team = new class() {
            public function get_name(): string {
                return 'Rafael Nadal';
            }
        };

        $match              = new stdClass();
        $match->id          = 99;
        $match->date        = '2026-07-12 14:00:00';
        $match->season      = '2026';
        $match->final       = 'final';
        $match->home_team   = '1';
        $match->away_team   = '2';
        $match->location    = 'Centre Court';
        $match->hour        = '14';
        $match->minutes     = '00';
        $match->link        = '/match/99/';
        $match->sets        = array(
            1 => new Set_Score( 6, 4 ),
            2 => new Set_Score( 7, 6, 7, 5 ),
            3 => new Set_Score( 6, 3 ),
        );
        $match->custom      = array( 'sets' => $match->sets );
        $match->home_points = 3;
        $match->away_points = 0;

        $fixture_details              = new stdClass();
        $fixture_details->fixture     = $match;
        $fixture_details->league      = $league;
        $fixture_details->event       = $event;
        $fixture_details->competition = $competition;
        $fixture_details->home_team   = (object) array( 'team' => $home_team );
        $fixture_details->away_team   = (object) array( 'team' => $away_team );

        $presenter = new \Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() );
        $presented_finals = $presenter->present_finals(
            array(
                (object) array(
                    'key'      => 'final',
                    'name'     => 'Final',
                    'fixtures' => array( $fixture_details ),
                ),
            ),
            $tournament,
            $league,
            '2026'
        );

        $vm = new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'fixtures',
            season: '2026',
            finals: $presented_finals,
        );

        $fixtures = $presented_finals[0]->fixtures;

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/championship/finals.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( '12 Jul 2026', $output );
        $this->assertStringContainsString( '14:00', $output );
        $this->assertStringContainsString( 'Roger Federer - Rafael Nadal', $output );
        $this->assertStringContainsString( 'Centre Court', $output );
        $this->assertStringNotContainsString( 'mysql2date', $output );
    }

    public function test_finals_template_renders_na_when_date_or_hour_is_missing(): void {
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );

        $competition                = new stdClass();
        $competition->id            = 10;
        $competition->type          = 'cup';
        $competition->is_tournament = false;
        $competition->is_cup        = true;
        $competition->is_team_entry = true;

        $event              = new stdClass();
        $event->id          = 20;
        $event->competition = $competition;

        $league                 = new stdClass();
        $league->id             = 30;
        $league->event          = $event;
        $league->num_rubbers    = 0;
        $league->num_sets       = 1;
        $league->current_season = array( 'name' => '2026' );
        $league->championship   = new class() {
            public function get_current_final_key(): string {
                return 'final';
            }
        };

        $home_team = new class() {
            public function get_name(): string {
                return 'Team A';
            }
        };
        $away_team = new class() {
            public function get_name(): string {
                return 'Team B';
            }
        };

        $match              = new stdClass();
        $match->id          = 101;
        $match->date        = null;
        $match->season      = '2026';
        $match->final       = 'final';
        $match->home_team   = '1';
        $match->away_team   = '2';
        $match->location    = null;
        $match->link        = '/match/101/';
        $match->sets        = array();
        $match->custom      = array();
        $match->home_points = null;
        $match->away_points = null;

        $fixture_details              = new stdClass();
        $fixture_details->fixture     = $match;
        $fixture_details->league      = $league;
        $fixture_details->event       = $event;
        $fixture_details->competition = $competition;
        $fixture_details->home_team   = (object) array( 'team' => $home_team );
        $fixture_details->away_team   = (object) array( 'team' => $away_team );

        $tournament = new stdClass();
        $tournament->id = 5;

        $presenter = new \Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() );
        $presented_finals = $presenter->present_finals(
            array(
                (object) array(
                    'key'      => 'final',
                    'name'     => 'Final',
                    'fixtures' => array( $fixture_details ),
                ),
            ),
            $tournament,
            $league,
            '2026'
        );

        $vm = new Tournament_Draw_Page_View_Model(
            tournament: $tournament,
            league: $league,
            tab: 'fixtures',
            season: '2026',
            finals: $presented_finals,
        );

        $fixtures = $presented_finals[0]->fixtures;

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/championship/finals.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( 'N/A', $output );
        $this->assertStringContainsString( 'Team A - Team B', $output );
    }

    private function make_cup_league(): object {
        $competition                = new stdClass();
        $competition->id            = 10;
        $competition->type          = Competition_Type::CUP;
        $competition->is_tournament = false;
        $competition->is_cup        = true;
        $competition->is_team_entry = true;

        $event              = new stdClass();
        $event->id          = 20;
        $event->competition = $competition;

        $league                 = new stdClass();
        $league->id             = 30;
        $league->event          = $event;
        $league->num_rubbers    = 0;
        $league->num_sets       = 1;
        $league->current_season = array( 'name' => '2026' );
        $league->championship   = new class() {
            public function get_current_final_key(): string {
                return 'final';
            }
        };

        return $league;
    }

    public function test_finals_template_renders_cup_fixture_from_supplied_finals_without_view_model(): void {
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );

        $league      = $this->make_cup_league();
        $event       = $league->event;
        $competition = $event->competition;

        $home_team = new class() {
            public function get_name(): string {
                return 'Cup Home';
            }
        };
        $away_team = new class() {
            public function get_name(): string {
                return 'Cup Away';
            }
        };

        $match              = new stdClass();
        $match->id          = 202;
        $match->date        = '2026-05-09 11:00:00';
        $match->season      = '2026';
        $match->final       = 'final';
        $match->home_team   = '1';
        $match->away_team   = '2';
        $match->location    = 'Club Court';
        $match->link        = '/match/202/';
        $match->sets        = array();
        $match->custom      = array();
        $match->home_points = null;
        $match->away_points = null;

        $fixture_details              = new stdClass();
        $fixture_details->fixture     = $match;
        $fixture_details->league      = $league;
        $fixture_details->event       = $event;
        $fixture_details->competition = $competition;
        $fixture_details->home_team   = (object) array( 'team' => $home_team );
        $fixture_details->away_team   = (object) array( 'team' => $away_team );

        $presenter = new \Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() );
        $finals    = $presenter->present_finals(
            array(
                (object) array(
                    'key'      => 'final',
                    'name'     => 'Final',
                    'fixtures' => array( $fixture_details ),
                ),
            ),
            null,
            $league,
            '2026'
        );
        $season = '2026';

        $errors = array();
        set_error_handler(
            static function ( int $errno, string $errstr ) use ( &$errors ): bool {
                $errors[] = $errstr;
                return true;
            }
        );
        try {
            ob_start();
            include dirname( __DIR__, 3 ) . '/templates/admin/championship/finals.php';
            $output = (string) ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertSame( array(), $errors );
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
        $league = $this->make_cup_league();
        $season = '2026';

        $errors = array();
        set_error_handler(
            static function ( int $errno, string $errstr ) use ( &$errors ): bool {
                $errors[] = $errstr;
                return true;
            }
        );
        try {
            ob_start();
            include dirname( __DIR__, 3 ) . '/templates/admin/championship/finals.php';
            $output = (string) ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertSame( array(), $errors );
        $this->assertStringContainsString( 'championship-block', $output );
        $this->assertStringNotContainsString( '<table', $output );
    }

    public function test_final_results_template_renders_presented_finals_with_team_names_and_winner(): void {
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );

        $league      = $this->make_cup_league();
        $event       = $league->event;
        $competition = $event->competition;

        $home_team = new class() {
            public function get_name(): string {
                return 'Carlos Alcaraz';
            }
        };
        $away_team = new class() {
            public function get_name(): string {
                return 'Jannik Sinner';
            }
        };

        $match              = new stdClass();
        $match->id          = 101;
        $match->date        = '2026-07-25 15:30:00';
        $match->season      = '2026';
        $match->final       = 'final';
        $match->home_team   = '1';
        $match->away_team   = '2';
        $match->winner_id   = 1;
        $match->home_points = 3;
        $match->away_points = 2;

        $fixture_details              = new stdClass();
        $fixture_details->fixture     = $match;
        $fixture_details->league      = $league;
        $fixture_details->event       = $event;
        $fixture_details->competition = $competition;
        $fixture_details->home_team   = (object) array( 'team' => $home_team );
        $fixture_details->away_team   = (object) array( 'team' => $away_team );

        $presenter = new \Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() );
        $finals    = $presenter->present_finals(
            array(
                (object) array(
                    'key'         => 'final',
                    'name'        => 'Championship Final',
                    'num_matches' => 1,
                    'fixtures'    => array( $fixture_details ),
                ),
            ),
            null,
            $league,
            '2026'
        );
        $season = '2026';

        $errors = array();
        set_error_handler(
            static function ( int $errno, string $errstr ) use ( &$errors ): bool {
                $errors[] = $errstr;
                return true;
            }
        );
        try {
            ob_start();
            include dirname( __DIR__, 3 ) . '/templates/admin/championship/final-results.php';
            $output = (string) ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertSame( array(), $errors );
        $this->assertStringContainsString( 'Championship Final', $output );
        $this->assertStringContainsString( 'Carlos Alcaraz', $output );
        $this->assertStringContainsString( 'Jannik Sinner', $output );
        $this->assertStringContainsString( 'team team-left winner', $output );
        $this->assertStringContainsString( 'title="Match winner"', $output );
        $this->assertStringContainsString( '<strong>3-2</strong>', $output );
    }

    public function test_final_results_template_renders_legacy_raw_finals_with_team_names_and_host_styling(): void {
        $league      = $this->make_cup_league();
        $event       = $league->event;
        $competition = $event->competition;

        $home_team = (object) array( 'title' => 'Legacy Home' );
        $away_team = (object) array( 'title' => 'Legacy Away' );

        $match              = new stdClass();
        $match->id          = 202;
        $match->home_team   = '10';
        $match->away_team   = '20';
        $match->winner_id   = null;
        $match->host        = 'away';
        $match->home_points = null;
        $match->away_points = null;

        $fixture_details              = new stdClass();
        $fixture_details->fixture     = $match;
        $fixture_details->league      = $league;
        $fixture_details->event       = $event;
        $fixture_details->competition = $competition;
        $fixture_details->home_team   = (object) array( 'team' => $home_team );
        $fixture_details->away_team   = (object) array( 'team' => $away_team );

        $finals = array(
            (object) array(
                'key'         => 'final',
                'name'        => 'Cup Final',
                'num_matches' => 1,
                'fixtures'    => array( $fixture_details ),
            ),
        );
        $season = '2026';

        $errors = array();
        set_error_handler(
            static function ( int $errno, string $errstr ) use ( &$errors ): bool {
                $errors[] = $errstr;
                return true;
            }
        );
        try {
            ob_start();
            include dirname( __DIR__, 3 ) . '/templates/admin/championship/final-results.php';
            $output = (string) ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertSame( array(), $errors );
        $this->assertStringContainsString( 'Cup Final', $output );
        $this->assertStringContainsString( 'Legacy Home', $output );
        $this->assertStringContainsString( 'Legacy Away', $output );
        $this->assertStringContainsString( 'team team-right host', $output );
        $this->assertStringContainsString( 'title="Home team"', $output );
        $this->assertStringContainsString( '-', $output );
    }

    public function test_final_results_template_renders_without_warnings_when_no_finals_supplied(): void {
        $league = $this->make_cup_league();
        $season = '2026';

        $errors = array();
        set_error_handler(
            static function ( int $errno, string $errstr ) use ( &$errors ): bool {
                $errors[] = $errstr;
                return true;
            }
        );
        try {
            ob_start();
            include dirname( __DIR__, 3 ) . '/templates/admin/championship/final-results.php';
            $output = (string) ob_get_clean();
        } finally {
            restore_error_handler();
        }

        $this->assertSame( array(), $errors );
        $this->assertStringContainsString( 'championship-block', $output );
    }
}
