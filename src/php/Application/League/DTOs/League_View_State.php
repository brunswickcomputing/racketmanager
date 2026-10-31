<?php
/**
 * League View State
 *
 * @package Racketmanager
 * @subpackage Application\League\DTOs
 */

declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

use function array_key_exists;
use function ceil;
use function is_array;
use function max;

/**
 * Encapsulates ephemeral UI state, templates dictionary, and pagination metadata for leagues.
 */
final readonly class League_View_State {
	/**
	 * Constructor.
	 *
	 * @param string               $tab Active tab (e.g. 'standings', 'matches', 'teams', 'crosstable', 'players').
	 * @param int                  $archive_tab Active archive tab integer index (e.g. 0, 1, 2, 3).
	 * @param array<string,string> $templates Template file overrides by key.
	 * @param string               $matches_template_type Template presentation type (e.g. 'compact', 'expanded').
	 * @param bool                 $is_archive Whether view is rendered within archive mode.
	 * @param int                  $current_match_day Active match day.
	 * @param int                  $num_matches_per_page Number of matches per page.
	 * @param int                  $current_page_matches Current page number for matches.
	 * @param int                  $num_pages_matches Total number of pages for matches.
	 * @param string|null          $pagination_matches Rendered pagination links for matches.
	 * @param int                  $num_teams_per_page Number of teams per page.
	 * @param int                  $current_page_teams Current page number for teams.
	 * @param int                  $num_pages_teams Total number of pages for teams.
	 * @param string|null          $pagination_teams Rendered pagination links for teams.
	 * @param bool                 $show_match_day_selection Whether to show match day selection UI.
	 * @param string               $match_display Display filter for matches (e.g. 'current_match_day').
	 * @param array<string,mixed>  $extra Additional UI flags and parameters.
	 */
	public function __construct(
		public string $tab = 'standings',
		public int $archive_tab = 0,
		public array $templates = array(),
		public string $matches_template_type = '',
		public bool $is_archive = false,
		public int $current_match_day = 1,
		public int $num_matches_per_page = 20,
		public int $current_page_matches = 1,
		public int $num_pages_matches = 1,
		public ?string $pagination_matches = null,
		public int $num_teams_per_page = 20,
		public int $current_page_teams = 1,
		public int $num_pages_teams = 1,
		public ?string $pagination_teams = null,
		public bool $show_match_day_selection = true,
		public string $match_display = 'current_match_day',
		public array $extra = array(),
	) {}

	/**
	 * Get template override by key with fallback default.
	 *
	 * @param string $key Template identifier key.
	 * @param string $fallback_default Default template if key is not present.
	 * @return string
	 */
	public function get_template( string $key, string $fallback_default = '' ): string {
		return $this->templates[ $key ] ?? $fallback_default;
	}

	/**
	 * Check if a template override key exists.
	 *
	 * @param string $key Template identifier key.
	 * @return bool
	 */
	public function has_template( string $key ): bool {
		return isset( $this->templates[ $key ] );
	}

	/**
	 * Calculate total page count for a given item count and page size.
	 *
	 * @param int $total_items Total number of items.
	 * @param int $items_per_page Items per page.
	 * @return int
	 */
	public static function calculate_num_pages( int $total_items, int $items_per_page ): int {
		if ( $items_per_page <= 0 ) {
			return 1;
		}

		return max( 1, (int) ceil( $total_items / $items_per_page ) );
	}

	/**
	 * Clamp requested page number between 1 and total pages.
	 *
	 * @param int $requested_page Requested page number.
	 * @param int $total_pages Total number of pages.
	 * @return int
	 */
	public static function normalize_current_page( int $requested_page, int $total_pages ): int {
		if ( $requested_page > $total_pages ) {
			$requested_page = max( 1, $total_pages );
		}
		if ( $requested_page <= 0 ) {
			$requested_page = 1;
		}

		return $requested_page;
	}

	/**
	 * Return a clone with updated properties.
	 *
	 * @param array<string,mixed> $properties Updated properties.
	 * @return self
	 */
	public function with( array $properties ): self {
		return new self(
			tab: (string) ( $properties['tab'] ?? $this->tab ),
			archive_tab: (int) ( $properties['archive_tab'] ?? $this->archive_tab ),
			templates: isset( $properties['templates'] ) && is_array( $properties['templates'] ) ? $properties['templates'] : $this->templates,
			matches_template_type: (string) ( $properties['matches_template_type'] ?? $this->matches_template_type ),
			is_archive: (bool) ( $properties['is_archive'] ?? $this->is_archive ),
			current_match_day: (int) ( $properties['current_match_day'] ?? $this->current_match_day ),
			num_matches_per_page: (int) ( $properties['num_matches_per_page'] ?? $this->num_matches_per_page ),
			current_page_matches: (int) ( $properties['current_page_matches'] ?? $this->current_page_matches ),
			num_pages_matches: (int) ( $properties['num_pages_matches'] ?? $this->num_pages_matches ),
			pagination_matches: array_key_exists( 'pagination_matches', $properties ) ? $properties['pagination_matches'] : $this->pagination_matches,
			num_teams_per_page: (int) ( $properties['num_teams_per_page'] ?? $this->num_teams_per_page ),
			current_page_teams: (int) ( $properties['current_page_teams'] ?? $this->current_page_teams ),
			num_pages_teams: (int) ( $properties['num_pages_teams'] ?? $this->num_pages_teams ),
			pagination_teams: array_key_exists( 'pagination_teams', $properties ) ? $properties['pagination_teams'] : $this->pagination_teams,
			show_match_day_selection: (bool) ( $properties['show_match_day_selection'] ?? $this->show_match_day_selection ),
			match_display: (string) ( $properties['match_display'] ?? $this->match_display ),
			extra: isset( $properties['extra'] ) && is_array( $properties['extra'] ) ? $properties['extra'] : $this->extra,
		);
	}

	/**
	 * Return a clone with calculated pagination for matches.
	 *
	 * @param int         $total_matches Total number of matches.
	 * @param int         $requested_page Requested page.
	 * @param string|null $pagination_links Rendered pagination links.
	 * @return self
	 */
	public function with_matches_pagination( int $total_matches, int $requested_page, ?string $pagination_links = null ): self {
		$num_pages    = self::calculate_num_pages( $total_matches, $this->num_matches_per_page );
		$current_page = self::normalize_current_page( $requested_page, $num_pages );

		return $this->with(
			array(
				'num_pages_matches'    => $num_pages,
				'current_page_matches' => $current_page,
				'pagination_matches'   => $pagination_links,
			)
		);
	}

	/**
	 * Return a clone with calculated pagination for teams.
	 *
	 * @param int         $total_teams Total number of teams.
	 * @param int         $requested_page Requested page.
	 * @param string|null $pagination_links Rendered pagination links.
	 * @return self
	 */
	public function with_teams_pagination( int $total_teams, int $requested_page, ?string $pagination_links = null ): self {
		$num_pages    = self::calculate_num_pages( $total_teams, $this->num_teams_per_page );
		$current_page = self::normalize_current_page( $requested_page, $num_pages );

		return $this->with(
			array(
				'num_pages_teams'    => $num_pages,
				'current_page_teams' => $current_page,
				'pagination_teams'   => $pagination_links,
			)
		);
	}
}
