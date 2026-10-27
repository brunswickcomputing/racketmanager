<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Presentation\Presenters\Tournament_Presenter;
use Racketmanager\Tests\Helpers\Fixture_Test_Factory;

final class Tournament_Presenter_Test extends TestCase {
	public function test_formats_dates_with_configured_format(): void {
		$tournament = Tournament::from_object( (object) [ 'name' => 'Cup', 'date_end' => '2026-08-20', 'date_closing' => '2026-08-01' ] );

		$dates = ( new Tournament_Presenter( 'd/m/Y' ) )->present_dates( $tournament );

		$this->assertSame( '20/08/2026', $dates->date_display );
		$this->assertSame( '01/08/2026', $dates->date_closing_display );
	}

	public function test_uses_fallbacks_for_missing_dates(): void {
		$tournament = Tournament::from_object( (object) [ 'name' => 'Cup', 'date_end' => '0000-00-00', 'date_start' => '0000-00-00' ] );

		$dates = ( new Tournament_Presenter() )->present_dates( $tournament );

		$this->assertSame( 'TBC', $dates->date_display );
		$this->assertSame( 'N/A', $dates->date_closing_display );
		$this->assertSame( 'N/A', $dates->date_withdrawal_display );
		$this->assertSame( 'N/A', $dates->date_open_display );
		$this->assertSame( 'N/A', $dates->date_start_display );
	}

	public function test_get_link_builds_relative_url_from_tournament_name(): void {
		$tournament = Tournament::from_object( (object) [ 'name' => 'Leigh Summer Open 2026' ] );
		$presenter  = new Tournament_Presenter();

		$this->assertSame( '/tournament/leigh-summer-open-2026/', $presenter->get_link( $tournament ) );
	}

	public function test_get_entry_link_formats_url_handling_trailing_slashes_in_site_url(): void {
		$tournament = Tournament::from_object( (object) [ 'name' => 'Winter Closed 2026' ] );
		$presenter  = new Tournament_Presenter();

		$this->assertSame( 'https://tennis.org/entry-form/winter-closed-2026-tournament/', $presenter->get_entry_link( $tournament, 'https://tennis.org' ) );
		$this->assertSame( 'https://tennis.org/entry-form/winter-closed-2026-tournament/', $presenter->get_entry_link( $tournament, 'https://tennis.org/' ) );
		$this->assertSame( '/entry-form/winter-closed-2026-tournament/', $presenter->get_entry_link( $tournament, '' ) );
		$this->assertSame(
			'https://tennis.org/entry-form/winter-closed-2026-tournament/payment-complete/',
			$presenter->get_payment_complete_url( $tournament, 'https://tennis.org/' )
		);
	}

	public function test_present_dates_integrates_with_date_range_windows_and_boundaries(): void {
		$tournament = Tournament::from_object( (object) [
			'name'            => 'Championships 2026',
			'date_start'      => '2026-07-01',
			'date_end'        => '2026-07-15',
			'date_open'       => '2026-05-01',
			'date_closing'    => '2026-06-20',
			'date_withdrawal' => '2026-06-25',
		] );

		$presenter = new Tournament_Presenter( 'd/m/Y' );
		$dates     = $presenter->present_dates( $tournament );

		$this->assertSame( '15/07/2026', $dates->date_display );
		$this->assertSame( '01/07/2026', $dates->date_start_display );
		$this->assertSame( '01/05/2026', $dates->date_open_display );
		$this->assertSame( '20/06/2026', $dates->date_closing_display );
		$this->assertSame( '25/06/2026', $dates->date_withdrawal_display );

		$header = $presenter->present_header( $tournament );
		$this->assertTrue( $header->has_date_range );
		$this->assertSame( '01/07/2026', $header->date_start_display );
		$this->assertSame( '15/07/2026', $header->date_end_display );
	}

	public function test_present_header_and_dates_with_partial_and_invalid_date_boundaries(): void {
		$presenter = new Tournament_Presenter( 'd/m/Y' );

		// 1. Partial dates: missing date_start
		$partial_tournament = Tournament::from_object( (object) [
			'name'       => 'Partial Tournament',
			'date_start' => null,
			'date_end'   => '2026-07-15',
		] );
		$dates   = $presenter->present_dates( $partial_tournament );
		$header  = $presenter->present_header( $partial_tournament );
		$this->assertSame( '15/07/2026', $dates->date_display );
		$this->assertSame( 'N/A', $dates->date_start_display );
		$this->assertFalse( $header->has_date_range );
		$this->assertNull( $header->date_start_display );
		$this->assertNull( $header->date_end_display );

		// 2. Reversed dates: start > end
		$reversed_tournament = Tournament::from_object( (object) [
			'name'       => 'Reversed Tournament',
			'date_start' => '2026-08-20',
			'date_end'   => '2026-08-01',
		] );
		$rev_dates  = $presenter->present_dates( $reversed_tournament );
		$rev_header = $presenter->present_header( $reversed_tournament );
		$this->assertSame( '01/08/2026', $rev_dates->date_display );
		$this->assertSame( '20/08/2026', $rev_dates->date_start_display );
		$this->assertTrue( $rev_header->has_date_range );
		$this->assertSame( '20/08/2026', $rev_header->date_start_display );
		$this->assertSame( '01/08/2026', $rev_header->date_end_display );

		// 3. Zero / empty dates
		$zero_tournament = Tournament::from_object( (object) [
			'name'       => 'Zero Tournament',
			'date_start' => '0000-00-00',
			'date_end'   => '',
		] );
		$zero_dates  = $presenter->present_dates( $zero_tournament );
		$zero_header = $presenter->present_header( $zero_tournament );
		$this->assertSame( 'TBC', $zero_dates->date_display );
		$this->assertSame( 'N/A', $zero_dates->date_start_display );
		$this->assertFalse( $zero_header->has_date_range );

		// 4. Datetime formatted inputs
		$datetime_tournament = (object) [
			'name'       => 'Datetime Tournament',
			'date_start' => '2026-07-01 10:00:00',
			'date_end'   => '2026-07-15 18:00:00',
			'is_open'    => false,
		];
		$dt_header = $presenter->present_header( $datetime_tournament );
		$this->assertTrue( $dt_header->has_date_range );
		$this->assertSame( '01/07/2026', $dt_header->date_start_display );
		$this->assertSame( '15/07/2026', $dt_header->date_end_display );
	}

	public function test_present_match_card_sets_with_home_and_away_winners_and_tiebreaks(): void {
		$fixture = Fixture_Test_Factory::create(
			id: 501,
			home_team: '10',
			away_team: '20',
			winner_id: 10,
			loser_id: 20,
			custom: [
				'sets' => [
					new Set_Score( 7, 6, 7, 5 ),
					new Set_Score( 3, 6 ),
					new Set_Score( 6, 7, 5, 7 ),
				],
			]
		);

		$league = $this->createStub( League::class );
		$league->title = 'Men Singles';
		$event = $this->createStub( Event::class );
		$event->name = 'Singles Event';
		$league->event = $event;

		$tournament = $this->createStub( Tournament::class );
		$tournament->id = 5;
		$tournament->name = 'Summer Championships';

		$p1 = $this->createStub( Player::class );
		$p1->method( 'get_id' )->willReturn( 101 );
		$p1->method( 'get_fullname' )->willReturn( 'Roger Federer' );
		$p2 = $this->createStub( Player::class );
		$p2->method( 'get_id' )->willReturn( 102 );
		$p2->method( 'get_fullname' )->willReturn( 'Rafael Nadal' );

		$t1 = $this->createStub( Team::class );
		$t1->method( 'get_id' )->willReturn( 10 );
		$t1->method( 'get_name' )->willReturn( 'Federer' );
		$t1->players = [ $p1 ];
		$t2 = $this->createStub( Team::class );
		$t2->method( 'get_id' )->willReturn( 20 );
		$t2->method( 'get_name' )->willReturn( 'Nadal' );
		$t2->players = [ $p2 ];

		$detail = new Fixture_Details_DTO(
			fixture: $fixture,
			league: $league,
			event: $event,
			competition: $this->createStub( Competition::class ),
			home_team: new Team_Details_DTO( $t1 ),
			away_team: new Team_Details_DTO( $t2 )
		);

		$presenter = new Tournament_Presenter( 'd/m/Y' );
		$card      = $presenter->present_match_card( $detail, $tournament );

		$this->assertCount( 3, $card->sets );

		// Set 1: Home won 7-6 (7-5 tiebreak)
		$this->assertSame( '7', $card->sets[0]->player1_games );
		$this->assertSame( '6', $card->sets[0]->player2_games );
		$this->assertSame( ' winner', $card->sets[0]->player1_winner_class );
		$this->assertSame( '', $card->sets[0]->player2_winner_class );
		$this->assertTrue( $card->sets[0]->show_p1_tiebreak );
		$this->assertFalse( $card->sets[0]->show_p2_tiebreak );
		$this->assertSame( '7', $card->sets[0]->tiebreak );

		// Set 2: Away won 3-6 (no tiebreak)
		$this->assertSame( '3', $card->sets[1]->player1_games );
		$this->assertSame( '6', $card->sets[1]->player2_games );
		$this->assertSame( '', $card->sets[1]->player1_winner_class );
		$this->assertSame( ' winner', $card->sets[1]->player2_winner_class );
		$this->assertFalse( $card->sets[1]->show_p1_tiebreak );
		$this->assertFalse( $card->sets[1]->show_p2_tiebreak );
		$this->assertSame( '', $card->sets[1]->tiebreak );

		// Set 3: Away won 6-7 (5-7 tiebreak)
		$this->assertSame( '6', $card->sets[2]->player1_games );
		$this->assertSame( '7', $card->sets[2]->player2_games );
		$this->assertSame( '', $card->sets[2]->player1_winner_class );
		$this->assertSame( ' winner', $card->sets[2]->player2_winner_class );
		$this->assertFalse( $card->sets[2]->show_p1_tiebreak );
		$this->assertTrue( $card->sets[2]->show_p2_tiebreak );
		$this->assertSame( '5', $card->sets[2]->tiebreak );
	}
}
