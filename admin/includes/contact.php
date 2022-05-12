<?php
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
  activaTab('<?php echo $tab ?>');
});
</script>
<div class="container">
  <div class="row justify-content-end">
    <div class="col-auto racketmanager_breadcrumb">
      <a href="admin.php?page=racketmanager"><?php _e( 'RacketManager', 'racketmanager' ) ?></a> &raquo; <a href="admin.php?page=racketmanager&amp;subpage=show-league&league_id=<?php echo $league->id ?>"><?php echo $league->title ?></a> &raquo; <?php _e('Contact', 'racketmanager') ?>
    </div>
  </div>
  <h1><?php _e('Contact clubs', 'racketmanager') ?></h1>
  <!-- Nav tabs -->
  <ul class="nav nav-tabs" id="myTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="compose-tab" data-bs-toggle="tab" data-bs-target="#compose" type="button" role="tab" aria-controls="compose" aria-selected="true"><?php _e( 'Compose', 'racketmanager' ) ?></button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab" aria-controls="preview" aria-selected="false"><?php _e( 'Preview', 'racketmanager' ) ?></button>
    </li>
  </ul>
  <!-- Tab panes -->
  <div class="tab-content">
    <div id="compose" class="tab-pane table-pane active show fade" role="tabpanel" aria-labelledby="compose">
      <form class="g-3 mt-3 form-control" action="admin.php?page=racketmanager&amp;subpage=contact" method="post" enctype="multipart/form-data" name="teams_contact">
        <?php wp_nonce_field( 'racketmanager_contact-teams' ) ?>
        <input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
        <input type="hidden" name="season" value="<?php echo $season ?>" />
        <div class="col-12 form-floating mb-3">
          <input type="text" class="form-control" name="contactTitle" id="contactTitle" placeholder="Enter title" value="<?php echo $emailTitle ?>" />
          <label for="contactTitle"><?php _e('Email title', 'racketmanager'); ?></label>
        </div>
        <div class="col-12 form-floating mb-3">
          <input type="textarea" class="form-control contactText" name="contactIntro" id="contactIntro" placeholder="Enter intro" value="<?php echo $emailIntro ?>" />
          <label for="contactIntro"><?php _e('Email introduction', 'racketmanager'); ?></label>
        </div>
        <?php for ($i=1; $i <= 5; $i++) { ?>
          <div class="col-12 form-floating mb-3">
            <input type="textarea" class="form-control contactBody" rows=20 name="contactBody[<?php echo $i ?>]" id="contactBody-<?php echo $i ?>" placeholder="Enter email text" <?php if (isset($emailBody[$i])) { echo 'value="'.$emailBody[$i].'"'; } ?> />
            <label for="contactBody-<?php echo $i ?>"><?php _e('Paragraph', 'racketmanager'); ?> <?php echo $i ?></label>
          </div>
        <?php } ?>
        <div class="col-12 form-floating mb-3">
          <input type="textarea" class="form-control contactText" name="contactClose" id="contactClose" placeholder="Enter closing" value="<?php echo $emailClose ?>" />
          <label for="contactClose"><?php _e('Email closing', 'racketmanager'); ?></label>
        </div>
        <div class="col-12">
          <button class="btn btn-primary" name="contactTeamPreview"><?php _e('Preview', 'racketmanager') ?></button>
          <a href="admin.php?page=racketmanager&amp;subpage=show-league&league_id=<?php echo $league->id ?>&amp;season=<?php echo $season ?>" class="btn btn-secondary"><?php _e('Cancel', 'racketmanager') ?></a>
        </div>
      </form>
    </div>
    <div id="preview" class="tab-pane table-pane <?php if ( $emailMessage ) { echo 'show active'; } ?> fade" role="tabpanel" aria-labelledby="preview">
      <?php if ( $emailMessage ) { ?>
        <iframe id="iframeMsg" onload='setIframeHeight(this.id)' style="height:200px;width:100%;border:none;overflow:hidden;" srcdoc='<?php echo $emailMessage ?>'></iframe>
      <?php } else { ?>
        <div class="mt-3 mb-3">
          <?php _e('No message to preview', 'racketmanager') ?>
        </div>
      <?php } ?>
      <form class="g-3 form-control" action="admin.php?page=racketmanager&amp;subpage=show-league&league_id=<?php echo $league->id ?>&amp;season=<?php echo $season ?>" method="post" enctype="multipart/form-data" name="teams_contact">
        <?php wp_nonce_field( 'racketmanager_contact-teams-preview' ) ?>
        <input type="hidden" name="league_id" value="<?php echo $league->id ?>" />
        <input type="hidden" name="season" value="<?php echo $season ?>" />
        <input type="hidden" name="emailMessage" value='<?php echo htmlspecialchars($emailMessage) ?>' />
        <div class="col-12">
          <button class="btn btn-primary" name="contactTeam"><?php _e('Send', 'racketmanager') ?></button>
          <button class="btn btn-secondary"><?php _e('Cancel', 'racketmanager') ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
