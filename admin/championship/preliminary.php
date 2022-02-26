<div class="championship-block">
  <form action="" method="post" style="display: inline;">
    <input type="hidden" name="league-tab" value="1" />
    <p><?php _e( 'After adding the teams and arranging the rankings then ', 'racketmanager' ) ?></p><input type="submit" class="btn btn-primary" value="<?php _e( 'Proceed to Final Rounds', 'racketmanager' ) ?>" name="startFinals" />
    <p><?php _e( 'Afterwards changes to rankings will NOT affect the final results', 'racketmanager' ) ?></p>
  </form>

  <?php $teams = $league->getLeagueTeams( array() ); ?>
  <?php include(RACKETMANAGER_PATH . '/admin/includes/standings.php'); ?>

</div>
