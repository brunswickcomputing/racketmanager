<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;

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

    public function test_get_wtn_badges_preserves_display_strings_and_precision(): void {
        $wtn = array(
            'singles' => '40',
            'doubles' => '24.53',
        );

        $badges = $this->presenter->get_wtn_badges( $wtn );
        $this->assertCount( 2, $badges );
        $this->assertEquals( '40', $badges[0]['value'] );
        $this->assertEquals( '24.53', $badges[1]['value'] );

        $wtn_whitespace = array(
            'S' => ' 12.3',
            'D' => '-1',
        );
        $badges_ws = $this->presenter->get_wtn_badges( $wtn_whitespace );
        $this->assertCount( 2, $badges_ws );
        $this->assertEquals( ' 12.3', $badges_ws[0]['value'] );
        $this->assertEquals( '-1', $badges_ws[1]['value'] );
    }

    public function test_get_wtn_badges_handles_non_numeric_and_unknown_ratings(): void {
        $wtn_placeholder = array(
            'singles' => '-',
            'doubles' => 'N/A',
        );

        $badges = $this->presenter->get_wtn_badges( $wtn_placeholder );
        $this->assertCount( 2, $badges );
        $this->assertEquals( 'S', $badges[0]['type'] );
        $this->assertEquals( '-', $badges[0]['value'] );
        $this->assertEquals( 'D', $badges[1]['type'] );
        $this->assertEquals( 'N/A', $badges[1]['value'] );
    }

    public function test_get_wtn_badges_handles_mixed_keys_and_single_entries(): void {
        $wtn_single = array(
            'S' => '15.5',
        );

        $badges = $this->presenter->get_wtn_badges( $wtn_single );
        $this->assertCount( 1, $badges );
        $this->assertEquals( 'S', $badges[0]['type'] );
        $this->assertEquals( '15.5', $badges[0]['value'] );
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

    public function test_present_player_fails_fast_for_raw_competitions_without_a_competition_presenter(): void {
        $this->expectException( \LogicException::class );

        ( new Player_Presenter() )->present_player(
            player: (object) [ 'display_name' => 'John Smith' ],
            player_competitions: [
                'cup' => [ (object) [ 'name' => 'County Cup', 'season' => '2026' ] ],
            ]
        );
    }

    public function test_present_player_accepts_empty_or_prebuilt_competition_view_models_without_a_competition_presenter(): void {
        $presenter = new Player_Presenter();
        $player    = (object) [ 'display_name' => 'John Smith' ];

        $empty_list = $presenter->present_player( $player, player_competitions: [ 'cup' => [] ] );
        $this->assertSame( [ 'cup' => [] ], $empty_list->player_competitions );

        $item = new Competition_List_Item_View_Model( 'County Cup 2026', '/county-cup/2026/' );
        $prebuilt_list = $presenter->present_player( $player, player_competitions: [ 'cup' => [ $item ] ] );
        $this->assertSame( [ 'cup' => [ $item ] ], $prebuilt_list->player_competitions );
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

    public function test_format_date_with_option_gateway(): void {
        $option_gateway = new class implements \Racketmanager\Gateways\Option\Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return 'Y-m-d';
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
                return true;
            }
            public function delete_option( string $option ): bool {
                return true;
            }
        };

        $presenter = new Player_Presenter( option_gateway: $option_gateway );
        $this->assertSame( '2026-06-15', $presenter->format_date( '2026-06-15 19:00:00' ) );
        $this->assertSame( '', $presenter->format_date( null ) );
        $this->assertSame( '', $presenter->format_date( '' ) );
        $this->assertSame( '', $presenter->format_date( '0000-00-00 00:00:00' ) );
    }

    public function test_present_club_membership_formats_dates(): void {
        $option_gateway = new class implements \Racketmanager\Gateways\Option\Option_Gateway_Interface {
            public function get_option( string $option, mixed $default = false ): mixed {
                return 'd/m/Y';
            }
            public function update_option( string $option, mixed $value, mixed $autoload = null ): bool {
                return true;
            }
            public function delete_option( string $option ): bool {
                return true;
            }
        };

        $presenter   = new Player_Presenter( option_gateway: $option_gateway );
        $membership  = (object) [
            'club_name'         => 'LTC',
            'club_display_name' => 'Leigh Tennis Club',
            'approval_date'     => '2026-01-10',
            'removal_date'      => '2026-05-20',
            'registration_date' => '2026-01-01',
        ];

        $presented = $presenter->present_club_membership( $membership );

        $this->assertSame( 'LTC', $presented->club_name );
        $this->assertSame( 'Leigh Tennis Club', $presented->club_display_name );
        $this->assertSame( '2026-01-10', $presented->approval_date );
        $this->assertSame( '10/01/2026', $presented->approval_date_display );
        $this->assertSame( '2026-05-20', $presented->removal_date );
        $this->assertSame( '20/05/2026', $presented->removal_date_display );
        $this->assertSame( '2026-01-01', $presented->registration_date );
    }
}
