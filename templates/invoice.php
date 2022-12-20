<?php
global $racketmanager;
$fmt = numfmt_create( get_locale(), NumberFormatter::CURRENCY );
if ( !isset($invoiceNumber) ) {
  $invoiceNumber = $billing['invoiceNumber'];
}
?>
<?php if ( is_user_logged_in() ) { ?>
  <style>
  .invoice-item-detail {
    width: 40%;
    display: inline;
  }

  .invoice-item-quantity {
    width: 20%;
    text-align: right;
  }

  .invoice-item-unit-price {
    width: 20%;
    text-align: right;
  }

  .invoice-item-net-price {
    width: 20%;
    text-align: right;
  }

  div#invoice-item {
    display: flex;
    line-height: 2em;
  }

  .invoice-total-desc {
    width: 80%;
    text-align: right;
  }

  div#invoice-totals {
    display: flex;
    font-weight: bold;
  }

  #invoice-amount #header-row {
    display: flex;
    font-weight: bold;
  }

  #invoice-header #company-address {
    text-align: right;
  }

  div#invoice-info {
    margin: .7cm 0 20px;
    float: right;
    text-align: right;
    width: 50%;
  }
  #invoice-header {
    border-bottom: 1px solid #006800;
    margin-top: 1.5cm;
    overflow: hidden;
    padding-bottom: 10px;
  }
    div#invoice {
      width: 660px;
    }
    div#client-details {
      margin: 0.7cm 0 20px;
      float: left;
      width: 50%;
    }
    #invoice h2 {
      font-size: 14pt;
      margin: 10px 0;
      font-weight: normal;
      text-transform: uppercase;
    }
    #payment-details strong {
      float: left;
      font-weight: normal;
      width: 12em;
    }
    #invoice #payment-details {
        font-size: 10pt;
        line-height: 14pt;
    }
    #invoice-info h2 {
      font-weight: normal;
      margin: 0;
    }
    #invoice-header h2, #client-details h2, #invoice-item h2 {
      margin: 0;
    }
    #invoice #invoice-info h2, #invoice #company-address div.email, #invoice #client-details h2, #invoice #invoice-header h2, #invoice #payment-details h2, #invoice #invoice-item h2 {
      color: #006800;
    }
    #invoice {
      border-left: 60px solid #006800;
      padding: 0 1cm 1cm;
    }
    .pagestyle {
      background-color: #fff;
      border: solid 1px #b4bcc1;
      box-shadow: 0 1px 2px 0 rgb(0 0 0 / 20%);
      margin: 0 auto 40px;
      max-width: 764px;
      padding: 40px;
      position: relative;
    }
    #invoice-header #company-address {
      font-size: 11pt;
      line-height: 14pt;
      text-align: right;
    }
    #invoice #client-details, #invoice-info p {
      font-size: 10pt;
      line-height: 14pt;
    }
    #invoice-amount {
      font-size: 10pt;
      line-height: 14pt;
      clear: both;
    }
    #invoice-amount #header-row, #invoice-totals {
      background: #006800;
      color: #FFFFFF;
      padding: 3px;
    }
    #invoice-amount {
      line-height: 2;
    }
    .invoice-due {
    font-style: italic;
    }
  </style>
  <!-- START MAIN CONTENT AREA -->
  <div id="invoice" class="pagestyle">
    <div id="invoice-header">
      <div id="company-address">
        <div class="org"><h2><?php echo $organisationName ?></h2></div>
        <div class="address">
          <div class="street-address"><?php echo str_replace(',','<br />',$billing['billingAddress']) ?></div>
          <div class="email"><?php echo $billing['billingEmail'] ?></div>
          <div class="url"><?php echo trim( str_replace( array( 'http://', 'https://' ), '', $racketmanager->site_url ), '/' ) ?></div>
          <div class="tel"><?php echo $billing['billingTelephone'] ?></div>
        </div>
      </div>
    </div>
    <div id="invoice-info">
      <div class="invoice-no">
        <h2><?php _e('Invoice', 'racketmanager') ?>&nbsp;<?php echo $invoiceNumber ?></h2>
      </div>
      <div class="invoice-date"><?php echo mysql2date($racketmanager->date_format,$invoice->date) ?></div>
      <div class="invoice-due"><?php if ( $invoice->date_due == $invoice->date ) { _e('Payment due on receipt', 'racketmanager'); } else { echo __('Payment Due', 'racketmanager').': '.mysql2date($racketmanager->date_format, $invoice->date_due); } ?></div>
    </div>
    <div id="client-details">
      <div class="org"><h2><?php echo $club->name ?></h2></div>
      <div class="address">
        <div class="street-address"><?php echo str_replace(',','<br />',$club->address) ?></div>
      </div>
    </div>
    <div id="invoice-amount">
      <div id="header-row">
        <div class="invoice-item-detail"><?php echo ucwords(__('description', 'racketmanger')) ?></div>
        <div class="invoice-item-quantity"><?php echo ucwords(__('quantity', 'racketmanger')) ?></div>
        <div class="invoice-item-unit-price"><?php echo ucwords(__('unit price', 'racketmanger')) ?></div>
        <div class="invoice-item-net-price"><?php echo ucwords(__('net total', 'racketmanger')) ?></div>
      </div>
      <div id="invoice-items">
        <div id="invoice-item">
          <h2 class="invoice-item-detail"><?php echo ucfirst($invoice->charge->type).' '.'League'.' '.$invoice->charge->season; ?></h2>
        </div>
        <div id="invoice-item">
          <div class="invoice-item-detail"><?php echo ucwords(__('club entry fee', 'racketmanger')) ?></div>
          <div class="invoice-item-quantity"></div>
          <div class="invoice-item-unit-price"><?php echo numfmt_format_currency($fmt, $charge->feeClub, $billing['billingCurrency']) ?></div>
          <div class="invoice-item-net-price"><?php echo numfmt_format_currency($fmt, $charge->feeClub, $billing['billingCurrency']) ?></div>
        </div>
        <?php foreach ($entry->competitions as $competition) { ?>
          <div id="invoice-item">
            <div class="invoice-item-detail"><?php echo Racketmanager_Util::getCompetitionType($competition->type) ?></div>
            <div class="invoice-item-quantity"><?php echo $competition->count ?></div>
            <div class="invoice-item-unit-price"><?php echo numfmt_format_currency($fmt, $charge->feeTeam, $billing['billingCurrency']) ?></div>
            <div class="invoice-item-net-price"><?php echo numfmt_format_currency($fmt, $competition->fee, $billing['billingCurrency']) ?></div>
          </div>
        <?php } ?>
      </div>
      <div id="invoice-totals">
        <div class="invoice-total-desc">Total</div>
        <div class="invoice-item-net-price"><?php echo numfmt_format_currency($fmt, $entry->fee, $billing['billingCurrency']) ?></div>
      </div>
      <div id="payment-details">
        <h2><?php _e('Payment Details', 'racketmanager') ?></h2>
        <div id="bank-name">
          <?php echo $billing['bankName'] ?>
        </div>
        <div id="sort-code">
          <strong><?php _e('Sort Code', 'racketmanager') ?></strong>
          <?php echo $billing['sortCode'] ?>
        </div>
        <div id="account-number">
          <strong><?php _e('Account Number', 'racketmanager') ?></strong>
          <?php echo $billing['accountNumber'] ?>
        </div>
        <div id="payment-reference">
          <strong><?php _e('Payment Reference', 'racketmanager') ?></strong>
          <?php echo $invoiceNumber ?>
        </div>
      </div>
    </div>
  </div>
<?php } else { ?>
  <p class="contact-login-msg">You need to <a href="<?php echo wp_login_url(); ?>">login</a> to view invoices</p>
<?php } ?>
