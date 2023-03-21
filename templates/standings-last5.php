<?php
/**
* Standings table template
*/
?>
<?php if ( have_teams() ) { ?>
  <div class="table-responsive">
    <table class="table table-striped align-middle" summary="" title="<?php _e( 'Standings', 'racketmanager' ) .' '.get_league_title(); ?>">
      <thead class="table-dark">
        <tr>
          <th class="num"><?php echo _e( 'Pos', 'racketmanager' ) ?></th>
          <?php if ( show_standings('status') ) {      ?><th class="num d-none d-md-table-cell">&#160;</th><?php } ?>
          <th class="team"><?php _e( 'Team', 'racketmanager' ) ?></th>
          <?php if ( show_standings('pld') ) {         ?><th class="num"><?php _e( 'Pld', 'racketmanager' ) ?></th><?php } ?>
          <?php if ( show_standings('won') ) {         ?><th class="num"><?php _e( 'W','racketmanager' ) ?></th><?php } ?>
          <?php if ( show_standings('tie') ) {         ?><th class="num"><?php _e( 'T','racketmanager' ) ?></th><?php } ?>
          <?php if ( show_standings('lost') ) {        ?><th class="num"><?php  _e( 'L','racketmanager' ) ?></th><?php } ?>
          <?php if ( show_standings('winPercent') ) {  ?><th class="num"><?php  _e( 'PCT','racketmanager' ) ?></th><?php } ?>
          <?php the_standings_header(); ?>
          <th class="num d-none d-md-table-cell"><?php _e( 'Pts Adjust', 'racketmanager' ) ?></th>
          <th class="num"><?php _e( 'Pts', 'racketmanager' ) ?></th>
          <?php if ( show_standings('last5') ) {       ?><th class="last5 d-none d-md-table-cell"><?php _e( 'Last 5', 'racketmanager' ) ?></th><?php } ?>
        </tr>
      </thead>
      <tbody>
        <?php while( have_teams() ) {
          the_team();
          ?>
          <tr class='<?php the_team_class(); ?>'>
            <td class='num'><span class="rank"><?php the_team_rank(); ?></span></td>
            <?php if ( show_standings('status') ) {      ?><td class="num d-none d-md-table-cell"><?php the_team_status(); ?></td><?php } ?>
            <td><?php the_team_name() ?></td>
            <?php if ( show_standings('pld') ) {         ?><td class='num'><?php num_done_matches(); ?></td><?php } ?>
            <?php if ( show_standings('won') ) {         ?><td class='num'><?php num_won_matches(); ?></td><?php } ?>
            <?php if ( show_standings('tie') ) {         ?><td class='num'><?php num_draw_matches(); ?></td><?php } ?>
            <?php if ( show_standings('lost') ) {        ?><td class='num'><?php num_lost_matches(); ?></td><?php } ?>
            <?php if ( show_standings('winPercent') ) {  ?><td class="num"><?php win_percentage() ?></td><?php } ?>
            <?php the_standings_columns(); ?>
            <td class='num d-none d-md-table-cell'><?php the_team_points_adjust(); ?></td>
            <td class='num'><?php the_team_points(); ?></td>
            <?php if ( show_standings('last5') ) {       ?><td class="last5Icon last5 d-none d-md-table-cell"><?php the_last5_matches(); ?></td><?php } ?>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>

<?php } ?>
