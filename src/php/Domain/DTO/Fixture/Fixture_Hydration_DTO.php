<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Fixture;

/**
 * Data Transfer Object for hydrating a Fixture entity from raw database row or array.
 */
readonly class Fixture_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string|null $fixture_title
	 * @param string|null $link
	 * @param string|null $group
	 * @param string|null $date
	 * @param string|null $date_original
	 * @param string|null $home_team
	 * @param string|null $away_team
	 * @param int|null $match_day
	 * @param string|null $location
	 * @param string|null $host
	 * @param int|null $league_id
	 * @param string|null $season
	 * @param string|null $home_points
	 * @param string|null $away_points
	 * @param int|null $winner_id
	 * @param int|null $loser_id
	 * @param int|null $status
	 * @param int|null $linked_fixture
	 * @param int|null $leg
	 * @param int|null $winner_id_tie
	 * @param int|null $loser_id_tie
	 * @param float|null $home_points_tie
	 * @param float|null $away_points_tie
	 * @param int|null $post_id
	 * @param string|null $final
	 * @param array|null $custom
	 * @param int|null $updated_user
	 * @param string|null $updated
	 * @param string|null $date_result_entered
	 * @param string|null $confirmed
	 * @param int|null $home_captain
	 * @param int|null $away_captain
	 * @param array|null $comments
	 * @param string|null $updated_by
	 * @param string|null $start_time
	 * @param array|null $rubbers
	 */
	public function __construct(
		public ?int $id = null,
		public ?string $fixture_title = null,
		public ?string $link = null,
		public ?string $group = null,
		public ?string $date = null,
		public ?string $date_original = null,
		public ?string $home_team = null,
		public ?string $away_team = null,
		public ?int $match_day = null,
		public ?string $location = null,
		public ?string $host = null,
		public ?int $league_id = null,
		public ?string $season = null,
		public ?string $home_points = null,
		public ?string $away_points = null,
		public ?int $winner_id = null,
		public ?int $loser_id = null,
		public ?int $status = null,
		public ?int $linked_fixture = null,
		public ?int $leg = null,
		public ?int $winner_id_tie = null,
		public ?int $loser_id_tie = null,
		public ?float $home_points_tie = null,
		public ?float $away_points_tie = null,
		public ?int $post_id = null,
		public ?string $final = null,
		public ?array $custom = null,
		public ?int $updated_user = null,
		public ?string $updated = null,
		public ?string $date_result_entered = null,
		public ?string $confirmed = null,
		public ?int $home_captain = null,
		public ?int $away_captain = null,
		public ?array $comments = null,
		public ?string $updated_by = null,
		public ?string $start_time = null,
		public ?array $rubbers = null
	) {
	}

	/**
	 * Create from a database row or generic object.
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$custom = $data->custom ?? null;
		if ( is_string( $custom ) && function_exists( 'maybe_unserialize' ) ) {
			$custom = maybe_unserialize( $custom );
		}
		if ( ! is_array( $custom ) ) {
			$custom = is_null( $custom ) ? null : (array) $custom;
		}

		$comments = $data->comments ?? null;
		if ( is_string( $comments ) && function_exists( 'maybe_unserialize' ) ) {
			$comments = maybe_unserialize( $comments );
		}
		if ( ! is_array( $comments ) && ! is_null( $comments ) ) {
			$comments = [ 'legacy' => $comments ];
		}

		return new self(
			id: isset( $data->id ) ? (int) $data->id : null,
			fixture_title: isset( $data->fixture_title ) ? (string) $data->fixture_title : ( $data->title ?? null ),
			link: $data->link ?? null,
			group: $data->group ?? null,
			date: $data->date ?? null,
			date_original: $data->date_original ?? null,
			home_team: isset( $data->home_team ) ? (string) $data->home_team : null,
			away_team: isset( $data->away_team ) ? (string) $data->away_team : null,
			match_day: isset( $data->match_day ) ? (int) $data->match_day : null,
			location: $data->location ?? null,
			host: $data->host ?? null,
			league_id: isset( $data->league_id ) ? (int) $data->league_id : null,
			season: isset( $data->season ) ? (string) $data->season : null,
			home_points: isset( $data->home_points ) ? (string) $data->home_points : null,
			away_points: isset( $data->away_points ) ? (string) $data->away_points : null,
			winner_id: isset( $data->winner_id ) ? (int) $data->winner_id : null,
			loser_id: isset( $data->loser_id ) ? (int) $data->loser_id : null,
			status: isset( $data->status ) ? (int) $data->status : null,
			linked_fixture: isset( $data->linked_match ) ? (int) $data->linked_match : ( isset( $data->linked_fixture ) ? (int) $data->linked_fixture : null ),
			leg: isset( $data->leg ) ? (int) $data->leg : null,
			winner_id_tie: isset( $data->winner_id_tie ) ? (int) $data->winner_id_tie : null,
			loser_id_tie: isset( $data->loser_id_tie ) ? (int) $data->loser_id_tie : null,
			home_points_tie: isset( $data->home_points_tie ) ? (float) $data->home_points_tie : null,
			away_points_tie: isset( $data->away_points_tie ) ? (float) $data->away_points_tie : null,
			post_id: isset( $data->post_id ) ? (int) $data->post_id : null,
			final: $data->final ?? null,
			custom: $custom,
			updated_user: isset( $data->updated_user ) ? (int) $data->updated_user : null,
			updated: $data->updated ?? null,
			date_result_entered: $data->date_result_entered ?? null,
			confirmed: isset( $data->confirmed ) ? (string) $data->confirmed : null,
			home_captain: isset( $data->home_captain ) ? (int) $data->home_captain : null,
			away_captain: isset( $data->away_captain ) ? (int) $data->away_captain : null,
			comments: $comments,
			updated_by: $data->updated_by ?? null,
			start_time: $data->start_time ?? null,
			rubbers: isset( $data->rubbers ) && is_array( $data->rubbers ) ? $data->rubbers : null
		);
	}
}
