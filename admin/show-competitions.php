<?php
/**
* Competitions main page administration panel
*
*/
namespace ns;
?>
<div class="container mb-3">
  <h1><?php echo $pageTitle ?></h1>
  <?php include('includes/competitions.php'); ?>
  <?php if ( isset($tournament) ) { ?>
    <div class="mt-3">
      <a class="btn btn-secondary" href="admin.php?page=racketmanager-admin&amp;subpage=competitions&amp;season=<?php echo $tournament->season ?>&amp;type=tournament&amp;tournamenttype=<?php echo $tournament->type ?>&amp;tournament=<?php echo $tournament->id ?>">Add Competitions</a>
    </div>
  <?php } ?>
</div>
