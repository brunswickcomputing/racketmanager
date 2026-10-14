<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Admin\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter;
use stdClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

final class Admin_Results_Match_Presenter_Test extends TestCase {

    private Admin_Results_Match_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Admin_Results_Match_Presenter( new Admin_Date_Presenter() );
    }

    public function test_present_builds_referrer_links_without_literal_php_terminator(): void {
        $match                   = new stdClass();
        $match->league_id        = 12;
        $match->date             = '2026-04-04 18:30:00';
        $match->match_title      = 'Home vs Away';
        $match->link             = 'https://example.test/match/home-vs-away/';
        $match->confirmed_display = 'Pending';
        $match->score            = '6-4';
        $match->league           = (object) array( 'id' => 12, 'title' => 'Division 1' );

        $row = $this->presenter->present( $match, 'pending-results', true, 'Result pending' );

        $this->assertSame( 12, $row->league_id );
        $this->assertSame( 'Division 1', $row->league_title );
        $this->assertSame( '2026-04-04', $row->formatted_date );
        $this->assertSame( 'Home vs Away', $row->match_title );
        $this->assertSame( 'https://example.test/match/home-vs-away/?referrer=pending-results', $row->match_link );
        $this->assertSame( 'https://example.test/match/home-vs-away/result/?referrer=pending-results', $row->result_link );
        $this->assertStringNotContainsString( '; ?>', $row->match_link );
        $this->assertStringNotContainsString( '; ?>', $row->result_link );
        $this->assertSame( 'Result pending', $row->tooltip );
    }

    public function test_tournament_result_link_does_not_append_result_path(): void {
        $match              = new stdClass();
        $match->league_id   = 3;
        $match->date        = '2026-05-01 10:00:00';
        $match->match_title = 'QF';
        $match->link        = 'https://example.test/tournament/match/';
        $match->league      = (object) array( 'title' => 'Mens Singles' );

        $row = $this->presenter->present( $match, 'tournament', false );

        $this->assertSame( 'https://example.test/tournament/match/?referrer=tournament', $row->match_link );
        $this->assertSame( 'https://example.test/tournament/match/?referrer=tournament', $row->result_link );
        $this->assertStringNotContainsString( '/result/', $row->result_link );
    }

    public function test_overdue_tooltip_replaces_default_when_result_pending_is_set(): void {
        $match                      = new stdClass();
        $match->league_id           = 1;
        $match->date                = '2026-01-01 10:00:00';
        $match->match_title         = 'Late';
        $match->link                = 'https://example.test/match/late/';
        $match->result_overdue_date = '2020-01-01';
        $match->overdue_time        = 4.2;
        $match->league              = (object) array( 'title' => 'Div 2' );

        $row = $this->presenter->present( $match, 'pending-results', true, 'Result pending', 3 );

        $this->assertSame( 'Result overdue by 5 days', $row->tooltip );
    }

    public function test_future_overdue_date_keeps_default_tooltip(): void {
        $match                      = new stdClass();
        $match->league_id           = 1;
        $match->date                = '2026-01-01 10:00:00';
        $match->match_title         = 'On time';
        $match->link                = 'https://example.test/match/on-time/';
        $match->result_overdue_date = '2099-01-01';
        $match->overdue_time        = 0;
        $match->league              = (object) array( 'title' => 'Div 2' );

        $row = $this->presenter->present( $match, 'pending-results', true, 'Result pending', 3 );

        $this->assertSame( 'Result pending', $row->tooltip );
    }

    public function test_present_collection_skips_non_objects(): void {
        $match            = new stdClass();
        $match->league_id = 9;
        $match->date      = '2026-04-04 09:00:00';
        $match->link      = 'https://example.test/m/';
        $match->league    = (object) array( 'title' => 'A' );

        $rows = $this->presenter->present_collection( array( $match, null, 'skip' ), 'results' );

        $this->assertCount( 1, $rows );
        $this->assertSame( 'https://example.test/m/result/?referrer=results', $rows[0]->result_link );
    }
}
