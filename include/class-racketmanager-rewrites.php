<?php
/**
 * RacketManager API: RacketManager_Rewrites class
 *
 * @author Paul Moffat
 * @package RacketManager
 */

namespace Racketmanager;

/**
 * Main class to implement RacketManager Rewrites
 */
class RacketManager_Rewrites {
	protected static ? RacketManager_Rewrites $instance = null;
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->racketmanager_rewrites();
		self::$instance = $this;
	}
	/**
	 * Create formatted url
	 */
	public function racketmanager_rewrites(): void {
		// competition list.
		add_rewrite_rule(
			'leagues/?$',
			'index.php?pagename=competitions&type=league',
			'top'
		);
		add_rewrite_rule(
			'cups/?$',
			'index.php?pagename=competitions&type=cup',
			'top'
		);
		add_rewrite_rule(
			'tournaments/?$',
			'index.php?pagename=competitions&type=tournament',
			'top'
		);
		// tournament entry form - name - payment complete.
		add_rewrite_rule(
			'entry-form/(.+?)-tournament/payment-complete/?$',
			'index.php?pagename=competition%2Fentry%2Fpayment-complete&tournament=$matches[1]&competition_type=tournament&club_name=$matches[2]',
			'top'
		);
		// tournament entry form - name - payment.
		add_rewrite_rule(
			'entry-form/(.+?)-tournament/payment/?$',
			'index.php?pagename=competition%2Fentry%2Fpayment&tournament=$matches[1]&competition_type=tournament&club_name=$matches[2]',
			'top'
		);
		// tournament entry form - name - player.
		add_rewrite_rule(
			'entry-form/(.+?)-tournament/player/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&tournament=$matches[1]&player_id=$matches[2]&competition_type=tournament',
			'top'
		);
		// tournament entry form - name - club.
		add_rewrite_rule(
			'entry-form/(.+?)-tournament/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&tournament=$matches[1]&competition_type=tournament&club_name=$matches[2]',
			'top'
		);
		// tournament entry form - name.
		add_rewrite_rule(
			'entry-form/(.+?)-tournament/?$',
			'index.php?pagename=competition%2Fentry&tournament=$matches[1]&competition_type=tournament',
			'top'
		);
		// entry form - competition - season - club.
		add_rewrite_rule(
			'entry-form/(.+?)/([0-9]{4})/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&club_name=$matches[3]&season=$matches[2]&competition_name=$matches[1]',
			'top'
		);
		// entry form - competition - season.
		add_rewrite_rule(
			'entry-form/(.+?)/([0-9]{4})/?$',
			'index.php?pagename=competition%2Fentry&season=$matches[2]&competition_name=$matches[1]',
			'top'
		);
		// entry form - competition - club.
		add_rewrite_rule(
			'entry-form/(.+?)/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&club_name=$matches[2]&competition_name=$matches[1]',
			'top'
		);
		// cup entry form - type - season - club.
		add_rewrite_rule(
			'cup/entry-form/(.+?)/([0-9]{4})/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&club_name=$matches[3]&season=$matches[2]&competition_name=$matches[1]&competition_type=cup',
			'top'
		);
		// league entry form - competition - season - club.
		add_rewrite_rule(
			'league/entry-form/(.+?)/([0-9]{4})/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&club_name=$matches[3]&season=$matches[2]&competition_name=$matches[1]&competition_type=league',
			'top'
		);
		// tournament entry form - name - player.
		add_rewrite_rule(
			'tournament/entry-form/(.+?)/player/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&competition_name=$matches[1]&player_id=$matches[2]&competition_type=tournament',
			'top'
		);
		// tournament entry form - name - club.
		add_rewrite_rule(
			'tournament/entry-form/(.+?)/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&competition_name=$matches[1]&club=$matches[2]&competition_type=tournament',
			'top'
		);
		// tournament entry form - name.
		add_rewrite_rule(
			'tournaments/entry-form/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&&competition_name=$matches[1]&competition_type=tournament',
			'top'
		);
		// tournament entry form - name.
		add_rewrite_rule(
			'tournament/entry-form/(.+?)/?$',
			'index.php?pagename=competition%2Fentry&&competition_name=$matches[1]&competition_type=tournament',
			'top'
		);
		// league news info.
		add_rewrite_rule(
			'league-news/?$',
			'index.php?pagename=leagues',
			'top'
		);
		add_rewrite_rule(
			'leagues/(.+?)-news/?$',
			'index.php?pagename=leagues%2F$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'cup-news/?$',
			'index.php?pagename=cups',
			'top'
		);
		add_rewrite_rule(
			'cups/(.+?)-news/?$',
			'index.php?pagename=cups%2F$matches[1]',
			'top'
		);
		add_rewrite_rule(
			'tournament-news/?$',
			'index.php?pagename=tournaments',
			'top'
		);
		add_rewrite_rule(
			'tournaments/(.+?)-news/?$',
			'index.php?pagename=tournaments%2F$matches[1]',
			'top'
		);
		// daily matches - date.
		add_rewrite_rule(
			'leagues/daily-matches/([0-9]{4})-([0-9]{2})-([0-9]{2})/?$',
			'index.php?pagename=competition%2Fdaily-matches&match_date=$matches[1]-$matches[2]-$matches[3]&competition_type=league',
			'top'
		);
		add_rewrite_rule(
			'cups/daily-matches/([0-9]{4})-([0-9]{2})-([0-9]{2})/?$',
			'index.php?pagename=competition%2Fdaily-matches&match_date=$matches[1]-$matches[2]-$matches[3]&competition_type=cup',
			'top'
		);
		// daily matches.
		add_rewrite_rule(
			'leagues/daily-matches/?$',
			'index.php?pagename=competition%2Fdaily-matches&competition_type=league',
			'top'
		);
		add_rewrite_rule(
			'cups/daily-matches/?$',
			'index.php?pagename=competition%2Fdaily-matches&competition_type=cup',
			'top'
		);
		// player + btm.
		add_rewrite_rule(
			'player/(.+?)/([0-9]+)/?$',
			'index.php?pagename=players%2Fplayer&player_id=$matches[1]&btm=$matches[2]',
			'top'
		);
		// player.
		add_rewrite_rule(
			'player/(.+?)/?$',
			'index.php?pagename=players%2Fplayer&player_id=$matches[1]',
			'top'
		);
		// players.
		add_rewrite_rule(
			'players/?$',
			'index.php?pagename=players',
			'top'
		);
		$this->rewrite_competition();
		$this->rewrite_tournament();
		$this->rewrite_league();
		$this->rewrite_cups();
		// club - players - player + btm.
		add_rewrite_rule(
			'clubs/(.+?)/players/(.+?)/([0-9]+)/?$',
			'index.php?pagename=clubs%2Fclub%2Fplayers&club_name=$matches[1]&player_id=$matches[2]&btm=$matches[3]',
			'top'
		);
		// club - players - player.
		add_rewrite_rule(
			'clubs/(.+?)/players/(.+?)/?$',
			'index.php?pagename=clubs%2Fclub%2Fplayers&club_name=$matches[1]&player_id=$matches[2]',
			'top'
		);
		// club - players.
		add_rewrite_rule(
			'clubs/(.+?)/players/?$',
			'index.php?pagename=clubs%2Fclub%2Fplayers&club_name=$matches[1]',
			'top'
		);
		// club - invoices - invoice.
		add_rewrite_rule(
			'clubs/(.+?)/invoices/(.+?)/?$',
			'index.php?pagename=clubs%2Fclub%2Finvoices&club_name=$matches[1]&invoice=$matches[2]',
			'top'
		);
		// club - invoices.
		add_rewrite_rule(
			'clubs/(.+?)/invoices/?$',
			'index.php?pagename=clubs%2Fclub%2Finvoices&club_name=$matches[1]',
			'top'
		);
		// club - team - event.
		add_rewrite_rule(
			'clubs/(.+?)/team/(.+?)/(.+?)?$',
			'index.php?pagename=clubs%2Fclub%2Fteam&club_name=$matches[1]&team=$matches[2]&event=$matches[3]',
			'top'
		);
		// club - event - season.
		add_rewrite_rule(
			'clubs/(.+?)/event/(.+?)/([0-9]{4})?$',
			'index.php?pagename=clubs%2Fclub%2Fevent&club_name=$matches[1]&event=$matches[2]&season=$matches[3]',
			'top'
		);
		// club - event.
		add_rewrite_rule(
			'clubs/(.+?)/event/(.+?)/?$',
			'index.php?pagename=clubs%2Fclub%2Fevent&club_name=$matches[1]&event=$matches[2]',
			'top'
		);
		// club - competitions.
		add_rewrite_rule(
			'clubs/(.+?)/competitions/?$',
			'index.php?pagename=clubs%2Fclub%2Fcompetitions&club_name=$matches[1]',
			'top'
		);
		// club player.
		add_rewrite_rule(
			'clubs/(.+?)/(.+?)/?$',
			'index.php?pagename=clubs%2Fclub%2Fplayer&club_name=$matches[1]&player_id=$matches[2]',
			'top'
		);
		// club.
		add_rewrite_rule(
			'clubs\/(.+?)\/?$',
			'index.php?pagename=clubs%2Fclub&club_name=$matches[1]',
			'top'
		);
		// invoice.
		add_rewrite_rule(
			'invoice\/(.+?)\/?$',
			'index.php?pagename=invoice&id=$matches[1]',
			'top'
		);
	}
	/**
	 * Rewrite competition urls function
	 *
	 * @return void
	 */
	private function rewrite_competition(): void {
		// latest results - age range.
		add_rewrite_rule(
			'leagues/latest-results/(.+?)/?$',
			'index.php?pagename=competition%2Flatest-results&age_group=$matches[1]&competition_type=league',
			'top'
		);
		add_rewrite_rule(
			'cups/latest-results/(.+?)/?$',
			'index.php?pagename=competition%2Flatest-results&competition_name=$matches[1]&competition_type=cup',
			'top'
		);
		add_rewrite_rule(
			'tournaments/latest-results/(.+?)/?$',
			'index.php?pagename=competition%2Flatest-results&competition_name=$matches[1]&competition_type=tournament',
			'top'
		);
		// latest results.
		add_rewrite_rule(
			'leagues/latest-results/?$',
			'index.php?pagename=competition%2Flatest-results&competition_type=league',
			'top'
		);
		add_rewrite_rule(
			'cups/latest-results/?$',
			'index.php?pagename=competition%2Flatest-results&competition_type=cup',
			'top'
		);
		add_rewrite_rule(
			'tournaments/latest-results/?$',
			'index.php?pagename=competition%2Flatest-results&competition_type=tournament',
			'top'
		);
		// cup - season - player.
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=competition&competition=$matches[1]-cups&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// cup - season - players.
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})/players/?$',
			'index.php?pagename=competition&competition=$matches[1]-cups&season=$matches[2]&tab=players',
			'top'
		);
		// cup - season - teams.
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})/teams/?$',
			'index.php?pagename=competition&competition=$matches[1]-cups&season=$matches[2]&tab=teams',
			'top'
		);
		// cup - season - team.
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=competition&competition=$matches[1]-cups&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// cup - season - clubs.
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})/clubs/?$',
			'index.php?pagename=competition&competition=$matches[1]-cups&season=$matches[2]&tab=clubs',
			'top'
		);
		// cup - season - club.
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})/club/(.+?)/?$',
			'index.php?pagename=competition&competition=$matches[1]-cups&season=$matches[2]&club_name=$matches[3]',
			'top'
		);
		// cup - season - overview.
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})/overview/?$',
			'index.php?pagename=competition&competition=$matches[1]-cups&season=$matches[2]&tab=overview',
			'top'
		);
		// cups - season.
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})?$',
			'index.php?pagename=competition&season=$matches[2]&competition=$matches[1]-cups',
			'top'
		);
		// competition.
		add_rewrite_rule(
			'(.+?)-cups/?$',
			'index.php?pagename=competition&competition=$matches[1]-cups',
			'top'
		);
		// cup - season - player.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// cup - season - players.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/players/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&season=$matches[2]&tab=players',
			'top'
		);
		// cup - season - teams.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/teams/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&season=$matches[2]&tab=teams',
			'top'
		);
		// cup - season - team.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// cup - season - clubs.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/clubs/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&season=$matches[2]&tab=clubs',
			'top'
		);
		// cup - season - club.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/club/(.+?)/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&season=$matches[2]&club_name=$matches[3]',
			'top'
		);
		// league - season - overview.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/overview/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&season=$matches[2]&tab=overview',
			'top'
		);
		// leagues - season.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})?$',
			'index.php?pagename=competition&season=$matches[2]&competition=$matches[1]-leagues',
			'top'
		);
		// leagues - season - events.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/events/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&season=$matches[2]&tab=events',
			'top'
		);
		// competition - season - winners.
		add_rewrite_rule(
			'(.+?)-leagues/([0-9]{4})/winners?$',
			'index.php?pagename=competition&season=$matches[2]&competition=$matches[1]-leagues&tab=winners',
			'top'
		);
		add_rewrite_rule(
			'(.+?)-cups/([0-9]{4})/winners?$',
			'index.php?pagename=competition&season=$matches[2]&competition=$matches[1]-cups&tab=winners',
			'top'
		);
		// competition - winners.
		add_rewrite_rule(
			'(.+?)-leagues/winners?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues&tab=winners',
			'top'
		);
		add_rewrite_rule(
			'(.+?)-cups/winners?$',
			'index.php?pagename=competition&competition=$matches[1]-cups&tab=winners',
			'top'
		);
		// competition.
		add_rewrite_rule(
			'(.+?)-leagues/?$',
			'index.php?pagename=competition&competition=$matches[1]-leagues',
			'top'
		);
		// cup - season (winners).
		add_rewrite_rule(
			'leagues/(.+?)/winners/([0-9]{4})?$',
			'index.php?pagename=leagues%2F$matches[1]%2Fwinners&season=$matches[2]',
			'top'
		);
	}
	/**
	 * Rewrite league urls function
	 *
	 * @return void
	 */
	private function rewrite_league(): void {
		$this->rewrite_league_events();
		// league - season - matchday - team.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/day([0-9]{1,2})/(.+?)/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]&team=$matches[4]',
			'top'
		);
		// league - season - matchday.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/day([0-9]{1,2})/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]',
			'top'
		);
		// league - season - matchday.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/matches/day([0-9]{1,2})/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]',
			'top'
		);
		// league - season - player.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// league - season - players.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/players/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// league - season - teams.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/teams/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&tab=teams',
			'top'
		);
		// league - round - teams.
		add_rewrite_rule(
			'league/(.+?)/round-([0-9]{1,2})/teams/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&tab=teams',
			'top'
		);
		// league - season - team.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// league - round - team.
		add_rewrite_rule(
			'league/(.+?)/round-([0-9]{1,2})/team/(.+?)/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// league - round.
		add_rewrite_rule(
			'league/(.+?)/round-([0-9]{1})\/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]',
			'top'
		);
		// league - match - gibberish.
		add_rewrite_rule(
			'league\/(.+?)\/match\/(.+?)\/(.+?)\/?$',
			'index.php?pagename=competition%2Fevent%2Fleague%2Fmatch&league_name=$matches[1]&match_id=$matches[2]',
			'top'
		);
		// league - match.
		add_rewrite_rule(
			'league\/(.+?)\/match\/(.+?)\/?$',
			'index.php?pagename=competition%2Fevent%2Fleague%2Fmatch&league_name=$matches[1]&match_id=$matches[2]',
			'top'
		);
		// league - season - matches.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})\/matches/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&tab=matches',
			'top'
		);
		// league - season - crosstable.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})\/crosstable/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&tab=crosstable',
			'top'
		);
		// league - season - standings.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})\/standings/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]&tab=standings',
			'top'
		);
		// league - season.
		add_rewrite_rule(
			'league/(.+?)/([0-9]{4})\/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]&season=$matches[2]',
			'top'
		);
		// league.
		add_rewrite_rule(
			'league/(.+?)/?$',
			'index.php?pagename=competition%2Fevent%2Fleague&league_name=$matches[1]',
			'top'
		);

		// league - season - round - match - leg - result.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/(.+?)/(.+?)-vs-(.+?)/leg-([0-9]{1})/result/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&round=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]&leg=$matches[6]&action=result',
			'top'
		);
		// league - season - round - match - leg.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/(.+?)/(.+?)-vs-(.+?)/leg-([0-9]{1})/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&round=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]&leg=$matches[6]',
			'top'
		);
		// league - season - matchday - match.
		add_rewrite_rule(
			'match/(.+?)/round-([0-9]{1})/day([0-9]{1,2})/(.+?)-vs-(.+?)/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]',
			'top'
		);
		// league - season - matchday - match - result.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/day([0-9]{1,2})/(.+?)-vs-(.+?)/result/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]&action=result',
			'top'
		);
		// league - season - matchday - match.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/day([0-9]{1,2})/(.+?)-vs-(.+?)/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&match_day=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]',
			'top'
		);
		// league - season - round - match - result.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/(.+?)/(.+?)-vs-(.+?)/result/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&round=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]&action=result',
			'top'
		);
		// league - season - round - match.
		add_rewrite_rule(
			'match/(.+?)/([0-9]{4})/(.+?)/(.+?)-vs-(.+?)/?$',
			'index.php?pagename=match%2F&league_name=$matches[1]&season=$matches[2]&round=$matches[3]&teamHome=$matches[4]&teamAway=$matches[5]',
			'top'
		);
	}
	/**
	 * Rewrite league events urls function
	 *
	 * @return void
	 */
	private function rewrite_league_events(): void {
		// league event - season.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]',
			'top'
		);
		// league event - round.
		add_rewrite_rule(
			'leagues/(.+?)/round-([0-9]{1,2,3})/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]',
			'top'
		);
		// league event - season - club.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/club/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&club_name=$matches[3]',
			'top'
		);
		// league event - season - clubs.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/clubs/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=clubs',
			'top'
		);
		// league event - season - team.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// league event - season - teams.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/teams/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=teams',
			'top'
		);
		// league event - season - player.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// league event - season - players.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/players/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// league event - season - standings.
		add_rewrite_rule(
			'leagues/(.+?)/([0-9]{4})/standings/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=standings',
			'top'
		);
		// league event.
		add_rewrite_rule(
			'leagues/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]',
			'top'
		);
	}
	/**
	 * Rewrite cups urls function
	 *
	 * @return void
	 */
	private function rewrite_cups(): void {
		$this->rewrite_cup_events();
		// cup - season - teams.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})/teams/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=teams',
			'top'
		);
		// cup - season - team.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// cup - season - player.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// cup - season - players.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})/players/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// cup - season.
		add_rewrite_rule(
			'cup/(.+?)/([0-9]{4})?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]',
			'top'
		);
		// cup.
		add_rewrite_rule(
			'cup/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]',
			'top'
		);
	}
	/**
	 * Rewrite cup events urls function
	 *
	 * @return void
	 */
	private function rewrite_cup_events(): void {
		// cup event - season.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]',
			'top'
		);
		// cup event - round.
		add_rewrite_rule(
			'cups/(.+?)/round-([0-9]{1,2,3})/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]',
			'top'
		);
		// cup event - season - club.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/club/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&club_name=$matches[3]',
			'top'
		);
		// cup event - season - clubs.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/clubs/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=clubs',
			'top'
		);
		// cup event - season - team.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/team/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&team=$matches[3]',
			'top'
		);
		// cup event - season - player.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/player/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// cup event - season - players.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/players/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// cup event - season - teams.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/teams/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=teams',
			'top'
		);
		// cup event - season - matches.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/matches/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=matches',
			'top'
		);
		// cup event - season - draw.
		add_rewrite_rule(
			'cups/(.+?)/([0-9]{4})/draw/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]&season=$matches[2]&tab=draw',
			'top'
		);
		// cup event.
		add_rewrite_rule(
			'cups/(.+?)/?$',
			'index.php?pagename=competition%2Fevent&event=$matches[1]',
			'top'
		);
	}
	/**
	 * Rewrite tournament urls function
	 *
	 * @return void
	 */
	private function rewrite_tournament(): void {
		// tournament - latest - age group.
		add_rewrite_rule(
			'tournaments/latest-(.+?)-tournament/?$',
			'index.php?pagename=tournaments%2Flatest-tournament&age_group=$matches[1]',
			'top'
		);
		// tournament - latest.
		add_rewrite_rule(
			'tournaments/latest-tournament/?$',
			'index.php?pagename=tournaments%2Flatest-tournament',
			'top'
		);
		// tournament - age group.
		add_rewrite_rule(
			'tournaments/(.+?)/?$',
			'index.php?pagename=competitions&type=tournament&age_group=$matches[1]',
			'top'
		);
		// tournament - order of play.
		add_rewrite_rule(
			'tournament/(.+?)/order_of_play/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=order_of_play',
			'top'
		);
		// tournament - order of play.
		add_rewrite_rule(
			'tournament/(.+?)/order-of-play/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=order_of_play',
			'top'
		);
		// tournament - match.
		add_rewrite_rule(
			'tournament/(.+?)/match/(.+?)/(.+?)-vs-(.+?)/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament%2Fmatch&tournament=$matches[1]&league_name=$matches[2]&teamHome=$matches[3]&teamAway=$matches[4]&match_id=$matches[5]',
			'top'
		);
		// tournament - match - gibberish.
		add_rewrite_rule(
			'tournament/(.+?)/match/(.+?)/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament%2Fmatch&tournament=$matches[1]&match_id=$matches[2]',
			'top'
		);
		// tournament - match.
		add_rewrite_rule(
			'tournament/(.+?)/match/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament%2Fmatch&tournament=$matches[1]&match_id=$matches[2]',
			'top'
		);
		// tournament - matches - match date.
		add_rewrite_rule(
			'tournament/(.+?)/matches/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&match_date=$matches[2]&tab=matches',
			'top'
		);
		// tournament - matches.
		add_rewrite_rule(
			'tournament/(.+?)/matches/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=matches',
			'top'
		);
		// tournament - name - winners.
		add_rewrite_rule(
			'tournament/(.+?)/winners/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=winners',
			'top'
		);
		// tournament - name - player.
		add_rewrite_rule(
			'tournament/(.+?)/player/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&player=$matches[2]&tab=players',
			'top'
		);
		// tournament - name - players.
		add_rewrite_rule(
			'tournament/(.+?)/players/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=players',
			'top'
		);
		// tournament - name - draws.
		add_rewrite_rule(
			'tournament/(.+?)/draws/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=draws',
			'top'
		);
		// tournament - name - draw.
		add_rewrite_rule(
			'tournament/(.+?)/draw/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&draw=$matches[2]&tab=draws',
			'top'
		);
		// tournament - name - events.
		add_rewrite_rule(
			'tournament/(.+?)/events/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=events',
			'top'
		);
		// tournament - name - event.
		add_rewrite_rule(
			'tournament/(.+?)/event/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&event=$matches[2]&tab=events',
			'top'
		);
		// tournament - name - overview.
		add_rewrite_rule(
			'tournament/(.+?)/overview/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]&tab=overview',
			'top'
		);
		// tournament - name.
		add_rewrite_rule(
			'tournament/(.+?)/?$',
			'index.php?pagename=tournaments%2Ftournament&tournament=$matches[1]',
			'top'
		);
		// tournament.
		add_rewrite_rule(
			'tournament/?$',
			'index.php?pagename=tournaments%2Ftournament',
			'top'
		);
		// tournament winners - type - season - tournament.
		add_rewrite_rule(
			'tournaments/(.+?)/winners/(.+?)/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2Fwinners&tournament=$matches[2]&type=$matches[1]',
			'top'
		);
		// tournament winners - type - season.
		add_rewrite_rule(
			'tournaments/(.+?)/winners/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2Fwinners&type=$matches[1]',
			'top'
		);
		// tournament order of play - type - season - tournament.
		add_rewrite_rule(
			'tournaments/(.+?)/order-of-play/(.+?)/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2F$matches[1]-order-of-play&tournament=$matches[2]&type=$matches[1]',
			'top'
		);
		// tournament order of play - type - season.
		add_rewrite_rule(
			'tournaments/(.+?)/order-of-play/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2F$matches[1]-order-of-play&type=$matches[1]',
			'top'
		);
		// tournament event - season - players.
		add_rewrite_rule(
			'tournaments/(.+?)/([0-9]{4})/players?$',
			'index.php?pagename=tournaments%2Fevent&event=$matches[1]&season=$matches[2]&tab=players',
			'top'
		);
		// tournament event - season - player.
		add_rewrite_rule(
			'tournaments/(.+?)/([0-9]{4})/player/(.+?)?$',
			'index.php?pagename=tournaments%2Fevent&event=$matches[1]&season=$matches[2]&player_id=$matches[3]',
			'top'
		);
		// tournament event - season.
		add_rewrite_rule(
			'tournaments/(.+?)/(.+?)-(.+?)-(.+?)/([0-9]{4})?$',
			'index.php?pagename=tournaments%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]&season=$matches[5]',
			'top'
		);
		// tournament event.
		add_rewrite_rule(
			'tournaments/(.+?)/(.+?)-(.+?)-(.+?)/?$',
			'index.php?pagename=tournaments%2F$matches[1]%2F$matches[2]-$matches[3]-$matches[4]',
			'top'
		);
	}
}
