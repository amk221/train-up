<table class="tu-table tu-results tu-results-small">
  <tr>
    <th><?php _e('Mark', 'trainup'); ?></th>
    <th><?php _e('Out of', 'trainup'); ?></th>
    <th><?php _e('Percentage', 'trainup'); ?></th>
    <th><?php _e('Grade', 'trainup'); ?></th>
    <th><?php _e('Passed', 'trainup'); ?></th>
    <?php if ($resitable) { ?>
      <th><?php _e('Resit', 'trainup'); ?></th>
    <?php } ?>
  </tr>
  <tr>
    <td><?php echo $mark; ?></td>
    <td><?php echo $out_of; ?></td>
    <td class="tu-result-percentage" title="<?php _e('Click to manually set a percentage', 'trainup'); ?>">
      <span><?php echo $percentage; ?></span>%
    </td>
    <td class="tu-result-grade"><?php echo $grade; ?></td>
    <td class="tu-result-passed" data-yes="<?php _e('Yes', 'trainup'); ?>" data-no="<?php _e('No', 'trainup'); ?>">
      <span class="tu-passed-<?php echo $passed; ?>">
        <?php echo $passed ? __('Yes', 'trainup') : __('No', 'trainup'); ?>
      </span>
    </td>
    <?php if ($resitable) { ?>
      <td><?php echo $resit_count ? "#{$resit_count}" : '&ndash;'; ?></td>
    <?php } ?>
  </tr>
</table>