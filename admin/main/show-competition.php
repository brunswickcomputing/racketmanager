<div id="competitions-<?php echo $competitionType ?>" class="league-block-container">
  <form id="competitions-filter" method="post" action="">
    <?php wp_nonce_field( 'competitions-bulk' ) ?>

    <?php if ( !$type ) { ?>
      <div class="tablenav">
        <!-- Bulk Actions -->
        <select name="action" size="1">
          <option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
          <option value="delete"><?php _e('Delete')?></option>
        </select>
        <input type="submit" value="<?php _e('Apply'); ?>" name="docompdel" id="docompdel" class="btn btn-secondary action" />
      </div>
    <?php } ?>

    <table class="widefat" summary="" title="RacketManager Competitions">
      <thead>
        <tr>
          <?php if ( !$type ) { ?>
            <th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('competitions-filter'));" /></th>
          <?php } ?>
          <th scope="col" class="column-num">ID</th>
          <th scope="col"><?php _e( 'Competition', 'racketmanager' ) ?></th>
          <?php if ( !$type ) { ?>
            <th scope="col" class="column-num"><?php _e( 'Number of Seasons', 'racketmanager' ) ?></th>
          <?php } ?>
          <th scope="col" class="column-num"><?php _e( 'Leagues', 'racketmanager' ) ?></th>
          <th scope="col" class="column-num"><?php _e( 'Number of Sets', 'racketmanager' ) ?></th>
          <th scope="col" class="column-num"><?php _e( 'Number of Rubbers', 'racketmanager' ) ?></th>
          <th scope="col" class="centered"><?php _e( 'Type', 'racketmanager' ) ?></th>
        </tr>
      </thead>
      <tbody id="the-list">
        <?php $competitions = $racketmanager->getCompetitions( $competitionQuery );
        $class = '';
        foreach ( $competitions AS $competition ) {
          $competition = get_competition($competition);
          $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
          <tr class="<?php echo $class ?>">
            <?php if ( !$type ) { ?>
              <th scope="row" class="check-column"><input type="checkbox" value="<?php echo $competition->id ?>" name="competition[<?php echo $competition->id ?>]" /></th>
            <?php } ?>
            <td class="column-num"><?php echo $competition->id ?></td>
            <td><a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a></td>
            <?php if ( !$type ) { ?>
              <td class="column-num"><?php echo $competition->num_seasons ?></td>
            <?php } ?>
            <td class="column-num"><?php echo $competition->num_leagues ?></td>
            <td class="column-num"><?php echo $competition->num_sets ?></td>
            <td class="column-num"><?php echo $competition->num_rubbers ?></td>
            <td>
              <?php switch ($competition->type) {
                case 'WS': _e( 'Ladies Singles', 'racketmanager' ); break;
                case 'WD': _e( 'Ladies Doubles', 'racketmanager' ); break;
                case 'MS': _e( 'Mens Singles', 'racketmanager' ); break;
                case 'MD': _e( 'Mens Doubles', 'racketmanager' ); break;
                case 'XD': _e( 'Mixed Doubles', 'racketmanager' ); break;
                case 'LD': _e( 'The League', 'racketmanager' ); break;
              } ?>
            </td>
          </tr>
        <?php } ?>
      </tbody>
    </table>
  </form>
</div>
