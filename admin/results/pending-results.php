<?php
global $racketmanager;
$matchArgs = array();
$matchArgs['time'] = 'outstanding';
$matchArgs['competitiontype'] = 'league';
$matchArgs['orderby'] = array( 'updated' => 'ASC', 'id' => 'ASC');
$options = $racketmanager->getOptions('league');
$resultPending = '';
if ( isset($options['resultPending']) ) {
  $resultPending = $options['resultPending'];
  $matchArgs['resultPending'] = $resultPending;
}
$matches = $racketmanager->getMatches( $matchArgs );
$prev_league = 0;
?>
<div class="container">
  <div class="row table-header">
    <div class="col-4 col-sm-2 col-xxl-1"><?php _e( 'Date','racketmanager' ) ?></div>
    <div class="col-5"><?php _e( 'Match','racketmanager' ) ?></div>
  </div>
  <?php if ( $matches ) { $class = '';
    foreach ( $matches AS $match ) {
      $match = get_match($match);
      $overdueClass = '';
      $overdue = false;
      if ( $resultPending ) {
        $now = date_create();
        $dateOverdue = date_create($match->resultOverdueDate);
        if ( $dateOverdue < $now ) {
          $overdueClass = 'bg-warning';
          $overdue = true;
        }
      }
      if ( $match->league->is_championship ) {
        $matchLink = 'final='.$match->final_round.'&amp;league-tab=matches';
      } else {
        $matchLink = 'match_day='.$match->match_day;
      }
      $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>

      <div class="row table-row <?php echo $class.' '.$overdueClass ?> align-items-center" <?php if ($overdue) { echo 'title="'.sprintf(__('Result overdue by %d days', 'racketmanager'), intval(ceil($match->overdueTime))).'"';} ?>>
        <div class="col-4 col-sm-2 col-xxl-1"><?php echo mysql2date('Y-m-d', $match->date) ?></div>
        <div class="col-6 col-sm-5 col-lg-4 match-title">
          <a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $match->league->id ?>&amp;season=<?php echo $match->season ?>&amp;<?php echo $matchLink ?> "><?php echo $match->match_title ?></a>
        </div>
        <div class="col-auto">
          <a href="admin.php?page=racketmanager-results&amp;subpage=match&amp;match_id=<?php echo $match->id ?>&amp;referrer=pendingresults" class="btn btn-primary"><?php _e('Enter result', 'racketmanager') ?></a>
        </div>
        <div class="col-auto">
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
