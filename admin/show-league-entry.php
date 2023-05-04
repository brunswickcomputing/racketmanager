<div class="container">
  <h3><?php _e( 'Notify League Entry Open', 'racketmanager' ) ?></h3>
  <div class="form-control">
    <form action="" method="post">
      <?php wp_nonce_field( 'racketmanager_notify-league-open' ) ?>
      <div class="form-group">
        <label class="form-label" for="type"><?php _e( 'Season', 'racketmanager' ) ?></label>
        <div class="form-input">
          <select size="1" name="season" id="season" >
            <?php $seasons = $racketmanager->getSeasons( "DESC" );
            foreach ( $seasons AS $season ) { ?>
              <option value="<?php echo $season->name ?>"><?php echo $season->name ?></option>
            <?php } ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="competitiontype"><?php _e( 'Type', 'racketmanager' ) ?></label>
        <div class="form-input">
          <select size="1" name="type" id="type">
            <option disabled selected><?php _e( 'Select type' , 'racketmanager') ?></option>
            <option value="summer"><?php _e( 'Summer', 'racketmanager') ?></option>
            <option value="winter"><?php _e( 'Winter', 'racketmanager') ?></option>
          </select>
        </div>
      </div>
      <input type="hidden" name="notifyLeagueOpen" value="open" />
      <input type="submit" name="notifyLeagueOpen" value="<?php _e( 'Notify league entry open','racketmanager' ) ?>" class="btn btn-primary" />
    </form>
  </div>
</div>
