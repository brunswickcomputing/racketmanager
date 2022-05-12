<?php
?>
<?php include('email-header.php'); ?>
<!-- START MAIN CONTENT AREA -->
<tr>
  <td class="wrapper">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
      <tr>
        <td>
          <div>
            <h1 class="align-center"><?php echo ucfirst($title) ?></h1>
            <p>Dear Captain</p>
            <?php if ( $intro ) { ?>
              <p><?php echo $intro ?></p>
            <?php } ?>
            <?php foreach ($body as $i => $bodyEntry) { ?>
              <p><?php echo $bodyEntry; ?></p>
            <?php } ?>
            <?php if ( $closing ) { ?>
              <p><?php echo $closing ?></p>
            <?php } ?>
            <p>Thanks</p>
            <p>The <?php echo $organisationName ?> Team</p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
<!-- END MAIN CONTENT AREA -->
</table>
<!-- END CENTERED WHITE CONTAINER -->
<?php include('email-footer.php'); ?>
