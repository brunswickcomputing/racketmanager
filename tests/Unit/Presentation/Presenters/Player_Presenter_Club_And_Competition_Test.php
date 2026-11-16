<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View_Models\Club_Link_View_Model;
use Racketmanager\Presentation\View_Models\Club_Membership_View_Model;
use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;
use TypeError;

class Player_Presenter_Club_And_Competition_Test extends TestCase {
    private Player_Presenter $presenter;
    private object $player;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Player_Presenter();
        $this->player    = (object) [
            'id'           => 1,
            'firstname'    => 'Roger',
            'surname'      => 'Federer',
            'display_name' => 'Roger Federer',
        ];
    }

    public function test_explicit_clubs_take_precedence_over_player_club_property(): void {
        $player       = (object) [
            'id'           => 1,
            'display_name' => 'Roger Federer',
            'club'         => [ 'Fallback Club' ],
        ];
        $explicit_club = 'Explicit Club';

        $vm = $this->presenter->present_player( $player, clubs: [ $explicit_club ] );

        $this->assertCount( 1, $vm->clubs );
        $this->assertInstanceOf( Club_Link_View_Model::class, $vm->clubs[0] );
        $this->assertSame( 'Explicit Club', $vm->clubs[0]->club_name );
    }

    public function test_fallback_to_player_club_when_clubs_is_empty(): void {
        $player_with_array = (object) [
            'id'           => 1,
            'display_name' => 'Roger Federer',
            'club'         => [ 'Club Alpha', 'Club Beta' ],
        ];
        $vm_array          = $this->presenter->present_player( $player_with_array, clubs: [] );

        $this->assertCount( 2, $vm_array->clubs );
        $this->assertSame( 'Club Alpha', $vm_array->clubs[0]->club_name );
        $this->assertSame( 'Club Beta', $vm_array->clubs[1]->club_name );

        $player_with_scalar = (object) [
            'id'           => 1,
            'display_name' => 'Roger Federer',
            'club'         => 'Single Club',
        ];
        $vm_scalar          = $this->presenter->present_player( $player_with_scalar, clubs: [] );

        $this->assertCount( 1, $vm_scalar->clubs );
        $this->assertSame( 'Single Club', $vm_scalar->clubs[0]->club_name );

        $player_with_object = (object) [
            'id'           => 1,
            'display_name' => 'Roger Federer',
            'club'         => (object) [ 'club_name' => 'Object Club' ],
        ];
        $vm_object          = $this->presenter->present_player( $player_with_object, clubs: [] );

        $this->assertCount( 1, $vm_object->clubs );
        $this->assertSame( 'Object Club', $vm_object->clubs[0]->club_name );

        $player_without_club = (object) [
            'id'           => 1,
            'display_name' => 'Roger Federer',
        ];
        $vm_empty            = $this->presenter->present_player( $player_without_club, clubs: [] );

        $this->assertEmpty( $vm_empty->clubs );
    }

    public function test_string_club_item_generates_link_view_model(): void {
        $vm = $this->presenter->present_player( $this->player, clubs: [ 'Westcliff Hard' ] );

        $this->assertCount( 1, $vm->clubs );
        $club_vm = $vm->clubs[0];
        $this->assertInstanceOf( Club_Link_View_Model::class, $club_vm );
        $this->assertSame( 'Westcliff Hard', $club_vm->club_name );
        $this->assertSame( 'Westcliff Hard', $club_vm->club_display_name );
        $this->assertSame( '/clubs/westcliff-hard/', $club_vm->club_url );
    }

    public function test_object_and_array_with_membership_fields_present_as_club_membership(): void {
        $object_with_registration_id = (object) [
            'club_name'       => 'Club RegId',
            'registration_id' => 101,
        ];
        $object_with_registration_date = (object) [
            'club_name'         => 'Club RegDate',
            'registration_date' => '2026-01-10',
        ];
        $array_with_approval_date = [
            'club_name'     => 'Club AppDate',
            'approval_date' => '2026-02-15',
        ];
        $array_with_removal_date = [
            'club_name'    => 'Club RemDate',
            'removal_date' => '2026-03-20',
        ];

        $vm = $this->presenter->present_player(
            $this->player,
            clubs: [
                $object_with_registration_id,
                $object_with_registration_date,
                $array_with_approval_date,
                $array_with_removal_date,
            ]
        );

        $this->assertCount( 4, $vm->clubs );
        foreach ( $vm->clubs as $club_vm ) {
            $this->assertInstanceOf( Club_Membership_View_Model::class, $club_vm );
        }
        $this->assertSame( 'Club RegId', $vm->clubs[0]->club_name );
        $this->assertSame( 101, $vm->clubs[0]->registration_id );
        $this->assertSame( 'Club RegDate', $vm->clubs[1]->club_name );
        $this->assertSame( '2026-01-10', $vm->clubs[1]->registration_date );
        $this->assertSame( 'Club AppDate', $vm->clubs[2]->club_name );
        $this->assertSame( '2026-02-15', $vm->clubs[2]->approval_date );
        $this->assertSame( 'Club RemDate', $vm->clubs[3]->club_name );
        $this->assertSame( '2026-03-20', $vm->clubs[3]->removal_date );
    }

    public function test_object_and_array_without_membership_fields_present_as_club_link(): void {
        $object_item = (object) [
            'club_name'         => 'Object Club',
            'club_display_name' => 'Object Club Display',
        ];
        $array_item = [
            'club_name'         => 'Array Club',
            'club_display_name' => 'Array Club Display',
        ];

        $vm = $this->presenter->present_player( $this->player, clubs: [ $object_item, $array_item ] );

        $this->assertCount( 2, $vm->clubs );
        $this->assertInstanceOf( Club_Link_View_Model::class, $vm->clubs[0] );
        $this->assertSame( 'Object Club', $vm->clubs[0]->club_name );
        $this->assertSame( 'Object Club Display', $vm->clubs[0]->club_display_name );

        $this->assertInstanceOf( Club_Link_View_Model::class, $vm->clubs[1] );
        $this->assertSame( 'Array Club', $vm->clubs[1]->club_name );
        $this->assertSame( 'Array Club Display', $vm->clubs[1]->club_display_name );
    }

    public function test_club_link_name_and_display_name_fallbacks(): void {
        $item_with_shortcode_only = (object) [
            'shortcode' => 'THB',
        ];
        $item_with_name_only = [
            'name' => 'Wickford Tennis Club',
        ];

        $vm = $this->presenter->present_player( $this->player, clubs: [ $item_with_shortcode_only, $item_with_name_only ] );

        $this->assertCount( 2, $vm->clubs );

        $this->assertSame( 'THB', $vm->clubs[0]->club_name );
        $this->assertSame( 'THB', $vm->clubs[0]->club_display_name );
        $this->assertSame( '/clubs/thb/', $vm->clubs[0]->club_url );

        $this->assertSame( 'Wickford Tennis Club', $vm->clubs[1]->club_name );
        $this->assertSame( 'Wickford Tennis Club', $vm->clubs[1]->club_display_name );
        $this->assertSame( '/clubs/wickford-tennis-club/', $vm->clubs[1]->club_url );
    }

    public function test_supplied_vs_generated_club_urls(): void {
        $custom_url_item = (object) [
            'club_name' => 'Custom Club',
            'club_url'  => 'https://example.com/custom-club',
        ];
        $empty_url_item = [
            'club_name' => 'Auto URL Club',
            'club_url'  => '',
        ];
        $blank_name_and_url_item = [
            'club_name' => '',
            'club_url'  => '',
        ];

        $vm = $this->presenter->present_player(
            $this->player,
            clubs: [ $custom_url_item, $empty_url_item, $blank_name_and_url_item ]
        );

        $this->assertCount( 3, $vm->clubs );
        $this->assertSame( 'https://example.com/custom-club', $vm->clubs[0]->club_url );
        $this->assertSame( '/clubs/auto-url-club/', $vm->clubs[1]->club_url );
        $this->assertSame( '', $vm->clubs[2]->club_url );
    }

    public function test_prebuilt_view_models_pass_through_with_identity_preserved(): void {
        $link_vm       = new Club_Link_View_Model( 'Prebuilt Link', '/club/prebuilt/', 'Prebuilt Link' );
        $membership_vm = new Club_Membership_View_Model( club_name: 'Prebuilt Membership' );

        $vm = $this->presenter->present_player( $this->player, clubs: [ $link_vm, $membership_vm ] );

        $this->assertCount( 2, $vm->clubs );
        $this->assertSame( $link_vm, $vm->clubs[0] );
        $this->assertSame( $membership_vm, $vm->clubs[1] );
    }

    public function test_unsupported_club_item_types_are_ignored(): void {
        $clubs = [
            123,
            null,
            true,
            3.14,
            'Valid Club',
        ];

        $vm = $this->presenter->present_player( $this->player, clubs: $clubs );

        $this->assertCount( 1, $vm->clubs );
        $this->assertSame( 'Valid Club', $vm->clubs[0]->club_name );
    }

    public function test_mixed_prebuilt_and_raw_competition_groups_with_lazy_resolution(): void {
        $prebuilt_item = new Competition_List_Item_View_Model( 'Summer Cup 2026', '/summer-cup/2026/' );
        $raw_group     = [
            (object) [ 'id' => 10, 'name' => 'Division 1' ],
        ];

        $mock_presenter = $this->createMock( Competition_Shortcode_Presenter::class );
        $presented_raw  = [ new Competition_List_Item_View_Model( 'Division 1', '/division-1/' ) ];

        $mock_presenter->expects( $this->once() )
            ->method( 'present_competition_list' )
            ->with(
                $this->equalTo( $raw_group ),
                $this->equalTo( 'league' ),
                $this->equalTo( $this->player )
            )
            ->willReturn( $presented_raw );

        $closure_invocations = 0;
        $presenter           = new Player_Presenter(
            competition_presenter: function () use ( $mock_presenter, &$closure_invocations ): Competition_Shortcode_Presenter {
                ++$closure_invocations;
                return $mock_presenter;
            }
        );

        $vm = $presenter->present_player(
            $this->player,
            player_competitions: [
                'tournaments' => [ $prebuilt_item ],
                'league'      => $raw_group,
                'cups'        => [],
            ]
        );

        $this->assertSame( 1, $closure_invocations );
        $this->assertArrayHasKey( 'tournaments', $vm->player_competitions );
        $this->assertArrayHasKey( 'league', $vm->player_competitions );
        $this->assertArrayHasKey( 'cups', $vm->player_competitions );

        $this->assertSame( [ $prebuilt_item ], $vm->player_competitions['tournaments'] );
        $this->assertSame( $presented_raw, $vm->player_competitions['league'] );
        $this->assertSame( [], $vm->player_competitions['cups'] );
    }

    public function test_integer_competition_type_keys_are_normalized_to_string(): void {
        $raw_group      = [ (object) [ 'id' => 1 ] ];
        $mock_presenter = $this->createMock( Competition_Shortcode_Presenter::class );

        $mock_presenter->expects( $this->once() )
            ->method( 'present_competition_list' )
            ->with(
                $this->equalTo( $raw_group ),
                $this->identicalTo( '2026' ),
                $this->equalTo( $this->player )
            )
            ->willReturn( [] );

        $presenter = new Player_Presenter( competition_presenter: $mock_presenter );

        $vm = $presenter->present_player(
            $this->player,
            player_competitions: [
                2026 => $raw_group,
            ]
        );

        $this->assertArrayHasKey( 2026, $vm->player_competitions );
    }

    public function test_non_array_competition_group_preserves_reset_semantics_and_throws_type_error(): void {
        $this->expectException( TypeError::class );

        $this->presenter->present_player(
            $this->player,
            player_competitions: [
                'invalid_group' => 'not an array',
            ]
        );
    }
}
