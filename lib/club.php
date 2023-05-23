<?php

/**
 * Club API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club
 */

/**
 * Class to implement the Club object
 *
 */
final class Club
{

  /**
   * retrieve club instance
   *
   * @param int $club_id
   */
  public static function get_instance($club_id, $queryTerm = "id")
  {
    global $wpdb;

    switch ($queryTerm) {
      case "name":
        $search = "`name` = '%s'";
        break;
      case "shortcode":
        $search = "`shortcode` = '%s'";
        break;
      case "id":
      default:
        $club_id = (int) $club_id;
        $search = "`id` = '%d'";
        break;
    }

    if (!$club_id) {
      return false;
    }

    $club = wp_cache_get($club_id, 'clubs');

    if (!$club) {
      $club = $wpdb->get_row($wpdb->prepare("SELECT `id`, `name`, `website`, `type`, `address`, `latitude`, `longitude`, `contactno`, `founded`, `facilities`, `shortcode`, `matchsecretary` FROM {$wpdb->racketmanager_clubs} WHERE " . $search . " LIMIT 1", $club_id));

      if (!$club) {
        return false;
      }

      $club = new Club($club);

      wp_cache_set($club->id, $club, 'clubs');
    }

    return $club;
  }

  /**
   * Constructor
   *
   * @param object $club Club object.
   */
  public function __construct($club = null)
  {
    if (!is_null($club)) {

      foreach (get_object_vars($club) as $key => $value) {
        $this->$key = $value;
      }

      if (!isset($this->id)) {
        $this->add();
      }
      $this->matchSecretaryName = '';
      $this->matchSecretaryEmail = '';
      $this->matchSecretaryContactNo = '';
      if (isset($this->matchsecretary) && $this->matchsecretary != '0') {
        $matchSecretaryDtls = get_userdata($this->matchsecretary);
        if ($matchSecretaryDtls) {
          $this->matchSecretaryName = $matchSecretaryDtls->display_name;
          $this->matchSecretaryEmail = $matchSecretaryDtls->user_email;
          $this->matchSecretaryContactNo = get_user_meta($this->matchsecretary, 'contactno', true);
        }
      }
      $this->desc = '';
    }
  }

  /**
   * create club in database
   *
   */
  private function add()
  {
    global $wpdb;

    $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->racketmanager_clubs} (`name`, `type`, `shortcode`, `contactno`, `website`, `founded`, `facilities`, `address`, `latitude`, `longitude`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s','%s' )", $this->name, $this->type, $this->shortcode, $this->contactno, $this->website, $this->founded, $this->facilities, $this->address, $this->latitude, $this->longitude));
    $this->id = $wpdb->insert_id;
  }

  /**
   * update club
   *
   * @param object $club
   * @param string $oldShortcode
   * @return null
   */
  public function update($club, $oldShortcode)
  {
    global $wpdb;

    $wpdb->query($wpdb->prepare("UPDATE {$wpdb->racketmanager_clubs} SET `name` = '%s', `type` = '%s', `shortcode` = '%s',`matchsecretary` = '%d', `contactno` = '%s', `website` = '%s', `founded`= '%s', `facilities` = '%s', `address` = '%s', `latitude` = '%s', `longitude` = '%s' WHERE `id` = %d", $club->name, $club->type, $club->shortcode, $club->matchsecretary, $club->contactno, $club->website, $club->founded, $club->facilities, $club->address, $club->latitude, $club->longitude, $this->id));

    if ($oldShortcode != $this->shortcode) {
      $teams = $this->getTeams();
      foreach ($teams as $team) {
        $team = get_team($team->id);
        $teamRef = substr($team->title, strlen($oldShortcode) + 1, strlen($team->title));
        $newTitle = $club->shortcode . ' ' . $teamRef;
        $team->updateTitle($newTitle);
      }
    }
    if ($club->matchsecretary != '') {
      $currentContactNo = get_user_meta($club->matchsecretary, 'contactno', true);
      $currentContactEmail = get_userdata($club->matchsecretary)->user_email;
      if ($currentContactNo != $club->matchSecretaryContactNo) {
        update_user_meta($club->matchsecretary, 'contactno', $club->matchSecretaryContactNo);
      }
      if ($currentContactEmail != $club->matchSecretaryEmail) {
        $userdata = array();
        $userdata['ID'] = $club->matchsecretary;
        $userdata['user_email'] = $club->matchSecretaryEmail;
        $userId = wp_update_user($userdata);
        if (is_wp_error($userId)) {
          $errorMsg = $userId->get_error_message();
          error_log('Unable to update user email ' . $club->matchsecretary . ' - ' . $club->matchSecretaryEmail . ' - ' . $errorMsg);
        }
      }
    }
  }

  /**
   * delete Club
   *
   */
  public function delete()
  {
    global $wpdb;

    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->racketmanager_club_player_requests} WHERE `affiliatedclub` = '%d'", $this->id));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->racketmanager_club_players} WHERE `affiliatedclub` = '%d'", $this->id));
    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->racketmanager_clubs} WHERE `id` = '%d'", $this->id));
  }

  /**
   * get teams from database
   *
   * @return count number of teams
   */
  public function hasTeams()
  {
    global $wpdb;

    $args = array();
    $sql = "SELECT count(*) FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = '%d'";
    $args[] = intval($this->id);
    $sql = $wpdb->prepare($sql, $args);

    return $wpdb->get_var($sql);
  }

  /**
   * get teams from database
   *
   * @param array $args
   * @param string $output OBJECT | ARRAY
   * @return array database results
   */
  public function getTeams($players = false, $type = false)
  {
    global $wpdb;

    $args = array();
    $sql = "SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = '%d'";
    $args[] = intval($this->id);
    if (!$players) {
      $sql .= " AND `status` != 'P'";
    } else {
      $sql .= " AND `status` = 'P'";
    }
    if ($type) {
      $sql .= " AND `type` = '%s'";
      $args[] = $type;
    }

    $sql .= " ORDER BY `title`";
    $sql = $wpdb->prepare($sql, $args);

    $teams = wp_cache_get(md5($sql), 'teams');
    if (!$teams) {
      $teams = $wpdb->get_results($sql);
      wp_cache_set(md5($sql), $teams, 'teams');
    }

    $class = '';
    foreach ($teams as $i => $team) {
      $class = ('alternate' == $class) ? '' : 'alternate';
      $team = get_team($team->id);
      $teams[$i] = $team;
    }

    return $teams;
  }

  /**
   * get single player request
   *
   * @param int $playerRequestId
   * @return array
   */
  private function getPlayerRequest($playerRequestId)
  {
    global $wpdb;

    $playerRequest = $wpdb->get_row("SELECT `first_name`, `surname`, `gender`, `btm`, `email`, `player_id`, `requested_date`, `requested_user`, `completed_date`, `completed_user` FROM {$wpdb->racketmanager_club_player_requests} WHERE `id` = '" . intval($playerRequestId) . "'");

    if (!$playerRequest) {
      return false;
    }

    return $playerRequest;
  }

  /**
   * approve Club Player Request
   *
   * @param int $playerRequestId
   * @return boolean
   */
  public function approvePlayerRequest($playerRequestId)
  {
    global $wpdb, $racketmanager;

    $playerRequest = $this->getPlayerRequest($playerRequestId);
    if (empty($playerRequest->completed_date)) {
      $this->addClubPlayer($playerRequest->player_id, false);
      $wpdb->query($wpdb->prepare("UPDATE {$wpdb->racketmanager_club_player_requests} SET `completed_date` = now(), `completed_user` = %d WHERE `id` = %d ", get_current_user_id(), $playerRequestId));
      $racketmanager->setMessage(__('Player added to club', 'racketmanager'));
    }

    return true;
  }

  /**
   * register player for Club
   *
   * @param integer $player
   */
  public function registerPlayer($newPlayer)
  {
    global $racketmanager;
    $player = get_player($newPlayer->user_login, 'name');
    if ( !$player ) {
      $player = new Player($newPlayer);
    }
    $playerActive = $this->playerActive($player->id);
    if (!$playerActive) {
      $playerPending = $this->isPlayerPending($player->id);
      if ($playerPending) {
        $racketmanager->setMessage(__('Player registration already pending', 'racketmanager'), true);
      } else {
        $playerRequestId = $this->addPlayerRequest($player->id);
        if (current_user_can('edit_teams')) {
          $this->approvePlayerRequest($playerRequestId);
        } else {
          $options = $racketmanager->getOptions('rosters');
          if ($options['rosterConfirmation'] == 'auto') {
            $this->approvePlayerRequest($playerRequestId);
            $action = 'add';
            $msg = __('Player added to club', 'racketmanager');
          } else {
            $action = 'request';
            $msg = __('Player registration pending', 'racketmanager');
          }
          if (isset($options['rosterConfirmationEmail']) && !is_null($options['rosterConfirmationEmail'])) {
            $clubName = $this->name;
            $emailTo = $options['rosterConfirmationEmail'];
            $messageArgs = array();
            $messageArgs['action'] = $action;
            $messageArgs['club'] = $clubName;
            $messageArgs['player'] = $player->fullname;
            $headers = array();
            $headers['from'] = $racketmanager->getFromUserEmail();
            $subject = $racketmanager->site_name . " - " . $msg . " - " . $clubName;
            $message = racketmanager_club_players_notification($messageArgs);
            wp_mail($emailTo, $subject, $message, $headers);
          }
          $racketmanager->setMessage($msg);
        }
      }
    } else {
      $racketmanager->setMessage(__('Player already registered', 'racketmanager'), true);
    }
  }

  /**
   * check for player registered active
   *
   * @return boolean is player registered active for club
   */
  public function playerActive($player)
  {
    global $wpdb;

    $args = array();
    $sql = "SELECT count(*) FROM {$wpdb->racketmanager_club_players} WHERE `affiliatedclub` = %d AND `player_id` = %d AND `removed_date` IS NULL";
    $args[] = intval($this->id);
    $args[] = intval($player);
    $sql = $wpdb->prepare($sql, $args);

    return $wpdb->get_var($sql);
  }

  /**
   * check for player pending registration
   *
   * @return boolean is player pending registration for club
   */
  public function isPlayerPending($player)
  {
    global $wpdb;

    $args = array();
    $sql = "SELECT count(*) FROM {$wpdb->racketmanager_club_player_requests} WHERE `affiliatedclub` = %d AND `player_id` = %d AND `completed_date` IS NULL";
    $args[] = intval($this->id);
    $args[] = intval($player);
    $sql = $wpdb->prepare($sql, $args);

    return $wpdb->get_var($sql);
  }

  /**
   * add new club player
   *
   * @param int $affiliatedclub
   * @param int $playerid
   * @param boolean $message (optional)
   * @return int | false
   */
  public function addClubPlayer($player_id)
  {
    global $wpdb, $racketmanager;

    $userid = get_current_user_id();
    $sql = "INSERT INTO {$wpdb->racketmanager_club_players} (`affiliatedclub`, `player_id`, `created_date`, `created_user` ) VALUES ('%d', '%d', now(), %d)";
    $wpdb->query($wpdb->prepare($sql, $this->id, $player_id, $userid));
    $clubPlayerId = $wpdb->insert_id;

    $racketmanager->setMessage(__('Club Player added', 'racketmanager'));

    return $clubPlayerId;
  }

  /**
   * add new player request
   *
   * @param int $player id
   * @return int player request id
   */
  public function addPlayerRequest($player)
  {
    global $wpdb, $racketmanager;

    $userid = get_current_user_id();
    $sql = "INSERT INTO {$wpdb->racketmanager_club_player_requests} (`affiliatedClub`, `first_name`, `surname`, `gender`, `player_id`, `requested_date`, `requested_user`) values (%d, '%s', '%s', '%s', %d, now(), %d)";
    $wpdb->query($wpdb->prepare($sql, $this->id, '', '', '', $player, $userid));
    $playerRequestId = $wpdb->insert_id;

    $racketmanager->setMessage(__('Player request added', 'racketmanager'));

    return $playerRequestId;
  }

  /**
   * gets club players from database
   *
   * @param array $query_args
   * @return array
   */
  public function getPlayers($args)
  {
    global $wpdb;

    $defaults = array('count' => false, 'team' => false, 'player' => false, 'gender' => false, 'active' => false, 'cache' => true, 'type' => false, 'orderby' => array("display_name" => "ASC"));
    $args = array_merge($defaults, (array)$args);
    extract($args, EXTR_SKIP);

    $search_terms = array();
    if ($team) {
      $search_terms[] = $wpdb->prepare("`affiliatedclub` in (select `affiliatedclub` from {$wpdb->racketmanager_teams} where `id` = '%d')", intval($team));
    }

    if ($player) {
      $search_terms[] = $wpdb->prepare("`player_id` = '%d'", intval($player));
    }

    if ($gender) {
      $gender = htmlspecialchars(strip_tags($gender));
      $search_terms[] = $wpdb->prepare("'%s' = '%s'", $gender, $gender);
    }

    if ($type) {
      $search_terms[] = "`system_record` IS NULL";
    }

    if ($active) {
      $search_terms[] = "`removed_date` IS NULL";
    }

    $search = "";
    if (!empty($search_terms)) {
      $search = implode(" AND ", $search_terms);
    }

    $orderby_string = "";
    $i = 0;
    foreach ($orderby as $order => $direction) {
      if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
        $direction = "ASC";
      }
      $orderby_string .= "`" . $order . "` " . $direction;
      if ($i < (count($orderby) - 1)) {
        $orderby_string .= ",";
      }
      $i++;
    }
    $order = $orderby_string;

    if ($count) {
      $sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_club_players} WHERE `affiliatedclub` = " . $this->id;
      if ($search != "") {
        $sql .= " AND $search";
      }
      $cachekey = md5($sql);
      if (isset($this->num_players[$cachekey]) && $cache && $count) {
        return intval($this->num_players[$cachekey]);
      } else {
        $this->num_players[$cachekey] = $wpdb->get_var($sql);
        return $this->num_players[$cachekey];
      }
    }

    $sql = "SELECT A.`id` as `roster_id`, A.`player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_club_players} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID` WHERE `affiliatedclub` = " . $this->id;
    if ($search != "") {
      $sql .= " AND $search";
    }
    if ($order != "") {
      $sql .= " ORDER BY $order";
    }

    $players = wp_cache_get(md5($sql), 'clubplayers');
    if (!$players) {
      $players = $wpdb->get_results($sql);
      $i = 0;
      $class = '';
      foreach ($players as $player) {
        $class = ('alternate' == $class) ? '' : 'alternate';
        $players[$i]->class = $class;
        $players[$i]->roster_id = $player->roster_id;
        $players[$i]->player_id = $player->player_id;
        $players[$i]->gender = get_user_meta($player->player_id, 'gender', true);
        if ($gender && $gender != $players[$i]->gender) {
          unset($players[$i]);
        } else {
          $players[$i]->removed_date = $player->removed_date;
          $players[$i]->removed_user = $player->removed_user;
          if ($player->removed_user) {
            $players[$i]->removedUserName = get_userdata($player->removed_user)->display_name;
          } else {
            $players[$i]->removedUserName = '';
          }
          $players[$i]->created_date = $player->created_date;
          $players[$i]->created_user = $player->created_user;
          if ($player->created_user) {
            $players[$i]->createdUserName = get_userdata($player->created_user)->display_name;
          } else {
            $players[$i]->createdUserName = '';
          }
          $player = get_player($player->player_id);
          $players[$i]->fullname = $player->display_name;
          $players[$i]->type = $player->type;
          $players[$i]->btm = $player->btm;
          $players[$i]->email = $player->user_email;
          $players[$i]->locked = $player->locked;
          $players[$i]->locked_date = $player->locked_date;
          $players[$i]->locked_user = $player->locked_user;
          $players[$i]->lockedUserName = $player->lockedUserName;
        }

        $i++;
      }
      wp_cache_set(md5($sql), $players, 'clubplayers');
    }

    return $players;
  }

  /**
   * gets player for club from database
   *
   * @param array $playerId
   * @return array
   */
  public function getPlayer($playerId)
  {
    global $wpdb;

    $sql = "SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_club_players} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID` WHERE `affiliatedclub` = " . $this->id . " AND `player_id` = " . intval($playerId);

    $player = wp_cache_get(md5($sql), 'players');
    if (!$player) {
      $player = $wpdb->get_row($sql);
      wp_cache_set(md5($sql), $player, 'players');
    }

    if ($player) {
      $player->gender = get_user_meta($player->player_id, 'gender', true);
      $player->type = get_user_meta($player->player_id, 'racketmanager_type', true);
      if ($player->removed_user) {
        $player->removedUserName = get_userdata($player->removed_user)->display_name;
      } else {
        $player->removedUserName = '';
      }
      $player->btm = get_user_meta($player->player_id, 'btm', true);
      if ($player->created_user) {
        $player->createdUserName = get_userdata($player->created_user)->display_name;
      } else {
        $player->createdUserName = '';
      }
      $player->locked = get_user_meta($player->player_id, 'locked', true);
      $player->locked_date = get_user_meta($player->player_id, 'locked_date', true);
      $player->locked_user = get_user_meta($player->player_id, 'locked_user', true);
      if ($player->locked_user) {
        $player->lockedUserName = get_userdata($player->locked_user)->display_name;
      } else {
        $player->lockedUserName = '';
      }
    }

    return $player;
  }

  /**
   * check if player is captain
   *
   * @param int $player
   * @return boolean
   */
  public function isPlayerCaptain($player)
  {
    global $wpdb;

    $args = array();
    $sql = "SELECT count(*) FROM {$wpdb->racketmanager_team_competition} tc, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager_clubs} c WHERE c.`id` = '%d' AND c.`id` = t.`affiliatedclub` AND t.`status` != 'P' AND t.`id` = tc.`team_id` AND tc.`captain` = %d";
    $args[] = intval($this->id);
    $args[] = intval($player);
    $sql = $wpdb->prepare($sql, $args);

    return $wpdb->get_var($sql);
  }
}

/**
 * get Club object
 *
 * @param int|Club|null Club ID or club object. Defaults to global $club
 * @return object club|null
 */
function get_club($club = null, $queryTerm = "id")
{
  if (empty($club) && isset($GLOBALS['club'])) {
    $club = $GLOBALS['club'];
  }

  if ($club instanceof Club) {
    $_club = $club;
  } elseif (is_object($club)) {
    $_club = new Club($club);
  } else {
    $_club = Club::get_instance($club, $queryTerm);
  }

  if (!$_club) {
    return null;
  } else {
    return $_club;
  }
}
