<?php
$title = $organisationName.' Cup Entry';
?>
<?php include('email-header.php'); ?>
<!-- START MAIN CONTENT AREA -->
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-center"><?php echo ucfirst($cupSeason) ?> Cup Entry - <?php echo $season ?></h1>
            <p><?php echo $club ?> have submitted a new cup entry for the following competitions.</p>
            <?php foreach ($cupEntries as $cupEntry) { ?>
              <h2><?php echo $cupEntry['competitionName']; ?></h2>
              <h3><?php echo $cupEntry['teamName']; ?></h3>
              <p>Home matches will be on <?php echo $cupEntry['matchday']; ?> at <?php echo $cupEntry['matchtime']; ?></p>
              <ul>
                <li>Captain: <?php echo $cupEntry['captain']; ?></li>
                <?php if ( $cupEntry['contactno'] > '' ) { ?>
                  <li>Telephone: <?php echo $cupEntry['contactno']; ?></li>
                <?php } ?>
                <?php if ( $cupEntry['contactemail'] > '' ) { ?>
                  <li>Email: <?php echo $cupEntry['contactemail']; ?></li>
                <?php } ?>
              </ul>
            <?php } ?>
            <p>Teams have been added as requested.</p>
            <p>Captains will be notified when the draws have taken place.</p>
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
