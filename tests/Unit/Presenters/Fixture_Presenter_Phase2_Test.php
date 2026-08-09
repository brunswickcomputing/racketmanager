<?php

namespace Racketmanager\Tests\Unit\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\DTOs\Fixture_Header_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Detail_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Team;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\Services\Fixture\Fixture_Link_Service;

class Fixture_Presenter_Phase2_Test extends TestCase {
    private Fixture_Presenter $presenter;
    private Fixture_Link_Service $link_service;

    protected function setUp(): void {
        parent::setUp();
        $this->link_service = $this->createStub( Fixture_Link_Service::class );
        $this->presenter    = new Fixture_Presenter( $this->link_service );
        
        // Mock WordPress functions if needed, but here we mostly use DI or stubs
        if (!function_exists('get_option')) {
            eval('function get_option($opt) { return $opt === "date_format" ? "Y-m-d" : "H:i"; }');
        }
        if (!function_exists('seo_url')) {
            eval('function seo_url($url) { return strtolower(str_replace(" ", "-", $url)); }');
        }
        if (!function_exists('__')) {
            eval('function __($text, $domain) { return $text; }');
        }
        if (!function_exists('mysql2date')) {
            eval('function mysql2date($format, $date) { return $date; }'); // Simplified for test
        }
    }

    public function test_map_to_header_read_model(): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_season')->willReturn('2023-24');
        $fixture->method('get_date')->willReturn('2023-10-10 19:00:00');
        $fixture->method('get_home_points')->willReturn('5');
        $fixture->method('get_away_points')->willReturn('3');
        $fixture->method('is_pending')->willReturn(false);

        $league = $this->createStub(League::class);
        $league->title = 'Division 1';
        
        $event = $this->createStub(Event::class);
        $event->method('get_name')->willReturn('Summer League');
        $event->name = 'Summer League';
        
        $competition = $this->createStub(Competition::class);
        $competition->method('get_type')->willReturn('league');
        $competition->type = Competition_Type::LEAGUE;

        $is_update_allowed = (object)[
            'user_can_update' => true,
            'user_type' => 'admin',
            'user_team' => 'both',
            'match_approval_mode' => false
        ];

        $home_team_details = $this->createStub(Team_Details_DTO::class);
        $home_team = $this->createStub(Team::class);
        $home_team->method('get_name')->willReturn('Home Team');
        $home_team_details->team = $home_team;

        $away_team_details = $this->createStub(Team_Details_DTO::class);
        $away_team = $this->createStub(Team::class);
        $away_team->method('get_name')->willReturn('Away Team');
        $away_team_details->team = $away_team;

        $dto = new Fixture_Details_DTO(
            fixture: $fixture
        );
        $dto->enriched_data = [
            'league' => $league,
            'event' => $event,
            'competition' => $competition,
            'home_team' => $home_team_details,
            'away_team' => $away_team_details,
            'is_update_allowed' => $is_update_allowed,
            'link' => 'https://example.com/match/123/'
        ];

        $model = $this->presenter->map_to_header_read_model($dto);
        $this->assertFalse($model->is_shortcode);

        $model_shortcode = $this->presenter->map_to_header_read_model($dto, false, true);
        $this->assertTrue($model_shortcode->is_shortcode);
    }

    public function test_map_to_detail_read_model(): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_home_team')->willReturn('1');
        $fixture->method('get_away_team')->willReturn('2');
        $fixture->method('get_host')->willReturn('home');
        
        $rubber = (object)[
            'id' => 456,
            'type' => 'MD',
            'rubber_number' => 1,
            'winner_id' => '1',
            'players' => [
                'home' => [
                    1 => (object)['display_name' => 'Player 1', 'system_record' => false]
                ],
                'away' => [
                    1 => (object)['display_name' => 'Player 2', 'system_record' => false]
                ]
            ],
            'sets' => [
                1 => ['player1' => 6, 'player2' => 4]
            ]
        ];
        $fixture->rubbers = [$rubber];
        $fixture->method('get_custom')->willReturn(['stats' => ['rubbers' => ['home' => 0, 'away' => 0]], 'comments' => ['home' => 'Home comment', 'away' => 'Away comment', 'result' => 'General comment']]);
        $fixture->method('get_comments')->willReturn(['home' => 'Home comment', 'away' => 'Away comment', 'result' => 'General comment']);

        $league = $this->createStub(League::class);
        $event = $this->createStub(Event::class);
        $event->method('get_num_sets')->willReturn(3);
        $event->scoring = 'TB';
        $event->method('get_name')->willReturn('Summer League');
        $event->name = 'Summer League';
        $competition = $this->createStub(Competition::class);
        $competition->method('get_type')->willReturn('league');
        $competition->type = Competition_Type::LEAGUE;

        $club = $this->createStub(\Racketmanager\Domain\Club::class);
        $club->method('get_name')->willReturn('Home Club');
        $club->method('get_address')->willReturn('123 Street');
        $club->method('get_website')->willReturn('https://homeclub.com');

        $match_secretary = $this->createStub(\Racketmanager\Domain\Player::class);
        $match_secretary->method('get_fullname')->willReturn('Sec Name');
        $match_secretary->method('get_contactno')->willReturn('01234');
        $match_secretary->method('get_email')->willReturn('sec@example.com');

        $home_team_details = $this->createStub(Team_Details_DTO::class);
        $home_team = $this->createStub(Team::class);
        $home_team->method('get_name')->willReturn('Home Team');
        $home_team_details->team = $home_team;
        $home_team_details->club = $club;
        $home_team_details->match_secretary = $match_secretary;

        $away_team_details = $this->createStub(Team_Details_DTO::class);
        $away_team = $this->createStub(Team::class);
        $away_team->method('get_name')->willReturn('Away Team');
        $away_team_details->team = $away_team;

        $dto = new Fixture_Details_DTO(
            fixture: $fixture
        );
        $dto->league = $league;
        $dto->event = $event;
        $dto->competition = $competition;
        $dto->home_team = $home_team_details;
        $dto->away_team = $away_team_details;
        $dto->rubbers = [$rubber];
        $dto->home_captain_name = 'Home Capt';
        $dto->home_captain_contactno = '0777';
        $dto->home_captain_email = 'capt@home.com';
        $dto->link = 'https://example.com/match/123/';
        $dto->is_update_allowed = (object)[
            'user_can_update' => true,
            'match_approval_mode' => false,
            'user_type' => 'admin'
        ];

        $model = $this->presenter->map_to_detail_read_model($dto);

        $this->assertInstanceOf(Fixture_Detail_Read_Model::class, $model);
        $this->assertNotEmpty($model->scoring_info);
        $this->assertEquals('TB', $model->scoring_info[1][1]['set_type']);
        $this->assertFalse($model->is_standalone);

        $model_standalone = $this->presenter->map_to_detail_read_model($dto, true);
        $this->assertTrue($model_standalone->is_standalone);

        $this->assertCount(1, $model->rubbers);
        $this->assertEquals('MD1', $model->rubbers[0]['title']);
        $this->assertEquals('W', $model->rubbers[0]['opponents']['home']['status_text']);
        $this->assertEquals('L', $model->rubbers[0]['opponents']['away']['status_text']);
        $this->assertCount(1, $model->rubbers[0]['sets']);
        $this->assertEquals(6, $model->rubbers[0]['sets'][1]['home']);
        $this->assertEquals('General comment', $model->general_comments);

        // Sidebar assertions
        $this->assertNotNull($model->location);
        $this->assertEquals('Home Club', $model->location['club_name']);
        $this->assertEquals('123 Street', $model->location['address']);
        $this->assertEquals('Sec Name', $model->location['match_secretary_name']);
        $this->assertEquals('01234', $model->location['match_secretary_contactno']);
        $this->assertEquals('sec@example.com', $model->location['match_secretary_email']);
        $this->assertEquals('https://homeclub.com', $model->location['website']);

        $this->assertNotNull($model->teams);
        $this->assertEquals('Home Capt', $model->teams['home']['captain_name']);
        $this->assertEquals('Home Team', $model->teams['home']['team_name']);
        $this->assertEquals('0777', $model->teams['home']['contactno']);
        $this->assertEquals('capt@home.com', $model->teams['home']['contactemail']);

        // module__aside assertions
        $this->assertTrue($model->show_print_button);
        $this->assertTrue($model->show_edit_button);
        $this->assertEquals(123, $model->match_id);
        $this->assertEquals('https://example.com/match/123/result/', $model->edit_url);
    }

    public function test_map_rubber_provides_placeholders_for_empty_sets(): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_id')->willReturn(123);
        $fixture->method('get_home_team')->willReturn('1');
        $fixture->method('get_away_team')->willReturn('2');
        $fixture->method('get_season')->willReturn('2024');

        $rubber = (object)[
            'id' => 456,
            'type' => 'MD',
            'rubber_number' => 1,
            'winner_id' => 0,
            'players' => [],
            'sets' => [] // Empty sets
        ];

        $dto = new Fixture_Details_DTO(fixture: $fixture);
        $dto->fixture = $fixture;
        $event = $this->createStub(Event::class);
        $event->method('get_num_sets')->willReturn(3);
        $event->name = 'Event';
        $dto->event = $event;
        $competition = $this->createStub(Competition::class);
        $competition->method('get_type')->willReturn('league');
        $competition->type = Competition_Type::LEAGUE;
        $dto->competition = $competition;

        // Accessing private map_rubber via reflection
        $reflection = new \ReflectionClass(Fixture_Presenter::class);
        $method = $reflection->getMethod('map_rubber');
        $method->setAccessible(true);

        $mapped = $method->invoke($this->presenter, $rubber, $dto);

        $this->assertCount(3, $mapped['sets']);
        $this->assertEquals('', $mapped['sets'][1]['home']);
        $this->assertEquals('', $mapped['sets'][2]['home']);
        $this->assertEquals('', $mapped['sets'][3]['home']);
    }

    public function test_map_to_detail_read_model_approvals_logic(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_comments' )->willReturn( [ 'home' => 'Home comment', 'away' => 'Away comment', 'result' => 'General comment' ] );
        $fixture->method( 'get_host' )->willReturn( 'home' );

        $dto = new Fixture_Details_DTO( fixture: $fixture );
        $dto->rubbers           = [];
        $dto->home_approver_name = 'Approver A';
        $dto->away_approver_name = null;

        $model = $this->presenter->map_to_detail_read_model( $dto );

        $this->assertEquals( 'Approver A', $model->approvals['home']['approver_name'] );
        $this->assertEquals( 'Home comment', $model->approvals['home']['comment'] );
        $this->assertNull( $model->approvals['away']['approver_name'] );
        $this->assertEquals( 'Away comment', $model->approvals['away']['comment'] );
        $this->assertEquals( 'General comment', $model->general_comments );
    }
}
