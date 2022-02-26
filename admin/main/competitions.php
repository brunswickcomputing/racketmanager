<?php
/**
* Competition main page administration panel
*
*/
namespace ns;
?>
<script type='text/javascript'>
jQuery(function() {
  jQuery("#tabs-competitions").tabs({
    active: <?php echo $comptab ?>
  });
});
</script>
<div id="tabs-competitions" class="racketmanager-blocks">
  <ul id="tablist">
    <li><a href="#competitions-cup"><?php _e( 'Cups', 'racketmanager' ) ?></a></li>
    <li><a href="#competitions-league"><?php _e( 'Leagues', 'racketmanager' ) ?></a></li>
    <li><a href="#competitions-tournament"><?php _e( 'Tournaments', 'racketmanager' ) ?></a></li>
  </ul>
  <?php $competitionTypes = array('cup','league','tournament');
  foreach ( $competitionTypes AS $competitionType ) { ?>
    <div id="competitions-<?php echo $competitionType ?>" class="league-block-container">
      <form id="competitions-filter" method="post" action="">
        <?php wp_nonce_field( 'competitions-bulk' ) ?>

        <div class="tablenav">
          <!-- Bulk Actions -->
          <select name="action" size="1">
            <option value="-1" selected="selected"><?php _e('Bulk Actions') ?></option>
            <option value="delete"><?php _e('Delete')?></option>
          </select>
          <input type="submit" value="<?php _e('Apply'); ?>" name="docompdel" id="docompdel" class="btn btn-secondary action" />
        </div>

        <table class="widefat" summary="" title="RacketManager Competitions">
          <thead>
            <tr>
              <th scope="col" class="check-column"><input type="checkbox" onclick="Racketmanager.checkAll(document.getElementById('competitions-filter'));" /></th>
              <th scope="col" class="column-num">ID</th>
              <th scope="col"><?php _e( 'Competition', 'racketmanager' ) ?></th>
              <th scope="col" class="column-num"><?php _e( 'Number of Seasons', 'racketmanager' ) ?></th>
              <th scope="col" class="column-num"><?php _e( 'Leagues', 'racketmanager' ) ?></th>
              <th scope="col" class="column-num"><?php _e( 'Number of Sets', 'racketmanager' ) ?></th>
              <th scope="col" class="column-num"><?php _e( 'Number of Rubbers', 'racketmanager' ) ?></th>
              <th scope="col" class="centered"><?php _e( 'Type', 'racketmanager' ) ?></th>
            </tr>
            <tbody id="the-list">
              <?php $competitions = $racketmanager->getCompetitions( array('type' => $competitionType) );
              $class = '';
              foreach ( $competitions AS $competition ) {
                $competition = get_competition($competition);
                $class = ( 'alternate' == $class ) ? '' : 'alternate'; ?>
                <tr class="<?php echo $class ?>">
                  <th scope="row" class="check-column"><input type="checkbox" value="<?php echo $competition->id ?>" name="competition[<?php echo $competition->id ?>]" /></th>
                  <td class="column-num"><?php echo $competition->id ?></td>
                  <td><a href="index.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a></td>
                  <td class="column-num"><?php echo $competition->num_seasons ?></td>
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

    <?php       } ?>

  </div>

  <h3><?php _e( 'Add Competition', 'racketmanager' ) ?></h3>
  <!-- Add New Competition -->
  <form action="" method="post">
    <?php wp_nonce_field( 'racketmanager_add-competition' ) ?>
    <div class="form-group">
      <label for="competition_name"><?php _e( 'Competition', 'racketmanager' ) ?></label>
      <div class="input">
        <input required="required" placeholder="<?php _e( 'Enter name for new competition', 'racketmanager') ?>" type="text" name="competition_name" id="competition_name" value="" size="30" />
      </div>
    </div>
    <div class="form-group">
      <label for='num_sets'><?php _e('Number of Sets', 'racketmanager') ?></label>
      <div class="input">
        <input required="required" placeholder="<?php _e( 'How many sets', 'racketmanager') ?>" type='number' name='num_sets' id='num_sets' value='' size='3' />
      </div>
    </div>
    <div class="form-group">
      <label for='num_rubbers'><?php _e('Number of Rubbers', 'racketmanager') ?></label>
      <div class="input">
        <input required="required" placeholder="<?php _e( 'How many rubbers', 'racketmanager') ?>" type='number' name='num_rubbers' id='num_rubbers' value='' size='3' />
      </div>
    </div>
    <div class="form-group">
      <label for='competition_type'><?php _e('Competition Type', 'racketmanager') ?></label>
      <div class="input">
        <select size='1' required="required" name='competition_type' id='competition_type'>
          <option><?php _e( 'Select', 'racketmanager') ?></option>
          <option value='WS' <?php if ( isset($competition->type)) ($competition->type == 'WS' ? 'selected' : '') ?>>
            <?php _e( 'Ladies Singles', 'racketmanager') ?>
          </option>
          <option value='WD' <?php if ( isset($competition->type)) ($competition->type == 'WD' ? 'selected' : '') ?>>
            <?php _e( 'Ladies Doubles', 'racketmanager') ?>
          </option>
          <option value='MD' <?php if ( isset($competition->type)) ($competition->type == 'MD' ? 'selected' : '') ?>>
            <?php _e( 'Mens Doubles', 'racketmanager') ?>
          </option>
          <option value='MS' <?php if ( isset($competition->type)) ($competition->type == 'MS' ? 'selected' : '') ?>>
            <?php _e( 'Mens Singles', 'racketmanager') ?>
          </option>
          <option value='XD' <?php if ( isset($competition->type)) ($competition->type == 'XD' ? 'selected' : '') ?>>
            <?php _e( 'Mixed Doubles', 'racketmanager') ?>
          </option>
          <option value='LD' <?php if ( isset($competition->type)) ($competition->type == 'LD' ? 'selected' : '') ?>>
            <?php _e( 'The League', 'racketmanager') ?>
          </option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="mode"><?php _e( 'Mode', 'racketmanager' ) ?></label>
      <div class="input">
        <select size="1" name="mode" id="mode">
          <option><?php _e( 'Select', 'racketmanager') ?></option>
          <?php foreach ( $this->getModes() AS $id => $mode ) { ?>
            <option value="<?php echo $id ?>"><?php echo $mode ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label for="entryType"><?php _e( 'Entry Type', 'racketmanager' ) ?></label>
      <div class="input">
        <select size="1" name="entryType" id="entryType">
          <option><?php _e( 'Select', 'racketmanager') ?></option>
          <?php foreach ( $this->getentryTypes() AS $id => $entryType ) { ?>
            <option value="<?php echo $id ?>"><?php echo $entryType ?></option>
          <?php } ?>
        </select>
      </div>
    </div>

    <input type="hidden" name="addCompetition" value="competition" />
    <input type="submit" name="addCompetition" value="<?php _e( 'Add Competition','racketmanager' ) ?>" class="btn btn-primary" />

  </form>
