<?php namespace TU; ?>

<div class="wrap">
  <?php if (count($performance) > 1) { ?>
    <div class="tu-visualisation-container">
      
      <div class="tu-visualisation" id="tu-user-performance"></div>
      <script>
      TU_RESULTS.userPerformance = <?php echo json_encode($performance); ?>
      </script>
    </div>
  <?php } ?>

  <a class="icon32 tu-avatar" href="user-edit.php?user_id=<?php echo $user->ID; ?>" title="<?php _e('Edit user', 'trainup'); ?>">
    <?php echo get_avatar($user->ID, 64); ?>
  </a>
  <h2>
    <?php
    printf(
      __('Archived %1$s results for %2$s', 'trainup'),
      strtolower($_test), $user->display_name
    );
    ?>
  </h2>

  <?php if (count($archives) > 0) { ?>
    <table class="tu-table tu-results tu-results-large tu-user-archive">
      <tr>
        <th class="tu-nesting">
          <a href="#" class="tu-toggle-nesting" title="<?php
            printf(__('Toggle %1$s parents', 'trainup'), strtolower($_test));
          ?>">&#8627;</a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;order_by=test_title&amp;order=<?php echo $flip_order; ?>">
            <?php echo $_test; ?>
          </a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;order_by=date_submitted&amp;order=<?php echo $flip_order; ?>">
            <?php _e('Date', 'trainup'); ?>
          </a>
        </th>
        <th>
          <?php _e('Time', 'trainup'); ?>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;order_by=duration&amp;order=<?php echo $flip_order; ?>">
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
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;order_by=percentage&amp;order=<?php echo $flip_order; ?>">
            <?php _e('Percentage', 'trainup'); ?>
          </a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;order_by=grade&amp;order=<?php echo $flip_order; ?>">
            <?php _e('Grade', 'trainup'); ?>
          </a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;order_by=passed&amp;order=<?php echo $flip_order; ?>">
            <?php _e('Passed', 'trainup'); ?>
          </a>
        </th>
        <th>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;order_by=resit_number&amp;order=<?php echo $flip_order; ?>">
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
        $test   = Tests::factory($archive['test_id']);
        $result = $user->get_result($test->ID);
      ?>
      <tr id="tu-user-archive-<?php echo $archive['id']; ?>">
        <td class="tu-nesting">
          <span>
            <?php if ($test->loaded()) {
              $parent_ids = array_reverse(get_post_ancestors($test->level->ID));
              foreach ($parent_ids as $post_id) {
                echo get_the_title($post_id), ' &rsaquo; ';
              }
            } ?>
          </span>
        </td>
        <td>
          <?php if ($test->loaded()) { ?>
            <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $archive['test_id']; ?>">
              <?php echo $archive['test_title']; ?>
            </a>
          <?php } else {
            echo $archive['test_title'];
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
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;tu_test_id=<?php echo $archive['test_id']; ?>&amp;tu_resit_number=<?php echo $archive['resit_number']; ?>">
            <?php _e('View', 'trainup'); ?>&nbsp;&raquo;
          </a>
        </td>
        <td>
          <?php if ($archive['latest'] && $result) { ?>
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
      <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $user->ID; ?>&amp;order_by=<?php echo $order_by; ?>&amp;order=<?php echo $order; ?>&amp;download=csv">
        <?php _e('Download as CSV', 'trainup'); ?> &darr;
      </a>
    </nav>

  <?php } else {
    tu()->message->error(__('No archived results to display', 'trainup'));
  } ?>
</div>