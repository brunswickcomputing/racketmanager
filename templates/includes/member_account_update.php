<?php
    /**
     * generate member account form
     * done here to allow for password update which regenerates login cookie
     *
     */
    global $racketmanager_login;
    
    $racketmanager_login->member_account_form = $racketmanager_login->generate_member_account_form();
?>
