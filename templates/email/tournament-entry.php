<?php
$title = $organisationName.' Tournament Entry';
?>
<?php include('email-header.php'); ?>
<!-- START MAIN CONTENT AREA -->
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-center"><?php echo ucfirst($tournamentSeason) ?> <?php echo $season ?> Tournament Entry</h1>
            <p>A new tournament entry has been submitted.</p>
              <ul>
                <li><?php echo $player ?> (<?php echo $club ?>)</li>
                <?php if ( $contactno > '' ) { ?>
                  <li><?php echo $contactno; ?></li>
                <?php } ?>
                <?php if ( $contactemail > '' ) { ?>
                  <li><?php echo $contactemail; ?></li>
                <?php } ?>
              </ul>
              <p>The following competitions have been entered and teams added:</p>
            <?php foreach ($tournamentEntries as $tournamentEntry) { ?>
              <ul>
                <li><span class="strong"><?php echo $tournamentEntry['competitionName']; ?></span><?php if ( isset($tournamentEntry['partner']) ) { ?> with partner <span class="strong"><?php echo $tournamentEntry['partner']; }?></span></li>
              </ul>
            <?php } ?>
            <p><?php echo $player ?> will be notified when the draws have taken place.</p>
            <p>Thanks</p>
            <p>The <?php echo $organisationName ?> Team</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
<!-- END MAIN CONTENT AREA -->
</table>
<!-- END CENTERED WHITE CONTAINER -->
<?php include('email-footer.php'); ?>
