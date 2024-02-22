<?php
$organisationName = $vars['site_name'];
$sitename         = $vars['site_name'];
$siteurl          = $vars['site_url'];
$userlogin        = $vars['user_login'];
$username         = $vars['display_name'];
$loginurl         = $vars['site_url'] . '/login';
$actionurl        = $vars['action_url'];
$emaillink        = $vars['email_link'];
$email_subject    = 'Welcome Email';
?>
<?php require 'email-header.php'; ?>
<!-- START MAIN CONTENT AREA -->
<tr>
	<td class="wrapper">
	<table role="presentation" border="0" cellpadding="0" cellspacing="0">
		<tr>
		<td>
			<div>
			<h1>Welcome, <?php echo $username; ?>!</h1>
			<p>Thanks for joining <?php echo $organisationName; ?>. We’re delighted to have you on board.</p>
			<p>To get the most out of <?php echo $organisationName; ?>, you need to complete the registration and chose a password:</p>
			<!-- Action -->
			<table class="body-action" align="center" width="100%" cellpadding="0" cellspacing="0">
				<tr>
				<td class="align-center">
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td align="align-center">
						<table border="0" cellspacing="0" cellpadding="0">
							<tr>
							<td>
								<a href="<?php echo $actionurl; ?>" class="button button--green" target="_blank">Complete Registration</a>
							</td>
							</tr>
						</table>
						</td>
					</tr>
					</table>
				</td>
				</tr>
			</table>
			<p>For reference, here's your login information:</p>
			<table class="attributes" width="100%" cellpadding="0" cellspacing="0">
				<tr>
					<td class="attributes_content">
						<table width="100%" cellpadding="0" cellspacing="0">
							<tr>
								<td class="attributes_item"><strong>Login Page:</strong> <?php echo $loginurl; ?></td>
							</tr>
							<tr>
								<td class="attributes_item"><strong>Username:</strong> <?php echo $userlogin; ?></td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<p>If you have any questions, feel free to <a href="mailto:<?php echo $emaillink; ?>">email us</a>.</p>
			<p>Thanks,</p>
			<p>The <?php echo $organisationName; ?> Team</p>
			<!-- Sub copy -->
			<table class="body-sub">
				<tr>
				<td>
					<p class="sub">If you’re having trouble with the button above, copy and paste the URL below into your web browser.</p>
					<p class="sub"><?php echo $actionurl; ?></p>
				</td>
				</tr>
			</table>
			</div>
		</td>
		</tr>
	</table>
	</td>
</tr>

<!-- END MAIN CONTENT AREA -->
</table>
<!-- END CENTERED WHITE CONTAINER -->
<?php require 'email-footer.php'; ?>
