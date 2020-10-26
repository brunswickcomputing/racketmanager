<?php
/**
 * League main page administration panel
 *
 */
 namespace ns;
?>
<script type='text/javascript'>
	jQuery(function() {
		jQuery("#tabs").tabs({
			active: <?php echo $tab ?>
		});
	});
</script>
<div class="wrap">
	<p class="leaguemanager_breadcrumb">
		<a href="admin.php?page=leaguemanager"><?php _e( 'LeagueManager', 'leaguemanager' ) ?></a>
		&raquo;
		<a href="admin.php?page=leaguemanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a>
		&raquo;
		<?php echo $league->title ?>
	</p>
	<h1><?php echo $league->title ?></h1>

    <?php if ( !empty($competition->seasons) ) { ?>
	<!-- Season Dropdown -->
	<div class="alignright" style="clear: both;">
	<form action="admin.php" method="get" style="display: inline;">
		<input type="hidden" name="page" value="leaguemanager" />
		<input type="hidden" name="subpage" value="show-league" />
		<input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
		<label for="season" style="vertical-align: middle;"><?php _e( 'Season', 'leaguemanager' ) ?></label>
		<select size="1" name="season" id="season">
        <?php foreach ( $competition->seasons AS $s ) { ?>
			<option value="<?php echo htmlspecialchars($s['name']) ?>"<?php if ( $s['name'] == $season ) echo ' selected="selected"' ?>><?php echo $s['name'] ?></option>
        <?php } ?>
		</select>
		<input type="submit" value="<?php _e( 'Show', 'leaguemanager' ) ?>" class="button" />
	</form>
	</div>
    <?php } ?>

	<!-- League Menu -->
	<ul class="subsubsub">
    <?php foreach ( $this->getMenu() AS $key => $menu ) { ?>
	<?php if ( !isset($menu['show']) || $menu['show'] ) { ?>
		<li><a class="button-secondary" href="admin.php?page=leaguemanager&amp;subpage=<?php echo $key ?>&amp;league_id=<?php echo $league->id ?>&amp;season=<?php echo $season ?>&amp;group=<?php echo $group ?>"><?php echo $menu['title'] ?></a></li>
	<?php } ?>
    <?php } ?>
	</ul>


    <?php if ( $league_mode == 'championship' ) {
        $league->championship->displayAdminPage();
    } else { ?>
        <div id="tabs" class="league-blocks">
            <ul id="tablist" style="display: none;">
                <li><a href="#standings-table"><?php _e( 'Standings', 'leaguemanager' ) ?></a></li>
                <li><a href="#crosstable"><?php _e( 'Crosstable', 'leaguemanager' ) ?></a></li>
                <li><a href="#matches-table"><?php _e( 'Match Plan', 'leaguemanager' ) ?></a></li>
            </ul>
            
            <div id="standings-table" class="league-block-container">
                <h2 class="header"><?php _e( 'Table', 'leaguemanager' ) ?></h2>
                <div class="alignright">
                    <form action="admin.php" method="get">
                    <input type="hidden" name="page" value="leaguemanager" />
                    <input type="hidden" name="subpage" value="show-league" />
                    <input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
                    
                    <?php echo $league->getStandingsSelection(); ?>
                    <input type="submit" class="button-secondary" value="<?php _e( 'Show', 'leaguemanager' ) ?>" />
                    </form>
                </div>
                <?php include_once('standings.php'); ?>
            </div>
            
            <!-- crosstable -->
            <div id="crosstable" class="league-block-container">
                <h2 class="header"><?php _e( 'Crosstable', 'leaguemanager' ) ?></h2>
                <?php include('crosstable.php'); ?>
            </div>
        
            <!-- match table -->
            <div id="matches-table" class="league-block-container">
                <h2 class="header"><?php _e( 'Match Plan','leaguemanager' ) ?></h2>
                <?php include('matches.php'); ?>
            </div>
        </div>
    <?php } ?>
</div>
