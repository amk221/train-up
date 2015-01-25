<div class="tu-image-browser<?php
  echo !empty($value) ? ' tu-has-image' : '';
?>">
  <button class="tu-image-browse button">
    <?php _e('Browse', 'trainup'); ?>
  </button>
  <input type="hidden" name="<?php echo $name; ?>" value="<?php echo $value; ?>">

  <div class="tu-uploaded-image">
    <img src="<?php echo $value; ?>">
  </div>
</div>