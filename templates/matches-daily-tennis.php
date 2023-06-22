<?php
/**
Template page for the specific match date match table in tennis

The following variables are usable:

$matches: contains all matches for current league

You can check the content of a variable when you insert the tag <?php var_dump($variable)
*/
global $wp_query;
$postID = $wp_query->post->ID;
wp_enqueue_script('jquery-ui-datepicker');
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
  $('.date_picker').datepicker({
    dateFormat : 'yy-mm-dd',
    firstDay: 1,
  });
});
</script>
<div id="racketmanager_match_selections container" class="">
  <form method="get" action="<?php echo get_permalink($postID); ?>" id="racketmanager_daily_matches">
    <input type="hidden" name="page_id" value="<?php echo $postID ?>" />

    <div class="form-group mb-3">
      <input type="text" name="match_date" id="match_date" class="form-control date_picker" value="<?php echo($match_date) ?>" />
    </div>
    <div class="form-group mb-3">
      <input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
    </div>
  </form>
</div>
<?php if ( $matches ) { ?>
  <table class="table">
    <thead>
    </thead>
    <tbody>
      <?php $leagueTitle = '';
      foreach ( $matches AS $match ) {
        if ( $match->league->title != $leagueTitle ) { ?>
          <tr class='table-dark'>
            <th class='league-title'><?php echo $match->league->title ?></th>
          </tr>
          <?php $leagueTitle = $match->league->title; ?>
        <?php } ?>
        <tr class='<?php echo $match->class ?>'>
          <td class="match-heading col-12">
            <?php echo $match->start_time." ".$match->location ?>
          </td>
          <td class="match-title col-12">
            <?php $matchTitle = get_matchTitle($match); ?>
            <a href="/<?php _e('leagues', 'racketmanager') ?>/<?php echo sanitize_title($match->league->title) ?>/<?php echo $match->league->current_season['name'] ?>/day<?php echo $match->match_day ?>"><?php echo $matchTitle ?></a>
          </td>
          <?php if ( isset($match->home_points) ) { ?>
            <td class="match-score col-12">
              <?php echo $match->score ?>
            </td>
          <?php } ?>
        </tr>
      <?php } ?>
    </tbody>
  </table>

<?php } else { ?>
  <p><?php echo __( 'No Matches on selected day', 'racketmanager' ) ?></p>
<?php }?>
