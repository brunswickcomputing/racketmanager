<?php
/**
* Player Team administration panel
*
*/
namespace ns;
?>

<div class="wrap league-block">
  <p class="racketmanager_breadcrumb"><a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a><?php if ( !$noleague ) { ?> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a><?php } ?> &raquo; <?php echo $form_title ?></p>
  <h1><?php printf( "%s - %s",  $league->title, $form_title ); ?></h1>
  <form action="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $league_id ?>&amp;season=<?php echo $season ?>" method="post" enctype="multipart/form-data" name="team_edit" id="teamPlayerFrm" >

    <?php wp_nonce_field( 'racketmanager_manage-teams' ) ?>

    <div class="form-group">
      <label for="team"><?php _e( 'Team', 'racketmanager' ) ?></label>
      <div class="input">
        <input type="text" id="team" name="team" value="<?php echo $team->title ?>" size="50" disabled />
      </div>
    </div>
    <div class="form-group">
      <label for="teamPlayer1"><?php _e( 'Player 1', 'racketmanager' ) ?></label>
      <div class="input">
        <input type="text" name="teamPlayer1" id="teamPlayer1" value="<?php echo isset($team->player['1']) ? $team->player['1'] : '' ?>" size="50" /><input type="hidden" name="teamPlayerId1" id="teamPlayerId1" value="<?php echo isset($team->playerId['1']) ? $team->playerId['1'] : '' ?>" />
      </div>
    </div>
    <?php if ( substr($league->type,1,1) == 'D'  ) { ?>
      <div class="form-group">
        <label for="teamPlayer2"><?php _e( 'Player 2', 'racketmanager' ) ?></label>
        <div class="input">
          <input type="text" name="teamPlayer2" id="teamPlayer2" value="<?php echo isset($team->player['2']) ? $team->player['2'] : '' ?>" size="50" /><input type="hidden" name="teamPlayerId2" id="teamPlayerId2" value="<?php echo isset($team->playerId['2']) ? $team->playerId['2'] : '' ?>" />
        </div>
      </div>
    <?php } ?>
    <div class="form-group">
      <label for="affiliatedclub"><?php _e( 'Affiliated Club', 'racketmanager' ) ?></label>
      <div class="input">
        <select size="1" name="affiliatedclub" id="affiliatedclub" >
          <option value=""><?php _e( 'Select club' , 'racketmanager') ?></option>
          <?php foreach ( $clubs AS $club ) { ?>
            <option value="<?php echo $club->id ?>"<?php if(isset($team->affiliatedclub)) selected($club->id, $team->affiliatedclub ) ?>><?php echo $club->name ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="captain"><?php _e( 'Captain', 'racketmanager' ) ?></label>
      <div class="input">
        <input type="text" name="captain" id="captain" autocomplete="name off" value="<?php echo $team->captain ?>" size="40" disabled /><input type="hidden" name="captainId" id="captainId" value="<?php echo $team->captainId ?>" />
      </div>
    </div>
    <div class="form-group">
      <label for="contactno"><?php _e( 'Contact Number', 'racketmanager' ) ?></label>
      <div class="input">
        <input type="tel" name="contactno" id="contactno" autocomplete="tel" value="<?php echo $team->contactno ?>" size="20" />
      </div>
    </div>
    <div class="form-group">
      <label for="contactemail"><?php _e( 'Contact Email', 'racketmanager' ) ?></label>
      <div class="input">
        <input type="email" name="contactemail" id="contactemail" autocomplete="email" value="<?php echo $team->contactemail ?>" size="60" />
      </div>
    </div>

    <?php do_action( 'team_edit_form', $team ) ?>
    <?php do_action( 'team_edit_form_'.(isset($league->sport) ? ($league->sport) : '' ), $team ) ?>

    <input type="hidden" name="team_id" id="team_id" value="<?php echo $team->id ?>" />
    <input type="hidden" name="league_id" value="<?php echo $league_id ?>" />
    <input type="hidden" name="updateLeague" value="teamPlayer" />
    <input type="hidden" name="season" value="<?php echo $season ?>" />

    <input type="submit" id="actionPlayerTeam" name="action" value="<?php echo $form_action ?>" class="btn btn-primary" />
  </form>
  <div id="errorMsg" style="display:none;"></div>
</div>
