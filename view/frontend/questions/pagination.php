<nav class="tu-pagination tu-question-pagination">
  <?php if (!empty($prev)) { ?>
    <a class="tu-prev tu-prev-question" href="<?php echo $prev->url; ?>"><?php
      echo '« ' . __('Prev', 'trainup')
     ?></a>
  <?php } ?>

  <a class="tu-next tu-next-question" href="<?php echo $next->url; ?>"><?php
    echo __('Next', 'trainup') . ' »'
   ?></a>
</nav>
