<?php
$title = $organisationName.' Match Result - '.$favouriteTitle;
?>
<?php include('email-header.php'); ?>
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="username">Hi <?php echo $user->first_name ?></h1>
            <p>The following result(s) for <?php echo $favouriteTitle ?> have been updated.</p>
            <!-- Action -->
            <table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0">
              <?php foreach ($matches AS $match) {
                $matchURL .= 'day'.$match->match_day.'/'; ?>
                <tr>
                  <td class="align-right"><a href="<?php echo $matchURL ?>"><?php echo $match->teams['home']->title ?></a></td>
                  <td class="align-center"><a href="<?php echo $matchURL ?>"><?php echo $match->score ?></a></td>
                  <td class="align-left"><a href="<?php echo $matchURL ?>"><?php echo $match->teams['away']->title ?></a></td>
                </tr>
              <?php } ?>
            </table>
            <p>Thanks</p>
            <p>The <?php echo $organisationName ?> Team</p>
            <!-- Sub copy -->
            <table class="body-sub">
              <tr>
                <td>
                  <p class="sub">You are receiving this message because you follow <?php echo $favouriteTitle ?>. If you wish to stop further emails, please update your <a href="<?php echo $favouriteURL ?>">favourites</a>.</p>
                  <p class="sub"></p>
                </td>
              </tr>
            </table>
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
