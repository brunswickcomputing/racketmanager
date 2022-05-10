<table class="table table-striped align-middle">
  <thead class="table-dark">
    <tr>
      <th scope="row"><?php _e( 'Club', 'racketmanager' ) ?></th>
      <th scope="row"><?php _e( 'Number of Courts', 'racketmanager' ) ?></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($racketmanager->getClubs() as $key => $club) { ?>
      <tr>
        <td scope="row"><?php echo $club->name ?></td>
        <td>
          <input type="number" step="1" min="0" class="small-text" name="settings[numCourtsAvailable][<?php echo $club->id ?>]" id="numCourtsAvailable[<?php echo $club->id ?>]" value="<?php if ( isset($competition->numCourtsAvailable[$club->id]) ) echo $competition->numCourtsAvailable[$club->id] ?>" size="2" />
        </td>
      </tr>
    <?php } ?>
  </tbody>
</table>
