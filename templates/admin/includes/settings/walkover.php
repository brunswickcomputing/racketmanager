<div class="form-control">
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Walkover', 'racketmanager' ); ?></legend>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="text" class="form-control" name='walkoverFemale' id='walkoverFemale' value='<?php echo $options['player']['walkover']['female'] ?? '' ?>'/>
                <label for='walkoverFemale'><?php _e( 'Walkover player female', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="text" class="form-control" name='walkoverMale' id='walkoverMale' value='<?php echo $options['player']['walkover']['male'] ?? '' ?>'/>
                <label for='walkoverMale'><?php _e( 'Walkover player male', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="number" class="form-control" name='walkoverPointsRubber' id='walkoverPointsRubber' value='<?php echo $options['player']['walkover']['rubber'] ?? '' ?>'/>
                <label for='walkoverPointsRubber'><?php _e( 'Walkover points per rubber', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-floating">
                <input type="number" class="form-control" name='walkoverPointsMatch' id='walkoverPointsMatch' value='<?php echo $options['player']['walkover']['match'] ?? '' ?>'/>
                <label for='walkoverPointsMatch'><?php _e( 'Walkover points per match', 'racketmanager' ) ?></label>
            </div>
        </div>
    </fieldset>
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Missing', 'racketmanager' ); ?></legend>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="text" class="form-control" name='noPlayerFemale' id='noPlayerFemale' value='<?php echo $options['player']['noplayer']['female'] ?? '' ?>'/>
                <label for='noPlayerFemale'><?php _e( 'Missing player female', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-floating">
                <input type="text" class="form-control" name='noPlayerMale' id='noPlayerMale' value='<?php echo $options['player']['noplayer']['male'] ?? '' ?>'/>
                <label for='noPlayerMale'><?php _e( 'Missing player male', 'racketmanager' ) ?></label>
            </div>
        </div>
    </fieldset>
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Share', 'racketmanager' ); ?></legend>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="text" class="form-control" name='shareFemale' id='shareFemale' value='<?php echo $options['player']['share']['female'] ?? '' ?>'/>
                <label for='shareFemale'><?php _e( 'Share player female', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="text" class="form-control" name='shareMale' id='shareMale' value='<?php echo $options['player']['share']['male'] ?? '' ?>'/>
                <label for='shareMale'><?php _e( 'Share player male', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-floating">
                <input type="number" step="0.1" class="form-control" name='sharePoints' id='sharePoints' value='<?php echo $options['player']['share']['rubber'] ?? '' ?>'/>
                <label for='sharePoints'><?php _e( 'Share points', 'racketmanager' ) ?></label>
            </div>
        </div>
    </fieldset>
    <fieldset class="row gx-3 mb-3">
        <legend><?php esc_html_e( 'Unregistered', 'racketmanager' ); ?></legend>
        <div class="col-md-3 mb-3 mb-md-0">
            <div class="form-floating">
                <input type="text" class="form-control" name='unregisteredFemale' id='unregisteredFemale' value='<?php echo $options['player']['unregistered']['female'] ?? '' ?>'/>
                <label for='unregisteredFemale'><?php _e( 'Unregistered player female', 'racketmanager' ) ?></label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-floating">
                <input type="text" class="form-control" name='unregisteredMale' id='unregisteredMale' value='<?php echo $options['player']['unregistered']['male'] ?? '' ?>'/>
                <label for='unregisteredMale'><?php _e( 'Unregistered player male', 'racketmanager' ) ?></label>
            </div>
        </div>
    </fieldset>
</div>
