<?php
/**
 * Matches by matchday template
 */
 namespace ns;
?>

<?php if (get_match_template_type() == 'accordion') { ?>
<script type='text/javascript'>
    jQuery(function() {
        jQuery(".matchlist").accordion({
            active: <?php the_current_match_day() - 1 ?>
        });
    });
</script>
<?php } ?>

<?php if (get_match_template_type() == 'tabs') { ?>
<script type='text/javascript'>
    jQuery(function() {
        jQuery(".matchlist").tabs({
            active: <?php the_current_match_day() - 1 ?>
        });
    });
</script>
<?php } ?>

<?php if ( is_single_match() ) { ?>
    <?php the_single_match(); ?>
<?php } else { ?>

<?php if ( $matches ) { ?>
<div class="matchlist <?php the_matchlist_class() ?>">
    <?php if (get_match_template_type() == 'tabs') { ?>
    <ul class="tablist">
    <?php for ($i = 1; $i <= get_num_match_days(); $i++) { ?>
        <li><a href="#match_day_tab_<?php echo $i ?>_<?php the_league_id() ?>"><?php printf(__('%d. Match Day', 'leaguemanager'), $i) ?></a></li>
    <?php } ?>
    </ul>
    <?php } ?>
    
    <?php for ($i = 1; $i <= get_num_match_days(); $i++) { ?>
    <div id="match_day_tab_<?php echo $i ?>_<?php the_league_id() ?>" class="match <?php the_matchbox_class() ?>">
        <h3 class="<?php the_matchbox_header_class() ?>"><?php printf(__('%d. Match Day', 'leaguemanager'), $i) ?></h3>
        
        <div class="<?php the_matchbox_content_class() ?>">
            <table class='leaguemanager matchtable' summary='' title='<?php _e( 'Match Plan', 'leaguemanager' ) ?> <?php the_league_title() ?>>'>
                <thead>
                    <tr>
                        <th class='match'><?php _e( 'Match', 'leaguemanager' ) ?></th>
                        <th class='score'><?php _e( 'Score', 'leaguemanager' ) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php $class = ''; ?>
                <?php foreach ( $matches AS $match ) { ?>
                <?php if ($match->match_day == $i) { ?>
                <?php $class = ( $class == 'alternate' ) ? '' : 'alternate'; ?>
                    <tr class='<?php echo $class ?>'>
                        <td class='match'><?php echo $match->match_date." ".$match->start_time." ".$match->location ?><br /><a href="<?php echo $match->pageURL ?>"><?php echo $match->match_title ?></a> <?php echo $match->report ?></td>
                        <td class='score' valign='bottom'><p class='match-score'><?php echo $match->score; ?></p></td>
                    </tr>
                <?php } ?>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php } ?>
</div>
<?php } ?>

<?php } ?>
