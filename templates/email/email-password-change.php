<?php
$organisationName = $vars['site_name'];
$sitename = $vars['site_name'];
$siteurl = $vars['site_url'];
$userlogin = $vars['user_login'];
$username = $vars['display_name'];
$title = 'Password Change';
$emaillink = $vars['email_link'];
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
            <p>Your password has now been changed for your <?php echo $organisationName ?> account.</p>
            <p>If you did not change your password, please <a href="mailto:<?php echo $emaillink ?>">contact the team</a>.</p>
            <p>Thanks,</p>
            <p>The <?php echo $organisationName ?> Team</p>
            <!-- Sub copy -->
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
