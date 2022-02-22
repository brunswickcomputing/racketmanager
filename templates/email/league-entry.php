<?php
$title = $organisationName.' League Entry';
?>
<?php include('email-header.php'); ?>
<!-- START MAIN CONTENT AREA -->
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-center"><?php echo ucfirst($leagueSeason) ?> League Entry - <?php echo $season ?></h1>
            <p><?php echo $club ?> have submitted a new league entry form for the following competitions.</p>
            <?php foreach ($competitionEntries['competitions'] as $competitionEntry) { ?>
              <h2><?php echo $competitionEntry['competitionName']; ?></h2>
              <?php foreach ($competitionEntry['teams'] AS $leagueEntry ) { ?>
                <h3><?php echo $leagueEntry['teamName']; ?></h3>
                <p>Home matches will be on <?php echo $leagueEntry['matchday']; ?> at <?php echo $leagueEntry['matchtime']; ?></p>
                <ul>
                  <li>Captain: <?php echo $leagueEntry['captain']; ?></li>
                  <?php if ( $leagueEntry['contactno'] > '' ) { ?>
                    <li>Telephone: <?php echo $leagueEntry['contactno']; ?></li>
                  <?php } ?>
                  <?php if ( $leagueEntry['contactemail'] > '' ) { ?>
                    <li>Email: <?php echo $leagueEntry['contactemail']; ?></li>
                  <?php } ?>
                </ul>
              <?php } ?>
            <?php } ?>
            <p>Captains will be notified when the leagues have been finalised and fixtures for the season are available.</p>
            <p>There are <?php echo $competitionEntries['numCourtsAvailable'] ?> court available for matches.</p>
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
