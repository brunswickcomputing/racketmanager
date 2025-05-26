<?php

namespace Racketmanager;

/** @var array $options */
?>
<div class="form-control">
  <div class="form-floating mb-3">
    <input type='text' class="form-control" name='color_headers' id='color_headers' value='<?php echo ( isset($options['colors']['headers']) ? ($options['colors']['headers']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" />
    <label for='color_headers'><?php _e( 'Table Headers', 'racketmanager' ) ?></label>
	  <span class="colorbox" style="background-color: <?php echo $options['colors']['headers'] ?>"></span>
  </div>
  <div class="form-control mb-3">
    <label for='color_rows'><?php _e( 'Table Rows', 'racketmanager' ) ?></label>
    <div class="form-floating mb-3">
      <input type='text' class="form-control" name='color_rows_alt' id='color_rows_alt' value='<?php echo (isset($options['colors']['rows']['alternate']) ? ($options['colors']['rows']['alternate']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" />
      <label for='color_rows_alt'><?php _e( 'Alternate Table Rows', 'racketmanager' ) ?></label>
      <span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['alternate'] ?>"></span>
    </div>
    <div class="form-floating mb-3">
      <input type='text' class="form-control" name='color_rows' id='color_rows' value='<?php echo ( isset($options['colors']['rows']['main']) ? ($options['colors']['rows']['main']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" />
      <label for='color_rows'><?php _e( 'Table Rows', 'racketmanager' ) ?></label>
      <span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['main'] ?>"></span>
    </div>
  </div>
  <div class="form-floating mb-3">
    <input type='text' class="form-control" name='color_rows_ascend' id='color_rows_ascend' value='<?php echo ( isset($options['colors']['rows']['ascend']) ? ($options['colors']['rows']['ascend']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" />
    <label for='color_rows_ascend'><?php _e( 'Teams Ascend', 'racketmanager' ) ?></label>
    <span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['ascend'] ?>"></span>
  </div>
  <div class="form-floating mb-3">
    <input type='text' class="form-control" name='color_rows_descend' id='color_rows_descend' value='<?php echo ( isset($options['colors']['rows']['descend']) ? ($options['colors']['rows']['descend']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" />
    <span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['descend'] ?>"></span>
    <label for='color_rows_descend'><?php _e( 'Teams Descend', 'racketmanager' ) ?></label>
  </div>
  <div class="form-floating mb-3">
    <input type='text' class="form-control" name='color_rows_relegation' id='color_rows_relegation' value='<?php echo ( isset($options['colors']['rows']['relegation']) ? ($options['colors']['rows']['relegation']) : '' ) ?>' size='7' class="racketmanager-colorpicker color" />
    <span class="colorbox" style="background-color: <?php echo $options['colors']['rows']['relegation'] ?>"></span>
    <label for='color_rows_relegation'><?php _e( 'Teams Relegation', 'racketmanager' ) ?></label>
  </div>
  <div class="form-control">
    <div class="form-label">
      <label for='color_rows'><?php _e( 'Box Header', 'projectmanager' ) ?></label>
    </div>
    <div class="form-floating mb-3">
      <input type='text' class="form-control" name='color_boxheader1' id='color_boxheader1' value='<?php echo $options['colors']['boxheader'][0] ?>' size='7' class="racketmanager-colorpicker color" />
      <label for='color_boxheader1'><?php _e( 'Box Header', 'projectmanager' ) ?></label>
      <span class="colorbox" style="background-color: <?php echo $options['colors']['boxheader'][0] ?>"></span>
    </div>
    <div class="form-floating mb-3">
      <input type='text' class="form-control" name='color_boxheader2' id='color_boxheader2' value='<?php echo $options['colors']['boxheader'][1] ?>' size='7' class="racketmanager-colorpicker color" />
      <label for='color_boxheader2'><?php _e( 'Box Header Gradient', 'projectmanager' ) ?></label>
      <span class="colorbox" style="background-color: <?php echo $options['colors']['boxheader'][1] ?>"></span>
    </div>
  </div>
</div>
