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
        <table class='leaguemanager matchtable' summary='' title='<?php echo __( 'Latest results', 'leaguemanager' ) ?>'>
            <thead>
            </thead>
            <tbody>
              <?php $leagueTitle = '';
              foreach ( $matches AS $match ) { ?>
                <?php if ( $match->league->title != $leagueTitle ) {
                  echo "<tr><th class='league-title' colspan='2'>".$match->league->title."</th></tr>";
                  $leagueTitle = $match->league->title; ?>
                <?php } ?>
                <tr class='<?php echo $match->class ?>'>
                    <td class='match'>
                      <?php include('matches-title.php');
                      if ( $match->league->mode == 'championship' ) {
                        echo "<a href='/".__('tournaments', 'leaguemanager')."/".sanitize_title($match->league->title)."/".$match->league->current_season['name']."' >".$matchTitle."</a>";
                      } else {
                        echo "<a href='/".__('leagues', 'leaguemanager')."/".sanitize_title($match->league->title)."/".$match->league->current_season['name']."/day".$match->match_day."' >".$matchTitle."</a>";
                      } ?>
                    </td>
                    <td class="score">
                      <?php if ( isset($match->home_points) ) echo $match->score ?>
                    </td>
                </tr>

            <?php } ?>
            </tbody>
        </table>

	<?php } else { ?>
        <p><?php echo __( 'No recent results', 'leaguemanager' ) ?></p>
	<?php }?>
