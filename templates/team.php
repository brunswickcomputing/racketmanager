<?php
/**
Template page to display single team

The following variables are usable:
 
	$league: league object
	$team: team object
	$next_match: next match object
	$prev_match: previous match object

	You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
*/
$season = end($league->seasons)['name'];
$teaminfo = $league->getTeamDtls(get_team_id());
?>

<div class="teampage">

	<h3 class="header"><?php echo the_team_name() ?></h3>

	<div class="tm-team-content">
<?php if ( $league->mode != 'championship' ) { ?>
        <div style='float: right; margin-top: 1em;'>
            <a href="/index.php?league_id=<?php echo $league->id ?>&team_id=<?php echo the_team_id() ?>&team=<?php echo the_team_name() ?>&season=<?php echo $season ?>&racketmanager_export=calendarFile" class="roll-button calendar-add" title="<?php _e( 'Add Matches to Calendar', 'racketmanager' ) ?>" >
                <i class="racketmanager-svg-icon">
                    <?php racketmanager_the_svg('icon-calendar') ?>
                </i>
            </a>
        </div>
<?php } ?>
		<dl class="team">
<?php
    if ( !empty($teaminfo->captain) ) { ?>
			<dt><?php _e( 'Captain', 'racketmanager' ) ?></dt><dd><?php echo $teaminfo->captain ?></dd>
<?php }
    if ( is_user_logged_in() ) {
        if ( !empty($teaminfo->contactno) ) { ?>
			<dt><?php _e( 'Contact Number', 'racketmanager' ) ?></dt><dd><?php echo $teaminfo->contactno ?></dd>
    <?php }
        if ( !empty($teaminfo->contactemail) ) { ?>
			<dt><?php _e( 'Contact Email', 'racketmanager' ) ?></dt><dd><?php echo $teaminfo->contactemail ?></dd>
    <?php }
    } else { ?>
            <dt class="contact-login-msg">You need to <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'].'#teams' ); ?>">login</a> to access captain contact details</dt>
    <?php }
    if ( !empty($teaminfo->match_day) ) { ?>
			<dt><?php _e( 'Match Day', 'racketmanager' ) ?></dt><dd><?php echo $teaminfo->match_day ?></dd>
<?php }
    if ( !empty($teaminfo->match_time) && $teaminfo->match_time > "00:00:00" ) { ?>
			<dt><?php _e( 'Match Time', 'racketmanager' ) ?></dt><dd><?php echo $teaminfo->match_time ?></dd>
<?php } ?>
        </dl>

<?php if ( $league->mode != 'championship' ) { ?>
		<div class="matches">
    <?php if ( has_next_match() ) { ?>
			<div class="matches-container">
                <div class="next_match">
                    <h4 class="header"><?php _e( 'Next Match','racketmanager' ) ?></h4>
                    <div class="content">
                        <p class="match"><?php echo the_match_title() ?></p>
                        <p class='match_day'><?php printf(__("<strong>%d.</strong> Match Day", 'racketmanager'), get_match_day()); ?></p>
                        <p class='match_date'><?php echo mysql2date("j. F Y", the_match_date()) ?>&#160;<span class='time'><?php echo the_match_time() ?></span> <span class='location'><?php echo the_match_location() ?></span></p>
                        <p class="score">&#160;</p>
                    </div>
                </div>
			</div>
    <?php } ?>

    <?php if ( has_prev_match() ) { ?>
			<div class="matches-container">
                <div class="prev_match">
                    <h4 class="header"><?php _e( 'Last Match','racketmanager' ) ?></h4>
                    <div class="content">
                        <p class="match"><?php echo the_match_title() ?></p>
                        <p class='match_day'><?php printf(__("<strong>%d.</strong> Match Day", 'racketmanager'), get_match_day()); ?></p>
                        <p class='match_date'><?php echo mysql2date("j. F Y", the_match_date()) ?>&#160;<span class='time'><?php echo the_match_time() ?></span> <span class='location'><?php echo the_match_location() ?></span></p>
                        <p class="score"><?php echo the_match_score() ?></p>
                    </div>
                </div>
			</div>
    <?php } ?>
		</div>
<?php } ?>
	</div>

</div>
