<div id="racketmanager_archive_selections" class="">
  <form method="get" action="<?php echo get_permalink($postID); ?>" id="racketmanager_archive">
    <div class="row g-1 align-items-center">
      <input type="hidden" name="page_id" value="<?php echo $postID ?>" />
      <div class="form-floating col-auto">
        <select class="form-select" size="1" name="league_id" id="league_id">
          <option value=""><?php _e( 'Select League', 'racketmanager' ) ?></option>
          <?php foreach ( $leagues AS $l ) { ?>
            <option value="<?php echo seoUrl($l->title) ?>"<?php if ( $l->id == $league->id ) echo ' selected="selected"' ?>><?php echo $l->title ?></option>
          <?php } ?>
        </select>
        <label for="league_id"><?php _e('League', 'racketmanager') ?></label>
      </div>
      <div class="form-floating col-auto">
        <select class="form-select" size="1" name="season" id="season">
          <option value=""><?php _e( 'Season', 'racketmanager' ) ?></option>
          <?php foreach ( array_reverse($seasons) AS $key => $season ) { ?>
            <option value="<?php echo $season['name'] ?>"<?php if ( $season['name'] == $league->current_season['name'] ) echo ' selected="selected"' ?>><?php echo $season['name'] ?></option>
          <?php } ?>
        </select>
        <label for="season"><?php _e('Season', 'racketmanager') ?></label>
      </div>
      <div class="col-auto">
        <input type="submit" class="submit" value="<?php _e( 'Show' ) ?>" />
      </div>
    </div>
  </form>
</div>
