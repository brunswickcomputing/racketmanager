Hello <?php echo $vm->name; ?>!<?php if (isset($extra)) echo " Extra: $extra"; ?> Renderer: <?php echo isset($renderer) ? "OK" : "FAIL"; ?>
