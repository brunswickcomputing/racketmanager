<?php
global $racketmanager;
$matches = $racketmanager->getMatches( array('confirmed' => true) );
$prev_league = 0;
?>
<div class="wrap">
  <div class="league-block">
    <?php wp_nonce_field( 'results-update' ) ?>
    <table class="widefat" summary="" title="<?php _e( 'Match Plan','racketmanager' ) ?>">
      <thead>
        <tr>
          <th><?php _e( 'ID', 'racketmanager' ) ?></th>
          <th><?php _e( 'Date','racketmanager' ) ?></th>
          <th><?php _e( 'Match','racketmanager' ) ?></th>
          <th><?php _e( 'Location','racketmanager' ) ?></th>
          <th><?php _e( 'Begin','racketmanager' ) ?></th>
          <th class="score"><?php _e( 'Score', 'racketmanager' ) ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody id="the-list-matches" class="lm-form-table">
        <?php if ( $matches ) { $class = '';
          foreach ( $matches AS $match ) {
            $match = get_match($match);
            if ( $match->league->is_championship ) {
              $matchLink = 'final='.$match->final_round.'&amp;league-tab=matches';
            } else {
              $matchLink = 'match_day='.$match->match_day;
            }
            $class = ( 'alternate' == $class ) ? '' : 'alternate';
            if ( $prev_league != $match->league_id) {
              $prev_league = $match->league_id; ?>
              <tr>
                <td><?php echo $match->league->title ?>
                </td>
              </tr>
            <?php } ?>
            <tr class="<?php echo $class ?>">
              <td scope="row"><?php echo $match->id ?>
                <input type="hidden" name="matches[<?php echo $match->league->id ?>][<?php echo $match->id ?>]" value="<?php echo $match->id ?>" />
                <input type="hidden" name="home_team[<?php echo $match->league->id ?>][<?php echo $match->id ?>]" value="<?php echo $match->home_team ?>" />
                <input type="hidden" name="away_team[<?php echo $match->league->id ?>][<?php echo $match->id ?>]" value="<?php echo $match->away_team ?>" />
              </td>
              <td><?php echo ( substr($match->date, 0, 10) == '0000-00-00' ) ? 'N/A' : mysql2date($this->date_format, $match->date) ?></td>
              <td class="match-title"><a href="admin.php?page=racketmanager&amp;subpage=match&amp;league_id=<?php echo $match->league_id ?>&amp;edit=<?php echo $match->id ?>&amp;season=<?php echo $match->season ?>"><?php echo $match->match_title ?></a></td>
              <td><?php echo ( empty($match->location) ) ? 'N/A' : $match->location ?></td>
              <td><?php echo ( '00:00' == $match->hour.":".$match->minutes ) ? 'N/A' : mysql2date($this->time_format, $match->date) ?></td>
              <td class="score">
                <?php echo $match->score ?>
              </td>
              <td><a href="admin.php?page=racketmanager&amp;subpage=show-league&amp;league_id=<?php echo $match->league->id ?>&amp;season=<?php echo $match->season ?>&amp;<?php echo $matchLink ?> " class="btn btn-secondary"><?php _e('View match', 'racketmanager') ?></a>
              </td>
            </tr>
          <?php }
        } else { ?>
          <tr><td><?php _e('No matches with pending results', 'racketmanager') ?></td></tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</div>
