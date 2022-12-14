<?php
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
  activaTab('<?php echo $tab ?>');
});
</script>
<div class="container">

  <h1><?php _e( 'Racketmanager Finances', 'racketmanager' ) ?></h1>

  <div class="container">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="charges-tab" data-bs-toggle="tab" data-bs-target="#charges" type="button" role="tab" aria-controls="charges" aria-selected="true"><?php _e( 'Charges', 'racketmanager' ) ?></button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab" aria-controls="invoices" aria-selected="true"><?php _e( 'Invoices', 'racketmanager' ) ?></button>
      </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
      <div class="tab-pane fade" id="charges" role="tabpanel" aria-labelledby="charges-tab">
        <h2 class="header"><?php _e( 'Charges', 'racketmanager' ) ?></h2>
        <?php include('finances/charges.php'); ?>
      </div>
      <div class="tab-pane fade" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
        <h2 class="header"><?php _e( 'Invoices', 'racketmanager' ) ?></h2>
        <?php include('finances/invoices.php'); ?>
      </div>
    </div>
  </div>
</div>
