<div class="wrap">

  <a class="icon32 tu-avatar" href="user-edit.php?user_id=<?php echo $user_id; ?>" title="<?php _e('Edit user', 'trainup'); ?>">
    <?php echo get_avatar($user_id, 64); ?>
  </a>

  <h2>
    <?php
    if ($users_name) {
      printf(__('Archived answers for %1$s (%2$s)', 'trainup'), $users_name, $test_title);
    } else {
      printf(__('Archived answers for %1$s', 'trainup'), $test_title);
    }
    ?>
  </h2>

  <?php echo $answers; ?>
</div>