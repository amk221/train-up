<?php $validator->error($slug); ?>

<select name="<?php echo $name; ?>">
  <?php foreach ($options as $option_value => $option_title) { ?>
    <option value="<?php echo $option_value; ?>"<?php
      echo $option_value == $value ? ' selected' : '';
    ?>>
      <?php echo $option_title; ?>
    </option>
  <?php } ?>
</select>