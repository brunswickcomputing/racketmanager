<?php
/**
 * Team administration panel
 *
 */
 namespace ns;
?>

<div class="wrap league-block">
    <p class="leaguemanager_breadcrumb"><a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a><?php if ( !$noleague ) { ?> &raquo; <a href="admin.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a><?php } ?> &raquo; <?php echo $form_title ?></p>
    <?php if ( !$noleague ) { ?>
    <h1><?php printf( "%s &mdash; %s",  $league->title, $form_title ); ?></h1>
    <?php } else { ?>
    <h1><?php printf(  $form_title ); ?></h1>
    <?php }
if ( !$noleague ) { ?>
    <form action="index.php?page=leaguemanager&amp;subpage=show-league&amp;league_id=<?php echo $league_id ?>&amp;season=<?php echo $season ?>" method="post" enctype="multipart/form-data" name="team_edit">
<?php } else { ?>
    <form action="admin.php?page=leaguemanager&amp;view=teams<?php if ( $clubId !== '' ) { ?>&amp;club_id=<?php echo $clubId ?> <?php } ?>" method="post" enctype="multipart/form-data" name="team_edit">
<?php } ?>
        <?php wp_nonce_field( 'leaguemanager_manage-teams' ) ?>

        <div class="form-group">
            <label for="team"><?php _e( 'Team', 'leaguemanager' ) ?></label>
            <div class="input">
                <input type="text" id="team" name="team" readonly value="<?php echo $team->title ?>" size="30" placeholder="<?php _e( 'Add Team', 'leaguemanager' ) ?>""/>
            </div>
        </div>
        <div class="form-group">
            <label for="affiliatedclub"><?php _e( 'Affiliated Club', 'leaguemanager' ) ?></label>
            <div class="input">
                <select size="1" name="affiliatedclub" id="affiliatedclub" >
                    <option><?php _e( 'Select club' , 'leaguemanager') ?></option>
                    <?php foreach ( $clubs AS $club ) { ?>
                    <option value="<?php echo $club->id ?>"<?php if ( isset($team->affiliatedclub) ) selected($team->affiliatedclub, $club->id) ?>><?php echo $club->name ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="team_type"><?php _e( 'Type', 'leaguemanager' ) ?></label>
            <div class="input">
                <select size='1' required="required" name='team_type' id='team_type'>
                    <option><?php _e( 'Select', 'leaguemanager') ?></option>
                    <option value='WS' <?php if ( isset($team->type) ) selected($team->type, 'WS') ?>><?php _e( 'Ladies Singles', 'leaguemanager') ?></option>
                    <option value='WD' <?php if ( isset($team->type) ) selected($team->type, 'WD') ?>><?php _e( 'Ladies Doubles', 'leaguemanager') ?></option>
                    <option value='MD' <?php if ( isset($team->type) ) selected($team->type, 'MD') ?>><?php _e( 'Mens Doubles', 'leaguemanager') ?></option>
                    <option value='MS' <?php if ( isset($team->type) ) selected($team->type, 'MS') ?>><?php _e( 'Mens Singles', 'leaguemanager') ?></option>
                    <option value='XD' <?php if ( isset($team->type) ) selected($team->type, 'XD') ?>><?php _e( 'Mixed Doubles', 'leaguemanager') ?></option>
                </select>
            </div>
        </div>

        <?php if ( !$noleague ) { ?>
            <div class="form-group">
                <label for="captain"><?php _e( 'Captain', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="text" name="captain" id="captain" autocomplete="name off" value="<?php echo $team->captain ?>" size="30" /><input type="hidden" name="captainId" id="captainId" value="<?php echo $team->captainId ?>" />
                </div>
            </div>
            <div class="form-group">
                <label for="contactno"><?php _e( 'Contact Number', 'leaguemanager' ) ?></label>
                    <div class="input">
                    <input type="tel" name="contactno" id="contactno" autocomplete="tel" value="<?php echo $team->contactno ?>" size="20" />
                </div>
            </div>
            <div class="form-group">
                <label for="contactemail"><?php _e( 'Contact Email', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="email" name="contactemail" id="contactemail" autocomplete="email" value="<?php echo $team->contactemail ?>" size="30" /></td>
                </div>
            </div>
            <div class="form-group">
                <label for="matchtime"><?php _e( 'Match Time', 'leaguemanager' ) ?></label>
                <div class="input">
                    <input type="time" name="matchtime" id="matchtime" value="<?php echo $team->match_time ?>" size="5" />
                </div>
            </div>
            <div class="form-group">
                <label for="matchday"><?php _e( 'Match Day', 'leaguemanager' ) ?></label>
                <div class="input">
                    <select size="1" name="matchday" id="matchday" >
                        <option><?php _e( 'Select match day' , 'leaguemanager') ?></option>
                        <?php foreach ( $matchdays AS $matchday ) { ?>
                        <option value="<?php echo $matchday ?>"<?php if(isset($team->match_day)) selected($matchday, $team->match_day ) ?>><?php echo $matchday ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
        <?php } ?>
        <?php do_action( 'team_edit_form', $team ) ?>
        <?php do_action( 'team_edit_form_'.(isset($league->sport) ? ($league->sport) : '' ), $team ) ?>

        <input type="hidden" name="team_id" id="team_id" value="<?php echo $team->id ?>" />
        <input type="hidden" name="league_id" value="<?php echo $league_id ?>" />
        <input type="hidden" name="updateLeague" value="team" />
        <input type="hidden" name="season" value="<?php echo $season ?>" />
        <?php if ( isset($clubId) ) { ?>
        <input type="hidden" name="clubId" value="<?php echo $clubId ?>" />
        <?php } ?>
        <?php if ( $edit ) { ?>
        <input type="hidden" name="editTeam" value="team" />
        <?php } ?>

        <p class="submit"><input type="submit" name="action" value="<?php echo $form_action ?>" class="button button-primary" /></p>
    </form>
</div>
