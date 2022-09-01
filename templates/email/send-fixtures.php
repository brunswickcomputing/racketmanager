<?php
$title = $organisationName.' Fixtures';
?>
<?php include('email-header.php'); ?>
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-left">Dear captain</h1>
            <p>Please find attached your fixture list for the <?php echo $competition ?> <?php echo $season ?> season.  If you could check your details and notify me of errors.</p>
            <table class="fixtures">
              <thead>
                <tr class="align-center bold">
                  <td><?php _e('Round', 'racketmanager') ?></td>
                  <td><?php _e('Date', 'racketmanager') ?></td>
                  <td><?php _e('Day', 'racketmanager') ?></td>
                  <td><?php _e('Time', 'racketmanager') ?></td>
                  <td class="align-right"><?php _e('Home', 'racketmanager') ?></td>
                  <td></td>
                  <td class="align-left"><?php _e('Away', 'racketmanager') ?></td>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($matches as $match) { ?>
                  <tr class="align-center">
                    <td><?php the_match_day() ?></td>
                    <td><?php $format = 'd M y'; the_match_date($format); ?></td>
                    <td><?php the_match_date('D') ?></td>
                    <td><?php the_match_time() ?></td>
                    <td class="align-right <?php if ($match->home_team == $team->id) { echo 'bold'; } ?>"><?php echo $match->teams['home']->title ?></td>
                    <td>-</td>
                    <td class="align-left <?php if ($match->away_team == $team->id) { echo 'bold'; } ?>"><?php echo $match->teams['away']->title ?></td>
                  </tr>
                <?php } ?>
              </tbody>
            </table>
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
                              <a href="<?php echo $actionURL ?>" class="button button--green" target="_blank">View fixtures</a>
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
