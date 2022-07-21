<div class="container">
  <div class="row justify-content-end">
    <div class="col-auto racketmanager_breadcrumb">
      <a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-competition&competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a> &raquo; <?php _e('Season', 'racketmanager') ?>
    </div>
  </div>
	<h1><?php echo __( 'Update', 'racketmanager' ).' '.$competition->name.' '.__( 'Season', 'racketmanager' ).' '.$seasonId ?></h1>
	<form action="" method="post"  class="form-control mb-3">
		<?php wp_nonce_field( 'racketmanager_update-season' ) ?>
		<div class="form-floating mb-3">
			<input type="number" class="form-control" min="1" step="1" name="num_match_days" id="num_match_days" value="<?php echo $season_data['num_match_days'] ?>" size="2" />
			<label for="num_match_days"><?php if ($competition->is_championship) { _e( 'Number of rounds', 'racketmanager' ); } else { _e( 'Number of match days', 'racketmanager' ); } ?></label>
		</div>

    <?php for ($i=0; $i < $season_data['num_match_days'] ; $i++) { ?>
      <div class="form-floating mb-3">
        <?php $matchDay = $i + 1; ?>
        <input type="date" class="form-control" name="matchDate[<?php echo $i ?>]" id="matchDate-<?php echo $i ?>" value="<?php echo isset($season_data['matchDates'][$i]) ? $season_data['matchDates'][$i] : '' ?>" />
        <label><?php echo __('Match Day', 'racketmanager').' '.$matchDay ?></label>
  		</div>
    <?php } ?>

    <input type="hidden" name="competitionId" value="<?php echo $competition->id ?>" />
  	<input type="hidden" name="seasonId" value="<?php echo $seasonId ?>" />
  	<input type="submit" name="saveSeason" class="btn btn-primary mb-3" value="<?php  _e( 'Update Season', 'racketmanager') ?>" />
  </form>
</div>
