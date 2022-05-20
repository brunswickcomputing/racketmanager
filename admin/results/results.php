<?php
global $racketmanager;
$matches = $racketmanager->getMatches( array('confirmed' => true, 'order' => array( 'updated' => 'ASC', 'id' => 'ASC')) );
$prev_league = 0;
?>
<div class="container">
  <?php wp_nonce_field( 'results-update' ) ?>
  <div class="row table-header">
    <div class="col-12 col-md-2"><?php _e( 'Date','racketmanager' ) ?></div>
    <div class="col-12 col-md-4"><?php _e( 'Match','racketmanager' ) ?></div>
    <div class="col-12 col-md-2"><?php _e( 'Score', 'racketmanager' ) ?></div>
    <div class="col-12 col-md-1"></div>
  </div>
  <?php if ( $matches ) { $class = '';
    foreach ( $matches AS $match ) {
      $match = get_match($match);
      if ( $match->league->is_championship ) {
        $matchLink = 'final='.$match->final_round.'&amp;league-tab=matches';
      } else {
        $matchLink = 'match_day='.$match->match_day;
      }
      $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>

      <div class="row table-row <?php echo $class ?>">
        <?php if ( $prev_league != $match->league_id) {
          $prev_league = $match->league_id; ?>
          <div class="col-12"><?php echo $match->league->title ?></div>
        <?php } ?>
        <input type="hidden" name="matches[<?php echo $match->league->id ?>][<?php echo $match->id ?>]" value="<?php echo $match->id ?>" />
        <input type="hidden" name="home_team[<?php echo $match->league->id ?>][<?php echo $match->id ?>]" value="<?php echo $match->home_team ?>" />
        <input type="hidden" name="away_team[<?php echo $match->league->id ?>][<?php echo $match->id ?>]" value="<?php echo $match->away_team ?>" />
        <div class="col-12 col-md-2"><?php echo ( substr($match->date, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date($this->date_format, $match->date) ?></div>
        <div class="col-12 col-md-4 match-title"><?php echo $match->match_title ?></div>
        <div class="col-12 col-md-1">
          <?php echo $match->score ?>
        </div>
        <div class="col-12 col-md-2"><a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $match->league->id ?>&amp;season=<?php echo $match->season ?>&amp;<?php echo $matchLink ?> " class="btn btn-secondary"><?php _e('View match', 'racketmanager') ?></a>
        </div>
      </div>
    <?php }
  } else { ?>
    <div class="col-auto"><?php _e('No matches with pending results', 'racketmanager') ?></div>
  <?php } ?>
</div>
