<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Seo;

use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Domain\Shared\Seo_Slug;

/**
 * Service responsible for constructing dynamic page and document titles.
 */
class Page_Title_Service implements Page_Title_Service_Interface {

	/**
	 * Constructor.
	 *
	 * @param string|null              $site_name  Default site name.
	 * @param Query_Vars_Interface|null $query_vars Query vars port.
	 */
	public function __construct(
		private ?string $site_name = null,
		private ?Query_Vars_Interface $query_vars = null,
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
		$slug      = function_exists( 'get_post_field' ) ? (string) get_post_field( 'post_name' ) : '';
		$site_name = $site_name ?? $this->site_name ?? ( function_exists( 'get_bloginfo' ) ? (string) get_bloginfo( 'name' ) : '' );

		$un_seo = static fn( string $val ): string => Seo_Slug::decode( $val );

		$get_var = function( string $key ) use ( $un_seo ): string {
			$val = $this->query_vars?->get( $key );
			return is_string( $val ) ? ucwords( $un_seo( $val ) ) : '';
		};

		$event       = $get_var( 'event' );
		$season      = $get_var( 'season' );
		$club        = $get_var( 'club_name' );
		$player      = $get_var( 'player_id' );
		$competition = $get_var( 'competition_name' );
		$tournament  = $get_var( 'tournament' );
		$type        = $get_var( 'competition_type' );

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
			$type = $get_var( 'type' );
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
			$competition = $get_var( 'competition' );
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
			$league = $get_var( 'league_name' );
			$team   = $get_var( 'team' );
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
			$team = $get_var( 'team' );
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
			$league    = $get_var( 'league_name' );
			$team_home = $get_var( 'teamHome' );
			$team_away = $get_var( 'teamAway' );
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
			$tournament = '' !== $tournament ? $tournament : __( 'Latest', 'racketmanager' );
			$draw       = $get_var( 'draw' );
			$player     = $get_var( 'player' );
			$tab        = $get_var( 'tab' );
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
			$invoice_raw = $this->query_vars?->get( 'invoice' );
			$invoice     = is_string( $invoice_raw ) ? ucwords( $un_seo( $invoice_raw ) ) : null;
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
