<?php
/**
 * Standings table template
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
            <?php if ( show_standings('won') ) {         ?><th class="num"><?php _e( 'W','leaguemanager' ) ?></th><?php } ?>
            <?php if ( show_standings('tie') ) {         ?><th class="num"><?php _e( 'T','leaguemanager' ) ?></th><?php } ?>
            <?php if ( show_standings('lost') ) {        ?><th class="num"><?php  _e( 'L','leaguemanager' ) ?></th><?php } ?>
            <?php if ( show_standings('winPercent') ) {  ?><th class="num"><?php  _e( 'PCT','leaguemanager' ) ?></th><?php } ?>
            <?php the_standings_header(); ?>
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
            <?php if ( show_standings('won') ) {         ?><td class='num'><?php num_won_matches(); ?></td><?php } ?>
            <?php if ( show_standings('tie') ) {         ?><td class='num'><?php num_draw_matches(); ?></td><?php } ?>
            <?php if ( show_standings('lost') ) {        ?><td class='num'><?php num_lost_matches(); ?></td><?php } ?>
            <?php if ( show_standings('winPercent') ) {  ?><td class="num"><?php win_percentage() ?></td><?php } ?>
            <?php the_standings_columns(); ?>
            <td class='num'><?php the_team_points(); ?></td>
        </tr>
<?php } ?>
    </tbody>
</table>

<?php } ?>

