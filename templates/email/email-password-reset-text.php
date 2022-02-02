<?php
    $productname = $vars['site_name'];
    $sitename = $vars['site_name'];
    $siteurl = $vars['site_url'];
    $supporturl = $vars['support_url'];
    $userlogin = $vars['user_login'];
    $username = $vars['display_name'];
    $actionurl = $vars['action_url'];
    $emaillink = $vars['email_link'];
    ?>

Hi <?php echo $username ?>,

You recently requested to reset your password for your <?php echo $productname ?> account. Use the button below to reset it. This password reset is only valid for the next 24 hours.

Reset your password ( <?php echo $actionurl ?> )

If you did not request a password reset, please ignore this email or contact support ( <?php echo $emaillink ?> ) if you have questions.

Thanks,
The <?php echo $productname ?> Team

If you’re having trouble with the button above, copy and paste the URL below into your web browser.

<?php echo $actionurl ?>

© 2018 <?php echo $sitename ?>. All rights reserved.

<?php echo $sitename ?>

