<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\League\Queries;

use ArrayIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\Queries\Get_League_Championship_Handler;
use Racketmanager\Application\League\Queries\Get_League_Championship_Query;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Handler;
use Racketmanager\Application\League\Queries\Get_League_Crosstable_Query;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Handler;
use Racketmanager\Application\League\Queries\Get_League_Last_5_Query;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Handler;
use Racketmanager\Application\League\Queries\Get_League_Match_Dropdown_Query;
use Racketmanager\Application\League\Queries\Get_League_Matches_Handler;
use Racketmanager\Application\League\Queries\Get_League_Matches_Query;
use Racketmanager\Application\League\Queries\Get_League_Standings_Handler;
use Racketmanager\Application\League\Queries\Get_League_Standings_Query;
use Racketmanager\Application\League\Queries\Get_League_Teams_Handler;
use Racketmanager\Application\League\Queries\Get_League_Teams_Query;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Team;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Championship;
use Racketmanager\Infrastructure\Gateways\Option\Wp_Option_Gateway;
use Racketmanager\Application\League\DTOs\League_View_State;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Presentation\Presenters\Team_Details_Presenter;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Stats_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use RuntimeException;
use Throwable;
use TypeError;

class League_Query_Handler_Delegation_Test extends TestCase {
    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_is_user_logged_in'],
            $GLOBALS['wp_stubs_current_user_can'],
            $GLOBALS['wp_stubs_current_user_can_calls'],
            $GLOBALS['wp_stubs_current_user_can_callback'],
            $GLOBALS['wp_stubs_get_current_user_id'],
            $GLOBALS['wp_stubs_current_user_id']
        );

        parent::tearDown();
    }

	private function league(): League {
		$event              = $this->createStub( Event::class );
		$event->competition = (object) [
			'type'            => 'league',
			'is_team_entry'   => true,
			'is_player_entry' => false,
		];

		$league                 = $this->createStub( League::class );
		$league->id             = 7;
		$league->title          = 'Division 1';
		$league->current_season = [ 'name' => '2026' ];
		$league->event          = $event;

		return $league;
	}

	private function league_service( League $league ): League_Service {
		$league_service = $this->createStub( League_Service::class );
		$league_service->method( 'get_league' )->willReturn( $league );

		return $league_service;
	}

	private function team_details_presenter(): Team_Details_Presenter {
		return new Team_Details_Presenter( new Wp_Option_Gateway(), new Settings_Service() );
	}

	private function template_renderer(): Shortcode_Template_Renderer {
		$renderer = $this->createStub( Shortcode_Template_Renderer::class );
		$renderer->method( 'template_exists' )->willReturn( false );

		return $renderer;
	}

	public function test_teams_handler_list_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$team         = (object) [ 'id' => 101, 'title' => 'Westcliff 1' ];
		$info         = (object) [ 'captain' => 'Jane Smith' ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_league_teams' )
			->with(
				$league,
				[ 'orderby' => [ 'title' => 'ASC' ] ]
			)
			->willReturn( [ $team ] );
		$league_query->expects( $this->once() )
			->method( 'get_team_dtls' )
			->with( $league, 101 )
			->willReturn( $info );
		$league_query->method( 'set_num_teams' )->willReturn( new League_View_State() );

		$handler = new Get_League_Teams_Handler(
			$this->league_service( $league ),
			$this->createStub( Team_Service::class ),
			$this->createStub( Player_Stats_Service::class ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Teams_Query( league_id: 7 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertFalse( $result->is_team_details );
		$this->assertSame( $league, $result->league );
		$this->assertCount( 1, $result->league_teams );
	}

	public function test_teams_handler_returns_team_pagination_state_from_query_service(): void {
		$league       = $this->league();
		$team         = (object) [ 'id' => 101, 'title' => 'Westcliff 1' ];
		$pagination   = '<nav class="pagination-links"><a class="page-numbers" href="?team_paged=2">2</a></nav>';
		$view_state   = new League_View_State(
			tab: 'teams',
			num_teams_per_page: 10,
			current_page_teams: 1,
			num_pages_teams: 2,
			pagination_teams: $pagination,
		);
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->method( 'get_league_teams' )->willReturn( [ $team ] );
		$league_query->method( 'get_team_dtls' )->willReturn( false );
		$league_query->expects( $this->once() )
			->method( 'set_num_teams' )
			->with( $league )
			->willReturn( $view_state );

		$handler = new Get_League_Teams_Handler(
			$this->league_service( $league ),
			$this->createStub( Team_Service::class ),
			$this->createStub( Player_Stats_Service::class ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Teams_Query( league_id: 7 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( 'teams', $result->view_state->tab );
		$this->assertSame( 10, $result->view_state->num_teams_per_page );
		$this->assertSame( 2, $result->view_state->num_pages_teams );
		$this->assertSame( $pagination, $result->view_state->pagination_teams );
	}

	public function test_teams_handler_skips_info_assignment_when_team_details_missing(): void {
		$league = $this->league();
		$team   = League_Team::from_object(
			(object) [
				'id'             => 101,
				'table_id'       => 1,
				'title'          => 'Westcliff 1',
				'points_plus'    => 0,
				'points_minus'   => 0,
				'points_2_plus'  => 0,
				'points_2_minus' => 0,
				'custom'         => '',
				'roster'         => '',
			]
		);
		$this->assertNull( $team->info );

		$league_query = $this->createStub( League_Query_Service::class );
		$league_query->method( 'get_league_teams' )->willReturn( [ $team ] );
		$league_query->method( 'get_team_dtls' )->willReturn( false );
		$league_query->method( 'set_num_teams' )->willReturn( new League_View_State() );

		$handler = new Get_League_Teams_Handler(
			$this->league_service( $league ),
			$this->createStub( Team_Service::class ),
			$this->createStub( Player_Stats_Service::class ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Teams_Query( league_id: 7 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertNull( $team->info );
		$this->assertCount( 1, $result->league_teams );
	}

	public function test_teams_handler_details_delegates_to_league_query_service(): void {
		$league = $this->league();
		$team   = $this->createStub( Team::class );
		$team->id = 12;

		$team_service = $this->createStub( Team_Service::class );
		$team_service->method( 'get_team_details' )->willReturn( new Team_Details_DTO( $team ) );

		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_team_dtls' )
			->with( $league, 12 )
			->willReturn( (object) [ 'captain' => 'Jane' ] );
		$league_query->expects( $this->once() )
			->method( 'get_league_team' )
			->with( $league, 12 )
			->willReturn( (object) [ 'rank' => 1 ] );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static function ( array $args ): bool {
						return 12 === ( $args['team_id'] ?? null )
							&& false === ( $args['match_day'] ?? null )
							&& 'false' === ( $args['limit'] ?? null )
							&& true === ( $args['reset_query_args'] ?? null );
					}
				)
			)
			->willReturn( [] );
		$league_query->expects( $this->once() )
			->method( 'get_players' )
			->with( $league, [ 'team' => 12 ] )
			->willReturn( [] );

		$stats = $this->createStub( Player_Stats_Service::class );
		$stats->method( 'populate_players_stats' )->willReturn( [] );

		$handler = new Get_League_Teams_Handler(
			$this->league_service( $league ),
			$team_service,
			$stats,
			$league_query
		);

		$result = $handler->handle( new Get_League_Teams_Query( league_id: 7, team_id: '12' ) );

		$this->assertFalse( $result->is_error() );
		$this->assertTrue( $result->is_team_details );
		$this->assertSame( 12, $result->team_id );
		$this->assertSame( $team, $result->team );
	}

    public function test_teams_handler_returns_error_when_league_not_found(): void {
        $league_service = $this->createStub( League_Service::class );
        $league_service->method( 'get_league' )->willReturn( null );

        $handler = new Get_League_Teams_Handler(
            $league_service,
            $this->createStub( Team_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $this->createStub( League_Query_Service::class )
        );

        $result = $handler->handle( new Get_League_Teams_Query( league_id: 999 ) );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'League not found', $result->error_message );
        $this->assertNull( $result->league );

        $result_empty = $handler->handle( new Get_League_Teams_Query( league_id: 0 ) );

        $this->assertTrue( $result_empty->is_error() );
        $this->assertSame( 'League not found', $result_empty->error_message );
        $this->assertNull( $result_empty->league );
    }

    #[DataProvider( 'provide_team_details_service_throwables' )]
    public function test_teams_handler_returns_error_when_team_details_service_throws( Throwable $throwable ): void {
        $league       = $this->league();
        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )
            ->willThrowException( $throwable );

        $handler = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $team_service,
            $this->createStub( Player_Stats_Service::class ),
            $this->createStub( League_Query_Service::class )
        );

        $result = $handler->handle( new Get_League_Teams_Query( league_id: 7, team_id: '12' ) );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Team not found', $result->error_message );
        $this->assertNull( $result->team );
        $this->assertNull( $result->team_details );
    }

    /**
     * Data provider for team details service failure throwables.
     *
     * @return array<string, array{0: Throwable}>
     */
    public static function provide_team_details_service_throwables(): array {
        return [
            'runtime exception' => [ new RuntimeException( 'Failed to retrieve team details' ) ],
            'type error'        => [ new TypeError( 'Type mismatch in team details' ) ],
        ];
    }

    public function test_teams_handler_returns_error_when_team_details_has_null_team(): void {
        $league       = $this->league();
        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )
            ->willReturn( $this->createStub( Team_Details_DTO::class ) );

        $handler = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $team_service,
            $this->createStub( Player_Stats_Service::class ),
            $this->createStub( League_Query_Service::class )
        );

        $result = $handler->handle( new Get_League_Teams_Query( league_id: 7, team_id: '12' ) );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Team not found in league', $result->error_message );
    }

    public function test_teams_handler_returns_error_when_team_not_in_league_or_unranked(): void {
        $league = $this->league();
        $team   = $this->createStub( Team::class );
        $team->id = 12;

        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )
            ->willReturn( new Team_Details_DTO( $team ) );

        $league_query = $this->createMock( League_Query_Service::class );
        $league_query->expects( $this->once() )
            ->method( 'get_team_dtls' )
            ->with( $league, 12 )
            ->willReturn( (object) [ 'captain' => 'John Doe' ] );
        $league_query->expects( $this->once() )
            ->method( 'get_league_team' )
            ->with( $league, 12 )
            ->willReturn( false );

        $handler = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $team_service,
            $this->createStub( Player_Stats_Service::class ),
            $league_query
        );

        $result = $handler->handle( new Get_League_Teams_Query( league_id: 7, team_id: '12' ) );

        $this->assertTrue( $result->is_error() );
        $this->assertSame( 'Team not found in league', $result->error_message );

        $league_query_empty_rank = $this->createMock( League_Query_Service::class );
        $league_query_empty_rank->expects( $this->once() )
            ->method( 'get_team_dtls' )
            ->with( $league, 12 )
            ->willReturn( (object) [ 'captain' => 'John Doe' ] );
        $league_query_empty_rank->expects( $this->once() )
            ->method( 'get_league_team' )
            ->with( $league, 12 )
            ->willReturn( (object) [ 'rank' => '' ] );

        $handler_empty_rank = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $team_service,
            $this->createStub( Player_Stats_Service::class ),
            $league_query_empty_rank
        );

        $result_empty_rank = $handler_empty_rank->handle( new Get_League_Teams_Query( league_id: 7, team_id: '12' ) );

        $this->assertTrue( $result_empty_rank->is_error() );
        $this->assertSame( 'Team not found in league', $result_empty_rank->error_message );
    }

    public function test_teams_handler_details_populates_matches_players_view_state_and_side_effects(): void {
        $league       = $this->league();
        $team         = $this->createStub( Team::class );
        $team->id     = 12;
        $info         = (object) [ 'captain' => 'Jane Smith' ];
        $standings    = (object) [ 'rank' => 3, 'points' => 24 ];
        $raw_matches  = [ (object) [ 'id' => 1001, 'home_team' => 12 ] ];
        $players      = [ (object) [ 'id' => 501, 'name' => 'Alice' ] ];
        $populated    = [ (object) [ 'id' => 501, 'name' => 'Alice', 'wins' => 5 ] ];
        $team_details = new Team_Details_DTO( $team );

        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturn( $team_details );

        $league_query = $this->createMock( League_Query_Service::class );
        $league_query->expects( $this->once() )
            ->method( 'resolve_requested_season' )
            ->with( $league )
            ->willReturn( '2026' );
        $league_query->expects( $this->once() )
            ->method( 'set_season' )
            ->with( $league );
        $league_query->expects( $this->once() )
            ->method( 'get_team_dtls' )
            ->with( $league, 12 )
            ->willReturn( $info );
        $league_query->expects( $this->once() )
            ->method( 'get_league_team' )
            ->with( $league, 12 )
            ->willReturn( $standings );
        $league_query->expects( $this->once() )
            ->method( 'get_matches' )
            ->with(
                $league,
                [
                    'team_id'          => 12,
                    'match_day'        => false,
                    'limit'            => 'false',
                    'reset_query_args' => true,
                ]
            )
            ->willReturn( $raw_matches );
        $league_query->expects( $this->once() )
            ->method( 'get_players' )
            ->with( $league, [ 'team' => 12 ] )
            ->willReturn( $players );

        $stats = $this->createMock( Player_Stats_Service::class );
        $stats->expects( $this->once() )
            ->method( 'populate_players_stats' )
            ->with( $players, $league, '2026', 'league' )
            ->willReturn( $populated );

        $handler = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $team_service,
            $stats,
            $league_query
        );

        $result = $handler->handle(
            new Get_League_Teams_Query(
                league_id: 7,
                template: 'custom_cards',
                team_id: '12',
                group: 'Group A'
            )
        );

        $this->assertFalse( $result->is_error() );
        $this->assertTrue( $result->is_team_details );
        $this->assertSame( 12, $result->team_id );
        $this->assertSame( $team, $result->team );
        $this->assertSame( $team_details, $result->team_details );
        $this->assertSame( $raw_matches, $result->raw_matches );
        $this->assertSame( 'custom_cards', $result->template );

        // Assert side-effects on $team.
        $this->assertSame( $info, $team->info );
        $this->assertSame( $standings, $team->standings );
        $this->assertSame( $populated, $team->players );

        // Assert side-effects on $league.
        $this->assertSame( $team, $league->team );

        // Assert view state.
        $this->assertInstanceOf( League_View_State::class, $result->view_state );
        $this->assertSame( 'teams', $result->view_state->tab );
        $this->assertSame( 'custom_cards', $result->view_state->get_template( 'teams' ) );
        $this->assertSame( 'Group A', $result->view_state->extra['group'] ?? null );
    }

    public function test_teams_handler_resolves_user_edit_permissions_correctly(): void {
        $league       = $this->league();
        $team         = $this->createStub( Team::class );
        $team->id     = 12;
        $secretary    = $this->createStub( Player::class );
        $secretary->id = 42;
        $team_details = new Team_Details_DTO( $team, match_secretary: $secretary );

        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturn( $team_details );

        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_league_team' )->willReturn( (object) [ 'rank' => 1 ] );
        $league_query->method( 'get_matches' )->willReturn( [] );
        $league_query->method( 'get_players' )->willReturn( [] );

        $handler = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $team_service,
            $this->createStub( Player_Stats_Service::class ),
            $league_query
        );

        $query = new Get_League_Teams_Query( league_id: 7, team_id: '12' );

        // Case A: Logged out user cannot edit even if administrator capability and match secretary ID match.
        $GLOBALS['wp_stubs_is_user_logged_in']   = false;
        $GLOBALS['wp_stubs_current_user_can']    = true;
        $GLOBALS['wp_stubs_get_current_user_id'] = 42;
        $result = $handler->handle( $query );
        $this->assertFalse( $result->user_can_edit_team );

        // Case B: Logged in administrator with manage_racketmanager capability can edit.
        $GLOBALS['wp_stubs_is_user_logged_in']   = true;
        $GLOBALS['wp_stubs_current_user_can']    = true;
        $GLOBALS['wp_stubs_get_current_user_id'] = 999;
        $result = $handler->handle( $query );
        $this->assertTrue( $result->user_can_edit_team );

        // Case C: Logged in match secretary can edit.
        $GLOBALS['wp_stubs_is_user_logged_in']   = true;
        $GLOBALS['wp_stubs_current_user_can']    = false;
        $GLOBALS['wp_stubs_get_current_user_id'] = 42;
        $result = $handler->handle( $query );
        $this->assertTrue( $result->user_can_edit_team );

        // Case D: Logged in unlinked non-secretary user cannot edit.
        $GLOBALS['wp_stubs_is_user_logged_in']   = true;
        $GLOBALS['wp_stubs_current_user_can']    = false;
        $GLOBALS['wp_stubs_get_current_user_id'] = 77;
        $result = $handler->handle( $query );
        $this->assertFalse( $result->user_can_edit_team );

        // Case E: Logged in non-admin user when team has no match secretary and current user id is 0 cannot edit.
        $team_no_sec_details = new Team_Details_DTO( $team );
        $team_service_no_sec = $this->createStub( Team_Service::class );
        $team_service_no_sec->method( 'get_team_details' )->willReturn( $team_no_sec_details );
        $handler_no_sec      = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $team_service_no_sec,
            $this->createStub( Player_Stats_Service::class ),
            $league_query
        );
        $GLOBALS['wp_stubs_is_user_logged_in']   = true;
        $GLOBALS['wp_stubs_current_user_can']    = false;
        $GLOBALS['wp_stubs_get_current_user_id'] = 0;
        $result_no_sec                           = $handler_no_sec->handle( $query );
        $this->assertFalse( $result_no_sec->user_can_edit_team );
    }

    public function test_teams_handler_list_with_group_filter_and_item_link(): void {
        $league       = $this->league();
        $team         = (object) [ 'id' => 101, 'title' => 'Westcliff 1' ];
        $info         = (object) [ 'captain' => 'Jane Smith' ];
        $league_query = $this->createMock( League_Query_Service::class );
        $league_query->expects( $this->once() )
            ->method( 'get_league_teams' )
            ->with(
                $league,
                [
                    'orderby' => [ 'title' => 'ASC' ],
                    'group'   => 'Division 1B',
                ]
            )
            ->willReturn( [ $team ] );
        $league_query->expects( $this->once() )
            ->method( 'set_num_teams' )
            ->with( $league )
            ->willReturn( new League_View_State() );
        $league_query->expects( $this->once() )
            ->method( 'get_team_dtls' )
            ->with( $league, 101 )
            ->willReturn( $info );

        $handler = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $this->createStub( Team_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $league_query
        );

        $result = $handler->handle(
            new Get_League_Teams_Query(
                league_id: 7,
                template: 'summary',
                group: 'Division 1B'
            )
        );

        $this->assertFalse( $result->is_error() );
        $this->assertFalse( $result->is_team_details );
        $this->assertSame( [ $team ], $result->league_teams );
        $this->assertSame( $info, $team->info );
        $this->assertSame( '/league/division-1/2026', $result->item_link );
        $this->assertSame( 'summary', $result->template );
        $this->assertSame( 'teams', $result->view_state->tab );
        $this->assertSame( 'summary', $result->view_state->get_template( 'teams' ) );
        $this->assertSame( 'Division 1B', $result->view_state->extra['group'] ?? null );
    }

    public function test_teams_handler_list_builds_item_link_with_enum_competition_type(): void {
        $league                           = $this->league();
        $league->event->competition->type = Competition_Type::LEAGUE;

        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_league_teams' )->willReturn( [] );
        $league_query->method( 'set_num_teams' )->willReturn( new League_View_State() );

        $handler = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $this->createStub( Team_Service::class ),
            $this->createStub( Player_Stats_Service::class ),
            $league_query
        );

        $result = $handler->handle( new Get_League_Teams_Query( league_id: 7 ) );

        $this->assertFalse( $result->is_error() );
        $this->assertSame( '/league/division-1/2026', $result->item_link );
    }

    public function test_teams_handler_details_normalizes_iterable_matches(): void {
        $league       = $this->league();
        $team         = $this->createStub( Team::class );
        $team->id     = 12;
        $team_details = new Team_Details_DTO( $team );

        $team_service = $this->createStub( Team_Service::class );
        $team_service->method( 'get_team_details' )->willReturn( $team_details );

        $league_query = $this->createStub( League_Query_Service::class );
        $league_query->method( 'get_league_team' )->willReturn( (object) [ 'rank' => 1 ] );
        $league_query->method( 'get_matches' )
            ->willReturn( new ArrayIterator( [ (object) [ 'id' => 999, 'home_team' => 12 ] ] ) );
        $league_query->method( 'get_players' )->willReturn( [] );

        $handler = new Get_League_Teams_Handler(
            $this->league_service( $league ),
            $team_service,
            $this->createStub( Player_Stats_Service::class ),
            $league_query
        );

        $result = $handler->handle( new Get_League_Teams_Query( league_id: 7, team_id: '12' ) );

        $this->assertFalse( $result->is_error() );
        $this->assertIsArray( $result->raw_matches );
        $this->assertCount( 1, $result->raw_matches );
        $this->assertSame( 999, $result->raw_matches[0]->id );
    }

	public function test_standings_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$teams        = [ (object) [ 'id' => 101, 'title' => 'Westcliff 1' ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'set_season' )
			->with( $league, false );
		$league_query->expects( $this->once() )
			->method( 'get_league_teams' )
			->with(
				$league,
				[ 'orderby' => [ 'rank' => 'ASC' ], 'active' => true ]
			)
			->willReturn( $teams );

		$handler = new Get_League_Standings_Handler(
			$this->league_service( $league ),
			null,
			$league_query
		);

		$vm = $handler->handle( new Get_League_Standings_Query( league_id: 7 ) );

		$this->assertNotNull( $vm );
		$this->assertSame( $league, $vm->league );
		$this->assertSame( $teams, $vm->teams );
		$this->assertInstanceOf( League_View_State::class, $vm->view_state );
		$this->assertSame( 'standings', $vm->view_state->tab );
		$this->assertSame( '', $vm->view_state->get_template( 'standings' ) );
	}

	public function test_crosstable_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$teams        = [ (object) [ 'id' => 101, 'title' => 'Westcliff 1' ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'set_season' )
			->with( $league, false );
		$league_query->expects( $this->once() )
			->method( 'get_league_teams' )
			->with( $league, [ 'orderby' => [ 'rank' => 'ASC' ] ] )
			->willReturn( $teams );
		// $league->teams stays empty (the mock does not populate it): no columns, hence no cell queries.
		$league_query->expects( $this->never() )->method( 'get_crosstable_field' );

		$handler = new Get_League_Crosstable_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Crosstable_Query( league_id: 7 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( '', $result->template );
		$this->assertSame( $teams, $result->teams );
		$this->assertSame( [], $result->columns );
		$this->assertSame( [], $result->cells );
	}

	public function test_crosstable_handler_precomputes_cells_for_every_row_and_column(): void {
		$league       = $this->league();
		$teams        = [
			(object) [ 'id' => 101, 'title' => 'Westcliff 1', 'rank' => 1 ],
			(object) [ 'id' => 202, 'title' => 'Leigh 2', 'rank' => 2 ],
		];
		// League order differs from the ranked order and carries a third team that has no ranked row.
		$league_order = [ $teams[1], $teams[0], (object) [ 'id' => 303, 'title' => 'Thorpe 3', 'rank' => 3 ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->method( 'get_league_teams' )
			->willReturnCallback(
                static function ( League $l ) use ( $teams, $league_order ): array {
                    $l->teams = $league_order;
                    return $teams;
                }
            );
		$league_query->expects( $this->exactly( 4 ) )
			->method( 'get_crosstable_field' )
			->willReturnCallback( static fn( League $l, int $team_id, int $opponent_id ): string => $team_id === $opponent_id ? '&nbsp;' : "{$team_id}v{$opponent_id}" );

		$handler = new Get_League_Crosstable_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Crosstable_Query( league_id: 7 ) );

		// Columns follow the league order, capped to the number of ranked rows the template renders.
		$this->assertSame( [ $teams[1], $teams[0] ], $result->columns );
		$this->assertSame(
			[
				101 => [ 202 => '101v202', 101 => '&nbsp;' ],
				202 => [ 202 => '&nbsp;', 101 => '202v101' ],
			],
			$result->cells
		);
	}

	public function test_last_5_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'set_season' )
			->with( $league );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static function ( array $args ): bool {
						return 'prev' === ( $args['time'] ?? null )
							&& 12 === ( $args['team_id'] ?? null )
							&& -1 === ( $args['match_day'] ?? null )
							&& 5 === ( $args['limit'] ?? null );
					}
				)
			)
			->willReturn( [] );

		$handler = new Get_League_Last_5_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Last_5_Query( league_id: 7, team_id: 12 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( '', $result->template );
		$this->assertSame( [], $result->matches );
		$this->assertSame( 12, $result->team_id );
	}

	public function test_match_dropdown_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$matches      = [ (object) [ 'id' => 44 ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				[
					'limit'            => false,
					'match_day'        => -1,
					'season'           => '2026',
					'reset_query_args' => true,
				]
			)
			->willReturn( $matches );

		$handler = new Get_League_Match_Dropdown_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Match_Dropdown_Query( league_id: 7, season: '2026', match_id: 44 ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( '', $result->template );
		$this->assertSame( $matches, $result->matches );
		$this->assertSame( 44, $result->match_id );
	}

	public function test_matches_handler_delegates_to_league_query_service(): void {
		$league       = $this->league();
		$league->settings = [ 'match_display' => 'all' ];
		$league->match_day = 4;
		$teams        = [ (object) [ 'id' => 101, 'title' => 'Westcliff 1' ] ];
		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'set_season' )
			->with( $league, false );
		$league_query->expects( $this->once() )
			->method( 'set_match_day' )
			->with( $league, 'current' );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				$this->callback(
					static function ( array $args ): bool {
						return '' === ( $args['final'] ?? null )
							&& '' === ( $args['time'] ?? null )
							&& false === ( $args['home_only'] ?? null );
					}
				)
			)
			->willReturn( [] );
		$league_query->expects( $this->once() )
			->method( 'set_num_matches' )
			->with( $league )
			->willReturn(
				new League_View_State(
					num_matches_per_page: 15,
					current_page_matches: 2,
					num_pages_matches: 3,
					pagination_matches: '<span class="page-numbers current">2</span>',
					current_match_day: 4,
					show_match_day_selection: false,
					match_display: 'all'
				)
			);
		$league_query->expects( $this->once() )
			->method( 'get_league_teams' )
			->with(
				$league,
				[
					'season'  => '2026',
					'orderby' => [ 'title' => 'ASC' ],
				]
			)
			->willReturn( $teams );

		$handler = new Get_League_Matches_Handler(
			$this->league_service( $league ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Matches_Query( league_id: 7, show_match_day_selection: 'false' ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( 'daily', $result->template );
		$this->assertSame( $teams, $result->teams );
		$this->assertSame( '2026', $result->season );
		$this->assertInstanceOf( League_View_State::class, $result->view_state );
		$this->assertSame( 'matches', $result->view_state->tab );
		$this->assertSame( 'daily', $result->view_state->get_template( 'matches' ) );
		$this->assertSame( 15, $result->view_state->num_matches_per_page );
		$this->assertSame( 2, $result->view_state->current_page_matches );
		$this->assertSame( 3, $result->view_state->num_pages_matches );
		$this->assertSame( '<span class="page-numbers current">2</span>', $result->view_state->pagination_matches );
		$this->assertSame( 4, $result->view_state->current_match_day );
		$this->assertFalse( $result->view_state->show_match_day_selection );
		$this->assertSame( 'all', $result->view_state->match_display );
	}

	public function test_archive_handler_populates_view_state(): void {
		$league = $this->league();
		$event  = $this->createStub( Event::class );
		$event->method( 'get_seasons' )->willReturn( [ '2026' ] );

		$league_service = $this->league_service( $league );
		$comp_service   = $this->createStub( Competition_Service::class );
		$comp_service->method( 'get_event_by_id' )->willReturn( $event );

		$event_query = $this->createStub( \Racketmanager\Services\Competition\Event_Query_Service::class );
		$event_query->method( 'get_leagues' )->willReturn( [ $league ] );

		$league_query = $this->createStub( League_Query_Service::class );
		$league_query->method( 'resolve_requested_season' )->willReturn( false );
		$league_query->method( 'resolve_requested_tab' )->willReturn( 1 );
		$league_query->method( 'set_tab' )->willReturn( new League_View_State( archive_tab: 1, is_archive: true ) );

		$handler = new \Racketmanager\Application\League\Queries\Get_League_Archive_Handler(
			$league_service,
			$comp_service,
			$event_query,
			$league_query
		);

		$result = $handler->handle(
            new \Racketmanager\Application\League\Queries\Get_League_Archive_Query(
                league_name: 'Premier Division',
                tab: 'crosstable',
                template: 'compact',
                standings: 'last5',
                crosstable: 'grid',
                matches: 'by_matchday',
                teams: 'list',
                matches_template_type: 'expanded'
            )
        );

		$this->assertFalse( $result->is_error() );
		$this->assertInstanceOf( League_View_State::class, $result->view_state );
		$this->assertSame( 'crosstable', $result->view_state->tab );
		$this->assertSame( 1, $result->view_state->archive_tab );
		$this->assertTrue( $result->view_state->is_archive );
		$this->assertSame( 'last5', $result->view_state->get_template( 'standings' ) );
		$this->assertSame( 'grid', $result->view_state->get_template( 'crosstable' ) );
		$this->assertSame( 'by_matchday', $result->view_state->get_template( 'matches' ) );
		$this->assertSame( 'list', $result->view_state->get_template( 'teams' ) );
		$this->assertSame( 'compact', $result->view_state->get_template( 'archive' ) );
		$this->assertSame( 'expanded', $result->view_state->matches_template_type );
		$this->assertSame( 20, $result->view_state->num_matches_per_page );
		$this->assertSame( 1, $result->view_state->current_page_matches );
		$this->assertSame( 1, $result->view_state->num_pages_matches );
		$this->assertNull( $result->view_state->pagination_matches );
		$this->assertSame( 20, $result->view_state->num_teams_per_page );
		$this->assertSame( 1, $result->view_state->current_page_teams );
		$this->assertSame( 1, $result->view_state->num_pages_teams );
		$this->assertNull( $result->view_state->pagination_teams );
		$this->assertTrue( $result->view_state->show_match_day_selection );
		$this->assertSame( 'current_match_day', $result->view_state->match_display );
	}

	public function test_archive_handler_with_zero_seasons(): void {
		$league = $this->league();
		$event  = $this->createStub( Event::class );
		$event->method( 'get_seasons' )->willReturn( [] );

		$league_service = $this->league_service( $league );
		$comp_service   = $this->createStub( Competition_Service::class );
		$comp_service->method( 'get_event_by_id' )->willReturn( $event );

		$event_query = $this->createStub( \Racketmanager\Services\Competition\Event_Query_Service::class );
		$event_query->method( 'get_leagues' )->willReturn( [ $league ] );

		$league_query = $this->createStub( League_Query_Service::class );
		$league_query->method( 'resolve_requested_season' )->willReturn( false );
		$league_query->method( 'resolve_requested_tab' )->willReturn( 0 );
		$league_query->method( 'set_tab' )->willReturn( new League_View_State( is_archive: true ) );

		$handler = new \Racketmanager\Application\League\Queries\Get_League_Archive_Handler(
			$league_service,
			$comp_service,
			$event_query,
			$league_query
		);

		$result = $handler->handle(
            new \Racketmanager\Application\League\Queries\Get_League_Archive_Query(
                league_name: 'Premier Division'
            )
        );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( [], $result->seasons );
		$this->assertInstanceOf( League_View_State::class, $result->view_state );
		$this->assertSame( 'standings', $result->view_state->tab );
		$this->assertSame( 0, $result->view_state->archive_tab );
		$this->assertTrue( $result->view_state->is_archive );
		$this->assertSame( 20, $result->view_state->num_matches_per_page );
		$this->assertSame( 1, $result->view_state->current_page_matches );
		$this->assertSame( 1, $result->view_state->num_pages_matches );
		$this->assertNull( $result->view_state->pagination_matches );
	}

	public function test_championship_handler_delegates_get_matches_for_finals(): void {
		$league       = $this->league();
		$championship = $this->createStub( Championship::class );
		$championship->method( 'get_finals' )->willReturn(
			[
				'final' => [
					'key'         => 'final',
					'name'        => 'Final',
					'num_matches' => 1,
					'round'       => 1,
				],
			]
		);
		$league->championship = $championship;

		$league_query = $this->createMock( League_Query_Service::class );
		$league_query->expects( $this->once() )
			->method( 'get_matches' )
			->with(
				$league,
				[
					'final'   => 'final',
					'orderby' => [ 'id' => 'ASC' ],
				]
			)
			->willReturn( [] );

		$handler = new Get_League_Championship_Handler(
			$this->league_service( $league ),
			$this->createStub( Fixture_Repository_Interface::class ),
			$this->createStub( Rubber_Repository_Interface::class ),
			$league_query
		);

		$result = $handler->handle( new Get_League_Championship_Query( league_id: 7, season: '2026' ) );

		$this->assertFalse( $result->is_error() );
		$this->assertSame( '', $result->template );
		$this->assertSame( $league, $result->league );
	}
}
