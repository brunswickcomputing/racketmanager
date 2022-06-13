<?php
$title = $organisationName.' Match Approval Pending';
?>
<?php include('email-header.php'); ?>
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-center"><?php the_match_title(); ?></h1>
            <p>The approval of this result is outstanding.</p>
            <p>Please either approval or challeange the result as soon as possible.</p>
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
                              <a href="<?php echo $actionURL ?>" class="button button--green" target="_blank">Approve result</a>
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
