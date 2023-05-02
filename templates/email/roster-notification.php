<?php
$title = $organisationName.' Club Player Request - '.$club;
?>
<?php include('email-header.php'); ?>
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-center">Player <?php echo ucfirst($action); ?></h1>
            <p>A new player has been added for <?php echo $club; ?>.</p>
            <?php if ( $player ) { ?>
              <p class="align-center"><?php echo $player; ?></p>
            <?php } ?>
            <?php if ( $action == 'request' ) { ?>
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
                                <a href="<?php echo $actionurl ?>" class="button button--green" target="_blank">View request</a>
                              </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>
              </table>
            <?php } ?>
            <p>Thanks</p>
            <p>The <?php echo $organisationName ?> Team</p>
            <?php if ( $action == 'request' ) { ?>
              <!-- Sub copy -->
              <table class="body-sub">
                <tr>
                  <td>
                    <p class="sub">If you’re having trouble with the button above, copy and paste the URL below into your web browser.</p>
                    <p class="sub"><?php echo $actionurl ?></p>
                  </td>
                </tr>
              </table>
            <?php } ?>
          </div>
        </td>
      </tr>
    </table>
  </td>
</tr>

<!-- END MAIN CONTENT AREA -->
</table>
<!-- END CENTERED WHITE CONTAINER -->
<?php include('email-footer.php'); ?>
