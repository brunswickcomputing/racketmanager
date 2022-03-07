<?php
/**
* Championship object
*
*/

add_filter( 'racketmanager_modes', 'racketmanager_championship_mode' );
/**
* add championship mode
*
* @param array $modes
* @return array
*/
function racketmanager_championship_mode( $modes ) {
  $modes['championship'] = __( 'Championship', 'racketmanager' );
  return $modes;
}

/**
* add settings
*
* @param Competition $competition
*/
function championship_settingsPage( $competition ) {
  if ( !isset($competition->settings['primary_league']) ) $competition->settings['primary_league'] = '';
  $leagues = $competition->getLeagues( array('competition' => $competition->id) ); ?>

  <div class="form-group">
    <div class="form-label">
      <label for='primary_league'><?php _e('Primary League', 'racketmanager') ?></label>
    </div>
    <div class="form-input">
      <select size="1" name="settings[primary_league] ?>" id="primary_league">;
        <option value=""><?php _e( 'Select', 'racketmanager') ?></option>
        <?php foreach($leagues AS $league) { ?>
          <option value="<?php echo $league->id ?>" <?php echo ($competition->settings['primary_league'] == $league->id ? 'selected' : '') ?> ><?php echo $league->title ?></option>
        <?php } ?>
      </select>
    </div>
  </div>
  <div class="form-group">
    <div class="form-label">
      <label for="groups"><?php _e( 'Groups', 'racketmanager' ) ?></label>
    </div>
    <div class="form-hint">
      <?php _e( 'Separate Groups by semicolon ;', 'racketmanager' ) ?>
    </div>
    <div class="form-input">
      <input type="text" name="settings[groups]" id="groups" size="20" value="<?php echo ((isset($competition->groups)) ? is_array($competition->groups) ? implode(";",$competition->groups) : $competition->groups  : '') ?>" />
    </div>
  </div>
  <div class="form-group">
    <div class="form-label">
      <label for="teams_per_group"><?php _e( 'Teams per group', 'racketmanager' ) ?></label>
    </div>
    <div class="form-input">
      <input type="text" name="settings[teams_per_group]" id="teams_per_group" size="3" value="<?php echo ((isset($competition->teams_per_group)) ? $competition->teams_per_group  : '') ?>" />
    </div>
  </div>
  <div class="form-group">
    <div class="form-label">
      <label for="num_advance"><?php _e('Teams Advance', 'racketmanager') ?></label>
    </div>
    <div class="form-input">
      <input type="text" size="3" id="num_advance" name="settings[num_advance]" value="<?php echo ((isset($competition->num_advance)) ? $competition->num_advance  : '') ?>" />
    </div>
  </div>
  <div class="form-group">
    <div class="form-check">
      <input type="checkbox" class="form-check-input" id="match_place3" name="settings[match_place3]" value="1" <?php echo (isset($competition->match_place3) && 1 == $competition->match_place3 ) ? ' checked="checked"' : ''; ?> />
      <label for="match_place3" class="form-check-label"><?php _e('Include 3rd place match', 'racketmanager' ) ?></label>
    </div>
  </div>
  <div class="form-group">
    <div class="form-check">
      <input type="checkbox" class="form-check-input" id="non_group" name="settings[non_group]" value="1" <?php echo (isset($competition->non_group) && 1 == $competition->non_group ) ? ' checked="checked"' : ''; ?> />
      <label for="non_group" class="form-check-label"><?php _e('Allow Non-Group Games', 'racketmanager' ) ?></label>
    </div>
  </div>
  <div class="form-group">
    <div class="form-check">
      <input type="checkbox" class="form-check-input" id="entry_open" name="settings[entry_open]" value="1" <?php echo (isset($competition->entry_open) && 1 == $competition->entry_open ) ? ' checked="checked"' : ''; ?> />
      <label for="entry_open" class="form-check-label"><?php _e('Open for entries', 'racketmanager' ) ?></label>
    </div>
  </div>
<?php }
add_action( 'competition_settings_championship', 'championship_settingsPage' );


/**
* Implement Championship mode
*
* @author 	Kolja Schleich
* @author  Paul Moffat
* @package	RacketManager
* @subpackage League_Championship
*/
final class League_Championship extends RacketManager {
  /**
  * League ID
  *
  * @var int
  */
  public $league_id = 0;

  /**
  * preliminary groups
  *
  * @var array
  */
  public $groups = array();

  /**
  * number of preliminary groups
  *
  * @var int
  */
  public $num_group = 0;

  /**
  * number of teams per group
  *
  * @var int
  */
  public $team_per_group = 0;

  /**
  * number of teams to advance to final rounds
  *
  * @var int
  */
  public $num_advance = 0;

  /**
  * number of final rounds
  *
  * @var int
  */
  public $num_rounds = 0;

  /**
  * number of teams in first round
  *
  * @var int
  */
  public $num_teams_first_round = 0;

  /**
  * final keys indexed by round
  *
  * @var array
  */
  private $keys = array();

  /**
  * finals indexed by key
  *
  * @var array
  */
  public $finals = array();

  /**
  * current final key
  *
  * @var array
  */
  public $current_final = '';

  /**
  * array of final team names
  *
  * @var array
  */
  public $final_teams = array();

  /**
  * image of cup icon
  *
  * @var string
  */
  public $cup_icon = '';

  /**
  * initialize Championship Mode
  *
  * @param none
  * @return void
  */
  public function __construct( $league, $settings ) {

    $this->league_id = $league->id;
    if ( isset($settings['groups']) && is_array($settings['groups']) ) $this->groups = $settings['groups'];
    $this->teams_per_group = isset($settings['teams_per_group']) ? intval($settings['teams_per_group']) : 4;
    $this->num_groups = count($this->groups);
    if ( $this->num_groups > 0 ) {
      $this->num_advance = isset($settings['num_advance']) ? $settings['num_advance'] : 0;
      $this->num_teams_first_round = $this->num_groups * $this->num_advance;
      $this->num_rounds = log($this->num_teams_first_round, 2);
    } else {
      $this->num_advance = $league->current_season['num_match_days'];
      $num_teams = $league->num_teams_total;
      if ( $num_teams < $league->current_season['num_match_days'] ) {
        $num_teams = $league->current_season['num_match_days'];
        $this->num_teams_first_round = $num_teams;
        $this->num_rounds = ceil(log($this->num_teams_first_round, 2));
      } else {
        $this->num_rounds = ceil(log($num_teams, 2));
        $this->num_teams_first_round = pow(2, $this->num_rounds);
      }
      $this->num_teams = $num_teams;
    }

    $num_teams = 2;
    $i = $this->num_rounds;
    while ( $num_teams <= $this->num_teams_first_round ) {
      $finalkey = $this->getFinalKey($num_teams);

      $num_matches = $num_teams/2;
      $is_final = ( $finalkey == 'final' ) ? true : false;
      $this->finals[$finalkey] = array(
        'key' => $finalkey,
        'is_final' => $is_final,
        'name' => $this->getFinalName($finalkey),
        'num_matches' => $num_matches,
        'num_teams' => $num_teams,
        'colspan' => ( $this->num_teams_first_round/2 >= 4 ) ? ceil(4/$num_matches) : ceil(($this->num_teams_first_round/2)/$num_matches),
        'round' => $i
      );

      // Separately add match for third place
      if ( $num_teams == 2 && (isset($settings['match_place3']) && $settings['match_place3'] == 1) ) {
        $finalkey = 'third';
        $this->finals[$finalkey] = array(
          'key' => $finalkey,
          'name' => $this->getFinalName($finalkey),
          'num_matches' => $num_matches,
          'num_teams' => $num_teams,
          'colspan' => ( $this->num_teams_first_round/2 >= 4 ) ? ceil(4/$num_matches) : ceil(($this->num_teams_first_round/2)/$num_matches),
          'round' => $i
        );
      }

      $this->keys[$i] = $finalkey;

      $i--;
      $num_teams = $num_teams * 2;
    }
    $this->setCurrentFinal();
    $this->setFinalTeams();

    $this->cup_icon = '<img style="vertical-align: middle;" src="'.RACKETMANAGER_URL . '/admin/icons/cup.png" />';
  }

  /**
  * get groups
  *
  * @return array
  */
  public function getGroups() {
    return $this->groups;
  }

  /**
  * get final key
  *
  * @param int $round
  * @return string
  */
  public function getFinalKeys( $round = false ) {
    if ( $round ) {
      if ( isset($this->keys[$round]) )
      return $this->keys[$round];

      return false;
    }

    return $this->keys;
  }

  /**
  * get final data
  *
  * @param int $round
  * @return mixed
  */
  public function getFinals( $key = false ) {
    if ( $key == 'current' )
    $key = $this->current_final;

    if ( $key )
    return $this->finals[$key];

    return $this->finals;
  }

  /**
  * get name of final depending on number of teams
  *
  * @param string $key
  * @return the name
  */
  public function getFinalName( $key = false ) {
    if ( empty($key) ) $key = $this->current_final;

    if (!empty($key)) {
      if ( 'final' == $key )
      return __( 'Final', 'racketmanager' );
      elseif ( 'third' == $key )
      return __( 'Third Place', 'racketmanager' );
      elseif ( 'semi' == $key )
      return __( 'Semi Final', 'racketmanager' );
      elseif ( 'quarter' == $key )
      return __( 'Quarter Final', 'racketmanager' );
      else {
        $tmp = explode("-", $key);
        return sprintf(__( 'Round of %d', 'racketmanager'), $tmp[1]);
      }
    }
  }

  /**
  * get key of final depending on number of teams
  *
  * @param int $num_teams
  * @return the key
  */
  private function getFinalKey( $num_teams ) {
    if ( 2 == $num_teams )
    return 'final';
    elseif ( 4 == $num_teams )
    return 'semi';
    elseif ( 8 == $num_teams )
    return 'quarter';
    else
    return 'last-'.$num_teams;
  }

  /**
  * set current final key
  *
  * @param string $final
  */
  public function setCurrentFinal($final = false) {
    if ( isset($_GET['final']) )
    $key = htmlspecialchars($_GET['final']);
    elseif ( $final )
    $key = htmlspecialchars($final);
    else
    $key = $this->getFinalKeys(1);

    $this->current_final =  $key;
  }


  /**
  * get current final key
  *
  * @return string
  */
  public function getCurrentFinalKey() {
    return $this->current_final;
  }

  /**
  * get number of matches for specific final
  *
  * @param string $finalkey
  * @return int
  */
  public function getNumMatches( $finalkey ) {
    if ( isset($this->finals[$finalkey]) )
    return $this->finals[$finalkey]['num_matches'];

    return 0;
  }

  /**
  * set general names for final rounds
  *
  */
  private function setFinalTeams() {
    // Final Rounds
    foreach ( $this->getFinals() AS $k => $data ) {
      $this->final_teams[$k] = array();

      if ($data['round'] > 1) {
        // get data of previous round
        $final = $this->getFinals( $this->getFinalKeys($data['round']-1) );

        for ( $x = 1; $x <= $final['num_matches']; $x++ ) {
          if ( $k == 'third' ) {
            $title = sprintf(__('Looser %s %d', 'racketmanager'), $final['name'], $x);
            $key = '2_'.$final['key'].'_'.$x;
          } else {
            $title = sprintf(__('Winner %s %d', 'racketmanager'), $final['name'], $x);
            $key = '1_'.$final['key'].'_'.$x;
          }

          $this->final_teams[$k][$key] = (object) array('id' => $key, 'title' => $title, 'home' => 0);
        }
      } else {
        // First Final Rounds
        if ( !empty($this->groups) ) {
          foreach ( $this->groups AS $group ) {
            for ( $a = 1; $a <= $this->num_advance; $a++ ) {
              $this->final_teams[$k][$a.'_'.$group] =    (object) array('id' => $a.'_'.$group, 'title' => sprintf(__('%d. Group %s', 'racketmanager'), $a, $group), 'home' => 0);
            }
          }
        } else {
          for ( $a = 1; $a <= $this->num_teams; $a++ ) {
            $this->final_teams[$k][$a.'_'] = (object) array('id' => $a.'_', 'title' => sprintf(__('Team Rank %d', 'racketmanager'), $a), 'home' => 0);
          }
          $this->final_teams[$k][$a] = (object) array('id' => '-1', 'title' => __('Bye', 'racketmanager'), 'home' => 0);
        }
      }
    }
  }

  /**
  * get final team names
  *
  * @param string $final
  * @return array
  */
  public function getFinalTeams( $final ) {
    return $this->final_teams[$final];
  }

  /**
  * update final rounds results
  *
  * @param int $league_id
  * @param array $matches
  * @param array $home_poinsts
  * @param array $away_points
  * @param array $home_team
  * @param array $away_team
  * @param array $custom
  * @param int $round
  * @param int $season
  */
  public function updateFinalResults( $matches, $home_points, $away_points, $home_team, $away_team, $custom, $round, $season ) {
    $league = get_league();
    $league->setFinals(true);
    $num_matches = $league->_updateResults($matches, $home_points, $away_points, $home_team, $away_team, $custom, $season, $round);

    if ( $round < $this->num_rounds )
    $this->proceed($this->getFinalKeys($round), $this->getFinalKeys($round+1));

    $this->setMessage( sprintf(__('Updated Results of %d matches','racketmanager'), $num_matches) );
  }

  /**
  * start final rounds
  *
  */
  public function startFinalRounds() {
    if ( is_admin() && current_user_can( 'update_results' ) ) {
      global $wpdb, $racketmanager;

      $league = get_league();

      $matches = $league->getMatches( array("final" => $this->getFinalKeys(1), "limit" => false, "match_day" => -1, "reset_query_args" => true) );
      foreach ( $matches AS $match ) {
        $update = true;

        if ($match->home_team == -1) {
          $home['team'] = -1;
          $home_team = array('id' => -1);
        } else {
          if ( strpos($match->home_team, "_") !== false ) {
            $home = explode("_", $match->home_team);
            $home = array( 'rank' => $home[0], 'group' => isset($home[1]) ? $home[1] : '' );
            $home_team = $league->getLeagueTeams( array("rank" => $home['rank'], "group" => $home['group'], "reset_query_args" => true) );
            if ( $home_team ) {
              $home['team'] = $home_team[0]->id;
              $match->home_team = $home['team'];
              $match->teams['home'] = $league->getTeamDtls($home_team[0]->id);
            }
          } else {
            $home_team = '';
          }
        }

        if ($match->away_team == -1) {
          $away['team'] = -1;
          $away_team = array('id' => -1);
        } else {
          if ( strpos($match->away_team, "_") !== false ) {
            $away = explode("_", $match->away_team);
            $away = array( 'rank' => $away[0], 'group' => isset($away[1]) ? $away[1] : '' );
            $away_team = $league->getLeagueTeams( array("rank" => $away['rank'], "group" => $away['group'], "reset_query_args" => true) );
            if ( $away_team )  {
              $away['team'] = $away_team[0]->id;
              $match->away_team = $away['team'];
              $match->teams['away'] = $league->getTeamDtls($away_team[0]->id);
            }
          } else {
            $away_team = '';
          }
        }

        if ( !$home_team || !$away_team ) {
          $update = false;
        }

        if ( $update ) {
          $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `home_team` = %d, `away_team` = %d WHERE `id` = %d", $home['team'], $away['team'], $match->id ) );
          if ( is_numeric($match->home_team) && is_numeric($match->away_team) ) {
            if ( isset($match->custom['host']) ) {
              $racketmanager->notifyNextMatchTeams($match);
            }
          }
        }
      }
    }
  }

  /**
  * proceed to next final round
  *
  * @param string $current current round
  * @param string $next next round
  * @param string $league_id
  * @return void
  */
  private function proceed( $current, $next ) {
    global $wpdb, $racketmanager;

    $league = get_league();
    $matches = $league->getMatches( array("final" => $next, "limit" => false) );

    foreach ( $matches AS $match ) {
      $update = true;
      $home = explode("_", $match->home_team);
      $away = explode("_", $match->away_team);

      if ( is_array($home) && is_array($away) ) {
        if ( isset($home[1]) ) {
          $col = ( $home[0] == 1 ) ? 'winner_id' : 'loser_id';
          $home = array( 'col' => $col, 'finalkey' => $home[1], 'no' => $home[2] );
        } else {
          $home['no'] = 0;
        }
        if ( isset($away[1]) ) {
          $col = ( $away[0] == 1 ) ? 'winner_id' : 'loser_id';
          $away = array( 'col' => $col, 'finalkey' => $away[1], 'no' => $away[2] );
        } else {
          $away['no'] = 0;
        }
        // get matches of current round

        $prev = $league->getMatches( array("final" => $current, "limit" => false, "orderby" => array("id" => "ASC")) );

        $home['team'] = 0;
        $away['team'] = 0;
        $new_home = '';
        $new_away = '';
        if ( isset($prev[$home['no']-1]) ) {
          $prev_home = $prev[$home['no']-1];
          $home['team'] = $prev_home->{$home['col']};
          if ( $prev_home->{$home['col']} == $prev_home->home_team) {
            $new_home = $prev_home->teams['home'];
          } else {
            $new_home = $prev_home->teams['away'];
          }
        }
        if ( isset($prev[$away['no']-1]) ) {
          $prev_away = $prev[$away['no']-1];
          $away['team'] = $prev_away->{$away['col']};
          if ( $prev_away->{$away['col']} == $prev_away->home_team) {
            $new_away = $prev_away->teams['home'];
          } else {
            $new_away = $prev_away->teams['away'];
          }
        }
        if ( $home['team'] == 0 && $away['team'] == 0 ) {
          $update = false;
        }

        if ( $update ) {
          if ( $home['team'] != 0 && $away['team'] != 0 ) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `home_team` = %d, `away_team` = %d WHERE `id` = %d", $home['team'], $away['team'], $match->id ) );
            $match->home_team = $home['team'];
            $match->away_team = $away['team'];
            $match->teams['home'] = $new_home;
            $match->teams['away'] = $new_away;
          } elseif ( $home['team'] != 0 && $away['team'] == 0 ) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `home_team` = %d WHERE `id` = %d", $home['team'], $match->id ) );
            $match->home_team = $home['team'];
            $match->teams['home'] = $new_home;
          } elseif ( $home['team'] == 0 && $away['team'] != 0 ) {
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `away_team` = %d WHERE `id` = %d", $away['team'], $match->id ) );
            $match->away_team = $away['team'];
            $match->teams['away'] = $new_away;
          }
          if ( is_numeric($match->home_team) && is_numeric($match->away_team) ) {
            if ( isset($match->custom['host']) ) {
              $racketmanager->notifyNextMatchTeams($match);
            }
          }
          // Set winners on final
          if ( $next == 'third' ) {
            $match = $league->getMatches( array_merge($match_args, array("final" => "final")) );
            $match = $match[0];
            $home_team = $prev_home->winner_id;
            $away_team = $prev_away->winner_id;
            $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->racketmanager_matches} SET `home_team`= %d, `away_team`= %d WHERE `id` = %d", $home_team, $away_team, $match->id ) );
          }
        }
      }
    }
  }

  /**
  * display administration panel
  *
  */
  public function displayAdminPage() {
    if ( is_admin() && current_user_can('view_leagues') ) {
      $league = get_league( );

      if ( isset($_POST['startFinals']) ) {
        if ( current_user_can( 'update_results' ) ) {
          $this->startFinalRounds($league->id);
        } else {
          echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
        }
      }

      if ( isset($_POST['updateFinalResults']) ) {
        if ( current_user_can( 'update_results' ) ) {
          $custom = isset($_POST['custom']) ? $_POST['custom'] : '';
          $this->updateFinalResults( $_POST['matches'], $_POST['home_points'], $_POST['away_points'], $_POST['home_team'], $_POST['away_team'], $custom, $_POST['round'], $_POST['season']);
        } else {
          $this->setMessage(__("You do not have sufficient permissions to access this page."), true);
        }
        $this->printMessage();
      }

      $class = 'alternate';
      if (count($this->groups) > 0) { $league->setGroup($this->groups[0]); }

      $tab = 'finalresults';
      if (isset($_REQUEST['league-tab'])) { $tab = $_REQUEST['league-tab']; }
      if (isset($_REQUEST['final'])) { $final = $_REQUEST['final']; }
      include_once( RACKETMANAGER_PATH . '/admin/championship.php' );
    } else {
      echo '<div class="error"><p style="text-align: center;">'.__("You do not have sufficient permissions to access this page.").'</p></div>';
    }
  }

}
?>
