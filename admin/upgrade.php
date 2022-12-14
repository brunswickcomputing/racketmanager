<?php
/**
 * racketmanager_upgrade() - update routine for older version
 *
 * @return Success Message
 */
function racketmanager_upgrade() {
	global $wpdb, $racketmanager, $lmLoader;

	$options = $racketmanager->options;
	$installed = $options['dbversion'];

	echo __('Upgrade database structure...', 'racketmanager') . "<br />\n";
	$wpdb->show_errors();

	if (version_compare($installed, '5.1.7', '<')) {

		$wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_teams} ADD `system_record` VARCHAR(1) NULL DEFAULT NULL AFTER `removed_date` ");

    }
    if (version_compare($installed, '5.1.8', '<')) {

        $wpdb->query( "CREATE TABLE {$wpdb->leaguemanager_team_competition} (`id` int( 11 ) NOT NULL AUTO_INCREMENT ,`team_id` int( 11 ) NOT NULL default 0, `competition_id` int( 11 ) NOT NULL default 0, `captain` varchar( 255 ) NOT NULL default '',`contactno` varchar( 255 ) NOT NULL default '',`contactemail` varchar( 255 ) NOT NULL default '', `match_day` varchar( 25 ) NOT NULL default '', `match_time` time NULL, PRIMARY KEY ( `id` ), INDEX( `team_id` ), INDEX( `competition_id` ))") ;
        $wpdb->query( "INSERT INTO {$wpdb->leaguemanager_team_competition} (team_id, competition_id, captain, contactno, contactemail, match_day, match_time) (SELECT TE.id, L.`competition_id`, TE.captain, TE.contactno, TE.contactemail, TE.match_day, TE.match_time FROM `wp_leaguemanager_teams` TE, `wp_leaguemanager_table` TA, `wp_leaguemanager_leagues` L WHERE TE.id = TA.`team_id` AND TA.`league_id` = L.`id` GROUP BY team_id, competition_id, captain, contactno, contactemail, match_day, match_time)" );

    }
    if (version_compare($installed, '5.2.0', '<')) {
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} CHANGE `home_team` `home_team` VARCHAR(255) NOT NULL DEFAULT '0';" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} CHANGE `away_team` `away_team` VARCHAR(255) NOT NULL DEFAULT '0';" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_teams} DROP `captain`, DROP `contactno`, DROP `contactemail`, DROP `match_day`, DROP `match_time`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_players} ADD `fullname` VARCHAR(255) NOT NULL AFTER `surname`;" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_players} SET `fullname`= concat(`firstname`,' ',`surname`);" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_competitions} ADD `competitiontype` VARCHAR(255) NOT NULL AFTER `seasons`;" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_competitions} SET `competitiontype` = 'league' WHERE `competitiontype` = '';" );
    }
    if (version_compare($installed, '5.3.0', '<')) {
        echo __('starting 5.3.0 upgrade', 'leaguemanager') . "<br />\n";
        $prev_player_id = 0;
        $rosters = $wpdb->get_results(" SELECT `id`, `player_id`, `affiliatedclub`, `removed_date` FROM {$wpdb->leaguemanager_roster} ORDER BY `player_id`;");
        foreach ($rosters AS $roster) {
            if ($roster->player_id != $prev_player_id) {
                $player = $wpdb->get_results( $wpdb->prepare(" SELECT `firstname`, `surname`, `gender`, `btm` FROM {$wpdb->leaguemanager_players} WHERE `id` = %d", $roster->player_id) );
                if ( !$player ) {
                    error_log($roster->player_id.' player not found');
                } else {
                    $player = $player[0];
                    $userdata = array();
                    $userdata['first_name'] = $player->firstname;
                    $userdata['last_name'] = $player->surname;
                    $userdata['display_name'] = $player->firstname.' '.$player->surname;
                    $userdata['user_login'] = strtolower($player->firstname).'.'.strtolower($player->surname);
                    $userdata['user_pass'] = $userdata['user_login'].'1';
                    $user = get_user_by( 'login', $userdata['user_login'] );
                    if ( !$user ) {
                        $user_id = wp_insert_user( $userdata );
                    } else {
                        $user_id = $user->ID;
                    }
                    update_user_meta($user_id, 'show_admin_bar_front', false );
                    update_user_meta($user_id, 'gender', $player->gender);
                    if ( isset($player->btm) && $player->btm != '' ) {
                        update_user_meta($user_id, 'btm', $player->btm);
                    }
                    if ( isset($player->removed_date) && $player->removed_date != '' ) {
                        update_user_meta($user_id, 'remove_date', $player->removed_date);
                    }
                }
            }
            $prev_player_id = $roster->player_id;
            $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_roster} SET `player_id` = %d WHERE `id` = %d", $user_id, $roster->id ) );
        }
    }
    if (version_compare($installed, '5.3.1', '<')) {
        echo __('starting 5.3.1 upgrade', 'leaguemanager') . "<br />\n";
        echo __('updating captains', 'leaguemanager') . "<br />\n";
        $prev_captain = '';
        $captains = $wpdb->get_results(" SELECT `id`, `captain`, `contactno`, `contactemail` FROM {$wpdb->leaguemanager_team_competition} WHERE `captain` != '' ORDER BY `captain`;");
        foreach ($captains AS $captain) {
            if ( !is_numeric($captain->captain) ) {
                if ( $prev_captain != $captain->captain ) {
                    $user = $wpdb->get_results( $wpdb->prepare( "SELECT `ID` FROM {$wpdb->users} WHERE `display_name` = '%s'", $captain->captain ) );
                    if ( !isset($user[0]) ) {
                        error_log($captain->captain.' not found');
                    } else {
                        $user = $user[0];
                        if ( isset($captain->contactno) && $captain->contactno != '' ) {
                            update_user_meta($user->ID, 'contactno', $captain->contactno);
                        }
                        if ( isset($captain->contactemail) && $captain->contactemail != '' ) {
                            $userid = wp_update_user( array( 'ID' => $user->ID, 'user_email' => $captain->contactemail ) );
                        }
                    }
                    $prev_captain = $captain->captain;
                }
                $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_team_competition} SET `captain` = %d WHERE `id` = %s", $user->ID, $captain->id ) );
            }
        }
    }
    if (version_compare($installed, '5.3.2', '<')) {
        echo __('starting 5.3.2 upgrade', 'leaguemanager') . "<br />\n";
        echo __('updating player captains', 'leaguemanager') . "<br />\n";
        $teams = $wpdb->get_results(" SELECT `id`, `title`, `roster` FROM {$wpdb->leaguemanager_teams} WHERE `status` = 'P' ORDER BY `title`; ");
        foreach ($teams AS $team) {
            $team->title = htmlspecialchars(stripslashes($team->title), ENT_QUOTES);
            $team->roster = maybe_unserialize($team->roster);
            $captain = $leaguemanager->getRosterEntry($team->roster[0])->player_id;
            $contacts = $wpdb->get_results( $wpdb->prepare(" SELECT `id`, `captain`, `contactno`, `contactemail` FROM {$wpdb->leaguemanager_team_competition} WHERE `team_id` = %s;", $team->id) );
            foreach($contacts AS $contact) {
                if ( isset($contact->contactno) && $contact->contactno != '' ) {
                    update_user_meta($captain, 'contactno', $contact->contactno);
                }
                if ( isset($contact->contactemail) && $contact->contactemail != '' ) {
                    $userid = wp_update_user( array( 'ID' => $captain, 'user_email' => $contact->contactemail ) );
                }
                $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_team_competition} SET `captain` = %d WHERE `id` = %s", $captain, $contact->id ) );
            }
        }
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_team_competition} DROP `contactno`, DROP `contactemail`;" );
    }
    if (version_compare($installed, '5.3.3', '<')) {
        echo __('starting 5.3.3 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_roster} ADD `system_record` VARCHAR(1) NULL DEFAULT NULL AFTER `updated`;" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_roster} SET `system_record` = 'Y' WHERE `player_id` BETWEEN 1479 AND 1514;" );
   }
    if (version_compare($installed, '5.3.4', '<')) {
        echo __('starting 5.3.4 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->leaguemanager_players = $wpdb->prefix . 'leaguemanager_players';
        $wpdb->query( "DROP TABLE {$wpdb->leaguemanager_players}" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `updated_user` int(11) NULL  AFTER `custom`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `updated` datetime NULL AFTER `updated_user`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `confirmed` VARCHAR(1) NULL AFTER `updated`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `home_captain` int(11) NULL  AFTER `confirmed`;" );
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_matches} ADD `away_captain` int(11) NULL  AFTER `home_captain`;" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_matches} SET `confirmed` = 'Y' WHERE `winner_id` != 0;" );
    }
//    if (version_compare($installed, '5.3.5', '<')) {
//        echo __('starting 5.3.5 upgrade', 'leaguemanager') . "<br />\n";
//        $teams = $wpdb->get_results(" SELECT `title` FROM {$wpdb->leaguemanager_teams} GROUP BY `title` HAVING COUNT(*) > 1 ORDER BY `title`; ");
//
//        foreach ($teams AS $team) {
//           $teamsList = $wpdb->get_results( $wpdb->prepare(" SELECT `id`, `title` FROM {$wpdb->leaguemanager_teams} WHERE `title` = '%s';", $team->title) );
//            $teamId = $prevTitle = '';
//            foreach($teamsList AS $teamEntry) {
//                if ( $prevTitle != $teamEntry->title ) {
//                    $teamId = $teamEntry->id;
//                    $prevTitle = $teamEntry->title;
//                    echo 'updating '.$prevTitle. '<br />';
//                } else {
//                    echo 'updating '.$teamEntry->id. '<br />';
//                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_matches} SET `home_team` = '%s' WHERE `home_team` = '%s'", $teamId, $teamEntry->id ) );
//                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_matches} SET `away_team` = '%s' WHERE `away_team` = '%s'", $teamId, $teamEntry->id ) );
//                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_matches} SET `winner_id` = '%d' WHERE `winner_id` = '%d'", $teamId, $teamEntry->id ) );
//                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_matches} SET `loser_id` = '%d' WHERE `loser_id` = '%d'", $teamId, $teamEntry->id ) );
//                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_team_competition} SET `team_id` = '%d' WHERE `team_id` = '%d'", $teamId, $teamEntry->id ) );
//                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_table} SET `team_id` = '%d' WHERE `team_id` = '%d'", $teamId, $teamEntry->id ) );
//                    $wpdb->query( $wpdb->prepare(" DELETE FROM {$wpdb->leaguemanager_team_competition} WHERE `team_id` = %s", $teamEntry->id ) );
//                    $wpdb->query( $wpdb->prepare(" DELETE FROM {$wpdb->leaguemanager_teams} WHERE `id` = %s", $teamEntry->id ) );
//                }
//            }
//        }
//    }
    if (version_compare($installed, '5.3.6', '<')) {
         echo __('starting 5.3.6 upgrade', 'leaguemanager') . "<br />\n";
         $players = $wpdb->get_results(" SELECT `id`, `user_nicename` FROM {$wpdb->users} WHERE `user_nicename` like 'SHARE%' or `user_nicename` like '%PAIR%' or `user_nicename` like 'walkover%' ORDER BY `user_nicename`; ");
         foreach ($players AS $player) {
             $playerId = $player->id;
             update_user_meta($playerId, 'leaguemanager_type', 'system' );
         }
    }
    if (version_compare($installed, '5.4.0', '<')) {
        echo __('starting 5.4.0 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "CREATE TABLE {$wpdb->leaguemanager_roster_requests} (`id` int( 11 ) NOT NULL AUTO_INCREMENT, `affiliatedclub` int( 11 ) NOT NULL default 0, first_name` varchar( 255 ) NOT NULL default '', `surname` varchar( 255 ) NOT NULL default '', `gender` varchar( 1 ) NOT NULL default '', `btm` int( 11 ) NULL , `player_id` int ( 11 )`, `requested_date` date NULL, `requested_user` int(11) NOT NULL, `completed_date` date NULL, `completed_user` int(11) NULL, PRIMARY KEY ( `id` ))" );
    }
    if (version_compare($installed, '5.4.1', '<')) {
        echo __('starting 5.4.1 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_roster} ADD `created_date` date NULL AFTER `system_record` ");
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_roster} ADD `created_user` int( 11 ) NULL AFTER `created_date` ");
    }
    if (version_compare($installed, '5.4.2', '<')) {
        echo __('starting 5.4.2 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "CREATE TABLE {$wpdb->leaguemanager_clubs} (`id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', `website` varchar( 100 ) NOT NULL default '', `type` varchar( 20 ) NOT NULL default '', `address` varchar( 255 ) NOT NULL default '', `latitude` varchar( 20 ) NOT NULL default '', `longitude` varchar( 20 ) NOT NULL default '', `contactno` varchar( 20 ) NOT NULL default '', `founded` int( 4 ) NULL, `facilities` varchar( 255 ) NOT NULL default '', `shortcode` varchar( 20 ) NOT NULL default '', `matchsecretary` int( 11 ) NULL, PRIMARY KEY ( `id` ))" );
    }
    if (version_compare($installed, '5.4.5', '<')) {
        echo __('starting 5.4.5 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_roster} ADD `removed_user` int( 11 ) NULL AFTER `removed_date` ");
    }
    if (version_compare($installed, '5.4.6', '<')) {
        echo __('starting 5.4.6 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "CREATE TABLE {$wpdb->leaguemanager_seasons} (`id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', PRIMARY KEY ( `id` ))" );
    }
    if (version_compare($installed, '5.4.7', '<')) {
        echo __('starting 5.4.7 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "CREATE TABLE {$wpdb->leaguemanager_competitions_seasons} (`id` int( 11 ) NOT NULL AUTO_INCREMENT, `competition_id` int( 11 ) NOT NULL, `season_id` int( 11 ) NOT NULL, PRIMARY KEY ( `id` ))" );
    }
    if (version_compare($installed, '5.5.6', '<')) {
        echo __('starting 5.5.6 upgrade', 'leaguemanager') . "<br />\n";
        $charset_collate = '';
        if ( $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty($wpdb->charset) )
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if ( ! empty($wpdb->collate) )
                $charset_collate .= " COLLATE $wpdb->collate";
        }
        $wpdb->query( "CREATE TABLE {$wpdb->leaguemanager_results_checker} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `league_id` int( 11 ) NOT NULL default '0', `match_id` int( 11 ) NOT NULL default '0', `team_id` int( 11 ) NULL, `player_id` int( 11 ) NULL, `description` varchar( 255 ) NULL, `status` int( 1 ) NULL, `updated_user` int( 11 ) NULL, `updated_date` datetime NULL, PRIMARY KEY ( `id` )) $charset_collate;" );
    }
    if (version_compare($installed, '5.5.7', '<')) {
        echo __('starting 5.5.7 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_teams} ADD `type` varchar( 2 ) NOT NULL default '' AFTER `custom` ");
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_teams} SET `type` = 'WD' WHERE `title` like '% Ladies %'" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_teams} SET `type` = 'MD' WHERE `title` like '% Mens %'" );
        $wpdb->query( "UPDATE {$wpdb->leaguemanager_teams} SET `type` = 'XD' WHERE `title` like '% Mixed %'" );
    }
    if (version_compare($installed, '5.6.0', '<')) {
        echo __('starting 5.6.0 upgrade', 'leaguemanager') . "<br />\n";
        $charset_collate = '';
        if ( $wpdb->has_cap( 'collation' ) ) {
            if ( ! empty($wpdb->charset) )
                $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if ( ! empty($wpdb->collate) )
                $charset_collate .= " COLLATE $wpdb->collate";
        }
        $wpdb->query( "CREATE TABLE {$wpdb->leaguemanager_tournaments} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `name` varchar( 100 ) NOT NULL default '', `type` varchar( 100 ) NOT NULL default '', `venue` int( 11 ) NULL, `date` date NULL, `closingdate` date NOT NULL, `tournamentsecretary` int( 11 ) NULL, PRIMARY KEY ( `id` )) $charset_collate;" );
    }
    if (version_compare($installed, '5.6.1', '<')) {
        echo __('starting 5.6.1 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->leaguemanager_tournaments} ADD `season` varchar( 255 ) NOT NULL default '' AFTER `type` ");
    }
    if (version_compare($installed, '5.6.10', '<')) {
        echo __('starting 5.6.10 upgrade', 'leaguemanager') . "<br />\n";
        $teams = $wpdb->get_results(" SELECT `title` FROM {$wpdb->leaguemanager_teams} GROUP BY `title` HAVING COUNT(*) > 1 ORDER BY `title`; ");

        foreach ($teams AS $team) {
           	$teamsList = $wpdb->get_results( $wpdb->prepare(" SELECT `id`, `title` FROM {$wpdb->leaguemanager_teams} WHERE `title` = '%s';", $team->title) );
            $teamId = $prevTitle = '';
            foreach($teamsList AS $teamEntry) {
                if ( $prevTitle != $teamEntry->title ) {
                    $teamId = $teamEntry->id;
                    $prevTitle = $teamEntry->title;
                    echo 'updating '.$prevTitle. '<br />';
                } else {
                    echo 'updating '.$teamEntry->id. '<br />';
                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_matches} SET `home_team` = '%s' WHERE `home_team` = '%s'", $teamId, $teamEntry->id ) );
                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_matches} SET `away_team` = '%s' WHERE `away_team` = '%s'", $teamId, $teamEntry->id ) );
                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_matches} SET `winner_id` = '%d' WHERE `winner_id` = '%d'", $teamId, $teamEntry->id ) );
                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_matches} SET `loser_id` = '%d' WHERE `loser_id` = '%d'", $teamId, $teamEntry->id ) );
                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_team_competition} SET `team_id` = '%d' WHERE `team_id` = '%d'", $teamId, $teamEntry->id ) );
                    $wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->leaguemanager_table} SET `team_id` = '%d' WHERE `team_id` = '%d'", $teamId, $teamEntry->id ) );
                    $wpdb->query( $wpdb->prepare(" DELETE FROM {$wpdb->leaguemanager_team_competition} WHERE `team_id` = %s", $teamEntry->id ) );
                    $wpdb->query( $wpdb->prepare(" DELETE FROM {$wpdb->leaguemanager_teams} WHERE `id` = %s", $teamEntry->id ) );
                }
            }
        }
    }
		if (version_compare($installed, '6.0.0', '<')) {
				echo __('starting 6.0.0 upgrade', 'leaguemanager') . "<br />\n";
				$wpdb_leaguemanager_leagues = $wpdb->prefix . 'leaguemanager_leagues';
				$wpdb_leaguemanager_table = $wpdb->prefix . 'leaguemanager_table';
				$wpdb_leaguemanager_teams = $wpdb->prefix . 'leaguemanager_teams';
				$wpdb_leaguemanager_matches = $wpdb->prefix . 'leaguemanager_matches';
				$wpdb_leaguemanager_rubbers = $wpdb->prefix . 'leaguemanager_rubbers';
				$wpdb_leaguemanager_roster = $wpdb->prefix . 'leaguemanager_roster';
				$wpdb_leaguemanager_competitions = $wpdb->prefix . 'leaguemanager_competitions';
				$wpdb_leaguemanager_team_competition = $wpdb->prefix . 'leaguemanager_team_competition';
				$wpdb_leaguemanager_roster_requests = $wpdb->prefix . 'leaguemanager_roster_requests';
				$wpdb_leaguemanager_clubs = $wpdb->prefix . 'leaguemanager_clubs';
				$wpdb_leaguemanager_seasons = $wpdb->prefix . 'leaguemanager_seasons';
				$wpdb_leaguemanager_competitions_seasons = $wpdb->prefix . 'leaguemanager_competitions_seasons';
				$wpdb_leaguemanager_results_checker = $wpdb->prefix . 'leaguemanager_results_checker';
				$wpdb_leaguemanager_tournaments = $wpdb->prefix . 'leaguemanager_tournaments';

				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_clubs TO $wpdb->racketmanager_clubs" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_competitions TO $wpdb->racketmanager_competitions" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_competitions_seasons TO $wpdb->racketmanager_competitions_seasons" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_leagues TO $wpdb->racketmanager" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_matches TO $wpdb->racketmanager_matches" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_results_checker TO $wpdb->racketmanager_results_checker" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_roster TO $wpdb->racketmanager_roster" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_roster_requests TO $wpdb->racketmanager_roster_requests" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_rubbers TO $wpdb->racketmanager_rubbers" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_seasons TO $wpdb->racketmanager_seasons" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_table TO $wpdb->racketmanager_table" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_teams TO $wpdb->racketmanager_teams" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_team_competition TO $wpdb->racketmanager_team_competition" );
				$wpdb->query( "RENAME TABLE $wpdb_leaguemanager_tournaments TO $wpdb->racketmanager_tournaments" );
				$recaptcha_site_key = get_option('leaguemanager-recaptcha-site-key');
				add_option('racketmanager-recaptcha-site-key', $recaptcha_site_key);
				$recaptcha_secret_key = get_option('leaguemanager-recaptcha-secret-key');
				add_option('racketmanager-recaptcha-secret-key', $recaptcha_secret_key);
				delete_option('leaguemanager-recaptcha-site-key');
				delete_option('leaguemanager-recaptcha-secret-key');
				/*
				* Set Capabilities
				*/
				$role = get_role('administrator');
				if ( $role !== null ) {
					$role->add_cap('racketmanager_settings');
					$role->add_cap('manage_racketmanager');
					$role->remove_cap('leaguemanager_settings');
					$role->remove_cap('manage_leaguemanager');
					$role->add_cap('racketmanager');
					$role->add_cap('racket_manager');
					$role->remove_cap('leaguemanager');
					$role->remove_cap('league_manager');
				}

				$role = get_role('editor');
				if ( $role !== null ) {
					$role->add_cap('racket_manager');
					$role->remove_cap('league_manager');
				}

		}
		if (version_compare($installed, '6.7.0', '<')) {
        echo __('starting 6.7.0 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->racketmanager_matches} ADD `comments` varchar( 500 ) NULL AFTER `away_captain` ");
    }
		if (version_compare($installed, '6.8.0', '<')) {
        echo __('starting 6.8.0 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->racketmanager_tournaments} ADD numcourts int( 1) NULL AFTER `tournamentsecretary` ");
				$wpdb->query( "ALTER TABLE {$wpdb->racketmanager_tournaments} ADD starttime time NULL AFTER `numcourts` ");
				$wpdb->query( "ALTER TABLE {$wpdb->racketmanager_tournaments} ADD timeincrement time NULL AFTER `starttime` ");
				$wpdb->query( "ALTER TABLE {$wpdb->racketmanager_tournaments} ADD orderofplay longtext NULL AFTER `timeincrement` ");
    }
		if (version_compare($installed, '6.10.0', '<')) {
        echo __('starting 6.10.0 upgrade', 'leaguemanager') . "<br />\n";
        $wpdb->query( "ALTER TABLE {$wpdb->racketmanager_roster_requests} ADD email varchar( 255 ) NULL AFTER `btm` ");
    }
		if (version_compare($installed, '6.13.0', '<')) {
        echo __('starting 6.13.0 upgrade', 'leaguemanager') . "<br />\n";
				$options = $racketmanager->getOptions();
				$competitionTypes = array();
				$rosters = array();
				$checks = array();
				$championship = array();
				foreach ($options as $option => $value) {
					if ( $option == 'matchCapability' || $option == 'matchCapabilityLeague' ) {
						$competitionTypes['league']['matchCapability'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'matchCapabilityCup' ) {
						$competitionTypes['cup']['matchCapability'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'matchCapabilityTournament' ) {
						$competitionTypes['tournament']['matchCapability'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultEntry' || $option == 'resultEntryLeague' ) {
						$competitionTypes['league']['resultEntry'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultEntryCup' ) {
						$competitionTypes['cup']['resultEntry'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultEntryTournament' ) {
						$competitionTypes['tournament']['resultEntry'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultConfirmation' || $option == 'resultConfirmationLeague' ) {
						$competitionTypes['league']['resultConfirmation'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultConfirmationCup' ) {
						$competitionTypes['cup']['resultConfirmation'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultConfirmationTournament' ) {
						$competitionTypes['tournament']['resultConfirmation'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultConfirmationEmail' || $option == 'resultConfirmationEmailLeague' ) {
						$competitionTypes['league']['resultConfirmationEmail'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultConfirmationEmailCup' ) {
						$competitionTypes['cup']['resultConfirmationEmail'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultConfirmationEmailTournament' ) {
						$competitionTypes['tournament']['resultConfirmationEmail'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultNotification' || $option == 'resultNotificationLeague' ) {
						$competitionTypes['league']['resultNotification'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultNotificationCup' ) {
						$competitionTypes['cup']['resultNotification'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'resultNotificationTournament' ) {
						$competitionTypes['tournament']['resultNotification'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'rosterConfirmation' ) {
						$rosters['rosterConfirmation'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'rosterConfirmationEmail' ) {
						$rosters['rosterConfirmationEmail'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'rosterLeadTime' ) {
						$checks['rosterLeadTime'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'playedRounds' ) {
						$checks['playedRounds'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'playerLocked' ) {
						$checks['playerLocked'] = $value;
						unset($options[$option]);
					}
					if ( $option == 'numRounds' ) {
						$championship['numRounds'] = $value;
						unset($options[$option]);
					}

				}
				$options['championship'] = $championship;
				$options['checks'] = $checks;
				$options['rosters'] = $rosters;
				$options['league'] = $competitionTypes['league'];
				$options['cup'] = $competitionTypes['cup'];
				$options['tournament'] = $competitionTypes['tournament'];
				update_option( 'leaguemanager', $options );
    }
		if (version_compare($installed, '6.17.0', '<')) {
        echo __('starting 6.17.0 upgrade', 'leaguemanager') . "<br />\n";
				$userId = get_current_user_id();
				$user = get_user_by('login','share.m6');
				if ( $user ) {
					$userData = array();
					$userData['ID'] = $user->ID;
					$userData['firstname'] = __('unregistered', 'racketmanager');
					$userData['surname'] = __('male player', 'racketmanager');
					$userData['user_login'] = __('unregistered.maleplayer', 'racketmanager');
					$userData['user_nicename'] = __('unregistered-male-player', 'racketmanager');
					$userData['display_name'] = __('Unregistered male player', 'racketmanager');
					$userData['nickname'] = __('unregistered.maleplayer', 'racketmanager');
					wp_update_user($userData);
				}
				$user = get_user_by('login','no pair.m1');
				if ( $user ) {
					$userData = array();
					$userData['ID'] = $user->ID;
					$userData['firstname'] = __('no', 'racketmanager');
					$userData['surname'] = __('male player', 'racketmanager');
					$userData['user_login'] = __('no.maleplayer', 'racketmanager');
					$userData['user_nicename'] = __('no-male-player', 'racketmanager');
					$userData['display_name'] = __('No male player', 'racketmanager');
					$userData['nickname'] = __('no.maleplayer', 'racketmanager');
					wp_update_user($userData);
				}
				for ($i=2; $i <= 6 ; $i++) {
					$userLogin = 'no pair.m'.$i;
					$user = get_user_by('login',$userLogin);
					if ( $user ) {
						$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_roster} SET `removed_date` = NOW(), `removed_user` = %d WHERE `player_id` = '%d'", $userId, $user->ID ) );
					}
				}
				$user = get_user_by('login','share.m1');
				if ( $user ) {
					$userData = array();
					$userData['ID'] = $user->ID;
					$userData['firstname'] = __('shared', 'racketmanager');
					$userData['surname'] = __('match', 'racketmanager');
					$userData['user_login'] = __('shared.match.male', 'racketmanager');
					$userData['user_nicename'] = __('shared-match', 'racketmanager');
					$userData['display_name'] = __('Shared Match', 'racketmanager');
					$userData['nickname'] = __('shared.match', 'racketmanager');
					wp_update_user($userData);
				}
				for ($i=2; $i <= 5 ; $i++) {
					$userLogin = 'share.m'.$i;
					$user = get_user_by('login',$userLogin);
					if ( $user ) {
						$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_roster} SET `removed_date` = NOW(), `removed_user` = %d WHERE `player_id` = '%d'", $userId, $user->ID ) );
					}
				}
				$user = get_user_by('login','walkover.m1');
				if ( $user ) {
					$userData = array();
					$userData['ID'] = $user->ID;
					$userData['firstname'] = __('walkover', 'racketmanager');
					$userData['surname'] = __('male', 'racketmanager');
					$userData['user_login'] = __('walkover.male', 'racketmanager');
					$userData['user_nicename'] = __('walkover-male', 'racketmanager');
					$userData['display_name'] = __('Walkover', 'racketmanager');
					$userData['nickname'] = __('walkover', 'racketmanager');
					wp_update_user($userData);
				}
				for ($i=2; $i <= 6 ; $i++) {
					$userLogin = 'walkover.m'.$i;
					$user = get_user_by('login',$userLogin);
					if ( $user ) {
						$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_roster} SET `removed_date` = NOW(), `removed_user` = %d WHERE `player_id` = '%d'", $userId, $user->ID ) );
					}
				}
				$user = get_user_by('login','share.f6');
				if ( $user ) {
					$userData = array();
					$userData['ID'] = $user->ID;
					$userData['firstname'] = __('unregistered', 'racketmanager');
					$userData['surname'] = __('female player', 'racketmanager');
					$userData['user_login'] = __('unregistered.femaleplayer', 'racketmanager');
					$userData['user_nicename'] = __('unregistered-female-player', 'racketmanager');
					$userData['display_name'] = __('Unregistered female player', 'racketmanager');
					$userData['nickname'] = __('unregistered.femaleplayer', 'racketmanager');
					wp_update_user($userData);
				}
				$user = get_user_by('login','no pair.f1');
				if ( $user ) {
					$userData = array();
					$userData['ID'] = $user->ID;
					$userData['firstname'] = __('no', 'racketmanager');
					$userData['surname'] = __('female player', 'racketmanager');
					$userData['user_login'] = __('no.femaleplayer', 'racketmanager');
					$userData['user_nicename'] = __('no-female-player', 'racketmanager');
					$userData['display_name'] = __('No female player', 'racketmanager');
					$userData['nickname'] = __('no.femaleplayer', 'racketmanager');
					wp_update_user($userData);
				}
				for ($i=2; $i <= 6 ; $i++) {
					$userLogin = 'no pair.f'.$i;
					$user = get_user_by('login',$userLogin);
					if ( $user ) {
						$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_roster} SET `removed_date` = NOW(), `removed_user` = %d WHERE `player_id` = '%d'", $userId, $user->ID ) );
					}
				}
				$user = get_user_by('login','share.f1');
				if ( $user ) {
					$userData = array();
					$userData['ID'] = $user->ID;
					$userData['firstname'] = __('shared', 'racketmanager');
					$userData['surname'] = __('match', 'racketmanager');
					$userData['user_login'] = __('shared.match.female', 'racketmanager');
					$userData['user_nicename'] = __('shared-match', 'racketmanager');
					$userData['display_name'] = __('Shared Match', 'racketmanager');
					$userData['nickname'] = __('shared.match', 'racketmanager');
					wp_update_user($userData);
				}
				for ($i=2; $i <= 5 ; $i++) {
					$userLogin = 'share.f'.$i;
					$user = get_user_by('login',$userLogin);
					if ( $user ) {
						$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_roster} SET `removed_date` = NOW(), `removed_user` = %d WHERE `player_id` = '%d'", $userId, $user->ID ) );
					}
				}
				$user = get_user_by('login','walkover.f1');
				if ( $user ) {
					$userData = array();
					$userData['ID'] = $user->ID;
					$userData['firstname'] = __('walkover', 'racketmanager');
					$userData['surname'] = __('female', 'racketmanager');
					$userData['user_login'] = __('walkover.female', 'racketmanager');
					$userData['user_nicename'] = __('walkover-female', 'racketmanager');
					$userData['display_name'] = __('Walkover', 'racketmanager');
					$userData['nickname'] = __('walkover', 'racketmanager');
					wp_update_user($userData);
				}
				for ($i=2; $i <= 6 ; $i++) {
					$userLogin = 'walkover.f'.$i;
					$user = get_user_by('login',$userLogin);
					if ( $user ) {
						$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_roster} SET `removed_date` = NOW(), `removed_user` = %d WHERE `player_id` = '%d'", $userId, $user->ID ) );
					}
				}
    }
		if (version_compare($installed, '6.18.0', '<')) {
			echo __('starting 6.18.0 upgrade', 'leaguemanager') . "<br />\n";
			$userId = get_current_user_id();
			$clubs = $wpdb->get_results(" SELECT `id` FROM {$wpdb->racketmanager_clubs}  ORDER BY `id`; ");

			foreach ($clubs AS $club) {
				//male shared
				$roster = $wpdb->get_results( $wpdb->prepare(" SELECT `id` FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = '%d' AND `player_id` = 1509;", $club->id) );
				if ( $roster ) {
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_1` = '%d' WHERE `home_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1509 and 1514 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_2` = '%d' WHERE `home_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1509 and 1514 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_1` = '%d' WHERE `away_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1509 and 1514 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_2` = '%d' WHERE `away_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1509 and 1514 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
				}
				//female shared
				$roster = $wpdb->get_results( $wpdb->prepare(" SELECT `id` FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = '%d' AND `player_id` = 1491;", $club->id) );
				if ( $roster ) {
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_1` = '%d' WHERE `home_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1491 and 1496 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_2` = '%d' WHERE `home_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1491 and 1496 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_1` = '%d' WHERE `away_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1491 and 1496 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_2` = '%d' WHERE `away_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1491 and 1496 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
				}
				//male walkover
				$roster = $wpdb->get_results( $wpdb->prepare(" SELECT `id` FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = '%d' AND `player_id` = 1497;", $club->id) );
				if ( $roster ) {
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_1` = '%d' WHERE `home_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1497 and 1502 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_2` = '%d' WHERE `home_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1497 and 1502 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_1` = '%d' WHERE `away_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1497 and 1502 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_2` = '%d' WHERE `away_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1497 and 1502 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
				}
				//female walkover
				$roster = $wpdb->get_results( $wpdb->prepare(" SELECT `id` FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = '%d' AND `player_id` = 1479;", $club->id) );
				if ( $roster ) {
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_1` = '%d' WHERE `home_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1479 and 1484 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_2` = '%d' WHERE `home_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1479 and 1484 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_1` = '%d' WHERE `away_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1479 and 1484 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_2` = '%d' WHERE `away_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1479 and 1484 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
				}
				//male no pair
				$roster = $wpdb->get_results( $wpdb->prepare(" SELECT `id` FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = '%d' AND `player_id` = 1503;", $club->id) );
				if ( $roster ) {
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_1` = '%d' WHERE `home_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1503 and 1508 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_2` = '%d' WHERE `home_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1503 and 1508 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_1` = '%d' WHERE `away_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1503 and 1508 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_2` = '%d' WHERE `away_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1503 and 1508 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
				}
				//female no pair
				$roster = $wpdb->get_results( $wpdb->prepare(" SELECT `id` FROM {$wpdb->racketmanager_roster} WHERE `affiliatedclub` = '%d' AND `player_id` = 1485;", $club->id) );
				if ( $roster ) {
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_1` = '%d' WHERE `home_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1485 and 1490 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `home_player_2` = '%d' WHERE `home_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1485 and 1490 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_1` = '%d' WHERE `away_player_1` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1485 and 1490 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
					$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `away_player_2` = '%d' WHERE `away_player_2` IN (select `id` from {$wpdb->racketmanager_roster} WHERE `player_id` between 1485 and 1490 AND `affiliatedclub` = '%d') ", $roster[0]->id, $club->id ));
				}
			}
		}
		if (version_compare($installed, '6.20.0', '<')) {
			echo __('starting 6.20.0 upgrade', 'racketmanager') . "<br />\n";
			$wpdb->query( "ALTER TABLE {$wpdb->racketmanager_rubbers} ADD type varchar( 2 ) NULL AFTER `final` ");
			$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `type` = 'WD' WHERE `match_id` in (SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` in (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = 'league' AND `type` = 'WD'))) "));
			$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `type` = 'MD' WHERE `match_id` in (SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` in (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = 'league' AND `type` = 'MD'))) "));
			$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `type` = 'XD' WHERE `match_id` in (SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` in (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = 'league' AND `type` = 'XD'))) "));
			$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `type` = 'WD' WHERE `rubber_number` = 1 AND `match_id` in (SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` in (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = 'league' AND `type` = 'LD'))) "));
			$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `type` = 'MD' WHERE `rubber_number` = 2 AND `match_id` in (SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` in (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = 'league' AND `type` = 'LD'))) "));
			$wpdb->query( $wpdb->prepare(" UPDATE {$wpdb->racketmanager_rubbers} SET `type` = 'XD' WHERE `rubber_number` = 3 AND `match_id` in (SELECT `id` from {$wpdb->racketmanager_matches} WHERE `league_id` in (SELECT `id` FROM {$wpdb->racketmanager} WHERE `competition_id` in (SELECT `id` FROM {$wpdb->racketmanager_competitions} WHERE `competitiontype` = 'league' AND `type` = 'LD'))) "));
		}
		if (version_compare($installed, '7.0.0', '<')) {
			echo __('starting 7.0.0 upgrade', 'racketmanager') . "<br />\n";
			$charset_collate = '';
			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty($wpdb->charset) ) {
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				}
				if ( ! empty($wpdb->collate) ) {
					$charset_collate .= " COLLATE $wpdb->collate";
				}
			}
			$wpdb->query( "CREATE TABLE {$wpdb->racketmanager_charges} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `type` varchar( 100 ) NOT NULL default '', `season` varchar( 255 ) NOT NULL default '', `date` date NULL, `status` varchar( 50 ) NOT NULL default '', PRIMARY KEY ( `id` )) $charset_collate;" );
		}
		if (version_compare($installed, '7.0.1', '<')) {
			echo __('starting 7.0.1 upgrade', 'racketmanager') . "<br />\n";
			$charset_collate = '';
			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty($wpdb->charset) ) {
					$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
				}
				if ( ! empty($wpdb->collate) ) {
					$charset_collate .= " COLLATE $wpdb->collate";
				}
			}
			$wpdb->query( "CREATE TABLE {$wpdb->racketmanager_invoices} ( `id` int( 11 ) NOT NULL AUTO_INCREMENT, `charge_id` int( 11 ) NOT NULL, `club_id` int( 11 ) NOT NULL, `invoiceNumber` int( 11 ) NOT NULL, `status` varchar( 50 ) NOT NULL, PRIMARY KEY ( `id` )) $charset_collate;" );
		}
		if (version_compare($installed, '7.0.2', '<')) {
			echo __('starting 7.0.2 upgrade', 'racketmanager') . "<br />\n";
			$wpdb->query( "ALTER TABLE {$wpdb->racketmanager_charges} ADD `feeClub` decimal(10,2) AFTER `status`");
			$wpdb->query( "ALTER TABLE {$wpdb->racketmanager_charges} ADD `feeTeam` decimal(10,2) AFTER `feeClub`");
		}
		if (version_compare($installed, '7.0.3', '<')) {
			echo __('starting 7.0.3 upgrade', 'racketmanager') . "<br />\n";
			$wpdb->query( "ALTER TABLE {$wpdb->racketmanager_charges} ADD `competitionType` varchar(255) AFTER `id`");
		}
  /*
	* Update version and dbversion
	*/
	$options['dbversion'] = RACKETMANAGER_DBVERSION;
	$options['version'] = RACKETMANAGER_VERSION;

	update_option('leaguemanager', $options);
	flush_rewrite_rules();
	echo __('finished', 'racketmanager') . "<br />\n";
	$wpdb->hide_errors();
	return;
}

/**
* racketmanager_upgrade_page() - This page showsup , when the database version doesn't fit to the script RACKETMANAGER_DBVERSION constant.
*
* @return Upgrade Message
*/
function racketmanager_upgrade_page()  {
	$filepath    = admin_url() . 'admin.php?page=' . htmlspecialchars($_GET['page']);

	if (isset($_GET['upgrade']) && $_GET['upgrade'] == 'now') {
		leaguemanager_do_upgrade($filepath);
		return;
	}
?>
	<div class="wrap">
		<h2><?php _e('Upgrade RacketManager', 'leaguemanager') ;?></h2>
		<p><?php _e('Your database for RacketManager is out-of-date, and must be upgraded before you can continue.', 'racketmanager'); ?>
		<p><?php _e('The upgrade process may take a while, so please be patient.', 'racketmanager'); ?></p>
		<h3><a class="button" href="<?php echo $filepath;?>&amp;upgrade=now"><?php _e('Start upgrade now', 'racketmanager'); ?>...</a></h3>
	</div>
	<?php
}

/**
 * leaguemanager_do_upgrade() - Proceed the upgrade routine
 *
 * @param mixed $filepath
 * @return void
 */
function leaguemanager_do_upgrade($filepath) {
	global $wpdb;
?>
<div class="wrap">
	<h2><?php _e('Upgrade RacketManager', 'racketmanager') ;?></h2>
	<p><?php racketmanager_upgrade();?></p>
	<p><?php _e('Upgrade successful', 'racketmanager') ;?></p>
	<h3><a class="button" href="<?php echo $filepath;?>"><?php _e('Continue', 'racketmanager'); ?>...</a></h3>
</div>
<?php
}

?>
