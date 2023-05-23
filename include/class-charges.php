<?php
/**
* Charges API: Charges class
*
* @author Paul Moffat
* @package RacketManager
* @subpackage Charges
*/

/**
* Class to implement the charges object
*
*/
final class Charges {
  public static function get_instance($charges_id) {
    global $wpdb;
    if ( !$charges_id ) {
      return false;
    }
    $charges = wp_cache_get( $charges_id, 'charges' );

    if ( ! $charges ) {
      $charges = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `competitionType`, `type`, `season`, `status`, `date`, `feeClub`, `feeTeam` FROM {$wpdb->racketmanager_charges} WHERE `id` = '%d' LIMIT 1", $charges_id ) );

      if ( !$charges ) {
        return false;
      }

      $charges = new Charges( $charges );

      wp_cache_set( $charges->id, $charges, 'charges' );
    }

    return $charges;
  }

  public function __construct( $charges = null ) {
    if ( !is_null($charges) ) {
      foreach ( get_object_vars( $charges ) as $key => $value ) {
        $this->$key = $value;
      }

      if ( !isset($this->id) ) {
        $this->add();
      }
    }
    return $this;
  }

  private function add() {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_charges} (`season`, `type`, `competitionType`, `status`, `date`, `feeClub`, `feeTeam`) VALUES ('%s', '%s', '%s', '%s', '%s', %d, %d)", $this->season, $this->type, $this->competitionType, $this->status, $this->date, $this->feeClub, $this->feeTeam ) );
    $this->id = $wpdb->insert_id;
  }

  public function setStatus($status) {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_charges} set `status` = '%s' WHERE `id` = %d", $status, $this->id ) );
    wp_cache_delete( $this->id, 'charges' );
  }

  public function setFeeClub($feeClub) {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_charges} set `feeClub` = %d WHERE `id` = %d", $feeClub, $this->id ) );
    wp_cache_delete( $this->id, 'charges' );
  }

  public function setFeeTeam($feeTeam) {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_charges} set `feeTeam` = %d WHERE `id` = %d", $feeTeam, $this->id ) );
    wp_cache_delete( $this->id, 'charges' );
  }

  public function setType($type) {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_charges} set `type` = '%s' WHERE `id` = %d", $type, $this->id ) );
    wp_cache_delete( $this->id, 'charges' );
  }

  public function setCompetitionType($type) {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_charges} set `competitionType` = '%s' WHERE `id` = %d", $type, $this->id ) );
    wp_cache_delete( $this->id, 'charges' );
  }

  public function setSeason($season) {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_charges} set `season` = '%s' WHERE `id` = %d", $season, $this->id ) );
    wp_cache_delete( $this->id, 'charges' );
  }

  public function setDate($date) {
    global $wpdb;

    $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_charges} set `date` = '%s' WHERE `id` = %d", $date, $this->id ) );
    wp_cache_delete( $this->id, 'charges' );
  }

	public function delete() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->racketmanager_charges} WHERE `id` = '%d'", $this->id) );
	}

  public function hasInvoices() {
    global $wpdb;

    return $wpdb->get_var( $wpdb->prepare("SELECT count(*) FROM {$wpdb->racketmanager_invoices} WHERE `charge_id` = '%d'", $this->id) );
  }

  public function getClubEntries() {
    global $racketmanager;
    $clubEntries = array();
    $clubs = $racketmanager->getClubs();
    foreach ($clubs as $club) {
      $clubEntry = $this->getClubEntry($club);
      if ( $clubEntry ) {
        $clubEntries[] = $clubEntry;
      }
    }
    return $clubEntries;
  }

  public function getClubEntry($club) {
    global $racketmanager;
    $clubTeams = 0;
    $clubCompetitions = array();
    $competitions = $racketmanager->getCompetitions( array('name' => $this->type, 'type' => $this->competitionType) );
    foreach ($competitions as $competition) {
      $competition = get_competition($competition);
      $numTeams = $competition->getTeams(array('club' => $club->id, 'season' => $this->season, 'count' => true ));
      if ( $numTeams > 0 ) {
        $clubCompetition = new stdClass();
        $clubCompetition->type = $competition->type;
        $clubCompetition->count = $numTeams;
        $clubCompetition->fee = $this->feeTeam * $numTeams;
        $clubCompetitions[] = $clubCompetition;
      }
      $clubTeams += $numTeams;
    }
    if ( $clubTeams > 0 ) {
      $clubEntry = new stdClass();
      $clubEntry->id = $club->id;
      $clubEntry->name = $club->name;
      $clubEntry->numTeams = $clubTeams;
      $clubEntry->feeClub = $this->feeClub;
      $clubEntry->feeTeams = $this->feeTeam * $clubTeams;
      $clubEntry->fee = $clubEntry->feeClub + $clubEntry->feeTeams;
      $clubEntry->competitions = $clubCompetitions;
      return $clubEntry;
    } else {
      return false;
    }
  }
}

/**
* get Charges object
*
* @param int|charges|null Charges ID or charges object. Defaults to global $charges
* @return object charges|null
*/
function get_charges( $charges = null ) {
  if ( empty( $charges ) && isset( $GLOBALS['charges'] ) ) {
    $charges = $GLOBALS['charges'];
  }

  if ( $charges instanceof Charges ) {
    $_charges = $charges;
  } elseif ( is_object( $charges ) ) {
    $_charges = new Charges( $charges );
  } else {
    $_charges = Charges::get_instance( $charges );
  }

  if ( ! $_charges ) {
    return null;
  }

  return $_charges;
}
