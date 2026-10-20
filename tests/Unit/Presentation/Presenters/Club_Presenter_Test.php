<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club_Player;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Player;
use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\View_Models\Club_Roster_Player_View_Model;
use Racketmanager\Tests\Helpers\Club_Test_Factory;

class Club_Presenter_Test extends TestCase {
    private Club_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Club_Presenter();
    }

    public function test_get_link_with_domain_club(): void {
        $club = Club_Test_Factory::create(
            id: 5,
            name: 'Leigh Tennis Club',
            shortcode: 'leigh-tc'
        );

        $link = $this->presenter->get_link( $club );
        $this->assertEquals( '/clubs/leigh_tc/', $link );

        $roles_link = $this->presenter->get_link( $club, 'roles' );
        $this->assertEquals( '/clubs/leigh_tc/roles/', $roles_link );

        $comp_link = $this->presenter->get_link( $club, 'competitions' );
        $this->assertEquals( '/clubs/leigh_tc/competitions/', $comp_link );
    }

    public function test_get_display_name(): void {
        $club = (object) array(
            'name' => 'Westcliff Hardcourt',
        );

        $name = $this->presenter->get_display_name( $club );
        $this->assertEquals( 'Westcliff Hardcourt', $name );
    }

    public function test_get_heading_tag(): void {
        $this->assertEquals( 'h1', $this->presenter->get_heading_tag( true ) );
        $this->assertEquals( 'h2', $this->presenter->get_heading_tag( false ) );
        $this->assertEquals( 'h3', $this->presenter->get_heading_tag( false, 3 ) );
        $this->assertEquals( 'h4', $this->presenter->get_heading_tag( true, 4 ) );
    }

    public function test_get_map_url(): void {
        $club = (object) array(
            'address' => '123 Tennis Lane, SS0 9SB',
        );

        $map_url = $this->presenter->get_map_url( $club, 'TEST_MAP_KEY' );
        $this->assertStringContainsString( 'key=' . rawurlencode( 'TEST_MAP_KEY' ), $map_url );
        $this->assertStringContainsString( 'q=' . rawurlencode( '123 Tennis Lane, SS0 9SB' ), $map_url );

        $this->assertNull( $this->presenter->get_map_url( (object) array( 'address' => '' ), 'TEST_MAP_KEY' ) );
        $this->assertNull( $this->presenter->get_map_url( $club, null ) );
    }

    public function test_get_subpage_links(): void {
        $club = (object) array(
            'shortcode' => 'westcliff-hard',
        );

        $links = $this->presenter->get_subpage_links( $club );
        $this->assertArrayHasKey( 'roles', $links );
        $this->assertArrayHasKey( 'competitions', $links );
        $this->assertArrayHasKey( 'players', $links );
        $this->assertEquals( '/clubs/westcliff_hard/roles/', $links['roles'] );
        $this->assertEquals( '/clubs/westcliff_hard/competitions/', $links['competitions'] );
        $this->assertEquals( '/clubs/westcliff_hard/players/', $links['players'] );
    }

    public function test_get_initials(): void {
        $this->assertEquals( 'LTC', $this->presenter->get_initials( 'Leigh Tennis Club' ) );
        $this->assertEquals( 'LT', $this->presenter->get_initials( 'Leigh TC' ) );
        $this->assertEquals( 'T', $this->presenter->get_initials( 'TBLTC' ) );
        // Hyphenated shortcodes split on space only (preserving HEAD semantics):
        $this->assertEquals( 'L', $this->presenter->get_initials( 'Leigh-on-Sea' ) );
        // Lowercase shortcodes preserve case:
        $this->assertEquals( 'wt', $this->presenter->get_initials( 'wickford tc' ) );
        $this->assertEquals( 'WL', $this->presenter->get_initials( 'Westcliff Lawn' ) );
    }

    public function test_error_view_model(): void {
        $error_vm = \Racketmanager\Presentation\View_Models\Club_View_Model::for_error( 'Club not found' );
        $this->assertEquals( 'alert', $error_vm->template );
        $this->assertEquals( 'Club not found', $error_vm->error_message );
    }

    public function test_present_creates_immutable_view_model(): void {
        $club = (object) array(
            'id'              => 12,
            'name'            => 'Rayleigh LTC',
            'shortcode'       => 'Rayleigh',
            'address'         => 'Watchfield Common, Rayleigh',
            'roles'           => array( 'secretary' => 'Jane' ),
            'teams'           => array( 'A', 'B' ),
            'match_secretary' => (object) array(
                'id'           => 99,
                'display_name' => 'Jane Smith',
                'email'        => 'jane@example.com',
                'contactno'    => '07123456789',
            ),
            'contact_no'      => '01268000000',
            'website'         => 'https://rayleightc.local',
            'founded'         => '1950',
            'facilities'      => '4 hard courts',
        );

        $vm = $this->presenter->present(
            club: $club,
            standalone: true,
            google_maps_key: 'MAP_KEY',
            user_can_update_club: true,
            header_level: 2,
            template: 'club-custom'
        );

        $this->assertEquals( 'club-custom', $vm->template );
        $this->assertEquals( 12, $vm->id );
        $this->assertEquals( 'Rayleigh LTC', $vm->name );
        $this->assertEquals( 'Rayleigh', $vm->shortcode );
        $this->assertEquals( 'rayleigh', $vm->slug );
        $this->assertEquals( 'Watchfield Common, Rayleigh', $vm->address );
        $this->assertEquals( '/clubs/rayleigh/', $vm->link );
        $this->assertEquals( 'R', $vm->initials );
        $this->assertEquals( 'h2', $vm->heading_tag );
        $this->assertTrue( $vm->user_can_update_club );
        $this->assertTrue( $vm->standalone );
        $this->assertEquals( 'Jane Smith', $vm->match_secretary_name );
        $this->assertEquals( 99, $vm->match_secretary_id );
        $this->assertEquals( 'jane@example.com', $vm->match_secretary_email );
        $this->assertEquals( '07123456789', $vm->match_secretary_contact_no );
        $this->assertEquals( '01268000000', $vm->contact_no );
        $this->assertEquals( 'https://rayleightc.local', $vm->website );
        $this->assertEquals( '1950', $vm->founded );
        $this->assertEquals( '4 hard courts', $vm->facilities );
        $this->assertStringContainsString( 'key=MAP_KEY', $vm->map_url );

        $vars = $vm->template_vars();
        $this->assertSame( $vm, $vars['vm'] );
        $this->assertEquals( 'Rayleigh LTC', $vars['club_name'] );
        $this->assertEquals( 'Jane Smith', $vars['match_secretary_name'] );
    }

    public function test_format_rating_display(): void {
        $this->assertSame( '[S:9.2] - [D:8.5]', $this->presenter->format_rating_display( [ 'S' => '9.2', 'D' => '8.5' ] ) );
        $this->assertSame( '[S:-] - [D:-]', $this->presenter->format_rating_display( [] ) );
        $this->assertSame( '[S:9.2] - [D:-]', $this->presenter->format_rating_display( [ 'S' => '9.2' ] ) );
    }

    public function test_present_roster_player_from_real_club_player_dto(): void {
        $club         = Club_Test_Factory::create( id: 10, name: 'Leigh Tennis Club', shortcode: 'Leigh TC' );
        $player       = new Player();
        $player->id   = 42;
        $player->display_name = 'Roger Federer';
        $player->gender       = 'M';
        $player->btm          = '12345678';
        $player->wtn          = [ 'S' => '9.4', 'D' => '10.1' ];
        $registration         = new Club_Player();
        $registration->id     = 101;
        $dto                  = new Club_Player_DTO( $player, $club, $registration );

        $presented = $this->presenter->present_roster_player( $dto, $club, use_wtn: true );
        $this->assertInstanceOf( Club_Roster_Player_View_Model::class, $presented );
        $this->assertSame( 101, $presented->registration_id );
        $this->assertSame( 42, $presented->user_id );
        $this->assertSame( 'Roger Federer', $presented->display_name );
        $this->assertSame( 'M', $presented->gender );
        $this->assertSame( '/clubs/leigh-tc/players/roger-federer/', $presented->player_url );
        $this->assertSame( '12345678', $presented->btm );
        $this->assertSame( '[S:9.4] - [D:10.1]', $presented->rating_display );

        $rating_mode = $this->presenter->present_roster_player( $dto, $club, use_wtn: false );
        $this->assertSame( '[S:-] - [D:-]', $rating_mode->rating_display, 'Registrations carry no rating points' );
    }

    public function test_present_club_competition_and_event_view_models(): void {
        $club        = (object) [ 'shortcode' => 'Westcliff Hard' ];
        $competition = (object) [ 'name' => 'Summer League', 'current_season' => [ 'name' => '2026' ] ];
        $event       = (object) [ 'id' => 7, 'name' => 'Men\'s Division 1', 'current_season' => [ 'name' => '2026' ] ];
        $off_season  = (object) [ 'id' => 8, 'name' => 'Plate' ];

        $event_vm       = $this->presenter->present_club_event( $event, $club, [ 'row' ] );
        $off_season_vm  = $this->presenter->present_club_event( $off_season, $club );
        $competition_vm = $this->presenter->present_club_competition( $competition, $club, [ 3 => $event_vm, 9 => $off_season_vm ] );

        $this->assertSame( 7, $event_vm->id );
        $this->assertSame( 'Men\'s Division 1', $event_vm->name );
        $this->assertSame( '/clubs/westcliff-hard/event/mens-division-1/', $event_vm->url );
        $this->assertSame( [ 'row' ], $event_vm->team_rows );
        $this->assertSame( '', $off_season_vm->url );
        $this->assertSame( 'Summer League', $competition_vm->name );
        $this->assertSame( '/summer-league/2026/club/westcliff-hard/', $competition_vm->url );
        $this->assertSame( [ $event_vm, $off_season_vm ], $competition_vm->events );
    }

    public function test_get_club_competition_and_event_links(): void {
        $comp = (object) [
            'name'           => 'Summer League',
            'current_season' => [ 'name' => '2026' ],
        ];
        $club = (object) [
            'shortcode' => 'Westcliff Hard',
        ];
        $event = (object) [
            'name' => 'Men\'s Division 1',
        ];

        $comp_link = $this->presenter->get_club_competition_link( $comp, $club );
        $this->assertSame( '/summer-league/2026/club/westcliff-hard/', $comp_link );

        $event_link = $this->presenter->get_club_event_link( $event, $club );
        $this->assertSame( '/clubs/westcliff-hard/event/mens-division-1/', $event_link );
    }
}
