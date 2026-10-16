<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\Player_Presenter;

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

    public function test_present_player_creates_immutable_view_model(): void {
        $player = (object) array(
            'id'           => 42,
            'firstname'    => 'Roger',
            'surname'      => 'Federer',
            'display_name' => 'Roger Federer',
            'btm'          => '12345678',
            'gender'       => 'M',
            'wtn'          => array( 'singles' => '1.0' ),
            'statistics'   => (object) array(
                'total' => (object) array(
                    'played'      => 100,
                    'matches_won' => 90,
                    'matches_lost'=> 10,
                    'win_pct'     => 90.0,
                ),
            ),
        );

        $vm = $this->presenter->present_player(
            player: $player,
            clubs: array( (object) array( 'club_name' => 'Basel TC' ) ),
            titles: array( '2026' => array() ),
            player_competitions: array( 'league' => array() ),
            is_page_header: true,
            user_can_update: true
        );

        $this->assertEquals( 'player', $vm->template );
        $this->assertEquals( 42, $vm->id );
        $this->assertEquals( 'Roger', $vm->firstname );
        $this->assertEquals( 'Federer', $vm->surname );
        $this->assertEquals( 'Roger Federer', $vm->display_name );
        $this->assertEquals( '12345678', $vm->btm );
        $this->assertEquals( 'RF', $vm->initials );
        $this->assertEquals( 1, $vm->header_level );
        $this->assertTrue( $vm->is_page_header );
        $this->assertTrue( $vm->user_can_update );
        $this->assertCount( 1, $vm->wtn_badges );
        $this->assertEquals( '90-10 (100)', $vm->win_loss_summary['record_text'] );

        $vars = $vm->template_vars();
        $this->assertSame( $vm, $vars['vm'] );
        $this->assertEquals( 'RF', $vars['player_initials'] );
    }

    public function test_error_view_model(): void {
        $error_vm = \Racketmanager\Presentation\View_Models\Player_View_Model::for_error( 'Player not found' );
        $this->assertEquals( 'alert', $error_vm->template );
        $this->assertEquals( 'Player not found', $error_vm->error_message );
    }

    public function test_present_players_directory_and_list(): void {
        $dir_vm = $this->presenter->present_players_directory(
            search_string: 'Smith',
            search_results: '<div class="res">Found</div>',
            favourites: array( 1, 2 )
        );
        $this->assertEquals( 'players', $dir_vm->template );
        $this->assertEquals( 'Smith', $dir_vm->search_string );
        $this->assertEquals( '<div class="res">Found</div>', $dir_vm->search_results );
        $this->assertCount( 2, $dir_vm->favourites );

        $list_vm = $this->presenter->present_players_list(
            players_list: array( 'A' => array(), 'B' => array() )
        );
        $this->assertEquals( 'players-list', $list_vm->template );
        $this->assertCount( 26, $list_vm->alphabet_nav );
        $this->assertTrue( $list_vm->alphabet_nav[0]['is_active'] );
        $this->assertTrue( $list_vm->alphabet_nav[1]['is_active'] );
        $this->assertFalse( $list_vm->alphabet_nav[2]['is_active'] );
    }
}
