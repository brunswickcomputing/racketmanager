<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Domain\Competition\Competition_Type;
use stdClass;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Admin_League_Matches_Template_Test extends TestCase {

    protected function tearDown(): void {
        unset( $GLOBALS['wp_stubs_options'] );
        parent::tearDown();
    }

    public function test_league_matches_template_uses_preformatted_dates_without_mysql2date(): void {
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );

        $competition                  = new stdClass();
        $competition->type            = Competition_Type::LEAGUE;
        $competition->is_championship = false;
        $event                        = new stdClass();
        $event->competition           = $competition;
        $league                       = new class() {
            public int $id = 4;
            public object $event;
            public string $groups = '';
            public int $num_rubbers = 0;
            public int $num_sets = 3;
            public array $current_season = array();

            public function get_page_links( string $which = '' ): string {
                return '';
            }
        };
        $league->event          = $event;
        $league->current_season = array( 'num_match_days' => 0, 'name' => '2026' );

        $match              = new stdClass();
        $match->id          = 55;
        $match->date        = '2026-04-04 18:30:00';
        $match->home_team   = '1';
        $match->away_team   = '2';
        $match->group       = '';
        $match->match_title = 'A vs B';
        $match->location    = 'Club';
        $match->hour        = '18';
        $match->minutes     = '30';
        $match->link        = '/match/a-vs-b/';
        $match->sets        = array();
        $match->home_points = '';
        $match->away_points = '';

        $season  = '2026';
        $teams   = array();
        $team_id = 0;
        $group   = '';

        $presenter = new \Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter( new Admin_Date_Presenter() );
        $matches   = $presenter->present_collection( array( $match ), $league, $season, $group );

        ob_start();
        include dirname( __DIR__, 3 ) . '/templates/admin/league/matches.php';
        $output = (string) ob_get_clean();

        $this->assertStringContainsString( '4 Apr 2026', $output );
        $this->assertStringContainsString( '18:30', $output );
        $this->assertStringContainsString( 'A vs B', $output );
        $this->assertStringContainsString( 'page=racketmanager-leagues&view=match&league_id=4&edit=55&season=2026', $output );
        $this->assertStringNotContainsString( 'mysql2date', $output );
    }

    public function test_admin_league_presents_league_match_view_models(): void {
        $GLOBALS['wp_stubs_options'] = array(
            'date_format' => 'j M Y',
            'time_format' => 'H:i',
        );

        $date_presenter    = new Admin_Date_Presenter();
        $matches_presenter = new \Racketmanager\Admin\Presenters\Admin_League_Matches_Presenter( $date_presenter );

        $competition       = new stdClass();
        $competition->type = Competition_Type::LEAGUE;
        $event             = new stdClass();
        $event->competition = $competition;
        $league            = new stdClass();
        $league->id        = 7;
        $league->event     = $event;

        $raw_match         = new stdClass();
        $raw_match->id     = 88;
        $raw_match->date   = '2026-05-10 10:00:00';
        $raw_match->hour   = '10';
        $raw_match->minutes = '00';
        $raw_match->location = 'Court 1';

        $vm = $matches_presenter->present( $raw_match, $league, '2026' );
        $this->assertInstanceOf( \Racketmanager\Admin\View_Models\Admin_League_Match_View_Model::class, $vm );
        $this->assertSame( '10 May 2026', $vm->formatted_date );
        $this->assertSame( '10:00', $vm->formatted_time );
        $this->assertSame( '/wp-admin/admin.php?page=racketmanager-leagues&view=match&league_id=7&edit=88&season=2026', $vm->edit_url );
        $this->assertSame( 'Court 1', $vm->formatted_location );
    }
}
