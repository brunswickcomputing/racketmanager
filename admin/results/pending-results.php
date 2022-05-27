<?php
global $racketmanager;
$matches = $racketmanager->getMatches( array('time' => 'outstanding', 'competitiontype' => 'league', 'orderby' => array('date' => 'ASC', 'id' => 'ASC')) );
$prev_league = 0;
?>
<div class="container">
  <div class="row table-header">
    <div class="col-3 col-sm-2 col-xxl-1"><?php _e( 'Date','racketmanager' ) ?></div>
    <div class="col-9"><?php _e( 'Match','racketmanager' ) ?></div>
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

      <div class="row table-row <?php echo $class ?> align-items-center">
        <div class="col-3 col-sm-2 col-xxl-1"><?php echo mysql2date('Y-m-d', $match->date) ?></div>
        <div class="col-9 col-sm-6 col-lg-4 match-title">
          <a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $match->league->id ?>&amp;season=<?php echo $match->season ?>&amp;<?php echo $matchLink ?> "><?php echo $match->match_title ?></a>
        </div>
        <div class="col-12 col-md-auto">
          <a href="admin.php?page=racketmanager-results&amp;subpage=match&amp;match_id=<?php echo $match->id ?>&amp;referrer=pendingresults" class="btn btn-primary"><?php _e('Enter result', 'racketmanager') ?></a>
        </div>
        <div class="col-12 col-md-auto">
          <a class="btn btn-secondary" onclick="Racketmanager.chaseMatchResult('<?php echo ($match->id) ?>');">
          <?php _e( 'Chase result', 'racketmanager' ) ?></a>
        </div>
        <div class="col-12 col-md-auto"><span id="notifyMessage-<?php echo $match->id ?>"></span></div>
      </div>
    <?php }
  } else { ?>
    <div class="col-auto my-3"><?php _e('No matches with pending results', 'racketmanager') ?></div>
  <?php } ?>
</div>
