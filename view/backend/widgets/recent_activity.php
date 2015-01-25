<?php if (count($archives) > 0) { ?>
  <table class="tu-latest-activity">
    <tr>
      <th>
        &nbsp;
      </th>
      <th>
        <?php echo $_trainee; ?>
      </th>
      <th>
        <?php echo $_test; ?>
      </th>
      <th>
        %
      </th>
      <th>
        <?php _e('When', 'trainup'); ?>
      </th>
    </tr>
    <?php foreach ($archives as $archive) { ?>
      <tr>
        <td class="tu-avatar">
          <a href="user-edit.php?user_id=<?php echo $archive['user_id']; ?>">
            <?php echo get_avatar($archive['user_id'], 32); ?>
          </a>
        </td>
        <td>
          <a href="admin.php?page=tu_results&amp;tu_user_id=<?php echo $archive['user_id']; ?>#tu-user-archive-<?php echo $archive['id'] ?>">
            <?php echo $archive['user_name']; ?>
          </a>
        </td>
        <td>
          <a href="admin.php?page=tu_results&amp;tu_test_id=<?php echo $archive['test_id']; ?>">
            <?php echo $archive['test_title']; ?>
          </a>
        </td>
        <td>
          <?php echo $archive['percentage']; ?>%
        </td>
        <td>
          <?php
            printf(
              __('%1$s ago', 'trainup'),
              human_time_diff(mysql2date('U', $archive['date_submitted']), time())
            );
          ?>
        </td>
      </tr>
    <?php } ?>
  </table>
<?php } else { ?>
  <p><?php _e('No activity to show just yet!', 'trainup'); ?></p>
<?php } ?>