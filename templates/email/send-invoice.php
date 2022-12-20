<?php
global $racketmanager;
$title = $emailSubject;
?>
<?php include('email-header.php'); ?>
<!-- START MAIN CONTENT AREA -->
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-left">Dear Match Secretary</h1>
            <p>Please find attached <?php if ( $resend ) { echo 'another copy of ';} ?>your invoice for the <?php echo ucfirst($invoice->charge->type) ?> <?php echo ucfirst($invoice->charge->competitionType) ?> <?php echo $invoice->charge->season ?> season.  If you could check your details and notify me of errors.</p>
            <!-- Action -->
            <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="align-center">
                  <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                      <td align="align-center">
                        <table border="0" cellspacing="0" cellpadding="0">
                          <tr>
                            <td>
                              <a href="<?php echo $actionURL ?>" class="button button--green" target="_blank">View Invoice</a>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            <p>Thanks</p>
            <p>The <?php echo $organisationName ?> Team</p>
            <!-- Sub copy -->
            <table class="body-sub">
              <tr>
                <td>
                  <p class="sub">If you’re having trouble with the button above, copy and paste the URL below into your web browser.</p>
                  <p class="sub"><?php echo $actionURL ?></p>
                </td>
              </tr>
            </table>
            <?php echo $invoiceView ?>
          </div>
        </td>
      </tr>
    </table>
  </td>
</tr>
<!-- END MAIN CONTENT AREA -->
</table>
<!-- END CENTERED WHITE CONTAINER -->
<?php require('email-footer.php'); ?>
