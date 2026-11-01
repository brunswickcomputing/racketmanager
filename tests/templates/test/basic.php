Hello <?php echo $vm->name; ?>!<?php if (isset($vars["extra"])) echo " Extra: {$vars["extra"]}"; ?> Renderer: <?php echo isset($renderer) ? "OK" : "FAIL"; ?>
