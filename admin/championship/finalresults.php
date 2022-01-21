<div class="championship-block">
  <table class="widefat">
    <thead>
      <tr>
        <th scope="col"><?php _e( 'Round', 'racketmanager' ) ?></th>
        <th scope="col" colspan="<?php echo ($league->championship->num_teams_first_round > 4) ? 4 : $league->championship->num_teams_first_round; ?>" style="text-align: center;"><?php _e( 'Matches', 'racketmanager' ) ?></th>
      </tr>
    </thead>
    <tbody id="the-list-finals" class="lm-form-table">
      <?php foreach ( $league->championship->getFinals() AS $final ) {
        $class = ( 'alternate' == $class ) ? '' : 'alternate';
        $matches = $league->getMatches( array("final" => $final['key'], "orderby" => array("id" => "ASC")));
        ?>
        <tr class="<?php echo $class ?>">
          <th scope="row" style="padding-left: 1em;"><strong><?php echo $final['name'] ?></strong></th>
          <?php for ( $i = 1; $i <= $final['num_matches']; $i++ ) {
            ((isset($matches[0])) ? $match = $matches[$i-1] : 0);
            $colspan = ( $league->championship->num_teams_first_round/2 >= 4 ) ? ceil(4/$final['num_matches']) : ceil(($league->championship->num_teams_first_round/2)/$final['num_matches']); ?>
            <td colspan="<?php echo $colspan ?>" style="text-align: center;">
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
                <p><span title="<?php echo $homeTip ?>" class="<?php echo $homeClass ?>"><?php echo $homeTeam ?></span> - <span title="<?php echo $awayTip ?>" class="<?php echo $awayClass?>"><?php echo $awayTeam; ?></span></p>
                <?php if ( $match->home_points != NULL && $match->away_points != NULL ) {
                  $match->score = sprintf("%d:%d", $match->home_points, $match->away_points);?>
                  <p><strong><?php echo $match->score ?></strong></p>
                <?php } else { ?>
                  <p>-:-</p>
                <?php }
              } ?>
            </td>
            <?php if ( $i%4 == 0 && $i < $final['num_matches'] ) { ?>
            </tr>
            <tr class="<?php echo $class ?>"><th>&#160;</th>
            <?php }
          } ?>
        </tr>
      <?php } ?>
    </tbody>
  </table>
</div>
