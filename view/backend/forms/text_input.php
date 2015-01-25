<?php $validator->error($slug); ?>

<input type="text" name="<?php echo $name; ?>" value="<?php echo $value; ?>"<?php
  echo isset($size) ? " size='{$size}'" : '';
?>>
<?php if (isset($help)) { ?>
  <span class="description"><?php echo $help; ?></span>
<?php } ?>