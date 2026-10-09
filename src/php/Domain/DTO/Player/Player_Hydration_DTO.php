<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Player;

/**
 * Data Transfer Object for hydrating a Player entity from raw database row, user object or user meta.
 */
readonly class Player_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string|null $email
	 * @param string|null $display_name
	 * @param string|null $user_registered
	 * @param string|null $firstname
	 * @param string|null $surname
	 * @param string|null $gender
	 * @param string|null $type
	 * @param string|null $btm
	 * @param int|null $year_of_birth
	 * @param int|null $age
	 * @param string|null $contactno
	 * @param string|null $removed_date
	 * @param int|null $removed_user
	 * @param bool|int|string|null $locked
	 * @param string|null $locked_date
	 * @param string|null $locked_user
	 * @param string|null $locked_user_name
	 * @param string|null $system_record
	 * @param array $matches
	 * @param array $statistics
	 * @param string|null $link
	 * @param array|null $opt_ins
	 * @param array $wtn
	 * @param string|null $user_pass
	 * @param string|null $user_nicename
	 * @param string|null $user_url
	 * @param string|null $user_activation_key
	 * @param string|null $user_login
	 * @param string|null $index
	 * @param array $stats
	 * @param int $matches_won
	 * @param int $matches_lost
	 * @param float|null $win_pct
	 * @param int $played
	 * @param array $competitions
	 * @param array $cup
	 * @param array $league
	 * @param array $tournament
	 * @param array $teams
	 * @param object|null $club
	 * @param object|null $team
	 * @param string|null $status
	 * @param string|null $user_status
	 * @param int|null $entry_id
	 * @param array $entry
	 * @param object|null $tournament_entry
	 */
	public function __construct(
		public ?int $id = null,
		public ?string $email = null,
		public ?string $display_name = null,
		public ?string $user_registered = null,
		public ?string $firstname = null,
		public ?string $surname = null,
		public ?string $gender = null,
		public ?string $type = null,
		public ?string $btm = null,
		public ?int $year_of_birth = null,
		public ?int $age = null,
		public ?string $contactno = null,
		public ?string $removed_date = null,
		public ?int $removed_user = null,
		public mixed $locked = null,
		public ?string $locked_date = null,
		public ?string $locked_user = null,
		public ?string $locked_user_name = null,
		public ?string $system_record = null,
		public array $matches = [],
		public array $statistics = [],
		public ?string $link = null,
		public mixed $opt_ins = null,
		public array $wtn = [],
		public ?string $user_pass = null,
		public ?string $user_nicename = null,
		public ?string $user_url = null,
		public ?string $user_activation_key = null,
		public ?string $user_login = null,
		public ?string $index = null,
		public array $stats = [],
		public int $matches_won = 0,
		public int $matches_lost = 0,
		public ?float $win_pct = null,
		public int $played = 0,
		public array $competitions = [],
		public array $cup = [],
		public array $league = [],
		public array $tournament = [],
		public array $teams = [],
		public ?object $club = null,
		public ?object $team = null,
		public ?string $status = null,
		public ?string $user_status = null,
		public ?int $entry_id = null,
		public array $entry = [],
		public ?object $tournament_entry = null
	) {
	}

	/**
	 * Create from a database row, WP_User or generic object.
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$id = isset( $data->ID ) ? (int) $data->ID : ( isset( $data->id ) ? (int) $data->id : null );
		$email = $data->user_email ?? ( $data->email ?? null );
		$display_name = $data->display_name ?? ( $data->fullname ?? ( $data->name ?? null ) );

		$year_of_birth = isset( $data->year_of_birth ) && '' !== $data->year_of_birth ? (int) $data->year_of_birth : null;

		return new self(
			id: $id,
			email: $email ? (string) $email : null,
			display_name: $display_name ? (string) $display_name : null,
			user_registered: isset( $data->user_registered ) ? (string) $data->user_registered : null,
			firstname: isset( $data->firstname ) ? (string) $data->firstname : ( isset( $data->first_name ) ? (string) $data->first_name : null ),
			surname: isset( $data->surname ) ? (string) $data->surname : ( isset( $data->last_name ) ? (string) $data->last_name : null ),
			gender: isset( $data->gender ) ? (string) $data->gender : null,
			type: isset( $data->type ) ? (string) $data->type : null,
			btm: isset( $data->btm ) ? (string) $data->btm : null,
			year_of_birth: $year_of_birth,
			age: isset( $data->age ) ? (int) $data->age : null,
			contactno: isset( $data->contactno ) ? (string) $data->contactno : null,
			removed_date: $data->removed_date ?? null,
			removed_user: isset( $data->removed_user ) ? (int) $data->removed_user : null,
			locked: $data->locked ?? null,
			locked_date: $data->locked_date ?? null,
			locked_user: isset( $data->locked_user ) ? (string) $data->locked_user : null,
			locked_user_name: $data->locked_user_name ?? null,
			system_record: isset( $data->system_record ) ? (string) $data->system_record : null,
			matches: isset( $data->matches ) && is_array( $data->matches ) ? $data->matches : [],
			statistics: isset( $data->statistics ) && is_array( $data->statistics ) ? $data->statistics : [],
			link: $data->link ?? null,
			opt_ins: $data->opt_ins ?? null,
			wtn: isset( $data->wtn ) && is_array( $data->wtn ) ? $data->wtn : [],
			user_pass: isset( $data->user_pass ) ? (string) $data->user_pass : null,
			user_nicename: isset( $data->user_nicename ) ? (string) $data->user_nicename : null,
			user_url: isset( $data->user_url ) ? (string) $data->user_url : null,
			user_activation_key: isset( $data->user_activation_key ) ? (string) $data->user_activation_key : null,
			user_login: isset( $data->user_login ) ? (string) $data->user_login : null,
			index: isset( $data->index ) ? (string) $data->index : null,
			stats: isset( $data->stats ) && is_array( $data->stats ) ? $data->stats : [],
			matches_won: isset( $data->matches_won ) ? (int) $data->matches_won : 0,
			matches_lost: isset( $data->matches_lost ) ? (int) $data->matches_lost : 0,
			win_pct: isset( $data->win_pct ) ? (float) $data->win_pct : null,
			played: isset( $data->played ) ? (int) $data->played : 0,
			competitions: isset( $data->competitions ) && is_array( $data->competitions ) ? $data->competitions : [],
			cup: isset( $data->cup ) && is_array( $data->cup ) ? $data->cup : [],
			league: isset( $data->league ) && is_array( $data->league ) ? $data->league : [],
			tournament: isset( $data->tournament ) && is_array( $data->tournament ) ? $data->tournament : [],
			teams: isset( $data->teams ) && is_array( $data->teams ) ? $data->teams : [],
			club: isset( $data->club ) && is_object( $data->club ) ? $data->club : null,
			team: isset( $data->team ) && is_object( $data->team ) ? $data->team : null,
			status: isset( $data->status ) ? (string) $data->status : null,
			user_status: isset( $data->user_status ) ? (string) $data->user_status : null,
			entry_id: isset( $data->entry_id ) ? (int) $data->entry_id : null,
			entry: isset( $data->entry ) && is_array( $data->entry ) ? $data->entry : [],
			tournament_entry: isset( $data->tournament_entry ) && is_object( $data->tournament_entry ) ? $data->tournament_entry : null
		);
	}
}
