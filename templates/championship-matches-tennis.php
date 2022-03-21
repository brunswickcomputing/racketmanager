<!-- Nav tabs -->
<ul class="nav nav-tabs frontend" id="myTab" role="tablist">
  <?php $i = 0;
  foreach ( $finals AS $final ) { ?>
    <li class="nav-item" role="presentation">
      <button class="nav-link <?php if ( $i == 0 ) { echo 'active'; } ?>" id="final-<?php echo $final->key ?>-tab" data-bs-toggle="pill" data-bs-target="#final-<?php echo $final->key ?>" type="button" role="tab" aria-controls="final-<?php echo $final->key ?>" aria-selected="true"><?php echo $final->name ?></button>
    </li>
    <?php $i ++;
    } ?>
</ul>
<!-- Tab panes -->
<div class="tab-content">
  <?php $i = 0;
  foreach ( $finals AS $final ) { ?>
	<div class="tab-pane fade <?php if ( $i == 0 ) { echo 'show active'; } ?>" id="final-<?php echo $final->key ?>" role="tabpanel" aria-labelledby="final-<?php echo $final->key ?>-tab">
    <?php $matches = $final->matches; ?>
    <?php include('matches-tennis-scores.php'); ?>
	</div>
  <?php $i ++;
  } ?>
</div>
<?php include('matches-tennis-modal.php'); ?>
