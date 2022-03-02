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
  <?php $competitionTypes = $this->getCompetitionTypes();
  foreach ( $competitionTypes AS $competitionType ) {
    $season = '';
    $competitionQuery = array( 'type' => $competitionType );
    include('show-competition.php');
  } ?>

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
