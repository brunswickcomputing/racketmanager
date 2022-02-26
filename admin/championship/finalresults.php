<div class="championship-block">
  <div class="container draw">
    <div class="row">
      <?php foreach ($league->championship->getFinals() as $key => $final) {
        $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
        <div class="finalround <?php echo $class ?>">
          <div class="roundName">
            <?php echo $final['name'] ?>
          </div>
          <div class="container roundmatches">
            <div class="row row-cols-1 row-cols-sm-<?php if ( $final['num_matches'] < 4 ) { echo $final['num_matches'];} else { echo 2;} ?> row-cols-lg-<?php if ( $final['num_matches'] < 4 ) { echo $final['num_matches'];} else { echo 4;} ?> finalmatches justify-content-center">
              <?php
              $matches = $league->getMatches( array("final" => $final['key'], "orderby" => array("id" => "ASC")));
              foreach ($matches as $i => $match) { ?>
                <div class="finalmatch">
                  <div class="row">
                    <?php if ( isset($match) ) {
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
                      $homeTeam = $match->teams['home']->title;
                      $awayTeam = $match->teams['away']->title; ?>
                      <div title="<?php echo $homeTip ?>" class="col-5 col-sm-5 team team-left <?php echo $homeClass ?>">
                        <?php echo $homeTeam ?>
                      </div>
                      <div class="col-2 col-sm-2 score">
                        <?php if ( $match->home_points != NULL && $match->away_points != NULL ) {
                          $match->score = sprintf("%d:%d", $match->home_points, $match->away_points);?>
                          <strong><?php echo $match->score ?></strong>
                        <?php } else { ?>
                          -
                        <?php } ?>
                      </div>
                      <div title="<?php echo $awayTip ?>" class="col-5 col-sm-5 team team-right <?php echo $awayClass?>">
                        <?php echo $awayTeam; ?>
                      </div>
                    </div>
                  <?php } ?>
                </div>
              <?php } ?>
            </div>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>
</div>
