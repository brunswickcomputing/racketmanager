<?php
/**
* invoice API: invoice class
*
* @author Paul Moffat
* @package RacketManager
* @subpackage invoice
*/

/**
* Class to implement the invoice object
*
*/
final class Invoice {
  public static function get_instance($invoice_id) {
    global $wpdb;
    if ( !$invoice_id ) {
      return false;
    }
    $invoice = wp_cache_get( $invoice_id, 'invoice' );

    if ( ! $invoice ) {
      $invoice = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `charge_id`, `club_id`, `status`, `invoiceNumber`, `date`, `date_due` FROM {$wpdb->racketmanager_invoices} WHERE `id` = '%d' LIMIT 1", $invoice_id ) );

      if ( !$invoice ) {
        return false;
      }

      $invoice = new invoice( $invoice );

      wp_cache_set( $invoice->id, $invoice, 'invoice' );
    }

    return $invoice;
  }

  public function __construct( $invoice = null ) {
    if ( !is_null($invoice) ) {
      foreach ( get_object_vars( $invoice ) as $key => $value ) {
        $this->$key = $value;
      }

      if ( !isset($this->id) && isset($this->invoiceNumber) ) {
        $this->add();
      }

      $this->club = get_club($this->club_id);
      $this->charge = get_charges($this->charge_id);
    }
    return $this;
  }

  private function add() {
    global $wpdb;

		$wpdb->query( $wpdb->prepare ( "INSERT INTO {$wpdb->racketmanager_invoices} (`charge_id`, `club_id`, `status`, `invoiceNumber`, `date`, `date_due`) VALUES (%d, %d, '%s', %d, '%s', '%s')", $this->charge_id, $this->club_id, $this->status, $this->invoiceNumber, $this->date, $this->date_due ) );
    $this->id = $wpdb->insert_id;
  }

  public function setStatus($status) {
    global $wpdb;

    if ( $status == 'resent' ) {
      $email = $this->send($status);
      if ( !$email ) {
        return false;
      }
    }
    $wpdb->query( $wpdb->prepare ( "UPDATE {$wpdb->racketmanager_invoices} set `status` = '%s' WHERE `id` = %d", $status, $this->id ) );
    wp_cache_delete( $this->id, 'invoice' );
    return true;
  }

  public function generate(){
    global $racketmanager_shortcodes, $racketmanager;
    $charge = get_charges($this->charge);
    $club = get_club($this->club);
    $entry = $charge->getClubEntry($club);
    $billing = $racketmanager->getOptions('billing');
		return $racketmanager_shortcodes->loadTemplate( 'invoice', array( 'organisationName' => $racketmanager->site_name, 'invoice' => $this, 'entry' => $entry, 'club' => $club, 'billing' => $billing, 'invoiceNumber' => $this->invoiceNumber ) );
  }

  public function send($resend = false) {
		global $racketmanager_shortcodes, $racketmanager;

    if ( $resend) { $resent = true;}
    $billing = $racketmanager->getOptions('billing');
		$headers = array();
		$fromEmail = $racketmanager->getConfirmationEmail($this->charge->competitionType);
		if ( $fromEmail ) {
			$headers[] = 'From: '.ucfirst($this->charge->competitionType).'Secretary <'.$fromEmail.'>';
			$headers[] = 'cc: '.ucfirst($this->charge->competitionType).'Secretary <'.$fromEmail.'>';
			$organisationName = $racketmanager->site_name;
			$headers[] = 'cc: Treasurer <'.$billing['billingEmail'].'>';
			$actionURL = $racketmanager->site_url.'/invoice/'.$this->id.'/';
			$emailTo = $this->club->matchSecretaryName.' <'.$this->club->matchSecretaryEmail.'>';
			$emailSubject = $racketmanager->site_name." - ".ucfirst($this->charge->type)." ".$this->charge->season." ".ucfirst($this->charge->competitionType)." Entry Fees Invoice - ".$this->club->name;
			$emailMessage = $racketmanager_shortcodes->loadTemplate( 'send-invoice', array( 'emailSubject' => $emailSubject, 'actionURL' => $actionURL, 'organisationName' => $organisationName, 'invoice' => $this, 'invoiceView' => $this->generate(), 'resend' => $resend ), 'email' );
			wp_mail($emailTo, $emailSubject, $emailMessage, $headers);
			return true;
		} else {
			return false;
		}
	}

}

/**
* get invoice object
*
* @param int|invoice|null invoice ID or invoice object. Defaults to global $invoice
* @return object invoice|null
*/
function get_invoice( $invoice = null ) {
  if ( empty( $invoice ) && isset( $GLOBALS['invoice'] ) ) {
    $invoice = $GLOBALS['invoice'];
  }

  if ( $invoice instanceof invoice ) {
    $_invoice = $invoice;
  } elseif ( is_object( $invoice ) ) {
    $_invoice = new invoice( $invoice );
  } else {
    $_invoice = invoice::get_instance( $invoice );
  }

  if ( ! $_invoice ) {
    return null;
  }

  return $_invoice;
}
