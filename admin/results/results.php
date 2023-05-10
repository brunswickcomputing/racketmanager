<?php
global $racketmanager;
$matchArgs = array();
$matchArgs['confirmed'] = true;
$matchArgs['order'] = array( 'updated' => 'ASC', 'id' => 'ASC');
$options = $racketmanager->getOptions('league');
$confirmationPending = '';
if ( isset($options['confirmationPending']) ) {
  $confirmationPending = $options['confirmationPending'];
  $matchArgs['confirmationPending'] = $confirmationPending;
}
$matches = $racketmanager->getMatches( $matchArgs );
$prev_league = 0;
?>
<div class="container">
  <?php wp_nonce_field( 'results-update' ) ?>
  <div class="row table-header">
    <div class="col-4 col-sm-2 "><?php _e( 'Date','racketmanager' ) ?></div>
    <div class="col-8 col-md-4"><?php _e( 'Match','racketmanager' ) ?></div>
    <div class="col-4 col-md-2"><?php _e( 'Status', 'racketmanager' ) ?></div>
    <div class="col-2 col-md-2"><?php _e( 'Score', 'racketmanager' ) ?></div>
  </div>
  <?php if ( $matches ) { $class = '';
    foreach ( $matches AS $match ) {
      $match = get_match($match);
      $overdueClass = '';
      $overdue = false;
      if ( $confirmationPending ) {
        $now = date_create();
        $dateOverdue = date_create($match->confirmationOverdueDate);
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

      <div class="row table-row <?php echo $class.' '.$overdueClass ?> align-items-center" <?php if ($overdue) { echo 'title="'.sprintf(__('Confirmation overdue by %d days', 'racketmanager'), intval($match->overdueTime)).'"';} ?>>
        <?php if ( $prev_league != $match->league_id) {
          $prev_league = $match->league_id; ?>
          <div class="col-12"><?php echo $match->league->title ?></div>
        <?php } ?>
        <div class="col-4 col-sm-2"><?php echo mysql2date('Y-m-d', $match->date) ?></div>
        <div class="col-8 col-md-4 match-title"><?php echo $match->match_title ?></div>
        <div class="col-4 col-md-2">
          <?php echo $match->confirmedDisplay ?>
        </div>
        <div class="col-2 col-md-1">
          <?php echo $match->score ?>
        </div>
        <div class="col-auto">
          <a href="admin.php?page=racketmanager-results&amp;subpage=match&amp;match_id=<?php echo $match->id ?>&amp;referrer=results" class="btn btn-secondary"><?php _e('View result', 'racketmanager') ?></a>
        </div>
        <?php if ( $match->confirmed == 'P' ) { ?>
          <div class="col-auto">
            <a class="btn btn-secondary" onclick="Racketmanager.chaseMatchApproval('<?php echo ($match->id) ?>');">
            <?php _e( 'Chase approval', 'racketmanager' ) ?></a>
          </div>
          <div class="col-12 col-md-auto"><span id="notifyMessage-<?php echo $match->id ?>"></span></div>
        <?php } ?>
      </div>
    <?php }
  } else { ?>
    <div class="col-auto my-3"><?php _e('No matches with pending results', 'racketmanager') ?></div>
  <?php } ?>
</div>
