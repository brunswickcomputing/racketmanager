<?php
/**
 * RacketManager_Shortcodes API: RacketManagerShortcodes class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodes
 */

namespace Racketmanager;

/**
 * Class to implement shortcode functions
 */
class RacketManager_Shortcodes {
	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		add_shortcode( 'dailymatches', array( &$this, 'show_daily_matches' ) );
		add_shortcode( 'latest_results', array( &$this, 'show_latest_results' ) );
		add_shortcode( 'players', array( &$this, 'show_players' ) );
		add_shortcode( 'player', array( &$this, 'show_player' ) );
		add_shortcode( 'favourites', array( &$this, 'show_favourites' ) );
		add_shortcode( 'invoice', array( &$this, 'show_invoice' ) );
		add_shortcode( 'memberships', array( &$this, 'show_memberships' ) );
		add_shortcode( 'search-players', array( &$this, 'show_player_search' ) );
		add_shortcode( 'team-order', array( &$this, 'show_team_order' ) );
        add_shortcode( 'show-alert', array( &$this, 'show_alert' ) );
	}
	/**
	 * Display Daily Matches
	 *
	 *    [dailymatches league_id="1" competition_id="1" match_date="dd/mm/yyyy" template="name"]
	 *
	 * - league_id is the ID of league (optional)
	 * - competition_id is the ID of the competition (optional)
	 * - season: display specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_daily_matches( array $atts ): string {
		global $racketmanager, $wp;
		wp_verify_nonce( 'matches-daily' );
		$args             = shortcode_atts(
			array(
				'competition_type' => 'league',
				'template'         => 'daily',
				'match_date'       => false,
			),
			$atts
		);
		$competition_type = $args['competition_type'];
		$template         = $args['template'];
		$match_date       = $args['match_date'];
		if ( ! $match_date ) {
			$match_date = get_query_var( 'match_date' );
			if ( '' === $match_date && isset( $_GET['match_date'] ) ) {
				$match_date = sanitize_text_field( wp_unslash( $_GET['match_date'] ) );
			}
		}
		if ( '' === $match_date ) {
			$match_date = gmdate( 'Y-m-d' );
		}
		if ( isset( $wp->query_vars['competition_type'] ) ) {
			$competition_type = un_seo_url( get_query_var( 'competition_type' ) );
		}
		$matches      = $racketmanager->get_matches(
			array(
				'match_date'       => $match_date,
				'competition_type' => $competition_type,
			)
		);
		$matches_list = array();
		foreach ( $matches as $match ) {
			$key = $match->league->title;
			if ( false === array_key_exists( $key, $matches_list ) ) {
				$matches_list[ $key ] = array();
			}
			$matches_list[ $key ][] = $match;
		}

		$filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches-daily';

		return $this->load_template(
			$filename,
			array(
				'matches_list' => $matches_list,
				'match_date'   => $match_date,
			)
		);
	}
	/**
	 * Display Latest Match results
	 *
	 *    [latest_results league_id="1" competition_id="1" match_date="dd/mm/yyyy" template="name"]
	 *
	 * - league_id is the ID of league (optional)
	 * - competition_id is the ID of the competition (optional)
	 * - season: display specific season (optional)
	 * - template is the template used for displaying. Replace name appropriately. Templates must be named "matches-template.php" (optional)
	 *
	 * @param array $atts shortcode attributes.
	 * @return string
	 */
	public function show_latest_results( array $atts ): string {
		global $racketmanager, $wp;

		$args             = shortcode_atts(
			array(
				'competition_type' => 'league',
				'template'         => 'results',
				'days'             => 7,
				'club'             => '',
				'competition_id'   => '',
				'header_level'     => 1,
				'age_group'        => false,
			),
			$atts
		);
		$competition_type = $args['competition_type'];
		$template         = $args['template'];
		$days             = $args['days'];
		$club_id          = $args['club'];
		$competition_id   = $args['competition_id'];
		$header_level     = $args['header_level'];
		$age_group        = $args['age_group'];
		if ( isset( $wp->query_vars['club_name'] ) ) {
			$club_name = str_replace( '-', ' ', get_query_var( 'club_name' ) );
			$club      = get_club( $club_name, 'shortcode' );
			$club_id   = $club->id;
		}
		if ( isset( $wp->query_vars['days'] ) ) {
			$days = str_replace( '-', ' ', get_query_var( 'days' ) );
		}
		if ( isset( $wp->query_vars['competition_type'] ) ) {
			$competition_type = un_seo_url( get_query_var( 'competition_type' ) );
		}
		if ( isset( $wp->query_vars['competition_name'] ) ) {
			$competition_name = un_seo_url( get_query_var( 'competition_name' ) );
			$competition      = get_competition( $competition_name, 'name' );
			if ( $competition ) {
				$competition_id = $competition->id;
			}
		}
		if ( isset( $wp->query_vars['age_group'] ) ) {
			$age_group = get_query_var( 'age_group' );
		}
		$time         = 'latest';
		$matches      = $racketmanager->get_matches(
			array(
				'days'             => $days,
				'competition_type' => $competition_type,
				'time'             => $time,
				'history'          => $days,
				'club'             => $club_id,
				'competition_id'   => $competition_id,
				'age_group'        => $age_group,
			)
		);
		$matches_list = array();
		foreach ( $matches as $match ) {
			$key = $match->league->title;
			if ( false === array_key_exists( $key, $matches_list ) ) {
				$matches_list[ $key ] = array();
			}
			$matches_list[ $key ][] = $match;
		}
		if ( empty( $template ) ) {
			$filename = 'matches-results';
		} elseif ( isset( $league ) && $this->check_template( 'matches-results-' . $league->sport ) ) {
			$filename = 'matches-results-' . $league->sport;
		} else {
			$filename = 'matches-' . $template;
		}
		return $this->load_template(
			$filename,
			array(
				'matches_list' => $matches_list,
				'header_level' => $header_level,
			)
		);
	}
	/**
	 * Function to display Players
	 *
	 *  [[players] template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_players( array $atts ): string {
		$args           = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template       = $args['template'];
		$search_string  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : null; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search_results = null;
		if ( $search_string ) {
			$search_results = player_search( $search_string );
		}
		$favourites = array();
		if ( is_user_logged_in() ) {
			$userid     = get_current_user_id();
			$user       = get_user( $userid );
			$favourites = $user->get_favourites( 'player' );
		}
		$filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
		return $this->load_template(
			$filename,
			array(
				'favourites'     => $favourites,
				'search_string'  => $search_string,
				'search_results' => $search_results,
			)
		);
	}
	/**
	 * Function to display Player
	 *
	 *  [[player] template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_player( array $atts ): string {
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Player by Name.
		$player_name = get_query_var( 'player_id' );
		$player_name = un_seo_url( $player_name );
		$btm         = get_query_var( 'btm' );
		if ( $btm ) {
			$player = get_player( $btm, 'btm' );
		} else {
			$player = get_player( $player_name, 'name' ); // get player by name.
		}
		if ( ! $player ) {
			return __( 'Player not found', 'racketmanager' );
		}
		$player->clubs        = $player->get_clubs();
		$player->titles       = $player->get_titles();
		$player->stats        = $player->get_career_stats();
		$player->competitions = array( 'cup', 'league', 'tournament' );
		foreach ( $player->competitions as $competition_type ) {
			if ( 'tournament' === $competition_type ) {
				$player->$competition_type = $player->get_tournaments( array( 'type' => $competition_type ) );
			} else {
				$player->$competition_type = $player->get_competitions( array( 'type' => $competition_type ) );
			}
		}

		$filename = ( ! empty( $template ) ) ? 'player-' . $template : 'player';
		return $this->load_template(
			$filename,
			array(
				'player' => $player,
			)
		);
	}
	/**
	 * Function to show favourites
	 *
	 *    [favourites template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_favourites( array $atts ): string {
		$args = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		if ( ! is_user_logged_in() ) {
			return $this->return_error( __( 'You must be logged in to view favourites', 'racketmanager' ) );
		}
		$template   = $args['template'];
		$user       = get_user( get_current_user_id() );
		$favourites = $user->get_favourites();
		$filename   = ( ! empty( $template ) ) ? 'form-favourites-' . $template : 'form-favourites';
		return $this->load_template( $filename, array( 'favourite_types' => $favourites ), 'form' );
	}
	/**
	 * Function to show invoice
	 *
	 *    [invoice template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_invoice( array $atts ): string {
		$args = shortcode_atts(
			array(
				'id' => '',
			),
			$atts
		);
		$id   = $args['id'];
		if ( ! $id ) {
			$id = get_query_var( 'id' );
		}
		if ( $id ) {
			$invoice = get_invoice( $id );
			if ( $invoice ) {
				return $invoice->generate();
			}
		}
		return $this->return_error( __( 'No invoice found', 'racketmanager' ) );
	}
	/**
	 * Function to show memberships
	 *
	 *    [memberships template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_memberships( array $atts ): string {
		$args = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		if ( ! is_user_logged_in() ) {
			return $this->return_error( __( 'You must be logged in to view memberships', 'racketmanager' ) );
		}
		$template = $args['template'];
		$player   = get_player( get_current_user_id() );
		if ( $player ) {
			$player->clubs         = $player->get_clubs( array( 'type' => 'active' ) );
			$player->clubs_archive = $player->get_clubs( array( 'type' => 'inactive' ) );
		} else {
			return $this->return_error( __( 'Player not found', 'racketmanager' ) );
		}
		$filename = ( ! empty( $template ) ) ? 'player-clubs-' . $template : 'player-clubs';

		return $this->load_template( $filename, array( 'player' => $player ), 'account' );
	}
	/**
	 * Function to search players
	 *
	 *    [search-players search=x template=X]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_player_search( array $atts ): string {
		global $racketmanager;
		$args          = shortcode_atts(
			array(
				'search'   => null,
				'template' => '',
			),
			$atts
		);
		$template      = $args['template'];
		$search_string = $args['search'];
		$players       = $racketmanager->get_all_players( array( 'name' => $search_string ) );
		$filename      = ( ! empty( $template ) ) ? 'players-list-' . $template : 'players-list';

		return $this->load_template( $filename, array( 'players' => $players ) );
	}
	/**
	 * Function to show team order
	 *
	 *    [team-order]
	 *
	 * @param array $atts shortcode attributes.
	 * @return string content
	 */
	public function show_team_order( array $atts ): string {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template          = $args['template'];
		$club_args         = array();
		$club_args['type'] = 'affiliated';
		$clubs             = $racketmanager->get_clubs( $club_args );
		if ( ! $clubs ) {
			return $this->return_error( __( 'No clubs found', 'racketmanager' ) );
		}
		$event_args                    = array();
		$event_args['entry_type']      = 'team';
		$event_args['reverse_rubbers'] = true;
		$events                        = $racketmanager->get_events( $event_args );
		if ( ! $events ) {
			return $this->return_error( __( 'No events found', 'racketmanager' ) );
		}
		$event_types   = Racketmanager_Util::get_event_types();
		$age_groups   = Racketmanager_Util::get_age_groups();
		$filename     = ( ! empty( $template ) ) ? 'team-order-' . $template : 'team-order';

		return $this->load_template( $filename, array(
													  'clubs'  => $clubs,
													  'events' => $events,
													  'event_types' => $event_types,
													  'age_groups'  => $age_groups,
													  )
									);
	}
	/**
	 * Load template for user display. First the current theme directory is checked for a template
	 * before defaulting to the plugin
	 *
	 * @param string $template Name of the template file (without extension).
	 * @param array $vars Array of variables name=>value available to display code (optional).
	 * @param false|string $template_type Type of content template (email, page).
	 * @return string the content
	 */
	public function load_template( string $template, array $vars = array(), false|string $template_type = false ): string {
		if ( $template_type ) {
			$template_dir = match ($template_type) {
				'competition' => 'templates/competition',
				'event'       => 'templates/event',
				'email'       => 'templates/email',
				'entry'       => 'templates/entry',
				'form'        => 'templates/forms',
				'includes'    => 'templates/includes',
				'page'        => 'templates/page',
				'tournament'  => 'templates/tournament',
				'account'     => 'templates/account',
				'league'      => 'templates/league',
				'match'       => 'templates/match',
				'club'        => 'templates/club',
				default       => 'templates',
			};
		} else {
			$template_dir = 'templates';
		}
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		ob_start();

		if ( file_exists( get_stylesheet_directory() . "/racketmanager/$template.php" ) ) {
			require get_stylesheet_directory() . "/racketmanager/$template.php";
		} elseif ( file_exists( get_template_directory() . "/racketmanager/$template.php" ) ) {
			require get_template_directory() . "/racketmanager/$template.php";
		} elseif ( file_exists( RACKETMANAGER_PATH . $template_dir . '/' . $template . '.php' ) ) {
			require RACKETMANAGER_PATH . $template_dir . '/' . $template . '.php';
		} else {
			/* translators: %1$s: template %2$s: directory */
			echo esc_html( sprintf( __( 'Could not load template %1$s.php from %2$s directory', 'racketmanager' ), $template, $template_dir ) );
		}
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	/**
	 * Check if template exists
	 *
	 * @param string $template template name.
	 * @param string|null $directory optional directory name.
	 * @return boolean
	 */
	public function check_template( string $template, string $directory = null ): bool {
		$template_dir = 'templates/';
		if ( $directory ) {
			$template_dir .= $directory . '/';
		}
		return file_exists( get_stylesheet_directory() . "/racketmanager/$template.php" ) || file_exists( get_template_directory() . "/racketmanager/$template.php" ) || file_exists( RACKETMANAGER_PATH . $template_dir . $template . '.php' );
	}
	/**
	 * Get league
	 *
	 * @param int $league_id league id.
	 * @return object
	 */
	public function get_league( int $league_id ): object {
		global $league;

		if ( 0 === $league_id ) {
			$league = get_league();
		} else {
			$league = get_league( $league_id );
		}
		return $league;
	}
	/**
	 * Get draws for event function
	 *
	 * @param object $event event object.
	 * @param string $season season.
	 * @return array of leagues with draws.
	 */
	public function get_draw( object $event, string $season ): array {
		$leagues = $event->get_leagues();
		foreach ( $leagues as $l => $league ) {
			$league = get_league( $league->id );
			$finals = array_reverse( $league->championship->get_finals() );
			foreach ( $finals as $f => $final ) {
				$matches = $league->get_matches(
					array(
						'season'  => $season,
						'final'   => $final['key'],
						'orderby' => array(
							'id' => 'ASC',
						),
					)
				);
				if ( count( $matches ) ) {
					$final['matches'] = $matches;
					$finals[ $f ]     = (object) $final;
				} else {
					unset( $finals[ $f ] );
				}
			}
			$league->finals = $finals;
			$leagues[ $l ]  = $league;
		}
		return $leagues;
	}
	/**
	 * Return error function
	 *
	 * @param string $msg message to display.
	 * @return string output html
	 */
	public function return_error(string $msg ): string {
        $filename= 'alert';
        return $this->load_template( $filename, array(
                'msg'   => $msg,
                'class' => 'danger',
            )
        );
	}
	/**
	 * Return modal error function
	 *
	 * @param string $msg message to display.
	 * @return string output html modal
	 */
	public function return_error_modal(string $msg ): string {
		ob_start();
		?>
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header modal__header">
                    <h4 class="modal-title"><?php esc_html_e( 'Error', 'racketmanager' ); ?></h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <div class="alert_rm alert--danger">
                            <div class="alert__body">
                                <div class="alert__body-inner">
                                    <span><?php echo esc_html( $msg ); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                </div>
            </div>
        </div>
		<?php
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
    /**
     * Show alert function
     *
     *    [show-alert msg=x type-x]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string output html
     */
    public function show_alert( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'msg'  => '',
                'type' => '',
            ),
            $atts
        );
        $msg      = $args['msg'];
        $type     = $args['type'];
        $filename = 'alert';
        return $this->load_template(
                $filename,
                array(
                        'msg'   => $msg,
                        'class' => $type,
                    )
        );
    }
}
