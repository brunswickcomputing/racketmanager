<div id="member-account-form" class="login-form-container  widecolumn">
    <?php if ( $vars['show_title'] ) { ?>
        <h3><?php _e( 'Member Account', 'leaguemanager' ); ?></h3>
    <?php } ?>

    <?php if ( count( $vars['messages'] ) > 0 ) {
        foreach ( $vars['messages'] as $message ) { ?>
            <p class="message-<?php echo $message['type'] ?>"><?php echo $message['text']; ?></p>
        <?php }
    } ?>

    <form name="memberaccountform" id="memberaccountform" action="<?php echo site_url( 'member-account' ); ?>" method="post" autocomplete="off">
        <?php wp_nonce_field( 'member_account_nonce', 'member_account_nonce_field' ); ?>

        <fieldset>
            <h4><?php _e( 'Personal Information', 'leaguemanager' ) ?></h4>
            <p>
                <label for="username"><?php _e( 'Username', 'leaguemanager' ) ?></label>
                <input type="email" required="required" placeholder="<?php _e( 'Email Address', 'leaguemanager' ) ?>" name="username" id="username" class="input" value="<?php echo $vars['user-name'] ?>" <?php if (isset($vars['user-name']) && $vars['user-name'] != '' ) echo 'disabled' ?> />
            </p>
            <p>
                <label for="firstname"><?php _e( 'First Name', 'leaguemanager' ) ?></label>
                <input type="text" required="required" autocomplete='given-name' placeholder="<?php _e( 'First Name', 'leaguemanager' ) ?>" name="firstname" id="firstname" class="input" value="<?php echo $vars['user-firstname'] ?>" />
            </p>
            <p>
                <label for="lastname"><?php _e( 'Last Name', 'leaguemanager' ) ?></label>
                <input type="text" required="required" autocomplete='family-name' placeholder="<?php _e( 'Last Name', 'leaguemanager' ) ?>" name="lastname" id="lastname" class="input" value="<?php echo $vars['user-lastname'] ?>" />
            </p>
            <p>
                <label for="contactno"><?php _e( 'Telephone Number', 'leaguemanager' ) ?></label>
                <input type="tel" autocomplete='tel' placeholder="<?php _e( 'Telephone Number', 'leaguemanager' ) ?>" name="contactno" id="contactno" class="input" value="<?php echo $vars['user-contactno'] ?>" />
            </p>
             <p>
                <label class="label-radio" for="gender"><?php _e( 'Gender', 'leaguemanager' ) ?></label>
                <input type="radio" required="required" name="gender" value="M"<?php echo ($vars['user-gender'] == 'M') ? 'checked' : '' ?>> <?php _e('Male', 'leaguemanager') ?><br />
                <input type="radio" name="gender" value="F" <?php echo ($vars['user-gender'] == 'F') ? 'checked' : '' ?>> <?php _e('Female', 'leaguemanager') ?>

            </p>
             <p>
                <label for="btm"><?php _e( 'BTM Number', 'leaguemanager' ) ?></label>
                <input type="tel" placeholder="<?php _e( 'BTM Number', 'leaguemanager' ) ?>" name="btm" id="btm" class="input" value="<?php echo $vars['user-btm'] ?>" />
            </p>
       </fieldset>

        <fieldset>
            <h4><?php _e( 'Change Password', 'leaguemanager' ) ?></h4>
            <p><?php _e('When both password fields are left empty, your password will not change', 'leaguemanager'); ?></p>
            <p>
                <label class="hidden" for="pass1"><?php _e( 'New password', 'leaguemanager' ) ?></label>
                <input type="password" placeholder="<?php _e( 'New password', 'leaguemanager' ) ?>" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" />
            </p>
            <p>
                <label class="hidden" for="pass2"><?php _e( 'Repeat new password', 'leaguemanager' ) ?></label>
                <input type="password" placeholder="<?php _e( 'Repeat new password', 'leaguemanager' ) ?>" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" />
            </p>
        </fieldset>

        <p class="memberaccount-submit">
            <input type="submit" name="submit" id="memberaccount-button"
                   class="button" value="<?php _e( 'Update Details', 'leaguemanager' ); ?>" />
            <input name="action" type="hidden" id="action" value="update-user" />
        </p>
    </form>
</div>
