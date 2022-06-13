<?php
/**
Template page for the specific match results table

The following variables are usable:

$matches: contains all matches for current league

You can check the content of a variable when you insert the tag <?php var_dump($variable)
*/
global $wp_query;
$postID = $wp_query->post->ID;
?>
<?php if ( $matches ) { ?>
  <table class='table table-striped matchtable' summary='' title='<?php echo __( 'Latest results', 'racketmanager' ) ?>'>
    <thead>
    </thead>
    <tbody>
      <?php $leagueTitle = '';
      foreach ( $matches AS $match ) { ?>
        <?php if ( $match->league->title != $leagueTitle ) { ?>
          <tr class='table-dark'>
            <th class='league-title' colspan='2'><?php echo $match->league->title ?></th>
          </tr>
          <?php $leagueTitle = $match->league->title; ?>
        <?php } ?>
        <tr class='<?php echo $match->class ?>'>
          <td class='match'>
            <?php $matchTitle = get_matchTitle($match);
            if ( $match->league->mode == 'championship' ) { ?>
              <a href="/<?php _e('tournaments', 'racketmanager') ?>/<?php echo sanitize_title($match->league->title) ?>/<?php echo $match->league->current_season['name'] ?>"><?php echo $matchTitle ?></a>
            <?php } else { ?>
              <a href="/<?php _e('leagues', 'racketmanager') ?>/<?php echo sanitize_title($match->league->title) ?>/<?php echo $match->league->current_season['name'] ?>/day<?php echo $match->match_day ?>"><?php echo $matchTitle ?></a>
            <?php } ?>
          </td>
          <td class="score">
            <?php if ( isset($match->home_points) ) {
              echo $match->score;
            } ?>
          </td>
        </tr>

      <?php } ?>
    </tbody>
  </table>

<?php } else { ?>
  <p><?php echo __( 'No recent results', 'racketmanager' ) ?></p>
<?php }?>
