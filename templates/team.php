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
?>

<div class="teampage">

	<h3 class="header"><?php echo $team->title ?></h3>

	<div class="tm-team-content">
<?php if ( $league->mode != 'championship' ) { ?>
        <div style='float: right; margin-top: 1em;'>
            <a href="/index.php?league_id=<?php echo $league->id ?>&team_id=<?php echo $team->id ?>&season=<?php echo $season ?>&leaguemanager_export=calendarFile" class="roll-button" >Add Matches to Calendar</a>
        </div>
<?php } ?>
		<dl class="team">
<?php
    if ( !empty($team->captain) ) { ?>
			<dt><?php _e( 'Captain', 'leaguemanager' ) ?></dt><dd><?php echo $team->captain ?></dd>
<?php }
    if ( is_user_logged_in() ) {
        if ( !empty($team->contactno) ) { ?>
			<dt><?php _e( 'Contact Number', 'leaguemanager' ) ?></dt><dd><?php echo $team->contactno ?></dd>
    <?php }
        if ( !empty($team->contactemail) ) { ?>
			<dt><?php _e( 'Contact Email', 'leaguemanager' ) ?></dt><dd><?php echo $team->contactemail ?></dd>
    <?php }
    } else { ?>
            <dt class="contact-login-msg">You need to <a href="<?php echo wp_login_url( $_SERVER['REQUEST_URI'].'#teams' ); ?>">login</a> to access captain contact details</dt>
    <?php }
    if ( !empty($team->match_day) ) { ?>
			<dt><?php _e( 'Match Day', 'leaguemanager' ) ?></dt><dd><?php echo $team->match_day ?></dd>
<?php }
    if ( !empty($team->match_time) && $team->match_time > "00:00:00" ) { ?>
			<dt><?php _e( 'Match Time', 'leaguemanager' ) ?></dt><dd><?php echo $team->match_time ?></dd>
<?php } ?>
        </dl>

		<?php if ( !empty($team->logo) ) : ?>
		<p class="teamlogo alignright"><img src="<?php echo $leaguemanager->getImageUrl($team->logo, false, 'thumb') ?>" alt="<?php _e( 'Logo', 'leaguemanager' ) ?>" /></p>
		<?php endif; ?>
<?php if ( $league->mode != 'championship' ) { ?>
		<div class="matches">
<?php if ( $team->next_match ) { ?>
			<div class="matches-container">
			<div class="next_match">
				<h4 class="header"><?php _e( 'Next Match','leaguemanager' ) ?></h4>
				<div class="content">
					<p class="match"><?php echo $team->next_match->match ?></p>
					<p class='match_day'><?php printf(__("<strong>%d.</strong> Match Day", 'leaguemanager'), $team->next_match->match_day); ?></p>
					<p class='match_date'><?php echo mysql2date("j. F Y", $team->next_match->match_date) ?>&#160;<span class='time'><?php echo $team->next_match->start_time ?></span> <span class='location'><?php echo $team->next_match->location ?></span></p>
					<p class="score">&#160;</p>
				</div>
			</div>
			</div>
<?php } ?>

<?php if ( $team->prev_match ) { ?>
			<div class="matches-container">
			<div class="prev_match">
				<h4 class="header"><?php _e( 'Last Match','leaguemanager' ) ?></h4>
				<div class="content">
					<p class="match"><?php echo $team->prev_match->match ?></p>
					<p class='match_day'><?php printf(__("<strong>%d.</strong> Match Day", 'leaguemanager'), $team->prev_match->match_day); ?></p>
					<p class='match_date'><?php echo mysql2date("j. F Y", $team->prev_match->match_date) ?>&#160;<span class='time'><?php echo $team->prev_match->start_time ?></span> <span class='location'><?php echo $team->prev_match->location ?></span></p>
					<p class="score"><?php echo $team->prev_match->score ?></p>
				</div>
			</div>
			</div>
<?php } ?>
		</div>
<?php } ?>
	</div>

</div>
