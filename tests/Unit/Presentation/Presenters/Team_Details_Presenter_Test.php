<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Team\Team_Hydration_DTO;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Services\Settings_Service;

class Team_Details_Presenter_Test extends TestCase {
    private Team_Details_Presenter $presenter;
    private $option_gateway;
    private $settings_service;

    protected function setUp(): void {
        $this->option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $this->settings_service = $this->createStub( Settings_Service::class );
        $this->presenter = new Team_Details_Presenter( $this->option_gateway, $this->settings_service );
    }

    public function test_presents_correct_wtn_format_for_non_s_d_event_type(): void {
        $this->settings_service->method( 'get_category' )->willReturn( [ 'wtn' => true ] );
        
        $object = (object) [ 'event' => (object) [ 'type' => 'XX' ] ];
        $team = new Team( new Team_Hydration_DTO() );
        $team_details = (object) [ 'team' => $team ];

        $vm = $this->presenter->present( $object, $team_details );
        
        $this->assertEquals( 'S', $vm->format );
        $this->assertEquals( 'Singles World Tennis Number', $vm->help_text );
    }

    public function test_presents_null_for_midnight_match_time(): void {
        $this->settings_service->method( 'get_category' )->willReturn( [] );
        
        $object = (object) [];
        $team = new Team( new Team_Hydration_DTO( info: (object) [ 'match_time' => '00:00:00' ] ) );
        $team_details = (object) [ 'team' => $team ];

        $vm = $this->presenter->present( $object, $team_details );
        
        $this->assertNull( $vm->match_time_formatted );
    }

    public function test_presents_empty_string_for_missing_club_shortcode(): void {
        $this->settings_service->method( 'get_category' )->willReturn( [] );
        
        $object = (object) [];
        $team = new Team( new Team_Hydration_DTO() );
        $team_details = (object) [ 'team' => $team, 'club' => (object) [ 'shortcode' => '' ] ];

        $vm = $this->presenter->present( $object, $team_details );
        
        $this->assertEquals( '', $vm->club_link );
    }

    public function test_presents_event_style_competition_branch(): void {
        $this->settings_service->method( 'get_category' )->willReturn( [ 'wtn' => true ] );

        $object = (object) [
            'title'          => 'Mens Doubles',
            'type'           => 'MD',
            'competition'    => (object) [ 'type' => 'league' ],
            'current_season' => [ 'name' => '2026' ],
        ];
        $team = new Team( new Team_Hydration_DTO() );
        $team_details = (object) [ 'team' => $team ];

        $vm = $this->presenter->present( $object, $team_details );
        
        $this->assertEquals( '/league/mens-doubles/2026/', $vm->standings_link );
        $this->assertEquals( 'D', $vm->format );
        $this->assertEquals( 'Doubles World Tennis Number', $vm->help_text );
    }

    public function test_present_team_row_formats_league_team(): void {
        $league = (object) [
            'id'             => 10,
            'is_championship'=> false,
            'current_season' => [ 'name' => '2026' ],
            'event'          => (object) [
                'id'          => 20,
                'competition' => (object) [
                    'is_team_entry'   => true,
                    'is_player_entry' => false,
                ],
            ],
        ];

        $team = (object) [
            'id'    => 5,
            'title' => 'Westcliff 1',
            'info'  => (object) [
                'captain'              => 'John Doe',
                'captain_contactno'    => '0123456789',
                'captain_contactemail' => 'john@example.com',
                'match_day'            => 'Wednesday',
                'match_time'           => '19:00:00',
            ],
        ];

        $row_vm = $this->presenter->present_team_row( $team, $league, '/leagues/division-1/2026' );

        $this->assertSame( 5, $row_vm->id );
        $this->assertSame( 'Westcliff 1', $row_vm->title );
        $this->assertSame( 'W1', $row_vm->initials );
        $this->assertSame( '/leagues/division-1/2026/team/westcliff-1/', $row_vm->link );
        $this->assertStringContainsString( 'data-link="/leagues/division-1/2026/team/westcliff-1/"', $row_vm->data_attrs );
        $this->assertStringContainsString( 'data-type="league"', $row_vm->data_attrs );
        $this->assertSame( 20, $row_vm->event_id );
        $this->assertSame( '-20-5', $row_vm->suffix );
        $this->assertNotNull( $row_vm->calendar_url );
        $this->assertStringContainsString( 'team_id=5', $row_vm->calendar_url );
        $this->assertTrue( $row_vm->is_team_entry );
        $this->assertFalse( $row_vm->is_player_entry );
        $this->assertSame( 'John Doe', $row_vm->captain_name );
        $this->assertSame( '0123456789', $row_vm->captain_contactno );
        $this->assertSame( 'john@example.com', $row_vm->captain_contactemail );
        $this->assertSame( 'Wednesday', $row_vm->match_day );
        $this->assertSame( '19:00:00', $row_vm->match_time );
        $this->assertSame( '19:00', $row_vm->match_time_formatted );
    }

    public function test_present_team_row_with_empty_item_link_creates_modal_attributes(): void {
        $event = (object) [
            'id'          => 15,
            'competition' => (object) [
                'is_team_entry'   => false,
                'is_player_entry' => true,
            ],
        ];

        $team = (object) [
            'id'    => 8,
            'title' => 'Thorpe Bay',
            'info'  => (object) [
                'players' => [
                    (object) [
                        'display_name' => 'Alice Player',
                        'contactno'    => '07777777777',
                        'email'        => 'alice@example.com',
                    ],
                ],
            ],
        ];

        $row_vm = $this->presenter->present_team_row( $team, $event, null );

        $this->assertSame( '#', $row_vm->link );
        $this->assertStringContainsString( 'data-action="open-team-edit-modal"', $row_vm->data_attrs );
        $this->assertStringContainsString( 'data-team-id="8"', $row_vm->data_attrs );
        $this->assertStringContainsString( 'data-event-id="15"', $row_vm->data_attrs );
        $this->assertTrue( $row_vm->is_player_entry );
        $this->assertCount( 1, $row_vm->players );
        $this->assertSame( 'Alice Player', $row_vm->players[0]['display_name'] );
    }

    public function test_present_team_rows_transforms_collection_and_preserves_existing(): void {
        $team1 = (object) [ 'id' => 1, 'title' => 'Alpha Team' ];
        $team2 = (object) [ 'id' => 2, 'title' => 'Beta Team' ];

        $rows = $this->presenter->present_team_rows( [ $team1, $team2 ], null, '/base' );

        $this->assertCount( 2, $rows );
        $this->assertSame( 'Alpha Team', $rows[0]->title );
        $this->assertSame( 'Beta Team', $rows[1]->title );

        // Preserves existing Team_Row_View_Model
        $existing = $rows[0];
        $this->assertSame( $existing, $this->presenter->present_team_row( $existing ) );
    }
}
