<ul class="tu-bullet-list">
  <li>
    <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>#tu-user-archive-<?php echo $archive['id'] ?>">
      <?php _e('View in archive', 'trainup'); ?>&nbsp;&raquo;
    </a>
  </li>
  <?php if ($is_admin) { ?>
    <li>
      <a href="post.php?post=<?php echo $test->ID; ?>&amp;action=edit">
        <?php printf(__('Edit %1$s', 'trainup'), $_test); ?>&nbsp;&raquo;
      </a>
    </li>
    <li>
      <a href="user-edit.php?user_id=<?php echo $user->ID; ?>">
        <?php printf(__('Edit %1$s', 'trainup'), $_trainee); ?>&nbsp;&raquo;
      </a>
    </li>
  <?php } ?>
</ul>
