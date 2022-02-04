<?php
$organisationName = $vars['site_name'];
$sitename = $vars['site_name'];
$siteurl = $vars['site_url'];
$userlogin = $vars['user_login'];
$username = $vars['display_name'];
$actionurl = $vars['action_url'];
$emaillink = $vars['email_link'];
$title = 'Password Reset Link';
?>
<?php include('email-header.php'); ?>
<!-- START MAIN CONTENT AREA -->
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="username">Hi <?php echo $username ?>,</h1>
            <p>You recently requested to reset your password for your <?php echo $organisationName ?> account. Use the button below to reset it. <strong>This password reset is only valid for the next 24 hours.</strong></p>
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
                              <a href="<?php echo $actionurl ?>" class="button button--green" target="_blank">Reset your password</a>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
            <p>If you did not request a password reset, please ignore this email.</p>
            <p>Thanks,</p>
            <p>The <?php echo $organisationName ?> Team</p>
            <!-- Sub copy -->
            <table class="body-sub">
              <tr>
                <td>
                  <p class="sub">If you’re having trouble with the button above, copy and paste the URL below into your web browser.</p>
                  <p class="sub"><?php echo $actionurl ?></p>
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
