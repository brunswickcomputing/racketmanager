<?php
/**
 * Slim standings table template
 */
 namespace ns;
?>
<?php if ( isset($_GET['team']) && !$widget ) { ?>
//    <?php the_single_team(); ?>
<?php } elseif ( have_teams() ) { ?>

<table class="leaguemanager standingstable" summary="" title="<?php _e( 'Standings', 'leaguemanager' ) .' '.get_league_title(); ?>">
    <thead>
        <tr>
            <th class="num"><?php echo _e( 'Pos', 'leaguemanager' ) ?></th>
            <?php if ( show_standings('status') ) {      ?><th class="num">&#160;</th><?php } ?>
            <th><?php _e( 'Team', 'leaguemanager' ) ?></th>
            <?php if ( show_standings('pld') ) {         ?><th class="num"><?php _e( 'Pld', 'leaguemanager' ) ?></th><?php } ?>
            <th class="num"><?php _e( 'Pts', 'leaguemanager' ) ?></th>
        </tr>
    </thead>
    <tbody>
<?php while( have_teams() ) {
    the_team();
?>
        <tr class='<?php the_team_class(); ?>'>
            <td class='num'><span class="rank"><?php the_team_rank(); ?></span></td>
            <?php if ( show_standings('status') ) {      ?><td class="num"><?php the_team_status(); ?></td><?php } ?>
            <td><?php the_team_name_url(show_standings('team_link')) ?></td>
            <?php if ( show_standings('pld') ) {         ?><td class='num'><?php num_done_matches(); ?></td><?php } ?>
            <td class='num'><?php the_team_points(); ?></td>
        </tr>
<?php } ?>
    </tbody>
</table>

<?php } ?>

