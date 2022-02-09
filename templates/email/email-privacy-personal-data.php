<?php
$organisationName = $vars['site_name'];
$sitename = $vars['site_name'];
$siteurl = $vars['site_url'];
$title = 'Personal Data Export';
?>
<?php include('email-header.php'); ?>
<!-- START MAIN CONTENT AREA -->
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="username">Hi,</h1>
            <p>Your request for an export of personal data from <?php echo $organisationName ?> has been completed.</p>
            <p><strong>For privacy and security, we will automatically delete the file on ###EXPIRATION###, so please download it before then.</strong></p>
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
                              <a href="###LINK###" class="button button--green" target="_blank">Download personal data</a>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            <p>Thanks,</p>
            <p>The <?php echo $organisationName ?> Team</p>
            <!-- Sub copy -->
            <table class="body-sub">
              <tr>
                <td>
                  <p class="sub">If you’re having trouble with the button above, copy and paste the URL below into your web browser.</p>
                  <p class="sub">###LINK###</p>
                </td>
              </tr>
            </table>
          </td>
        </td>
      </tr>
    </table>
  </td>
</tr>

<!-- END MAIN CONTENT AREA -->
</table>
<!-- END CENTERED WHITE CONTAINER -->
<?php require('email-footer.php'); ?>
