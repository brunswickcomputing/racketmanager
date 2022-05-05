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
wp_enqueue_style('jquery-style', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css');
?>
<script type="text/javascript">
jQuery(document).ready(function($) {
  $('.date_picker').datepicker({
    dateFormat : 'yy-mm-dd',
    firstDay: 1,
    showOtherMonths: true,
    selectOtherMonths: true,
    changeYear: true,
    changeMonth: true
  });
});
</script>
<div id="racketmanager_match_selections" class="">
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
  <table class='racketmanager matchtable' summary='' title='<?php echo __( 'Daily Match Plan', 'racketmanager' ) ?>'>
    <thead>
    </thead>
    <tbody>
      <?php $leagueTitle = '';
      foreach ( $matches AS $match ) {
        if ( $match->league->title != $leagueTitle ) {
          echo "<tr><th class='league-title' colspan='2'>".$match->league->title."</th></tr>";
          $leagueTitle = $match->league->title; ?>
        <?php } ?>
        <tr class='<?php echo $match->class ?>'>
          <td class='match'>
            <?php if ( $match->league->title != $leagueTitle ) {
              echo "<b>".$match->league->title."</b>";
              $leagueTitle = $match->league->title; ?><br />
            <?php } ?>
            <?php echo $match->start_time." ".$match->location ?><br />
            <?php include('matches-title.php');
            echo $matchTitle;
            ?>
            <?php if ( isset($match->home_points) ) echo "<br />".$match->score ?>
          </td>
        </tr>

      <?php } ?>
    </tbody>
  </table>

<?php } else { ?>
  <p><?php echo __( 'No Matches on selected day', 'racketmanager' ) ?></p>
<?php }?>
