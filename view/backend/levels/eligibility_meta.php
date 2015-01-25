<p>
  <select name="tu_eligibility[]" multiple>
    <?php foreach ($levels as $l) {
      $test = $l->get_test(array('post_status' => 'any'));
      ?>
      <option value="<?php echo $test ? $test->ID : ''; ?>" <?php
        if (!$test || $l->ID == $level->ID) {
          echo 'disabled';
        } else {
          echo in_array($test->ID, $test_ids) ? 'selected' : '';
        }
      ?>>
        <?php echo str_repeat('&nbsp;', $l->depth * 2), $l->post_title; ?>
      </option>
    <?php } ?>
  </select>
</p>