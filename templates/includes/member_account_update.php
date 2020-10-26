<?php
    /**
     * generate member account form
     * done here to allow for password update which regenerates login cookie
     *
     */
    global $leaguemanager_login;
    
    $leaguemanager_login->member_account_form = $leaguemanager_login->generate_member_account_form();
?>
