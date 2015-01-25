<ul class="tu-bullet-list">
  <li>
    <a href="edit.php?post_type=tu_result_<?php echo $test->ID; ?>">
      <?php _e('View results', 'trainup'); ?>&nbsp;&raquo;
    </a>
  </li>
  <li>
    <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>">
     <?php _e('View archive', 'trainup'); ?>&nbsp;&raquo;
    </a>
  </li>
  <li>
    <a href="users.php?role=tu_trainee&s=taking_<?php echo $_test; ?>:<?php echo $test->ID; ?>">
     <?php printf(__('View active %1$s', 'trainup'), $_trainees); ?>&nbsp;&raquo;
    </a>
  </li>
  <li>
    <a href="post.php?post=<?php echo $level->ID; ?>&amp;action=edit">
      <?php printf(__('Edit %1$s', 'trainup'), $_level); ?>&nbsp;&raquo;
    </a>
  </li>
</ul>
