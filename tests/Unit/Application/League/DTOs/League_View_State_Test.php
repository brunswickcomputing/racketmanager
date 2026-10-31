<?php
/**
 * League View State Test
 *
 * @package Racketmanager
 * @subpackage Tests\Unit\Application\League\DTOs
 */

declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\League\DTOs;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\League\DTOs\League_View_State;

/**
 * Tests for League_View_State value object.
 */
final class League_View_State_Test extends TestCase {
	/**
	 * Test default values.
	 */
	public function test_default_values(): void {
		$state = new League_View_State();

		$this->assertSame( 'standings', $state->tab );
		$this->assertSame( 0, $state->archive_tab );
		$this->assertSame( array(), $state->templates );
		$this->assertSame( '', $state->matches_template_type );
		$this->assertFalse( $state->is_archive );
		$this->assertSame( 1, $state->current_match_day );
		$this->assertSame( 20, $state->num_matches_per_page );
		$this->assertSame( 1, $state->current_page_matches );
		$this->assertSame( 1, $state->num_pages_matches );
		$this->assertNull( $state->pagination_matches );
		$this->assertSame( 20, $state->num_teams_per_page );
		$this->assertSame( 1, $state->current_page_teams );
		$this->assertSame( 1, $state->num_pages_teams );
		$this->assertNull( $state->pagination_teams );
		$this->assertTrue( $state->show_match_day_selection );
		$this->assertSame( 'current_match_day', $state->match_display );
		$this->assertSame( array(), $state->extra );
	}

	/**
	 * Test explicit initialization.
	 */
	public function test_explicit_initialization(): void {
		$templates = array(
			'standings'  => 'last5',
			'matches'    => 'by_matchday',
			'crosstable' => 'grid',
		);
		$extra     = array( 'custom_flag' => true );

		$state = new League_View_State(
			tab: 'matches',
			archive_tab: 2,
			templates: $templates,
			matches_template_type: 'compact',
			is_archive: true,
			current_match_day: 4,
			num_matches_per_page: 30,
			current_page_matches: 2,
			num_pages_matches: 5,
			pagination_matches: '<div class="pagination">1 2 3</div>',
			num_teams_per_page: 15,
			current_page_teams: 3,
			num_pages_teams: 4,
			pagination_teams: '<div class="teams-page">1 2 3</div>',
			show_match_day_selection: false,
			match_display: 'all',
			extra: $extra,
		);

		$this->assertSame( 'matches', $state->tab );
		$this->assertSame( 2, $state->archive_tab );
		$this->assertSame( $templates, $state->templates );
		$this->assertSame( 'compact', $state->matches_template_type );
		$this->assertTrue( $state->is_archive );
		$this->assertSame( 4, $state->current_match_day );
		$this->assertSame( 30, $state->num_matches_per_page );
		$this->assertSame( 2, $state->current_page_matches );
		$this->assertSame( 5, $state->num_pages_matches );
		$this->assertSame( '<div class="pagination">1 2 3</div>', $state->pagination_matches );
		$this->assertSame( 15, $state->num_teams_per_page );
		$this->assertSame( 3, $state->current_page_teams );
		$this->assertSame( 4, $state->num_pages_teams );
		$this->assertSame( '<div class="teams-page">1 2 3</div>', $state->pagination_teams );
		$this->assertFalse( $state->show_match_day_selection );
		$this->assertSame( 'all', $state->match_display );
		$this->assertSame( $extra, $state->extra );
	}

	/**
	 * Test get_template and has_template.
	 */
	public function test_get_and_has_template(): void {
		$state = new League_View_State(
			templates: array(
				'standings' => 'compact-standings',
				'matches'   => 'daily',
			)
		);

		$this->assertTrue( $state->has_template( 'standings' ) );
		$this->assertTrue( $state->has_template( 'matches' ) );
		$this->assertFalse( $state->has_template( 'teams' ) );

		$this->assertSame( 'compact-standings', $state->get_template( 'standings' ) );
		$this->assertSame( 'daily', $state->get_template( 'matches', 'fallback' ) );
		$this->assertSame( 'fallback', $state->get_template( 'teams', 'fallback' ) );
		$this->assertSame( '', $state->get_template( 'teams' ) );
	}

	/**
	 * Test pagination calculation helper.
	 */
	public function test_calculate_num_pages(): void {
		$this->assertSame( 1, League_View_State::calculate_num_pages( 0, 20 ) );
		$this->assertSame( 1, League_View_State::calculate_num_pages( 10, 20 ) );
		$this->assertSame( 1, League_View_State::calculate_num_pages( 20, 20 ) );
		$this->assertSame( 2, League_View_State::calculate_num_pages( 21, 20 ) );
		$this->assertSame( 5, League_View_State::calculate_num_pages( 100, 20 ) );
		$this->assertSame( 1, League_View_State::calculate_num_pages( 100, 0 ) );
		$this->assertSame( 1, League_View_State::calculate_num_pages( 100, -5 ) );
	}

	/**
	 * Test current page normalization helper.
	 */
	public function test_normalize_current_page(): void {
		$this->assertSame( 1, League_View_State::normalize_current_page( 1, 5 ) );
		$this->assertSame( 3, League_View_State::normalize_current_page( 3, 5 ) );
		$this->assertSame( 5, League_View_State::normalize_current_page( 5, 5 ) );
		$this->assertSame( 5, League_View_State::normalize_current_page( 10, 5 ) );
		$this->assertSame( 1, League_View_State::normalize_current_page( 0, 5 ) );
		$this->assertSame( 1, League_View_State::normalize_current_page( -2, 5 ) );
	}

	/**
	 * Test with() method for immutable copying.
	 */
	public function test_with_creates_new_instance_with_updated_properties(): void {
		$initial = new League_View_State(
			tab: 'standings',
			current_match_day: 1,
			templates: array( 'standings' => 'default' )
		);

		$modified = $initial->with(
			array(
				'tab'               => 'matches',
				'current_match_day' => 3,
				'is_archive'        => true,
			)
		);

		$this->assertNotSame( $initial, $modified );
		$this->assertSame( 'standings', $initial->tab );
		$this->assertSame( 1, $initial->current_match_day );
		$this->assertFalse( $initial->is_archive );

		$this->assertSame( 'matches', $modified->tab );
		$this->assertSame( 3, $modified->current_match_day );
		$this->assertTrue( $modified->is_archive );
		$this->assertSame( array( 'standings' => 'default' ), $modified->templates );
	}

	/**
	 * Test with_matches_pagination helper.
	 */
	public function test_with_matches_pagination(): void {
		$initial = new League_View_State( num_matches_per_page: 10 );

		$updated = $initial->with_matches_pagination(
			total_matches: 45,
			requested_page: 3,
			pagination_links: '<nav>pages</nav>'
		);

		$this->assertSame( 5, $updated->num_pages_matches );
		$this->assertSame( 3, $updated->current_page_matches );
		$this->assertSame( '<nav>pages</nav>', $updated->pagination_matches );

		// Clamping test: requested page out of range
		$clamped = $initial->with_matches_pagination(
			total_matches: 45,
			requested_page: 10,
			pagination_links: null
		);
		$this->assertSame( 5, $clamped->num_pages_matches );
		$this->assertSame( 5, $clamped->current_page_matches );
	}

	/**
	 * Test with_teams_pagination helper.
	 */
	public function test_with_teams_pagination(): void {
		$initial = new League_View_State( num_teams_per_page: 8 );

		$updated = $initial->with_teams_pagination(
			total_teams: 20,
			requested_page: 2,
			pagination_links: '<nav>team-pages</nav>'
		);

		$this->assertSame( 3, $updated->num_pages_teams );
		$this->assertSame( 2, $updated->current_page_teams );
		$this->assertSame( '<nav>team-pages</nav>', $updated->pagination_teams );

		// Clamping test: negative requested page
		$clamped = $initial->with_teams_pagination(
			total_teams: 20,
			requested_page: -1,
			pagination_links: null
		);
		$this->assertSame( 3, $clamped->num_pages_teams );
		$this->assertSame( 1, $clamped->current_page_teams );
	}
}
