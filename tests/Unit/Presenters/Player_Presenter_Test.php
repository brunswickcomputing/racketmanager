<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presenters\Player_Presenter;

class Player_Presenter_Test extends TestCase {
    private Player_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Player_Presenter();
    }

    public function test_get_initials(): void {
        $this->assertEquals( 'JD', $this->presenter->get_initials( 'John', 'Doe' ) );
        $this->assertEquals( 'P', $this->presenter->get_initials( 'Paul', null ) );
        $this->assertEquals( '', $this->presenter->get_initials( null, null ) );
    }

    public function test_get_heading_level(): void {
        $this->assertEquals( 1, $this->presenter->get_heading_level( true ) );
        $this->assertEquals( 3, $this->presenter->get_heading_level( false ) );
        $this->assertEquals( 2, $this->presenter->get_heading_level( false, 2 ) );
        $this->assertEquals( 4, $this->presenter->get_heading_level( false, '4' ) );
    }

    public function test_get_wtn_badges(): void {
        $wtn = array(
            'singles' => '24.1',
            'doubles' => '26.8',
        );

        $badges = $this->presenter->get_wtn_badges( $wtn );
        $this->assertCount( 2, $badges );
        $this->assertEquals( 'S', $badges[0]['type'] );
        $this->assertEquals( 'Singles', $badges[0]['description'] );
        $this->assertEquals( '24.1', $badges[0]['value'] );
        $this->assertNotEmpty( $badges[0]['tooltip'] );

        $this->assertEmpty( $this->presenter->get_wtn_badges( null ) );
        $this->assertEmpty( $this->presenter->get_wtn_badges( 'invalid' ) );
    }

    public function test_get_win_loss_summary(): void {
        $stats = (object) array(
            'total' => (object) array(
                'played'      => 10,
                'matches_won' => 7,
                'matches_lost'=> 3,
                'win_pct'     => 70.0,
            ),
        );

        $summary = $this->presenter->get_win_loss_summary( $stats );
        $this->assertNotNull( $summary );
        $this->assertEquals( 10, $summary['played'] );
        $this->assertEquals( 7, $summary['matches_won'] );
        $this->assertEquals( 3, $summary['matches_lost'] );
        $this->assertEquals( '7-3 (10)', $summary['record_text'] );
        $this->assertEquals( '70 won%', $summary['progress_title'] );
    }

    public function test_get_player_link(): void {
        $link1 = $this->presenter->get_player_link( 'John Doe' );
        $this->assertEquals( '/players/john-doe/', $link1 );

        $link2 = $this->presenter->get_player_link( 'John Doe', 'leigh-tc' );
        $this->assertEquals( '/clubs/leigh_tc/players/john-doe/', $link2 );

        $link3 = $this->presenter->get_player_link( 'John Doe', null, '/tournaments/championship/2026/player/' );
        $this->assertEquals( '/tournaments/championship/2026/player/john-doe/', $link3 );
    }

    public function test_get_alphabet_navigation(): void {
        $active_keys = array( 'A', 'B', 'M', 'Z' );
        $navigation  = $this->presenter->get_alphabet_navigation( $active_keys );

        $this->assertCount( 26, $navigation );
        $this->assertTrue( $navigation[0]['is_active'] ); // 'A'
        $this->assertNull( $navigation[0]['class'] );
        $this->assertFalse( $navigation[2]['is_active'] ); // 'C'
        $this->assertEquals( 'is-disabled', $navigation[2]['class'] );
    }
}
