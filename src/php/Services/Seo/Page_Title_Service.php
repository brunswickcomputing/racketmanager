<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Seo;

use Racketmanager\Domain\Shared\Seo_Slug;

/**
 * Service responsible for constructing dynamic page and document titles.
 */
class Page_Title_Service implements Page_Title_Service_Interface {

	/**
	 * Constructor.
	 *
	 * @param string|null $site_name Default site name.
	 */
	public function __construct(
		private ?string $site_name = null
	) {
	}

	/**
	 * Build dynamic page title based on current post slug and query variables.
	 *
	 * @param string      $title     Default or existing title.
	 * @param string|null $site_name Optional site name override.
	 * @return string
	 */
	public function build_title( string $title = '', ?string $site_name = null ): string {
		global $wp;

		$slug      = function_exists( 'get_post_field' ) ? (string) get_post_field( 'post_name' ) : '';
		$site_name = $site_name ?? $this->site_name ?? ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'name' ) : '' );

		$un_seo = static fn( string $val ): string => Seo_Slug::decode( $val );

		$event       = isset( $wp->query_vars['event'] ) && is_string( $wp->query_vars['event'] ) ? ucwords( $un_seo( $wp->query_vars['event'] ) ) : '';
		$season      = isset( $wp->query_vars['season'] ) && is_string( $wp->query_vars['season'] ) ? ucwords( $un_seo( $wp->query_vars['season'] ) ) : '';
		$club        = isset( $wp->query_vars['club_name'] ) && is_string( $wp->query_vars['club_name'] ) ? ucwords( $un_seo( $wp->query_vars['club_name'] ) ) : '';
		$player      = isset( $wp->query_vars['player_id'] ) && is_string( $wp->query_vars['player_id'] ) ? ucwords( $un_seo( $wp->query_vars['player_id'] ) ) : '';
		$competition = isset( $wp->query_vars['competition_name'] ) && is_string( $wp->query_vars['competition_name'] ) ? ucwords( $un_seo( $wp->query_vars['competition_name'] ) ) : '';
		$tournament  = isset( $wp->query_vars['tournament'] ) && is_string( $wp->query_vars['tournament'] ) ? ucwords( $un_seo( $wp->query_vars['tournament'] ) ) : '';
		$type        = isset( $wp->query_vars['competition_type'] ) && is_string( $wp->query_vars['competition_type'] ) ? ucwords( $un_seo( $wp->query_vars['competition_type'] ) ) : '';

		if ( 'player' === $slug && $player ) {
			$title = $player . ' - ' . $site_name;
		}

		if ( 'event' === $slug ) {
			if ( $season ) {
				$event .= ' ' . $season;
			}
			if ( $player ) {
				$title = $player . ' - ' . $event . ' - ' . $site_name;
			} elseif ( $club ) {
				$title = $club . ' - ' . $event . ' - ' . $site_name;
			} else {
				$title = $event . ' - ' . $site_name;
			}
		}

		if ( 'competitions' === $slug ) {
			$type = isset( $wp->query_vars['type'] ) && is_string( $wp->query_vars['type'] ) ? ucwords( $un_seo( $wp->query_vars['type'] ) ) : '';
			if ( $type ) {
				$title = $type . ' ' . __( 'List', 'racketmanager' );
			} else {
				$title = __( 'Competitions', 'racketmanager' );
			}
			if ( $club ) {
				$title .= ' - ' . $club;
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'competition' === $slug ) {
			$competition = isset( $wp->query_vars['competition'] ) && is_string( $wp->query_vars['competition'] ) ? ucwords( $un_seo( $wp->query_vars['competition'] ) ) : '';
			if ( $competition ) {
				$title = $competition . ' ';
			} else {
				$title = __( 'Competition', 'racketmanager' );
			}
			if ( $season ) {
				$title .= ' ' . $season;
			}
			if ( $club ) {
				$title .= ' - ' . $club;
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'latest-results' === $slug ) {
			if ( $type ) {
				/* translators: %s: competition type */
				$title = sprintf( __( 'Latest %s Results', 'racketmanager' ), $type );
			} else {
				$title = __( 'Latest results', 'racketmanager' );
			}
			if ( $competition ) {
				$title .= ' - ' . $competition;
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'cup' === $slug ) {
			$title = $event;
			if ( $season ) {
				$title .= ' - ' . $season;
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'league' === $slug ) {
			$league = isset( $wp->query_vars['league_name'] ) && is_string( $wp->query_vars['league_name'] ) ? ucwords( $un_seo( $wp->query_vars['league_name'] ) ) : '';
			$team   = isset( $wp->query_vars['team'] ) && is_string( $wp->query_vars['team'] ) ? ucwords( $un_seo( $wp->query_vars['team'] ) ) : '';
			if ( $season ) {
				$league .= ' - ' . $season;
			}
			if ( $team ) {
				$title = $team . ' - ' . $league . ' - ' . $site_name;
			} else {
				$title = $league . ' - ' . $site_name;
			}
		}

		if ( 'team' === $slug ) {
			$team = isset( $wp->query_vars['team'] ) && is_string( $wp->query_vars['team'] ) ? ucwords( $un_seo( $wp->query_vars['team'] ) ) : '';
			if ( $team ) {
				$title = $team;
			}
			if ( $competition ) {
				$title .= ' - ' . $competition;
			}
			if ( $event ) {
				$title .= ' - ' . $event;
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'match' === $slug ) {
			$league    = isset( $wp->query_vars['league_name'] ) && is_string( $wp->query_vars['league_name'] ) ? ucwords( $un_seo( $wp->query_vars['league_name'] ) ) : '';
			$team_home = isset( $wp->query_vars['teamHome'] ) && is_string( $wp->query_vars['teamHome'] ) ? ucwords( $un_seo( $wp->query_vars['teamHome'] ) ) : '';
			$team_away = isset( $wp->query_vars['teamAway'] ) && is_string( $wp->query_vars['teamAway'] ) ? ucwords( $un_seo( $wp->query_vars['teamAway'] ) ) : '';
			if ( $season ) {
				$league .= ' - ' . $season;
			}
			if ( $team_home && $team_away ) {
				$title = $team_home . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $team_away . ' - ';
			} else {
				$title .= __( 'Match', 'racketmanager' ) . ' - ';
			}
			if ( $league ) {
				$title .= $league . ' - ';
			}
			if ( $tournament ) {
				$title .= $tournament . ' - ';
			}
			$title .= $site_name;
		}

		if ( 'entry' === $slug ) {
			$title = __( 'Entry Form', 'racketmanager' );
			if ( $competition ) {
				$title .= ' - ' . $competition;
			} elseif ( $tournament ) {
				$title .= ' - ' . $tournament . ' - ' . __( 'Tournament', 'racketmanager' );
				if ( $player ) {
					$title .= ' - ' . $player;
				}
			}
			if ( $season ) {
				$title .= ' - ' . $season;
			}
			if ( $club ) {
				$title .= ' - ' . $club;
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'tournament' === $slug ) {
			$tournament = isset( $wp->query_vars['tournament'] ) && is_string( $wp->query_vars['tournament'] ) ? ucwords( $un_seo( $wp->query_vars['tournament'] ) ) : __( 'Latest', 'racketmanager' );
			$draw       = isset( $wp->query_vars['draw'] ) && is_string( $wp->query_vars['draw'] ) ? ucwords( $un_seo( $wp->query_vars['draw'] ) ) : '';
			$player     = isset( $wp->query_vars['player'] ) && is_string( $wp->query_vars['player'] ) ? ucwords( $un_seo( $wp->query_vars['player'] ) ) : '';
			$tab        = isset( $wp->query_vars['tab'] ) && is_string( $wp->query_vars['tab'] ) ? ucwords( $un_seo( $wp->query_vars['tab'] ) ) : '';
			$title      = '';
			if ( $player ) {
				$title .= $player . ' - ' . __( 'Player', 'racketmanager' ) . ' - ';
			}
			if ( $draw ) {
				$title .= $draw . ' ' . __( 'Draw', 'racketmanager' ) . ' - ';
			}
			if ( $event ) {
				$title .= $event . ' ' . __( 'Event', 'racketmanager' ) . ' - ';
			}
			if ( 'matches' === $tab ) {
				$title .= __( 'Matches', 'racketmanager' ) . ' - ';
			}
			$title .= $tournament . ' - ' . __( 'Tournament', 'racketmanager' );
			$title .= ' - ' . $site_name;
		}

		if ( 'club' === $slug ) {
			if ( $club ) {
				$title = $club;
			} else {
				$title = __( 'Clubs', 'racketmanager' );
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'invoices' === $slug ) {
			$invoice = isset( $wp->query_vars['invoice'] ) && is_string( $wp->query_vars['invoice'] ) ? ucwords( $un_seo( $wp->query_vars['invoice'] ) ) : null;
			if ( $invoice ) {
				$title = __( 'Invoice', 'racketmanager' ) . ' - ' . $invoice;
			} else {
				$title = __( 'Invoices', 'racketmanager' );
			}
			if ( $club ) {
				$title .= ' - ' . $club;
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'roles' === $slug ) {
			$title = __( 'Roles', 'racketmanager' );
			if ( $club ) {
				$title .= ' - ' . $club;
			}
			$title .= ' - ' . $site_name;
		}

		if ( 'players' === $slug ) {
			if ( $player ) {
				$title = $player;
			} else {
				$title = __( 'Players', 'racketmanager' );
			}
			if ( $club ) {
				$title .= ' - ' . $club;
			}
			$title .= ' - ' . $site_name;
		}

		return $title;
	}
}
