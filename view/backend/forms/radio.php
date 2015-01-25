<?php foreach ($options as $option_value => $option_title) { ?>
<label>
  <input type="radio" name="<?php echo $name; ?>" value="<?php echo $option_value; ?>"<?php
    echo $option_value == $value ? ' checked' : '';
  ?>>
  <?php echo $option_title; ?>
</label>
&nbsp;
<?php } ?>