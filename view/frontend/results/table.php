<?php if (count($archives) > 0) { ?>
  <table class="tu-table tu-results-table">
    <thead>
      <tr>
        <?php if (isset($columns['avatar'])) { ?>
          <th class="tu-results-avatar">
            &nbsp;
          </th>
        <?php }
        if (isset($columns['rank'])) { ?>
          <th class="tu-results-rank">
            <?php _e('Rank', 'trainup'); ?>
          </th>
        <?php }
        if (isset($columns['user_name'])) { ?>
          <th class="tu-results-user-name">
            <?php echo tu()->config['trainees']['single']; ?>
          </th>
        <?php }
        if (isset($columns['mark'])) { ?>
          <th class="tu-results-mark">
            <?php _e('Mark', 'trainup'); ?>
          </th>
        <?php }
        if (isset($columns['marks'])) { ?>
          <th class="tu-results-marks">
            <?php _e('Marks', 'trainup'); ?>
          </th>
        <?php }
        if (isset($columns['out_of'])) { ?>
          <th class="tu-results-out-of">
            <?php _e('Out of', 'trainup'); ?>
          </th>
        <?php }
        if (isset($columns['percentage'])) { ?>
          <th class="tu-results-percentage">
            <?php _e('Percentage', 'trainup'); ?>
          </th>
        <?php }
        if (isset($columns['grade'])) { ?>
          <th class="tu-results-grade">
            <?php _e('Grade', 'trainup'); ?>
          </th>
        <?php } ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($archives as $i => $archive) { ?>
        <tr>
          <?php if (isset($columns['avatar'])) { ?>
            <td class="tu-avatar tu-results-avatar">
              <?php echo get_avatar($archive['user_id'], 64); ?>
            </td>
          <?php }
          if (isset($columns['rank'])) { ?>
            <td class="tu-results-rank">
              <?php echo isset($archive['rank']) ? $archive['rank'] : '&ndash;'; ?>
            </td>
          <?php }
          if (isset($columns['user_name'])) { ?>
            <td class="tu-results-user-name">
              <?php echo $archive['user_name']; ?>
            </td>
          <?php }
          if (isset($columns['mark'])) { ?>
            <td class="tu-results-mark">
              <?php echo $archive['mark']; ?>
            </td>
          <?php }
          if (isset($columns['marks'])) { ?>
            <td class="tu-results-marks">
              <?php echo $archive['marks']; ?>
            </td>
          <?php }
          if (isset($columns['out_of'])) { ?>
            <td class="tu-results-out-of">
              <?php echo $archive['out_of']; ?>
            </td>
          <?php }
          if (isset($columns['percentage'])) { ?>
            <td class="tu-results-percentage">
              <?php echo $archive['percentage']; ?>%
            </td>
          <?php }
          if (isset($columns['grade'])) { ?>
            <td class="tu-results-grade">
              <?php echo $archive['grade']; ?>
            </td>
          <?php } ?>
        </tr>
      <?php } ?>
    </tbody>
  </table>
<?php } ?>