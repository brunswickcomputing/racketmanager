<?php
$competitionName = $competition;
$homeTeam = $homeDtls['name'];
$homeCaptain = $homeDtls['captain'];
$homeCaptainEmail = $homeDtls['captainEmail'];
$homeCaptainTel = $homeDtls['captainTel'];
$awayTeam = $awayDtls['name'];
$awayCaptain = $awayDtls['captain'];
$awayCaptainEmail = $awayDtls['captainEmail'];
$awayCaptainTel = $awayDtls['captainTel'];
$matchDate = $match->match_date;
$numSets = $match->league->num_sets;
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
                          <td><h1 class="align-center"><?php echo $competitionName; ?></h1></td>
                        </tr>
                        <tr>
                          <td><h2 class="align-center"><?php echo $round; ?></h2></td>
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
                          <td class="strong">Match to be played week commencing <?php echo $matchDate; ?></td>
                        </tr>
                        <tr>
                          <td><?php echo $homeDtls['matchDay']; ?> at <?php echo $homeDtls['matchTime']; ?></td>
                        </tr>
                        <tr class="wrapper">
                          <td><h4>Winners</h4></td>
                        </tr>
                        <tr>
                          <td>Should <a href="mailto: ?subject=<?php echo $competitionName; ?>&nbsp;<?php echo $round; ?> Result">inform</a> the cup secretary of the result</td>
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
