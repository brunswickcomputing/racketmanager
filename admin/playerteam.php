<?php
/**
 * Player Team administration panel
 *
 */
 namespace ns;
?>

<div class="wrap league-block">
    <p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a><?php if ( !$noleague ) { ?> &raquo; <a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a><?php } ?> &raquo; <?php echo $form_title ?></p>
    <h1><?php printf( "%s &mdash; %s",  $league->title, $form_title ); ?></h1>
    <form action="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league_id ?>&amp;season=<?php echo $season ?>" method="post" enctype="multipart/form-data" name="team_edit">

        <?php wp_nonce_field( 'leaguemanager_manage-teams' ) ?>

        <table class="lm-form-table">
            <tr valign="top">
                <th scope="row" style="width: 225px;"><label for="team"><?php _e( 'Team', 'leaguemanager' ) ?></label></th>
                <td>
                    <input type="text" id="team" name="team" value="<?php echo $team->title ?>" size="50" disabled />
                    <?php if ( !$edit ) { ?>
                        <div id="teams_db" style="display: none; overflow: auto; width: 300px; height: 80px;"><div>
                        <select size="1" name="team_db_select" id="team_db_select" style="display: block; margin: 0.5em auto;">
                            <option value=""><?php _e( 'Choose Team', 'leaguemanager' ) ?></option>
                            <?php $this->teamPlayersDropdownCleaned() ?>
                        </select>
                        <div style='text-align: center; margin-top: 1em;'><input type="button" value="<?php _e('Insert', 'leaguemanager') ?>" class="button-secondary" onClick="Leaguemanager.getTeamPlayerFromDatabase(); return false;" />&#160;<input type="button" value="<?php _e('Cancel', 'leaguemanager') ?>" class="button-secondary" onClick="tb_remove();" /></div>
                        </div></div>
                        <a class="thickbox" href="#TB_inline&amp;width=300&amp;height=80&amp;inlineId=teams_db" title="<?php _e( 'Add Team from Database', 'leaguemanager' ) ?>"><img src="<?php echo LEAGUEMANAGER_URL ?>/admin/icons/database.png" alt="<?php _e( 'Add Team from Database', 'leaguemanager' ) ?>" title="<?php _e( 'Add Team from Database', 'leaguemanager' ) ?>" style="vertical-align: middle;" /></a>
                    <?php } ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="teamPlayer1"><?php _e( 'Player 1', 'leaguemanager' ) ?></label></th><td><input type="text" name="teamPlayer1" id="teamPlayer1" value="<?php echo isset($team->player['1']) ? $team->player['1'] : '' ?>" size="50" /><input type="hidden" name="teamPlayerId1" id="teamPlayerId1" value="<?php echo isset($team->playerId['1']) ? $team->playerId['1'] : '' ?>" /></td>
            </tr>
            <?php if ( substr($league->type,1,1) == 'D'  ) { ?>
                <tr valign="top">
                    <th scope="row"><label for="teamPlayer2"><?php _e( 'Player 2', 'leaguemanager' ) ?></label></th><td><input type="text" name="teamPlayer2" id="teamPlayer2" value="<?php echo isset($team->player['2']) ? $team->player['2'] : '' ?>" size="50" /><input type="hidden" name="teamPlayerId2" id="teamPlayerId2" value="<?php echo isset($team->playerId['2']) ? $team->playerId['2'] : '' ?>" /></td>
                </tr>
            <?php } ?>
            <tr valign="top">
                <th scope="row"><label for="affiliatedclub"><?php _e( 'Affiliated Club', 'leaguemanager' ) ?></label></th>
                <td>
                    <select size="1" name="affiliatedclub" id="affiliatedclub" >
                        <option><?php _e( 'Select club' , 'leaguemanager') ?></option>
                        <?php foreach ( $clubs AS $club ) { ?>
                        <option value="<?php echo $club->id ?>"<?php if(isset($team->affiliatedclub)) selected($club->id, $team->affiliatedclub ) ?>><?php echo $club->name ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="captain"><?php _e( 'Captain', 'leaguemanager' ) ?></label></th><td><input type="text" name="captain" id="captain" autocomplete="name off" value="<?php echo $team->captain ?>" size="40" disabled /><input type="hidden" name="captainId" id="captainId" value="<?php echo $team->captainId ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="contactno"><?php _e( 'Contact Number', 'leaguemanager' ) ?></label></th><td><input type="tel" name="contactno" id="contactno" autocomplete="tel" value="<?php echo $team->contactno ?>" size="20" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="contactemail"><?php _e( 'Contact Email', 'leaguemanager' ) ?></label></th><td><input type="email" name="contactemail" id="contactemail" autocomplete="email" value="<?php echo $team->contactemail ?>" size="60" /></td>
            </tr>

            <?php do_action( 'team_edit_form', $team ) ?>
            <?php do_action( 'team_edit_form_'.(isset($league->sport) ? ($league->sport) : '' ), $team ) ?>
        </table>

        <input type="hidden" name="team_id" id="team_id" value="<?php echo $team->id ?>" />
        <input type="hidden" name="league_id" value="<?php echo $league_id ?>" />
        <input type="hidden" name="updateLeague" value="teamPlayer" />
        <input type="hidden" name="season" value="<?php echo $season ?>" />

        <p class="submit"><input type="submit" name="action" value="<?php echo $form_action ?>" class="button button-primary" /></p>
    </form>
</div>
