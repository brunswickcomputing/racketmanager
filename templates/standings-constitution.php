<?php
/**
* standings table by status template
*/
namespace ns;
?>
<?php if ( have_teams() ) { ?>

  <table class="racketmanager standingstable" summary="" title="<?php _e( 'Standings', 'racketmanager' ) .' '.get_league_title(); ?>">
    <tbody>
      <?php while( have_teams() ) {
        the_team(); ?>
        <tr class='<?php the_team_class(); ?>'>
          <td><?php the_team_name() ?></td>
          <?php if ( show_standings('status') ) { ?><td class="num"><?php the_team_status(); ?></td><?php } ?>
        </tr>
      <?php } ?>
    </tbody>
  </table>

<?php } ?>
