<?php
$competitionName = $match->league->title;
$tournamentName = $tournament->name;
$tournamentDate = $tournament->date;
$tournamentVenue = $tournament->venueName;
$homeTeam = $homeDtls['name'];
$homeClub = $homeDtls['club'];
$homeCaptain = $homeDtls['captain'];
$homeCaptainEmail = $homeDtls['captainEmail'];
$homeCaptainTel = $homeDtls['captainTel'];
$awayTeam = $awayDtls['name'];
$awayClub = $awayDtls['club'];
$awayCaptain = $awayDtls['captain'];
$awayCaptainEmail = $awayDtls['captainEmail'];
$awayCaptainTel = $awayDtls['captainTel'];
$matchDate = $match->match_date;
$numSets = $match->league->num_sets;
$tournamentSecretary = $tournament->tournamentSecretaryEmail;
$title = $organisationName.' Match Details - '.$competitionName.' - '.$round;
?>
<?php include('email-header.php'); ?>
            <!-- START MAIN CONTENT AREA -->
            <tr>
              <td class="wrapper">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td>
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><h1 class="align-center"><?php echo $tournamentName; ?></h1></td>
                        </tr>
                        <tr>
                          <td class="align-center">Finals date <?php echo $tournamentDate; ?></td>
                        </tr>
                        <tr>
                          <td class="align-center">at <?php echo $tournamentVenue; ?></td>
                        </tr>
                        <tr>
                          <td><h2 class="align-center"><?php echo $competitionName; ?> <?php echo $round; ?></h2></td>
                        </tr>
                        <tr>
                          <td>
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="">
                              <tbody>
                                <tr>
                                  <td align="left">
                                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                      <tbody>
                                        <tr>
                                          <td>
                                            <h4><?php echo $homeDtls['title']; ?></h4>
                                            <ul>
                                              <li><?php echo $homeTeam; ?></li>
                                              <li>(<?php echo $homeClub; ?>)</li>
                                            </ul>
                                            <ul>
                                              <li><?php echo $homeCaptain; ?></li>
                                              <?php if ( $homeCaptainEmail > '' ) { ?>
                                                <li><?php echo $homeCaptainEmail; ?></li>
                                              <?php } ?>
                                              <?php if ( $homeCaptainTel > '' ) { ?>
                                                <li><?php echo $homeCaptainTel; ?></li>
                                              <?php } ?>
                                            </ul>
                                          </td>
                                          <td>
                                            <h4><?php echo $awayDtls['title']; ?></h4>
                                            <ul>
                                              <li><?php echo $awayTeam; ?></li>
                                              <li>(<?php echo $awayClub; ?>)</li>
                                            </ul>
                                            <ul>
                                              <li><?php echo $awayCaptain; ?></li>
                                              <?php if ( $homeCaptainEmail > '' ) { ?>
                                                <li><?php echo $awayCaptainEmail; ?></li>
                                              <?php } ?>
                                              <li><?php echo $awayCaptainTel; ?></li>
                                            </ul>
                                          </td>
                                        </tr>
                                      </tbody>
                                    </table>
                                  </td>
                                </tr>
                              </tbody>
                            </table>
                          </td>
                        </tr>
                        <tr class="wrapper">
                          <td class="strong">Match to be played by <?php echo $matchDate; ?></td>
                        </tr>
                        <tr>
                          <td>Play best of <?php echo $numSets; ?> tie-break sets</td>
                        </tr>
                        <tr class="wrapper">
                          <td>Home players are required to contact their opponents within <strong>3 days</strong> of this notice, failing which, their opponents may constitute themselves the home players and arrange the match accordingly. <a href="mailto: <?php echo $tournamentSecretary; ?>">Contact</a> the tournament organiser regarding any problems arising re playing by the deadline.</td>
                        </tr>
                        <tr class="wrapper">
                          <td><h4>Match cancellation</h4></td>
                        </tr>
                        <tr class="wrapper">
                          <td>24 hours clear notice should be given if you need to cancel, otherwise your opponents can claim the match i.e. match due to be played 2pm Sunday – cancel by 2pm Saturday. This has been implemented to cover players giving up work to play their matches, and being let down at the last minute.</td>
                        </tr>
                        <tr class="wrapper">
                          <td><h4>Winners</h4></td>
                        </tr>
                        <tr>
                          <td>Should <a href="mailto: <?php echo $tournamentSecretary; ?>?subject=<?php echo $competitionName; ?>&nbsp;<?php echo $round; ?> Result">inform</a> the tournament organiser of the result</td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <!-- END MAIN CONTENT AREA -->
          </table>
          <!-- END CENTERED WHITE CONTAINER -->
<?php include('email-footer.php'); ?>
