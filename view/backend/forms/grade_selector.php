<script type="text/html" id="grade_template">
  <tr class="tu-grade">
    <td class="tu-grade-description">
      <input type="text" name="tu_settings_tests[grades][<%=i%>][description]" value="">
    </td>
    <td class="tu-grade-percentage">
      <input type="number" min="0" max="100" step="1" name="tu_settings_tests[grades][<%=i%>][percentage]" value="<%=percentage%>">
    </td>
    <td class="tu-grade-remove">
      <a href="#" class="tu-remove">&times;</a>
    </td>
  </tr>
</script>

<table class="tu-table tu-grades<?php echo $small ? ' tu-grades-small' : ''; ?>">
  <thead>
    <tr>
      <th class="tu-grade-description">
        <?php _e('Description', 'trainup'); ?>
      </th>
      <th class="tu-grade-percentage">
        <?php echo $small ? '%' : __('Percentage', 'trainup'); ?>
      </th>
      <?php if (!$disabled) { ?>
        <th class="tu-grade-remove">&nbsp;</th>
      <?php } ?>
    </tr>
  </thead>
  <tbody class="th-grades">
    <?php $i=0; foreach ($grades as $i => $grade) { ?>
      <tr class="tu-grade">
        <td class="tu-grade-description">
          <input type="text" name="tu_settings_tests[grades][<?php echo $i; ?>][description]"
          value="<?php echo isset($grade['description']) ? $grade['description'] : ''; ?>"
          placeholder="<?php
            echo $i === 0 ? __('Fail', 'trainup') : '',
                 $i === 1 ? __('Pass', 'trainup') : ''?>"
          <?php echo $disabled ? ' disabled' : ''; ?>>
        </td>
        <td class="tu-grade-percentage">
          <input type="number" min="0" max="100" step="1"
            name="tu_settings_tests[grades][<?php echo $i; ?>][percentage]"
            value="<?php echo isset($grade['percentage']) && $i > 0 ? $grade['percentage'] : ''; ?>"
            <?php echo $disabled || $i === 0 ? ' disabled' : ''; ?>>
        </td>
        <?php if (!$disabled) { ?>
          <td class="tu-grade-remove">
            <?php if ($i > 1) { ?>
              <a href="#" class="tu-remove">&times;</a>
            <?php } else { ?>
              &nbsp;
            <?php } ?>
          </td>
        <?php } ?>
      </tr>
    <?php $i++; } ?>
  </tbody>
  <?php if (!$disabled) { ?>
    <tfoot>
      <tr>
        <td>
          <span class="tu-plus">+</span>
          <a href="#" class="tu-add-new-grade">
            <?php _e('Add another grade', 'trainup') ?>
          </a>
        </td>
        <td colspan="2"></td>
      </tr>
    </tfoot>
  <?php } ?>
</table>