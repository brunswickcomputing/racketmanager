<div class="championship-block">
    <form action="" method="post" style="display: inline;">
        <input type="hidden" name="jquery-ui-tab" value="1" />
        <p><?php _e( 'After adding the teams and arranging the rankings then ', 'leaguemanager' ) ?><input type="submit" class="button-secondary" value="<?php _e( 'Proceed to Final Rounds', 'leaguemanager' ) ?>" name="startFinals" /></p>
        <p><?php _e( 'Afterwards changes to rankings will NOT affect the final results', 'leaguemanager' ) ?></p>
    </form>

    <?php $teams = $leaguemanager->getTeams( array("league_id" => $league->id, "season" => $season['name'], "group" => $group) ); ?>
    <?php include('standings.php'); ?>

</div>
