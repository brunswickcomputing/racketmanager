<?php
$competitionName = $match->league->title;
$matchDate = $match->match_date;
$title = $organisationName.' Match Result - '.$competitionName;
?>
<?php include('email-header.php'); ?>
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-center"><?php echo $competitionName; ?></h1>
            <h2 class="align-center"><?php echo the_match_title(); ?></h2>
            <?php if ( isset($override) && $override ) { ?>
              <p>The approval of this result was outstanding<?php if ($timePeriod) { echo ' for more than '.$timePeriod.' hours after the result was entered'; } ?>.</p>
              <p>The entered result of this match has therefore been confirmed.</p>
              <?php if ($timePeriod) { ?>
                <p>Failure to approve or challenge future results within <?php echo $timePeriod; ?> hours of the result being entered may result in a point deduction.</p>
              <?php } ?>
            <?php } elseif ( isset($outstanding) && $outstanding ) { ?>
              <p>The approval of this result is outstanding<?php if ($timePeriod) { echo ' more than '.$timePeriod.' hours after the result was entered'; } ?>.</p>
              <p>Please either approval or challenge the result as soon as possible.</p>
              <?php if ($timePeriod) { echo '<p>Failure to do so may result in a point deduction.</p>'; } ?>
            <?php } elseif ( isset($errors) && $errors ) { ?>
              <p>The result of this match has been confirmed and updated.</p>
              <p>There are player checks that need actioning.</p>
            <?php } elseif ( isset($complete) && $complete ) { ?>
              <p>The result of this match has been confirmed and updated.</p>
              <p>There is no further action required.</p>
            <?php } else { ?>
              <p>The result of this match has been entered and requires action.</p>
            <?php } ?>
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
                              <a href="<?php echo $actionurl ?>" class="button button--green" target="_blank">View result</a>
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
                  <p class="sub"><?php echo $actionurl ?></p>
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
