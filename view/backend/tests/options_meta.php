<p>
  <?php
  printf(
    __('Select which %1$s this %2$s is for:', 'trainup'),
    "<a href='edit.php?post_type=tu_level'>{$_level}</a>",
    $_test
  );
  ?>
</p>

<p>
  <select name="tu_level_id">
    <option value="">...</option>
    <?php foreach ($levels as $level) { ?>
      <option value="<?php echo $level->ID; ?>"<?php
        echo (
          isset($_REQUEST['tu_level_id']) &&
          $_REQUEST['tu_level_id'] == $level->ID
        ) || (
          $current_level && $current_level->ID == $level->ID
        ) ? ' selected' : '';
        echo (
          $level->test &&
          $level->ID != $current_level->ID
        ) ? ' disabled' : '';
      ?>>
        <?php echo str_repeat('&nbsp;', $level->depth * 2), $level->post_title; ?>
      </option>
    <?php } ?>
  </select>
</p>

<p>
  <?php _e('Number of allowed re-sit attempts:', 'trainup'); ?>
</p>

<p>
  <input type="text" name="tu_resit_attempts" value="<?php echo $test->resit_attempts; ?>" size="5" title="<?php _e('Enter -1 for unlimited resits', 'trainup'); ?>">
</p>

<p>
  <?php _e('Time limit:', 'trainup'); ?>
</p>

<p>
  <input type="text" pattern="[0-9]{2}:[0-9]{2}:[0-9]{2}" name="tu_time_limit" value="<?php echo $test->time_limit; ?>" placeholder="HH:MM:SS" size="8">
</p>

<p>
  <?php _e('Publish results:', 'trainup'); ?>
</p>

<p>
  <?php foreach ($post_stati as $status => $title) { ?>
    <label>
      <input type="radio" name="tu_result_status" value="<?php echo $status; ?>"<?php
        echo ($test->result_status === $status) ? ' checked' : '';
      ?>>
      <?php echo $title; ?>
    </label>
    &nbsp;
  <?php } ?>
</p>






