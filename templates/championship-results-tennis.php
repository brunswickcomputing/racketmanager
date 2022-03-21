<div class="tournament-bracket">
  <?php foreach ( $finals AS $final ) { ?>

    <div class="round-header"><?php echo $final->name; ?></div>
  <?php } ?>
</div>
<div class="tournament-bracket">

  <?php foreach ( $finals AS $final ) { ?>

    <ul class="round">
      <li class="spacer">&nbsp;</li>
      <?php foreach ( (array)$final->matches AS $no => $match ) {
        $topClass = '';
        $bottomClass = '';
        if ( isset($match->home_team) && $match->home_team == $match->winner_id ) {
          $topClass .= ' winner';
          if ( $final->name == 'Final' ) { $champion = $match->teams['home']->title;
          }
        } elseif ( isset($match->away_team) && $match->away_team == $match->winner_id ) {
          $bottomClass .= ' winner';
          if ( $final->name == 'Final' ) { $champion=$match->teams['away']->title;
          }
        }
        if ( isset($league->entryType) && $league->entryType == 'player' && isset($league->type) && substr($league->type,1,1) == 'D' )  $bottomClass .= ' doubles';
        ?>
        <li class="game game-top<?php echo $topClass; ?>">
          <span class="draw-team">
            <?php if ( isset($match->teams['home']) && is_numeric($match->home_team) ) {
              echo str_replace('/','<br/>',$match->teams['home']->title);
            } else {
              echo '&nbsp;';
            } ?>
          </span>
          <span class="draw-home"><?php if ( isset($match->custom['host']) && $match->custom['host'] == 'home' ) { echo "H";} ?></span>
        </li>
        <li class="game game-spacer"><?php if ( $match->score != '' ) { echo $match->score; } ?> </li>
        <li class="game game-bottom<?php echo $bottomClass; ?>">
          <span class="draw-team">
            <?php if ( isset($match->teams['away']) && is_numeric($match->away_team) ) {
              echo str_replace('/','<br/>',$match->teams['away']->title);
            } else {
              echo '&nbsp;';
            }?>
          </span>
          <span class="draw-home"><?php if ( isset($match->custom['host']) && $match->custom['host'] == 'away' ) { echo "H";} ?></span>
        </li>
        <li class="spacer">&nbsp;</li>
      <?php } ?>
    </ul>
    <?php if ( isset($champion) ) { ?>
      <ul class="round">
        <li class="spacer">&nbsp;</li>
        <li class="game game-top winner"><?php echo str_replace('/','<br/>',$champion) ?></li>
        <li class="spacer">&nbsp;</li>
      </ul>
    <?php } ?>

  <?php } ?>

</div>
