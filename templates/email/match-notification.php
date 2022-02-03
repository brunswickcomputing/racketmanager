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
?>
<!doctype html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <title><?php echo $organisationName; ?> Match Details - <?php echo $competitionName ?> - <?php echo $round ?></title>
  <style>
  /* -------------------------------------
  GLOBAL RESETS
  ------------------------------------- */

  /*All the styling goes here*/

  img {
    border: none;
    -ms-interpolation-mode: bicubic;
    max-width: 100%;
  }

  body {
    background-color: #f6f6f6;
    font-family: sans-serif;
    -webkit-font-smoothing: antialiased;
    font-size: 16px;
    line-height: 1.4;
    margin: 0;
    padding: 0;
    -ms-text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%;
  }

  table {
    border-collapse: separate;
    mso-table-lspace: 0pt;
    mso-table-rspace: 0pt;
    width: 100%;
  }
  table td {
    font-family: sans-serif;
    font-size: 16px;
    vertical-align: top;
  }

  /* -------------------------------------
  BODY & CONTAINER
  ------------------------------------- */

  .body {
    background-color: #f6f6f6;
    width: 100%;
  }

  /* Set a max-width, and make it display as block so it will automatically stretch to that width, but will also shrink down on a phone or something */
  .container {
    display: block;
    margin: 0 auto !important;
    /* makes it centered */
    max-width: 660px;
    padding: 10px;
    width: 660px;
  }

  /* This should also be a block element, so that it will fill 100% of the .container */
  .content {
    box-sizing: border-box;
    display: block;
    margin: 0 auto;
    max-width: 660px;
    padding: 10px;
  }

  /* -------------------------------------
  HEADER, FOOTER, MAIN
  ------------------------------------- */
  .main {
    background: #ffffff;
    border-radius: 3px;
    width: 100%;
  }

  .wrapper {
    box-sizing: border-box;
    padding: 20px;
  }

  .content-block {
    padding-bottom: 10px;
    padding-top: 10px;
  }

  .footer {
    clear: both;
    margin-top: 10px;
    text-align: center;
    width: 100%;
  }
  .footer td,
  .footer p,
  .footer span,
  .footer a {
    color: #999999;
    font-size: 12px;
    text-align: center;
  }

  /* -------------------------------------
  TYPOGRAPHY
  ------------------------------------- */
  h1,
  h2,
  h3,
  h4 {
    color: #000000;
    font-family: sans-serif;
    font-weight: 700;
    line-height: 1.4;
    margin: 0;
    margin-bottom: 10px;
  }

  h1 {
    font-size: 35px;
    font-weight: 900;
    text-align: center;
    text-transform: capitalize;
  }
  h2 {
    margin-bottom: 5px;
  }
  h3 {
    padding-top: 10px;
  }
  h4 {
    padding-top: 10px;
    margin-bottom: 0;
  }
  p,
  ul,
  ol {
    font-family: sans-serif;
    font-size: 16px;
    font-weight: normal;
    margin: 0;
    margin-bottom: 15px;
  }
  p li,
  ul li,
  ol li {
    list-style-position: inside;
    margin-left: 5px;
  }
  ul {
    list-style: none;
    padding-left: 0;
  }

  a {
    color: #3498db;
    text-decoration: underline;
  }

  /* -------------------------------------
  BUTTONS
  ------------------------------------- */
  .btn {
    box-sizing: border-box;
    width: 100%;
  }
  .btn > tbody > tr > td {
    padding-bottom: 15px;
  }
  .btn table {
    width: auto;
  }
  .btn table td {
    background-color: #ffffff;
    border-radius: 5px;
    text-align: center;
  }
  .btn a {
    background-color: #ffffff;
    border: solid 1px #3498db;
    border-radius: 5px;
    box-sizing: border-box;
    color: #3498db;
    cursor: pointer;
    display: inline-block;
    font-size: 14px;
    font-weight: bold;
    margin: 0;
    padding: 12px 25px;
    text-decoration: none;
    text-transform: capitalize;
  }

  .btn-primary table td {
    background-color: #3498db;
  }

  .btn-primary a {
    background-color: #3498db;
    border-color: #3498db;
    color: #ffffff;
  }

  /* -------------------------------------
  OTHER STYLES THAT MIGHT BE USEFUL
  ------------------------------------- */
  .strong {
    font-weight: 700;
  }
  .last {
    margin-bottom: 0;
  }

  .first {
    margin-top: 0;
  }

  .align-center {
    text-align: center;
  }

  .align-right {
    text-align: right;
  }

  .align-left {
    text-align: left;
  }

  .clear {
    clear: both;
  }

  .mt0 {
    margin-top: 0;
  }

  .mb0 {
    margin-bottom: 0;
  }

  .preheader {
    color: transparent;
    display: none;
    height: 0;
    max-height: 0;
    max-width: 0;
    opacity: 0;
    overflow: hidden;
    mso-hide: all;
    visibility: hidden;
    width: 0;
  }

  .powered-by a {
    text-decoration: none;
  }

  hr {
    border: 0;
    border-bottom: 1px solid #f6f6f6;
    margin: 20px 0;
  }

  /* -------------------------------------
  RESPONSIVE AND MOBILE FRIENDLY STYLES
  ------------------------------------- */
  @media only screen and (max-width: 660px) {
    table.body h1 {
      font-size: 28px !important;
      margin-bottom: 10px !important;
    }
    table.body p,
    table.body ul,
    table.body ol,
    table.body td,
    table.body span,
    table.body a {
      font-size: 16px !important;
    }
    table.body .wrapper,
    table.body .article {
      padding: 10px !important;
    }
    table.body .content {
      padding: 0 !important;
    }
    table.body .container {
      padding: 0 !important;
      width: 100% !important;
    }
    table.body .main {
      border-left-width: 0 !important;
      border-radius: 0 !important;
      border-right-width: 0 !important;
    }
    table.body .btn table {
      width: 100% !important;
    }
    table.body .btn a {
      width: 100% !important;
    }
    table.body .img-responsive {
      height: auto !important;
      max-width: 100% !important;
      width: auto !important;
    }
  }

  /* -------------------------------------
  PRESERVE THESE STYLES IN THE HEAD
  ------------------------------------- */
  @media all {
    .ExternalClass {
      width: 100%;
    }
    .ExternalClass,
    .ExternalClass p,
    .ExternalClass span,
    .ExternalClass font,
    .ExternalClass td,
    .ExternalClass div {
      line-height: 100%;
    }
    .apple-link a {
      color: inherit !important;
      font-family: inherit !important;
      font-size: inherit !important;
      font-weight: inherit !important;
      line-height: inherit !important;
      text-decoration: none !important;
    }
    #MessageViewBody a {
      color: inherit;
      text-decoration: none;
      font-size: inherit;
      font-family: inherit;
      font-weight: inherit;
      line-height: inherit;
    }
    .btn-primary table td:hover {
      background-color: #34495e !important;
    }
    .btn-primary a:hover {
      background-color: #34495e !important;
      border-color: #34495e !important;
    }
  }

  </style>
</head>
<body class="">
  <span class="preheader"><?php echo $homeTeam; ?> (<?php echo $homeClub; ?>) - <?php echo $awayTeam; ?> (<?php echo $awayClub; ?>)</span>
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="body">
    <tr>
      <td>&nbsp;</td>
      <td class="container">
        <div class="content">

          <!-- START CENTERED WHITE CONTAINER -->
          <table role="presentation" class="main">

            <!-- START MAIN CONTENT AREA -->
            <tr>
              <td class="wrapper">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                  <tr>
                    <td>
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td><h1><?php echo $organisationName; ?></h1></td>
                        </tr>
                        <tr>
                          <td><h2 class="align-center"><?php echo $tournamentName; ?></h2></td>
                        </tr>
                        <tr>
                          <td class="align-center">Finals date <?php echo $tournamentDate; ?></td>
                        </tr>
                        <tr>
                          <td class="align-center">at <?php echo $tournamentVenue; ?></td>
                        </tr>
                        <tr>
                          <td><h3 class="align-center"><?php echo $competitionName; ?> <?php echo $round; ?></h3></td>
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

          <!-- START FOOTER -->
          <div class="footer">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
              <tr>
                <td class="content-block">
                  <span class="apple-link"><?php echo $organisationName; ?></span>
                </td>
              </tr>
            </table>
          </div>
          <!-- END FOOTER -->

        </div>
      </td>
      <td>&nbsp;</td>
    </tr>
  </table>
</body>
</html>
