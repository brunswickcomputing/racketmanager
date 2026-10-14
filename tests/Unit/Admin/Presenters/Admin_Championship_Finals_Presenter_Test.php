<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\View_Models\Admin_Championship_Final_Fixture_View_Model;
use stdClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

final class Admin_Championship_Finals_Presenter_Test extends TestCase {

    private Admin_Championship_Finals_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );
        $this->presenter             = new Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() );
    }

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_options'] );
        parent::tearDown();
    }

    public function test_present_tournament_final_link(): void {
        $tournament     = (object) array( 'id' => 10 );
        $league         = (object) array( 'id' => 20 );
        $competition    = (object) array(
            'id'            => 1,
            'is_tournament' => true,
            'is_cup'        => false,
        );

        $fixture_details              = new stdClass();
        $fixture_details->competition = $competition;
        $fixture_details->league      = $league;
        $fixture_details->fixture     = (object) array(
            'id'       => 501,
            'season'   => '2026',
            'final'    => 'semi',
            'date'     => '2026-07-12 14:00:00',
            'hour'     => '14',
            'location' => 'Centre Court',
        );
        $fixture_details->home_team   = (object) array(
            'team' => new class() {
                public function get_name(): string {
                    return 'Player A';
                }
            },
        );
        $fixture_details->away_team   = (object) array(
            'team' => new class() {
                public function get_name(): string {
                    return 'Player B';
                }
            },
        );

        $vm = $this->presenter->present_fixture_details( $fixture_details, $tournament, $league, '2026' );

        $this->assertInstanceOf( Admin_Championship_Final_Fixture_View_Model::class, $vm );
        $this->assertSame( '12 Jul 2026', $vm->formatted_date );
        $this->assertSame( '14:00', $vm->formatted_time );
        $this->assertSame( 'Centre Court', $vm->formatted_location );
        $this->assertSame( 'Player A', $vm->home_name );
        $this->assertSame( 'Player B', $vm->away_name );
        $this->assertSame( 'Player A - Player B', $vm->teams_label );
        $this->assertSame(
            'admin.php?page=racketmanager-tournaments&view=fixture&tournament=10&league=20&edit=501&season=2026&final=semi',
            $vm->match_link
        );
    }

    public function test_present_cup_final_link(): void {
        $league      = (object) array( 'id' => 30 );
        $competition = (object) array(
            'id'            => 5,
            'is_tournament' => false,
            'is_cup'        => true,
        );

        $fixture_details              = new stdClass();
        $fixture_details->competition = $competition;
        $fixture_details->league      = $league;
        $fixture_details->fixture     = (object) array(
            'id'     => 602,
            'season' => '2026',
            'final'  => 'final',
        );
        $fixture_details->home_team   = (object) array( 'name' => 'Club X' );
        $fixture_details->away_team   = (object) array( 'title' => 'Club Y' );

        $vm = $this->presenter->present_fixture_details( $fixture_details, null, $league, '2026' );

        $this->assertSame( 'Club X - Club Y', $vm->teams_label );
        $this->assertSame(
            'admin.php?page=racketmanager-cups&view=match&competition_id=5&league=30&edit=602&season=2026&final=final',
            $vm->match_link
        );
    }

    public function test_present_league_championship_link(): void {
        $league      = (object) array( 'id' => 40 );
        $competition = (object) array(
            'id'            => 8,
            'is_tournament' => false,
            'is_cup'        => false,
        );

        $fixture_details              = new stdClass();
        $fixture_details->competition = $competition;
        $fixture_details->league      = $league;
        $fixture_details->fixture     = (object) array(
            'id'     => 703,
            'season' => '2026',
        );
        $fixture_details->home_team   = 'Team Alpha';
        $fixture_details->away_team   = 'Team Beta';

        $vm = $this->presenter->present_fixture_details( $fixture_details, null, $league, '2026' );

        $this->assertSame(
            'admin.php?page=racketmanager&subpage=match&league=40&edit=703&season=2026',
            $vm->match_link
        );
    }

    public function test_present_finals_preserves_keys_and_handles_arrays_and_objects(): void {
        $league      = (object) array( 'id' => 1 );
        $competition = (object) array(
            'id'            => 1,
            'is_tournament' => false,
            'is_cup'        => false,
        );

        $fixture_details = (object) array(
            'competition' => $competition,
            'league'      => $league,
            'fixture'     => (object) array( 'id' => 10, 'date' => '2026-06-01 10:00:00', 'hour' => '10' ),
            'home_team'   => (object) array( 'name' => 'A' ),
            'away_team'   => (object) array( 'name' => 'B' ),
        );

        $finals_input = array(
            'round_1' => (object) array(
                'key'      => 'round_1',
                'name'     => 'Round 1',
                'fixtures' => array( $fixture_details ),
            ),
            'round_2' => array(
                'key'      => 'round_2',
                'name'     => 'Round 2',
                'fixtures' => array( $fixture_details ),
            ),
        );

        $presented = $this->presenter->present_finals( $finals_input, null, $league, '2026' );

        $this->assertArrayHasKey( 'round_1', $presented );
        $this->assertArrayHasKey( 'round_2', $presented );
        $this->assertInstanceOf( Admin_Championship_Final_Fixture_View_Model::class, $presented['round_1']->fixtures[0] );
        $this->assertInstanceOf( Admin_Championship_Final_Fixture_View_Model::class, $presented['round_2']->fixtures[0] );
        $this->assertSame( '1 Jun 2026', $presented['round_1']->fixtures[0]->formatted_date );
    }

    public function test_missing_date_and_time_returns_na(): void {
        $league      = (object) array( 'id' => 1 );
        $competition = (object) array( 'id' => 1 );

        $fixture_details = (object) array(
            'competition' => $competition,
            'league'      => $league,
            'fixture'     => (object) array( 'id' => 99, 'date' => null, 'hour' => null, 'location' => null ),
        );

        $vm = $this->presenter->present_fixture_details( $fixture_details, null, $league, '2026' );

        $this->assertSame( 'N/A', $vm->formatted_date );
        $this->assertSame( 'N/A', $vm->formatted_time );
        $this->assertSame( 'N/A', $vm->formatted_location );
    }

    public function test_present_winner_and_scores(): void {
        $league      = (object) array( 'id' => 1 );
        $competition = (object) array( 'id' => 1 );

        $fixture_details = (object) array(
            'competition' => $competition,
            'league'      => $league,
            'fixture'     => (object) array(
                'id'          => 10,
                'home_team'   => '101',
                'away_team'   => '102',
                'winner_id'   => 101,
                'home_points' => 3,
                'away_points' => 1,
            ),
            'home_team'   => (object) array( 'name' => 'Winner Team' ),
            'away_team'   => (object) array( 'name' => 'Runner-up Team' ),
        );

        $vm = $this->presenter->present_fixture_details( $fixture_details, null, $league, '2026' );

        $this->assertSame( 'Winner Team', $vm->home_name );
        $this->assertSame( 'Runner-up Team', $vm->away_name );
        $this->assertSame( 'winner', $vm->home_class );
        $this->assertSame( 'Match winner', $vm->home_tip );
        $this->assertSame( '', $vm->away_class );
        $this->assertSame( '', $vm->away_tip );
        $this->assertSame( '3-1', $vm->formatted_score );
    }

    public function test_present_host_and_uncompleted_score(): void {
        $league      = (object) array( 'id' => 1 );
        $competition = (object) array( 'id' => 1 );

        $fixture_details = (object) array(
            'competition' => $competition,
            'league'      => $league,
            'fixture'     => (object) array(
                'id'          => 11,
                'home_team'   => '101',
                'away_team'   => '102',
                'winner_id'   => null,
                'host'        => 'away',
                'home_points' => null,
                'away_points' => null,
            ),
            'home_team'   => (object) array( 'name' => 'Home Team' ),
            'away_team'   => (object) array( 'name' => 'Away Host Team' ),
        );

        $vm = $this->presenter->present_fixture_details( $fixture_details, null, $league, '2026' );

        $this->assertSame( '', $vm->home_class );
        $this->assertSame( 'host', $vm->away_class );
        $this->assertSame( 'Home team', $vm->away_tip );
        $this->assertSame( '-', $vm->formatted_score );
    }
}
