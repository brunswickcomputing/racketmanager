<?php
/**
* Team API: Team class
*
* @author Kolja Schleich
* @package RacketManager
* @subpackage Team
*/

/**
* Class to implement the Team object
*
*/
final class Team {

	/**
	* retrieve team instance
	*
	* @param int $team_id
	*/
	public static function get_instance($team_id) {
		global $wpdb;
		$team_id = (int) $team_id;
		if ( ! $team_id ) {
			return false;
		}
		if ( $team_id == -1) {
			$team = (object)array( 'id' => $team_id, 'title' => __( 'Bye', 'racketmanager' ) );
			return $team;
		}

		$team = wp_cache_get( $team_id, 'teams' );

		if ( ! $team ) {
			$team = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `title`, `stadium`, `home`, `roster`, `profile`, `status`, `affiliatedclub`, `type` FROM {$wpdb->racketmanager_teams} WHERE `id` = '%d' LIMIT 1", $team_id ) );

			if ( !$team ) return false;

			$team = new Team( $team );

			wp_cache_set( $team->id, $team, 'teams' );
		}

		return $team;
	}

	/**
	* Constructor
	*
	* @param object $team Team object.
	*/
	public function __construct( $team = null ) {
		global $racketmanager;

		if ( !is_null($team) ) {
			foreach ( get_object_vars( $team ) as $key => $value ) {
				$this->$key = $value;
			}

			$this->title = htmlspecialchars(stripslashes($this->title), ENT_QUOTES);
			$this->stadium = stripslashes($this->stadium);

			$this->roster = intval($this->roster);
			$this->profile = intval($this->profile);

			$this->affiliatedclubname = get_club( $this->affiliatedclub )->name;
			if ( $this->status == 'P' && $this->roster != null ) {
				$i = 1;
				foreach ($this->roster AS $player) {
					$teamplayer = $this->getRosterEntry($player);
					$this->player[$i] = $teamplayer->fullname;
					$this->playerId[$i] = $player;
					$i++;
				};
			}
		}
	}

  	/**
	* update title
	*
	* @param string $title
	*/
	public function updateTitle( $title ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_teams} SET `title` = '%s' WHERE `id` = %d", $title, $this->id ) );
	}

}

/**
* get Team object
*
* @param int|Team|null Team ID or team object. Defaults to global $team
* @return Team|null
*/
function get_team( $team = null ) {
	if ( empty( $team ) && isset( $GLOBALS['team'] ) )
	$team = $GLOBALS['team'];

	if ( $team instanceof Team ) {
		$_team = $team;
	} elseif ( is_object( $team ) ) {
		$_team = new Team( $team );
	} else {
		$_team = Team::get_instance( $team );
	}

	if ( ! $_team )
	return null;

	return $_team;
}
?>
