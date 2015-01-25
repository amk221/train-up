<?php namespace TU; ?>

<div class="wrap">
  <?php if (count($performance) > 2) { ?>
    <div class="tu-visualisation-container">
      <div class="tu-visualisation" id="tu-group-performance"></div>
      <script>
      TU_RESULTS.groupPerformance = <?php echo json_encode($performance); ?>
      </script>
    </div>
  <?php } ?>

  <a id="icon-tu_plugin" class="icon32 icon32-posts-tu_level" href="post.php?post=<?php echo $test->ID; ?>&amp;action=edit" title="<?php printf(__('Edit %1$s', 'trainup'), $_test); ?>">

  </a>
  <h2>
    <?php printf(__('Archived %1$s %2$s results', 'trainup'), $test->post_title, $_test); ?>

    <?php if ($group->loaded()) { ?>
      <a
        class="add-new-h2 tu-h2-label"
        title="<?php printf(__('Remove %1$s filter', 'trainup'), $_group); ?>"
        href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;order_by=<?php echo $order_by; ?>&amp;order=<?php echo $order; ?>">
        <span class="tu-h2-label-remove">&times;</span>
        <?php echo $group->post_title; ?>
      </a>
    <?php } ?>
  </h2>

  <?php if (count($archives) > 0) { ?>
    <table class="tu-table tu-results tu-results-large tu-test-archive">
      <tr>
        <th>
          &nbsp;
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;tu_group_id=<?php echo $group->ID; ?>&amp;order_by=user_name&amp;order=<?php echo $flip_order ?>">
            <?php _e('User', 'trainup'); ?>
          </a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;tu_group_id=<?php echo $group->ID; ?>&amp;order_by=date_submitted&amp;order=<?php echo $flip_order ?>">
            <?php _e('Date', 'trainup'); ?>
          </a>
        </th>
        <th>
          <?php _e('Time', 'trainup'); ?>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;tu_group_id=<?php echo $group->ID; ?>&amp;order_by=duration&amp;order=<?php echo $flip_order ?>">
            <?php _e('Duration', 'trainup'); ?>
          </a>
        </th>
        <th>
          <?php _e('Mark', 'trainup'); ?>
        </th>
        <th>
          <?php _e('Out of', 'trainup'); ?>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;tu_group_id=<?php echo $group->ID; ?>&amp;order_by=percentage&amp;order=<?php echo $flip_order ?>">
            <?php _e('Percentage', 'trainup'); ?>
          </a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;tu_group_id=<?php echo $group->ID; ?>&amp;order_by=grade&amp;order=<?php echo $flip_order ?>">
            <?php _e('Grade', 'trainup'); ?>
          </a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;tu_group_id=<?php echo $group->ID; ?>&amp;order_by=passed&amp;order=<?php echo $flip_order ?>">
            <?php _e('Passed', 'trainup'); ?>
          </a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;tu_group_id=<?php echo $group->ID; ?>&amp;order_by=resit_number&amp;order=<?php echo $flip_order ?>">
            <?php _e('Resit', 'trainup'); ?>
          </a>
        </th>
        <th>
          <?php _e('Answers', 'trainup'); ?>
        </th>
        <th>
          <?php _e('Result', 'trainup'); ?>
        </th>
      </tr>
      <?php foreach ($archives as $archive) {
        $user   = Users::factory($archive['user_id']);
        $result = $user->loaded() ? $user->get_result($test->ID) : null;
      ?>
      <tr class="tu-test-archive-<?php echo $archive['id']; ?>">
        <td class="tu-avatar">
          <a href="user-edit.php?user_id=<?php echo $archive['user_id']; ?>">
            <?php echo get_avatar($archive['user_id'], 32); ?>
          </a>
        </td>
        <td>
          <?php if ($test->loaded() && $user->loaded()) { ?>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $archive['user_id']; ?>">
            <?php echo $user->display_name; ?>
          </a>
          <?php } else {
            echo $archive['user_name'];
          } ?>
        </td>
        <td>
          <?php echo date_i18n(get_option('date_format'), strtotime($archive['date_submitted'])); ?>
        </td>
        <td>
          <?php echo date_i18n(get_option('time_format'), strtotime($archive['date_submitted'])); ?>
        </td>
        <td>
          <?php echo empty($archive['duration']) ? '&ndash;' : human_time_diff(0, $archive['duration']); ?>
        </td>
        <td>
          <?php echo $archive['mark']; ?>
        </td>
        <td>
          <?php echo $archive['out_of']; ?>
        </td>
        <td>
          <?php echo $archive['percentage']; ?>%
        </td>
        <td>
          <?php echo $archive['grade']; ?>
        </td>
        <td>
          <span class="tu-passed-<?php echo $archive['passed']; ?>">
            <?php echo $archive['passed'] ? __('Yes', 'trainup') : __('No', 'trainup'); ?>
          </span>
        </td>
        <td>
          <?php echo $archive['resit_number'] ? "#{$archive['resit_number']}" : '&ndash;'; ?>
        </td>
        <td>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $archive['user_id']; ?>&amp;tu_test_id=<?php echo $archive['test_id']; ?>&amp;tu_resit_number=<?php echo $archive['resit_number']; ?>">
            <?php _e('View', 'trainup'); ?>&nbsp;&raquo;
          </a>
        </td>
        <td>
          <?php if ($result && $result->loaded()) { ?>
            <a href="post.php?post=<?php echo $result->ID; ?>&amp;action=edit">
              <?php _e('Edit', 'trainup'); ?>&nbsp;&raquo;
            </a>
          <?php } else { ?>
            &ndash;
          <?php } ?>
        </td>
      </tr>
      <?php } ?>
    </table>

    <nav class="tu-archive-menu">
      <a class="tu-delete-files" href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;delete_redundant_files=1">
        <?php _e('Delete redundant files', 'trainup'); ?>
      </a>
      <a class="tu-download-csv" href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $test->ID; ?>&amp;tu_group_id=<?php echo $group->ID; ?>&amp;order_by=<?php echo $order_by; ?>&amp;order=<?php echo $order; ?>&amp;download=csv">
        <?php _e('Download as CSV', 'trainup'); ?> &darr;
      </a>
    </nav>

  <?php } else {
    tu()->message->error(__('No archived results to display', 'trainup'));
  } ?>
</div>