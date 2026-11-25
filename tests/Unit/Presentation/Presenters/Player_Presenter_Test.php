<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Club\Club_Player;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Club_Membership_View_Model;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use Racketmanager\Tests\Helpers\Club_Test_Factory;
use Racketmanager\Tests\Helpers\Player_Test_Factory;

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
        $option_gateway = new class implements \Racketmanager\Application\Ports\Option_Gateway_Interface {
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
        $option_gateway = new class implements \Racketmanager\Application\Ports\Option_Gateway_Interface {
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

    public function test_present_club_membership_with_club_player_dto_and_datetime_formatting(): void {
        $option_gateway = new class implements \Racketmanager\Application\Ports\Option_Gateway_Interface {
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

        $presenter = new Player_Presenter( option_gateway: $option_gateway );

        $player = Player_Test_Factory::create(
            id: 42,
            display_name: 'Andy Murray'
        );

        $club = Club_Test_Factory::create(
            id: 101,
            name: 'Westcliff Hardcourt Tennis Club',
            shortcode: 'Westcliff Hardcourt'
        );

        $registration = Club_Player::from_object( (object) [
            'id'             => 55,
            'player_id'      => 42,
            'club_id'        => 101,
            'requested_date' => '2026-01-10 09:30:00',
            'created_date'   => '2026-01-15 14:00:00',
            'removed_date'   => '2026-06-30 18:45:00',
            'requested_user' => 42,
            'created_user'   => 1,
            'removed_user'   => 2,
            'system_record'  => 0,
        ] );

        $dto = new Club_Player_DTO( $player, $club, $registration );

        $presented = $presenter->present_club_membership( $dto );

        $this->assertInstanceOf( Club_Membership_View_Model::class, $presented );
        $this->assertSame( 'Westcliff Hardcourt', $presented->club_name );
        $this->assertSame( 'Westcliff Hardcourt Tennis Club', $presented->club_display_name );
        $this->assertSame( '2026-01-15 14:00:00', $presented->approval_date );
        $this->assertSame( '15/01/2026', $presented->approval_date_display );
        $this->assertSame( '2026-06-30 18:45:00', $presented->removal_date );
        $this->assertSame( '30/06/2026', $presented->removal_date_display );
        $this->assertSame( '2026-01-10 09:30:00', $presented->registration_date );
        $this->assertSame( '10/01/2026', $presented->registration_date_display );
        $this->assertSame( 101, $presented->club_id );
        $this->assertSame( 42, $presented->user_id );
        $this->assertSame( 55, $presented->registration_id );
        $this->assertSame( 1, $presented->approved_by_user_id );
        $this->assertSame( 42, $presented->registered_by_user_id );
        $this->assertSame( 2, $presented->removed_by_user_id );
        $this->assertSame( false, $presented->system_record );
        $this->assertSame( '/clubs/westcliff-hardcourt/', $presented->club_url );
    }

    public function test_present_club_membership_with_system_record_true(): void {
        $presenter = new Player_Presenter();

        $player = Player_Test_Factory::create( id: 42 );

        $club = Club_Test_Factory::create( id: 101 );

        $registration = Club_Player::from_object( (object) [
            'id'            => 57,
            'player_id'     => 42,
            'club_id'       => 101,
            'system_record' => 1,
        ] );

        $dto = new Club_Player_DTO( $player, $club, $registration );

        $presented = $presenter->present_club_membership( $dto );

        $this->assertSame( true, $presented->system_record );
    }

    public function test_present_club_membership_with_null_and_zero_dates(): void {
        $presenter = new Player_Presenter();

        $player = Player_Test_Factory::create(
            id: 42,
            display_name: 'Andy Murray'
        );

        $club = Club_Test_Factory::create(
            id: 101,
            name: 'Leigh TC'
        );

        $registration = Club_Player::from_object( (object) [
            'id'             => 56,
            'player_id'      => 42,
            'club_id'        => 101,
            'requested_date' => null,
            'created_date'   => '0000-00-00 00:00:00',
            'removed_date'   => '',
        ] );

        $dto = new Club_Player_DTO( $player, $club, $registration );

        $presented = $presenter->present_club_membership( $dto );

        $this->assertSame( '', $presented->approval_date_display );
        $this->assertSame( '', $presented->removal_date_display );
        $this->assertSame( '', $presented->registration_date_display );
    }

    public function test_present_player_resolves_competition_presenter_lazily_from_closure(): void {
        $resolutions           = 0;
        $expected_items        = [ 'item' ];
        $competition_presenter = $this->createMock( Competition_Shortcode_Presenter::class );
        $competition_presenter->expects( $this->once() )
            ->method( 'present_competition_list' )
            ->with( $this->anything(), 'league' )
            ->willReturn( $expected_items );

        $presenter = new Player_Presenter(
            static function () use ( &$resolutions, $competition_presenter ) {
                ++$resolutions;
                return $competition_presenter;
            }
        );
        $this->assertSame( 0, $resolutions, 'Closure must not run at construction time.' );

        $vm = $presenter->present_player(
            player: (object) [ 'id' => 5, 'display_name' => 'Jane Doe' ],
            player_competitions: [ 'league' => [ (object) [ 'id' => 1 ] ], 'cup' => [] ]
        );

        $this->assertSame( 1, $resolutions );
        $this->assertSame( $expected_items, $vm->player_competitions['league'] );
        $this->assertSame( [], $vm->player_competitions['cup'] );
    }

    public function test_present_player_does_not_resolve_closure_when_no_raw_competitions(): void {
        $resolutions = 0;
        $presenter   = new Player_Presenter(
            static function () use ( &$resolutions ) {
                ++$resolutions;
                throw new \LogicException( 'Should not be resolved.' );
            }
        );

        $vm = $presenter->present_player( player: (object) [ 'id' => 5, 'display_name' => 'Jane Doe' ] );

        $this->assertSame( 0, $resolutions );
        $this->assertSame( [], $vm->player_competitions );
    }

    public function test_present_player_accepts_direct_competition_presenter_instance(): void {
        $competition_presenter = $this->createMock( Competition_Shortcode_Presenter::class );
        $competition_presenter->expects( $this->once() )->method( 'present_competition_list' )->willReturn( [ 'x' ] );

        $presenter = new Player_Presenter( $competition_presenter );
        $vm        = $presenter->present_player(
            player: (object) [ 'id' => 5, 'display_name' => 'Jane Doe' ],
            player_competitions: [ 'league' => [ (object) [ 'id' => 1 ] ] ]
        );

        $this->assertSame( [ 'x' ], $vm->player_competitions['league'] );
    }

    public function test_present_league_player_links_with_objects_and_enum_competition(): void {
        $league = (object) [
            'title'          => 'Premier Division',
            'current_season' => [ 'name' => '2026' ],
            'event'          => (object) [
                'competition' => (object) [
                    'type' => Competition_Type::LEAGUE,
                ],
            ],
        ];

        $player1 = (object) [
            'id'        => 101,
            'firstname' => 'Alice',
            'surname'   => 'Smith',
            'team'      => (object) [
                'title' => 'St Saviours 1',
            ],
        ];

        $player2 = (object) [
            'ID'           => 102,
            'display_name' => 'Bob Jones',
            'team'         => (object) [
                'title' => 'Westcliff 2',
            ],
        ];

        $links = $this->presenter->present_league_player_links( $league, [ $player1, $player2 ] );

        $this->assertArrayHasKey( 101, $links );
        $this->assertArrayHasKey( 102, $links );
        $this->assertSame( '/league/premier-division/2026/player/alice-smith/', $links[101]['player_link'] );
        $this->assertSame( '/league/premier-division/2026/team/st-saviours-1/', $links[101]['team_link'] );
        $this->assertSame( '/league/premier-division/2026/player/bob-jones/', $links[102]['player_link'] );
        $this->assertSame( '/league/premier-division/2026/team/westcliff-2/', $links[102]['team_link'] );
    }

    public function test_present_league_player_links_with_getters_and_string_competition(): void {
        $league = (object) [
            'title'          => 'Cup Knockout',
            'current_season' => [ 'name' => '2025-2026' ],
            'event'          => (object) [
                'competition' => (object) [
                    'type' => 'cup',
                ],
            ],
        ];

        $player = new class() {
            public function get_id(): int {
                return 201;
            }

            public function get_fullname(): string {
                return 'Charlie Brown';
            }

            public object $team;

            public function __construct() {
                $this->team = (object) [
                    'name' => 'Hadleigh Park',
                ];
            }
        };

        $links = $this->presenter->present_league_player_links( $league, [ $player ] );

        $this->assertArrayHasKey( 201, $links );
        $this->assertSame( '/cup/cup-knockout/2025-2026/player/charlie-brown/', $links[201]['player_link'] );
        $this->assertSame( '/cup/cup-knockout/2025-2026/team/hadleigh-park/', $links[201]['team_link'] );
    }

    public function test_present_league_player_links_with_arrays_and_fallback_competition(): void {
        $league = (object) [
            'title'          => 'Division 2',
            'current_season' => [ 'name' => '2026' ],
            'event'          => (object) [],
        ];

        $player1 = [
            'id'       => 301,
            'fullname' => 'David Miller',
            'team'     => [
                'title' => 'Thorpe Bay 1',
            ],
        ];

        $player2 = [
            'id'           => '302',
            'display_name' => 'Emma Watson',
            'team'         => [
                'name' => 'Wickford 3',
            ],
        ];

        $links = $this->presenter->present_league_player_links( $league, [ $player1, $player2 ] );

        $this->assertArrayHasKey( 301, $links );
        $this->assertArrayHasKey( 302, $links );
        $this->assertSame( '/league/division-2/2026/player/david-miller/', $links[301]['player_link'] );
        $this->assertSame( '/league/division-2/2026/team/thorpe-bay-1/', $links[301]['team_link'] );
        $this->assertSame( '/league/division-2/2026/player/emma-watson/', $links[302]['player_link'] );
        $this->assertSame( '/league/division-2/2026/team/wickford-3/', $links[302]['team_link'] );
    }

    public function test_present_league_player_links_handles_missing_team_and_empty_names(): void {
        $league = (object) [];

        $player1 = (object) [];
        $player2 = [
            'id'       => 402,
            'fullname' => 'Frank Castle',
        ];
        $player3 = (object) [
            'id'           => 403,
            'display_name' => 'Grace Hopper',
            'team'         => (object) [
                'title' => '',
            ],
        ];

        $links = $this->presenter->present_league_player_links( $league, [ $player1, $player2, $player3 ] );

        $this->assertArrayHasKey( 0, $links );
        $this->assertSame( '/league///player//', $links[0]['player_link'] );
        $this->assertSame( '', $links[0]['team_link'] );

        $this->assertArrayHasKey( 402, $links );
        $this->assertSame( '/league///player/frank-castle/', $links[402]['player_link'] );
        $this->assertSame( '', $links[402]['team_link'] );

        $this->assertArrayHasKey( 403, $links );
        $this->assertSame( '/league///player/grace-hopper/', $links[403]['player_link'] );
        $this->assertSame( '', $links[403]['team_link'] );
    }
}
