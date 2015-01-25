<nav class="tu-pagination tu-resource-pagination">
  <?php if (!empty($prev)) { ?>
    <a class="tu-prev tu-prev-resource" href="<?php echo $prev->url; ?>"><?php
      echo '« ' . __('Prev', 'trainup')
     ?></a>
  <?php } ?>

  <a class="tu-next tu-next-resource" href="<?php echo $next->url; ?>"><?php
    echo __('Next', 'trainup') . ' »'
   ?></a>
</nav>