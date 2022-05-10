<?php
/**
* League main page administration panel
*
*/
namespace ns;
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
  activaTab('<?php echo $tab ?>');
});
</script>
<div class="container">
  <div class="row justify-content-end">
    <div class="col-auto racketmanager_breadcrumb">
      <a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-competition&amp;competition_id=<?php echo $competition->id ?>"><?php echo $competition->name ?></a> &raquo; <?php echo $league->title ?>
    </div>
  </div>
  <h1><?php echo $league->title ?></h1>
  <!-- League Menu -->
  <div class="container">
    <div class="row justify-content-between">
      <div class="col-auto">
        <?php foreach ( $this->getMenu() AS $key => $menu ) { ?>
          <?php if ( isset($menu['show']) && $menu['show'] ) { ?>
            <a class="btn btn-secondary" href="admin.php?page=racketmanager&amp;subpage=<?php echo $key ?>&amp;league_id=<?php echo $league->id ?>&amp;season=<?php echo $season ?>&amp;group=<?php echo $group ?>"><?php echo $menu['title'] ?></a>
          <?php } ?>
        <?php } ?>
      </div>
      <?php if ( !empty($competition->seasons) ) { ?>
        <!-- Season Dropdown -->
        <div class="col-auto">
          <form action="admin.php" method="get" class="form-control">
            <input type="hidden" name="page" value="racketmanager" />
            <input type="hidden" name="subpage" value="show-league" />
            <input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
            <label for="season" style="vertical-align: middle;"><?php _e( 'Season', 'racketmanager' ) ?></label>
            <select size="1" name="season" id="season">
              <?php foreach ( $competition->seasons AS $s ) { ?>
                <option value="<?php echo htmlspecialchars($s['name']) ?>"<?php if ( $s['name'] == $season ) { echo ' selected="selected"'; } ?>><?php echo $s['name'] ?></option>
              <?php } ?>
            </select>
            <input type="submit" value="<?php _e( 'Show', 'racketmanager' ) ?>" class="btn btn-secondary" />
          </form>
        </div>
      <?php } ?>
    </div>
  </div>

  <?php if ( $league_mode == 'championship' ) {
    $league->championship->displayAdminPage();
  } else { ?>
    <div class="container">
      <!-- Nav tabs -->
      <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="standings-tab" data-bs-toggle="tab" data-bs-target="#standings" type="button" role="tab" aria-controls="standings" aria-selected="true"><?php _e( 'Standings', 'racketmanager' ) ?></button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="crosstable-tab" data-bs-toggle="tab" data-bs-target="#crosstable" type="button" role="tab" aria-controls="crosstable" aria-selected="false"><?php _e( 'Crosstable', 'racketmanager' ) ?></button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="matches-tab" data-bs-toggle="tab" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php _e( 'Match Plan', 'racketmanager' ) ?></button>
        </li>
      </ul>
      <!-- Tab panes -->
      <div class="tab-content">
        <div class="tab-pane fade" id="standings" role="tabpanel" aria-labelledby="standings-tab">
          <h2><?php _e( 'Standings', 'racketmanager' ) ?></h2>
          <?php include_once(RACKETMANAGER_PATH . '/admin/includes/standings.php'); ?>
        </div>
        <div class="tab-pane fade" id="crosstable" role="tabpanel" aria-labelledby="crosstable-tab">
          <h2><?php _e( 'Crosstable', 'racketmanager' ) ?></h2>
          <?php include_once(RACKETMANAGER_PATH . '/admin/includes/crosstable.php'); ?>
        </div>
        <div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
          <h2><?php _e( 'Matches', 'racketmanager' ) ?></h2>
          <?php include_once(RACKETMANAGER_PATH . '/admin/includes/matches.php'); ?>
        </div>
      </div>
    </div>
  <?php } ?>
</div>
