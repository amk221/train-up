<ul class="tu-bullet-list">
  <li>
    <a href="edit.php?post_type=tu_resource_<?php echo $level->ID; ?>">
      <?php printf(__('View %1$s', 'trainup'), $_resources); ?>&nbsp;&raquo;
    </a>
  </li>
  <li>
    <?php if ($test) { ?>
      <a href="post.php?post=<?php echo $test->ID; ?>&action=edit">
        <?php printf(__('Edit %1$s', 'trainup'), $_test); ?>&nbsp;&raquo;
      </a>
    <?php } else { ?>
      <a href="post-new.php?post_type=tu_test&amp;tu_level_id=<?php echo $level->ID; ?>">
       <?php printf(__('Add %1$s', 'trainup'), $_test); ?>&nbsp;&raquo;
      </a>
    <?php } ?>
  </li>
</ul>
