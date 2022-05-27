<?php
global $racketmanager;
?>
<div class="table-responsive">
  <table class='table align-middle' summary='' title='<?php _e( 'Match Plan', 'racketmanager' ) ?> <?php the_league_title() ?>'>
    <thead class="table-dark">
      <tr>
        <?php if ( $league->mode == 'championship' ) { ?>
          <th><?php _e( '#', 'racketmanager' ) ?></th>
        <?php } ?>
        <th colspan="2" class='match'><?php _e( 'Match', 'racketmanager' ) ?></th>
        <th class='score'><?php _e( 'Score', 'racketmanager' ) ?></th>
      </tr>
    </thead>
    <tbody>
      <?php $matchday = isset($_GET['match_day']) ? $_GET['match_day'] : $league->match_day; ?>
      <?php foreach ( $matches AS $no => $match ) {
        if ( isset($match->teams['home']) && isset($match->teams['away']) ) {
          $userCanUpdateArray = $racketmanager->getMatchUpdateAllowed($match->teams['home'], $match->teams['away'], $match->league->competitionType, $match->confirmed);
          $userCanUpdate = $userCanUpdateArray[0];
        } else {
          $userCanUpdate = false;
        }
        if ( $league->mode == 'default' && $matchday != $match->match_day ) { ?>
          <tr class='match-day-row'>
            <th colspan="3" class='match']>Week <?php echo $match->match_day; ?></th>
          </tr>
          <?php
          $matchday = $match->match_day;
        } ?>

        <tr class='match-row rubber-view <?php echo $match->class ?>'>
          <?php if ( $league->mode == 'championship' ) { ?>
            <td><?php echo $no ?>
            </td>
          <?php } ?>
          <?php if ( isset($match->num_rubbers) && $match->num_rubbers > 0 ) {
            if ( ($match->winner_id) != 0) { ?>
              <?php if ( $match->home_team == -1 || $match->away_team == -1 ) { ?>
                <td class='angledir'></td>
              <?php } else { ?>
                <td class='angledir' title="<?php _e( 'View rubbers', 'racketmanager' ) ?>"><i class="racketmanager-svg-icon angledir"><?php racketmanager_the_svg('icon-chevron-right') ?></i></td>
              <?php } ?>
            <?php } else { ?>
              <td>
                <a href="" class='' type="<?php echo $match->league->entryType ?>" id="<?php echo $match->id ?>" onclick="Racketmanager.printScoreCard(event, this)" title="<?php _e( 'Print matchcard', 'racketmanager' ) ?>">
                  <i class="racketmanager-svg-icon"><?php racketmanager_the_svg('icon-printer') ?></i>
                </a>
              </td>
            <?php } ?>
          <?php } else {
            if ( ($match->winner_id) != 0) { ?>
              <td class='angledir'></td>
            <?php } else {
              if ( strpos($match->home_team, "_") == false && strpos($match->away_team, "_") == false ) { ?>
                <td class='angledir'>
                  <a href="#" class='' type="<?php echo $match->league->entryType ?>"
                    id="<?php echo $match->id ?>" onclick="Racketmanager.printScoreCard(event, this)" title="<?php _e( 'Print matchcard', 'racketmanager' ) ?>">
                    <i class="racketmanager-svg-icon"><?php racketmanager_the_svg('icon-printer') ?></i>
                  </a>
                  <?php if ( $userCanUpdate == true ) { ?>
                    <a href="#" class="" onclick="Racketmanager.showMatch(<?php echo $match->id ?>)"  title="<?php _e( 'Enter match result', 'racketmanager' ) ?>">
                      <i class="racketmanager-svg-icon"><?php racketmanager_the_svg('icon-pencil') ?></i>
                    </a>
                  <?php } ?>
                </td>
              <?php } else { ?>
                <td class='angledir'></td>
              <?php }
            }
          } ?>
          <td class='match'>

            <?php
            $homeClass = '';
            $awayClass = '';
            $homeTip = '';
            $awayTip = '';
            if ( $match->winner_id == $match->teams['home']->id ) {
              $homeClass = 'winner';
              $homeTip = 'Match winner';
            } elseif ( $match->winner_id == $match->teams['away']->id ) {
              $awayClass = 'winner';
              $awayTip = 'Match winner';
            } elseif ( isset( $match->custom['host'] ) ) {
              if ( $match->custom['host'] == 'home' ) {
                $homeClass = 'host';
                $homeTip = 'Home team';
              } elseif ( $match->custom['host'] == 'away' ) {
                $awayClass = 'host';
                $awayTip = 'Home team';
              }
            }
            ?>
            <?php the_match_date() ?> <?php the_match_time() ?> <?php the_match_location() ?><br />
            <?php if ( isset($match->teams['home']->title) && isset($match->teams['away']->title) ) {
              if ( is_numeric($match->home_team) && is_numeric($match->away_team) ) {
                $matchLink = false;
                if ( $userCanUpdate == true && ( !isset($match->confirmed) || $match->confirmed == "P" ) ) {
                  $matchLink = true;
                  if ($match->league->is_championship) {
                    $matchRef = $match->final_round;
                  } else {
                    $matchRef = 'day'.$match->match_day;
                  } ?>
                  <a href="/match/<?php echo seoUrl($match->league->title); ?>/<?php echo $match->season ?>/<?php echo $matchRef; ?>/<?php echo seoUrl($match->teams['home']->title) ?>-vs-<?php echo seoUrl($match->teams['away']->title) ?>/">
                <?php } ?>
                <span title="<?php echo $homeTip ?>" class="<?php echo $homeClass ?>"><?php echo $match->teams['home']->title ?></span> - <span title="<?php echo $awayTip ?>" class="<?php echo $awayClass?>"><?php echo $match->teams['away']->title; ?></span>
                <?php if ( $matchLink ) { ?>
                  </a>
                <?php } ?>
              <?php } else { ?>
                <?php the_match_title() ;?>
              <?php } ?>
            <?php } else { ?>
              <?php the_match_title() ;?>
            <?php } ?>
            <?php the_match_report() ?>
          </td>
          <td class='score'>
            <?php if (isset($league->num_rubbers) && $league->num_rubbers > 0) {
              echo the_match_score();
            } elseif ( isset($match->home_points) ) {
              echo $match->score;
            } else {
              echo '';
            } ?>
          </td>

        </tr>
        <?php if ( isset($match->num_rubbers) && $match->num_rubbers > 0 && ($match->winner_id != 0) ) { ?>
          <tr class='match-rubber-row <?php echo $match->class ?>'>
            <td colspan="<?php if ( $league->mode == 'championship' ) echo '4'; else echo '3' ?>">
              <table id='rubbers_<?php echo $match->id ?>'>
                <tbody>
                  <?php foreach ($match->rubbers as $rubber) { ?>
                    <?php if ( $rubber->homePlayer1 != 0 && $rubber->awayPlayer1 != 0 ) { ?>
                      <tr class='rubber-row <?php echo $match->class ?>'>
                        <td><?php echo $rubber->rubber_number ?></td>

                        <td class="playername <?php if ( $rubber->winner_id == $match->teams['home']->id ) echo 'winner' ?>" ><?php echo $rubber->home_player_1_name ?></td>
                        <td class="playername <?php if ( $rubber->winner_id == $match->teams['home']->id ) echo 'winner' ?>"><?php echo $rubber->home_player_2_name ?></td>
                        <?php if ( isset($rubber->sets) ) {
                          foreach ($rubber->sets as $set) { ?>
                            <?php if ( ($set['player1'] !== '') && ( $set['player2'] !== '' )) { ?>
                              <td class='score'><?php echo $set['player1']?> - <?php echo $set['player2']?></td>
                            <?php } else { ?>
                              <td class='score'></td>
                            <?php } ?>
                          <?php } ?>
                        <?php } ?>
                        <td class="playername <?php if ( $rubber->winner_id == $match->teams['away']->id ) echo 'winner' ?>"><?php echo $rubber->away_player_1_name ?></td>
                        <td class="playername <?php if ( $rubber->winner_id == $match->teams['away']->id ) echo 'winner' ?>"><?php echo $rubber->away_player_2_name ?></td>
                      </tr>
                    <?php } ?>
                  <?php } ?>
                </tbody>
              </table>
            </td>
          </tr>
        <?php } ?>


      <?php } ?>
    </tbody>
  </table>
</div>

<?php the_matches_pagination() ?>
